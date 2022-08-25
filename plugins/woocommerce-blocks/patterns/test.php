<?php
/**
 * Title: Test
 * Slug: woocommerce-blocks/all-filters
 * Categories: WooCommerce
 */
?>

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide">
	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:woocommerce/price-filter {"headingLevel":5,"heading":"Price range"} -->
		<div class="wp-block-woocommerce-price-filter is-loading" data-showinputfields="true" data-showfilterbutton="false" data-heading="Price range" data-heading-level="5"><span aria-hidden="true" class="wc-block-product-categories__placeholder"></span></div>
		<!-- /wp:woocommerce/price-filter -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} -->
	<div class="wp-block-column" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
		<!-- wp:woocommerce/attribute-filter {"headingLevel":5,"displayStyle":"dropdown"} -->
		<div class="wp-block-woocommerce-attribute-filter is-loading" data-attribute-id="0" data-show-counts="true" data-query-type="or" data-heading-level="5" data-heading="Filter by attribute" data-display-style="dropdown"><span aria-hidden="true" class="wc-block-product-attribute-filter__placeholder"></span></div>
		<!-- /wp:woocommerce/attribute-filter -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:woocommerce/stock-filter {"headingLevel":5,"heading":"Stock status"} -->
		<div class="wp-block-woocommerce-stock-filter is-loading" data-show-counts="true" data-heading="Stock status" data-heading-level="5"><span aria-hidden="true" class="wc-block-product-stock-filter__placeholder"></span></div>
		<!-- /wp:woocommerce/stock-filter -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide">
	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:woocommerce/active-filters {"displayStyle":"chips","headingLevel":5} -->
		<div class="wp-block-woocommerce-active-filters is-loading" data-display-style="chips" data-heading="Active filters" data-heading-level="5"><span aria-hidden="true" class="wc-block-active-product-filters__placeholder"></span></div>
		<!-- /wp:woocommerce/active-filters -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column -->
	<div class="wp-block-column"></div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
