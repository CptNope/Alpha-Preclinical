<?php
/**
 * Contact details card. Values default to Settings > Alpha Preclinical.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

$alpha_email   = isset( $attributes['email'] ) && '' !== $attributes['email'] ? wp_strip_all_tags( $attributes['email'] ) : alpha_site( 'email' );
$alpha_address = isset( $attributes['address'] ) && '' !== $attributes['address'] ? wp_kses_post( $attributes['address'] ) : alpha_binding_site( array( 'key' => 'address' ) );
$alpha_phone   = isset( $attributes['phone'] ) && '' !== $attributes['phone'] ? wp_strip_all_tags( $attributes['phone'] ) : alpha_site( 'phone' );
$alpha_careers = get_page_by_path( 'careers' );
$alpha_wrap    = get_block_wrapper_attributes( array( 'class' => 'alpha-contact-card' ) );
?>
<div <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $alpha_email ) : ?>
		<div><h3><?php esc_html_e( 'Email', 'alpha-preclinical' ); ?></h3><a class="alpha-contact-card__big" href="<?php echo esc_attr( 'mailto:' . antispambot( $alpha_email ) ); ?>"><?php echo esc_html( antispambot( $alpha_email ) ); ?></a></div>
	<?php endif; ?>
	<?php if ( $alpha_phone ) : ?>
		<div><h3><?php esc_html_e( 'Phone', 'alpha-preclinical' ); ?></h3><a class="alpha-contact-card__big" href="<?php echo esc_attr( 'tel:' . preg_replace( '/[^0-9+]/', '', $alpha_phone ) ); ?>"><?php echo esc_html( $alpha_phone ); ?></a></div>
	<?php endif; ?>
	<div><h3><?php esc_html_e( 'Visit', 'alpha-preclinical' ); ?></h3><address class="alpha-contact-card__big"><?php echo wp_kses_post( $alpha_address ); ?></address>
		<?php if ( alpha_site( 'map_url' ) ) : ?>
			<p><a href="<?php echo esc_url( alpha_site( 'map_url' ) ); ?>"><?php esc_html_e( 'Get directions', 'alpha-preclinical' ); ?></a></p>
		<?php endif; ?>
	</div>
	<?php if ( ! empty( $attributes['showCareers'] ) && $alpha_careers ) : ?>
		<div><h3><?php esc_html_e( 'Careers', 'alpha-preclinical' ); ?></h3><p><?php
			printf(
				/* translators: %s: link to the careers page. */
				esc_html__( 'See our %s.', 'alpha-preclinical' ),
				'<a href="' . esc_url( get_permalink( $alpha_careers ) ) . '">' . esc_html__( 'open roles', 'alpha-preclinical' ) . '</a>'
			);
		?></p></div>
	<?php endif; ?>
</div>
