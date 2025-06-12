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
<main class="wp-block-group" style="margin-top:0;margin-bottom:0"><!-- wp:group {"metadata":{"name":"Single Product"},"align":"wide","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><!-- wp:woocommerce/store-notices /-->

<!-- wp:spacer {"height":"var:preset|spacing|40","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|70","left":"var:preset|spacing|70"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"50%","layout":{"type":"default"}} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:woocommerce/product-gallery {"layout":{"type":"flex","flexWrap":"nowrap","orientation":"horizontal"}} -->
<div class="wp-block-woocommerce-product-gallery wc-block-product-gallery"><!-- wp:woocommerce/product-gallery-thumbnails {"thumbnailSize":"16%","aspectRatio":"3/4"} /-->

<!-- wp:woocommerce/product-gallery-large-image -->
<div class="wp-block-woocommerce-product-gallery-large-image wc-block-product-gallery-large-image__inner-blocks"><!-- wp:woocommerce/product-gallery-large-image-next-previous {"style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-3"}}}},"textColor":"theme-3","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"bottom"}} -->
<div class="wp-block-woocommerce-product-gallery-large-image-next-previous"></div>
<!-- /wp:woocommerce/product-gallery-large-image-next-previous --></div>
<!-- /wp:woocommerce/product-gallery-large-image --></div>
<!-- /wp:woocommerce/product-gallery --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%","style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:group {"style":{"spacing":{"blockGap":"0","margin":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group" style="margin-top:0;margin-bottom:0"><!-- wp:post-terms {"term":"product_cat","style":{"typography":{"textTransform":"uppercase","letterSpacing":"1.4px","fontStyle":"normal","fontWeight":"500"},"spacing":{"padding":{"bottom":"0"}}},"fontSize":"small"} /-->

<!-- wp:post-title {"style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|30","bottom":"0"}}},"fontSize":"x-large","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:woocommerce/product-price {"isDescendentOfSingleProductTemplate":true,"fontSize":"large","style":{"typography":{"lineHeight":"1"},"spacing":{"padding":{"top":"0"}}}} /-->

<!-- wp:woocommerce/product-sale-badge /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-rating {"isDescendentOfSingleProductTemplate":true,"fontSize":"small","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-summary {"isDescendentOfSingleProductTemplate":true,"showDescriptionIfEmpty":true,"fontSize":"medium","style":{"typography":{"lineHeight":1.4},"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"bottom":"var:preset|spacing|40"}}}} /-->

<!-- wp:woocommerce/add-to-cart-with-options /-->

<!-- wp:pattern {"slug":"woostarter/woocommerce-product-details"} /-->










</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|50","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"blockGap":"0","padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-default" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:heading {"align":"wide","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|40"}}}} -->
<h2 class="wp-block-heading alignwide" style="padding-bottom:var(--wp--preset--spacing--40)">You might like</h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-collection {"queryId":1,"query":{"perPage":3,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":true,"relatedBy":{"categories":true,"tags":true}},"tagName":"div","displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"dimensions":{"widthType":"fill","fixedWidth":"20px"},"queryContextIncludes":["collection"],"__privatePreviewState":{"isPreview":false,"previewMessage":"Actual products will vary depending on the page being viewed."},"align":"wide"} -->
<div class="wp-block-woocommerce-product-collection alignwide"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true,"height":""} -->
<!-- wp:woocommerce/product-sale-badge {"align":"right"} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:post-title {"textAlign":"left","level":3,"isLink":true,"style":{"spacing":{"margin":{"bottom":"0","top":"0"}}},"fontSize":"large","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","fontSize":"large","style":{"typography":{"lineHeight":"1"}}} /--></div>
<!-- /wp:group -->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"categories":["about"],"name":"Left-aligned image, text on the right","patternName":"woostarter/left-aligned-image-right-text"},"align":"full","className":"alignfull is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","bottom":"0"},"blockGap":"0"},"dimensions":{"minHeight":""}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull is-style-default" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0"><!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"aspectRatio":"1","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="http://wp-dev.test/wp-content/themes/woo-themes/woostarter/assets/images/coming-soon-img.webp" alt="A person holding a rustic, handmade ceramic mug in a cozy, dimly lit setting." class="" style="aspect-ratio:1;object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.2"}},"fontSize":"xx-large"} -->
<p class="has-xx-large-font-size" style="line-height:1.2">At Store Objects, we believe that imperfection tells a story—a quiet, captivating reflection of life itself.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"medium"} -->
<p class="has-medium-font-size"><a href="#">Learn more</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:cover {"url":"http://wp-dev.test/wp-content/themes/woo-themes/woostarter/assets/images/coming-soon-img.webp","dimRatio":50,"customOverlayColor":"#8d8d94","isUserOverlayColor":false,"contentPosition":"bottom center","isDark":false,"sizeSlug":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-light has-custom-content-position is-position-bottom-center" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><img class="wp-block-cover__image-background size-full" alt="" src="http://wp-dev.test/wp-content/themes/woo-themes/woostarter/assets/images/coming-soon-img.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim" style="background-color:#8d8d94"></span><div class="wp-block-cover__inner-container"><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"theme-1","textColor":"theme-5","style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-5"}}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-theme-5-color has-theme-1-background-color has-text-color has-background has-link-color wp-element-button">Shop Category</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:cover {"url":"http://wp-dev.test/wp-content/themes/woo-themes/woostarter/assets/images/coming-soon-img.webp","dimRatio":50,"customOverlayColor":"#8d8d94","isUserOverlayColor":false,"contentPosition":"bottom center","isDark":false,"sizeSlug":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-light has-custom-content-position is-position-bottom-center" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><img class="wp-block-cover__image-background size-full" alt="" src="http://wp-dev.test/wp-content/themes/woo-themes/woostarter/assets/images/coming-soon-img.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim" style="background-color:#8d8d94"></span><div class="wp-block-cover__inner-container"><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"theme-1","textColor":"theme-5","style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-5"}}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-theme-5-color has-theme-1-background-color has-text-color has-background has-link-color wp-element-button">Shop Category</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:cover {"url":"http://wp-dev.test/wp-content/themes/woo-themes/woostarter/assets/images/coming-soon-img.webp","dimRatio":50,"customOverlayColor":"#8d8d94","isUserOverlayColor":false,"contentPosition":"bottom center","isDark":false,"sizeSlug":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-light has-custom-content-position is-position-bottom-center" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><img class="wp-block-cover__image-background size-full" alt="" src="http://wp-dev.test/wp-content/themes/woo-themes/woostarter/assets/images/coming-soon-img.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim" style="background-color:#8d8d94"></span><div class="wp-block-cover__inner-container"><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"theme-1","textColor":"theme-5","style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-5"}}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-theme-5-color has-theme-1-background-color has-text-color has-background has-link-color wp-element-button">Shop Category</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->


<!-- wp:woocommerce/blockified-product-reviews -->
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
<!-- /wp:woocommerce/blockified-product-reviews -->

</div>
<!-- /wp:group --></main>
<!-- /wp:group -->