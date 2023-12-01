<?php
/**
 * Title: Testimonials Single
 * Slug: woocommerce-blocks/testimonials-single
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;

$testimonials_title = $content['titles'][0]['default'] ?? '';
$description        = $content['descriptions'][0]['default'] ?? '';
?>

<!-- wp:columns {"align":"wide","style":{"spacing":{"padding":{"right":"32px","left":"32px"}}}} -->
<div class="wp-block-columns alignwide" style="padding-right:32px;padding-left:32px">
	<!-- wp:column {"verticalAlignment":"center","width":"160px"} -->
	<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:160px">
		<!-- wp:image {"width": "164px", "className":"is-style-rounded"} -->
		<figure class="wp-block-image is-resized is-style-rounded">
			<img src="<?php echo esc_url( PatternsHelper::get_image_url( $images, 0, 'images/pattern-placeholders/portrait.png' ) ); ?>" alt="<?php esc_attr_e( 'Placeholder image with the avatar of the user who is writing the testimonial.', 'woo-gutenberg-products-block' ); ?>" style="width:164px"/>
		</figure>
		<!-- /wp:image -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"layout":{"type":"constrained","justifyContent":"left"}} -->
	<div class="wp-block-column">
		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html( $testimonials_title ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $description ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p>– Monica P.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
