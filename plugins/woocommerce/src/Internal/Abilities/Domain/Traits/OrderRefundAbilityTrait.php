<?php
/**
 * Order refund ability trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;

defined( 'ABSPATH' ) || exit;

/**
 * Output of a refund for the order refund abilities. Amounts are positive, though a refund stores its lines as
 * negative copies of the order lines. The class that uses it also uses DomainAbilityHelpers.
 */
trait OrderRefundAbilityTrait {

	/**
	 * Get the output schema of a refund.
	 *
	 * @return array
	 */
	protected static function get_refund_output_schema(): array {
		$schema = array(
			'type'                 => 'object',
			'properties'           => array(
				'id'               => array(
					'type'        => 'integer',
					'description' => __( 'Refund ID. Omitted in a dry run.', 'woocommerce' ),
				),
				'order_id'         => array( 'type' => 'integer' ),
				'amount'           => array( 'type' => 'string' ),
				'reason'           => array(
					'type'        => 'string',
					'description' => __( 'Reason for the refund. Omitted when none was given.', 'woocommerce' ),
				),
				'refunded_by'      => array(
					'type'        => 'integer',
					'description' => __( 'ID of the user who made the refund. Omitted when nobody was logged in.', 'woocommerce' ),
				),
				'refunded_payment' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the payment gateway sent the money back. False for a manual refund.', 'woocommerce' ),
				),
				'date_created'     => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Refund date in the store timezone. Omitted in a dry run.', 'woocommerce' ),
				),
				'line_items'       => array(
					'type'        => 'array',
					'description' => __( 'Order lines that the refund covers. Empty for a refund of an amount only.', 'woocommerce' ),
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'refunded_item_id' => array(
								'type'        => 'integer',
								'description' => __( 'ID of the order line.', 'woocommerce' ),
							),
							'name'             => array( 'type' => 'string' ),
							'quantity'         => array( 'type' => 'number' ),
							'total'            => array(
								'type'        => 'string',
								'description' => __( 'Amount refunded for the line, excluding tax. Negative for a discount or credit line.', 'woocommerce' ),
							),
							'total_tax'        => array( 'type' => 'string' ),
						),
						'additionalProperties' => false,
					),
				),
			),
			'additionalProperties' => false,
		);

		return ExtensibleAbilities::add_fields_schema( $schema, 'order_refund' );
	}

	/**
	 * Format a refund for the ability output.
	 *
	 * @param \WC_Order_Refund $refund Refund, saved or not.
	 * @return array|\WP_Error
	 */
	protected static function format_refund_for_response( \WC_Order_Refund $refund ) {
		$data = array(
			'order_id'         => $refund->get_parent_id(),
			'amount'           => self::format_amount_for_response( $refund->get_amount() ),
			'refunded_payment' => $refund->get_refunded_payment(),
			'line_items'       => array(),
		);

		if ( $refund->get_id() > 0 ) {
			$data['id'] = $refund->get_id();
		}

		if ( '' !== $refund->get_reason() ) {
			$data['reason'] = $refund->get_reason();
		}

		if ( $refund->get_refunded_by() > 0 ) {
			$data['refunded_by'] = $refund->get_refunded_by();
		}

		$date_created = self::format_date_for_response( $refund->get_date_created(), false );
		if ( null !== $date_created ) {
			$data['date_created'] = $date_created;
		}

		foreach ( $refund->get_items( array( 'line_item', 'fee', 'shipping' ) ) as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product && ! $item instanceof \WC_Order_Item_Fee && ! $item instanceof \WC_Order_Item_Shipping ) {
				continue;
			}
			$data['line_items'][] = array(
				'refunded_item_id' => (int) $item->get_meta( '_refunded_item_id' ),
				'name'             => $item->get_name(),
				'quantity'         => abs( (float) $item->get_quantity() ),
				'total'            => self::format_refunded_amount( $item->get_total() ),
				'total_tax'        => self::format_refunded_amount( $item->get_total_tax() ),
			);
		}

		return ExtensibleAbilities::add_fields_to_object( $data, 'order_refund', $refund );
	}

	/**
	 * Refund lines store the refunded amount negated, so a credit line, refunded with a negative amount, stays negative.
	 *
	 * @param string|float $stored Amount as stored on the refund line.
	 * @return string
	 */
	private static function format_refunded_amount( $stored ): string {
		$amount = (float) $stored;

		return self::format_amount_for_response( 0.0 === $amount ? 0.0 : -$amount );
	}
}
