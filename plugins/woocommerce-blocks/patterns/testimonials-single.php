<?php
/**
 * Title: Testimonials Single
 * Slug: woocommerce-blocks/testimonials-single
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;
$content = PatternsHelper::get_pattern_content( 'woocommerce-blocks/testimonials-single' );
$images  = PatternsHelper::get_pattern_images( 'woocommerce-blocks/testimonials-single' );
?>

<!-- wp:columns {"align":"wide","style":{"spacing":{"padding":{"right":"32px","left":"32px"}}}} -->
<div class="wp-block-columns alignwide" style="padding-right:32px;padding-left:32px">
	<!-- wp:column {"verticalAlignment":"center","width":"160px"} -->
	<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:160px">
		<!-- wp:image {"width":164,"className":"is-style-rounded"} -->
		<figure class="wp-block-image is-resized is-style-rounded">
			<img src="<?php echo esc_url( PatternsHelper::get_image_url( $images, 0, 'https://s.w.org/images/core/5.8/portrait.jpg' ) ); ?>" alt="<?php esc_attr_e( 'Placeholder image with the avatar of the user who is writing the testimonial.', 'woo-gutenberg-products-block' ); ?>" width="164"/>
		</figure>
		<!-- /wp:image -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"layout":{"type":"constrained","justifyContent":"left"}} -->
	<div class="wp-block-column">
		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html( $content['titles'][0]['default'] ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $content['descriptions'][0]['default'] ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p>~ Anna W.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
