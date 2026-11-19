<?php
/**
 * Alpha Preclinical block theme.
 *
 * Everything bespoke is a dynamic `alpha/*` block (see /blocks) whose content
 * attributes can be bound with the Block Bindings API. Data lives in custom post
 * types and site settings and reaches blocks through the binding sources in
 * inc/bindings.php. Requires WordPress 6.9 or later.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

define( 'ALPHA_THEME_VERSION', '1.2.6' );
define( 'ALPHA_THEME_DIR', get_template_directory() );
define( 'ALPHA_THEME_URI', get_template_directory_uri() );
define( 'ALPHA_MIN_WP', '6.9' );

if ( version_compare( get_bloginfo( 'version' ), ALPHA_MIN_WP, '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %s: minimum WordPress version. */
						__( 'The Alpha Preclinical theme needs WordPress %s or later for block bindings on its custom blocks. Please update WordPress.', 'alpha-preclinical' ),
						ALPHA_MIN_WP
					)
				)
			);
		}
	);
}

require_once ALPHA_THEME_DIR . '/inc/helpers.php';
require_once ALPHA_THEME_DIR . '/inc/settings.php';
require_once ALPHA_THEME_DIR . '/inc/post-types.php';
require_once ALPHA_THEME_DIR . '/inc/bindings.php';
require_once ALPHA_THEME_DIR . '/inc/blocks.php';
require_once ALPHA_THEME_DIR . '/inc/sections.php';
require_once ALPHA_THEME_DIR . '/inc/section-forms.php';
require_once ALPHA_THEME_DIR . '/inc/section-data.php';
require_once ALPHA_THEME_DIR . '/inc/forms.php';
require_once ALPHA_THEME_DIR . '/inc/schema.php';
require_once ALPHA_THEME_DIR . '/inc/redirects.php';
require_once ALPHA_THEME_DIR . '/inc/staging.php';
require_once ALPHA_THEME_DIR . '/inc/review.php';
require_once ALPHA_THEME_DIR . '/inc/review-invite.php';
require_once ALPHA_THEME_DIR . '/inc/seed.php';

/**
 * Theme supports.
 */
function alpha_setup() {
	load_theme_textdomain( 'alpha-preclinical', ALPHA_THEME_DIR . '/languages' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/theme.css' );
	// Only this theme's own patterns appear in the inserter.
	remove_theme_support( 'core-block-patterns' );
}
add_action( 'after_setup_theme', 'alpha_setup' );

/**
 * Front-end styles and the no-dependency motion script.
 */
function alpha_enqueue_assets() {
	wp_enqueue_style(
		'alpha-theme',
		ALPHA_THEME_URI . '/assets/css/theme.css',
		array(),
		alpha_asset_version( 'assets/css/theme.css' )
	);
	wp_enqueue_script(
		'alpha-theme',
		ALPHA_THEME_URI . '/assets/js/theme.js',
		array(),
		alpha_asset_version( 'assets/js/theme.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'alpha_enqueue_assets' );

/**
 * Preload the two fonts used above the fold.
 */
function alpha_preload_fonts() {
	foreach ( array( 'BricolageGrotesque-Variable.woff2', 'Figtree-400.woff2' ) as $file ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( ALPHA_THEME_URI . '/assets/fonts/' . $file )
		);
	}
}
add_action( 'wp_head', 'alpha_preload_fonts', 1 );

/**
 * Skip link to the main content (block themes print one only for some templates).
 */
function alpha_skip_link() {
	if ( function_exists( 'alpha_uses_shell' ) && alpha_uses_shell() ) {
		return; // The design's page frame has its own skip link.
	}
	echo '<a class="alpha-skip" href="#main">' . esc_html__( 'Skip to main content', 'alpha-preclinical' ) . '</a>';
}
add_action( 'wp_body_open', 'alpha_skip_link', 1 );

/**
 * Pattern category for the theme's own patterns.
 */
function alpha_register_pattern_categories() {
	register_block_pattern_category( 'alpha-sections', array( 'label' => __( 'Alpha: sections', 'alpha-preclinical' ) ) );
	register_block_pattern_category( 'alpha-pages', array( 'label' => __( 'Alpha: full pages', 'alpha-preclinical' ) ) );
}
add_action( 'init', 'alpha_register_pattern_categories', 9 );

/**
 * Tag each post in a Query Loop with its research area, so filter chips can work without reloading.
 *
 * @param string[] $classes Post classes.
 * @param string[] $class   Extra classes.
 * @param int      $post_id Post ID.
 * @return string[]
 */
function alpha_post_area_classes( $classes, $class, $post_id ) {
	foreach ( array( 'research_area', 'category', 'job_team' ) as $tax ) {
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$terms = get_the_terms( $post_id, $tax );
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$classes[] = 'alpha-filter-' . sanitize_html_class( $term->slug );
			}
		}
	}
	return $classes;
}
add_filter( 'post_class', 'alpha_post_area_classes', 10, 3 );
