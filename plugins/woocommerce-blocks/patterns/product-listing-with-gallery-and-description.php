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
			<!-- wp:column {"width":"33.33%","style":{"spacing":{"padding":{"top":"0","right":"var:preset|spacing|20","bottom":"0","left":"0"},"blockGap":"0"}},"layout":{"type":"constrained","wideSize":"80px"}} -->
			<div class="wp-block-column" style="padding-top:0;padding-right:var(--wp--preset--spacing--20);padding-bottom:0;padding-left:0;flex-basis:33.33%">
				<!-- wp:group {"style":{"spacing":{"blockGap":"1rem"}},"layout":{"type":"flex","orientation":"vertical","verticalAlignment":"center","justifyContent":"center"}} -->
				<div class="wp-block-group">
					<!-- wp:image {"sizeSlug":"thumbnail","linkDestination":"none","style":{"border":{"color":"#dddddd","width":"1px","radius":"5px"}}} -->
					<figure class="wp-block-image size-thumbnail is-resized has-custom-border">
						<img src="<?php echo esc_url( plugins_url( 'images/pattern-placeholders/product-furniture-1.png', dirname( __FILE__ ) ) ); ?>" alt="" class="has-border-color" style="border-color:#dddddd;border-width:1px;border-radius:5px"/>
					</figure>
					<!-- /wp:image -->

					<!-- wp:image {"sizeSlug":"thumbnail","linkDestination":"none","style":{"border":{"color":"#dddddd","width":"1px","radius":"5px"}}} -->
					<figure class="wp-block-image size-thumbnail is-resized has-custom-border">
						<img src="<?php echo esc_url( plugins_url( 'images/pattern-placeholders/product-furniture-2.png', dirname( __FILE__ ) ) ); ?>" alt="" class="has-border-color" style="border-color:#dddddd;border-width:1px;border-radius:5px"/>
					</figure>
					<!-- /wp:image -->

					<!-- wp:image {"sizeSlug":"thumbnail","linkDestination":"none","style":{"border":{"color":"#dddddd","width":"1px","radius":"5px"}}} -->
					<figure class="wp-block-image size-thumbnail is-resized has-custom-border">
						<img src="<?php echo esc_url( plugins_url( 'images/pattern-placeholders/product-furniture-4.png', dirname( __FILE__ ) ) ); ?>" alt="" class="has-border-color" style="border-color:#dddddd;border-width:1px;border-radius:5px"/>
					</figure>
					<!-- /wp:image -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"66.66%","style":{"spacing":{"blockGap":"0","padding":{"right":"0","bottom":"0","left":"0","top":"20px"}}}} -->
			<div class="wp-block-column" style="padding-top:20px;padding-right:0;padding-bottom:0;padding-left:0;flex-basis:66.66%">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
				<figure class="wp-block-image size-full">
					<img src="<?php echo esc_url( plugins_url( 'images/pattern-placeholders/product-furniture-4.png', dirname( __FILE__ ) ) ); ?>" alt="" />
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
		<h2 class="wp-block-heading has-foreground-color has-text-color" style="font-size:48px;font-style:normal;font-weight:700">Wicker Back Dining Chair, Blue Seat, Wood Base</h2>
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
			<p class="has-foreground-color has-text-color" style="font-size:18px">Crafted from solid wood with a rich
				walnut finish, this table exudes warmth and sophistication. The clean lines and minimalist design
				effortlessly complement a variety of interior styles, from modern to rustic. With its spacious tabletop,
				the Urban Loft Dining Table comfortably seats six guests, making it ideal for family dinners, social
				gatherings, and special occasions. The sturdy construction ensures lasting durability, while the
				easy-to-clean surface allows for hassle-free maintenance.
			</p>
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
