<?php
/**
 * Title: WooCommerce Single Product
 * Slug: purple/woocommerce-single-product
 * Categories: page, purple
 * Post Types: page, wp_template
 * Viewport width: 1400
 * Description: A page template for the WooCommerce single product page.
 */
?>
<!-- wp:group {"metadata":{"name":"Single Product"},"align":"wide","style":{"spacing":{"blockGap":"0","padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:woocommerce/store-notices /-->

<!-- wp:woocommerce/breadcrumbs /-->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|70","left":"var:preset|spacing|70"},"margin":{"top":"var:preset|spacing|40"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:column {"width":"50%","layout":{"type":"default"}} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:woocommerce/product-gallery {"hoverZoom":false} -->
<div class="wp-block-woocommerce-product-gallery wc-block-product-gallery"><!-- wp:woocommerce/product-gallery-thumbnails {"thumbnailSize":"18%","aspectRatio":"3/4","activeThumbnailStyle":"outline"} /-->

<!-- wp:woocommerce/product-gallery-large-image -->
<div class="wp-block-woocommerce-product-gallery-large-image wc-block-product-gallery-large-image__inner-blocks"><!-- wp:woocommerce/product-image {"showProductLink":false,"showSaleBadge":false,"style":{"dimensions":{"aspectRatio":"3/4"}}} -->
<div class="is-loading"></div>
<!-- /wp:woocommerce/product-image -->

<!-- wp:woocommerce/product-gallery-large-image-next-previous {"align":"right","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"bottom"}} -->
<div class="wp-block-woocommerce-product-gallery-large-image-next-previous alignright"></div>
<!-- /wp:woocommerce/product-gallery-large-image-next-previous --></div>
<!-- /wp:woocommerce/product-gallery-large-image --></div>
<!-- /wp:woocommerce/product-gallery --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%","style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:group {"style":{"spacing":{"blockGap":"0","margin":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group" style="margin-top:0;margin-bottom:0"><!-- wp:post-title {"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"fontSize":"x-large","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--20)"><!-- wp:woocommerce/product-price {"isDescendentOfSingleProductTemplate":true,"style":{"typography":{"lineHeight":"1"},"spacing":{"padding":{"top":"0"}}}} /-->

<!-- wp:woocommerce/product-sale-badge /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-rating {"isDescendentOfSingleProductTemplate":true,"style":{"spacing":{"margin":{"bottom":"0","top":"var:preset|spacing|30"}}}} /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-summary {"isDescendentOfSingleProductTemplate":true,"showDescriptionIfEmpty":true,"style":{"spacing":{"padding":{"top":"0","bottom":"0"}}}} /-->

<!-- wp:woocommerce/add-to-cart-with-options /-->

<!-- wp:woocommerce/product-details -->
<div class="wp-block-woocommerce-product-details alignwide"><!-- wp:accordion {"style":{"spacing":{"blockGap":"0"}}} -->
<div role="group" class="wp-block-accordion"><!-- wp:accordion-item -->
<div class="wp-block-accordion-item"><!-- wp:accordion-heading {"level":3,"style":{"border":{"bottom":{"color":"var:preset|color|theme-6","width":"1px"}},"spacing":{"padding":{"top":"0.94rem","bottom":"0.94rem"}}}} -->
<h3 class="wp-block-accordion-heading" style="border-bottom-color:var(--wp--preset--color--theme-6);border-bottom-width:1px"><button type="button" class="wp-block-accordion-heading__toggle" style="padding-top:0.94rem;padding-bottom:0.94rem"><span class="wp-block-accordion-heading__toggle-title">Description</span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h3>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}}} -->
<div role="region" class="wp-block-accordion-panel" style="padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:woocommerce/product-description /--></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item -->

<!-- wp:accordion-item -->
<div class="wp-block-accordion-item"><!-- wp:accordion-heading {"level":3,"style":{"border":{"bottom":{"color":"var:preset|color|theme-6","width":"1px"}},"spacing":{"padding":{"top":"0.94rem","bottom":"0.94rem"}}}} -->
<h3 class="wp-block-accordion-heading" style="border-bottom-color:var(--wp--preset--color--theme-6);border-bottom-width:1px"><button type="button" class="wp-block-accordion-heading__toggle" style="padding-top:0.94rem;padding-bottom:0.94rem"><span class="wp-block-accordion-heading__toggle-title">Additional Information</span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h3>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"style":{"spacing":{"padding":{"top":"10px","bottom":"var:preset|spacing|30"}}}} -->
<div role="region" class="wp-block-accordion-panel" style="padding-top:10px;padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:woocommerce/product-specifications /--></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item --></div>
<!-- /wp:accordion --></div>
<!-- /wp:woocommerce/product-details -->

<!-- wp:spacer {"height":"var:preset|spacing|30","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:template-part {"slug":"single-product-info"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"purple/upsell-products-collection"} /-->

<!-- wp:pattern {"slug":"purple/related-products-collection"} /-->

<!-- wp:pattern {"slug":"purple/left-aligned-product-right-text"} /-->

<!-- wp:pattern {"slug":"purple/categories-on-three-columns"} /-->

<!-- wp:group {"metadata":{"name":"Product reviews"},"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|40"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","contentSize":"","wideSize":"49rem"}} -->
<div class="wp-block-group alignwide" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--40)"><!-- wp:woocommerce/product-reviews -->
<div class="wp-block-woocommerce-product-reviews"><!-- wp:woocommerce/product-reviews-title {"showProductTitle":false,"showReviewsCount":false,"level":3,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}}} /-->

<!-- wp:woocommerce/product-review-template -->
<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"width":"140px"} -->
<div class="wp-block-column" style="flex-basis:140px"><!-- wp:woocommerce/product-review-author-name {"isLink":false,"style":{"spacing":{"padding":{"bottom":"0","top":"0.25em"}}}} /--></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:woocommerce/product-review-rating /-->

<!-- wp:woocommerce/product-review-content /--></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"100px"} -->
<div class="wp-block-column" style="flex-basis:100px"><!-- wp:woocommerce/product-review-date {"isLink":false,"style":{"spacing":{"padding":{"bottom":"0","top":"0.25em"}}}} /--></div>
<!-- /wp:column --></div>
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
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:woocommerce/product-review-form /--></div>
<!-- /wp:group --></div>
<!-- /wp:woocommerce/product-reviews --></div>
<!-- /wp:group -->
