<?php
/**
 * FAQ list: renders its questions and, once per page, FAQPage schema built from them.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

$alpha_items = array();
foreach ( $block->inner_blocks as $alpha_inner ) {
	if ( 'alpha/faq-item' !== $alpha_inner->name ) {
		continue;
	}
	$alpha_q = isset( $alpha_inner->attributes['question'] ) ? wp_strip_all_tags( (string) $alpha_inner->attributes['question'] ) : '';
	$alpha_a = isset( $alpha_inner->attributes['answer'] ) ? wp_strip_all_tags( (string) $alpha_inner->attributes['answer'] ) : '';
	if ( '' !== $alpha_q && '' !== $alpha_a ) {
		$alpha_items[] = array(
			'@type'          => 'Question',
			'name'           => $alpha_q,
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $alpha_a,
			),
		);
	}
}
$alpha_wrap = get_block_wrapper_attributes( array( 'class' => 'alpha-faq' ) );
?>
<div <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inner blocks. ?>
</div>
<?php
if ( $alpha_items && ( ! isset( $attributes['schema'] ) || $attributes['schema'] ) ) {
	alpha_print_json_ld(
		array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $alpha_items,
		)
	);
}
