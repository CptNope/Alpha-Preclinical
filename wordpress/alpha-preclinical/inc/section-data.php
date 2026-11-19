<?php
/**
 * Sections fed by live content. The design's markup is kept; the values come from
 * the records editors maintain (and the content review page updates).
 *
 * - Team cards (home, About): name and card title from each team member.
 * - Team page cards: name and job title via the Block Bindings API (in the seed).
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Team member by slug.
 *
 * @param string $slug Slug.
 * @return WP_Post|null
 */
function alpha_member_by_slug( $slug ) {
	static $cache = array();
	if ( ! array_key_exists( $slug, $cache ) ) {
		$found          = get_posts(
			array(
				'post_type'      => 'team_member',
				'name'           => $slug,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
			)
		);
		$cache[ $slug ] = $found ? $found[0] : null;
	}
	return $cache[ $slug ];
}

/**
 * Card text ("<b>Name, DVM</b><small>Title</small>") from the team member record.
 *
 * @param array  $item     Loop item.
 * @param string $url_key  Field holding the /team/#slug link.
 * @param string $title    Meta key for the title shown.
 * @return array
 */
function alpha_member_card_item( $item, $url_key, $title ) {
	$url = isset( $item[ $url_key ] ) ? (string) $item[ $url_key ] : '';
	if ( ! preg_match( '/#([a-z0-9-]+)$/', $url, $m ) ) {
		return $item;
	}
	$p = alpha_member_by_slug( $m[1] );
	if ( ! $p ) {
		return $item;
	}
	$t = (string) get_post_meta( $p->ID, $title, true );
	if ( '' === $t ) {
		$t = (string) get_post_meta( $p->ID, 'job_title', true );
	}
	$item['div'] = '<b>' . esc_html( alpha_person_name( $p ) ) . '</b><small>' . esc_html( $t ) . '</small>';
	return $item;
}

/**
 * Live values for section blocks.
 *
 * @param array    $attributes Attributes.
 * @param WP_Block $block      Block.
 * @return array
 */
function alpha_section_live_data( $attributes, $block ) {
	switch ( $block->name ) {
		case 'alpha/home-team':
			foreach ( (array) ( $attributes['member_items'] ?? array() ) as $i => $item ) {
				$attributes['member_items'][ $i ] = alpha_member_card_item( (array) $item, 'member_url', 'short_title' );
			}
			break;
		case 'alpha/about-leadership':
			foreach ( (array) ( $attributes['leader_items'] ?? array() ) as $i => $item ) {
				$attributes['leader_items'][ $i ] = alpha_member_card_item( (array) $item, 'leader_url', 'job_title' );
			}
			break;
	}
	return $attributes;
}
add_filter( 'alpha_section_data', 'alpha_section_live_data', 10, 2 );

/**
 * Published papers in display order (Publications › drag order / menu order).
 *
 * @return WP_Post[]
 */
function alpha_publications_ordered() {
	static $list = null;
	if ( null === $list ) {
		$list = get_posts(
			array(
				'post_type'      => 'publication',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'date'       => 'DESC',
				),
			)
		);
	}
	return $list;
}

/**
 * A paper's research area (first term).
 *
 * @param int $id Post ID.
 * @return string
 */
function alpha_paper_area( $id ) {
	$terms = get_the_terms( $id, 'research_area' );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
}

/**
 * Citation line: "<i>Journal</i>. 2019 Apr;27(4):878–889. Epub 2019 Mar 5."
 *
 * @param int $id Post ID.
 * @return string
 */
function alpha_paper_cite( $id ) {
	$m     = static function ( $k ) use ( $id ) {
		return (string) get_post_meta( $id, $k, true );
	};
	$cite  = '<i>' . esc_html( $m( 'journal' ) ) . '</i>. ' . esc_html( $m( 'issue_date' ) ) . ';' . esc_html( $m( 'volume' ) );
	$cite .= '' !== $m( 'issue' ) ? '(' . esc_html( $m( 'issue' ) ) . ')' : '';
	$cite .= ':' . esc_html( $m( 'pages' ) ) . '.';
	$cite .= '' !== $m( 'epub_date' ) ? ' Epub ' . esc_html( $m( 'epub_date' ) ) . '.' : '';
	return $cite;
}

