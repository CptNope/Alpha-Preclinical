<?php
/**
 * Structured data. Organization on every page; Person, ScholarlyArticle and
 * JobPosting for exactly the people, papers and roles the page rendered (recorded by
 * the binding sources and blocks). Article schema on posts is left to the SEO plugin
 * (Rank Math or Yoast) so it is not printed twice.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Print one JSON-LD block.
 *
 * @param array $data Schema data.
 */
function alpha_print_json_ld( $data ) {
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' . "\n";
}

/**
 * Organization (MedicalBusiness is too narrow for a CRO; ResearchOrganization fits).
 *
 * @return array
 */
function alpha_org_schema() {
	$org = array(
		'@type'        => array( 'Organization', 'ResearchOrganization' ),
		'@id'          => home_url( '/#organization' ),
		'name'         => alpha_site( 'legal_name' ),
		'url'          => home_url( '/' ),
		'email'        => alpha_site( 'email' ),
		'foundingDate' => alpha_site( 'founded' ),
		'address'      => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => alpha_site( 'street' ),
			'addressLocality' => alpha_site( 'locality' ),
			'addressRegion'   => alpha_site( 'region' ),
			'postalCode'      => alpha_site( 'postal_code' ),
			'addressCountry'  => 'US',
		),
		'sameAs'       => array_values( array_filter( array( alpha_site( 'linkedin' ) ) ) ),
	);
	if ( alpha_site( 'phone' ) ) {
		$org['telephone'] = alpha_site( 'phone' );
	}
	$logo = get_theme_file_uri( 'assets/img/logo-color.png' );
	if ( has_custom_logo() ) {
		$logo = wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'full' );
	}
	$org['logo'] = $logo;
	return array_filter( $org );
}

/**
 * Organization in the head of every page.
 */
function alpha_head_schema() {
	alpha_print_json_ld( array( '@context' => 'https://schema.org' ) + alpha_org_schema() );
}
add_action( 'wp_head', 'alpha_head_schema', 20 );

/**
 * Person schema for one team member.
 *
 * @param int $id Post ID.
 * @return array
 */
function alpha_person_schema( $id ) {
	$m       = static function ( $k ) use ( $id ) {
		return (string) get_post_meta( $id, $k, true );
	};
	$parts   = explode( ' ', get_the_title( $id ) );
	$person  = array(
		'@type'      => 'Person',
		'@id'        => get_permalink( $id ) . '#person',
		'name'       => get_the_title( $id ),
		'givenName'  => $parts[0],
		'familyName' => end( $parts ),
		'url'        => get_permalink( $id ),
		'jobTitle'   => $m( 'job_title' ),
		'description' => wp_strip_all_tags( get_the_excerpt( $id ) ),
		'image'      => get_the_post_thumbnail_url( $id, 'large' ),
		'worksFor'   => array( '@id' => home_url( '/#organization' ) ),
		'sameAs'     => array_values( array_filter( array( $m( 'linkedin' ), $m( 'orcid' ), $m( 'scholar' ) ) ) ),
		'knowsAbout' => array_values( array_filter( array_map( 'trim', explode( ',', $m( 'leads' ) ) ) ) ),
	);
	if ( $m( 'credentials' ) ) {
		$person['honorificSuffix'] = $m( 'credentials' );
	}
	return array_filter( $person );
}

/**
 * ScholarlyArticle schema for one publication.
 *
 * @param int $id Post ID.
 * @return array
 */
