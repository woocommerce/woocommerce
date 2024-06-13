<?php
/**
 * Simple Product Form
 *
 * Title: Simple
 * Slug: simple
 * Description: A single physical or virtual product, e.g. a t-shirt or an eBook
 * Product Types: simple, variable
 *
 * @package WooCommerce\Templates
 * @version 9.1.0-beta.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<!-- wp:woocommerce/product-tab {"title":"General","id":"general","_templateBlockId":"general","_templateBlockOrder":10} -->
	<!-- wp:woocommerce/product-has-variations-notice {"content":"This product has options, such as size or color. You can manage each variation's images, downloads, and other details individually.","buttonText":"Go to Variations","type":"info","_templateBlockId":"product_variation_notice_general_tab","_templateBlockOrder":10} /-->
	<!-- wp:woocommerce/product-section {"title":"Basic details","description":"This info will be displayed on the product page, category pages, social media, and search results.","_templateBlockId":"basic-details","_templateBlockOrder":10} -->
		<!-- wp:woocommerce/product-details-section-description {"_templateBlockId":"product-details-section-description","_templateBlockOrder":10} /-->
		<!-- wp:woocommerce/product-name-field {"name":"Product name","autoFocus":true,"metadata":{"bindings":{"value":{"source":"woocommerce/entity-product","args":{"prop":"name"}}}},"_templateBlockId":"product-name","_templateBlockOrder":10} /-->
		<!-- wp:columns {"_templateBlockId":"product-pricing-group-pricing-columns","_templateBlockOrder":10} --><div class="wp-block-columns"></div>
			<!-- wp:column {"templateLock":"all","_templateBlockId":"product-pricing-group-pricing-column-1","_templateBlockOrder":10} --><div class="wp-block-column"></div>
				<!-- wp:woocommerce/product-regular-price-field {"name":"regular_price","label":"Regular price","help":"Per your \u003ca href=\u0022https://test.local/wp-admin/admin.php?page=wc-settings\u0026tab=general\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003estore settings\u003c/a\u003e, taxes are not enabled.","_templateBlockId":"product-pricing-regular-price","_templateBlockOrder":10,"_templateBlockDisableConditions":[{"expression":"editedProduct.type === \u0022variable\u0022"}]} /-->
			<!-- /wp:column -->
			<!-- wp:column {"templateLock":"all","_templateBlockId":"product-pricing-group-pricing-column-2","_templateBlockOrder":20} --><div class="wp-block-column"></div>
				<!-- wp:woocommerce/product-sale-price-field {"label":"Sale price","_templateBlockId":"product-pricing-sale-price","_templateBlockOrder":10,"_templateBlockDisableConditions":[{"expression":"editedProduct.type === \u0022variable\u0022"}]} /-->
			<!-- /wp:column -->
		<!-- /wp:columns -->
		<!-- wp:woocommerce/product-schedule-sale-fields {"_templateBlockId":"product-pricing-schedule-sale-fields","_templateBlockOrder":20} /-->
		<!-- wp:woocommerce/product-text-area-field {"label":"Summary","help":"Summarize this product in 1-2 short sentences. We'll show it at the top of the page.","property":"short_description","_templateBlockId":"product-summary","_templateBlockOrder":50} /-->
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"title":"Description","description":"What makes this product unique? What are its most important features? Enrich the product page by adding rich content using blocks.","_templateBlockId":"product-description-section","_templateBlockOrder":20} -->
		<!-- wp:woocommerce/product-description-field {"_templateBlockId":"product-description","_templateBlockOrder":10} -->
			<!-- wp:woocommerce/product-summary-field {"helpText":null,"label":null,"property":"description","_templateBlockId":"product-description__content","_templateBlockOrder":10} /--><!-- /wp:woocommerce/product-description-field -->
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"title":"Buy button","description":"Add a link and choose a label for the button linked to a product sold elsewhere.","_templateBlockId":"product-buy-button-section","_templateBlockOrder":30,"_templateBlockHideConditions":[{"expression":"editedProduct.type !== \u0022external\u0022"}]} -->
		<!-- wp:woocommerce/product-text-field {"property":"external_url","label":"Link to the external product","placeholder":"Enter the external URL to the product","suffix":true,"type":{"value":"url","message":"Link to the external product is an invalid URL."},"_templateBlockId":"product-external-url","_templateBlockOrder":10} /-->
		<!-- wp:columns {"_templateBlockId":"product-button-text-columns","_templateBlockOrder":20} --><div class="wp-block-columns"></div>
			<!-- wp:column {"_templateBlockId":"product-button-text-column1","_templateBlockOrder":10} --><div class="wp-block-column"></div>
				<!-- wp:woocommerce/product-text-field {"property":"button_text","label":"Buy button text","_templateBlockId":"product-button-text","_templateBlockOrder":10} /-->
			<!-- /wp:column -->
			<!-- wp:column {"_templateBlockId":"product-button-text-column2","_templateBlockOrder":20} /--><div class="wp-block-column"></div>
		<!-- /wp:columns -->
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"title":"Products in this group","description":"Make a collection of related products, enabling customers to purchase multiple items together.","_templateBlockId":"product-list-section","_templateBlockOrder":35,"_templateBlockHideConditions":[{"expression":"editedProduct.type !== \u0022grouped\u0022"}]} -->
		<!-- wp:woocommerce/product-list-field {"property":"grouped_products","_templateBlockId":"product-list","_templateBlockOrder":10} /-->
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"title":"Images","description":"Drag images, upload new ones or select files from your library. For best results, use JPEG files that are 1000 by 1000 pixels or larger. \u003ca href=\u0022https://woocommerce.com/posts/how-to-take-professional-product-photos-top-tips\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eHow to prepare images?\u003c/a\u003e","_templateBlockId":"product-images-section","_templateBlockOrder":40} -->
		<!-- wp:woocommerce/product-images-field {"images":[],"property":"images","_templateBlockId":"product-images","_templateBlockOrder":10} /-->
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"blockGap":"unit-40","_templateBlockId":"product-downloads-section-group","_templateBlockOrder":50,"_templateBlockHideConditions":[{"expression":"postType === \u0022product\u0022 \u0026\u0026 editedProduct.type !== \u0022simple\u0022"}]} -->
		<!-- wp:woocommerce/product-toggle-field {"property":"downloadable","label":"Include downloads","checkedHelp":"Add any files you'd like to make available for the customer to download after purchasing, such as instructions or warranty info.","uncheckedHelp":"Add any files you'd like to make available for the customer to download after purchasing, such as instructions or warranty info.","_templateBlockId":"product-downloadable","_templateBlockOrder":10} /-->
		<!-- wp:woocommerce/product-subsection {"title":"Downloads","description":"Add any files you'd like to make available for the customer to download after purchasing, such as instructions or warranty info. Store-wide updates can be managed in your \u003ca href=\u0022https://test.local/wp-admin/admin.php?page=wc-settings\u0026tab=products\u0026section=downloadable\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eproduct settings\u003c/a\u003e.","_templateBlockId":"product-downloads-section","_templateBlockOrder":20,"_templateBlockHideConditions":[{"expression":"editedProduct.downloadable !== true"}]} -->
			<!-- wp:woocommerce/product-downloads-field {"_templateBlockId":"product-downloads","_templateBlockOrder":10} /-->
		<!-- /wp:woocommerce/product-subsection -->
	<!-- /wp:woocommerce/product-section -->
<!-- /wp:woocommerce/product-tab -->
<!-- wp:woocommerce/product-tab {"title":"Variations","id":"variations","_templateBlockId":"variations","_templateBlockOrder":20,"_templateBlockHideConditions":[{"expression":"editedProduct.type === \u0022grouped\u0022"},{"expression":"editedProduct.type === \u0022external\u0022"}]} -->
	<!-- wp:woocommerce/product-section {"title":"Variation options","description":"Add and manage attributes used for product options, such as size and color.","_templateBlockId":"product-variation-options-section","_templateBlockOrder":10} -->
		<!-- wp:woocommerce/product-variations-options-field {"_templateBlockId":"product-variation-options","_templateBlockOrder":10} /-->
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"title":"Variations","description":"Manage individual product combinations created from options.","_templateBlockId":"product-variation-section","_templateBlockOrder":20} -->
		<!-- wp:woocommerce/product-variation-items-field {"_templateBlockId":"product-variation-items","_templateBlockOrder":10} /-->
	<!-- /wp:woocommerce/product-section -->
<!-- /wp:woocommerce/product-tab -->
<!-- wp:woocommerce/product-tab {"title":"Organization","id":"organization","_templateBlockId":"organization","_templateBlockOrder":30} -->
	<!-- wp:woocommerce/product-section {"title":"Product catalog","description":"Help customers find this product by assigning it to categories, adding extra details, and managing its visibility in your store and other channels.","_templateBlockId":"product-catalog-section","_templateBlockOrder":10} -->
		<!-- wp:woocommerce/product-taxonomy-field {"slug":"product_cat","property":"categories","label":"Categories","createTitle":"Create new category","dialogNameHelpText":"Shown to customers on the product page.","parentTaxonomyText":"Parent category","placeholder":"Search or create categories…","_templateBlockId":"product-categories","_templateBlockOrder":10} /-->
		<!-- wp:woocommerce/product-catalog-visibility-field {"label":"Hide in product catalog","visibility":"search","_templateBlockId":"product-catalog-search-visibility","_templateBlockOrder":20} /-->
		<!-- wp:woocommerce/product-catalog-visibility-field {"label":"Hide from search results","visibility":"catalog","_templateBlockId":"product-catalog-catalog-visibility","_templateBlockOrder":30} /-->
		<!-- wp:woocommerce/product-checkbox-field {"label":"Enable product reviews","property":"reviews_allowed","_templateBlockId":"product-enable-product-reviews","_templateBlockOrder":40} /-->
		<!-- wp:woocommerce/product-password-field {"label":"Require a password","_templateBlockId":"product-post-password","_templateBlockOrder":50} /-->
		<!-- wp:woocommerce/product-tag-field {"name":"tags","_templateBlockId":"product-tags","_templateBlockOrder":10000} /-->
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"title":"Attributes","description":"Use global attributes to allow shoppers to filter and search for this product. Use custom attributes to provide detailed product information.","blockGap":"unit-40","_templateBlockId":"product-attributes-section","_templateBlockOrder":20} -->
		<!-- wp:woocommerce/product-attributes-field {"_templateBlockId":"product-attributes","_templateBlockOrder":10} /-->
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"_templateBlockId":"product-custom-fields-wrapper-section","_templateBlockOrder":30} -->
		<!-- wp:woocommerce/product-custom-fields-toggle-field {"label":"Show custom fields","_templateBlockId":"product-custom-fields-toggle","_templateBlockOrder":10} -->
		<!-- wp:woocommerce/product-section {"blockGap":"unit-30","title":"Custom fields","description":"Custom fields can be used in a variety of ways, such as sharing more detailed product information, showing more input fields, or for internal inventory organization. \u003ca href=\u0022https://woocommerce.com/document/custom-product-fields/\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eRead more about custom fields\u003c/a\u003e","_templateBlockId":"product-custom-fields-section","_templateBlockOrder":10} -->
			<!-- wp:woocommerce/product-custom-fields {"_templateBlockId":"product-custom-fields","_templateBlockOrder":10} /-->
		<!-- /wp:woocommerce/product-section --><!-- /wp:woocommerce/product-custom-fields-toggle-field -->
	<!-- /wp:woocommerce/product-section -->
<!-- /wp:woocommerce/product-tab -->
<!-- wp:woocommerce/product-tab {"title":"Inventory","id":"inventory","_templateBlockId":"inventory","_templateBlockOrder":50} --><!-- wp:woocommerce/product-has-variations-notice {"content":"This product has options, such as size or color. You can now manage each variation's inventory and other details individually.","buttonText":"Go to Variations","type":"info","_templateBlockId":"product_variation_notice_inventory_tab","_templateBlockOrder":10} /-->
	<!-- wp:woocommerce/product-section {"title":"Inventory","description":"Set up and manage inventory for this product, including status and available quantity. \u003ca href=\u0022https://test.local/wp-admin/admin.php?page=wc-settings\u0026tab=products\u0026section=inventory\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eManage store inventory settings\u003c/a\u003e","blockGap":"unit-40","_templateBlockId":"product-inventory-section","_templateBlockOrder":20} -->
		<!-- wp:woocommerce/product-subsection {"_templateBlockId":"product-inventory-inner-section","_templateBlockOrder":10} -->
			<!-- wp:woocommerce/product-sku-field {"_templateBlockId":"product-sku-field","_templateBlockOrder":10,"_templateBlockDisableConditions":[{"expression":"editedProduct.type === \u0022variable\u0022"}]} /-->
			<!-- wp:woocommerce/product-toggle-field {"label":"Track inventory","property":"manage_stock","disabled":false,"disabledCopy":null,"_templateBlockId":"product-track-stock","_templateBlockOrder":20,"_templateBlockHideConditions":[{"expression":"editedProduct.type === \u0022external\u0022 || editedProduct.type === \u0022grouped\u0022"}],"_templateBlockDisableConditions":[{"expression":"editedProduct.type === \u0022variable\u0022"}]} /-->
			<!-- wp:woocommerce/product-inventory-quantity-field {"_templateBlockId":"product-inventory-quantity","_templateBlockOrder":30,"_templateBlockHideConditions":[{"expression":"editedProduct.manage_stock === false"},{"expression":"editedProduct.type === \u0022grouped\u0022"}]} /-->
		<!-- /wp:woocommerce/product-subsection -->
		<!-- wp:woocommerce/product-radio-field {"title":"Stock status","property":"stock_status","options":[{"label":"In stock","value":"instock"},{"label":"Out of stock","value":"outofstock"},{"label":"On backorder","value":"onbackorder"}],"_templateBlockId":"product-stock-status","_templateBlockOrder":10,"_templateBlockHideConditions":[{"expression":"editedProduct.manage_stock === true"},{"expression":"editedProduct.type === \u0022grouped\u0022"}],"_templateBlockDisableConditions":[{"expression":"editedProduct.type === \u0022variable\u0022"}]} /-->
		<!-- wp:woocommerce/product-text-area-field {"property":"purchase_note","label":"Post-purchase note","placeholder":"Enter an optional note attached to the order confirmation message sent to the shopper.","_templateBlockId":"product-purchase-note","_templateBlockOrder":20} /-->
		<!-- wp:woocommerce/product-collapsible {"toggleText":"Advanced","initialCollapsed":true,"persistRender":true,"_templateBlockId":"product-inventory-advanced","_templateBlockOrder":30,"_templateBlockHideConditions":[{"expression":"editedProduct.type === \u0022grouped\u0022"}]} -->
			<!-- wp:woocommerce/product-section {"blockGap":"unit-40","_templateBlockId":"woocommerce/product-section-1","_templateBlockOrder":10} -->
				<!-- wp:woocommerce/product-radio-field {"title":"When out of stock","property":"backorders","options":[{"label":"Allow purchases","value":"yes"},{"label":"Allow purchases, but notify customers","value":"notify"},{"label":"Don't allow purchases","value":"no"}],"_templateBlockId":"product-out-of-stock","_templateBlockOrder":10,"_templateBlockHideConditions":[{"expression":"editedProduct.manage_stock === false"}]} /-->
				<!-- wp:woocommerce/product-inventory-email-field {"_templateBlockId":"product-inventory-email","_templateBlockOrder":20,"_templateBlockHideConditions":[{"expression":"editedProduct.manage_stock === false"}]} /-->
				<!-- wp:woocommerce/product-checkbox-field {"title":"Restrictions","label":"Limit purchases to 1 item per order","property":"sold_individually","tooltip":"When checked, customers will be able to purchase only 1 item in a single order. This is particularly useful for items that have limited quantity, like art or handmade goods.","_templateBlockId":"product-limit-purchase","_templateBlockOrder":20} /-->
			<!-- /wp:woocommerce/product-section -->
		<!-- /wp:woocommerce/product-collapsible -->
	<!-- /wp:woocommerce/product-section -->
<!-- /wp:woocommerce/product-tab -->
<!-- wp:woocommerce/product-tab {"title":"Shipping","id":"shipping","_templateBlockId":"shipping","_templateBlockOrder":60,"_templateBlockHideConditions":[{"expression":"editedProduct.type === \u0022grouped\u0022"},{"expression":"editedProduct.type === \u0022external\u0022"}]} -->
	<!-- wp:woocommerce/product-has-variations-notice {"content":"This product has options, such as size or color. You can now manage each variation's shipping settings and other details individually.","buttonText":"Go to Variations","type":"info","_templateBlockId":"product_variation_notice_shipping_tab","_templateBlockOrder":10} /-->
	<!-- wp:woocommerce/product-section {"_templateBlockId":"product-virtual-section","_templateBlockOrder":10,"_templateBlockHideConditions":[{"expression":"editedProduct.type !== \u0022simple\u0022"}]} -->
		<!-- wp:woocommerce/product-toggle-field {"property":"virtual","checkedValue":false,"uncheckedValue":true,"label":"This product requires shipping or pickup","uncheckedHelp":"This product will not trigger your customer's shipping calculator in cart or at checkout. This product also won't require your customers to enter their shipping details at checkout. \u003ca href=\u0022https://woocommerce.com/document/managing-products/#adding-a-virtual-product\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eRead more about virtual products\u003c/a\u003e.","_templateBlockId":"product-virtual","_templateBlockOrder":10} /-->
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"title":"Fees \u0026 dimensions","description":"Set up shipping costs and enter dimensions used for accurate rate calculations. \u003ca href=\u0022https://woocommerce.com/posts/how-to-calculate-shipping-costs-for-your-woocommerce-store/\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eHow to get started?\u003c/a\u003e","_templateBlockId":"product-fee-and-dimensions-section","_templateBlockOrder":20} -->
		<!-- wp:woocommerce/product-shipping-class-field {"_templateBlockId":"product-shipping-class","_templateBlockOrder":10,"_templateBlockDisableConditions":[{"expression":"editedProduct.type === \u0022variable\u0022"}]} /-->
		<!-- wp:woocommerce/product-shipping-dimensions-fields {"_templateBlockId":"product-shipping-dimensions","_templateBlockOrder":20,"_templateBlockDisableConditions":[{"expression":"editedProduct.type === \u0022variable\u0022"}]} /-->
	<!-- /wp:woocommerce/product-section -->
<!-- /wp:woocommerce/product-tab -->
<!-- wp:woocommerce/product-tab {"title":"Linked products","id":"linked-products","_templateBlockId":"linked-products","_templateBlockOrder":70} -->
	<!-- wp:woocommerce/product-section {"title":"Upsells","description":"Upsells are typically products that are extra profitable or better quality or more expensive. Experiment with combinations to boost sales. \u003cbr /\u003e\u003ca href=\u0022https://woocommerce.com/document/related-products-up-sells-and-cross-sells/\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eLearn more about linked products\u003c/a\u003e","_templateBlockId":"product-linked-upsells-section","_templateBlockOrder":10} -->
		<!-- wp:woocommerce/product-linked-list-field {"property":"upsell_ids","emptyState":{"image":"ShoppingBags","tip":"Tip: Upsells are products that are extra profitable or better quality or more expensive. Experiment with combinations to boost sales.","isDismissible":true},"_templateBlockId":"product-linked-upsells","_templateBlockOrder":10} /-->
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"title":"Cross-sells","description":"By suggesting complementary products in the cart using cross-sells, you can significantly increase the average order value. \u003cbr /\u003e\u003ca href=\u0022https://woocommerce.com/document/related-products-up-sells-and-cross-sells/\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eLearn more about linked products\u003c/a\u003e","_templateBlockId":"product-linked-cross-sells-section","_templateBlockOrder":20,"_templateBlockHideConditions":[{"expression":"editedProduct.type === \u0022external\u0022 || editedProduct.type === \u0022grouped\u0022"}]} -->
	<!-- wp:woocommerce/product-linked-list-field {"property":"cross_sell_ids","emptyState":{"image":"CashRegister","tip":"Tip: By suggesting complementary products in the cart using cross-sells, you can significantly increase the average order value.","isDismissible":true},"_templateBlockId":"product-linked-cross-sells","_templateBlockOrder":10} /-->
	<!-- /wp:woocommerce/product-section -->
<!-- /wp:woocommerce/product-tab -->
