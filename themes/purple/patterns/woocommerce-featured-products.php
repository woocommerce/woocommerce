<?php
/**
 * Title: Featured products
 * Slug: purple/woocommerce-featured-products
 * Categories: woo-commerce, purple
 * Keywords: woo-commerce, products, featured, collection
 * Description: A featured products section with a heading and 4 column product grid.
 * Block Types: woocommerce/product-collection
 * Viewport width: 1440
 */
?>

<!-- wp:group {"metadata":{"name":"Featured products","categories":["woo-commerce","purple"],"patternName":"purple/woocommerce-featured-products","description":"A featured products section with a heading and 4 column product grid."},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)"><!-- wp:woocommerce/product-collection {"queryId":47,"query":{"perPage":8,"pages":"1","offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"featured":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false,"relatedBy":{"categories":true,"tags":true}},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/featured","hideControls":["inherit","featured","filterable"],"queryContextIncludes":["collection"],"__privatePreviewState":{"isPreview":false,"previewMessage":"Actual products will vary depending on the page being viewed."},"align":"wide"} -->
<div class="wp-block-woocommerce-product-collection alignwide"><!-- wp:heading {"style":{"typography":{"textAlign":"left"}},"fontSize":"x-large"} -->
<h2 class="wp-block-heading has-text-align-left has-x-large-font-size"><?php esc_html_e('Featured products', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"isDescendentOfQueryLoop":true,"style":{"dimensions":{"aspectRatio":"3/4"},"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"0"}}}} -->
<!-- wp:woocommerce/product-sale-badge {"align":"left"} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"level":3,"isLink":true,"style":{"spacing":{"margin":{"bottom":"0","top":"var:preset|spacing|30"}},"typography":{"lineHeight":"1.18","textAlign":"left"}},"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","fontSize":"large","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","left":"0"}},"typography":{"lineHeight":"1.55","fontStyle":"normal"}}} /-->

<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:group -->
