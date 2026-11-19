<?php
/**
 * Page header: breadcrumbs, H1, lead and the drifting strata band, on abyss.
 * An empty heading falls back to the current page or post title.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

$alpha_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_queried_object_id();
$alpha_heading = isset( $attributes['heading'] ) ? trim( (string) $attributes['heading'] ) : '';
if ( '' === $alpha_heading ) {
	if ( is_home() ) {
		$alpha_heading = esc_html( get_the_title( (int) get_option( 'page_for_posts' ) ) );
	} elseif ( is_archive() ) {
		$alpha_heading = esc_html( wp_strip_all_tags( get_the_archive_title() ) );
	} elseif ( is_search() ) {
		/* translators: %s: search query. */
		$alpha_heading = esc_html( sprintf( __( 'Results for “%s”', 'alpha-preclinical' ), get_search_query() ) );
	} elseif ( is_404() ) {
		$alpha_heading = esc_html__( 'Page not found', 'alpha-preclinical' );
	} else {
		$alpha_heading = esc_html( get_the_title( $alpha_post_id ) );
	}
}
$alpha_lead = isset( $attributes['lead'] ) ? trim( (string) $attributes['lead'] ) : '';
if ( '' === $alpha_lead && $alpha_post_id && is_singular() && has_excerpt( $alpha_post_id ) ) {
	$alpha_lead = esc_html( get_the_excerpt( $alpha_post_id ) );
}
$alpha_variant = isset( $attributes['variant'] ) && 'post' === $attributes['variant'] ? 'post' : 'page';
$alpha_wrap    = get_block_wrapper_attributes( array( 'class' => 'alpha-page-header alpha-page-header--' . $alpha_variant ) );
?>
<header <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="alpha-page-header__body">
		<?php
		if ( ! empty( $attributes['showBreadcrumbs'] ) && ! is_front_page() ) {
			echo render_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered block markup.
				array(
					'blockName' => 'alpha/breadcrumbs',
					'attrs'     => array(),
					'innerHTML' => '',
				)
			);
		}
		?>
		<h1 class="alpha-page-header__title"><?php echo wp_kses_post( $alpha_heading ); ?></h1>
		<?php if ( '' !== $alpha_lead ) : ?>
			<p class="alpha-page-header__lead"><?php echo wp_kses_post( $alpha_lead ); ?></p>
		<?php endif; ?>
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inner blocks. ?>
	</div>
	<?php
	echo render_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered block markup.
		array(
			'blockName' => 'alpha/strata-band',
			'attrs'     => array( 'variant' => 'post' === $alpha_variant ? 'thin' : 'page' ),
			'innerHTML' => '',
		)
	);
	?>
</header>
