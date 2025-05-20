<?php
/**
 * Title: single-product
 * Slug: woostarter/single-product
 * Inserter: no
 */
?>
<!-- wp:group {"tagName":"main","metadata":{"name":"Main"},"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"inherit":true,"type":"constrained"}} -->
<main class="wp-block-group" style="margin-top:0;margin-bottom:0"><!-- wp:group {"metadata":{"name":"Single Product"},"align":"wide","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><!-- wp:woocommerce/store-notices /-->

<!-- wp:spacer {"height":"var:preset|spacing|40","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|70","left":"var:preset|spacing|70"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"50%","layout":{"type":"default"}} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:woocommerce/product-gallery {"layout":{"type":"flex","flexWrap":"nowrap","orientation":"horizontal"}} -->
<div class="wp-block-woocommerce-product-gallery wc-block-product-gallery"><!-- wp:woocommerce/product-gallery-thumbnails {"thumbnailSize":"16%"} /-->

<!-- wp:woocommerce/product-gallery-large-image -->
<div class="wp-block-woocommerce-product-gallery-large-image wc-block-product-gallery-large-image__inner-blocks"><!-- wp:woocommerce/product-sale-badge {"align":"right"} /-->

<!-- wp:woocommerce/product-gallery-large-image-next-previous {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"bottom"}} -->
<div class="wp-block-woocommerce-product-gallery-large-image-next-previous"></div>
<!-- /wp:woocommerce/product-gallery-large-image-next-previous --></div>
<!-- /wp:woocommerce/product-gallery-large-image --></div>
<!-- /wp:woocommerce/product-gallery --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%","style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:group {"style":{"spacing":{"blockGap":"0","margin":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group" style="margin-top:0;margin-bottom:0"><!-- wp:post-terms {"term":"product_cat","style":{"typography":{"textTransform":"uppercase","letterSpacing":"1.4px","fontStyle":"normal","fontWeight":"500"},"spacing":{"padding":{"bottom":"0"}}},"fontSize":"small"} /-->

<!-- wp:post-title {"level":1,"style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|30","bottom":"0"}}},"fontSize":"xx-large","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|20"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--20)"><!-- wp:woocommerce/product-price {"isDescendentOfSingleProductTemplate":true,"fontSize":"large","style":{"typography":{"lineHeight":"1"},"spacing":{"padding":{"top":"0"}}}} /-->

<!-- wp:woocommerce/product-sale-badge /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-rating {"isDescendentOfSingleProductTemplate":true} /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-summary {"isDescendentOfSingleProductTemplate":true,"showDescriptionIfEmpty":true,"fontSize":"medium","style":{"typography":{"lineHeight":1.3999999999999999},"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"bottom":"var:preset|spacing|40"}}}} /-->

<!-- wp:woocommerce/add-to-cart-with-options /-->

<!-- wp:woocommerce/blockified-product-details -->
<div class="wp-block-woocommerce-blockified-product-details"><!-- wp:woocommerce/accordion-group {"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-woocommerce-accordion-group"><!-- wp:woocommerce/accordion-item {"style":{"spacing":{"blockGap":"0"},"border":{"bottom":{"color":"var:preset|color|theme-4","width":"1px"},"top":[],"right":[],"left":[]}}} -->
<div class="wp-block-woocommerce-accordion-item" style="border-bottom-color:var(--wp--preset--color--theme-4);border-bottom-width:1px"><!-- wp:woocommerce/accordion-header {"icon":"chevron","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"fontSize":"medium"} -->
<h3 class="wp-block-woocommerce-accordion-header has-medium-font-size accordion-item__heading"><button class="accordion-item__toggle" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><span>Description</span><span class="accordion-item__toggle-icon has-icon-chevron" style="width:1.2em;height:1.2em"><svg width="1.2em" height="1.2em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.0041 10.5547L11.9996 16.0134L5.99512 10.5547L7.00413 9.44482L11.9996 13.9862L16.9951 9.44483L18.0041 10.5547Z" fill="currentColor"></path></svg></span></button></h3>
<!-- /wp:woocommerce/accordion-header -->

<!-- wp:woocommerce/accordion-panel {"style":{"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|40"}}}} -->
<div class="wp-block-woocommerce-accordion-panel"><div class="accordion-content__wrapper" style="padding-top:0;padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:woocommerce/product-description /--></div></div>
<!-- /wp:woocommerce/accordion-panel --></div>
<!-- /wp:woocommerce/accordion-item -->

<!-- wp:woocommerce/accordion-item {"style":{"border":{"bottom":{"color":"var:preset|color|theme-4","width":"1px"},"top":[],"right":[],"left":[]}}} -->
<div class="wp-block-woocommerce-accordion-item" style="border-bottom-color:var(--wp--preset--color--theme-4);border-bottom-width:1px"><!-- wp:woocommerce/accordion-header {"icon":"chevron","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"fontSize":"medium"} -->
<h3 class="wp-block-woocommerce-accordion-header has-medium-font-size accordion-item__heading"><button class="accordion-item__toggle" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><span>Additional Information</span><span class="accordion-item__toggle-icon has-icon-chevron" style="width:1.2em;height:1.2em"><svg width="1.2em" height="1.2em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.0041 10.5547L11.9996 16.0134L5.99512 10.5547L7.00413 9.44482L11.9996 13.9862L16.9951 9.44483L18.0041 10.5547Z" fill="currentColor"></path></svg></span></button></h3>
<!-- /wp:woocommerce/accordion-header -->

<!-- wp:woocommerce/accordion-panel {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|40"}}}} -->
<div class="wp-block-woocommerce-accordion-panel"><div class="accordion-content__wrapper" style="padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:woocommerce/product-specifications /--></div></div>
<!-- /wp:woocommerce/accordion-panel --></div>
<!-- /wp:woocommerce/accordion-item -->

<!-- wp:woocommerce/accordion-item {"style":{"border":{"bottom":{"color":"var:preset|color|theme-4","width":"1px"},"top":[],"right":[],"left":[]}}} -->
<div class="wp-block-woocommerce-accordion-item" style="border-bottom-color:var(--wp--preset--color--theme-4);border-bottom-width:1px"><!-- wp:woocommerce/accordion-header {"icon":"chevron","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"fontSize":"medium"} -->
<h3 class="wp-block-woocommerce-accordion-header has-medium-font-size accordion-item__heading"><button class="accordion-item__toggle" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><span>Reviews</span><span class="accordion-item__toggle-icon has-icon-chevron" style="width:1.2em;height:1.2em"><svg width="1.2em" height="1.2em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.0041 10.5547L11.9996 16.0134L5.99512 10.5547L7.00413 9.44482L11.9996 13.9862L16.9951 9.44483L18.0041 10.5547Z" fill="currentColor"></path></svg></span></button></h3>
<!-- /wp:woocommerce/accordion-header -->

<!-- wp:woocommerce/accordion-panel {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|40"}}}} -->
<div class="wp-block-woocommerce-accordion-panel"><div class="accordion-content__wrapper" style="padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:woocommerce/blockified-product-reviews -->
<div class="wp-block-woocommerce-blockified-product-reviews"><!-- wp:woocommerce/product-reviews-title {"level":4} /-->

<!-- wp:woocommerce/product-review-template -->
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"40px"} -->
<div class="wp-block-column" style="flex-basis:40px"><!-- wp:avatar {"size":40,"style":{"border":{"radius":"20px"}}} /--></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group"><!-- wp:woocommerce/product-review-author-name {"fontSize":"small"} /-->

<!-- wp:woocommerce/product-review-rating /--></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"0px","bottom":"0px"}}},"layout":{"type":"flex"}} -->
<div class="wp-block-group" style="margin-top:0px;margin-bottom:0px"><!-- wp:woocommerce/product-review-date {"fontSize":"small"} /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-review-content /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:woocommerce/product-review-template -->

<!-- wp:woocommerce/product-reviews-pagination -->
<!-- wp:woocommerce/product-reviews-pagination-previous /-->

<!-- wp:woocommerce/product-reviews-pagination-numbers /-->

<!-- wp:woocommerce/product-reviews-pagination-next /-->
<!-- /wp:woocommerce/product-reviews-pagination -->

<!-- wp:woocommerce/product-review-form /--></div>
<!-- /wp:woocommerce/blockified-product-reviews --></div></div>
<!-- /wp:woocommerce/accordion-panel --></div>
<!-- /wp:woocommerce/accordion-item --></div>
<!-- /wp:woocommerce/accordion-group --></div>
<!-- /wp:woocommerce/blockified-product-details --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|50","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"woostarter/product-related-3-column"} /-->
<!-- wp:pattern {"slug":"woostarter/left-aligned-image-right-text"} /-->
<!-- wp:pattern {"slug":"woostarter/categories-on-three-columns"} /-->

</main>
<!-- /wp:group -->