/**
 * Where a paper's title links: the PDF, else the DOI, else PubMed.
 *
 * @param int $id Post ID.
 * @return array{url:string,pdf:bool}
 */
function alpha_paper_link( $id ) {
	$pdf = (string) get_post_meta( $id, 'pdf_url', true );
	if ( '' !== $pdf ) {
		return array(
			'url' => $pdf,
			'pdf' => true,
		);
	}
	$doi  = (string) get_post_meta( $id, 'doi', true );
	$pmid = (string) get_post_meta( $id, 'pmid', true );
	return array(
		'url' => $doi ? 'https://doi.org/' . $doi : ( $pmid ? 'https://pubmed.ncbi.nlm.nih.gov/' . $pmid . '/' : get_permalink( $id ) ),
		'pdf' => false,
	);
}

/**
 * One paper on the Publications page, in the design's markup.
 *
 * @param WP_Post $p Paper.
 * @return string
 */
function alpha_paper_item( $p ) {
	$id    = $p->ID;
	$t     = get_the_title( $p );
	$m     = static function ( $k ) use ( $id ) {
		return (string) get_post_meta( $id, $k, true );
	};
	$link  = alpha_paper_link( $id );
	$ext   = ' rel="noopener" target="_blank"';
	$html  = '<li class="paper"><h3><a aria-label="' . esc_attr( $t . ( $link['pdf'] ? ' (PDF, opens in new tab)' : ' (opens in new tab)' ) ) . '" class="t" href="' . esc_url( $link['url'] ) . '"' . $ext . '>' . esc_html( $t ) . '</a></h3>';
	$html .= '<p class="by">' . esc_html( $m( 'authors' ) ) . '</p><p class="cite">' . alpha_paper_cite( $id ) . '</p><dl class="ids">';
	if ( $m( 'doi' ) ) {
		$html .= '<div><dt>DOI</dt><dd><a href="' . esc_url( 'https://doi.org/' . $m( 'doi' ) ) . '"' . $ext . '>' . esc_html( $m( 'doi' ) ) . '</a></dd></div>';
	}
	if ( $m( 'pmid' ) ) {
		$html .= '<div><dt>PMID</dt><dd>' . esc_html( $m( 'pmid' ) ) . '</dd></div>';
	}
	if ( $m( 'pmcid' ) ) {
		$html .= '<div><dt>PMCID</dt><dd>' . esc_html( $m( 'pmcid' ) ) . '</dd></div>';
	}
	$alpha = $m( 'alpha_authors' );
	$html .= '</dl><p class="meta"><span class="alpha">' . esc_html__( 'Alpha authors:', 'alpha-preclinical' ) . ' ' . esc_html( '' !== $alpha ? $alpha : __( 'None listed [confirm role]', 'alpha-preclinical' ) ) . '</span></p><div class="links">';
	if ( $link['pdf'] ) {
		/* translators: %s: paper title. */
		$html .= '<a aria-label="' . esc_attr( sprintf( __( 'Read PDF: %s (opens in new tab)', 'alpha-preclinical' ), $t ) ) . '" class="pdf" href="' . esc_url( $link['url'] ) . '"' . $ext . '>' . esc_html__( 'Read PDF', 'alpha-preclinical' ) . '</a>';
	}
	if ( $m( 'pmid' ) ) {
		/* translators: %s: paper title. */
		$html .= '<a aria-label="' . esc_attr( sprintf( __( 'PubMed record: %s (opens in new tab)', 'alpha-preclinical' ), $t ) ) . '" class="pdf nih" href="' . esc_url( 'https://pubmed.ncbi.nlm.nih.gov/' . $m( 'pmid' ) . '/' ) . '"' . $ext . '>PubMed</a>';
	}
	if ( $m( 'pmcid' ) ) {
		/* translators: %s: paper title. */
		$html .= '<a aria-label="' . esc_attr( sprintf( __( 'Free full text on PubMed Central: %s (opens in new tab)', 'alpha-preclinical' ), $t ) ) . '" class="pdf nih" href="' . esc_url( 'https://pmc.ncbi.nlm.nih.gov/articles/' . $m( 'pmcid' ) . '/' ) . '"' . $ext . '>' . esc_html__( 'Free full text', 'alpha-preclinical' ) . '</a>';
	}
	return $html . '</div></li>';
}

