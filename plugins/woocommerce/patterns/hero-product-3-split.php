<?php
/**
 * Title: Hero Product 3 Split
 * Slug: woocommerce-blocks/hero-product-3-split
 * Categories: WooCommerce, featured-selling
 */


$main_title   = __( 'New: Retro Glass Jug', 'woocommerce' );
$first_title  = __( 'Timeless elegance', 'woocommerce' );
$second_title = __( 'Durable glass', 'woocommerce' );
$third_title  = __( 'Versatile charm', 'woocommerce' );

$first_description  = __( 'Elevate your table with a 330ml Retro Glass Jug, blending classic design and durable hardened glass.', 'woocommerce' );
$second_description = __( 'Crafted from resilient thick glass, this jug ensures lasting quality, making it perfect for everyday use with a touch of vintage charm.', 'woocommerce' );
$third_description  = __( "The Retro Glass Jug's classic silhouette effortlessly complements any setting, making it the ideal choice for serving beverages with style and flair.", 'woocommerce' );
?>

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","bottom":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","left":"var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))","right":"var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal))"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-right:var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal));padding-bottom:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-left:var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))">
	<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
	<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"0"},"margin":{"top":"0px","bottom":"0px"}}}} -->
	<div class="wp-block-columns alignwide" style="margin-top:0px;margin-bottom:0px">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:cover {"url":"<?php echo esc_url( plugins_url( 'assets/images/pattern-placeholders/drinkware-liquid-tableware-dishware-bottle-fluid.jpg', WC_PLUGIN_FILE ) ); ?>","dimRatio":0,"minHeight":800,"minHeightUnit":"px","isDark":false,"layout":{"type":"constrained"}} -->
			<div class="wp-block-cover is-light" style="min-height:800px">
				<span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span>
				<img
					class="wp-block-cover__image-background"
					alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased.', 'woocommerce' ); ?>"
					src="<?php echo esc_url( plugins_url( 'assets/images/pattern-placeholders/drinkware-liquid-tableware-dishware-bottle-fluid.jpg', WC_PLUGIN_FILE ) ); ?>"
					data-object-fit="cover" />
				<div class="wp-block-cover__inner-container">
					<!-- wp:paragraph {"align":"center","placeholder":" ","fontSize":"large"} -->
					<p class="has-text-align-center has-large-font-size"></p>
					<!-- /wp:paragraph -->
				</div>
			</div>
			<!-- /wp:cover -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"50px","right":"50px"},"blockGap":"48px","margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-top:20px;padding-right:50px;padding-bottom:20px;padding-left:50px">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading"><?php echo esc_html( $main_title ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:group {"style":{"spacing":{"blockGap":"35px"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"constrained"}} -->
					<div class="wp-block-group">
						<!-- wp:heading {"level":5,"style":{"typography":{"textTransform":"capitalize"}}} -->
						<h5 class="wp-block-heading" style="text-transform:capitalize"><?php echo esc_html( $first_title ); ?></h5>
						<!-- /wp:heading -->

						<!-- wp:paragraph -->
						<p><?php echo esc_html( $first_description ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:separator {"className":"is-style-wide"} -->
					<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide" />
					<!-- /wp:separator -->

					<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"constrained"}} -->
					<div class="wp-block-group">
						<!-- wp:heading {"level":5,"style":{"typography":{"textTransform":"capitalize"}}} -->
						<h5 class="wp-block-heading" style="text-transform:capitalize"><?php echo esc_html( $second_title ); ?></h5>
						<!-- /wp:heading -->

						<!-- wp:paragraph -->
						<p><?php echo esc_html( $second_description ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:separator {"className":"is-style-wide"} -->
					<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide" />
					<!-- /wp:separator -->

					<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"constrained"}} -->
					<div class="wp-block-group">
						<!-- wp:heading {"level":5,"style":{"typography":{"textTransform":"capitalize"}}} -->
						<h5 class="wp-block-heading" style="text-transform:capitalize"><?php echo esc_html( $third_title ); ?></h5>
						<!-- /wp:heading -->

						<!-- wp:paragraph -->
						<p><?php echo esc_html( $third_description ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons"><!-- wp:button -->
					<div class="wp-block-button">
						<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Shop now', 'woocommerce' ); ?></a>
					</div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
	<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->
</div>
<!-- /wp:group -->
