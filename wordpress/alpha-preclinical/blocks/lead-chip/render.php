<?php
/**
 * "Led by" chip: the scientist(s) behind an area or service, linked to their profiles.
 * `members` is a comma-separated list of team member slugs (bindable).
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

$alpha_slugs = array_filter( array_map( 'trim', explode( ',', isset( $attributes['members'] ) ? (string) $attributes['members'] : '' ) ) );
$alpha_people = $alpha_slugs ? alpha_get_team( $alpha_slugs ) : array();
if ( ! $alpha_people ) {
	return;
}
$alpha_label = isset( $attributes['label'] ) && '' !== $attributes['label'] ? $attributes['label'] : __( 'Led by', 'alpha-preclinical' );
$alpha_links = array();
foreach ( $alpha_people as $alpha_person ) {
	alpha_schema_record( 'person', $alpha_person->ID );
	$alpha_links[] = '<a href="' . esc_url( get_permalink( $alpha_person ) ) . '">' . esc_html( alpha_person_name( $alpha_person ) ) . '</a>';
}
$alpha_wrap = get_block_wrapper_attributes( array( 'class' => 'alpha-lead' ) );
?>
<div <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<span class="alpha-lead__faces">
		<?php
		foreach ( $alpha_people as $alpha_person ) {
			echo get_the_post_thumbnail( $alpha_person, 'thumbnail', array( 'alt' => '', 'loading' => 'lazy' ) );
		}
		?>
	</span>
	<span class="alpha-lead__text">
		<small><?php echo esc_html( wp_strip_all_tags( $alpha_label ) ); ?></small>
		<b><?php echo wp_kses_post( implode( ' ' . __( 'and', 'alpha-preclinical' ) . ' ', $alpha_links ) ); ?></b>
	</span>
</div>
