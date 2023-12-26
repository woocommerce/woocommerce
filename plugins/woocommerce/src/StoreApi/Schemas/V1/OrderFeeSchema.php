<?php
namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

/**
 * OrderFeeSchema class.
 */
class OrderFeeSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'order_fee';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'order-fee';

	/**
	 * Cart schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'id'     => [
				'description' => __( 'Unique identifier for the fee within the cart', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'name'   => [
				'description' => __( 'Fee name', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'totals' => [
				'description' => __( 'Fee total amounts provided using the smallest unit of the currency.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'properties'  => array_merge(
					$this->get_store_currency_properties(),
					[
						'total'     => [
							'description' => __( 'Total amount for this fee.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'total_tax' => [
							'description' => __( 'Total tax amount for this fee.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
					]
				),
			],
		];
	}

	/**
	 * Convert a WooCommerce cart fee to an object suitable for the response.
	 *
	 * @param \WC_Order_Item_Fee $fee Order fee object.
	 * @return array
	 */
	public function get_item_response( $fee ) {
		if ( ! $fee ) {
			return [];
		}
		return [
			'key'    => $fee->get_id(),
			'name'   => $this->prepare_html_response( $fee->get_name() ),
			'totals' => (object) $this->prepare_currency_response(
				[
					'total'     => $this->prepare_money_response( $fee->get_total(), wc_get_price_decimals() ),
					'total_tax' => $this->prepare_money_response( $fee->get_total_tax(), wc_get_price_decimals(), PHP_ROUND_HALF_DOWN ),
				]
			),
		];
	}
}
