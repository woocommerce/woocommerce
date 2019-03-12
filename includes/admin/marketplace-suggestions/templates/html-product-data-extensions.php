<?php
/**
 * The marketplace suggestions tab HTML in the product tabs
 *
 * @package WooCommerce\Classes
 * @since   3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="marketplace_suggestions" class="panel woocommerce_options_panel hidden">
	<?php
		WC_Marketplace_Suggestions::render_suggestions_container( 'product-edit-meta-tab-header' );
		WC_Marketplace_Suggestions::render_suggestions_container( 'product-edit-meta-tab-body' );
		WC_Marketplace_Suggestions::render_suggestions_container( 'product-edit-meta-tab-footer' );
	?>
	<div class="marketplace-suggestions-metabox-nosuggestions-placeholder hidden">
		<img src="https://woocommerce.com/wp-content/plugins/wccom-plugins/marketplace-suggestions/icons/get_more_options.svg" class="marketplace-suggestion-icon">
		<div class="marketplace-suggestion-placeholder-content">
			<h4>Enhance your products</h4>
			<p>Extensions can add new functionality to your product pages that make your store stand out</p>
		</div>
		<a href="https://woocommerce.com/product-category/woocommerce-extensions/?utm_source=editproduct&amp;utm_campaign=marketplacesuggestions&amp;utm_medium=product" target="blank" class="button">Browse the Marketplace</a>
	</div>
</div>
