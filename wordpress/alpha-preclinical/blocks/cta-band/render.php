<?php
/**
 * Quote band: the closing deep-blue band with contour lines. Every page ends with one.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

$alpha_get = static function ( $key ) use ( $attributes ) {
	return isset( $attributes[ $key ] ) ? trim( (string) $attributes[ $key ] ) : '';
};
$alpha_url  = '' !== $alpha_get( 'buttonUrl' ) ? $alpha_get( 'buttonUrl' ) : alpha_contact_url();
$alpha_url2 = '' !== $alpha_get( 'secondaryUrl' ) ? $alpha_get( 'secondaryUrl' ) : alpha_contact_url( 'Something else' );
$alpha_line = 'M0 60 C240 20 480 100 720 60 S1200 20 1440 60';
$alpha_wrap = get_block_wrapper_attributes( array( 'class' => 'alpha-cta alpha-reveal' ) );
?>
<section <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<svg class="alpha-cta__lines alpha-cta__lines--top" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path class="alpha-draw" pathLength="1" d="<?php echo esc_attr( $alpha_line ); ?>"></path><path class="alpha-draw" pathLength="1" d="M0 86 C240 50 480 120 720 86 S1200 50 1440 86"></path></svg>
	<div class="alpha-cta__body">
		<h2><?php echo wp_kses_post( $alpha_get( 'heading' ) ); ?></h2>
		<?php if ( '' !== $alpha_get( 'text' ) ) : ?>
			<p><?php echo wp_kses_post( $alpha_get( 'text' ) ); ?></p>
		<?php endif; ?>
		<div class="alpha-cta__actions">
			<?php if ( '' !== $alpha_get( 'buttonText' ) ) : ?>
				<a class="alpha-btn alpha-btn--light" href="<?php echo esc_url( $alpha_url ); ?>"><?php echo esc_html( wp_strip_all_tags( $alpha_get( 'buttonText' ) ) ); ?></a>
			<?php endif; ?>
			<?php if ( '' !== $alpha_get( 'secondaryText' ) ) : ?>
				<a class="alpha-btn alpha-btn--ghost-dark" href="<?php echo esc_url( $alpha_url2 ); ?>"><?php echo esc_html( wp_strip_all_tags( $alpha_get( 'secondaryText' ) ) ); ?></a>
			<?php endif; ?>
		</div>
	</div>
	<svg class="alpha-cta__lines alpha-cta__lines--bottom" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path class="alpha-draw" pathLength="1" d="<?php echo esc_attr( $alpha_line ); ?>"></path></svg>
</section>
