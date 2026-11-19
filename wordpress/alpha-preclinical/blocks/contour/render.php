<?php
/**
 * Contour line: a single wave line used as a section divider. Draws in on arrival.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

$alpha_tone = isset( $attributes['tone'] ) && 'shallows' === $attributes['tone'] ? 'shallows' : 'current';
$alpha_wrap = get_block_wrapper_attributes( array( 'class' => 'alpha-contour alpha-contour--' . $alpha_tone . ( ! empty( $attributes['draw'] ) ? ' alpha-reveal' : '' ) ) );
?>
<div <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<svg viewBox="0 0 1200 28" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path class="alpha-draw" pathLength="1" d="M0 14 C150 2 300 26 450 14 S750 2 900 14 S1110 24 1200 12"></path></svg>
</div>
