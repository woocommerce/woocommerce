<?php
/**
 * The Extensions tab HTML in the product tabs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="marketplace_suggestions" class="panel woocommerce_options_panel hidden">
	<?php
		WC_Marketplace_Suggestions::render_suggestions_container( 'product-edit-header' );
		WC_Marketplace_Suggestions::render_suggestions_container( 'product-edit-meta-tab-body' );
		WC_Marketplace_Suggestions::render_suggestions_container( 'product-edit-footer' );
	?>
</div>
