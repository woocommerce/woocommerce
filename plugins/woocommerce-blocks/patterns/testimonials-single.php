<?php
/**
 * Title: Testimonials Single
 * Slug: woocommerce-blocks/testimonials-single
 * Categories: WooCommerce
 */
?>
<!-- wp:columns {"align":"wide","style":{"spacing":{"padding":{"right":"20px","left":"20px"}}}} -->
<div class="wp-block-columns alignwide" style="padding-right:20px;padding-left:20px">
	<!-- wp:column {"width":"160px"} -->
	<div class="wp-block-column" style="flex-basis:160px">
		<!-- wp:image {"align":"left","width":164,"height":164,"sizeSlug":"large","style":{"border":{"radius":"100%"}},"className":"is-style-rounded"} -->
		<figure class="wp-block-image alignleft size-large is-resized has-custom-border is-style-rounded"><img src="https://s.w.org/images/core/5.8/portrait.jpg" alt="" style="border-radius:100%" width="164" height="164"/></figure>
		<!-- /wp:image -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"layout":{"type":"default"}} -->
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