/**
 * Filters and area groups for the Publications page.
 *
 * @return string
 */
function alpha_publications_groups_html() {
	$groups = array();
	foreach ( alpha_publications_ordered() as $p ) {
		$groups[ alpha_paper_area( $p->ID ) ][] = $p;
	}
	$total   = count( alpha_publications_ordered() );
	$filters = '<div class="filters" data-filter-group="pubs" data-noun="paper" role="group" aria-label="' . esc_attr__( 'Filter by research area', 'alpha-preclinical' ) . '">'
		. "\n      " . '<button type="button" data-filter="All" aria-pressed="true">' . esc_html__( 'All', 'alpha-preclinical' ) . ' <small>' . $total . '</small></button>';
	$body    = '';
	foreach ( $groups as $area => $papers ) {
		$filters .= "\n        " . '<button type="button" data-filter="' . esc_attr( $area ) . '" aria-pressed="false">' . esc_html( $area ) . ' <small>' . count( $papers ) . '</small></button>';
		/* translators: %d: number of papers. */
		$count = sprintf( _n( '%d paper', '%d papers', count( $papers ), 'alpha-preclinical' ), count( $papers ) );
		$body .= "\n    " . '<section class="group" data-item="pubs" data-topic="' . esc_attr( $area ) . '"><div><h2>' . esc_html( $area ) . '</h2><p class="count">' . esc_html( $count ) . '</p></div><ol class="list">';
		foreach ( $papers as $p ) {
			$body .= alpha_paper_item( $p );
		}
		$body .= '</ol></section>';
	}
	return $filters . "\n    </div>" . $body;
}

/**
 * Publications page and homepage papers come from Publications.
 *
 * @param string   $html       Section markup.
 * @param array    $attributes Attributes.
 * @param WP_Block $block      Block.
 * @return string
 */
function alpha_section_publications_html( $html, $attributes, $block ) {
	if ( 'alpha/publications-list' !== $block->name || ! alpha_publications_ordered() ) {
		return $html;
	}
	$start = strpos( $html, '<div class="filters"' );
	$end   = strpos( $html, '<div class="band"' );
	if ( false === $start || false === $end ) {
		return $html;
	}
	// Keep the whitespace before the band as in the design.
	$tail = substr( $html, 0, $end );
	$gap  = substr( $tail, strlen( rtrim( $tail ) ) );
	return substr( $html, 0, $start ) . alpha_publications_groups_html() . $gap . substr( $html, $end );
}
add_filter( 'alpha_section_html', 'alpha_section_publications_html', 10, 3 );

/**
 * Homepage: the papers marked "Feature on the homepage", with Alpha authors in bold.
 *
 * @param array    $attributes Attributes.
 * @param WP_Block $block      Block.
 * @return array
 */
function alpha_section_publications_data( $attributes, $block ) {
	if ( 'alpha/home-publications' !== $block->name ) {
		return $attributes;
	}
	$all = alpha_publications_ordered();
	if ( ! $all ) {
		return $attributes;
	}
	$attributes['span_data_count'] = (string) count( $all );
	$attributes['span']            = (string) count( $all );
	$featured                      = array_values(
		array_filter(
			$all,
			static function ( $p ) {
				return (bool) get_post_meta( $p->ID, 'featured', true );
			}
		)
	);
	if ( ! $featured ) {
		return $attributes;
	}
	// Up to three featured papers, in the Publications order.
	$items = array();
	foreach ( array_slice( $featured, 0, 3 ) as $p ) {
		$t       = get_the_title( $p );
		$link    = alpha_paper_link( $p->ID );
		$year    = substr( (string) get_post_meta( $p->ID, 'issue_date', true ), 0, 4 );
		$items[] = array(
			'jr'   => '<span>' . esc_html( (string) get_post_meta( $p->ID, 'journal', true ) ) . '</span> ' . esc_html( $year ),
			'h3'   => '<a aria-label="' . esc_attr( $t . ( $link['pdf'] ? ' (PDF, opens in new tab)' : ' (opens in new tab)' ) ) . '" href="' . esc_url( $link['url'] ) . '" rel="noopener" target="_blank">' . esc_html( $t ) . '</a>',
			'by'   => alpha_bold_alpha_authors( $p->ID ),
			'area' => esc_html( alpha_paper_area( $p->ID ) ),
		);
	}
	$attributes['paper_col_items'] = $items;
	return $attributes;
}
add_filter( 'alpha_section_data', 'alpha_section_publications_data', 10, 2 );

