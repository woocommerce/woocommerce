/**
 * Product settings returned by the v4 endpoint. Settings and select options can
 * be filtered, so properties may be absent and select values remain strings.
 */
export type Settings = Partial< {
	woocommerce_shop_page_id: string;
	woocommerce_cart_redirect_after_add: boolean;
	woocommerce_enable_ajax_add_to_cart: boolean;
	woocommerce_placeholder_image: string;
	woocommerce_weight_unit: string;
	woocommerce_dimension_unit: string;
	woocommerce_enable_reviews: boolean;
	woocommerce_review_rating_verification_label: boolean;
	woocommerce_review_rating_verification_required: boolean;
	woocommerce_enable_review_rating: boolean;
	woocommerce_review_rating_required: boolean;
	woocommerce_manage_stock: boolean;
	woocommerce_hold_stock_minutes: number;
	woocommerce_notify_low_stock: boolean;
	woocommerce_notify_no_stock: boolean;
	woocommerce_notify_backorder: boolean;
	woocommerce_stock_email_recipient: string;
	woocommerce_notify_low_stock_amount: number;
	woocommerce_notify_no_stock_amount: number;
	woocommerce_hide_out_of_stock_items: boolean;
	woocommerce_stock_format: string;
	woocommerce_file_download_method: string;
	woocommerce_downloads_redirect_fallback_allowed: boolean;
	woocommerce_downloads_require_login: boolean;
	woocommerce_downloads_grant_access_after_payment: boolean;
	woocommerce_downloads_deliver_inline: boolean;
	woocommerce_downloads_add_hash_to_filename: boolean;
	woocommerce_downloads_count_partial: boolean;
	woocommerce_attribute_lookup_enabled: boolean;
	woocommerce_attribute_lookup_direct_updates: boolean;
	woocommerce_attribute_lookup_optimized_updates: boolean;
	woocommerce_product_match_featured_image_by_sku: boolean;
} >;
