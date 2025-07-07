<?php
/**
 * Title: Product grids with filters
 * Slug: woostarter/product-grid-with-filters
 * Categories: woo-commerce
 * Keywords: woo-commerce, product, grid, filters
 * Description: A product grid with filters.
 */
?>
<!-- wp:group {"metadata":{"name":"Product grids with filters","patternName":"woostarter/product-grid-with-filters"},"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide">

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"22%"} -->
<div class="wp-block-column" style="flex-basis:22%"><!-- wp:heading {"level":3,"style":{"margin":{"top":"0","bottom":"0"},"spacing":{"margin":{"top":"0","bottom":"0"}},"typography":{"lineHeight":1.6,"fontStyle":"normal","fontWeight":"400"}},"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size" style="margin-top:0;margin-bottom:0;font-style:normal;font-weight:400;line-height:1.6"><?php esc_html_e('Filter by:', 'woostarter');?></h3>
<!-- /wp:heading --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"88%"} -->
<div class="wp-block-column" style="flex-basis:88%"><!-- wp:group {"className":"alignwide","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"fontSize":"medium","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide has-medium-font-size" style="margin-bottom:var(--wp--preset--spacing--50)"><!-- wp:woocommerce/product-results-count /-->

<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:paragraph -->
<p><?php esc_html_e('Sort by:', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:woocommerce/catalog-sorting {"fontSize":"medium","style":{"layout":{"selfStretch":"fit","flexSize":null}}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"22%"} -->
<div class="wp-block-column" style="flex-basis:22%"><!-- wp:woocommerce/product-filters {"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-woocommerce-product-filters wc-block-product-filters" style="--wc-product-filters-text-color:#111;--wc-product-filters-background-color:#fff;--wc-product-filter-block-spacing:0"><!-- wp:woocommerce/accordion-group {"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-woocommerce-accordion-group"><!-- wp:woocommerce/accordion-item -->
<div class="wp-block-woocommerce-accordion-item"><!-- wp:woocommerce/accordion-header {"level":4,"icon":"chevron"} -->
<h4 class="wp-block-woocommerce-accordion-header accordion-item__heading"><button class="accordion-item__toggle"><span><?php esc_html_e('Price', 'woostarter');?></span><span class="accordion-item__toggle-icon has-icon-chevron" style="width:1.2em;height:1.2em"><svg width="1.2em" height="1.2em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.0041 10.5547L11.9996 16.0134L5.99512 10.5547L7.00413 9.44482L11.9996 13.9862L16.9951 9.44483L18.0041 10.5547Z" fill="currentColor"></path></svg></span></button></h4>
<!-- /wp:woocommerce/accordion-header -->

<!-- wp:woocommerce/accordion-panel {"style":{"spacing":{"padding":{"right":"var:preset|spacing|20","left":"var:preset|spacing|20","top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"},"margin":{"bottom":"var:preset|spacing|30"}}}} -->
<div class="wp-block-woocommerce-accordion-panel"><div class="accordion-content__wrapper" style="margin-bottom:var(--wp--preset--spacing--30);padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20)"><!-- wp:woocommerce/product-filter-price -->
<div class="wp-block-woocommerce-product-filter-price"><!-- wp:woocommerce/product-filter-price-slider -->
<div class="wp-block-woocommerce-product-filter-price-slider wc-block-product-filter-price-slider"></div>
<!-- /wp:woocommerce/product-filter-price-slider --></div>
<!-- /wp:woocommerce/product-filter-price --></div></div>
<!-- /wp:woocommerce/accordion-panel --></div>
<!-- /wp:woocommerce/accordion-item -->

<!-- wp:woocommerce/accordion-item -->
<div class="wp-block-woocommerce-accordion-item"><!-- wp:woocommerce/accordion-header {"level":4,"icon":"chevron"} -->
<h4 class="wp-block-woocommerce-accordion-header accordion-item__heading"><button class="accordion-item__toggle"><span><?php esc_html_e('Rating', 'woostarter');?></span><span class="accordion-item__toggle-icon has-icon-chevron" style="width:1.2em;height:1.2em"><svg width="1.2em" height="1.2em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.0041 10.5547L11.9996 16.0134L5.99512 10.5547L7.00413 9.44482L11.9996 13.9862L16.9951 9.44483L18.0041 10.5547Z" fill="currentColor"></path></svg></span></button></h4>
<!-- /wp:woocommerce/accordion-header -->

<!-- wp:woocommerce/accordion-panel {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<div class="wp-block-woocommerce-accordion-panel"><div class="accordion-content__wrapper" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:woocommerce/product-filter-rating -->
<div class="wp-block-woocommerce-product-filter-rating"><!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-rating --></div></div>
<!-- /wp:woocommerce/accordion-panel --></div>
<!-- /wp:woocommerce/accordion-item -->

<!-- wp:woocommerce/accordion-item -->
<div class="wp-block-woocommerce-accordion-item"><!-- wp:woocommerce/accordion-header {"level":4,"icon":"chevron"} -->
<h4 class="wp-block-woocommerce-accordion-header accordion-item__heading"><button class="accordion-item__toggle"><span><?php esc_html_e('Color', 'woostarter');?></span><span class="accordion-item__toggle-icon has-icon-chevron" style="width:1.2em;height:1.2em"><svg width="1.2em" height="1.2em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.0041 10.5547L11.9996 16.0134L5.99512 10.5547L7.00413 9.44482L11.9996 13.9862L16.9951 9.44483L18.0041 10.5547Z" fill="currentColor"></path></svg></span></button></h4>
<!-- /wp:woocommerce/accordion-header -->

<!-- wp:woocommerce/accordion-panel {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<div class="wp-block-woocommerce-accordion-panel"><div class="accordion-content__wrapper" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:woocommerce/product-filter-attribute -->
<div class="wp-block-woocommerce-product-filter-attribute"><!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-attribute --></div></div>
<!-- /wp:woocommerce/accordion-panel --></div>
<!-- /wp:woocommerce/accordion-item -->

<!-- wp:woocommerce/accordion-item -->
<div class="wp-block-woocommerce-accordion-item"><!-- wp:woocommerce/accordion-header {"level":4,"icon":"chevron"} -->
<h4 class="wp-block-woocommerce-accordion-header accordion-item__heading"><button class="accordion-item__toggle"><span><?php esc_html_e('Status', 'woostarter');?></span><span class="accordion-item__toggle-icon has-icon-chevron" style="width:1.2em;height:1.2em"><svg width="1.2em" height="1.2em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.0041 10.5547L11.9996 16.0134L5.99512 10.5547L7.00413 9.44482L11.9996 13.9862L16.9951 9.44483L18.0041 10.5547Z" fill="currentColor"></path></svg></span></button></h4>
<!-- /wp:woocommerce/accordion-header -->

<!-- wp:woocommerce/accordion-panel {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<div class="wp-block-woocommerce-accordion-panel"><div class="accordion-content__wrapper" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:woocommerce/product-filter-status -->
<div class="wp-block-woocommerce-product-filter-status"><!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-status --></div></div>
<!-- /wp:woocommerce/accordion-panel --></div>
<!-- /wp:woocommerce/accordion-item --></div>
<!-- /wp:woocommerce/accordion-group -->

<!-- wp:woocommerce/product-filter-active {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}}} -->
<div class="wp-block-woocommerce-product-filter-active" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40)"><!-- wp:woocommerce/product-filter-removable-chips -->
<div class="wp-block-woocommerce-product-filter-removable-chips wc-block-product-filter-removable-chips"></div>
<!-- /wp:woocommerce/product-filter-removable-chips -->

<!-- wp:woocommerce/product-filter-clear-button -->
<!-- wp:buttons {"layout":{"type":"flex","verticalAlignment":"stretched"}} -->
<div class="wp-block-buttons"><!-- wp:button {"textAlign":"center","textColor":"theme-4","className":"wc-block-product-filter-clear-button is-style-outline","style":{"typography":{"textDecoration":"underline"},"outline":"none","fontSize":"medium","spacing":{"padding":{"left":"0px","right":"0px","top":"0px","bottom":"0px"}},"elements":{"link":{"color":{"text":"var:preset|color|theme-4"}}},"border":{"radius":"0px","width":"0px","style":"none"},"color":{"background":"#00000000"}},"fontSize":"medium"} -->
<div class="wp-block-button wc-block-product-filter-clear-button is-style-outline"><a class="wp-block-button__link has-theme-4-color has-text-color has-background has-link-color has-medium-font-size has-text-align-center has-custom-font-size wp-element-button" style="border-style:none;border-width:0px;border-radius:0px;background-color:#00000000;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;text-decoration:underline"><?php esc_html_e('Clear filters', 'woostarter');?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:woocommerce/product-filter-clear-button --></div>
<!-- /wp:woocommerce/product-filter-active --></div>
<!-- /wp:woocommerce/product-filters --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"88%"} -->
<div class="wp-block-column" style="flex-basis:88%"><!-- wp:woocommerce/product-collection {"queryId":3,"query":{"woocommerceAttributes":[],"woocommerceStockStatus":["instock","outofstock","onbackorder"],"taxQuery":[],"isProductCollectionBlock":true,"perPage":10,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":true},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"queryContextIncludes":["collection"],"__privatePreviewState":{"isPreview":false,"previewMessage":"Actual products will vary depending on the page being viewed."},"align":"wide"} -->
<div class="wp-block-woocommerce-product-collection alignwide"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true,"style":{"dimensions":{"aspectRatio":"3/4"},"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|30"}}}} -->
<!-- wp:woocommerce/product-sale-badge {"align":"left","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:post-title {"textAlign":"left","level":3,"isLink":true,"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","fontSize":"medium","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /--></div>
<!-- /wp:group -->
 <!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- /wp:woocommerce/product-template -->

<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous {"label":"<?php esc_html_e('Previous', 'woostarter');?>"} /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next {"label":"<?php esc_html_e('Next', 'woostarter');?>"} /-->
<!-- /wp:query-pagination -->

<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:woocommerce/product-collection-no-results -->
<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"fontSize":"medium"} -->
<p class="has-medium-font-size"><?php /* Translators: 1. is the start of a 'a' HTML element, 2. is the end of a 'a' HTML element, 3. is the start of a 'a' HTML element, 4. is the end of a 'a' HTML element */ 
echo sprintf( esc_html__( 'No results found. You can try %1$sclearing any filters%2$s or head to our %3$sstore\'s home.%4$s', 'woostarter' ), '<a class="wc-link-clear-any-filters" href="' . esc_url( '#' ) . '">', '</a>', '<a class="wc-link-stores-home" href="' . esc_url( '#' ) . '">', '</a>' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<!-- /wp:woocommerce/product-collection-no-results --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|80"} -->
<div style="height:var(--wp--preset--spacing--80)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->
