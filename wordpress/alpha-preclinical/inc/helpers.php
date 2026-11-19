<?php
/**
 * Small shared helpers.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache-busting version for a theme file.
 *
 * @param string $relative Path relative to the theme root.
 * @return string
 */
function alpha_asset_version( $relative ) {
	$file = ALPHA_THEME_DIR . '/' . ltrim( $relative, '/' );
	return file_exists( $file ) ? ALPHA_THEME_VERSION . '.' . filemtime( $file ) : ALPHA_THEME_VERSION;
}

/**
 * Study types offered on the quote form, in the order shown. Links pass one of these as ?study=.
 *
 * @return string[]
 */
function alpha_study_types() {
	$types = array(
		'A study quote',
		'PK/PD',
		'Gene therapy',
		'Autoimmune disease',
		'Metabolic disease',
		'Inflammation and fibrosis',
		'Tumor models',
		'IVIS imaging, surgery or lab services',
		'Something else',
	);
	/**
	 * Filters the study types on the quote form.
	 *
	 * @param string[] $types Study type labels.
	 */
	return apply_filters( 'alpha_study_types', $types );
}

/**
 * URL of the contact page's form, with the study type preset.
 *
 * @param string $study Study type label (optional).
 * @return string
 */
function alpha_contact_url( $study = '' ) {
	$page = get_page_by_path( 'contact' );
	$url  = $page ? get_permalink( $page ) : home_url( '/contact/' );
	if ( '' !== $study ) {
		$url = add_query_arg( 'study', rawurlencode( $study ), $url );
	}
	return $url . '#form';
}

/**
 * NLM-style source line: "2012 May;61(5):1160–1168. Epub 2012 Apr 13."
 *
 * @param int $post_id Publication ID.
 * @return string Plain text.
 */
function alpha_citation_source( $post_id ) {
	$date   = (string) get_post_meta( $post_id, 'issue_date', true );
	$volume = (string) get_post_meta( $post_id, 'volume', true );
	$issue  = (string) get_post_meta( $post_id, 'issue', true );
	$pages  = (string) get_post_meta( $post_id, 'pages', true );
	$epub   = (string) get_post_meta( $post_id, 'epub_date', true );

	$out = $date;
	if ( '' !== $volume ) {
		$out .= ';' . $volume;
	}
	if ( '' !== $issue ) {
		$out .= '(' . $issue . ')';
	}
	if ( '' !== $pages ) {
		$out .= ':' . $pages;
	}
	$out .= '.';
	if ( '' !== $epub ) {
		$out .= ' Epub ' . $epub . '.';
	}
	return trim( $out );
}

/**
 * Author list with Alpha scientists in bold.
 *
 * @param int $post_id Publication ID.
 * @return string HTML.
 */
function alpha_authors_html( $post_id ) {
	$authors = array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $post_id, 'authors', true ) ) ) );
	$alpha   = array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $post_id, 'alpha_authors', true ) ) ) );
	$out     = array();
	foreach ( $authors as $name ) {
		$out[] = in_array( $name, $alpha, true ) ? '<strong>' . esc_html( $name ) . '</strong>' : esc_html( $name );
	}
	return implode( ', ', $out );
}

/**
 * Convert "2012 Apr 13" / "2012 May" / "2004" to ISO 8601.
 *
 * @param string $date NLM date.
 * @return string
 */
function alpha_nlm_to_iso( $date ) {
	$months = array( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' );
	$parts  = preg_split( '/\s+/', trim( (string) $date ) );
	if ( empty( $parts[0] ) || ! ctype_digit( $parts[0] ) ) {
		return '';
	}
	$iso = $parts[0];
	if ( isset( $parts[1] ) ) {
		$m = array_search( substr( $parts[1], 0, 3 ), $months, true );
		if ( false !== $m ) {
			$iso .= sprintf( '-%02d', $m + 1 );
			if ( isset( $parts[2] ) && ctype_digit( $parts[2] ) ) {
				$iso .= sprintf( '-%02d', (int) $parts[2] );
			}
		}
	}
	return $iso;
}

/**
 * Team members by slug, in menu order.
 *
 * @param string[] $slugs Slugs; empty for all.
 * @return WP_Post[]
 */
function alpha_get_team( $slugs = array() ) {
	$args = array(
		'post_type'      => 'team_member',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		'no_found_rows'  => true,
	);
	if ( $slugs ) {
		$args['post_name__in'] = array_map( 'sanitize_title', $slugs );
		$args['orderby']       = 'post_name__in';
	}
	return get_posts( $args );
}

/**
 * Display name with credentials: "Barak Yahalom, DVM".
 *
 * @param WP_Post|int $person Team member.
 * @return string
 */
function alpha_person_name( $person ) {
	$person = get_post( $person );
	if ( ! $person ) {
		return '';
	}
	$creds = (string) get_post_meta( $person->ID, 'credentials', true );
	return get_the_title( $person ) . ( '' !== $creds ? ', ' . $creds : '' );
}

/**
 * Allowed markup for the inline SVG the strata blocks print.
 *
 * @return array
 */
function alpha_svg_kses() {
	$common = array(
		'class'        => true,
		'd'            => true,
		'fill'         => true,
		'style'        => true,
		'stroke'       => true,
		'stroke-width' => true,
		'opacity'      => true,
	);
	return array(
		'svg'      => array(
			'viewbox'             => true,
			'preserveaspectratio' => true,
			'aria-hidden'         => true,
			'focusable'           => true,
			'class'               => true,
			'width'               => true,
			'height'              => true,
			'xmlns'               => true,
			'role'                => true,
		),
		'g'        => array(
			'class'     => true,
			'clip-path' => true,
		),
		'path'     => $common + array(
			'clip-rule'      => true,
			'stroke-opacity' => true,
			'pathlength'     => true,
		),
		'rect'     => $common + array(
			'x'      => true,
			'y'      => true,
			'width'  => true,
			'height' => true,
		),
		'defs'     => array(),
		'clippath' => array( 'id' => true ),
	);
}
