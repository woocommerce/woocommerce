<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors\Settings;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;

class ConfigureSettingsAdvanced extends MapFieldsToOptions implements StepProcessor {
	protected array $options_map = array(
		// Page setup -> Page setup
		"cart_page"=> "woocommerce_cart_page_id",
		"checkout_page"=> "woocommerce_checkout_page_id",
		"my_account_page"=> "woocommerce_myaccount_page_id",
		"terms_and_conditions"=> "woocommerce_terms_page_id",
		"force_secure_checkout"=> "woocommerce_force_ssl_checkout",

		// Page setup -> Checkout endpoints
		"pay"=> "woocommerce_checkout_pay_endpoint",
		"order_received"=> "woocommerce_checkout_order_received_endpoint",
		"add_payment_method"=> "woocommerce_myaccount_add_payment_method_endpoint",
		"delete_payment_method"=> "woocommerce_myaccount_delete_payment_method_endpoint",
		"set_default_payment_method"=> "woocommerce_myaccount_set_default_payment_method_endpoint",

		// Page setup -> Account endpoints
		"orders"=> "woocommerce_myaccount_orders_endpoint",
		"view_order"=> "woocommerce_myaccount_view_order_endpoint",
		"downloads"=> "woocommerce_myaccount_downloads_endpoint",
		"edit_account"=> "woocommerce_myaccount_edit_account_endpoint",
		"addresses"=> "woocommerce_myaccount_edit_address_endpoint",
		"payment_methods"=> "woocommerce_myaccount_payment_methods_endpoint",
		"lost_password"=> "woocommerce_myaccount_lost_password_endpoint",
		"logout"=> "woocommerce_logout_endpoint",

		// WooCommerce.com
		"allow_usage_of_woocommerce_to_be_tracked" => "woocommerce_allow_tracking",
		"display_suggestions_within_woocommerce" => "woocommerce_show_marketplace_suggestions",

		// Features
		"enable_woocommerce_analytics"=> "woocommerce_analytics_enabled",
		"enable_order_attribution"=> "woocommerce_feature_order_attribution_enabled",
		"try_the_new_product_editor"=> "woocommerce_feature_product_block_editor_enabled",
		"create_and_use_full_text_search_indexes_for_orders"=> "woocommerce_hpos_fts_index_enabled"
	);
}
