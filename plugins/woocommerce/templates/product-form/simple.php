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
<!-- wp:woocommerce/product-tab {"title":"General","id":"general"} -->
<div data-block-name="woocommerce/product-tab" data-title="General" data-id="general">
	<!-- wp:woocommerce/product-has-variations-notice {"content":"This product has options, such as size or color. You can manage each variation's images, downloads, and other details individually.","buttonText":"Go to Variations","type":"info"} -->
	<div data-block-name="woocommerce/product-has-variations-notice"
	data-content="This product has options, such as size or color. You can manage each variation&#039;s images, downloads, and other details individually."
	data-buttonText="Go to Variations" data-type="info">

	</div>
	<!-- /wp:woocommerce/product-has-variations-notice -->
	<!-- wp:woocommerce/product-section {"title":"Basic details","description":"This info will be displayed on the product page, category pages, social media, and search results."} -->
	<div data-block-name="woocommerce/product-section" data-title="Basic details"
	data-description="This info will be displayed on the product page, category pages, social media, and search results.">

	<!-- wp:woocommerce/product-name-field {"name":"Product name","autoFocus":true,"metadata":{"bindings":{"value":{"source":"woocommerce/entity-product","args":{"prop":"name"}}}}} -->
	<div data-block-name="woocommerce/product-name-field" data-name="Product name" data-autoFocus="1"
		data-metadata="Array">

	</div>
	<!-- /wp:woocommerce/product-name-field -->
	<!-- wp:column {"templateLock":"all"} -->
	<div data-block-name="core/column" data-templateLock="all">
		<!-- wp:woocommerce/product-regular-price-field {"name":"regular_price","label":"Regular price","help":"Per your \u003ca href=\u0022https://test.local/wp-admin/admin.php?page=wc-settings\u0026tab=general\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003estore settings\u003c/a\u003e, taxes are not enabled."} -->
		<div data-block-name="woocommerce/product-regular-price-field" data-name="regular_price"
		data-label="Regular price"
		data-help="Per your &lt;a href=&quot;https://test.local/wp-admin/admin.php?page=wc-settings&amp;tab=general&quot; target=&quot;_blank&quot; rel=&quot;noreferrer&quot;&gt;store settings&lt;/a&gt;, taxes are not enabled.">

		</div>
		<!-- /wp:woocommerce/product-regular-price-field -->
	</div>
	<!-- /wp:column -->
	<!-- wp:column {"templateLock":"all"} -->
	<div data-block-name="core/column" data-templateLock="all">
		<!-- wp:woocommerce/product-sale-price-field {"label":"Sale price"} -->
		<div data-block-name="woocommerce/product-sale-price-field" data-label="Sale price">

		</div>
		<!-- /wp:woocommerce/product-sale-price-field -->
	</div>
	<!-- /wp:column -->

	<!-- wp:woocommerce/product-text-area-field {"label":"Summary","help":"Summarize this product in 1-2 short sentences. We'll show it at the top of the page.","property":"short_description"} -->
	<div data-block-name="woocommerce/product-text-area-field" data-label="Summary"
		data-help="Summarize this product in 1-2 short sentences. We&#039;ll show it at the top of the page."
		data-property="short_description">

	</div>
	<!-- /wp:woocommerce/product-text-area-field -->
	</div>
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"title":"Description","description":"What makes this product unique? What are its most important features? Enrich the product page by adding rich content using blocks."} -->
	<div data-block-name="woocommerce/product-section" data-title="Description"
	data-description="What makes this product unique? What are its most important features? Enrich the product page by adding rich content using blocks.">
	<!-- wp:woocommerce/product-summary-field {"helpText":null,"label":null,"property":"description"} -->
	<div data-block-name="woocommerce/product-summary-field" data-helpText="" data-label="" data-property="description">

	</div>
	<!-- /wp:woocommerce/product-summary-field -->
	</div>
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"title":"Buy button","description":"Add a link and choose a label for the button linked to a product sold elsewhere."} -->
	<div data-block-name="woocommerce/product-section" data-title="Buy button"
	data-description="Add a link and choose a label for the button linked to a product sold elsewhere.">
	<!-- wp:woocommerce/product-text-field {"property":"external_url","label":"Link to the external product","placeholder":"Enter the external URL to the product","suffix":true,"type":{"value":"url","message":"Link to the external product is an invalid URL."}} -->
	<div data-block-name="woocommerce/product-text-field" data-property="external_url"
		data-label="Link to the external product" data-placeholder="Enter the external URL to the product" data-suffix="1"
		data-type="Array">

	</div>
	<!-- /wp:woocommerce/product-text-field -->
	<!-- wp:woocommerce/product-text-field {"property":"button_text","label":"Buy button text"} -->
	<div data-block-name="woocommerce/product-text-field" data-property="button_text" data-label="Buy button text">

	</div>
	<!-- /wp:woocommerce/product-text-field -->

	</div>
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"title":"Products in this group","description":"Make a collection of related products, enabling customers to purchase multiple items together."} -->
	<div data-block-name="woocommerce/product-section" data-title="Products in this group"
	data-description="Make a collection of related products, enabling customers to purchase multiple items together.">
	<!-- wp:woocommerce/product-list-field {"property":"grouped_products"} -->
	<div data-block-name="woocommerce/product-list-field" data-property="grouped_products">

	</div>
	<!-- /wp:woocommerce/product-list-field -->
	</div>
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"title":"Images","description":"Drag images, upload new ones or select files from your library. For best results, use JPEG files that are 1000 by 1000 pixels or larger. \u003ca href=\u0022https://woocommerce.com/posts/how-to-take-professional-product-photos-top-tips\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eHow to prepare images?\u003c/a\u003e"} -->
	<div data-block-name="woocommerce/product-section" data-title="Images"
	data-description="Drag images, upload new ones or select files from your library. For best results, use JPEG files that are 1000 by 1000 pixels or larger. &lt;a href=&quot;https://woocommerce.com/posts/how-to-take-professional-product-photos-top-tips&quot; target=&quot;_blank&quot; rel=&quot;noreferrer&quot;&gt;How to prepare images?&lt;/a&gt;">
	<!-- wp:woocommerce/product-images-field {"images":[],"property":"images"} -->
	<div data-block-name="woocommerce/product-images-field" data-images="Array" data-property="images">

	</div>
	<!-- /wp:woocommerce/product-images-field -->
	</div>
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"blockGap":"unit-40"} -->
	<div data-block-name="woocommerce/product-section" data-blockGap="unit-40">
	<!-- wp:woocommerce/product-toggle-field {"property":"downloadable","label":"Include downloads","checkedHelp":"Add any files you'd like to make available for the customer to download after purchasing, such as instructions or warranty info.","uncheckedHelp":"Add any files you'd like to make available for the customer to download after purchasing, such as instructions or warranty info."} -->
	<div data-block-name="woocommerce/product-toggle-field" data-property="downloadable" data-label="Include downloads"
		data-checkedHelp="Add any files you&#039;d like to make available for the customer to download after purchasing, such as instructions or warranty info."
		data-uncheckedHelp="Add any files you&#039;d like to make available for the customer to download after purchasing, such as instructions or warranty info.">

	</div>
	<!-- /wp:woocommerce/product-toggle-field -->
	<!-- wp:woocommerce/product-subsection {"title":"Downloads","description":"Add any files you'd like to make available for the customer to download after purchasing, such as instructions or warranty info. Store-wide updates can be managed in your \u003ca href=\u0022https://test.local/wp-admin/admin.php?page=wc-settings\u0026tab=products\u0026section=downloadable\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eproduct settings\u003c/a\u003e."} -->
	<div data-block-name="woocommerce/product-subsection" data-title="Downloads"
		data-description="Add any files you&#039;d like to make available for the customer to download after purchasing, such as instructions or warranty info. Store-wide updates can be managed in your &lt;a href=&quot;https://test.local/wp-admin/admin.php?page=wc-settings&amp;tab=products&amp;section=downloadable&quot; target=&quot;_blank&quot; rel=&quot;noreferrer&quot;&gt;product settings&lt;/a&gt;.">

	</div>
	<!-- /wp:woocommerce/product-subsection -->
	</div>
	<!-- /wp:woocommerce/product-section -->
