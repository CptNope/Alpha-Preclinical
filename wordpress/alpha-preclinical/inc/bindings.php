<?php
/**
 * Block Bindings: the data layer of the theme.
 *
 * Sources
 * - alpha/site         Site details from Settings > Alpha Preclinical, plus live counts.
 * - alpha/publication  Computed fields of a publication (citation, linked title, IDs).
 * - alpha/person       A team member, from the Query Loop context or by `slug` arg.
 * - alpha/job          An open role.
 * - alpha/study        Quote-form links with the study type preset (`study` arg).
 *
 * Plain meta fields can also be bound with the core/post-meta source.
 *
 * The `block_bindings_supported_attributes` filter (WordPress 6.9) makes the
 * content attributes of every alpha/* block bindable too.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bindable attributes of the theme's custom blocks.
 *
 * @return array<string,string[]>
 */
function alpha_bindable_attributes() {
	return array(
		'alpha/stat'         => array( 'value', 'suffix', 'label' ),
		'alpha/lead-chip'    => array( 'members', 'label' ),
		'alpha/faq-item'     => array( 'question', 'answer' ),
		'alpha/cta-band'     => array( 'heading', 'text', 'buttonText', 'buttonUrl', 'secondaryText', 'secondaryUrl' ),
		'alpha/page-header'  => array( 'heading', 'lead' ),
		'alpha/contact-card' => array( 'email', 'address', 'phone' ),
		'alpha/person-links' => array( 'linkedin', 'orcid', 'scholar', 'pubmed' ),
		'alpha/person-card'  => array( 'slug' ),
		'alpha/breadcrumbs'  => array( 'current' ),
	);
}

/**
 * Let block bindings target the alpha/* attributes above.
 *
 * @param string[] $supported  Supported attributes.
 * @param string   $block_type Block name.
 * @return string[]
 */
function alpha_supported_binding_attributes( $supported, $block_type ) {
	$map = alpha_bindable_attributes();
	if ( isset( $map[ $block_type ] ) ) {
		$supported = array_values( array_unique( array_merge( (array) $supported, $map[ $block_type ] ) ) );
	}
	return $supported;
}
add_filter( 'block_bindings_supported_attributes', 'alpha_supported_binding_attributes', 10, 2 );

/**
 * Records which publications, people and jobs were rendered, so schema.php can
 * describe exactly what is on the page.
 *
 * @param string $type Record type.
 * @param int    $id   Post ID.
 * @return int[]|null All recorded IDs of the type when $id is 0.
 */
function alpha_schema_record( $type, $id = 0 ) {
	static $seen = array();
	if ( $id ) {
		$seen[ $type ][ $id ] = true;
		return null;
	}
	return isset( $seen[ $type ] ) ? array_keys( $seen[ $type ] ) : array();
}

/**
 * Post ID from binding context, or from a `slug` arg for a post type.
 *
 * @param array    $args      Binding args.
 * @param WP_Block $block     Block instance.
 * @param string   $post_type Expected post type.
 * @return int
 */
