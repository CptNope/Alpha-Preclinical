<?php
/**
 * Section blocks: every section of the approved design is a custom block whose
 * output is the design's exact markup.
 *
 * Each block in blocks/sections/<name>/ has:
 *   template.html  the design's markup with editable values as tokens
 *   block.json     attributes (one per value) and `alphaFields`, the field schema
 *                  used by the editor sidebar
 *
 * Tokens: {{&field}} rich text, {{@field}} link or image, {{field}} plain text in an
 * attribute, {{%field}} inline SVG, {{#items}}…{{/items}} repeated items, {{>nav}}
 * the shared header row. Every value is escaped for where it lands.
 *
 * Pages use the alpha/shell block, which outputs the design's page frame (skip link,
 * .ap wrapper, header outside <main>, footer) so the design's CSS and motion script
 * apply unchanged. Each page loads only its own stylesheet ("skin"), copied verbatim
 * from the design, so pages that styled the same class differently never collide.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Section block category.
 *
 * @param array $categories Categories.
 * @return array
 */
function alpha_sections_category( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'alpha-sections',
			'title' => __( 'Alpha Preclinical sections', 'alpha-preclinical' ),
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', 'alpha_sections_category' );

/**
 * Section block metadata, keyed by block name.
 *
 * @return array<string,array>
 */
function alpha_sections() {
	static $all = null;
	if ( null !== $all ) {
		return $all;
	}
	$all = array();
	foreach ( glob( ALPHA_THEME_DIR . '/blocks/sections/*/block.json' ) as $file ) {
		$meta = wp_json_file_decode( $file, array( 'associative' => true ) );
		if ( is_array( $meta ) && ! empty( $meta['name'] ) ) {
			$meta['dir']           = dirname( $file );
			$all[ $meta['name'] ] = $meta;
		}
	}
	return $all;
}

/**
 * Register the section blocks and the page shell.
 */
function alpha_register_sections() {
	foreach ( alpha_sections() as $meta ) {
		register_block_type( $meta['dir'] );
	}
	register_block_type(
		'alpha/shell',
		array(
			'api_version'     => 3,
			'title'           => __( 'Page frame', 'alpha-preclinical' ),
			'category'        => 'alpha-sections',
			'description'     => __( 'The approved design’s page frame: skip link, header, main content and footer.', 'alpha-preclinical' ),
			'supports'        => array(
				'html'     => false,
				'inserter' => false,
			),
			'render_callback' => 'alpha_render_shell',
		)
	);
}
add_action( 'init', 'alpha_register_sections' );

/**
 * Render a section block.
 *
 * @param string   $dir        Block folder.
 * @param array    $attributes Attributes.
 * @param WP_Block $block      Block instance.
 * @param string   $content    Rendered inner blocks (article text in a post body).
 * @return string
 */
function alpha_section_render( $dir, $attributes, $block, $content = '' ) {
	static $templates = array();
	if ( ! isset( $templates[ $dir ] ) ) {
		$templates[ $dir ] = (string) file_get_contents( $dir . '/template.html' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- theme file.
	}
	$meta   = alpha_sections()[ $block->name ] ?? array();
	$fields = $meta['alphaFields'] ?? array();
	/**
	 * Filter a section's values before rendering, for sections fed by live content
	 * (team members, publications, open roles, posts). See inc/section-data.php.
	 *
	 * @param array    $attributes Attributes.
	 * @param WP_Block $block      Block.
	 */
	$attributes = apply_filters( 'alpha_section_data', (array) $attributes, $block );
	$ctx    = array(
		'block'   => $block->name,
		'region'  => $meta['alphaRegion'] ?? 'main',
		'content' => $content,
	);
	$html   = alpha_tpl( $templates[ $dir ], (array) $attributes, $fields, $ctx );
	/**
	 * Filter a section's markup, for sections that need live data (forms, lists).
	 *
	 * @param string   $html       Markup.
	 * @param array    $attributes Attributes.
	 * @param WP_Block $block      Block.
	 */
	return apply_filters( 'alpha_section_html', $html, $attributes, $block );
}

/**
 * Fill a template. One pass, so values are never read as tokens.
 *
 * @param string $tpl    Template.
 * @param array  $data   Values.
 * @param array  $fields Field schema.
 * @param array  $ctx    Context.
 * @return string
 */
function alpha_tpl( $tpl, $data, $fields, $ctx ) {
	return preg_replace_callback(
		'/\{\{#([a-z0-9_]+)\}\}(.*?)\{\{\/\1\}\}|\{\{([&@%>$]?)([a-z0-9_]+)\}\}/s',
		function ( $m ) use ( $data, $fields, $ctx ) {
			if ( '' !== $m[1] ) {
				$items = isset( $data[ $m[1] ] ) && is_array( $data[ $m[1] ] ) ? $data[ $m[1] ] : array();
				$sub   = $fields[ $m[1] ]['fields'] ?? array();
				$out   = '';
				foreach ( $items as $item ) {
					if ( is_array( $item ) ) {
						$out .= alpha_tpl( $m[2], $item, $sub, $ctx );
					}
				}
				return $out;
			}
			$value = $data[ $m[4] ] ?? '';
			$value = is_scalar( $value ) ? (string) $value : '';
			switch ( $m[3] ) {
				case '&':
					return alpha_kses_inline( alpha_resolve_links( $value ) );
				case '@':
					return esc_url( alpha_resolve_url( $value ) );
				case '%':
					return alpha_safe_svg( $value );
				case '$':
					return esc_attr( alpha_resolve_srcset( $value ) );
				case '>':
					return alpha_partial( $m[4], $ctx );
				default:
					return esc_attr( $value );
			}
		},
		$tpl
	);
}

/**
 * Turn a stored link into a full URL: theme images ("assets/…") and site paths ("/about/").
 *
 * @param string $url Stored value.
 * @return string
 */
function alpha_resolve_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	if ( 0 === strpos( $url, 'assets/' ) ) {
		return get_theme_file_uri( $url );
	}
	if ( '/' === $url[0] && ( ! isset( $url[1] ) || '/' !== $url[1] ) ) {
		return home_url( $url );
	}
	return $url;
}

/**
 * Resolve each URL in a srcset ("assets/a.jpg 1200w, assets/b.jpg 1698w").
 *
 * @param string $srcset Stored srcset.
 * @return string
 */
function alpha_resolve_srcset( $srcset ) {
	$out = array();
	foreach ( array_filter( array_map( 'trim', explode( ',', (string) $srcset ) ) ) as $candidate ) {
		$parts = preg_split( '/\s+/', $candidate, 2 );
		$out[] = esc_url( alpha_resolve_url( $parts[0] ) ) . ( isset( $parts[1] ) ? ' ' . $parts[1] : '' );
	}
	return implode( ', ', $out );
}

/**
 * Resolve links inside rich text.
 *
 * @param string $html Rich text.
 * @return string
 */
function alpha_resolve_links( $html ) {
	if ( false === strpos( $html, '="' ) ) {
		return $html;
	}
	return preg_replace_callback(
		'/\b(href|src)="([^"]*)"/',
		function ( $m ) {
			return $m[1] . '="' . esc_url( alpha_resolve_url( html_entity_decode( $m[2], ENT_QUOTES ) ) ) . '"';
		},
		$html
	);
}

/**
 * Inline HTML allowed in rich-text fields.
 *
 * @param string $html Rich text.
 * @return string
 */
function alpha_kses_inline( $html ) {
	static $allowed = null;
	if ( null === $allowed ) {
		$common  = array(
			'class'       => true,
			'id'          => true,
			'aria-hidden' => true,
			'aria-label'  => true,
			'title'       => true,
			'lang'        => true,
			'data-*'      => true,
			'style'       => true,
		);
		$allowed = array(
			'a'      => $common + array(
				'href'     => true,
				'target'   => true,
				'rel'      => true,
				'download' => true,
			),
			'span'   => $common + array( 'role' => true ),
			'time'   => $common + array( 'datetime' => true ),
			'abbr'   => $common,
			'br'     => array(),
			'wbr'    => array(),
		);
		foreach ( array( 'em', 'strong', 'b', 'i', 'sup', 'sub', 'small', 'code', 'mark', 'u', 's', 'q', 'cite', 'bdi', 'kbd', 'dfn' ) as $tag ) {
			$allowed[ $tag ] = $common;
		}
	}
	return wp_kses( $html, $allowed );
}

/**
 * Inline SVG icons: shapes only, no scripts, events or external references.
 *
 * @param string $svg SVG markup.
 * @return string
 */
function alpha_safe_svg( $svg ) {
	$svg = trim( (string) $svg );
	if ( 0 !== strpos( $svg, '<svg' ) ) {
		return '';
	}
	if ( preg_match( '/<\s*(script|foreignObject|iframe|object|embed|image|use|a)\b|\son[a-z]+\s*=|javascript:|data:|xlink:href|href\s*=/i', $svg ) ) {
		return '';
	}
	return $svg;
}

/**
 * Shared pieces inside section templates.
 *
 * @param string $name Partial.
 * @param array  $ctx  Context.
 * @return string
 */
function alpha_partial( $name, $ctx ) {
	if ( 'inner' === $name ) {
		return (string) ( $ctx['content'] ?? '' );
	}
	if ( 'nav' === $name ) {
		$variant = 'alpha/home-hero' === $ctx['block'] ? 'hero' : ( 'alpha/post-bar' === $ctx['block'] ? 'bar' : 'top' );
		return alpha_nav_html( $variant );
	}
	return '';
}

/**
 * Path of the page being viewed, with a trailing slash ("/about/").
 *
 * @return string
 */
function alpha_current_path() {
	$id = get_queried_object_id();
	if ( is_singular() && $id ) {
		$url = get_permalink( $id );
	} elseif ( is_home() ) {
		$url = get_permalink( (int) get_option( 'page_for_posts' ) );
	} else {
		$url = home_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- path only, compared not printed.
	}
	if ( is_front_page() ) {
		return '/';
	}
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( $home && '/' !== $home && 0 === strpos( $path, $home ) ) {
		$path = '/' . substr( $path, strlen( $home ) );
	}
	return trailingslashit( '/' . ltrim( $path, '/' ) );
}

/**
 * The design's header row: logo, menu with dropdowns, quote button, menu toggle.
 * Marks the current page the way the design does (aria-current, in-section).
 *
 * @param string $variant hero|top|bar.
 * @return string
 */
function alpha_nav_html( $variant ) {
	static $tpl = null;
	if ( null === $tpl ) {
		$tpl = (string) file_get_contents( ALPHA_THEME_DIR . '/inc/templates/nav.html' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- theme file.
	}
	$cur   = alpha_current_path();
	$width = array(
		'hero' => '84px',
		'top'  => '72px',
		'bar'  => '64px',
	);
	$html  = strtr(
		$tpl,
		array(
			'{{logo_href}}'  => 'hero' === $variant ? '#top' : esc_url( home_url( '/' ) ),
			'{{logo_width}}' => $width[ $variant ],
			'{{theme}}'      => esc_url( get_theme_file_uri() ),
		)
	);
	// On the contact page the quote button jumps to the form.
	if ( '/contact/' === $cur ) {
		$html = str_replace( '<a class="btn btn-light" href="/contact/">', '<a aria-current="page" class="btn btn-light" href="#form">', $html );
	}
	// Current page links.
	$html = preg_replace_callback(
		'/<a ((?:class="[^"]*" )?)href="(\/[^"#]*)"/',
		function ( $m ) use ( $cur ) {
			$mark = ( $m[2] === $cur && ! preg_match( '/sub-all|sub-feature/', $m[1] ) ) ? 'aria-current="page" ' : '';
			return '<a ' . $mark . $m[1] . 'href="' . esc_url( home_url( $m[2] ) ) . '"';
		},
		$html
	);
	$html = preg_replace_callback(
		'/<a ((?:class="[^"]*" )?)href="(\/[^"]*[#?][^"]*)"/',
		function ( $m ) {
			return '<a ' . $m[1] . 'href="' . esc_url( home_url( $m[2] ) ) . '"';
		},
		$html
	);
	// A dropdown whose page is open, but not its own landing page, is "in section".
	$html = preg_replace_callback(
		'/<li class="has-sub" data-sub="">(.*?)<\/div><\/li>/s',
		function ( $m ) {
			$top_current = (bool) preg_match( '/^<a aria-current="page" class="top-link"/', $m[1] );
			$child       = false !== strpos( $m[1], 'aria-current="page"' );
			$cls         = ( $child && ! $top_current ) ? 'has-sub in-section' : 'has-sub';
			return '<li class="' . $cls . '" data-sub="">' . $m[1] . '</div></li>';
		},
		$html
	);
	return $html;
}

/**
 * Page frame: skip link, .ap wrapper, the page's header section, <main>, footer.
 *
 * @param array    $attributes Attributes.
 * @param string   $content    Rendered inner blocks (unused; inner blocks render below).
 * @param WP_Block $block      Block.
 * @return string
 */
function alpha_render_shell( $attributes, $content, $block ) {
	$header = '';
	$main   = '';
	$after  = '';
	foreach ( $block->inner_blocks as $inner ) {
		if ( 'core/post-content' === $inner->name ) {
			list( $h, $m ) = alpha_render_post_sections( $inner );
			$header       .= $h;
			$main         .= $m;
		} elseif ( alpha_block_region( $inner->name ) === 'header' ) {
			$header .= $inner->render();
		} elseif ( alpha_block_region( $inner->name ) === 'footer' ) {
			$after .= $inner->render();
		} else {
			$main .= $inner->render();
		}
	}
	// The design's list pages (publications, blog) give <main> the "body" class.
	$main_class = in_array( alpha_current_skin(), array( 'publications', 'blog' ), true ) ? ' class="body"' : '';
	return '<a class="skip" href="#main">' . esc_html__( 'Skip to content', 'alpha-preclinical' ) . '</a>'
		. '<div class="ap">' . $header . '<main id="main"' . $main_class . '>' . $main . '</main>' . $after . '</div>';
}

/**
 * Region of a block: header (above <main>), footer, or main.
 *
 * @param string $name Block name.
 * @return string
 */
function alpha_block_region( $name ) {
	$meta = alpha_sections()[ $name ] ?? null;
	if ( $meta ) {
		return $meta['alphaRegion'] ?? 'main';
	}
	return in_array( $name, array( 'alpha/post-bar' ), true ) ? 'header' : 'main';
}

/**
 * Render the current post's blocks, splitting header sections from the rest.
 *
 * @param WP_Block $post_content The core/post-content block in the template.
 * @return string[] [ header, main ].
 */
function alpha_render_post_sections( $post_content ) {
	static $seen = array();
	$post_id = $post_content->context['postId'] ?? get_the_ID();
	$post    = get_post( $post_id );
	if ( ! $post || isset( $seen[ $post_id ] ) || post_password_required( $post ) ) {
		return array( '', $post && post_password_required( $post ) ? get_the_password_form( $post ) : '' );
	}
	$seen[ $post_id ] = true;
	$header           = '';
	$main             = '';
	$loose            = '';
	foreach ( parse_blocks( $post->post_content ) as $parsed ) {
		if ( empty( $parsed['blockName'] ) ) {
			$loose .= $parsed['innerHTML'];
			continue;
		}
		$html = ( new WP_Block(
			$parsed,
			array(
				'postId'   => $post_id,
				'postType' => $post->post_type,
			)
		) )->render();
		if ( 'header' === alpha_block_region( $parsed['blockName'] ) ) {
			$header .= $html;
		} else {
			$main .= $html;
		}
	}
	unset( $seen[ $post_id ] );
	if ( trim( $loose ) ) {
		$main .= wpautop( $loose );
	}
	return array( $header, $main );
}

/**
 * Which design stylesheet a request uses.
 *
 * @return string
 */
function alpha_current_skin() {
	$skins = array( 'home', 'expertise', 'services', 'tumor-models', 'about', 'publications', 'team', 'careers', 'contact', 'blog', 'legal', 'blog-article' );
	$skin  = '';
	if ( is_singular( 'page' ) ) {
		$skin = (string) get_post_meta( get_queried_object_id(), '_alpha_skin', true );
		if ( ! $skin ) {
			$slug = get_post_field( 'post_name', get_queried_object_id() );
			$skin = is_front_page() ? 'home' : ( in_array( $slug, $skins, true ) ? $slug : 'legal' );
		}
	} elseif ( is_singular( 'post' ) ) {
		$skin = 'blog-article';
	} elseif ( is_home() || is_category() || is_tag() ) {
		$skin = 'blog';
	} else {
		$skin = 'legal';
	}
	return in_array( $skin, $skins, true ) ? $skin : 'legal';
}

/**
 * Does the current template use the design's page frame?
 *
 * @return bool
 */
function alpha_uses_shell() {
	global $_wp_current_template_content;
	return is_string( $_wp_current_template_content ) && false !== strpos( $_wp_current_template_content, '<!-- wp:alpha/shell' );
}

/**
 * Design styles and motion script on pages that use the frame.
 */
function alpha_sections_assets() {
	if ( ! alpha_uses_shell() ) {
		return;
	}
	$skin = alpha_current_skin();
	// Same order as the design: the page's own styles first, then the shared site.css.
	wp_enqueue_style( 'alpha-skin', ALPHA_THEME_URI . '/assets/css/demo/skins/' . $skin . '.css', array(), alpha_asset_version( 'assets/css/demo/skins/' . $skin . '.css' ) );
	wp_enqueue_style( 'alpha-design', ALPHA_THEME_URI . '/assets/css/demo/site.css', array( 'alpha-skin' ), alpha_asset_version( 'assets/css/demo/site.css' ) );
	wp_enqueue_style( 'alpha-design-wp', ALPHA_THEME_URI . '/assets/css/demo/wp.css', array( 'alpha-design' ), alpha_asset_version( 'assets/css/demo/wp.css' ) );
	// Staging banner and the content review page.
	if ( alpha_site( 'staging_mode' ) || ( is_singular() && has_block( 'alpha/content-review' ) ) ) {
		wp_enqueue_style( 'alpha-staging', ALPHA_THEME_URI . '/assets/css/demo/staging.css', array( 'alpha-design-wp' ), alpha_asset_version( 'assets/css/demo/staging.css' ) );
	}
	wp_enqueue_script(
		'alpha-design',
		ALPHA_THEME_URI . '/assets/js/demo-site.js',
		array(),
		alpha_asset_version( 'assets/js/demo-site.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	// The design has its own skip link ("Skip to content").
	wp_dequeue_script( 'wp-block-template-skip-link' );
	wp_dequeue_style( 'wp-block-template-skip-link' );
	remove_action( 'wp_footer', 'the_block_template_skip_link' );
	// The design's own theme script and styles are not used on these pages.
	wp_dequeue_style( 'alpha-theme' );
	wp_dequeue_script( 'alpha-theme' );
	// theme.json global styles and the core block library set heading line heights,
	// margins, link colours and icon sizes the design never declares. The design's CSS
	// covers every element on these pages (article text uses plain paragraphs, headings
	// and lists), so they are left out. Fonts still load from theme.json.
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'wp-block-library' );
}
add_action( 'wp_enqueue_scripts', 'alpha_sections_assets', 20 );

/**
 * Core prints its own skip link for block themes; the frame already has the design's.
 */
function alpha_sections_skip_link() {
	if ( alpha_uses_shell() ) {
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_block_template_skip_link' );
	}
}
add_action( 'wp_enqueue_scripts', 'alpha_sections_skip_link', 1 );

/**
 * Body class with the skin, for any skin-specific WordPress adjustments.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function alpha_sections_body_class( $classes ) {
	if ( alpha_uses_shell() ) {
		$classes[] = 'alpha-skin-' . alpha_current_skin();
	}
	return $classes;
}
add_filter( 'body_class', 'alpha_sections_body_class' );

/**
 * Section attributes can be bound with the Block Bindings API (for example to the
 * alpha/site source for the address, email and phone).
 *
 * @param string[] $supported  Supported attributes.
 * @param string   $block_type Block name.
 * @return string[]
 */
function alpha_sections_bindable( $supported, $block_type ) {
	$meta = alpha_sections()[ $block_type ] ?? null;
	if ( ! $meta ) {
		return $supported;
	}
	foreach ( $meta['alphaFields'] as $name => $field ) {
		if ( 'items' !== $field['type'] && 'svg' !== $field['type'] ) {
			$supported[] = $name;
		}
	}
	return array_values( array_unique( (array) $supported ) );
}
add_filter( 'block_bindings_supported_attributes', 'alpha_sections_bindable', 10, 2 );

/**
 * Keep the design's typography exactly: no automatic curly quotes or dash changes.
 */
add_filter( 'run_wptexturize', '__return_false' );

/**
 * The design has no emoji script or styles.
 */
function alpha_sections_no_emoji() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
}
add_action( 'init', 'alpha_sections_no_emoji' );

/**
 * Blocks that are not design sections (the content review) sit in the design's
 * section container so they line up with the page.
 *
 * @param string $html  Block output.
 * @param array  $block Parsed block.
 * @return string
 */
function alpha_sections_wrap_other_blocks( $html, $block ) {
	if ( 'alpha/content-review' === ( $block['blockName'] ?? '' ) && alpha_uses_shell() && '' !== trim( $html ) ) {
		return '<section class="sec"><div class="wrap">' . $html . '</div></section>';
	}
	return $html;
}
add_filter( 'render_block', 'alpha_sections_wrap_other_blocks', 10, 2 );

/**
 * Editor: the sections' editing UI, with the field schema of every section block.
 */
function alpha_sections_editor_script() {
	wp_enqueue_script(
		'alpha-sections-editor',
		ALPHA_THEME_URI . '/assets/js/sections-editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-data', 'wp-i18n' ),
		alpha_asset_version( 'assets/js/sections-editor.js' ),
		true
	);
	$blocks  = array();
	$article = array( 'core/paragraph', 'core/heading', 'core/list', 'core/list-item' );
	foreach ( alpha_sections() as $name => $meta ) {
		$blocks[ $name ] = array(
			'fields' => $meta['alphaFields'],
			'region' => $meta['alphaRegion'] ?? 'main',
			'inner'  => false !== strpos( (string) file_get_contents( $meta['dir'] . '/template.html' ), '{{>inner}}' ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- theme file.
		);
		if ( 0 === strpos( $name, 'alpha/article-' ) ) {
			$article[] = $name;
		}
	}
	wp_add_inline_script(
		'alpha-sections-editor',
		'window.alphaSections = ' . wp_json_encode(
			array(
				'blocks'   => $blocks,
				'article'  => $article,
				'themeUrl' => get_theme_file_uri(),
				// Fields filled from records at render time (inc/section-data.php).
				'live'     => alpha_section_live_fields(),
			)
		) . ';',
		'before'
	);
	wp_enqueue_style( 'alpha-sections-editor-ui', ALPHA_THEME_URI . '/assets/css/demo/editor-ui.css', array(), alpha_asset_version( 'assets/css/demo/editor-ui.css' ) );
}
add_action( 'enqueue_block_editor_assets', 'alpha_sections_editor_script' );

/**
 * Editor canvas: the design's styles for the page being edited, so previews look
 * exactly like the site.
 */
function alpha_sections_canvas_styles() {
	if ( ! is_admin() ) {
		return;
	}
	$skin = 'home';
	// On the edit screen WordPress has already loaded (and permission-checked) the post.
	$post = get_post();
	if ( $post && ! current_user_can( 'edit_post', $post->ID ) ) {
		$post = null;
	}
	if ( $post && 'post' === $post->post_type ) {
		$skin = 'blog-article';
	} elseif ( $post && 'page' === $post->post_type ) {
		$saved = (string) get_post_meta( $post->ID, '_alpha_skin', true );
		$skin  = $saved ? $saved : ( (int) get_option( 'page_on_front' ) === $post->ID ? 'home' : 'legal' );
	}
	wp_enqueue_style( 'alpha-canvas-skin', ALPHA_THEME_URI . '/assets/css/demo/skins/' . sanitize_key( $skin ) . '.css', array(), alpha_asset_version( 'assets/css/demo/skins/' . sanitize_key( $skin ) . '.css' ) );
	wp_enqueue_style( 'alpha-canvas-design', ALPHA_THEME_URI . '/assets/css/demo/site.css', array( 'alpha-canvas-skin' ), alpha_asset_version( 'assets/css/demo/site.css' ) );
	wp_enqueue_style( 'alpha-canvas', ALPHA_THEME_URI . '/assets/css/demo/editor-canvas.css', array( 'alpha-canvas-design' ), alpha_asset_version( 'assets/css/demo/editor-canvas.css' ) );
}
add_action( 'enqueue_block_assets', 'alpha_sections_canvas_styles' );

/**
 * Pages can choose which page design's styles they use (Page settings › Design styles).
 */
function alpha_register_skin_meta() {
	register_post_meta(
		'page',
		'_alpha_skin',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'default'           => '',
			'sanitize_callback' => 'sanitize_key',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_pages' );
			},
		)
	);
}
add_action( 'init', 'alpha_register_skin_meta' );