</div>
<!-- /wp:woocommerce/product-tab -->
<!-- wp:woocommerce/product-tab {"title":"Variations","id":"variations"} -->
<div data-block-name="woocommerce/product-tab" data-title="Variations" data-id="variations">
	<!-- wp:woocommerce/product-section {"title":"Variation options","description":"Add and manage attributes used for product options, such as size and color."} -->
	<div data-block-name="woocommerce/product-section" data-title="Variation options"
	data-description="Add and manage attributes used for product options, such as size and color.">

	</div>
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"title":"Variations","description":"Manage individual product combinations created from options."} -->
	<div data-block-name="woocommerce/product-section" data-title="Variations"
	data-description="Manage individual product combinations created from options.">

	</div>
	<!-- /wp:woocommerce/product-section -->
</div>
<!-- /wp:woocommerce/product-tab -->
<!-- wp:woocommerce/product-tab {"title":"Organization","id":"organization"} -->
<div data-block-name="woocommerce/product-tab" data-title="Organization" data-id="organization">
	<!-- wp:woocommerce/product-section {"title":"Product catalog","description":"Help customers find this product by assigning it to categories, adding extra details, and managing its visibility in your store and other channels."} -->
	<div data-block-name="woocommerce/product-section" data-title="Product catalog"
	data-description="Help customers find this product by assigning it to categories, adding extra details, and managing its visibility in your store and other channels.">
	<!-- wp:woocommerce/product-taxonomy-field {"slug":"product_cat","property":"categories","label":"Categories","createTitle":"Create new category","dialogNameHelpText":"Shown to customers on the product page.","parentTaxonomyText":"Parent category","placeholder":"Search or create categories…"} -->
	<div data-block-name="woocommerce/product-taxonomy-field" data-slug="product_cat" data-property="categories"
		data-label="Categories" data-createTitle="Create new category"
		data-dialogNameHelpText="Shown to customers on the product page." data-parentTaxonomyText="Parent category"
		data-placeholder="Search or create categories…">

	</div>
	<!-- /wp:woocommerce/product-taxonomy-field -->
	<!-- wp:woocommerce/product-catalog-visibility-field {"label":"Hide in product catalog","visibility":"search"} -->
	<div data-block-name="woocommerce/product-catalog-visibility-field" data-label="Hide in product catalog"
		data-visibility="search">

	</div>
	<!-- /wp:woocommerce/product-catalog-visibility-field -->
	<!-- wp:woocommerce/product-catalog-visibility-field {"label":"Hide from search results","visibility":"catalog"} -->
	<div data-block-name="woocommerce/product-catalog-visibility-field" data-label="Hide from search results"
		data-visibility="catalog">

	</div>
	<!-- /wp:woocommerce/product-catalog-visibility-field -->
	<!-- wp:woocommerce/product-checkbox-field {"label":"Enable product reviews","property":"reviews_allowed"} -->
	<div data-block-name="woocommerce/product-checkbox-field" data-label="Enable product reviews"
		data-property="reviews_allowed">

	</div>
	<!-- /wp:woocommerce/product-checkbox-field -->
	<!-- wp:woocommerce/product-password-field {"label":"Require a password"} -->
	<div data-block-name="woocommerce/product-password-field" data-label="Require a password">

	</div>
	<!-- /wp:woocommerce/product-password-field -->
	<!-- wp:woocommerce/product-tag-field {"name":"tags"} -->
	<div data-block-name="woocommerce/product-tag-field" data-name="tags">

	</div>
	<!-- /wp:woocommerce/product-tag-field -->
	</div>
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"title":"Attributes","description":"Use global attributes to allow shoppers to filter and search for this product. Use custom attributes to provide detailed product information.","blockGap":"unit-40"} -->
	<div data-block-name="woocommerce/product-section" data-title="Attributes"
	data-description="Use global attributes to allow shoppers to filter and search for this product. Use custom attributes to provide detailed product information."
	data-blockGap="unit-40">

	</div>
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-custom-fields-toggle-field {"label":"Show custom fields"} -->
	<div data-block-name="woocommerce/product-custom-fields-toggle-field" data-label="Show custom fields">
	<!-- wp:woocommerce/product-section {"blockGap":"unit-30","title":"Custom fields","description":"Custom fields can be used in a variety of ways, such as sharing more detailed product information, showing more input fields, or for internal inventory organization. \u003ca href=\u0022https://woocommerce.com/document/custom-product-fields/\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eRead more about custom fields\u003c/a\u003e"} -->
	<div data-block-name="woocommerce/product-section" data-blockGap="unit-30" data-title="Custom fields"
		data-description="Custom fields can be used in a variety of ways, such as sharing more detailed product information, showing more input fields, or for internal inventory organization. &lt;a href=&quot;https://woocommerce.com/document/custom-product-fields/&quot; target=&quot;_blank&quot; rel=&quot;noreferrer&quot;&gt;Read more about custom fields&lt;/a&gt;">

	</div>
	<!-- /wp:woocommerce/product-section -->
	</div>
	<!-- /wp:woocommerce/product-custom-fields-toggle-field -->
