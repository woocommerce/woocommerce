<?php
/**
 * Title: Product Collection Rows
 * Slug: woocommerce-blocks/product-collection-rows
 * Categories: WooCommerce
 * Block Types: woocommerce/product-collection
 */
?>

<!-- wp:woocommerce/product-collection {"query":{"perPage":9,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]},"tagName":"div","displayLayout":{"type":"list","columns":3}} -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:woocommerce/product-image {"isDescendentOfQueryLoop":true} /--></div>
<!-- /wp:column -->

<!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:post-title {"style":{"typography":{"fontStyle":"normal","fontWeight":"700","lineHeight":"1"},"spacing":{"margin":{"top":"0","bottom":"0","left":"0","right":"0"},"padding":{"right":"0","left":"0","top":"0","bottom":"0"}}},"fontSize":"large","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"fontSize":"medium","style":{"typography":{"lineHeight":"1","fontStyle":"normal","fontWeight":"700"}}} /-->

<!-- wp:woocommerce/product-rating {"isDescendentOfQueryLoop":true,"textColor":"luminous-vivid-amber","fontSize":"medium","style":{"spacing":{"padding":{"top":"0px","bottom":"0px"}}}} /-->

<!-- wp:post-excerpt {"showMoreOnNewLine":false,"excerptLength":23,"fontSize":"small","__woocommerceNamespace":"woocommerce/product-collection/product-summary"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
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
<!-- /wp:query-no-results --></div>
<!-- /wp:woocommerce/product-collection -->