function alpha_publication_schema( $id ) {
	$m       = static function ( $k ) use ( $id ) {
		return (string) get_post_meta( $id, $k, true );
	};
	$authors = array();
	foreach ( array_filter( array_map( 'trim', explode( ',', $m( 'authors' ) ) ) ) as $name ) {
		$authors[] = array(
			'@type' => 'Person',
			'name'  => $name,
		);
	}
	$ids = array();
	foreach ( array( 'doi' => 'DOI', 'pmid' => 'PMID', 'pmcid' => 'PMCID' ) as $key => $label ) {
		if ( $m( $key ) ) {
			$ids[] = array(
				'@type'      => 'PropertyValue',
				'propertyID' => $label,
				'value'      => $m( $key ),
			);
		}
	}
	return array_filter(
		array(
			'@type'         => 'ScholarlyArticle',
			'headline'      => get_the_title( $id ),
			'name'          => get_the_title( $id ),
			'author'        => $authors,
			'datePublished' => alpha_nlm_to_iso( $m( 'epub_date' ) ? $m( 'epub_date' ) : $m( 'issue_date' ) ),
			'isPartOf'      => array_filter(
				array(
					'@type'        => 'PublicationIssue',
					'issueNumber'  => $m( 'issue' ),
					'isPartOf'     => array_filter(
						array(
							'@type'        => 'PublicationVolume',
							'volumeNumber' => $m( 'volume' ),
							'isPartOf'     => array(
								'@type' => 'Periodical',
								'name'  => $m( 'journal' ),
							),
						)
					),
				)
			),
			'pagination'    => $m( 'pages' ),
			'sameAs'        => $m( 'doi' ) ? 'https://doi.org/' . $m( 'doi' ) : '',
			'url'           => $m( 'pdf_url' ),
			'identifier'    => $ids,
		)
	);
}

/**
 * JobPosting schema for one role.
 *
 * @param int $id Post ID.
 * @return array
 */
function alpha_job_schema( $id ) {
	$m        = static function ( $k ) use ( $id ) {
		return (string) get_post_meta( $id, $k, true );
	};
	$desc     = '<p>' . esc_html( $m( 'summary' ) ) . '</p>';
	$reqs     = array_filter( array_map( 'trim', explode( "\n", $m( 'requirements' ) ) ) );
	if ( $reqs ) {
		$desc .= '<ul><li>' . implode( '</li><li>', array_map( 'esc_html', $reqs ) ) . '</li></ul>';
	}
	$desc    .= wp_kses_post( get_post_field( 'post_content', $id ) );
	$posting  = array(
		'@type'              => 'JobPosting',
		'title'              => get_the_title( $id ),
		'description'        => $desc,
		'datePosted'         => get_the_date( 'Y-m-d', $id ),
		'employmentType'     => $m( 'employment_type' ) ? $m( 'employment_type' ) : 'FULL_TIME',
		'hiringOrganization' => array(
			'@type'  => 'Organization',
			'name'   => alpha_site( 'legal_name' ),
			'sameAs' => home_url( '/' ),
		),
		'jobLocation'        => array(
			'@type'   => 'Place',
			'address' => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => alpha_site( 'street' ),
				'addressLocality' => alpha_site( 'locality' ),
				'addressRegion'   => alpha_site( 'region' ),
				'postalCode'      => alpha_site( 'postal_code' ),
				'addressCountry'  => 'US',
			),
		),
		'directApply'        => true,
	);
	if ( $m( 'valid_through' ) ) {
		$posting['validThrough'] = $m( 'valid_through' );
	}
	return $posting;
}

/**
 * Schema for what the page actually rendered.
 */
function alpha_footer_schema() {
	$graph = array();
	foreach ( alpha_schema_record( 'person' ) as $id ) {
		$graph[] = alpha_person_schema( $id );
	}
	foreach ( alpha_schema_record( 'publication' ) as $id ) {
		$graph[] = alpha_publication_schema( $id );
	}
	if ( is_singular( 'team_member' ) ) {
		$graph[] = alpha_person_schema( get_queried_object_id() );
	}
	if ( $graph ) {
		alpha_print_json_ld(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => array_values( array_unique( $graph, SORT_REGULAR ) ),
			)
		);
	}
	// Google wants one JobPosting per page, on the role's own page.
	if ( is_singular( 'job' ) ) {
		alpha_print_json_ld( array( '@context' => 'https://schema.org' ) + alpha_job_schema( get_queried_object_id() ) );
	}
}
add_action( 'wp_footer', 'alpha_footer_schema', 20 );