function alpha_binding_post_id( $args, $block, $post_type ) {
	// `field`: read the slug from a meta field of the post in context (a post's author_member).
	if ( empty( $args['slug'] ) && ! empty( $args['field'] ) ) {
		$ctx          = $block instanceof WP_Block && isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
		$args['slug'] = $ctx ? (string) get_post_meta( $ctx, sanitize_key( $args['field'] ), true ) : '';
		if ( '' === $args['slug'] ) {
			return 0;
		}
	}
	if ( ! empty( $args['slug'] ) ) {
		$found = get_posts(
			array(
				'post_type'      => $post_type,
				'name'           => sanitize_title( $args['slug'] ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		return $found ? (int) $found[0] : 0;
	}
	$id = $block instanceof WP_Block && isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
	return ( $id && get_post_type( $id ) === $post_type ) ? $id : 0;
}

/**
 * Register the sources.
 */
function alpha_register_binding_sources() {
	register_block_bindings_source(
		'alpha/site',
		array(
			'label'              => __( 'Alpha: site details', 'alpha-preclinical' ),
			'get_value_callback' => 'alpha_binding_site',
		)
	);
	register_block_bindings_source(
		'alpha/publication',
		array(
			'label'              => __( 'Alpha: publication', 'alpha-preclinical' ),
			'get_value_callback' => 'alpha_binding_publication',
			'uses_context'       => array( 'postId', 'postType' ),
		)
	);
	register_block_bindings_source(
		'alpha/person',
		array(
			'label'              => __( 'Alpha: team member', 'alpha-preclinical' ),
			'get_value_callback' => 'alpha_binding_person',
			'uses_context'       => array( 'postId', 'postType' ),
		)
	);
	register_block_bindings_source(
		'alpha/job',
		array(
			'label'              => __( 'Alpha: open role', 'alpha-preclinical' ),
			'get_value_callback' => 'alpha_binding_job',
			'uses_context'       => array( 'postId', 'postType' ),
		)
	);
	register_block_bindings_source(
		'alpha/study',
		array(
			'label'              => __( 'Alpha: study quote link', 'alpha-preclinical' ),
			'get_value_callback' => 'alpha_binding_study',
		)
	);
}
add_action( 'init', 'alpha_register_binding_sources' );

/**
 * Values for alpha/site.
 *
 * @param array $args Binding args: key.
 * @return string|null
 */
function alpha_binding_site( $args ) {
	$key = isset( $args['key'] ) ? (string) $args['key'] : '';
	switch ( $key ) {
		case 'email_link':
			$email = alpha_site( 'email' );
			return $email ? '<a href="mailto:' . esc_attr( antispambot( $email ) ) . '">' . esc_html( antispambot( $email ) ) . '</a>' : '';
		case 'email_url':
			return alpha_site( 'email' ) ? 'mailto:' . antispambot( alpha_site( 'email' ) ) : '';
		case 'phone_link':
			$phone = alpha_site( 'phone' );
			return $phone ? '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>' : '';
		case 'address':
			return esc_html( alpha_site( 'street' ) ) . '<br>' . esc_html( alpha_site( 'locality' ) . ', ' . alpha_site( 'region' ) . ' ' . alpha_site( 'postal_code' ) );
		case 'address_inline':
			return trim( alpha_site( 'street' ) . ', ' . alpha_site( 'locality' ) . ', ' . alpha_site( 'region' ) . ' ' . alpha_site( 'postal_code' ) );
		case 'years':
			$founded = (int) alpha_site( 'founded' );
			return $founded ? (string) max( 1, (int) wp_date( 'Y' ) - $founded ) : '';
		case 'publication_count':
			return (string) (int) wp_count_posts( 'publication' )->publish;
		case 'team_count':
			return (string) (int) wp_count_posts( 'team_member' )->publish;
		case 'job_count':
			return (string) (int) wp_count_posts( 'job' )->publish;
		case 'quote_url':
			return alpha_contact_url();
		case 'copyright':
			return '&copy; ' . esc_html( wp_date( 'Y' ) . ' ' . alpha_site( 'legal_name' ) );
		case 'a11y_promise':
			$t = alpha_site( 'a11y_response' );
			/* translators: %s: response time, e.g. "within 2 business days". */
			return '' !== $t ? esc_html( sprintf( __( 'We aim to respond %s.', 'alpha-preclinical' ), $t ) ) : esc_html__( 'We aim to respond as quickly as we can.', 'alpha-preclinical' );
		case 'a11y_response':
			$t = alpha_site( 'a11y_response' );
			return '' !== $t ? esc_html( $t ) : esc_html__( 'as quickly as we can', 'alpha-preclinical' );
		default:
			return array_key_exists( $key, alpha_site_defaults() ) ? esc_html( alpha_site( $key ) ) : null;
	}
}

/**
 * Values for alpha/publication.
 *
 * @param array    $args  Binding args: key.
 * @param WP_Block $block Block instance.
 * @return string|null
 */
function alpha_binding_publication( $args, $block ) {
	$id = alpha_binding_post_id( $args, $block, 'publication' );
	if ( ! $id ) {
		return null;
	}
	alpha_schema_record( 'publication', $id );
	$m   = static function ( $k ) use ( $id ) {
		return (string) get_post_meta( $id, $k, true );
	};
	$key = isset( $args['key'] ) ? (string) $args['key'] : '';
	switch ( $key ) {
		case 'title':
			return esc_html( get_the_title( $id ) );
		case 'title_link':
			$url = $m( 'pdf_url' ) ? $m( 'pdf_url' ) : ( $m( 'doi' ) ? 'https://doi.org/' . $m( 'doi' ) : '' );
			$t   = esc_html( get_the_title( $id ) );
			return $url ? '<a href="' . esc_url( $url ) . '">' . $t . '</a>' : $t;
		case 'authors':
			return alpha_authors_html( $id );
		case 'journal':
			return esc_html( $m( 'journal' ) );
		case 'source':
			return esc_html( alpha_citation_source( $id ) );
		case 'citation':
			return '<em>' . esc_html( $m( 'journal' ) ) . '</em>. ' . esc_html( alpha_citation_source( $id ) );
		case 'year':
			return esc_html( substr( $m( 'issue_date' ), 0, 4 ) );
		case 'ids':
			$parts = array();
			if ( $m( 'doi' ) ) {
				$parts[] = 'DOI <a href="' . esc_url( 'https://doi.org/' . $m( 'doi' ) ) . '">' . esc_html( $m( 'doi' ) ) . '</a>';
			}
			if ( $m( 'pmid' ) ) {
				$parts[] = 'PMID ' . esc_html( $m( 'pmid' ) );
			}
			if ( $m( 'pmcid' ) ) {
				$parts[] = 'PMCID ' . esc_html( $m( 'pmcid' ) );
			}
			return implode( ' &middot; ', $parts );
		case 'pdf_url':
			return esc_url( $m( 'pdf_url' ) );
		case 'doi_url':
			return $m( 'doi' ) ? esc_url( 'https://doi.org/' . $m( 'doi' ) ) : '';
		case 'pubmed_url':
			return $m( 'pmid' ) ? esc_url( 'https://pubmed.ncbi.nlm.nih.gov/' . rawurlencode( $m( 'pmid' ) ) . '/' ) : '';
		case 'pmc_url':
			return $m( 'pmcid' ) ? esc_url( 'https://pmc.ncbi.nlm.nih.gov/articles/' . rawurlencode( $m( 'pmcid' ) ) . '/' ) : '';
		case 'area':
			$terms = get_the_terms( $id, 'research_area' );
			return is_array( $terms ) ? esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) ) : '';
		default:
			return null;
	}
}

/**
 * Values for alpha/person.
 *
 * @param array    $args  Binding args: key, optional slug.
 * @param WP_Block $block Block instance.
 * @return string|null
 */
function alpha_binding_person( $args, $block ) {
	$id = alpha_binding_post_id( $args, $block, 'team_member' );
	if ( ! $id ) {
		return null;
	}
	alpha_schema_record( 'person', $id );
	$m   = static function ( $k ) use ( $id ) {
		return (string) get_post_meta( $id, $k, true );
	};
	$key = isset( $args['key'] ) ? (string) $args['key'] : '';
	switch ( $key ) {
		case 'name':
			return esc_html( alpha_person_name( $id ) );
		case 'name_link':
			return '<a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( alpha_person_name( $id ) ) . '</a>';
		case 'job_title':
		case 'short_title':
			$v = $m( $key );
			return esc_html( '' !== $v ? $v : $m( 'job_title' ) );
		case 'url':
			return esc_url( get_permalink( $id ) );
		case 'photo_url':
			return esc_url( (string) get_the_post_thumbnail_url( $id, 'large' ) );
		case 'bio':
			return wp_kses_post( get_the_excerpt( $id ) );
		case 'education':
		case 'highlights':
		case 'leads':
			$sep   = 'leads' === $key ? ',' : "\n";
			$items = array_filter( array_map( 'trim', explode( $sep, $m( $key ) ) ) );
			return $items ? '<li>' . implode( '</li><li>', array_map( 'esc_html', $items ) ) . '</li>' : '';
		case 'linkedin':
		case 'orcid':
		case 'scholar':
			return esc_url( $m( $key ) );
		case 'pubmed_url':
			$ids = array_filter( array_map( 'trim', explode( ',', $m( 'pubmed_ids' ) ) ) );
			// PubMed has no author pages; search for exactly this person's PMIDs.
			return $ids ? esc_url( 'https://pubmed.ncbi.nlm.nih.gov/?term=' . rawurlencode( implode( '[pmid] OR ', $ids ) . '[pmid]' ) ) : '';
		case 'paper_count':
			$ids = array_filter( array_map( 'trim', explode( ',', $m( 'pubmed_ids' ) ) ) );
			return (string) count( $ids );
		default:
			return null;
	}
}

/**
 * Values for alpha/job.
 *
 * @param array    $args  Binding args: key.
 * @param WP_Block $block Block instance.
 * @return string|null
 */
function alpha_binding_job( $args, $block ) {
	$id = alpha_binding_post_id( $args, $block, 'job' );
	if ( ! $id ) {
		return null;
	}
	alpha_schema_record( 'job', $id );
	$key = isset( $args['key'] ) ? (string) $args['key'] : '';
	switch ( $key ) {
		case 'title':
			return esc_html( get_the_title( $id ) );
		case 'summary':
			return esc_html( (string) get_post_meta( $id, 'summary', true ) );
		case 'requirements':
			$items = array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $id, 'requirements', true ) ) ) );
			return $items ? '<li>' . implode( '</li><li>', array_map( 'esc_html', $items ) ) . '</li>' : '';
		case 'team':
			$terms = get_the_terms( $id, 'job_team' );
			return is_array( $terms ) ? esc_html( $terms[0]->name ) : '';
		case 'location':
			$loc = (string) get_post_meta( $id, 'location', true );
			return esc_html( '' !== $loc ? $loc : alpha_site( 'locality' ) . ', ' . alpha_site( 'region' ) );
		case 'apply_url':
			$careers = get_page_by_path( 'careers' );
			$base    = $careers ? get_permalink( $careers ) : home_url( '/careers/' );
			return esc_url( add_query_arg( 'role', rawurlencode( get_the_title( $id ) ), $base ) . '#apply' );
		default:
			return null;
	}
}

