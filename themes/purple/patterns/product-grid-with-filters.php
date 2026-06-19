<?php
/**
 * Title: Product grids with filters
 * Slug: purple/product-grid-with-filters
 * Categories: purple
 * Keywords: woo-commerce, product, grid, filters
 * Description: A product grid with filters.
 */
?>

<!-- wp:group {"metadata":{"name":"Product grids with filters","patternName":"purple/product-grid-with-filters","description":"A product grid with filters.","categories":["purple"]},"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|60","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--60);margin-bottom:0"><!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"22%"} -->
<div class="wp-block-column" style="flex-basis:22%"><!-- wp:heading {"level":3,"style":{"margin":{"top":"0","bottom":"0"},"spacing":{"margin":{"top":"0","bottom":"0"}},"typography":{"lineHeight":1.6,"fontStyle":"normal","fontWeight":"400"}},"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size" style="margin-top:0;margin-bottom:0;font-style:normal;font-weight:400;line-height:1.6"><?php esc_html_e('Filter by:', 'purple');?></h3>
<!-- /wp:heading --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"88%"} -->
<div class="wp-block-column" style="flex-basis:88%"><!-- wp:group {"className":"alignwide","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide"><!-- wp:woocommerce/product-results-count {"metadata":{"blockVisibility":{"viewport":{"mobile":false}}}} /-->

<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:paragraph -->
<p><?php esc_html_e('Sort by:', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:woocommerce/catalog-sorting {"style":{"layout":{"selfStretch":"fit","flexSize":null}}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"0"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:var(--wp--preset--spacing--50);margin-bottom:0"><!-- wp:column {"width":"22%"} -->
<div class="wp-block-column" style="flex-basis:22%"><!-- wp:woocommerce/product-filters {"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-woocommerce-product-filters wc-block-product-filters" style="--wc-product-filters-text-color:#111;--wc-product-filters-background-color:#fff;--wc-product-filter-block-spacing:0"><!-- wp:accordion {"style":{"spacing":{"blockGap":"0"}}} -->
<div role="group" class="wp-block-accordion"><!-- wp:accordion-item {"style":{"border":{"bottom":{"color":"var:preset|color|theme-6","width":"1px"},"top":[],"right":[],"left":[]}}} -->
<div class="wp-block-accordion-item" style="border-bottom-color:var(--wp--preset--color--theme-6);border-bottom-width:1px"><!-- wp:accordion-heading {"level":4} -->
<h4 class="wp-block-accordion-heading"><button type="button" class="wp-block-accordion-heading__toggle"><span class="wp-block-accordion-heading__toggle-title"><?php esc_html_e('Price', 'purple');?></span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h4>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"style":{"spacing":{"padding":{"right":"var:preset|spacing|20","left":"var:preset|spacing|20","top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"},"margin":{"bottom":"var:preset|spacing|30"}}}} -->
<div role="region" class="wp-block-accordion-panel" style="margin-bottom:var(--wp--preset--spacing--30);padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20)"><!-- wp:woocommerce/product-filter-price -->
<div class="wp-block-woocommerce-product-filter-price"><!-- wp:woocommerce/product-filter-price-slider -->
<div class="wp-block-woocommerce-product-filter-price-slider wc-block-product-filter-price-slider"></div>
<!-- /wp:woocommerce/product-filter-price-slider --></div>
<!-- /wp:woocommerce/product-filter-price --></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item -->

<!-- wp:accordion-item -->
<div class="wp-block-accordion-item"><!-- wp:accordion-heading {"level":4,"style":{"border":{"bottom":{"color":"var:preset|color|theme-6","width":"1px"},"top":{},"right":{},"left":{}}}} -->
<h4 class="wp-block-accordion-heading" style="border-bottom-color:var(--wp--preset--color--theme-6);border-bottom-width:1px"><button type="button" class="wp-block-accordion-heading__toggle"><span class="wp-block-accordion-heading__toggle-title"><?php esc_html_e('Rating', 'purple');?></span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h4>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<div role="region" class="wp-block-accordion-panel" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:woocommerce/product-filter-rating -->
<div class="wp-block-woocommerce-product-filter-rating"><!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-rating --></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item -->

<!-- wp:accordion-item -->
<div class="wp-block-accordion-item"><!-- wp:accordion-heading {"level":4,"style":{"border":{"bottom":{"color":"var:preset|color|theme-6","width":"1px"},"top":{},"right":{},"left":{}}}} -->
<h4 class="wp-block-accordion-heading" style="border-bottom-color:var(--wp--preset--color--theme-6);border-bottom-width:1px"><button type="button" class="wp-block-accordion-heading__toggle"><span class="wp-block-accordion-heading__toggle-title"><?php esc_html_e('Color', 'purple');?></span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h4>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<div role="region" class="wp-block-accordion-panel" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:woocommerce/product-filter-attribute -->
<div class="wp-block-woocommerce-product-filter-attribute"><!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-attribute --></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item -->

<!-- wp:accordion-item -->
<div class="wp-block-accordion-item"><!-- wp:accordion-heading {"level":4,"style":{"border":{"bottom":{"color":"var:preset|color|theme-6","width":"1px"},"top":{},"right":{},"left":{}}}} -->
<h4 class="wp-block-accordion-heading" style="border-bottom-color:var(--wp--preset--color--theme-6);border-bottom-width:1px"><button type="button" class="wp-block-accordion-heading__toggle"><span class="wp-block-accordion-heading__toggle-title"><?php esc_html_e('Status', 'purple');?></span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h4>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<div role="region" class="wp-block-accordion-panel" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:woocommerce/product-filter-status -->
<div class="wp-block-woocommerce-product-filter-status"><!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-status --></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item --></div>
<!-- /wp:accordion -->

<!-- wp:woocommerce/product-filter-active {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}}} -->
<div class="wp-block-woocommerce-product-filter-active" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40)"><!-- wp:woocommerce/product-filter-removable-chips -->
<div class="wp-block-woocommerce-product-filter-removable-chips wc-block-product-filter-removable-chips"></div>
<!-- /wp:woocommerce/product-filter-removable-chips -->

