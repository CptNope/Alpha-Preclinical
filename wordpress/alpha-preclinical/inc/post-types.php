<?php
/**
 * Content types: publications, team members and open roles, with their meta.
 * Every meta key is registered with show_in_rest so the editor can read it and the
 * core/post-meta binding source can bind it.
 *
 * Note: content types are in the theme because this build is a single-site theme
 * the agency maintains. If the client might ever switch themes, move this file
 * into a small site plugin unchanged.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta fields per post type: key => [ type, label ].
 *
 * @return array
 */
function alpha_meta_fields() {
	return array(
		'publication' => array(
			'authors'       => array( 'string', __( 'Authors (comma separated, as cited)', 'alpha-preclinical' ) ),
			'alpha_authors' => array( 'string', __( 'Alpha authors (comma separated, as they appear in Authors)', 'alpha-preclinical' ) ),
			'journal'       => array( 'string', __( 'Journal', 'alpha-preclinical' ) ),
			'volume'        => array( 'string', __( 'Volume', 'alpha-preclinical' ) ),
			'issue'         => array( 'string', __( 'Issue', 'alpha-preclinical' ) ),
			'pages'         => array( 'string', __( 'Pages', 'alpha-preclinical' ) ),
			'issue_date'    => array( 'string', __( 'Issue date (e.g. 2012 May)', 'alpha-preclinical' ) ),
			'epub_date'     => array( 'string', __( 'Epub date (e.g. 2012 Apr 13)', 'alpha-preclinical' ) ),
			'doi'           => array( 'string', __( 'DOI', 'alpha-preclinical' ) ),
			'pmid'          => array( 'string', __( 'PMID', 'alpha-preclinical' ) ),
			'pmcid'         => array( 'string', __( 'PMCID', 'alpha-preclinical' ) ),
			'pdf_url'       => array( 'string', __( 'PDF URL (media library)', 'alpha-preclinical' ) ),
			'legacy_pdf'    => array( 'string', __( 'Old Wix PDF URL (redirected to the PDF above)', 'alpha-preclinical' ) ),
			'featured'      => array( 'boolean', __( 'Feature on the homepage', 'alpha-preclinical' ) ),
		),
		'team_member' => array(
			'credentials' => array( 'string', __( 'Credentials (e.g. DVM, PhD)', 'alpha-preclinical' ) ),
			'job_title'   => array( 'string', __( 'Job title', 'alpha-preclinical' ) ),
			'short_title' => array( 'string', __( 'Short title for cards (e.g. CEO & CSO)', 'alpha-preclinical' ) ),
			'education'   => array( 'string', __( 'Education (one per line)', 'alpha-preclinical' ) ),
			'highlights'  => array( 'string', __( 'Credentials list (one per line)', 'alpha-preclinical' ) ),
			'linkedin'    => array( 'string', __( 'LinkedIn URL', 'alpha-preclinical' ) ),
			'orcid'       => array( 'string', __( 'ORCID URL', 'alpha-preclinical' ) ),
			'scholar'     => array( 'string', __( 'Google Scholar URL', 'alpha-preclinical' ) ),
			'pubmed_ids'  => array( 'string', __( 'PMIDs of their papers (comma separated)', 'alpha-preclinical' ) ),
			'leads'       => array( 'string', __( 'Areas led (comma separated)', 'alpha-preclinical' ) ),
			'alt_photo'   => array( 'integer', __( 'Second photo (attachment ID, shown on hover)', 'alpha-preclinical' ) ),
		),
		'job'         => array(
			'summary'         => array( 'string', __( 'One-paragraph summary', 'alpha-preclinical' ) ),
			'requirements'    => array( 'string', __( 'Requirements (one per line)', 'alpha-preclinical' ) ),
			'employment_type' => array( 'string', __( 'Employment type (FULL_TIME, PART_TIME…)', 'alpha-preclinical' ) ),
			'location'        => array( 'string', __( 'Location (empty = the site address)', 'alpha-preclinical' ) ),
			'valid_through'   => array( 'string', __( 'Close date (YYYY-MM-DD, optional)', 'alpha-preclinical' ) ),
		),
	);
}

/**
 * Register post types, taxonomies and meta.
 */
