<?php
/**
 * Title: Product Collection 3 Columns
 * Slug: woocommerce-blocks/product-collection-3-columns
 * Categories: WooCommerce
 */
?>
<!-- wp:woocommerce/product-collection {"query":{"perPage":3,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":false,"taxQuery":{},"parents":[],"isProductCollectionBlock":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]},"tagName":"div","displayLayout":{"type":"flex","columns":3},"align":"wide"} -->
<div class="wp-block-woocommerce-product-collection alignwide">
	<!-- wp:woocommerce/product-template -->
	<!-- wp:woocommerce/product-image {"imageSizing":"thumbnail","isDescendentOfQueryLoop":true} /-->

	<!-- wp:woocommerce/product-rating {"isDescendentOfQueryLoop":true,"textAlign":"center"} /-->

	<!-- wp:post-title {"textAlign":"center","level":3,"isLink":true,"style":{"spacing":{"margin":{"bottom":"0.75rem","top":"0"}}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

	<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small"} /-->
	<!-- /wp:woocommerce/product-template -->

	<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
	<!-- wp:query-pagination-previous /-->

	<!-- wp:query-pagination-numbers /-->

	<!-- wp:query-pagination-next /-->
	<!-- /wp:query-pagination -->

	<!-- wp:query-no-results -->
	<!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} -->
	<p></p>
	<!-- /wp:paragraph -->
	<!-- /wp:query-no-results -->
</div>
<!-- /wp:woocommerce/product-collection -->
