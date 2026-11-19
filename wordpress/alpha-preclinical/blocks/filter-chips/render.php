<?php
/**
 * Filter chips: pill toggles that filter the next Query Loop by term, in place.
 * Items carry `alpha-filter-{term}` classes (functions.php, post_class); theme.js
 * hides the rest and announces the count in a status region (WCAG 4.1.3).
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

$alpha_tax = isset( $attributes['taxonomy'] ) ? sanitize_key( $attributes['taxonomy'] ) : 'research_area';
if ( ! taxonomy_exists( $alpha_tax ) ) {
	return;
}
$alpha_terms = get_terms(
	array(
		'taxonomy'   => $alpha_tax,
		'hide_empty' => true,
	)
);
if ( is_wp_error( $alpha_terms ) || ! $alpha_terms ) {
	return;
}
$alpha_total  = 0;
$alpha_counts = ! empty( $attributes['showCounts'] );
foreach ( $alpha_terms as $alpha_term ) {
	$alpha_total += (int) $alpha_term->count;
}
$alpha_all  = isset( $attributes['allLabel'] ) && '' !== $attributes['allLabel'] ? $attributes['allLabel'] : __( 'All', 'alpha-preclinical' );
$alpha_lbl  = isset( $attributes['label'] ) ? $attributes['label'] : '';
$alpha_wrap = get_block_wrapper_attributes( array( 'class' => 'alpha-filters' ) );
?>
<div <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-filter-group>
	<div class="alpha-filters__chips" role="group" aria-label="<?php echo esc_attr( $alpha_lbl ); ?>">
		<button type="button" class="alpha-chip" aria-pressed="true" data-filter=""><?php echo esc_html( $alpha_all ); ?><?php echo $alpha_counts ? ' <small>' . (int) $alpha_total . '</small>' : ''; ?></button>
		<?php foreach ( $alpha_terms as $alpha_term ) : ?>
			<button type="button" class="alpha-chip" aria-pressed="false" data-filter="<?php echo esc_attr( 'alpha-filter-' . sanitize_html_class( $alpha_term->slug ) ); ?>"><?php echo esc_html( $alpha_term->name ); ?><?php echo $alpha_counts ? ' <small>' . (int) $alpha_term->count . '</small>' : ''; ?></button>
		<?php endforeach; ?>
	</div>
	<p class="screen-reader-text" role="status" aria-live="polite" data-filter-status></p>
</div>