<!-- wp:woocommerce/product-filter-clear-button -->
<!-- wp:buttons {"layout":{"type":"flex","verticalAlignment":"stretched"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"wc-block-product-filter-clear-button is-style-outline","style":{"typography":{"textDecoration":"underline","textAlign":"center"},"outline":"none","fontSize":"medium","spacing":{"padding":{"left":"0px","right":"0px","top":"0px","bottom":"0px"}},"border":{"radius":"0px","width":"0px","style":"none"},"color":{"background":"#00000000"}},"fontSize":"medium"} -->
<div class="wp-block-button wc-block-product-filter-clear-button is-style-outline"><a class="wp-block-button__link has-background has-text-align-center has-medium-font-size has-custom-font-size wp-element-button" style="border-style:none;border-width:0px;border-radius:0px;background-color:#00000000;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;text-decoration:underline"><?php esc_html_e('Clear filters', 'purple');?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:woocommerce/product-filter-clear-button --></div>
<!-- /wp:woocommerce/product-filter-active --></div>
<!-- /wp:woocommerce/product-filters --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"88%"} -->
<div class="wp-block-column" style="flex-basis:88%"><!-- wp:woocommerce/product-collection {"queryId":27,"query":{"woocommerceAttributes":[],"woocommerceStockStatus":["instock","outofstock","onbackorder"],"taxQuery":[],"isProductCollectionBlock":true,"perPage":10,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":true},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"queryContextIncludes":["collection"],"__privatePreviewState":{"isPreview":false,"previewMessage":"Actual products will vary depending on the page being viewed."},"align":"wide"} -->
<div class="wp-block-woocommerce-product-collection alignwide"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"isDescendentOfQueryLoop":true,"style":{"dimensions":{"aspectRatio":"3/4"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<!-- wp:woocommerce/product-sale-badge {"align":"left","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"level":3,"isLink":true,"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","left":"0"}},"typography":{"lineHeight":"1.5625"}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","left":"0"}}}} /-->
<!-- /wp:woocommerce/product-template -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--50);margin-bottom:0"><!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous {"label":"<?php esc_html_e('Previous', 'purple');?>"} /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next {"label":"<?php esc_html_e('Next', 'purple');?>"} /-->
<!-- /wp:query-pagination --></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-collection-no-results -->
<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:paragraph -->
<p><?php /* Translators: 1. is the start of a 'a' HTML element, 2. is the end of a 'a' HTML element, 3. is the start of a 'a' HTML element, 4. is the end of a 'a' HTML element */ 
echo sprintf( esc_html__( 'No results found. You can try %1$sclearing any filters%2$s or head to our %3$sstore\'s home.%4$s', 'purple' ), '<a class="wc-link-clear-any-filters" href="' . esc_url( '#' ) . '">', '</a>', '<a class="wc-link-stores-home" href="' . esc_url( '#' ) . '">', '</a>' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<!-- /wp:woocommerce/product-collection-no-results --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
