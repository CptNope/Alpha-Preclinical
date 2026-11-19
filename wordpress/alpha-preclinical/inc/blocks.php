<?php
/**
 * Registers the alpha/* blocks and the editor script that gives them an editing UI.
 *
 * All blocks are dynamic (render.php) so markup and schema stay in one place and
 * bound values are resolved on the server.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block category.
 *
 * @param array $categories Categories.
 * @return array
 */
function alpha_block_category( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'alpha',
			'title' => __( 'Alpha Preclinical', 'alpha-preclinical' ),
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', 'alpha_block_category' );

/**
 * Register every block folder.
 */
function alpha_register_blocks() {
	foreach ( glob( ALPHA_THEME_DIR . '/blocks/*/block.json' ) as $file ) {
		register_block_type( dirname( $file ) );
	}
}
add_action( 'init', 'alpha_register_blocks' );

/**
 * Core blocks the theme binds beyond the WordPress defaults.
 *
 * @param string[] $supported  Supported attributes.
 * @param string   $block_type Block name.
 * @return string[]
 */
function alpha_extra_core_bindings( $supported, $block_type ) {
	$extra = array(
		'core/details' => array( 'summary' ),
	);
	if ( isset( $extra[ $block_type ] ) ) {
		$supported = array_values( array_unique( array_merge( (array) $supported, $extra[ $block_type ] ) ) );
	}
	return $supported;
}
add_filter( 'block_bindings_supported_attributes', 'alpha_extra_core_bindings', 10, 2 );

/**
 * Editor script: edit UI for the alpha/* blocks, editor-side binding sources and
 * the meta panels for publications, team members and roles.
 */
function alpha_editor_assets() {
	wp_enqueue_script(
		'alpha-editor',
		ALPHA_THEME_URI . '/assets/js/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-data', 'wp-core-data', 'wp-i18n', 'wp-plugins', 'wp-editor', 'wp-url' ),
		alpha_asset_version( 'assets/js/editor.js' ),
		true
	);
	$site = array();
	foreach ( array( 'email', 'email_link', 'phone', 'phone_link', 'address', 'address_inline', 'years', 'founded', 'legal_name', 'linkedin', 'map_url', 'tagline', 'publication_count', 'team_count', 'job_count', 'quote_url', 'copyright', 'a11y_response', 'a11y_promise' ) as $key ) {
		$site[ $key ] = alpha_binding_site( array( 'key' => $key ) );
	}
	wp_add_inline_script(
		'alpha-editor',
		'window.alphaEditor = ' . wp_json_encode(
			array(
				'site'       => $site,
				'contactUrl' => alpha_contact_url(),
				'studyTypes' => alpha_study_types(),
				'meta'       => alpha_editor_meta_config(),
				'bindable'   => alpha_bindable_attributes(),
			)
		) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'alpha_editor_assets' );

/**
 * Query Loop: `alphaFeatured` in the query attribute limits a publications loop to
 * papers marked "Feature on the homepage".
 *
 * @param array    $query Query vars.
 * @param WP_Block $block Post Template block.
 * @return array
 */
function alpha_featured_query_vars( $query, $block ) {
	if ( ! empty( $block->context['query']['alphaFeatured'] ) ) {
		$query['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- three papers.
			array(
				'key'   => 'featured',
				'value' => '1',
			),
		);
	}
	return $query;
}
add_filter( 'query_loop_block_query_vars', 'alpha_featured_query_vars', 10, 2 );
