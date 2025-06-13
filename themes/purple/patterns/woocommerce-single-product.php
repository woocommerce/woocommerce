<?php
/**
 * Title: WooCommerce Single Product
 * Slug: woostarter/woocommerce-single-product
 * Categories: page
 * Post Types: page, wp_template
 * Viewport width: 1400
 */
?>
<!-- wp:group {"tagName":"main","metadata":{"name":"Main"},"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"inherit":true,"type":"constrained"}} -->
<main class="wp-block-group" style="margin-top:0;margin-bottom:0">
	
	<!-- wp:group {"metadata":{"name":"Single Product"},"align":"wide","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:woocommerce/store-notices /-->

		<!-- wp:spacer {"height":"var:preset|spacing|40","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
		<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->

		<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|70","left":"var:preset|spacing|70"}}}} -->
		<div class="wp-block-columns alignwide">
			<!-- wp:column {"width":"50%","layout":{"type":"default"}} -->
			<div class="wp-block-column" style="flex-basis:50%">
				<!-- wp:woocommerce/product-gallery {"layout":{"type":"flex","flexWrap":"nowrap","orientation":"horizontal"}} -->
				<div class="wp-block-woocommerce-product-gallery wc-block-product-gallery">
					<!-- wp:woocommerce/product-gallery-thumbnails {"thumbnailSize":"16%","aspectRatio":"3/4"} /-->
					<!-- wp:woocommerce/product-gallery-large-image -->
					<div class="wp-block-woocommerce-product-gallery-large-image wc-block-product-gallery-large-image__inner-blocks">
						<!-- wp:woocommerce/product-gallery-large-image-next-previous {"style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-3"}}}},"textColor":"theme-3","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"bottom"}} -->
						<div class="wp-block-woocommerce-product-gallery-large-image-next-previous"></div>
						<!-- /wp:woocommerce/product-gallery-large-image-next-previous -->
					</div>
					<!-- /wp:woocommerce/product-gallery-large-image -->
				</div>
				<!-- /wp:woocommerce/product-gallery -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"50%","style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
			<div class="wp-block-column" style="flex-basis:50%">
				<!-- wp:group {"style":{"spacing":{"blockGap":"0","margin":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
				<div class="wp-block-group" style="margin-top:0;margin-bottom:0">
					<!-- wp:post-terms {"term":"product_cat","style":{"typography":{"textTransform":"uppercase","letterSpacing":"1.4px","fontStyle":"normal","fontWeight":"500"},"spacing":{"padding":{"bottom":"0"}}},"fontSize":"small"} /-->

					<!-- wp:post-title {"style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|30","bottom":"0"}}},"fontSize":"x-large","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

					<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
					<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--30)">
						<!-- wp:woocommerce/product-price {"isDescendentOfSingleProductTemplate":true,"fontSize":"large","style":{"typography":{"lineHeight":"1"},"spacing":{"padding":{"top":"0"}}}} /-->

						<!-- wp:woocommerce/product-sale-badge /-->
					</div>
					<!-- /wp:group -->

					<!-- wp:woocommerce/product-rating {"isDescendentOfSingleProductTemplate":true,"fontSize":"small","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} /-->
				</div>
				<!-- /wp:group -->

				<!-- wp:woocommerce/product-summary {"isDescendentOfSingleProductTemplate":true,"showDescriptionIfEmpty":true,"fontSize":"medium","style":{"typography":{"lineHeight":1.4},"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"bottom":"var:preset|spacing|40"}}}} /-->

				<!-- wp:woocommerce/add-to-cart-with-options /-->

				<!-- wp:pattern {"slug":"woostarter/woocommerce-product-details"} /-->

			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:spacer {"height":"var:preset|spacing|50","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
		<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->
	</div>
	<!-- /wp:group -->

	<!-- wp:pattern {"slug":"woostarter/related-products-collection"} /-->

	<!-- wp:pattern {"slug":"woostarter/left-aligned-image-right-text"} /-->

	<!-- wp:pattern {"slug":"woostarter/categories-on-three-columns"} /-->

	<!-- wp:group {"metadata":{"name":"Product reviews"},"align":"wide","layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:woocommerce/blockified-product-reviews {"align":"wide"} -->
		<div class="wp-block-woocommerce-blockified-product-reviews alignwide">
			<!-- wp:woocommerce/product-reviews-title /-->
			<!-- wp:woocommerce/product-review-template -->
		
			<!-- wp:columns -->
			<div class="wp-block-columns">
				<!-- wp:column {"width":"40px"} -->
				<div class="wp-block-column" style="flex-basis:40px">
					<!-- wp:avatar {"size":40,"style":{"border":{"radius":"20px"}}} /-->
				</div>
				<!-- /wp:column -->

				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
					<div class="wp-block-group">
						<!-- wp:woocommerce/product-review-author-name {"fontSize":"small"} /-->
						<!-- wp:woocommerce/product-review-rating /-->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"style":{"spacing":{"margin":{"top":"0px","bottom":"0px"}}},"layout":{"type":"flex"}} -->
					<div class="wp-block-group" style="margin-top:0px;margin-bottom:0px">
						<!-- wp:woocommerce/product-review-date {"fontSize":"small"} /-->
					</div>
					<!-- /wp:group -->

					<!-- wp:woocommerce/product-review-content /-->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->

			<!-- /wp:woocommerce/product-review-template -->
			<!-- wp:woocommerce/product-reviews-pagination -->
			<!-- wp:woocommerce/product-reviews-pagination-previous /-->
			<!-- wp:woocommerce/product-reviews-pagination-numbers /-->
			<!-- wp:woocommerce/product-reviews-pagination-next /-->
			<!-- /wp:woocommerce/product-reviews-pagination -->
			<!-- wp:woocommerce/product-review-form /-->

		</div>
		<!-- /wp:woocommerce/blockified-product-reviews -->

	</div>
	<!-- /wp:group -->

</main>
<!-- /wp:group -->