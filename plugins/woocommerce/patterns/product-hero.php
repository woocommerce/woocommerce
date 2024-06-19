<?php
/**
 * Title: Product Hero
 * Slug: woocommerce-blocks/product-hero
 * Categories: WooCommerce
 */

?>

<!-- wp:woocommerce/single-product {"isPreview":true, "align":"wide"} -->
<div class="wp-block-woocommerce-single-product alignwide">
	<!-- wp:columns -->
	<div class="wp-block-columns">
		<!-- wp:column {"verticalAlignment":"center","width":"40%","style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"}}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-column is-vertically-aligned-center" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;flex-basis:40%">
			<!-- wp:woocommerce/product-image {"showSaleBadge":false,"isDescendentOfSingleProductBlock":true,"height":"300px"} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"60%","layout":{"type":"constrained","justifyContent":"left","contentSize":"650px"}} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:60%">
			<!-- wp:post-title {"textAlign":"","isLink":true,"style":{"spacing":{"margin":{"top":"0","right":"0","bottom":"0","left":"0"}}},"__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

			<!-- wp:woocommerce/product-price {"isDescendentOfSingleProductBlock":true} /-->

			<!-- wp:group {"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group">
				<!-- wp:woocommerce/product-button -->
				<div class="wp-block-woocommerce-product-button is-loading"></div>
				<!-- /wp:woocommerce/product-button -->
			</div>
			<!-- /wp:group -->

			<!-- wp:post-excerpt {"__woocommerceNamespace":"woocommerce/product-query/product-summary"} /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:woocommerce/single-product -->