function alpha_register_content_types() {
	$common = array(
		'public'       => true,
		'show_in_rest' => true,
		'menu_position' => 21,
	);

	register_post_type(
		'publication',
		$common + array(
			'labels'        => array(
				'name'          => __( 'Publications', 'alpha-preclinical' ),
				'singular_name' => __( 'Publication', 'alpha-preclinical' ),
				'add_new_item'  => __( 'Add publication', 'alpha-preclinical' ),
				'edit_item'     => __( 'Edit publication', 'alpha-preclinical' ),
			),
			'menu_icon'     => 'dashicons-media-document',
			'supports'      => array( 'title', 'excerpt', 'custom-fields', 'page-attributes' ),
			'has_archive'   => false,
			// Must be viewable or Query Loop blocks silently fall back to posts.
			'rewrite'       => array(
				'slug'       => 'publications',
				'with_front' => false,
			),
		)
	);

	register_post_type(
		'team_member',
		$common + array(
			'labels'      => array(
				'name'          => __( 'Team', 'alpha-preclinical' ),
				'singular_name' => __( 'Team member', 'alpha-preclinical' ),
				'add_new_item'  => __( 'Add team member', 'alpha-preclinical' ),
				'edit_item'     => __( 'Edit team member', 'alpha-preclinical' ),
			),
			'menu_icon'   => 'dashicons-groups',
			'supports'    => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'page-attributes' ),
			'has_archive' => false,
			'rewrite'     => array(
				'slug'       => 'team',
				'with_front' => false,
			),
		)
	);

	register_post_type(
		'job',
		$common + array(
			'labels'      => array(
				'name'          => __( 'Open roles', 'alpha-preclinical' ),
				'singular_name' => __( 'Open role', 'alpha-preclinical' ),
				'add_new_item'  => __( 'Add role', 'alpha-preclinical' ),
				'edit_item'     => __( 'Edit role', 'alpha-preclinical' ),
			),
			'menu_icon'   => 'dashicons-id',
			'supports'    => array( 'title', 'editor', 'custom-fields', 'page-attributes' ),
			'has_archive' => false,
			'rewrite'     => array(
				'slug'       => 'careers',
				'with_front' => false,
			),
		)
	);

	register_taxonomy(
		'research_area',
		array( 'publication' ),
		array(
			'labels'            => array(
				'name'          => __( 'Research areas', 'alpha-preclinical' ),
				'singular_name' => __( 'Research area', 'alpha-preclinical' ),
			),
			'public'            => false,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'hierarchical'      => true,
		)
	);

	register_taxonomy(
		'job_team',
		array( 'job' ),
		array(
			'labels'            => array(
				'name'          => __( 'Teams', 'alpha-preclinical' ),
				'singular_name' => __( 'Team', 'alpha-preclinical' ),
			),
			'public'            => false,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'hierarchical'      => true,
		)
	);

	foreach ( alpha_meta_fields() as $post_type => $fields ) {
		foreach ( $fields as $key => $field ) {
			list( $type, $label ) = $field;
			register_post_meta(
				$post_type,
				$key,
				array(
					'type'              => $type,
					'label'             => $label,
					'description'       => $label,
					'single'            => true,
					'show_in_rest'      => true,
					'default'           => 'boolean' === $type ? false : ( 'integer' === $type ? 0 : '' ),
					'sanitize_callback' => alpha_meta_sanitizer( $key, $type ),
					'auth_callback'     => static function () use ( $post_type ) {
						$pto = get_post_type_object( $post_type );
						return $pto && current_user_can( $pto->cap->edit_posts );
					},
				)
			);
		}
	}
}
add_action( 'init', 'alpha_register_content_types' );

/**
 * Meta on core types: a page's short title (menus, breadcrumbs) and a post's author,
 * as a team member slug, for the author box and byline.
 */
function alpha_register_core_meta() {
	register_post_meta(
		'page',
		'short_title',
		array(
			'type'              => 'string',
			'label'             => __( 'Short title (breadcrumbs)', 'alpha-preclinical' ),
			'single'            => true,
			'show_in_rest'      => true,
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_pages' );
			},
		)
	);
	register_post_meta(
		'post',
		'author_member',
		array(
			'type'              => 'string',
			'label'             => __( 'Author (team member slug)', 'alpha-preclinical' ),
			'single'            => true,
			'show_in_rest'      => true,
			'default'           => '',
			'sanitize_callback' => 'sanitize_title',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'alpha_register_core_meta' );

/**
 * Sanitizer for a meta key.
 *
 * @param string $key  Meta key.
 * @param string $type Meta type.
 * @return callable
 */
function alpha_meta_sanitizer( $key, $type ) {
	if ( 'boolean' === $type ) {
		return 'rest_sanitize_boolean';
	}
	if ( 'integer' === $type ) {
		return 'absint';
	}
	if ( in_array( $key, array( 'pdf_url', 'legacy_pdf', 'linkedin', 'orcid', 'scholar' ), true ) ) {
		return 'esc_url_raw';
	}
	if ( in_array( $key, array( 'education', 'highlights', 'requirements', 'summary' ), true ) ) {
		return 'sanitize_textarea_field';
	}
	return 'sanitize_text_field';
}

/**
 * A simple meta panel in the editor sidebar for the content types, built from the
 * same field list (assets/js/editor.js renders it).
 */
function alpha_editor_meta_config() {
	$out = array(
		'page' => array( array( 'key' => 'short_title', 'type' => 'string', 'label' => __( 'Short title (breadcrumbs)', 'alpha-preclinical' ), 'multi' => false ) ),
		'post' => array( array( 'key' => 'author_member', 'type' => 'string', 'label' => __( 'Author (team member slug, e.g. barak-yahalom)', 'alpha-preclinical' ), 'multi' => false ) ),
	);
	foreach ( alpha_meta_fields() as $post_type => $fields ) {
		foreach ( $fields as $key => $field ) {
			$out[ $post_type ][] = array(
				'key'   => $key,
				'type'  => $field[0],
				'label' => $field[1],
				'multi' => in_array( $key, array( 'education', 'highlights', 'requirements', 'summary' ), true ),
			);
		}
	}
	return $out;
}

/**
 * Flush rewrite rules once after the theme is switched on.
 */
function alpha_flush_rewrites() {
	alpha_register_content_types();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'alpha_flush_rewrites' );
