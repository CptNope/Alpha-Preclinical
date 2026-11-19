<?php
/**
 * Team member card: 4:5 portrait (a second photo swaps in on hover), name, role.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

$alpha_id = alpha_binding_post_id( array( 'slug' => isset( $attributes['slug'] ) ? $attributes['slug'] : '' ), $block, 'team_member' );
if ( ! $alpha_id ) {
	return;
}
alpha_schema_record( 'person', $alpha_id );
$alpha_field = isset( $attributes['titleField'] ) && 'job_title' === $attributes['titleField'] ? 'job_title' : 'short_title';
$alpha_title = (string) get_post_meta( $alpha_id, $alpha_field, true );
if ( '' === $alpha_title ) {
	$alpha_title = (string) get_post_meta( $alpha_id, 'job_title', true );
}
$alpha_alt  = (int) get_post_meta( $alpha_id, 'alt_photo', true );
$alpha_wrap = get_block_wrapper_attributes( array( 'class' => 'alpha-person' ) );
?>
<a <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> href="<?php echo esc_url( get_permalink( $alpha_id ) ); ?>">
	<span class="alpha-person__face">
		<?php echo get_the_post_thumbnail( $alpha_id, 'large', array( 'alt' => '', 'loading' => 'lazy' ) ); ?>
		<?php if ( $alpha_alt ) : ?>
			<?php echo wp_get_attachment_image( $alpha_alt, 'large', false, array( 'alt' => '', 'class' => 'alpha-person__alt', 'loading' => 'lazy' ) ); ?>
		<?php endif; ?>
	</span>
	<span class="alpha-person__text">
		<b><?php echo esc_html( alpha_person_name( $alpha_id ) ); ?></b>
		<?php if ( '' !== $alpha_title ) : ?>
			<small><?php echo esc_html( $alpha_title ); ?></small>
		<?php endif; ?>
		<?php if ( ! empty( $attributes['showLeads'] ) && get_post_meta( $alpha_id, 'leads', true ) ) : ?>
			<small class="alpha-person__leads"><?php echo esc_html( get_post_meta( $alpha_id, 'leads', true ) ); ?></small>
		<?php endif; ?>
	</span>
</a>
