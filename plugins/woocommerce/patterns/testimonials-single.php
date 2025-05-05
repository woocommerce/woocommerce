<?php
/**
 * Title: Testimonials Single
 * Slug: woocommerce-blocks/testimonials-single
 * Categories: WooCommerce, Reviews
 */


$testimonials_title = __( 'A ‘brewtiful’ experience :-)', 'woocommerce' );
$description        = __( 'Exceptional flavors, sustainable choices. The carefully curated collection of coffee pots and accessories turned my kitchen into a haven of style and taste.', 'woocommerce' );
?>

<!-- wp:group {"metadata":{"name":"Testimonials Single"},"align":"full","style":{"spacing":{"padding":{"top":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","bottom":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","left":"var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))","right":"var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal))"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-right:var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal));padding-bottom:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-left:var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))"><!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
	<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:columns {"metadata":{"categories":["woo-commerce"],"patternName":"woocommerce-blocks/testimonials-single"},"align":"wide","style":{"spacing":{"blockGap":{"top":"0","left":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"verticalAlignment":"center","width":"160px"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:160px">
			<!-- wp:image {"width":"164px","className":"is-style-rounded"} -->
			<figure class="wp-block-image is-resized is-style-rounded">
				<img src="<?php echo esc_url( plugins_url( 'assets/images/pattern-placeholders/portrait.png', WC_PLUGIN_FILE ) ); ?>" alt="<?php esc_attr_e( 'Placeholder image with the avatar of the user who is writing the testimonial.', 'woocommerce' ); ?>" style="width:164px"/>
			</figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","layout":{"type":"constrained","justifyContent":"left"}} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:paragraph -->
			<p><strong><?php echo esc_html( $testimonials_title ); ?></strong></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><?php echo esc_html( $description ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p>Monica P.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
	<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->
</div>
<!-- /wp:group -->