</div>
<!-- /wp:woocommerce/product-tab -->
<!-- wp:woocommerce/product-tab {"title":"Inventory","id":"inventory"} -->
<div data-block-name="woocommerce/product-tab" data-title="Inventory" data-id="inventory">
	<!-- wp:woocommerce/product-has-variations-notice {"content":"This product has options, such as size or color. You can now manage each variation's inventory and other details individually.","buttonText":"Go to Variations","type":"info"} -->
	<div data-block-name="woocommerce/product-has-variations-notice"
	data-content="This product has options, such as size or color. You can now manage each variation&#039;s inventory and other details individually."
	data-buttonText="Go to Variations" data-type="info">

	</div>
	<!-- /wp:woocommerce/product-has-variations-notice -->
	<!-- wp:woocommerce/product-section {"title":"Inventory","description":"Set up and manage inventory for this product, including status and available quantity. \u003ca href=\u0022https://test.local/wp-admin/admin.php?page=wc-settings\u0026tab=products\u0026section=inventory\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eManage store inventory settings\u003c/a\u003e","blockGap":"unit-40"} -->
	<div data-block-name="woocommerce/product-section" data-title="Inventory"
	data-description="Set up and manage inventory for this product, including status and available quantity. &lt;a href=&quot;https://test.local/wp-admin/admin.php?page=wc-settings&amp;tab=products&amp;section=inventory&quot; target=&quot;_blank&quot; rel=&quot;noreferrer&quot;&gt;Manage store inventory settings&lt;/a&gt;"
	data-blockGap="unit-40">

	<!-- wp:woocommerce/product-toggle-field {"label":"Track inventory","property":"manage_stock","disabled":false,"disabledCopy":null} -->
	<div data-block-name="woocommerce/product-toggle-field" data-label="Track inventory" data-property="manage_stock"
		data-disabled="" data-disabledCopy="">

	</div>
	<!-- /wp:woocommerce/product-toggle-field -->

	<!-- wp:woocommerce/product-radio-field {"title":"Stock status","property":"stock_status","options":[{"label":"In stock","value":"instock"},{"label":"Out of stock","value":"outofstock"},{"label":"On backorder","value":"onbackorder"}]} -->
	<div data-block-name="woocommerce/product-radio-field" data-title="Stock status" data-property="stock_status"
		data-options="Array">

	</div>
	<!-- /wp:woocommerce/product-radio-field -->
	<!-- wp:woocommerce/product-text-area-field {"property":"purchase_note","label":"Post-purchase note","placeholder":"Enter an optional note attached to the order confirmation message sent to the shopper."} -->
	<div data-block-name="woocommerce/product-text-area-field" data-property="purchase_note"
		data-label="Post-purchase note"
		data-placeholder="Enter an optional note attached to the order confirmation message sent to the shopper.">

	</div>
	<!-- /wp:woocommerce/product-text-area-field -->
	<!-- wp:woocommerce/product-collapsible {"toggleText":"Advanced","initialCollapsed":true,"persistRender":true} -->
	<div data-block-name="woocommerce/product-collapsible" data-toggleText="Advanced" data-initialCollapsed="1"
		data-persistRender="1">
		<!-- wp:woocommerce/product-section {"blockGap":"unit-40"} -->
		<div data-block-name="woocommerce/product-section" data-blockGap="unit-40">
		<!-- wp:woocommerce/product-radio-field {"title":"When out of stock","property":"backorders","options":[{"label":"Allow purchases","value":"yes"},{"label":"Allow purchases, but notify customers","value":"notify"},{"label":"Don't allow purchases","value":"no"}]} -->
		<div data-block-name="woocommerce/product-radio-field" data-title="When out of stock" data-property="backorders"
			data-options="Array">

		</div>
		<!-- /wp:woocommerce/product-radio-field -->

		<!-- wp:woocommerce/product-checkbox-field {"title":"Restrictions","label":"Limit purchases to 1 item per order","property":"sold_individually","tooltip":"When checked, customers will be able to purchase only 1 item in a single order. This is particularly useful for items that have limited quantity, like art or handmade goods."} -->
		<div data-block-name="woocommerce/product-checkbox-field" data-title="Restrictions"
			data-label="Limit purchases to 1 item per order" data-property="sold_individually"
			data-tooltip="When checked, customers will be able to purchase only 1 item in a single order. This is particularly useful for items that have limited quantity, like art or handmade goods.">

		</div>
		<!-- /wp:woocommerce/product-checkbox-field -->
		</div>
		<!-- /wp:woocommerce/product-section -->
	</div>
	<!-- /wp:woocommerce/product-collapsible -->
	</div>
	<!-- /wp:woocommerce/product-section -->