/**
 * Author list with the Alpha scientists in bold (matched by surname).
 *
 * @param int $id Post ID.
 * @return string
 */
function alpha_bold_alpha_authors( $id ) {
	$list    = (string) get_post_meta( $id, 'authors_short', true );
	$authors = array_map( 'trim', explode( ',', '' !== $list ? $list : (string) get_post_meta( $id, 'authors', true ) ) );
	$alpha   = array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $id, 'alpha_authors', true ) ) ) );
	$surname = array();
	foreach ( $alpha as $name ) {
		$parts     = preg_split( '/\s+/', $name );
		$surname[] = strtolower( (string) end( $parts ) );
	}
	$out = array();
	foreach ( $authors as $a ) {
		$first = strtolower( (string) strtok( $a, ' ' ) );
		$out[] = in_array( $first, $surname, true ) ? '<b>' . esc_html( $a ) . '</b>' : esc_html( $a );
	}
	return implode( ', ', $out );
}

/**
 * Open roles (Careers › Open roles) in the design's accordion markup.
 *
 * @return array{filters:string,roles:string}|null
 */
function alpha_roles_html() {
	$jobs = get_posts(
		array(
			'post_type'      => 'job',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		)
	);
	if ( ! $jobs ) {
		return null;
	}
	$types = array(
		'FULL_TIME'  => __( 'Full-time, on-site', 'alpha-preclinical' ),
		'PART_TIME'  => __( 'Part-time, on-site', 'alpha-preclinical' ),
		'CONTRACTOR' => __( 'Contract', 'alpha-preclinical' ),
		'TEMPORARY'  => __( 'Temporary', 'alpha-preclinical' ),
		'INTERN'     => __( 'Internship', 'alpha-preclinical' ),
	);
	$teams = array();
	$roles = '';
	foreach ( $jobs as $i => $job ) {
		$terms = get_the_terms( $job->ID, 'job_team' );
		$team  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
		if ( $team && ! in_array( $team, $teams, true ) ) {
			$teams[] = $team;
		}
		$type  = (string) get_post_meta( $job->ID, 'employment_type', true );
		$where = (string) get_post_meta( $job->ID, 'location', true );
		$where = '' !== $where ? $where : 'Worcester, MA';
		$chips = ( $team ? '<span class="chip">' . esc_html( $team ) . '</span>' : '' )
			. '<span class="chip">' . esc_html( $types[ $type ] ?? ucwords( strtolower( str_replace( '_', ' ', $type ) ) ) ) . '</span>'
			. '<span class="chip">' . esc_html( $where ) . '</span>';
		$reqs  = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) get_post_meta( $job->ID, 'requirements', true ) ) ) );
		$title = get_the_title( $job );
		$open  = 0 === $i;
		$roles .= ( $i ? "\n        " : '' ) . '<li class="role" data-item="roles" data-topic="' . esc_attr( $team ) . '"><h3 class="role-h"><button class="role-btn" type="button" data-accordion aria-expanded="' . ( $open ? 'true' : 'false' ) . '" aria-controls="role-' . $i . '">'
			. '<span class="role-title">' . esc_html( $title ) . '</span><span class="role-meta">' . $chips . '</span><span class="ico" aria-hidden="true">' . ( $open ? '−' : '+' ) . '</span></button></h3>'
			. '<div class="role-body" id="role-' . $i . '"' . ( $open ? '' : ' hidden' ) . '><div><p>' . esc_html( (string) get_post_meta( $job->ID, 'summary', true ) ) . '</p>'
			. '<a class="btn btn-primary apply" href="#apply" data-apply-role="' . esc_attr( $title ) . '">' . esc_html__( 'Apply for this role', 'alpha-preclinical' ) . '</a></div>'
			. '<div><h4>' . esc_html__( 'What we\'re looking for', 'alpha-preclinical' ) . '</h4><ul>' . ( $reqs ? '<li>' . implode( '</li><li>', array_map( 'esc_html', $reqs ) ) . '</li>' : '' ) . '</ul></div></div></li>';
	}
	$filters = '<button type="button" data-filter="All" aria-pressed="true">' . esc_html__( 'All', 'alpha-preclinical' ) . '</button>';
	foreach ( $teams as $team ) {
		$filters .= "\n        " . '<button type="button" data-filter="' . esc_attr( $team ) . '" aria-pressed="false">' . esc_html( $team ) . '</button>';
	}
	return array(
		'filters' => $filters,
		'roles'   => $roles,
	);
}

