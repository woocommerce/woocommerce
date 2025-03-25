/**
 * Internal dependencies
 */
import { BlueprintStep } from './types';

const OPTIONS_GROUPS = {
	woocommerce_store_address: 'General',
	woocommerce_store_address_2: 'General',
	woocommerce_store_city: 'General',
	woocommerce_default_country: 'General',
	woocommerce_store_postcode: 'General',
	woocommerce_allowed_countries: 'General',
	woocommerce_all_except_countries: 'General',
	woocommerce_specific_allowed_countries: 'General',
	woocommerce_ship_to_countries: 'General',
	woocommerce_specific_ship_to_countries: 'General',
	woocommerce_default_customer_address: 'General',
	woocommerce_calc_taxes: 'General',
	woocommerce_enable_coupons: 'General',
	woocommerce_calc_discounts_sequentially: 'General',
	woocommerce_currency: 'General',
	woocommerce_currency_pos: 'General',
	woocommerce_price_thousand_sep: 'General',
	woocommerce_price_decimal_sep: 'General',
	woocommerce_price_num_decimals: 'General',
	woocommerce_shop_page_id: 'Products',
	woocommerce_cart_redirect_after_add: 'Products',
	woocommerce_enable_ajax_add_to_cart: 'Products',
	woocommerce_placeholder_image: 'Products',
	woocommerce_weight_unit: 'Products',
	woocommerce_dimension_unit: 'Products',
	woocommerce_enable_reviews: 'Products',
	woocommerce_review_rating_verification_label: 'Products',
	woocommerce_review_rating_verification_required: 'Products',
	woocommerce_enable_review_rating: 'Products',
	woocommerce_review_rating_required: 'Products',
	woocommerce_manage_stock: 'Products',
	woocommerce_hold_stock_minutes: 'Products',
	woocommerce_notify_low_stock: 'Products',
	woocommerce_notify_no_stock: 'Products',
	woocommerce_stock_email_recipient: 'Products',
	woocommerce_notify_low_stock_amount: 'Products',
	woocommerce_notify_no_stock_amount: 'Products',
	woocommerce_hide_out_of_stock_items: 'Products',
	woocommerce_stock_format: 'Products',
	woocommerce_file_download_method: 'Products',
	woocommerce_downloads_redirect_fallback_allowed: 'Products',
	woocommerce_downloads_require_login: 'Products',
	woocommerce_downloads_grant_access_after_payment: 'Products',
	woocommerce_downloads_deliver_inline: 'Products',
	woocommerce_downloads_add_hash_to_filename: 'Products',
	woocommerce_downloads_count_partial: 'Products',
	woocommerce_attribute_lookup_enabled: 'Products',
	woocommerce_attribute_lookup_direct_updates: 'Products',
	woocommerce_attribute_lookup_optimized_updates: 'Products',
	woocommerce_product_match_featured_image_by_sku: 'Products',
	woocommerce_bacs_settings: 'Payments',
	woocommerce_cheque_settings: 'Payments',
	woocommerce_cod_settings: 'Payments',
	woocommerce_enable_guest_checkout: 'Accounts',
	woocommerce_enable_checkout_login_reminder: 'Accounts',
	woocommerce_enable_delayed_account_creation: 'Accounts',
	woocommerce_enable_signup_and_login_from_checkout: 'Accounts',
	woocommerce_enable_myaccount_registration: 'Accounts',
	woocommerce_registration_generate_password: 'Accounts',
	woocommerce_erasure_request_removes_order_data: 'Accounts',
	woocommerce_erasure_request_removes_download_data: 'Accounts',
	woocommerce_allow_bulk_remove_personal_data: 'Accounts',
	woocommerce_registration_privacy_policy_text: 'Accounts',
	woocommerce_checkout_privacy_policy_text: 'Accounts',
	woocommerce_delete_inactive_accounts: 'Accounts',
	woocommerce_trash_pending_orders: 'Accounts',
	woocommerce_trash_failed_orders: 'Accounts',
	woocommerce_trash_cancelled_orders: 'Accounts',
	woocommerce_anonymize_refunded_orders: 'Accounts',
	woocommerce_anonymize_completed_orders: 'Accounts',
	woocommerce_email_from_name: 'Emails',
	woocommerce_email_from_address: 'Emails',
	woocommerce_email_header_image: 'Emails',
	woocommerce_email_base_color: 'Emails',
	woocommerce_email_background_color: 'Emails',
	woocommerce_email_body_background_color: 'Emails',
	woocommerce_email_text_color: 'Emails',
	woocommerce_email_footer_text: 'Emails',
	woocommerce_email_footer_text_color: 'Emails',
	woocommerce_email_auto_sync_with_theme: 'Emails',
	woocommerce_merchant_email_notifications: 'Emails',
	woocommerce_coming_soon: 'Site visibility',
	woocommerce_store_pages_only: 'Site visibility',
	woocommerce_cart_page_id: 'Advanced',
	woocommerce_checkout_page_id: 'Advanced',
	woocommerce_myaccount_page_id: 'Advanced',
	woocommerce_terms_page_id: 'Advanced',
	woocommerce_checkout_pay_endpoint: 'Advanced',
	woocommerce_checkout_order_received_endpoint: 'Advanced',
	woocommerce_myaccount_add_payment_method_endpoint: 'Advanced',
	woocommerce_myaccount_delete_payment_method_endpoint: 'Advanced',
	woocommerce_myaccount_set_default_payment_method_endpoint: 'Advanced',
	woocommerce_myaccount_orders_endpoint: 'Advanced',
	woocommerce_myaccount_view_order_endpoint: 'Advanced',
	woocommerce_myaccount_downloads_endpoint: 'Advanced',
	woocommerce_myaccount_edit_account_endpoint: 'Advanced',
	woocommerce_myaccount_edit_address_endpoint: 'Advanced',
	woocommerce_myaccount_payment_methods_endpoint: 'Advanced',
	woocommerce_myaccount_lost_password_endpoint: 'Advanced',
	woocommerce_logout_endpoint: 'Advanced',
	woocommerce_api_enabled: 'Advanced',
	woocommerce_allow_tracking: 'Advanced',
	woocommerce_show_marketplace_suggestions: 'Advanced',
	woocommerce_custom_orders_table_enabled: 'Advanced',
	woocommerce_custom_orders_table_data_sync_enabled: 'Advanced',
	woocommerce_analytics_enabled: 'Advanced',
	woocommerce_feature_rate_limit_checkout_enabled: 'Advanced',
	woocommerce_feature_order_attribution_enabled: 'Advanced',
	woocommerce_feature_site_visibility_badge_enabled: 'Advanced',
	woocommerce_feature_remote_logging_enabled: 'Advanced',
	woocommerce_feature_email_improvements_enabled: 'Advanced',
	woocommerce_feature_blueprint_enabled: 'Advanced',
	woocommerce_feature_product_block_editor_enabled: 'Advanced',
	woocommerce_hpos_fts_index_enabled: 'Advanced',
	woocommerce_feature_cost_of_goods_sold_enabled: 'Advanced',
};
/**
 * Get option groups from options
 *
 * Takes a list of options and return the groups they belong to.
 *
 * In this context, groups are the sections in the settings page (e.g. General, Products, Payments, etc).
 *
 * @param options a list of options
 * @return string[] a list of groups
 */
export const getOptionGroups = ( options: string[] ) => {
	const groups = new Set();
	options.forEach( ( option ) => {
		if ( OPTIONS_GROUPS[ option as keyof typeof OPTIONS_GROUPS ] ) {
			groups.add(
				OPTIONS_GROUPS[ option as keyof typeof OPTIONS_GROUPS ]
			);
		}
	} );
	return Array.from( groups );
};

/**
 * Take an array of Blueprint steps, filter `setSiteOptions` steps and return the groups of options
 *
 * @param steps a list of Blueprint steps
 * @return string[] a list of groups
 */
export const getOptionGroupsFromSteps = (
	steps: ( BlueprintStep & { options?: Record< string, string > } )[]
) => {
	const options = steps.reduce< string[] >( ( acc, step ) => {
		if ( step.step === 'setSiteOptions' && step.options ) {
			acc.push( ...Object.keys( step.options ) );
		}
		return acc;
	}, [] );

	return getOptionGroups( options );
};
