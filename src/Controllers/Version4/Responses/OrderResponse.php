<?php
/**
 * Convert an order object to the order schema format.
 *
 * @package Automattic/WooCommerce/RestApi
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4\Responses;

defined( 'ABSPATH' ) || exit;

/**
 * OrderResponse class.
 */
class OrderResponse extends AbstractObjectResponse {

	/**
	 * Decimal places to round to.
	 *
	 * @var int
	 */
	protected $dp;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->dp = wc_get_price_decimals();
	}

	/**
	 * Set decimal places.
	 *
	 * @param int $dp Decimals.
	 */
	public function set_dp( $dp ) {
		$this->dp = (int) $dp;
	}

	/**
	 * Convert object to match data in the schema.
	 *
	 * @param \WC_Order $object Product data.
	 * @param string    $context Request context. Options: 'view' and 'edit'.
	 * @return array
	 */
	public function prepare_response( $object, $context ) {
		$data              = $object->get_data();
		$format_decimal    = array( 'discount_total', 'discount_tax', 'shipping_total', 'shipping_tax', 'shipping_total', 'shipping_tax', 'cart_tax', 'total', 'total_tax' );
		$format_date       = array( 'date_created', 'date_modified', 'date_completed', 'date_paid' );
		$format_line_items = array( 'line_items', 'tax_lines', 'shipping_lines', 'fee_lines', 'coupon_lines' );

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
			$data[ $key ] = wc_format_decimal( $data[ $key ], $this->dp );
		}

		// Format date values.
		foreach ( $format_date as $key ) {
			$datetime              = $data[ $key ];
			$data[ $key ]          = wc_rest_prepare_date_response( $datetime, false );
			$data[ $key . '_gmt' ] = wc_rest_prepare_date_response( $datetime );
		}

		// Format the order status.
		$data['status'] = 'wc-' === substr( $data['status'], 0, 3 ) ? substr( $data['status'], 3 ) : $data['status'];

		// Format line items.
		foreach ( $format_line_items as $key ) {
			$data[ $key ] = array_values( array_map( array( $this, 'prepare_order_item_data' ), $data[ $key ] ) );
		}

		// Refunds.
		$data['refunds'] = array();
		foreach ( $object->get_refunds() as $refund ) {
			$data['refunds'][] = array(
				'id'     => $refund->get_id(),
				'reason' => $refund->get_reason() ? $refund->get_reason() : '',
				'total'  => '-' . wc_format_decimal( $refund->get_amount(), $this->dp ),
			);
		}

		// Currency symbols.
		$currency_symbol         = get_woocommerce_currency_symbol( $data['currency'] );
		$data['currency_symbol'] = html_entity_decode( $currency_symbol );

		return array(
			'id'                   => $object->get_id(),
			'parent_id'            => $data['parent_id'],
			'number'               => $data['number'],
			'order_key'            => $data['order_key'],
			'created_via'          => $data['created_via'],
			'version'              => $data['version'],
			'status'               => $data['status'],
			'currency'             => $data['currency'],
			'currency_symbol'      => $data['currency_symbol'],
			'date_created'         => $data['date_created'],
			'date_created_gmt'     => $data['date_created_gmt'],
			'date_modified'        => $data['date_modified'],
			'date_modified_gmt'    => $data['date_modified_gmt'],
			'discount_total'       => $data['discount_total'],
			'discount_tax'         => $data['discount_tax'],
			'shipping_total'       => $data['shipping_total'],
			'shipping_tax'         => $data['shipping_tax'],
			'cart_tax'             => $data['cart_tax'],
			'total'                => $data['total'],
			'total_tax'            => $data['total_tax'],
			'prices_include_tax'   => $data['prices_include_tax'],
			'customer_id'          => $data['customer_id'],
			'customer_ip_address'  => $data['customer_ip_address'],
			'customer_user_agent'  => $data['customer_user_agent'],
			'customer_note'        => $data['customer_note'],
			'billing'              => $data['billing'],
			'shipping'             => $data['shipping'],
			'payment_method'       => $data['payment_method'],
			'payment_method_title' => $data['payment_method_title'],
			'transaction_id'       => $data['transaction_id'],
			'date_paid'            => $data['date_paid'],
			'date_paid_gmt'        => $data['date_paid_gmt'],
			'date_completed'       => $data['date_completed'],
			'date_completed_gmt'   => $data['date_completed_gmt'],
			'cart_hash'            => $data['cart_hash'],
			'meta_data'            => $data['meta_data'],
			'line_items'           => $data['line_items'],
			'tax_lines'            => $data['tax_lines'],
			'shipping_lines'       => $data['shipping_lines'],
			'fee_lines'            => $data['fee_lines'],
			'coupon_lines'         => $data['coupon_lines'],
			'refunds'              => $data['refunds'],
		);
	}

	/**
	 * Expands an order item to get its data.
	 *
	 * @param \WC_Order_item $item Order item data.
	 * @return array
	 */
	protected function prepare_order_item_data( $item ) {
		$data           = $item->get_data();
		$format_decimal = array( 'subtotal', 'subtotal_tax', 'total', 'total_tax', 'tax_total', 'shipping_tax_total' );

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$data[ $key ] = wc_format_decimal( $data[ $key ], $this->dp );
			}
		}

		// Add SKU and PRICE to products.
		if ( is_callable( array( $item, 'get_product' ) ) ) {
			$data['sku']   = $item->get_product() ? $item->get_product()->get_sku() : null;
			$data['price'] = $item->get_quantity() ? $item->get_total() / $item->get_quantity() : 0;
		}

		// Format taxes.
		if ( ! empty( $data['taxes']['total'] ) ) {
			$taxes = array();

			foreach ( $data['taxes']['total'] as $tax_rate_id => $tax ) {
				$taxes[] = array(
					'id'       => $tax_rate_id,
					'total'    => $tax,
					'subtotal' => isset( $data['taxes']['subtotal'][ $tax_rate_id ] ) ? $data['taxes']['subtotal'][ $tax_rate_id ] : '',
				);
			}
			$data['taxes'] = $taxes;
		} elseif ( isset( $data['taxes'] ) ) {
			$data['taxes'] = array();
		}

		// Remove names for coupons, taxes and shipping.
		if ( isset( $data['code'] ) || isset( $data['rate_code'] ) || isset( $data['method_title'] ) ) {
			unset( $data['name'] );
		}

		// Remove props we don't want to expose.
		unset( $data['order_id'] );
		unset( $data['type'] );

		return $data;
	}
}
