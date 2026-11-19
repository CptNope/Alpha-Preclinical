<?php
/**
 * Content importer: loads the approved site (seed/*.json and seed/pages/*.html,
 * exported from the repo by tools/export_wp.py) into WordPress.
 *
 * Run once on staging:  wp alpha seed
 * Or: Tools > Alpha Preclinical setup (administrators only).
 * Safe to re-run: items are matched by slug and updated; with --skip-existing,
 * anything already present is left as the client edited it.
 *
 * Then, while the old Wix site is still online:  wp alpha migrate-pdfs
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read a seed JSON file.
 *
 * @param string $name File name in seed/.
 * @return array
 */
function alpha_seed_json( $name ) {
	$file = ALPHA_THEME_DIR . '/seed/' . $name;
	if ( ! file_exists( $file ) ) {
		return array();
	}
	$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
	return is_array( $data ) ? $data : array();
}

/**
 * Import a theme image into the media library once; returns the attachment ID.
 *
 * @param string $relative Path under assets/img/.
 * @param string $alt      Alt text.
 * @return int
 */
function alpha_seed_image( $relative, $alt = '' ) {
	static $cache = array();
	if ( isset( $cache[ $relative ] ) ) {
		return $cache[ $relative ];
	}
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_alpha_seed_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one-off import.
			'meta_value'     => $relative, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- one-off import.
		)
	);
	if ( $existing ) {
		$cache[ $relative ] = (int) $existing[0];
		return $cache[ $relative ];
	}
	$src = ALPHA_THEME_DIR . '/assets/img/' . $relative;
	if ( ! file_exists( $src ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( basename( $src ) );
	copy( $src, $tmp );
	$id = media_handle_sideload(
		array(
			'name'     => basename( $src ),
			'tmp_name' => $tmp,
		),
		0
	);
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	update_post_meta( $id, '_alpha_seed_source', $relative );
	if ( $alt ) {
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	}
	$cache[ $relative ] = (int) $id;
	return $cache[ $relative ];
}

/**
 * Swap {{img:file}} tokens for media-library URLs and add the image IDs.
 *
 * @param string $content Block markup.
 * @return string
 */
function alpha_seed_images_in( $content ) {
	return preg_replace_callback(
		'#<!-- wp:image (\{[^}]*\}) -->\s*<figure class="([^"]*)"><img src="\{\{img:([^}]+)\}\}" alt="([^"]*)"/>#',
		static function ( $m ) {
			$id = alpha_seed_image( $m[3], html_entity_decode( $m[4] ) );
			if ( ! $id ) {
				return $m[0];
			}
			$attrs       = json_decode( $m[1], true );
			$attrs['id'] = $id;
			$url         = wp_get_attachment_image_url( $id, 'large' );
			return '<!-- wp:image ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES ) . ' -->' . "\n"
				. '<figure class="' . $m[2] . '"><img src="' . esc_url( $url ) . '" alt="' . $m[4] . '" class="wp-image-' . $id . '"/>';
		},
		$content
	);
}

/**
 * Create or update a post by type + slug.
 *
 * @param array $postarr       wp_insert_post args (post_type and post_name required).
 * @param bool  $skip_existing Leave existing posts alone.
 * @return int Post ID (0 on failure, existing ID when skipped).
 */
function alpha_seed_upsert( $postarr, $skip_existing ) {
	$found = get_posts(
		array(
			'post_type'      => $postarr['post_type'],
			'name'           => $postarr['post_name'],
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'post_parent'    => isset( $postarr['post_parent'] ) ? $postarr['post_parent'] : '',
		)
	);
	if ( $found ) {
		if ( $skip_existing ) {
			return (int) $found[0];
		}
		$postarr['ID'] = (int) $found[0];
	}
	$postarr += array( 'post_status' => 'publish' );
	$id = wp_insert_post( wp_slash( $postarr ), true );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

/**
 * Run the import.
 *
 * @param array    $opts { skip_existing: bool }.
 * @param callable $log  Logger.
 * @return array Counts.
 */
function alpha_seed_run( $opts = array(), $log = null ) {
	$skip  = ! empty( $opts['skip_existing'] );
	$log   = is_callable( $log ) ? $log : static function () {};
	$count = array();

	kses_remove_filters(); // Block comments and bindings must survive unfiltered for the importer only.

	// Team.
	foreach ( alpha_seed_json( 'team.json' ) as $p ) {
		$id = alpha_seed_upsert(
			array(
				'post_type'    => 'team_member',
				'post_name'    => $p['slug'],
				'post_title'   => $p['name'],
				'post_excerpt' => $p['excerpt'],
				'post_content' => $p['content'],
				'menu_order'   => (int) $p['menu_order'],
			),
			$skip
		);
		if ( ! $id ) {
			continue;
		}
		foreach ( array( 'credentials', 'job_title', 'short_title', 'linkedin', 'pubmed_ids', 'leads', 'highlights', 'education' ) as $k ) {
			update_post_meta( $id, $k, $p[ $k ] );
		}
		$photo = alpha_seed_image( $p['photo'], $p['name'] );
		if ( $photo ) {
			set_post_thumbnail( $id, $photo );
		}
		$alt = alpha_seed_image( $p['alt_photo'], '' );
		if ( $alt ) {
			update_post_meta( $id, 'alt_photo', $alt );
		}
		$count['team'] = isset( $count['team'] ) ? $count['team'] + 1 : 1;
	}
	$log( 'Team: ' . ( isset( $count['team'] ) ? $count['team'] : 0 ) );

	// Publications.
	foreach ( alpha_seed_json( 'publications.json' ) as $p ) {
		$id = alpha_seed_upsert(
			array(
				'post_type'  => 'publication',
				'post_name'  => sanitize_title( wp_trim_words( $p['title'], 10, '' ) ),
				'post_title' => $p['title'],
				'menu_order' => (int) $p['menu_order'],
			),
			$skip
		);
		if ( ! $id ) {
			continue;
		}
		foreach ( array( 'authors', 'alpha_authors', 'journal', 'volume', 'issue', 'pages', 'issue_date', 'epub_date', 'doi', 'pmid', 'pmcid', 'legacy_pdf' ) as $k ) {
			update_post_meta( $id, $k, $p[ $k ] );
		}
		if ( ! get_post_meta( $id, 'pdf_url', true ) ) {
			update_post_meta( $id, 'pdf_url', $p['pdf_url'] );
		}
		update_post_meta( $id, 'featured', ! empty( $p['featured'] ) );
		wp_set_object_terms( $id, $p['area'], 'research_area' );
		$count['publications'] = isset( $count['publications'] ) ? $count['publications'] + 1 : 1;
	}
	$log( 'Publications: ' . ( isset( $count['publications'] ) ? $count['publications'] : 0 ) );

	// Open roles.
	foreach ( alpha_seed_json( 'jobs.json' ) as $j ) {
		$id = alpha_seed_upsert(
			array(
				'post_type'  => 'job',
				'post_name'  => sanitize_title( $j['title'] ),
				'post_title' => $j['title'],
				'menu_order' => (int) $j['menu_order'],
			),
			$skip
		);
		if ( ! $id ) {
			continue;
		}
		foreach ( array( 'summary', 'requirements', 'employment_type', 'location' ) as $k ) {
			update_post_meta( $id, $k, $j[ $k ] );
		}
		wp_set_object_terms( $id, $j['team'], 'job_team' );
		$count['jobs'] = isset( $count['jobs'] ) ? $count['jobs'] + 1 : 1;
	}
	$log( 'Roles: ' . ( isset( $count['jobs'] ) ? $count['jobs'] : 0 ) );

	// Pages (parents first: the list is ordered that way).
	$ids = array();
	foreach ( alpha_seed_json( 'pages.json' ) as $p ) {
		$file    = ALPHA_THEME_DIR . '/seed/pages/' . $p['slug'] . '.html';
		$content = file_exists( $file ) ? alpha_seed_images_in( (string) file_get_contents( $file ) ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
		$parent  = '' !== $p['parent'] && isset( $ids[ $p['parent'] ] ) ? $ids[ $p['parent'] ] : 0;
		$id      = alpha_seed_upsert(
			array(
				'post_type'    => 'page',
				'post_name'    => $p['slug'],
				'post_title'   => $p['title'],
				'post_excerpt' => $p['excerpt'],
				'post_content' => $content,
				'post_parent'  => $parent,
				'menu_order'   => (int) $p['menu_order'],
			),
			$skip
		);
		if ( ! $id ) {
			continue;
		}
		$ids[ $p['slug'] ] = $id;
		update_post_meta( $id, 'short_title', $p['short_title'] );
		update_post_meta( $id, '_wp_page_template', $p['template'] ? $p['template'] : 'default' );
		$count['pages'] = isset( $count['pages'] ) ? $count['pages'] + 1 : 1;
	}
	$log( 'Pages: ' . ( isset( $count['pages'] ) ? $count['pages'] : 0 ) );

	// Blog posts, as drafts: each author reviews and adds a first-hand detail before publishing.
	foreach ( alpha_seed_json( 'posts.json' ) as $p ) {
		$cat = term_exists( $p['category'], 'category' );
		if ( ! $cat ) {
			$cat = wp_insert_term( $p['category'], 'category' );
		}
		$id = alpha_seed_upsert(
			array(
				'post_type'     => 'post',
				'post_name'     => $p['slug'],
				'post_title'    => $p['title'],
				'post_excerpt'  => $p['excerpt'],
				'post_content'  => alpha_seed_images_in( $p['content'] ),
				'post_status'   => 'draft',
				'post_category' => is_array( $cat ) ? array( (int) $cat['term_id'] ) : array(),
			),
			$skip
		);
		if ( ! $id ) {
			continue;
		}
		update_post_meta( $id, 'author_member', $p['author_slug'] );
		// SEO plugin fields (whichever is installed reads its own keys).
		update_post_meta( $id, '_yoast_wpseo_title', $p['seo_title'] );
		update_post_meta( $id, '_yoast_wpseo_metadesc', $p['seo_description'] );
		update_post_meta( $id, '_yoast_wpseo_focuskw', $p['focus_keyword'] );
		update_post_meta( $id, 'rank_math_title', $p['seo_title'] );
		update_post_meta( $id, 'rank_math_description', $p['seo_description'] );
		update_post_meta( $id, 'rank_math_focus_keyword', $p['focus_keyword'] );
		if ( $p['hero'] ) {
			$img = alpha_seed_image( $p['hero'], $p['title'] );
			if ( $img ) {
				set_post_thumbnail( $id, $img );
			}
		}
		$count['posts'] = isset( $count['posts'] ) ? $count['posts'] + 1 : 1;
	}
	$log( 'Blog drafts: ' . ( isset( $count['posts'] ) ? $count['posts'] : 0 ) );

	kses_init_filters();

	// Reading settings, privacy page, permalinks.
	if ( isset( $ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
	}
	if ( isset( $ids['blog'] ) ) {
		update_option( 'page_for_posts', $ids['blog'] );
	}
	if ( isset( $ids['privacy-policy'] ) ) {
		update_option( 'wp_page_for_privacy_policy', $ids['privacy-policy'] );
	}
	update_option( 'blogname', 'Alpha Preclinical' );
	update_option( 'blogdescription', alpha_site( 'tagline' ) );
	update_option( 'permalink_structure', '/blog/%postname%/' );
	flush_rewrite_rules();
	$log( 'Settings: static front page, blog at /blog/, permalinks /blog/%postname%/.' );

	return $count;
}

/**
 * Move each paper's PDF from the old Wix site into the media library.
 * Run while the Wix site is still online. Confirm the client may host publisher PDFs;
 * for any paper it may not, clear the PDF URL and the DOI link is used instead.
 *
 * @param callable $log Logger.
 * @return int Number moved.
 */
function alpha_migrate_pdfs( $log = null ) {
	$log = is_callable( $log ) ? $log : static function () {};
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$moved = 0;
	$papers = get_posts(
		array(
			'post_type'      => 'publication',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		)
	);
	foreach ( $papers as $paper ) {
		$url = (string) get_post_meta( $paper->ID, 'pdf_url', true );
		if ( false === strpos( $url, '/_files/ugd/' ) ) {
			continue;
		}
		$tmp = download_url( $url, 60 );
		if ( is_wp_error( $tmp ) ) {
			$log( 'Could not download: ' . $url . ' (' . $tmp->get_error_message() . ')' );
			continue;
		}
		$id = media_handle_sideload(
			array(
				'name'     => sanitize_file_name( wp_trim_words( $paper->post_title, 8, '' ) ) . '.pdf',
				'tmp_name' => $tmp,
			),
			$paper->ID,
			$paper->post_title
		);
		if ( is_wp_error( $id ) ) {
			wp_delete_file( $tmp );
			$log( 'Could not import: ' . $url );
			continue;
		}
		update_post_meta( $paper->ID, 'pdf_url', wp_get_attachment_url( $id ) );
		++$moved;
		$log( 'Moved: ' . $paper->post_title );
	}
	return $moved;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Alpha Preclinical site setup.
	 */
	class Alpha_CLI {
		/**
		 * Import the approved content (team, papers, roles, pages, blog drafts) and set reading options.
		 *
		 * ## OPTIONS
		 *
		 * [--skip-existing]
		 * : Leave items that already exist untouched.
		 *
		 * @param array $args       Positional args.
		 * @param array $assoc_args Flags.
		 */
		public function seed( $args, $assoc_args ) {
			alpha_seed_run(
				array( 'skip_existing' => ! empty( $assoc_args['skip-existing'] ) ),
				static function ( $m ) {
					WP_CLI::log( $m );
				}
			);
			WP_CLI::success( 'Content imported.' );
		}

		/**
		 * Copy publication PDFs from the old Wix site into the media library.
		 *
		 * @subcommand migrate-pdfs
		 */
		public function migrate_pdfs() {
			$n = alpha_migrate_pdfs(
				static function ( $m ) {
					WP_CLI::log( $m );
				}
			);
			WP_CLI::success( $n . ' PDFs moved.' );
		}
	}
	WP_CLI::add_command( 'alpha', 'Alpha_CLI' );
}

/**
 * Tools > Alpha Preclinical setup: the same two actions for administrators.
 */
function alpha_setup_menu() {
	add_management_page( __( 'Alpha Preclinical setup', 'alpha-preclinical' ), __( 'Alpha Preclinical setup', 'alpha-preclinical' ), 'manage_options', 'alpha-setup', 'alpha_render_setup_page' );
}
add_action( 'admin_menu', 'alpha_setup_menu' );

/**
 * Render the setup page.
 */
function alpha_render_setup_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$done = isset( $_GET['alpha_done'] ) ? sanitize_key( wp_unslash( $_GET['alpha_done'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- status message only.
	echo '<div class="wrap"><h1>' . esc_html__( 'Alpha Preclinical setup', 'alpha-preclinical' ) . '</h1>';
	if ( $done ) {
		echo '<div class="notice notice-success"><p>' . esc_html( 'pdfs' === $done ? __( 'PDF migration finished. Check each publication’s PDF link.', 'alpha-preclinical' ) : __( 'Content imported. Blog posts are drafts for author review.', 'alpha-preclinical' ) ) . '</p></div>';
	}
	echo '<p>' . esc_html__( 'Import the approved content: team, 16 publications, open roles, all pages and the 7 blog drafts. Existing items with the same slug are updated unless you tick the box.', 'alpha-preclinical' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'alpha_seed', 'alpha_seed_nonce' );
	echo '<input type="hidden" name="action" value="alpha_seed">';
	echo '<p><label><input type="checkbox" name="skip_existing" value="1" checked> ' . esc_html__( 'Leave existing items untouched', 'alpha-preclinical' ) . '</label></p>';
	submit_button( __( 'Import content', 'alpha-preclinical' ), 'primary', 'submit', false );
	echo '</form><hr>';
	echo '<p>' . esc_html__( 'While the old Wix site is still online, copy every publication PDF into the media library.', 'alpha-preclinical' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'alpha_pdfs', 'alpha_pdfs_nonce' );
	echo '<input type="hidden" name="action" value="alpha_pdfs">';
	submit_button( __( 'Migrate PDFs', 'alpha-preclinical' ), 'secondary', 'submit', false );
	echo '</form></div>';
}

/**
 * Import handler: nonce + capability.
 */
function alpha_handle_seed() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'alpha-preclinical' ), 403 );
	}
	check_admin_referer( 'alpha_seed', 'alpha_seed_nonce' );
	alpha_seed_run( array( 'skip_existing' => ! empty( $_POST['skip_existing'] ) ) );
	wp_safe_redirect( admin_url( 'tools.php?page=alpha-setup&alpha_done=seed' ) );
	exit;
}
add_action( 'admin_post_alpha_seed', 'alpha_handle_seed' );

/**
 * PDF migration handler: nonce + capability.
 */
function alpha_handle_pdfs() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'alpha-preclinical' ), 403 );
	}
	check_admin_referer( 'alpha_pdfs', 'alpha_pdfs_nonce' );
	alpha_migrate_pdfs();
	wp_safe_redirect( admin_url( 'tools.php?page=alpha-setup&alpha_done=pdfs' ) );
	exit;
}
add_action( 'admin_post_alpha_pdfs', 'alpha_handle_pdfs' );
