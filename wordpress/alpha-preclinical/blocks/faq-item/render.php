<?php
/**
 * One FAQ question. A real button controls the answer (aria-expanded / hidden).
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

$alpha_q = isset( $attributes['question'] ) ? (string) $attributes['question'] : '';
$alpha_a = isset( $attributes['answer'] ) ? (string) $attributes['answer'] : '';
if ( '' === trim( wp_strip_all_tags( $alpha_q ) ) ) {
	return;
}
$alpha_id   = wp_unique_id( 'alpha-faq-' );
$alpha_open = ! empty( $attributes['open'] );
$alpha_wrap = get_block_wrapper_attributes( array( 'class' => 'alpha-faq__item' ) );
?>
<div <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<h3 class="alpha-faq__q">
		<button type="button" aria-expanded="<?php echo $alpha_open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $alpha_id ); ?>" data-accordion>
			<span><?php echo wp_kses( $alpha_q, array( 'em' => array(), 'strong' => array() ) ); ?></span>
			<span class="alpha-faq__icon" aria-hidden="true"><svg viewBox="0 0 12 12" width="14" height="14" focusable="false"><path d="M2 4l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path></svg></span>
		</button>
	</h3>
	<div class="alpha-faq__a" id="<?php echo esc_attr( $alpha_id ); ?>"<?php echo $alpha_open ? '' : ' hidden'; ?>><p><?php echo wp_kses_post( $alpha_a ); ?></p></div>
</div>
