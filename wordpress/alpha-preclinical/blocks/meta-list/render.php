<?php
/**
 * A bulleted list from a multi-line field of the person or role in context.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

$alpha_source = isset( $attributes['source'] ) && 'job' === $attributes['source'] ? 'job' : 'person';
$alpha_key    = isset( $attributes['key'] ) ? sanitize_key( $attributes['key'] ) : 'highlights';
$alpha_items  = 'job' === $alpha_source
	? alpha_binding_job( array( 'key' => $alpha_key ), $block )
	: alpha_binding_person( array( 'key' => $alpha_key ), $block );
if ( ! $alpha_items ) {
	return;
}
$alpha_wrap = get_block_wrapper_attributes( array( 'class' => 'alpha-meta-list' ) );
?>
<ul <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo wp_kses_post( $alpha_items ); ?></ul>
