<?php
/**
 * REST API Order Refunds controller
 *
 * Handles requests to the /orders/<order_id>/refunds endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RestApiParameterUtil;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareTrait;

/**
 * REST API Order Refunds controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Order_Refunds_V2_Controller
 */
class WC_REST_Order_Refunds_Controller extends WC_REST_Order_Refunds_V2_Controller {
	use CogsAwareTrait;

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Prepares one object for create or update operation.
	 *
	 * @since  3.0.0
	 * @param  WP_REST_Request $request Request object.
	 * @param  bool            $creating If is creating a new object.
	 * @return WP_Error|WC_Data The prepared item, or WP_Error object on failure.
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		RestApiParameterUtil::adjust_create_refund_request_parameters( $request );

		$order = wc_get_order( (int) $request['order_id'] );

		if ( ! $order ) {
			return new WP_Error( 'woocommerce_rest_invalid_order_id', __( 'Invalid order ID.', 'woocommerce' ), 404 );
		}

		if ( 0 > $request['amount'] ) {
			return new WP_Error( 'woocommerce_rest_invalid_order_refund', __( 'Refund amount must be greater than zero.', 'woocommerce' ), 400 );
		}

		// Create the refund.
		$refund = wc_create_refund(
			array(
				'order_id'       => $order->get_id(),
				'amount'         => $request['amount'],
				'reason'         => $request['reason'],
				'line_items'     => $request['line_items'],
				'refund_payment' => $request['api_refund'],
				'restock_items'  => $request['api_restock'],
			)
		);

		if ( is_wp_error( $refund ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create_order_refund', $refund->get_error_message(), 500 );
		}

		if ( ! $refund ) {
			return new WP_Error( 'woocommerce_rest_cannot_create_order_refund', __( 'Cannot create order refund, please try again.', 'woocommerce' ), 500 );
		}

		if ( ! empty( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$refund->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
			$refund->save_meta_data();
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`,
		 * refers to the object type slug.
		 *
		 * @param WC_Data         $coupon   Object object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}_object", $refund, $request, $creating );
	}

	/**
	 * Get formatted item data.
	 * Invokes parents and then adds the proper Cost of Goods Sold information.
	 *
	 * @param  WC_Data $data_object WC_Data instance.
	 * @return array
	 * @since  9.9.0
	 */
	protected function get_formatted_item_data( $data_object ) {
		$data = parent::get_formatted_item_data( $data_object );
		if ( ! $this->cogs_is_enabled() ) {
			return $data;
		}

		if ( $data_object instanceof WC_Abstract_Order && $data_object->has_cogs() ) {
			$data['cost_of_goods_sold'] = array(
				'value' => $data_object->get_cogs_total_value(),
			);

			foreach ( $data['line_items'] as $key => $line_item ) {
				$cogs_value = $line_item['cogs_value'] ?? null;
				if ( ! is_null( $cogs_value ) ) {
					$data['line_items'][ $key ]['cost_of_goods_sold'] = array(
						'value' => $cogs_value,
					);
					unset( $data['line_items'][ $key ]['cogs_value'] );
				}
			}
		}
		return $data;
	}

	/**
	 * Get the refund schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['line_items']['items']['properties']['refund_total'] = array(
			'description' => __( 'Amount that will be refunded for this line item (excluding taxes).', 'woocommerce' ),
			'type'        => 'number',
			'context'     => array( 'edit' ),
			'readonly'    => true,
		);

		$schema['properties']['line_items']['items']['properties']['taxes']['items']['properties']['refund_total'] = array(
			'description' => __( 'Amount that will be refunded for this tax.', 'woocommerce' ),
			'type'        => 'number',
			'context'     => array( 'edit' ),
			'readonly'    => true,
		);

		$schema['properties']['api_restock'] = array(
			'description' => __( 'When true, refunded items are restocked.', 'woocommerce' ),
			'type'        => 'boolean',
			'context'     => array( 'edit' ),
			'default'     => true,
		);

		if ( $this->cogs_is_enabled() ) {
			$schema = $this->add_cogs_related_schema( $schema );
		}

		return $schema;
	}

	/**
	 * Add the Cost of Goods Sold related fields to the schema.
	 *
	 * @param array $schema The original schema.
	 * @return array The updated schema.
	 */
	private function add_cogs_related_schema( array $schema ): array {
		$schema['properties']['cost_of_goods_sold'] = array(
			'description' => __( 'Cost of Goods Sold data.', 'woocommerce' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'total_value' => array(
					'description' => __( 'Total value of the Cost of Goods Sold for the refund.', 'woocommerce' ),
					'type'        => 'number',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		$schema['properties']['line_items']['items']['properties']['cost_of_goods_sold'] = array(
			'description' => __( 'Cost of Goods Sold data. Only present for product refund line items.', 'woocommerce' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'total_value' => array(
					'description' => __( 'Value of the Cost of Goods Sold for the refund item.', 'woocommerce' ),
					'type'        => 'number',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $schema;
	}
}
