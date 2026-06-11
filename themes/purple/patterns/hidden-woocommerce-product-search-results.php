<?php
/**
 * Title: Product Search Results
 * Slug: purple/hidden-woocommerce-product-search-results
 * Inserter: no
 */
?>
<!-- wp:group {"tagName":"main","metadata":{"name":"Search Results"},"style":{"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"top":"0","bottom":"0"}}},"layout":{"inherit":true,"type":"constrained"}} -->
<main class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0"><!-- wp:spacer {"height":"var:preset|spacing|60"} -->
<div style="height:var(--wp--preset--spacing--60)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:woocommerce/store-notices /-->

<!-- wp:woocommerce/breadcrumbs /-->

<!-- wp:woocommerce/product-collection {"queryId":1,"query":{"woocommerceAttributes":[],"woocommerceStockStatus":["instock","outofstock","onbackorder"],"taxQuery":[],"isProductCollectionBlock":true,"perPage":10,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":true},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"queryContextIncludes":["collection"],"__privatePreviewState":{"isPreview":false,"previewMessage":"Actual products will vary depending on the page being viewed."},"align":"wide"} -->
<div class="wp-block-woocommerce-product-collection alignwide"><!-- wp:query-title {"type":"search","level":2,"showPrefix":false,"align":"wide","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} /-->

<!-- wp:search {"showLabel":false,"placeholder":"<?php esc_html_e('Search products...', 'purple');?>","buttonText":"Search","query":{"post_type":"product"},"style":{"border":{"color":"rgba(17, 17, 17, 0.2)","radius":"0px"}}} /-->

<!-- wp:woocommerce/product-collection-no-results {"align":"wide"} -->
 
<!-- wp:pattern {"slug":"purple/hidden-no-search-results"} /-->
<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- /wp:woocommerce/product-collection-no-results -->

<!-- wp:spacer {"height":"var:preset|spacing|60"} -->
<div style="height:var(--wp--preset--spacing--60)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"className":"alignwide","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide" style="margin-bottom:var(--wp--preset--spacing--50)"><!-- wp:woocommerce/product-results-count {"metadata":{"blockVisibility":{"viewport":{"mobile":false}}}} /-->

<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:paragraph -->
<p><?php esc_html_e('Sort by:', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:woocommerce/catalog-sorting {"style":{"layout":{"selfStretch":"fit","flexSize":null}}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true,"style":{"dimensions":{"aspectRatio":"3/4"},"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|30"}}}} -->
<!-- wp:woocommerce/product-sale-badge {"align":"left","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:post-title {"textAlign":"left","level":3,"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /--></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- /wp:woocommerce/product-template -->

<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous {"label":"<?php esc_html_e('Previous', 'purple');?>"} /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next {"label":"<?php esc_html_e('Next', 'purple');?>"} /-->
<!-- /wp:query-pagination --></div>
<!-- /wp:woocommerce/product-collection -->

<!-- wp:spacer {"height":"var:preset|spacing|80"} -->
<div style="height:var(--wp--preset--spacing--80)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></main>
<!-- /wp:group -->
