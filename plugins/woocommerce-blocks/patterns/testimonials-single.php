<?php
/**
 * Title: Testimonials Single
 * Slug: woocommerce-blocks/testimonials-single
 * Categories: WooCommerce
 */
?>
<!-- wp:columns {"align":"wide","style":{"spacing":{"padding":{"right":"32px","left":"32px"}}}} -->
<div class="wp-block-columns alignwide" style="padding-right:32px;padding-left:32px">
	<!-- wp:column {"verticalAlignment":"center","width":"160px"} -->
	<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:160px">
		<!-- wp:image {"width":164,"className":"is-style-rounded"} -->
		<figure class="wp-block-image is-resized is-style-rounded">
			<img src="https://s.w.org/images/core/5.8/portrait.jpg" alt="<?php esc_attr_e( 'Placeholder image with the avatar of the user who is writing the testimonial.', 'woo-gutenberg-products-block' ); ?>" width="164"/>
		</figure>
		<!-- /wp:image -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"layout":{"type":"constrained","justifyContent":"left"}} -->
	<div class="wp-block-column">
		<!-- wp:paragraph -->
		<p><strong><?php esc_html_e( 'Great experience', 'woo-gutenberg-products-block' ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php esc_html_e( 'In the end the couch wasn\'t exactly what I was looking for but my experience with the Burrow team was excellent. First in providing a discount when the couch was delayed, then timely feedback and updates as the...', 'woo-gutenberg-products-block' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p>~ Anna W.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
