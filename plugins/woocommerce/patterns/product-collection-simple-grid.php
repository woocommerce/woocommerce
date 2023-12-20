<?php
/**
 * Title: Product Collection Simple Grid
 * Slug: woocommerce-blocks/product-collection-simple-grid
 * Categories: WooCommerce
 * Block Types: woocommerce/product-collection
 */
?>

<!-- wp:woocommerce/product-collection {"query":{"perPage":5,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]},"tagName":"div","displayLayout":{"type":"flex","columns":5,"shrinkColumns":true}} -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"saleBadgeAlign":"left","imageSizing":"thumbnail","isDescendentOfQueryLoop":true,"style":{"typography":{"fontSize":"0.8rem"}}} /-->

<!-- wp:post-title {"textAlign":"center","level":3,"isLink":true,"style":{"spacing":{"margin":{"bottom":"0.75rem","top":"0"}},"typography":{"fontSize":"1rem"}},"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
<!-- /wp:woocommerce/product-template -->

<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center","orientation":"horizontal"}} -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

<!-- wp:query-no-results -->
<!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} -->
<p></p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:woocommerce/product-collection -->
