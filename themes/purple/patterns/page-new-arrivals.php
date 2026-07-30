<?php
/**
 * Title: New arrivals with filters
 * Slug: purple/page-new-arrivals
 * Categories: woo-commerce, purple
 * Keywords: woo-commerce, products, new, arrivals, latest, grid, filters, pagination, breadcrumbs
 * Description: A newest-first product grid with breadcrumbs, page title, sidebar filters, and pagination, for use on regular pages. Pairs with the Page no title template.
 * Block Types: core/post-content, woocommerce/product-collection
 * Post Types: page, wp_template
 * Viewport width: 1440
 *
 * @package purple
 */

?>

<!-- wp:group {"metadata":{"name":"New arrivals with filters","categories":["woo-commerce","purple"],"patternName":"purple/page-new-arrivals","description":"A newest-first product grid with breadcrumbs, page title, sidebar filters, and pagination, for use on regular pages. Pairs with the Page no title template."},"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|90"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--90)"><!-- wp:woocommerce/store-notices /-->

<!-- wp:breadcrumbs {"align":"wide"} /-->

<!-- wp:post-title {"level":1,"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}}} /-->

<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|70"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:var(--wp--preset--spacing--70)"><!-- wp:column {"width":"22%"} -->
<div class="wp-block-column" style="flex-basis:22%"><!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|20"}},"typography":{"lineHeight":1.6,"fontStyle":"normal","fontWeight":"400"}},"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size" style="margin-top:0;margin-bottom:var(--wp--preset--spacing--20);font-style:normal;font-weight:400;line-height:1.6"><?php esc_html_e( 'Filter by:', 'purple' );?></h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filters {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
<div class="wp-block-woocommerce-product-filters wc-block-product-filters"><!-- wp:accordion {"style":{"spacing":{"blockGap":"0"}}} -->
<div role="group" class="wp-block-accordion"><!-- wp:accordion-item {"style":{"border":{"bottom":{"color":"var:preset|color|theme-6","width":"1px"},"top":[],"right":[],"left":[]},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<div class="wp-block-accordion-item" style="border-bottom-color:var(--wp--preset--color--theme-6);border-bottom-width:1px;margin-top:0;margin-bottom:0"><!-- wp:accordion-heading {"level":4,"style":{"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|10"}}}} -->
<h4 class="wp-block-accordion-heading has-icon has-icon-right"><button type="button" class="wp-block-accordion-heading__toggle" style="padding-top:0;padding-bottom:var(--wp--preset--spacing--10)"><span class="wp-block-accordion-heading__toggle-title"><?php esc_html_e( 'Price', 'purple' );?></span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h4>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"style":{"spacing":{"blockGap":"0"}}} -->
<div role="region" class="wp-block-accordion-panel"><!-- wp:woocommerce/product-filter-price -->
<div class="wp-block-woocommerce-product-filter-price"><!-- wp:woocommerce/product-filter-price-slider -->
<div class="wp-block-woocommerce-product-filter-price-slider wc-block-product-filter-price-slider"></div>
<!-- /wp:woocommerce/product-filter-price-slider --></div>
<!-- /wp:woocommerce/product-filter-price -->

<!-- wp:spacer {"height":"var:preset|spacing|10"} -->
<div style="height:var(--wp--preset--spacing--10)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item -->

<!-- wp:accordion-item {"style":{"border":{"bottom":{"color":"var:preset|color|theme-6","width":"1px"},"top":{},"right":{},"left":{}}}} -->
<div class="wp-block-accordion-item" style="border-bottom-color:var(--wp--preset--color--theme-6);border-bottom-width:1px"><!-- wp:accordion-heading {"level":4,"style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
<h4 class="wp-block-accordion-heading has-icon has-icon-right"><button type="button" class="wp-block-accordion-heading__toggle" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)"><span class="wp-block-accordion-heading__toggle-title"><?php esc_html_e( 'Rating', 'purple' );?></span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h4>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"style":{"spacing":{"blockGap":"0"}}} -->
<div role="region" class="wp-block-accordion-panel"><!-- wp:woocommerce/product-filter-rating -->
<div class="wp-block-woocommerce-product-filter-rating"><!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list -->

<!-- wp:spacer {"height":"var:preset|spacing|10"} -->
<div style="height:var(--wp--preset--spacing--10)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:woocommerce/product-filter-rating --></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item -->

<!-- wp:accordion-item {"style":{"border":{"bottom":{"color":"var:preset|color|theme-6","width":"1px"},"top":{},"right":{},"left":{}}}} -->
<div class="wp-block-accordion-item" style="border-bottom-color:var(--wp--preset--color--theme-6);border-bottom-width:1px"><!-- wp:accordion-heading {"level":4,"style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
<h4 class="wp-block-accordion-heading has-icon has-icon-right"><button type="button" class="wp-block-accordion-heading__toggle" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)"><span class="wp-block-accordion-heading__toggle-title"><?php esc_html_e( 'Color', 'purple' );?></span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h4>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"style":{"spacing":{"blockGap":"0"}}} -->
<div role="region" class="wp-block-accordion-panel"><!-- wp:woocommerce/product-filter-attribute -->
<div class="wp-block-woocommerce-product-filter-attribute"><!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list -->

