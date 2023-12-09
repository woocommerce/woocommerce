<?php
/**
 * Title: Product Listing with Gallery and Description
 * Slug: woocommerce-blocks/product-listing-with-gallery-and-description
 * Categories: WooCommerce
 */
?>

<!-- wp:columns {"align":"wide","style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"blockGap":{"top":"60px","left":"60px"}}}} -->
<div class="wp-block-columns alignwide" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:columns {"isStackedOnMobile":false,"style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"margin":{"top":"0","bottom":"0"},"blockGap":{"top":"1.5rem","left":"1.5rem"}}}} -->
		<div class="wp-block-columns is-not-stacked-on-mobile" style="margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
			<!-- wp:column {"width":"15%","style":{"spacing":{"padding":{"top":"0","right":"var:preset|spacing|20","bottom":"0","left":"0"},"blockGap":"0"}},"layout":{"type":"constrained","wideSize":"80px"}} -->
			<div class="wp-block-column" style="padding-top:0;padding-right:var(--wp--preset--spacing--20);padding-bottom:0;padding-left:0;flex-basis:15%">
				<!-- wp:group {"style":{"spacing":{"blockGap":"1rem"}},"layout":{"type":"flex","orientation":"vertical","verticalAlignment":"center","justifyContent":"center"}} -->
				<div class="wp-block-group">
					<!-- wp:image {"sizeSlug":"thumbnail","linkDestination":"none","style":{"border":{"color":"#dddddd","width":"1px","radius":"5px"}},"className":"is-resized"} -->
					<figure class="wp-block-image size-thumbnail has-custom-border is-resized">
						<img src="<?php echo esc_url( plugins_url( 'images/pattern-placeholders/desk-table-wood-chair-floor-home-square.png', dirname( __FILE__ ) ) ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in a product gallery. Secondary image - 1 out of 3.', 'woo-gutenberg-products-block' ); ?>" class="has-border-color" style="border-color:#dddddd;border-width:1px;border-radius:5px"/>
					</figure>
					<!-- /wp:image -->

					<!-- wp:image {"sizeSlug":"thumbnail","linkDestination":"none","style":{"border":{"color":"#dddddd","width":"1px","radius":"5px"}},"className":"is-resized"} -->
					<figure class="wp-block-image size-thumbnail has-custom-border is-resized">
						<img src="<?php echo esc_url( plugins_url( 'images/pattern-placeholders/table-floor-interior-atmosphere-living-room-furniture-square.png', dirname( __FILE__ ) ) ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in a product gallery. Secondary image - 2 out of 3.', 'woo-gutenberg-products-block' ); ?>" class="has-border-color" style="border-color:#dddddd;border-width:1px;border-radius:5px"/>
					</figure>
					<!-- /wp:image -->

					<!-- wp:image {"sizeSlug":"thumbnail","linkDestination":"none","style":{"border":{"color":"#dddddd","width":"1px","radius":"5px"}},"className":"is-resized"} -->
					<figure class="wp-block-image size-thumbnail has-custom-border is-resized">
						<img src="<?php echo esc_url( plugins_url( 'images/pattern-placeholders/table-floor-home-living-room-furniture-room-square.png', dirname( __FILE__ ) ) ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in a product gallery. Secondary image - 3 out of 3.', 'woo-gutenberg-products-block' ); ?>" class="has-border-color" style="border-color:#dddddd;border-width:1px;border-radius:5px"/>
					</figure>
					<!-- /wp:image -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"85%","style":{"spacing":{"blockGap":"0"}}} -->
			<div class="wp-block-column" style="flex-basis:85%">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
				<figure class="wp-block-image size-full">
					<img src="<?php echo esc_url( plugins_url( 'images/pattern-placeholders/table-wood-chair-floor-living-room-furniture-vertical.png', dirname( __FILE__ ) ) ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in a product gallery as a main image', 'woo-gutenberg-products-block' ); ?>"/>
				</figure>
				<!-- /wp:image -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:heading {"style":{"typography":{"fontSize":"48px","fontStyle":"normal","fontWeight":"700"}},"textColor":"foreground"} -->
		<h2 class="wp-block-heading has-foreground-color has-text-color" style="font-size:48px;font-style:normal;font-weight:700">Patterned Upright, Orange and White, Wood Legs</h2>
		<!-- /wp:heading -->

		<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"blockGap":"0px","margin":{"top":"10px","bottom":"0px"}}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
		<div class="wp-block-group" style="margin-top:10px;margin-bottom:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
			<!-- wp:paragraph {"textColor":"luminous-vivid-amber"} -->
			<p class="has-luminous-vivid-amber-color has-text-color">★★★★</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"color":{"text":"#ffe8a4"},"spacing":{"margin":{"right":"5px"}}}} -->
			<p class="has-text-color" style="color:#ffe8a4;margin-right:5px">★</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.7em"}},"textColor":"foreground"} -->
			<p class="has-foreground-color has-text-color" style="font-size:0.7em">
				<strong>4.2</strong>(1,079 reviews)
			</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"blockGap":"8px","padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"2px","bottom":"0px"}}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
		<div class="wp-block-group" style="margin-top:2px;margin-bottom:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.2em"}},"textColor":"foreground"} -->
			<p class="has-foreground-color has-text-color" style="font-size:1.2em">
				<strong><sup><sub>$</sub></sup>37.49</strong>
			</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.7em"},"layout":{"selfStretch":"fit","flexSize":null},"color":{"text":"#7c0a99"}}} -->
			<p class="has-text-color" style="color:#7c0a99;font-size:0.7em">Save $10 <s>was $47.49</s></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"layout":{"type":"constrained","justifyContent":"left"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}},"textColor":"foreground"} -->
			<p class="has-foreground-color has-text-color" style="font-size:18px">Designed with your well-being in mind, this chair features a contoured backrest that provides exceptional lumbar support, helping to reduce strain on your back during long hours of sitting. The adjustable height and tilt mechanisms allow you to customize the chair to your preferred sitting position, ensuring a comfortable and productive workday. Upholstered in premium fabric and available in a variety of colors, the Harmony Ergonomic Chair adds a touch of elegance to any room.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"layout":{"type":"constrained","justifyContent":"left"}} -->
		<div class="wp-block-group">
			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"style":{"spacing":{"padding":{"left":"80px","right":"80px"}},"color":{"text":"#ffffff","background":"#000000"}}} -->
				<div class="wp-block-button">
					<a class="wp-block-button__link has-text-color has-background wp-element-button" style="color:#ffffff;background-color:#000000;padding-right:80px;padding-left:80px">Add to cart</a>
				</div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->

			<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
			<p style="font-size:16px">SKU 6355793</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
