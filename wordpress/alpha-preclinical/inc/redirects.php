<?php
/**
 * 301 redirects from the old Wix site so links and rankings carry over.
 *
 * Built-in map below, plus "Extra redirects" in Settings > Alpha Preclinical.
 * Old PDF links (/_files/ugd/…) go to the publication's current PDF.
 * Before launch: crawl the live Wix site (or export its sitemap) and add any
 * paths missing here; then check Search Console for 404s after launch.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Old path => new path. Paths are compared lower-case without trailing slashes.
 *
 * @return array<string,string>
 */
function alpha_redirect_map() {
	$map = array(
		'/home'                  => '/',
		'/experetise'            => '/expertise/',
		'/expertise-1'           => '/expertise/',
		'/about-us'              => '/about/',
		'/about'                 => '/about/',
		'/our-team'              => '/team/',
		'/team-1'                => '/team/',
		'/contact-us'            => '/contact/',
		'/contact-1'             => '/contact/',
		'/careers-1'             => '/careers/',
		'/jobs'                  => '/careers/',
		'/publications-1'        => '/publications/',
		'/pk-pd'                 => '/expertise/#pk-pd',
		'/pkpd'                  => '/expertise/#pk-pd',
		'/gene-therapy'          => '/expertise/#gene-therapy',
		'/autoimmune'            => '/expertise/#autoimmune',
		'/autoimmune-disease'    => '/expertise/#autoimmune',
		'/metabolic'             => '/expertise/#metabolic',
		'/metabolic-disease'     => '/expertise/#metabolic',
		'/inflammation-fibrosis' => '/expertise/#fibrosis',
		'/fibrosis'              => '/expertise/#fibrosis',
		'/oncology'              => '/tumor-models/',
		'/tumor-models-1'        => '/tumor-models/',
		'/ivis'                  => '/services/#ivis',
		'/ivis-imaging'          => '/services/#ivis',
		'/surgical-services'     => '/services/#surgery',
		'/surgery'               => '/services/#surgery',
		'/colony-management'     => '/services/#colony',
		'/in-vitro'              => '/services/#in-vitro',
		'/in-vitro-services'     => '/services/#in-vitro',
		'/blog-1'                => '/blog/',
	);
	foreach ( preg_split( '/\r\n|\r|\n/', alpha_site( 'redirects' ) ) as $line ) {
		$parts = array_map( 'trim', explode( '=', $line, 2 ) );
		if ( 2 === count( $parts ) && '' !== $parts[0] && '' !== $parts[1] ) {
			$map[ '/' . trim( strtolower( $parts[0] ), '/' ) ] = $parts[1];
		}
	}
	/**
	 * Filters the old-URL redirect map.
	 *
	 * @param array<string,string> $map Old path => new path.
	 */
	return apply_filters( 'alpha_redirect_map', $map );
}

/**
 * Redirect requests that would otherwise 404.
 */
function alpha_legacy_redirects() {
	if ( ! is_404() ) {
		return;
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	$home = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$path = '/' . trim( strtolower( substr( (string) $uri, strlen( (string) $home ) - 1 ) ), '/' );

	// Old Wix PDF links: /_files/ugd/<file>.pdf → the publication's PDF now.
	if ( 0 === strpos( $path, '/_files/ugd/' ) ) {
		$file = basename( $path );
		$hits = get_posts(
			array(
				'post_type'      => 'publication',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 404 path only.
					array(
						'key'     => 'legacy_pdf',
						'value'   => $file,
						'compare' => 'LIKE',
					),
				),
			)
		);
		$pdf  = $hits ? (string) get_post_meta( $hits[0], 'pdf_url', true ) : '';
		$dest = ( $pdf && false === strpos( $pdf, '/_files/ugd/' ) ) ? $pdf : home_url( '/publications/' );
		wp_safe_redirect( $dest, 301 );
		exit;
	}

	$map = alpha_redirect_map();
	if ( isset( $map[ $path ] ) ) {
		$dest = $map[ $path ];
		wp_safe_redirect( 0 === strpos( $dest, 'http' ) ? $dest : home_url( $dest ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'alpha_legacy_redirects', 1 );

/**
 * Allow redirects to the hosts PDFs may live on.
 *
 * @param string[] $hosts Allowed hosts.
 * @return string[]
 */
function alpha_redirect_hosts( $hosts ) {
	$hosts[] = 'doi.org';
	return $hosts;
}
add_filter( 'allowed_redirect_hosts', 'alpha_redirect_hosts' );
