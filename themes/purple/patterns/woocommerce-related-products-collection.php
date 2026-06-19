<?php
/**
 * Title: Related products collection
 * Slug: purple/related-products-collection
 * Categories: purple
 * Keywords: woo-commerce, featured, featured-selling, call-to-action
 * Description: A section with a heading and a carousel of related products.
 */
?>

<!-- wp:group {"metadata":{"name":"Related products collection","categories":["purple"]},"align":"full","className":"is-style-default","style":{"spacing":{"blockGap":"0","margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-default" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)"><!-- wp:woocommerce/product-collection {"queryId":21,"query":{"perPage":8,"pages":1,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false,"relatedBy":{"categories":true,"tags":true},"productReference":74},"tagName":"div","displayLayout":{"type":"carousel","columns":4,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/related","hideControls":["inherit"],"queryContextIncludes":["collection"],"__privatePreviewState":{"isPreview":true,"previewMessage":"Actual products will vary depending on the product being viewed."},"align":"wide"} -->
<div class="wp-block-woocommerce-product-collection alignwide"><!-- wp:group {"style":{"typography":{"lineHeight":"1"},"spacing":{"margin":{"left":"0","top":"var:preset|spacing|20"}}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"top","flexWrap":"nowrap","orientation":"horizontal"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--20);margin-left:0;line-height:1"><!-- wp:heading {"fontSize":"x-large"} -->
<h2 class="wp-block-heading has-x-large-font-size"><?php esc_html_e('Frequently bought together', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-gallery-large-image-next-previous {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-woocommerce-product-gallery-large-image-next-previous"></div>
<!-- /wp:woocommerce/product-gallery-large-image-next-previous --></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-template {"style":{"typography":{"lineHeight":"1"},"spacing":{"margin":{"left":"0","top":"var:preset|spacing|20"}}},"layout":{"type":"flex","justifyContent":"left","verticalAlignment":"top","flexWrap":"nowrap","orientation":"horizontal"}} -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"isDescendentOfQueryLoop":true,"style":{"dimensions":{"aspectRatio":"3/4"},"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"top":"var:preset|spacing|40","bottom":"0"}}}} -->
<!-- wp:woocommerce/product-sale-badge /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"level":3,"isLink":true,"style":{"typography":{"lineHeight":"1.5625"},"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"0"}}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","left":"0"}}}} /-->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:group -->
