<?php
/**
 * Profile links for a team member. Each value can be typed, bound, or left empty to
 * come from the person in context.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

$alpha_links = array(
	'linkedin' => __( 'LinkedIn', 'alpha-preclinical' ),
	'orcid'    => __( 'ORCID', 'alpha-preclinical' ),
	'scholar'  => __( 'Google Scholar', 'alpha-preclinical' ),
	'pubmed'   => __( 'PubMed', 'alpha-preclinical' ),
);
$alpha_out = array();
foreach ( $alpha_links as $alpha_key => $alpha_label ) {
	$alpha_url = isset( $attributes[ $alpha_key ] ) ? trim( (string) $attributes[ $alpha_key ] ) : '';
	if ( '' === $alpha_url ) {
		$alpha_args = array( 'key' => 'pubmed' === $alpha_key ? 'pubmed_url' : $alpha_key );
		if ( ! empty( $attributes['field'] ) ) {
			$alpha_args['field'] = $attributes['field'];
		}
		$alpha_url = (string) alpha_binding_person( $alpha_args, $block );
	}
	if ( '' !== $alpha_url ) {
		$alpha_out[] = '<a class="alpha-chip alpha-chip--link" href="' . esc_url( $alpha_url ) . '">' . esc_html( $alpha_label ) . '</a>';
	}
}
if ( ! $alpha_out ) {
	return;
}
$alpha_wrap = get_block_wrapper_attributes( array( 'class' => 'alpha-person-links' ) );
?>
<p <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo wp_kses_post( implode( ' ', $alpha_out ) ); ?></p>
