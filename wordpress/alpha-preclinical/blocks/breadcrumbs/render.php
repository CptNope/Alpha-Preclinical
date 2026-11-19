<?php
/**
 * Breadcrumbs with BreadcrumbList schema.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

if ( is_front_page() ) {
	return;
}
$alpha_label = static function ( $id ) {
	$short = (string) get_post_meta( $id, 'short_title', true );
	return '' !== $short ? $short : get_the_title( $id );
};
$alpha_crumbs = array(
	array( __( 'Home', 'alpha-preclinical' ), home_url( '/' ) ),
);
$alpha_obj = get_queried_object();
if ( is_singular( 'post' ) && get_option( 'page_for_posts' ) ) {
	$alpha_crumbs[] = array( $alpha_label( (int) get_option( 'page_for_posts' ) ), get_permalink( (int) get_option( 'page_for_posts' ) ) );
} elseif ( is_singular( 'team_member' ) && get_page_by_path( 'team' ) ) {
	$alpha_crumbs[] = array( $alpha_label( get_page_by_path( 'team' )->ID ), get_permalink( get_page_by_path( 'team' ) ) );
} elseif ( is_singular( 'job' ) && get_page_by_path( 'careers' ) ) {
	$alpha_crumbs[] = array( $alpha_label( get_page_by_path( 'careers' )->ID ), get_permalink( get_page_by_path( 'careers' ) ) );
} elseif ( $alpha_obj instanceof WP_Post && $alpha_obj->post_parent ) {
	foreach ( array_reverse( get_post_ancestors( $alpha_obj ) ) as $alpha_anc ) {
		$alpha_crumbs[] = array( $alpha_label( $alpha_anc ), get_permalink( $alpha_anc ) );
	}
}
$alpha_current = isset( $attributes['current'] ) && '' !== $attributes['current'] ? wp_strip_all_tags( $attributes['current'] ) : '';
if ( '' === $alpha_current ) {
	$alpha_current = $alpha_obj instanceof WP_Post ? $alpha_label( $alpha_obj->ID ) : wp_strip_all_tags( wp_get_document_title() );
}
$alpha_ld = array();
foreach ( $alpha_crumbs as $alpha_i => $alpha_c ) {
	$alpha_ld[] = array(
		'@type'    => 'ListItem',
		'position' => $alpha_i + 1,
		'name'     => wp_strip_all_tags( $alpha_c[0] ),
		'item'     => $alpha_c[1],
	);
}
$alpha_ld[] = array(
	'@type'    => 'ListItem',
	'position' => count( $alpha_crumbs ) + 1,
	'name'     => $alpha_current,
);
$alpha_wrap = get_block_wrapper_attributes( array( 'class' => 'alpha-crumbs' ) );
?>
<nav <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php esc_attr_e( 'Breadcrumb', 'alpha-preclinical' ); ?>">
	<ol>
		<?php foreach ( $alpha_crumbs as $alpha_c ) : ?>
			<li><a href="<?php echo esc_url( $alpha_c[1] ); ?>"><?php echo esc_html( $alpha_c[0] ); ?></a></li>
		<?php endforeach; ?>
		<li aria-current="page"><?php echo esc_html( $alpha_current ); ?></li>
	</ol>
</nav>
<?php
alpha_print_json_ld(
	array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $alpha_ld,
	)
);
