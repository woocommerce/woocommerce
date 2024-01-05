<?php
/**
 * Title: Large Image Product Gallery
 * Slug: woocommerce-blocks/product-query-large-image-product-gallery
 * Categories: WooCommerce
 * Block Types: core/query/woocommerce/product-query
 */

?>
<!-- wp:group {"style":{"spacing":{"margin":{"top":"0px","bottom":"80px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:0px;margin-bottom:80px">
	<!-- wp:query {"query":{"perPage":"4","pages":0,"offset":0,"postType":"product","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false,"__woocommerceStockStatus":["instock","outofstock","onbackorder"]},"displayLayout":{"type":"flex","columns":2},"namespace":"woocommerce/product-query"} -->
	<div class="wp-block-query">
		<!-- wp:post-template {"__woocommerceNamespace":"woocommerce/product-query/product-template"} -->
			<!-- wp:woocommerce/product-image {"aspectRatio":"2/3","saleBadgeAlign":"left","isDescendentOfQueryLoop":true} /-->

			<!-- wp:post-title {"textAlign":"center","level":3,"isLink":true,"style":{"spacing":{"margin":{"bottom":"0.75rem","top":"0"}}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

			<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"0.75rem"}}}} /-->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->
