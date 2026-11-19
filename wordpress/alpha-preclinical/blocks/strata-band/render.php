<?php
/**
 * Strata band: the drifting wave bands in the brand blues, with a Pause button.
 * hero = five bands, page = four (every inner page header), thin = two (blog posts).
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

$alpha_variant = isset( $attributes['variant'] ) && in_array( $attributes['variant'], array( 'hero', 'page', 'thin' ), true ) ? $attributes['variant'] : 'page';
$alpha_melt    = isset( $attributes['meltInto'] ) && in_array( $attributes['meltInto'], array( 'paper', 'mist', 'abyss' ), true ) ? $attributes['meltInto'] : 'paper';
$alpha_pause   = ! isset( $attributes['showPause'] ) || $attributes['showPause'];

$alpha_sets = array(
	// height => [ [ baseline, amplitude, colour ], ... ] top to bottom.
	'hero' => array( 300, array( array( 90, 34, 'tide' ), array( 150, 30, 'current' ), array( 200, 26, 'strata-2' ), array( 240, 22, 'shallows' ), array( 274, 14, $alpha_melt ) ) ),
	'page' => array( 110, array( array( 30, 22, 'tide' ), array( 54, 18, 'current' ), array( 76, 14, 'shallows' ), array( 94, 10, $alpha_melt ) ) ),
	'thin' => array( 44, array( array( 16, 8, 'current' ), array( 32, 6, $alpha_melt ) ) ),
);
list( $alpha_h, $alpha_bands ) = $alpha_sets[ $alpha_variant ];

$alpha_paths = '';
foreach ( $alpha_bands as $alpha_i => $alpha_band ) {
	list( $alpha_y, $alpha_a, $alpha_color ) = $alpha_band;
	$alpha_up = $alpha_y - $alpha_a;
	$alpha_dn = $alpha_y + $alpha_a;
	$alpha_d  = "M0 {$alpha_y} C180 {$alpha_up} 540 {$alpha_dn} 720 {$alpha_y} S1260 {$alpha_dn} 1440 {$alpha_y} S1980 {$alpha_dn} 2160 {$alpha_y} S2700 {$alpha_dn} 2880 {$alpha_y} L2880 {$alpha_h} L0 {$alpha_h} Z";
	$alpha_paths .= sprintf(
		'<path class="ap-wv ap-wv%1$d" d="%2$s" style="fill:var(--wp--preset--color--%3$s)"></path>',
		$alpha_i + 1,
		esc_attr( $alpha_d ),
		esc_attr( $alpha_color )
	);
}

$alpha_wrap = get_block_wrapper_attributes(
	array(
		'class'       => 'alpha-strata alpha-strata--' . $alpha_variant,
		'data-strata' => '',
		'style'       => '--alpha-strata-h:' . $alpha_h . 'px',
	)
);
?>
<div <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<svg viewBox="0 0 1440 <?php echo (int) $alpha_h; ?>" preserveAspectRatio="none" aria-hidden="true" focusable="false"><?php echo wp_kses( $alpha_paths, alpha_svg_kses() ); ?></svg>
	<?php if ( $alpha_pause ) : ?>
		<button class="alpha-motion-toggle" type="button" aria-pressed="false" data-motion-toggle><?php esc_html_e( 'Pause animation', 'alpha-preclinical' ); ?></button>
	<?php endif; ?>
</div>