/**
 * Section fields whose values come from records, with where to edit them.
 *
 * @return array<string,array<string,string>>
 */
function alpha_section_live_fields() {
	$post_head = array(
		'h1'  => __( 'the post title', 'alpha-preclinical' ),
		'tag' => __( 'the post’s category', 'alpha-preclinical' ),
		'li'  => __( 'the post’s category', 'alpha-preclinical' ),
	);
	$fields    = array(
		'alpha/post-head'              => $post_head,
		'alpha/post-head-infographic'  => $post_head,
		'alpha/home-team'              => array( 'member_items' => __( 'Team: each card’s name and short title come from the team member (photos and links stay here).', 'alpha-preclinical' ) ),
		'alpha/about-leadership'       => array( 'leader_items' => __( 'Team: names and job titles come from the team members (photos and links stay here).', 'alpha-preclinical' ) ),
		'alpha/home-publications'      => array(
			'paper_col_items' => __( 'Publications marked “Feature on the homepage”.', 'alpha-preclinical' ),
			'span'            => __( 'the number of published papers', 'alpha-preclinical' ),
			'span_data_count' => __( 'the number of published papers', 'alpha-preclinical' ),
		),
		'alpha/publications-list'      => array(
			'button_items' => __( 'Publications (research areas)', 'alpha-preclinical' ),
			'button'       => __( 'Publications', 'alpha-preclinical' ),
		),
		'alpha/careers-roles'          => array( 'role_items' => __( 'Careers › Open roles', 'alpha-preclinical' ) ),
	);
	return $fields;
}

/**
 * The design has no separate pages for team members, papers or roles: each lives on
 * the Team, Publications or Careers page. Their single URLs redirect there.
 */
function alpha_sections_single_redirects() {
	if ( is_admin() || is_preview() || ! is_singular( array( 'team_member', 'publication', 'job' ) ) ) {
		return;
	}
	$post = get_queried_object();
	$map  = array(
		'team_member' => array( 'team', '#' . $post->post_name ),
		'publication' => array( 'publications', '' ),
		'job'         => array( 'careers', '#roles' ),
	);
	list( $slug, $hash ) = $map[ $post->post_type ];
	$page                = get_page_by_path( $slug );
	if ( $page ) {
		wp_safe_redirect( get_permalink( $page ) . $hash, 301 );
		exit;
	}
}
add_action( 'template_redirect', 'alpha_sections_single_redirects', 5 );