/**
 * Values for alpha/study.
 *
 * @param array $args Binding args: study, key (url|label).
 * @return string
 */
function alpha_binding_study( $args ) {
	$study = isset( $args['study'] ) ? sanitize_text_field( (string) $args['study'] ) : '';
	$key   = isset( $args['key'] ) ? (string) $args['key'] : 'url';
	if ( 'label' === $key ) {
		/* translators: %s: study type, e.g. PK/PD. */
		return esc_html( '' !== $study ? sprintf( __( 'Plan your %s study', 'alpha-preclinical' ), $study ) : __( 'Request a study quote', 'alpha-preclinical' ) );
	}
	return esc_url( alpha_contact_url( $study ) );
}

/**
 * Drop core blocks whose binding came back empty (a paper with no PMCID should not
 * show an empty "Free full text" button, a person with no LinkedIn no icon).
 *
 * @param string   $content Rendered block.
 * @param array    $block   Parsed block.
 * @param WP_Block $instance Block instance.
 * @return string
 */
function alpha_hide_empty_bound_blocks( $content, $block, $instance ) {
	$bindings = isset( $block['attrs']['metadata']['bindings'] ) ? $block['attrs']['metadata']['bindings'] : array();
	if ( ! $bindings || ! ( $instance instanceof WP_Block ) ) {
		return $content;
	}
	$checks = array(
		'core/button'    => 'url',
		'core/paragraph' => 'content',
		'core/heading'   => 'content',
		'core/image'     => 'url',
	);
	$name = $block['blockName'];
	if ( isset( $checks[ $name ], $bindings[ $checks[ $name ] ] ) ) {
		$attr = $checks[ $name ];
		if ( ! isset( $instance->attributes[ $attr ] ) || '' === trim( wp_strip_all_tags( (string) $instance->attributes[ $attr ] ) ) ) {
			// A paragraph or heading bound to markup (e.g. a link) is not empty.
			if ( 'url' !== $attr && isset( $instance->attributes[ $attr ] ) && false !== strpos( (string) $instance->attributes[ $attr ], '<' ) ) {
				return $content;
			}
			return '';
		}
	}
	return $content;
}
add_filter( 'render_block', 'alpha_hide_empty_bound_blocks', 20, 3 );
