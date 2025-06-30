<?php
/**
 * Title: WooCommerce Single Product
 * Slug: woostarter/woocommerce-single-product
 * Categories: page
 * Post Types: page, wp_template
 * Viewport width: 1400
 */
?>


<!-- wp:group {"metadata":{"name":"Single Product"},"align":"wide","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide">

	<!-- wp:spacer {"height":"var:preset|spacing|60"} -->
	<div style="height:var(--wp--preset--spacing--60)" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:woocommerce/store-notices /-->

	<!-- wp:woocommerce/breadcrumbs /-->

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
					<!-- wp:woocommerce/product-gallery-large-image-next-previous {"style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}}},"textColor":"theme-2","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"bottom"}} -->
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

<!-- wp:pattern {"slug":"woostarter/left-aligned-product-right-text"} /-->

<!-- wp:pattern {"slug":"woostarter/categories-on-three-columns"} /-->

<!-- wp:group {"metadata":{"name":"Product reviews"},"align":"wide","layout":{"type":"constrained","contentSize":"","wideSize":"49rem"}} -->
<div class="wp-block-group alignwide">
	<!-- wp:spacer {"height":"var:preset|spacing|40"} -->
	<div style="height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:woocommerce/product-reviews -->
	<div class="wp-block-woocommerce-product-reviews">
		<!-- wp:woocommerce/product-reviews-title {"showProductTitle":false,"showReviewsCount":false,"level":3,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}}} /-->
		<!-- wp:woocommerce/product-review-template -->
	
			<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
			<div class="wp-block-columns">
				<!-- wp:column {"width":"140px"} -->
				<div class="wp-block-column" style="flex-basis:140px">
					<!-- wp:woocommerce/product-review-author-name {"isLink":false,"style":{"spacing":{"padding":{"bottom":"0","top":"0.25em"}}}} /-->
				</div>
				<!-- /wp:column -->

				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical"}} -->
					<div class="wp-block-group">
						<!-- wp:woocommerce/product-review-rating /-->
						<!-- wp:woocommerce/product-review-content /-->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"width":"100px"} -->
				<div class="wp-block-column" style="flex-basis:100px">
					<!-- wp:woocommerce/product-review-date {"isLink":false,"style":{"spacing":{"padding":{"bottom":"0","top":"0.25em"}}}} /-->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->

			<!-- wp:spacer {"height":"var:preset|spacing|60"} -->
			<div style="height:var(--wp--preset--spacing--60)" aria-hidden="true" class="wp-block-spacer"></div>
			<!-- /wp:spacer -->

		<!-- /wp:woocommerce/product-review-template -->

		<!-- wp:woocommerce/product-reviews-pagination {"layout":{"type":"flex","justifyContent":"space-between"}} -->
			<!-- wp:woocommerce/product-reviews-pagination-previous /-->
			<!-- wp:woocommerce/product-reviews-pagination-next /-->
		<!-- /wp:woocommerce/product-reviews-pagination -->

		<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">
			<!-- wp:woocommerce/product-review-form /-->
		</div>
		<!-- /wp:group -->

	</div>
	<!-- /wp:woocommerce/product-reviews -->

</div>
<!-- /wp:group -->