/**
 * Careers: the open roles come from Careers › Open roles.
 *
 * @param string   $html       Section markup.
 * @param array    $attributes Attributes.
 * @param WP_Block $block      Block.
 * @return string
 */
function alpha_section_roles_html( $html, $attributes, $block ) {
	if ( 'alpha/careers-roles' !== $block->name ) {
		return $html;
	}
	$live = alpha_roles_html();
	if ( ! $live ) {
		return $html;
	}
	$html = preg_replace( '/(<ul class="roles">\s*)(.*?)(\s*<\/ul>\s*<p class="none">)/s', '${1}' . str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $live['roles'] ) . '${3}', $html, 1 );
	$html = preg_replace( '/(data-filter-group="roles"[^>]*>\s*)(.*?)(\s*<\/div>)/s', '${1}' . str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $live['filters'] ) . '${3}', $html, 1 );
	return $html;
}
add_filter( 'alpha_section_html', 'alpha_section_roles_html', 10, 3 );

/**
 * Reading time line: "Barak Yahalom, DVM, 3 min read".
 *
 * @param WP_Post $post Post.
 * @return string
 */
function alpha_post_byline( $post ) {
	$slug   = (string) get_post_meta( $post->ID, 'author_member', true );
	$member = $slug ? alpha_member_by_slug( $slug ) : null;
	$name   = $member ? alpha_person_name( $member ) : get_the_author_meta( 'display_name', (int) $post->post_author );
	// The post header states the reading time ("…, 3 min read"); use the same number.
	if ( preg_match( '/(\d+) min read/', (string) $post->post_content, $m ) ) {
		$mins = (int) $m[1];
	} else {
		$mins = (int) ceil( str_word_count( wp_strip_all_tags( do_blocks( $post->post_content ) ) ) / 230 );
	}
	/* translators: 1: author, 2: minutes. */
	return sprintf( __( '%1$s, %2$d min read', 'alpha-preclinical' ), $name, max( 1, $mins ) );
}

/**
 * Blog page: once posts are published, the list comes from Posts (newest first; a
 * sticky post is the feature). Until then the approved list is shown as designed.
 *
 * @param array    $attributes Attributes.
 * @param WP_Block $block      Block.
 * @return array
 */
