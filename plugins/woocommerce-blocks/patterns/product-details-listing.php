<?php
/**
 * Title: Product Details Product Listing
 * Slug: woocommerce-blocks/product-details-listing
 * Categories: WooCommerce
 */
?>

<!-- wp:woocommerce/single-product {"isPreview": true} -->
<div class="wp-block-woocommerce-single-product">
	<!-- wp:columns -->
	<div class="wp-block-columns">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:post-featured-image {"height":"490px"} /-->

			<!-- wp:group {"style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center"}} -->
			<div class="wp-block-group">
				<!-- wp:woocommerce/product-rating-stars {"isDescendentOfSingleProductBlock":true,"textColor":"luminous-vivid-amber","style":{"spacing":{"margin":{"right":"5px"}}}} /-->
				<!-- wp:woocommerce/product-average-rating {"style":{"typography":{"fontWeight":"600","fontSize":"14px"}}} /-->
				<!-- wp:woocommerce/product-rating-counter {"isDescendentOfSingleProductBlock":true,"style":{"elements":{"link":{"color":{"text":"var:preset|color|contrast"}}},"typography":{"fontSize":"14px"}}} /-->
			</div>
			<!-- /wp:group -->

			<!-- wp:post-title {"textAlign":"center","isLink":true,"style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"x-large","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->
			<!-- wp:woocommerce/product-price {"textAlign":"center","isDescendentOfSingleProductBlock":true,"style":{"typography":{"fontSize":"28px","fontStyle":"normal","fontWeight":"700"}}} /-->
			<!-- wp:woocommerce/product-button {"textAlign":"center","isDescendentOfSingleProductBlock":true} /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:woocommerce/single-product -->
