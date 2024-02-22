<?php
namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\Utilities\ProductItemTrait;

/**
 * OrderItemSchema class.
 */
class OrderItemSchema extends ItemSchema {
	use ProductItemTrait;

	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'order_item';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'order-item';

	/**
	 * Get order items data.
	 *
	 * @param \WC_Order_Item_Product $order_item Order item instance.
	 * @return array
	 */
	public function get_item_response( $order_item ) {
		$order   = $order_item->get_order();
		$product = $order_item->get_product();

		return [
			'key'                  => $order->get_order_key(),
			'id'                   => $order_item->get_id(),
			'quantity'             => $order_item->get_quantity(),
			'quantity_limits'      => array(
				'minimum'     => $order_item->get_quantity(),
				'maximum'     => $order_item->get_quantity(),
				'multiple_of' => 1,
				'editable'    => false,
			),
			'name'                 => $order_item->get_name(),
			'short_description'    => $this->prepare_html_response( wc_format_content( wp_kses_post( $product->get_short_description() ) ) ),
			'description'          => $this->prepare_html_response( wc_format_content( wp_kses_post( $product->get_description() ) ) ),
			'sku'                  => $this->prepare_html_response( $product->get_sku() ),
			'low_stock_remaining'  => null,
			'backorders_allowed'   => false,
			'show_backorder_badge' => false,
			'sold_individually'    => $product->is_sold_individually(),
			'permalink'            => $product->get_permalink(),
			'images'               => $this->get_images( $product ),
			'variation'            => $this->format_variation_data( $product->get_attributes(), $product ),
			'item_data'            => $order_item->get_all_formatted_meta_data(),
			'prices'               => (object) $this->prepare_product_price_response( $product, get_option( 'woocommerce_tax_display_cart' ) ),
			'totals'               => (object) $this->prepare_currency_response( $this->get_totals( $order_item ) ),
			'catalog_visibility'   => $product->get_catalog_visibility(),
		];
	}

	/**
	 * Get totals data.
	 *
	 * @param \WC_Order_Item_Product $order_item Order item instance.
	 * @return array
	 */
	public function get_totals( $order_item ) {
		return [
			'line_subtotal'     => $this->prepare_money_response( $order_item->get_subtotal(), wc_get_price_decimals() ),
			'line_subtotal_tax' => $this->prepare_money_response( $order_item->get_subtotal_tax(), wc_get_price_decimals() ),
			'line_total'        => $this->prepare_money_response( $order_item->get_total(), wc_get_price_decimals() ),
			'line_total_tax'    => $this->prepare_money_response( $order_item->get_total_tax(), wc_get_price_decimals() ),
		];
	}
}
