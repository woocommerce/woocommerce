<?php
/**
 * Order refunds query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderRefundAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Lists the refunds of one order, newest first.
 */
class OrderRefundsQuery extends AbstractDomainAbility implements AbilityDefinition {

	use OrderAbilityTrait;
	use OrderRefundAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/order-refunds-query';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Query order refunds', 'woocommerce' ),
			'description'         => __(
				'List the refunds of an order, newest first. Use the woocommerce/order-refund-create ability to refund an order.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_view_order' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => true,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * List the refunds of an order, only those in `include` when it is sent.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 11.4.0
	 */
	public static function execute( array $input ) {
		$order = self::get_order_from_input( $input );

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		$refunds = $order->get_refunds();
		if ( isset( $input['include'] ) ) {
			$refunds = array_filter(
				$refunds,
				static fn( \WC_Order_Refund $refund ): bool => in_array( $refund->get_id(), $input['include'], true )
			);
		}

		$items = array();
		foreach ( $refunds as $refund ) {
			$item = self::format_refund_for_response( $refund );
			if ( is_wp_error( $item ) ) {
				return $item;
			}
			$items[] = $item;
		}

		return array(
			'refunds' => $items,
			'total'   => count( $items ),
		);
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'      => array(
					'type'        => 'integer',
					'description' => __( 'Order ID.', 'woocommerce' ),
					'minimum'     => 1,
				),
				'include' => array(
					'type'        => 'array',
					'description' => __( 'Limit the result to these refund IDs of the order.', 'woocommerce' ),
					'items'       => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
					'minItems'    => 1,
					'maxItems'    => 200,
				),
			),
			'required'             => array( 'id' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the ability output schema.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'refunds' => array(
					'type'  => 'array',
					'items' => self::get_refund_output_schema(),
				),
				'total'   => array(
					'type'        => 'integer',
					'description' => __( 'Number of refunds returned.', 'woocommerce' ),
				),
			),
			'additionalProperties' => false,
		);
	}
}