</div>
<!-- /wp:woocommerce/product-tab -->
<!-- wp:woocommerce/product-tab {"title":"Shipping","id":"shipping"} -->
<div data-block-name="woocommerce/product-tab" data-title="Shipping" data-id="shipping">
	<!-- wp:woocommerce/product-has-variations-notice {"content":"This product has options, such as size or color. You can now manage each variation's shipping settings and other details individually.","buttonText":"Go to Variations","type":"info"} -->
	<div data-block-name="woocommerce/product-has-variations-notice"
	data-content="This product has options, such as size or color. You can now manage each variation&#039;s shipping settings and other details individually."
	data-buttonText="Go to Variations" data-type="info">

	</div>
	<!-- /wp:woocommerce/product-has-variations-notice -->
	<!-- wp:woocommerce/product-toggle-field {"property":"virtual","checkedValue":false,"uncheckedValue":true,"label":"This product requires shipping or pickup","uncheckedHelp":"This product will not trigger your customer's shipping calculator in cart or at checkout. This product also won't require your customers to enter their shipping details at checkout. \u003ca href=\u0022https://woocommerce.com/document/managing-products/#adding-a-virtual-product\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eRead more about virtual products\u003c/a\u003e."} -->
	<div data-block-name="woocommerce/product-toggle-field" data-property="virtual" data-checkedValue=""
	data-uncheckedValue="1" data-label="This product requires shipping or pickup"
	data-uncheckedHelp="This product will not trigger your customer&#039;s shipping calculator in cart or at checkout. This product also won&#039;t require your customers to enter their shipping details at checkout. &lt;a href=&quot;https://woocommerce.com/document/managing-products/#adding-a-virtual-product&quot; target=&quot;_blank&quot; rel=&quot;noreferrer&quot;&gt;Read more about virtual products&lt;/a&gt;.">

	</div>
	<!-- /wp:woocommerce/product-toggle-field -->
	<!-- wp:woocommerce/product-section {"title":"Fees \u0026 dimensions","description":"Set up shipping costs and enter dimensions used for accurate rate calculations. \u003ca href=\u0022https://woocommerce.com/posts/how-to-calculate-shipping-costs-for-your-woocommerce-store/\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eHow to get started?\u003c/a\u003e"} -->
	<div data-block-name="woocommerce/product-section" data-title="Fees &amp; dimensions"
	data-description="Set up shipping costs and enter dimensions used for accurate rate calculations. &lt;a href=&quot;https://woocommerce.com/posts/how-to-calculate-shipping-costs-for-your-woocommerce-store/&quot; target=&quot;_blank&quot; rel=&quot;noreferrer&quot;&gt;How to get started?&lt;/a&gt;">


	</div>
	<!-- /wp:woocommerce/product-section -->