function alpha_section_blog_data( $attributes, $block ) {
	if ( 'alpha/blog-list' !== $block->name ) {
		return $attributes;
	}
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 30,
		)
	);
	if ( ! $posts ) {
		return $attributes;
	}
	$sticky = array_values(
		array_filter(
			$posts,
			static function ( $p ) {
				return is_sticky( $p->ID );
			}
		)
	);
	$feature = $sticky ? $sticky[0] : $posts[0];
	$topic   = static function ( $p ) {
		$cats = get_the_category( $p->ID );
		return $cats ? $cats[0]->name : '';
	};
	$image   = static function ( $p ) {
		return (string) get_the_post_thumbnail_url( $p->ID, 'large' );
	};
	$topics  = array();
	$items   = array();
	foreach ( $posts as $p ) {
		$t = $topic( $p );
		if ( $t && ! in_array( $t, $topics, true ) ) {
			$topics[] = $t;
		}
		if ( $p->ID === $feature->ID ) {
			continue;
		}
		$items[] = array(
			'post_data_topic' => $t,
			'post_url'        => get_permalink( $p ),
			'img_img'         => $image( $p ) ? $image( $p ) : get_theme_file_uri( 'assets/img/building-sign.jpg' ),
			'tag'             => esc_html( $t ),
			'h3'              => esc_html( get_the_title( $p ) ),
			'p'               => esc_html( get_the_excerpt( $p ) ),
			'draft'           => esc_html( alpha_post_byline( $p ) ),
		);
	}
	$attributes['button_items'] = array_map(
		static function ( $t ) {
			return array(
				'button_data_filter' => $t,
				'button'             => esc_html( $t ),
			);
		},
		$topics
	);
	$attributes['feature_url']  = get_permalink( $feature );
	$attributes['img_img']      = $image( $feature ) ? $image( $feature ) : get_theme_file_uri( 'assets/img/building-sign.jpg' );
	$attributes['tag']          = esc_html( $topic( $feature ) );
	$attributes['h2']           = esc_html( get_the_title( $feature ) );
	$attributes['p']            = esc_html( get_the_excerpt( $feature ) );
	$attributes['meta']         = esc_html( alpha_post_byline( $feature ) );
	$attributes['post_items']   = $items;
	$attributes['post_items_2'] = array();
	$attributes['alpha_feature_topic'] = $topic( $feature );
	return $attributes;
}
add_filter( 'alpha_section_data', 'alpha_section_blog_data', 10, 2 );

/**
 * The feature card's topic is fixed in the design markup; set it from the live feature.
 *
 * @param string   $html       Section markup.
 * @param array    $attributes Attributes.
 * @param WP_Block $block      Block.
 * @return string
 */
function alpha_section_blog_html( $html, $attributes, $block ) {
	if ( 'alpha/blog-list' !== $block->name ) {
		return $html;
	}
	$live = apply_filters( 'alpha_section_data', (array) $attributes, $block );
	if ( isset( $live['alpha_feature_topic'] ) ) {
		$html = preg_replace( '/(<a class="feature" data-item="posts" data-topic=")[^"]*(")/', '${1}' . esc_attr( $live['alpha_feature_topic'] ) . '${2}', $html, 1 );
	}
	return $html;
}
add_filter( 'alpha_section_html', 'alpha_section_blog_html', 10, 3 );

/**
 * Blog post header: title and topic come from the post itself, so the post title and
 * category stay the single source. Dates fill in once published.
 *
 * @param array    $attributes Attributes.
 * @param WP_Block $block      Block.
 * @return array
 */
function alpha_section_post_head_data( $attributes, $block ) {
	if ( ! in_array( $block->name, array( 'alpha/post-head', 'alpha/post-head-infographic' ), true ) ) {
		return $attributes;
	}
	$post = get_post( $block->context['postId'] ?? get_the_ID() );
	if ( ! $post || 'post' !== $post->post_type ) {
		return $attributes;
	}
	$attributes['h1'] = esc_html( get_the_title( $post ) );
	$cats = get_the_category( $post->ID );
	if ( $cats ) {
		$attributes['tag'] = esc_html( $cats[0]->name );
		$attributes['li']  = esc_html( $cats[0]->name );
	}
	if ( 'publish' === $post->post_status && isset( $attributes['dates'] ) ) {
		$dates = (string) $attributes['dates'];
		$pos   = strpos( $dates, '[Date]' );
		if ( false !== $pos ) {
			$dates = substr_replace( $dates, esc_html( get_the_date( 'M j, Y', $post ) ), $pos, 6 );
		}
		$pos = strpos( $dates, '[Date]' );
		if ( false !== $pos ) {
			$dates = substr_replace( $dates, esc_html( get_the_modified_date( 'M j, Y', $post ) ), $pos, 6 );
		}
		$attributes['dates'] = $dates;
	}
	return $attributes;
}
add_filter( 'alpha_section_data', 'alpha_section_post_head_data', 10, 2 );