<!-- wp:spacer {"height":"var:preset|spacing|10"} -->
<div style="height:var(--wp--preset--spacing--10)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:woocommerce/product-filter-attribute --></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item -->

<!-- wp:accordion-item {"style":{"border":{"bottom":{"color":"var:preset|color|theme-6","width":"1px"},"top":{},"right":{},"left":{}}}} -->
<div class="wp-block-accordion-item" style="border-bottom-color:var(--wp--preset--color--theme-6);border-bottom-width:1px"><!-- wp:accordion-heading {"level":4,"style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
<h4 class="wp-block-accordion-heading has-icon has-icon-right"><button type="button" class="wp-block-accordion-heading__toggle" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)"><span class="wp-block-accordion-heading__toggle-title"><?php esc_html_e( 'Status', 'purple' );?></span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h4>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"style":{"spacing":{"blockGap":"0"}}} -->
<div role="region" class="wp-block-accordion-panel"><!-- wp:woocommerce/product-filter-status -->
<div class="wp-block-woocommerce-product-filter-status"><!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-status -->

<!-- wp:spacer {"height":"var:preset|spacing|10"} -->
<div style="height:var(--wp--preset--spacing--10)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item --></div>
<!-- /wp:accordion -->

<!-- wp:woocommerce/product-filter-active -->
<div class="wp-block-woocommerce-product-filter-active"><!-- wp:woocommerce/product-filter-removable-chips -->
<div class="wp-block-woocommerce-product-filter-removable-chips wc-block-product-filter-removable-chips"></div>
<!-- /wp:woocommerce/product-filter-removable-chips -->

<!-- wp:woocommerce/product-filter-clear-button -->
<!-- wp:buttons {"layout":{"type":"flex","verticalAlignment":"stretched"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"wc-block-product-filter-clear-button is-style-outline","style":{"typography":{"textDecoration":"underline","textAlign":"center"},"outline":"none","fontSize":"medium","spacing":{"padding":{"left":"0px","right":"0px","top":"0px","bottom":"0px"}},"border":{"radius":"0px","width":"0px","style":"none"},"color":{"background":"#00000000"}},"fontSize":"medium"} -->
<div class="wp-block-button wc-block-product-filter-clear-button is-style-outline"><a class="wp-block-button__link has-background has-text-align-center has-medium-font-size has-custom-font-size wp-element-button" style="border-style:none;border-width:0px;border-radius:0px;background-color:#00000000;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;text-decoration:underline"><?php esc_html_e( 'Clear filters', 'purple' );?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:woocommerce/product-filter-clear-button --></div>
<!-- /wp:woocommerce/product-filter-active --></div>
<!-- /wp:woocommerce/product-filters --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"88%"} -->
<div class="wp-block-column" style="flex-basis:88%"><!-- wp:woocommerce/product-collection {"queryId":28,"query":{"woocommerceAttributes":[],"woocommerceStockStatus":["instock","onbackorder"],"taxQuery":[],"isProductCollectionBlock":true,"perPage":10,"pages":0,"offset":0,"postType":"product","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"queryContextIncludes":["collection"],"align":"wide"} -->
<div class="wp-block-woocommerce-product-collection alignwide"><!-- wp:woocommerce/product-template -->
<!-- wp:group {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:woocommerce/product-image {"showSaleBadge":false,"isDescendentOfQueryLoop":true,"style":{"dimensions":{"aspectRatio":"1"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<!-- wp:woocommerce/product-sale-badge {"align":"left","style":{"spacing":{"margin":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|10","right":"var:preset|spacing|10"}}}} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"level":3,"isLink":true,"style":{"typography":{"lineHeight":"1.5625"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"0"}}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","style":{"spacing":{"margin":{"top":"var:preset|spacing|10","left":"0"}}}} /--></div>
<!-- /wp:group -->
<!-- /wp:woocommerce/product-template -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:0"><!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous {"label":"<?php esc_html_e( 'Previous', 'purple' );?>"} /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next {"label":"<?php esc_html_e( 'Next', 'purple' );?>"} /-->
<!-- /wp:query-pagination --></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-collection-no-results -->
<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:paragraph -->
<p><?php /* Translators: 1. is the start of a 'a' HTML element, 2. is the end of a 'a' HTML element, 3. is the start of a 'a' HTML element, 4. is the end of a 'a' HTML element */
printf( esc_html__( 'No results found. You can try %1$sclearing any filters%2$s or head to our %3$sstore\'s home.%4$s', 'purple' ), '<a class="wc-link-clear-any-filters" href="' . esc_url( '#' ) . '">', '</a>', '<a class="wc-link-stores-home" href="' . esc_url( '#' ) . '">', '</a>' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<!-- /wp:woocommerce/product-collection-no-results --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
