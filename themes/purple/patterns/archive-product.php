<?php
/**
 * Title: archive-product
 * Slug: woostarter/archive-product
 * Inserter: no
 */
?>
<!-- wp:group {"metadata":{"name":"Sticky Header"},"style":{"position":{"type":"sticky","top":"0px"}},"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:template-part {"slug":"header"} /--></div>
<!-- /wp:group -->

<!-- wp:group {"tagName":"main","metadata":{"name":"Product Archives"},"className":"is-style-default","style":{"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"top":"0","bottom":"0"}}},"layout":{"inherit":true,"type":"constrained"}} -->
<main class="wp-block-group is-style-default" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0"><!-- wp:spacer {"height":"var:preset|spacing|60"} -->
<div style="height:var(--wp--preset--spacing--60)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:query-title {"type":"archive","level":2,"showPrefix":false,"align":"wide","fontSize":"xx-large"} /-->

<!-- wp:term-description {"align":"wide"} /--></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"10px"} -->
<div style="height:10px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:woocommerce/store-notices /-->

<!-- wp:spacer -->
<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"22%"} -->
<div class="wp-block-column" style="flex-basis:22%"><!-- wp:heading {"level":3,"style":{"margin":{"top":"0","bottom":"0"},"spacing":{"margin":{"top":"0","bottom":"0"}},"typography":{"lineHeight":1.6}},"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size" style="margin-top:0;margin-bottom:0;line-height:1.6"><?php esc_html_e('Filter by:', 'woostarter');?></h3>
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
<div class="wp-block-woocommerce-accordion-group"><!-- wp:woocommerce/accordion-item {"style":{"border":{"top":[],"bottom":{"width":"1px","color":"var:preset|color|theme-4"},"left":[]}}} -->
<div class="wp-block-woocommerce-accordion-item" style="border-bottom-color:var(--wp--preset--color--theme-4);border-bottom-width:1px"><!-- wp:woocommerce/accordion-header {"level":4,"icon":"chevron","style":{"spacing":{"padding":{"top":"1rem","bottom":"1rem"}}},"fontSize":"medium"} -->
<h4 class="wp-block-woocommerce-accordion-header has-medium-font-size accordion-item__heading"><button class="accordion-item__toggle" style="padding-top:1rem;padding-bottom:1rem"><span>Price</span><span class="accordion-item__toggle-icon has-icon-chevron" style="width:1.2em;height:1.2em"><svg width="1.2em" height="1.2em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.0041 10.5547L11.9996 16.0134L5.99512 10.5547L7.00413 9.44482L11.9996 13.9862L16.9951 9.44483L18.0041 10.5547Z" fill="currentColor"></path></svg></span></button></h4>
<!-- /wp:woocommerce/accordion-header -->

<!-- wp:woocommerce/accordion-panel -->
<div class="wp-block-woocommerce-accordion-panel"><div class="accordion-content__wrapper"><!-- wp:woocommerce/product-filter-price -->
<div class="wp-block-woocommerce-product-filter-price"><!-- wp:woocommerce/product-filter-price-slider -->
<div class="wp-block-woocommerce-product-filter-price-slider wc-block-product-filter-price-slider"></div>
<!-- /wp:woocommerce/product-filter-price-slider --></div>
<!-- /wp:woocommerce/product-filter-price --></div></div>
<!-- /wp:woocommerce/accordion-panel --></div>
<!-- /wp:woocommerce/accordion-item -->

<!-- wp:woocommerce/accordion-item {"style":{"border":{"top":[],"bottom":{"width":"1px","color":"var:preset|color|theme-4"},"left":[]}}} -->
<div class="wp-block-woocommerce-accordion-item" style="border-bottom-color:var(--wp--preset--color--theme-4);border-bottom-width:1px"><!-- wp:woocommerce/accordion-header {"level":4,"icon":"chevron","style":{"spacing":{"padding":{"top":"1rem","bottom":"1rem"}}},"fontSize":"medium"} -->
<h4 class="wp-block-woocommerce-accordion-header has-medium-font-size accordion-item__heading"><button class="accordion-item__toggle" style="padding-top:1rem;padding-bottom:1rem"><span>Rating</span><span class="accordion-item__toggle-icon has-icon-chevron" style="width:1.2em;height:1.2em"><svg width="1.2em" height="1.2em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.0041 10.5547L11.9996 16.0134L5.99512 10.5547L7.00413 9.44482L11.9996 13.9862L16.9951 9.44483L18.0041 10.5547Z" fill="currentColor"></path></svg></span></button></h4>
<!-- /wp:woocommerce/accordion-header -->

<!-- wp:woocommerce/accordion-panel -->
<div class="wp-block-woocommerce-accordion-panel"><div class="accordion-content__wrapper"><!-- wp:woocommerce/product-filter-rating -->
<div class="wp-block-woocommerce-product-filter-rating"><!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-rating --></div></div>
<!-- /wp:woocommerce/accordion-panel --></div>
<!-- /wp:woocommerce/accordion-item -->

<!-- wp:woocommerce/accordion-item {"style":{"border":{"top":[],"bottom":{"width":"1px","color":"var:preset|color|theme-4"},"left":[]}}} -->
<div class="wp-block-woocommerce-accordion-item" style="border-bottom-color:var(--wp--preset--color--theme-4);border-bottom-width:1px"><!-- wp:woocommerce/accordion-header {"level":4,"icon":"chevron","style":{"spacing":{"padding":{"top":"1rem","bottom":"1rem"}}},"fontSize":"medium"} -->
<h4 class="wp-block-woocommerce-accordion-header has-medium-font-size accordion-item__heading"><button class="accordion-item__toggle" style="padding-top:1rem;padding-bottom:1rem"><span>Color</span><span class="accordion-item__toggle-icon has-icon-chevron" style="width:1.2em;height:1.2em"><svg width="1.2em" height="1.2em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.0041 10.5547L11.9996 16.0134L5.99512 10.5547L7.00413 9.44482L11.9996 13.9862L16.9951 9.44483L18.0041 10.5547Z" fill="currentColor"></path></svg></span></button></h4>
<!-- /wp:woocommerce/accordion-header -->

<!-- wp:woocommerce/accordion-panel -->
<div class="wp-block-woocommerce-accordion-panel"><div class="accordion-content__wrapper"><!-- wp:woocommerce/product-filter-attribute -->
<div class="wp-block-woocommerce-product-filter-attribute"><!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-attribute --></div></div>
<!-- /wp:woocommerce/accordion-panel --></div>
<!-- /wp:woocommerce/accordion-item -->

<!-- wp:woocommerce/accordion-item {"style":{"border":{"top":[],"bottom":{"width":"1px","color":"var:preset|color|theme-4"},"left":[]}}} -->
<div class="wp-block-woocommerce-accordion-item" style="border-bottom-color:var(--wp--preset--color--theme-4);border-bottom-width:1px"><!-- wp:woocommerce/accordion-header {"level":4,"icon":"chevron","style":{"spacing":{"padding":{"top":"1rem","bottom":"1rem"}}},"fontSize":"medium"} -->
<h4 class="wp-block-woocommerce-accordion-header has-medium-font-size accordion-item__heading"><button class="accordion-item__toggle" style="padding-top:1rem;padding-bottom:1rem"><span>Status</span><span class="accordion-item__toggle-icon has-icon-chevron" style="width:1.2em;height:1.2em"><svg width="1.2em" height="1.2em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.0041 10.5547L11.9996 16.0134L5.99512 10.5547L7.00413 9.44482L11.9996 13.9862L16.9951 9.44483L18.0041 10.5547Z" fill="currentColor"></path></svg></span></button></h4>
<!-- /wp:woocommerce/accordion-header -->

<!-- wp:woocommerce/accordion-panel -->
<div class="wp-block-woocommerce-accordion-panel"><div class="accordion-content__wrapper"><!-- wp:woocommerce/product-filter-status -->
<div class="wp-block-woocommerce-product-filter-status"><!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-status --></div></div>
<!-- /wp:woocommerce/accordion-panel --></div>
<!-- /wp:woocommerce/accordion-item --></div>
<!-- /wp:woocommerce/accordion-group -->

<!-- wp:woocommerce/product-filter-active -->
<div class="wp-block-woocommerce-product-filter-active"><!-- wp:woocommerce/product-filter-removable-chips -->
<div class="wp-block-woocommerce-product-filter-removable-chips wc-block-product-filter-removable-chips"></div>
<!-- /wp:woocommerce/product-filter-removable-chips -->

<!-- wp:woocommerce/product-filter-clear-button -->
<!-- wp:buttons {"layout":{"type":"flex","verticalAlignment":"stretched"}} -->
<div class="wp-block-buttons"><!-- wp:button {"textAlign":"center","width":100,"className":"wc-block-product-filter-clear-button is-style-outline","style":{"border":{"width":"1px"},"typography":{"textDecoration":"none"},"outline":"none","fontSize":"medium","spacing":{"padding":{"left":"8px","right":"8px","top":"5px","bottom":"5px"}}}} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100 wc-block-product-filter-clear-button is-style-outline"><a class="wp-block-button__link has-text-align-center wp-element-button" style="border-width:1px;padding-top:5px;padding-right:8px;padding-bottom:5px;padding-left:8px;text-decoration:none"><?php esc_html_e('Clear filters', 'woostarter');?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:woocommerce/product-filter-clear-button --></div>
<!-- /wp:woocommerce/product-filter-active --></div>
<!-- /wp:woocommerce/product-filters --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"88%"} -->
<div class="wp-block-column" style="flex-basis:88%"><!-- wp:woocommerce/product-collection {"queryId":3,"query":{"woocommerceAttributes":[],"woocommerceStockStatus":["instock","outofstock","onbackorder"],"taxQuery":[],"isProductCollectionBlock":true,"perPage":10,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":true},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"queryContextIncludes":["collection"],"__privatePreviewState":{"isPreview":false,"previewMessage":"Actual products will vary depending on the page being viewed."},"align":"wide"} -->
<div class="wp-block-woocommerce-product-collection alignwide"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"imageSizing":"thumbnail","isDescendentOfQueryLoop":true} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:post-title {"textAlign":"left","level":3,"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /--></div>
<!-- /wp:group -->
<!-- /wp:woocommerce/product-template -->

<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"space-between"}} -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
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
<!-- /wp:spacer --></main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer"} /-->