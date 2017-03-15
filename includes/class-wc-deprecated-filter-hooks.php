<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles deprecation notices and triggering of legacy filter hooks.
 *
 * @since 3.0.0
 */
class WC_Deprecated_Filter_Hooks extends WC_Deprecated_Hooks {

	/**
	 * Array of deprecated hooks we need to handle.
	 *
	 * @var array
	 */
	protected $deprecated_hooks = array(
		'woocommerce_structured_data_order'         => 'woocommerce_email_order_schema_markup',
		'woocommerce_add_to_cart_fragments'         => 'add_to_cart_fragments',
		'woocommerce_add_to_cart_redirect'          => 'add_to_cart_redirect',
		'woocommerce_product_get_width'             => 'woocommerce_product_width',
		'woocommerce_product_get_height'            => 'woocommerce_product_height',
		'woocommerce_product_get_length'            => 'woocommerce_product_length',
		'woocommerce_product_get_weight'            => 'woocommerce_product_weight',
		'woocommerce_product_get_sku'               => 'woocommerce_get_sku',
		'woocommerce_product_get_price'             => 'woocommerce_get_price',
		'woocommerce_product_get_regular_price'     => 'woocommerce_get_regular_price',
		'woocommerce_product_get_sale_price'        => 'woocommerce_get_sale_price',
		'woocommerce_product_get_tax_class'         => 'woocommerce_product_tax_class',
		'woocommerce_product_get_stock_quantity'    => 'woocommerce_get_stock_quantity',
		'woocommerce_product_get_attributes'        => 'woocommerce_get_product_attributes',
		'woocommerce_product_get_gallery_image_ids' => 'woocommerce_product_gallery_attachment_ids',
		'woocommerce_product_get_review_count'      => 'woocommerce_product_review_count',
		'woocommerce_product_get_downloads'         => 'woocommerce_product_files',
		'woocommerce_order_get_currency'            => 'woocommerce_get_currency',
		'woocommerce_order_get_discount_total'      => 'woocommerce_order_amount_discount_total',
		'woocommerce_order_get_discount_tax'        => 'woocommerce_order_amount_discount_tax',
		'woocommerce_order_get_shipping_total'      => 'woocommerce_order_amount_shipping_total',
		'woocommerce_order_get_shipping_tax'        => 'woocommerce_order_amount_shipping_tax',
		'woocommerce_order_get_cart_tax'            => 'woocommerce_order_amount_cart_tax',
		'woocommerce_order_get_total'               => 'woocommerce_order_amount_total',
		'woocommerce_order_get_total_tax'           => 'woocommerce_order_amount_total_tax',
		'woocommerce_order_get_total_discount'      => 'woocommerce_order_amount_total_discount',
		'woocommerce_order_get_subtotal'            => 'woocommerce_order_amount_subtotal',
		'woocommerce_order_get_tax_totals'          => 'woocommerce_order_tax_totals',
		'woocommerce_get_order_refund_get_amount'   => 'woocommerce_refund_amount',
		'woocommerce_get_order_refund_get_reason'   => 'woocommerce_refund_reason',
		'default_checkout_billing_country'          => 'default_checkout_country',
		'default_checkout_billing_state'            => 'default_checkout_state',
		'default_checkout_billing_postcode'         => 'default_checkout_postcode',
	);

	/**
	 * Hook into the new hook so we can handle deprecated hooks once fired.
	 * @param  string $hook_name
	 */
	public function hook_in( $hook_name ) {
		add_filter( $hook_name, array( $this, 'maybe_handle_deprecated_hook' ), -1000, 8 );
	}

	/**
	 * If the old hook is in-use, trigger it.
	 *
	 * @param string $new_hook
	 * @param string $old_hook
	 * @param array $new_callback_args
	 * @param mixed $return_value
	 * @return mixed
	 */
	public function handle_deprecated_hook( $new_hook, $old_hook, $new_callback_args, $return_value ) {
		if ( has_filter( $old_hook ) ) {
			$this->display_notice( $old_hook, $new_hook );
			$return_value = $this->trigger_hook( $old_hook, $new_callback_args );
		}
		return $return_value;
	}

	/**
	 * Fire off a legacy hook with it's args.
	 *
	 * @param  string $old_hook
	 * @param  array $new_callback_args
	 * @return mixed
	 */
	protected function trigger_hook( $old_hook, $new_callback_args ) {
		return apply_filters_ref_array( $old_hook, $new_callback_args );
	}
}
