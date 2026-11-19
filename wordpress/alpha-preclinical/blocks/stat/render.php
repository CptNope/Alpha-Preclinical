<?php
/**
 * Stat: a big number that counts up on arrival. Screen readers get the real value.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

$alpha_value  = isset( $attributes['value'] ) ? wp_strip_all_tags( (string) $attributes['value'] ) : '';
$alpha_suffix = isset( $attributes['suffix'] ) ? wp_strip_all_tags( (string) $attributes['suffix'] ) : '';
$alpha_label  = isset( $attributes['label'] ) ? wp_strip_all_tags( (string) $attributes['label'] ) : '';
$alpha_size   = isset( $attributes['size'] ) && in_array( $attributes['size'], array( 'm', 'l', 'xl' ), true ) ? $attributes['size'] : 'm';
if ( '' === $alpha_value ) {
	return;
}
$alpha_wrap = get_block_wrapper_attributes( array( 'class' => 'alpha-stat alpha-stat--' . $alpha_size ) );
?>
<div <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<span class="screen-reader-text"><?php echo esc_html( trim( $alpha_value . $alpha_suffix . ' ' . $alpha_label ) ); ?></span>
	<span class="alpha-stat__value" aria-hidden="true"><span data-count="<?php echo esc_attr( $alpha_value ); ?>"><?php echo esc_html( $alpha_value ); ?></span><span><?php echo esc_html( $alpha_suffix ); ?></span></span>
	<?php if ( '' !== $alpha_label ) : ?>
		<span class="alpha-stat__label" aria-hidden="true"><?php echo esc_html( $alpha_label ); ?></span>
	<?php endif; ?>
</div>