</div>
<!-- /wp:woocommerce/product-tab -->
<!-- wp:woocommerce/product-tab {"title":"Linked products","id":"linked-products"} -->
<div data-block-name="woocommerce/product-tab" data-title="Linked products" data-id="linked-products">
	<!-- wp:woocommerce/product-section {"title":"Upsells","description":"Upsells are typically products that are extra profitable or better quality or more expensive. Experiment with combinations to boost sales. \u003cbr /\u003e\u003ca href=\u0022https://woocommerce.com/document/related-products-up-sells-and-cross-sells/\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eLearn more about linked products\u003c/a\u003e"} -->
	<div data-block-name="woocommerce/product-section" data-title="Upsells"
	data-description="Upsells are typically products that are extra profitable or better quality or more expensive. Experiment with combinations to boost sales. &lt;br /&gt;&lt;a href=&quot;https://woocommerce.com/document/related-products-up-sells-and-cross-sells/&quot; target=&quot;_blank&quot; rel=&quot;noreferrer&quot;&gt;Learn more about linked products&lt;/a&gt;">
	<!-- wp:woocommerce/product-linked-list-field {"property":"upsell_ids","emptyState":{"image":"ShoppingBags","tip":"Tip: Upsells are products that are extra profitable or better quality or more expensive. Experiment with combinations to boost sales.","isDismissible":true}} -->
	<div data-block-name="woocommerce/product-linked-list-field" data-property="upsell_ids" data-emptyState="Array">

	</div>
	<!-- /wp:woocommerce/product-linked-list-field -->
	</div>
	<!-- /wp:woocommerce/product-section -->
	<!-- wp:woocommerce/product-section {"title":"Cross-sells","description":"By suggesting complementary products in the cart using cross-sells, you can significantly increase the average order value. \u003cbr /\u003e\u003ca href=\u0022https://woocommerce.com/document/related-products-up-sells-and-cross-sells/\u0022 target=\u0022_blank\u0022 rel=\u0022noreferrer\u0022\u003eLearn more about linked products\u003c/a\u003e"} -->
	<div data-block-name="woocommerce/product-section" data-title="Cross-sells"
	data-description="By suggesting complementary products in the cart using cross-sells, you can significantly increase the average order value. &lt;br /&gt;&lt;a href=&quot;https://woocommerce.com/document/related-products-up-sells-and-cross-sells/&quot; target=&quot;_blank&quot; rel=&quot;noreferrer&quot;&gt;Learn more about linked products&lt;/a&gt;">
	<!-- wp:woocommerce/product-linked-list-field {"property":"cross_sell_ids","emptyState":{"image":"CashRegister","tip":"Tip: By suggesting complementary products in the cart using cross-sells, you can significantly increase the average order value.","isDismissible":true}} -->
	<div data-block-name="woocommerce/product-linked-list-field" data-property="cross_sell_ids" data-emptyState="Array">

	</div>
	<!-- /wp:woocommerce/product-linked-list-field -->
	</div>
	<!-- /wp:woocommerce/product-section -->
</div>
<!-- /wp:woocommerce/product-tab -->
