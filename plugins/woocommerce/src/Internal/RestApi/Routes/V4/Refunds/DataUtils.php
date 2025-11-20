<?php
/**
 * DataUtils class file.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds;

defined( 'ABSPATH' ) || exit;

use WP_Error;
use WC_Order;

/**
 * Helper methods for the REST API.
 *
 * Class DataUtils
 *
 * @package Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds
 */
class DataUtils {
	/**
	 * Convert line items (schema format) to internal format. This keys arrays by item ID and has some different naming
	 * conventions.
	 *
	 * 111 => [
	 *   "qty" => 1,
	 *   "refund_total" => 123,
	 *   "refund_tax" => [
	 *     1 => 123,
	 *     2 => 456,
	 *   ],
	 * ]
	 *
	 * @param array $line_items The line items to convert.
	 * @return array The converted line items.
	 */
	public function convert_line_items_to_internal_format( $line_items ) {
		$prepared_line_items = array();

		foreach ( $line_items as $line_item ) {
			if ( ! isset( $line_item['line_item_id'], $line_item['quantity'], $line_item['refund_total'] ) ) {
				continue;
			}
			$prepared_line_items[ $line_item['line_item_id'] ] = array(
				'qty'          => $line_item['quantity'],
				'refund_total' => $line_item['refund_total'],
				'refund_tax'   => $this->convert_line_item_taxes_to_internal_format( $line_item['refund_tax'] ?? array() ),
			);
		}

		return $prepared_line_items;
	}

	/**
	 * Convert line item taxes (schema format) to internal format. This keys arrays by tax ID and has some different naming
	 *
	 * @param array $line_item_taxes The taxes to convert.
	 * @return array The converted taxes.
	 */
	private function convert_line_item_taxes_to_internal_format( $line_item_taxes ) {
		$prepared_taxes = array();

		foreach ( $line_item_taxes as $line_item_tax ) {
			if ( ! isset( $line_item_tax['id'], $line_item_tax['refund_total'] ) ) {
				continue;
			}
			$prepared_taxes[ $line_item_tax['id'] ] = $line_item_tax['refund_total'];
		}

		return $prepared_taxes;
	}

	/**
	 * Calculate the refund amount from line items.
	 *
	 * @param array $line_items The line items to calculate the refund amount from.
	 * @return float|null The refund amount, or null if it can't be calculated.
	 */
	public function calculate_refund_amount( array $line_items ): ?float {
		if ( empty( $line_items ) ) {
			return null;
		}

		$amount = 0;

		foreach ( $line_items as $line_item ) {
			if ( ! empty( $line_item['refund_total'] ) && is_numeric( $line_item['refund_total'] ) ) {
				$amount += $line_item['refund_total'];
			}

			if ( ! empty( $line_item['refund_tax'] ) && is_array( $line_item['refund_tax'] ) ) {
				foreach ( $line_item['refund_tax'] as $tax ) {
					if ( ! empty( $tax['refund_total'] ) && is_numeric( $tax['refund_total'] ) ) {
						$amount += $tax['refund_total'];
					}
				}
			}
		}

		return $amount;
	}

	/**
	 * Validate line items (schema format) before conversion to internal format.
	 *
	 * @param array    $line_items The line items to validate.
	 * @param WC_Order $order The order object.
	 * @return boolean|WP_Error
	 */
	public function validate_line_items( $line_items, WC_Order $order ) {
		foreach ( $line_items as $line_item ) {
			$line_item_id = $line_item['line_item_id'] ?? null;

			if ( ! $line_item_id ) {
				return new WP_Error( 'invalid_line_item', __( 'Line item ID is required.', 'woocommerce' ) );
			}

			$item = $order->get_item( $line_item_id );

			// Validate item exists and belongs to the order.
			if ( ! $item || $item->get_order_id() !== $order->get_id() ) {
				return new WP_Error( 'invalid_line_item', __( 'Line item not found.', 'woocommerce' ) );
			}

			if ( ! $item instanceof \WC_Order_Item_Product && ! $item instanceof \WC_Order_Item_Fee && ! $item instanceof \WC_Order_Item_Shipping ) {
				return new WP_Error( 'invalid_line_item', __( 'Line item is not a product, fee, or shipping line.', 'woocommerce' ) );
			}

			// Validate item quantity is not greater than the item quantity.
			if ( $item->get_quantity() < $line_item['quantity'] ) {
				/* translators: %s: item quantity */
				return new WP_Error( 'invalid_line_item', sprintf( __( 'Line item quantity cannot be greater than the item quantity (%s).', 'woocommerce' ), $item->get_quantity() ) );
			}

			// Validate refund total is not greater than the item total.
			if ( $item->get_total() < $line_item['refund_total'] ) {
				return new WP_Error(
					'invalid_refund_amount',
					sprintf(
						/* translators: %s: item total */
						__( 'Refund total cannot be greater than the line item total (%s).', 'woocommerce' ),
						$item->get_total()
					)
				);
			}

			if ( isset( $line_item['refund_tax'] ) ) {
				$item_taxes = $item->get_taxes();

				if ( $item_taxes ) {
					$allowed_tax_ids = array_keys( $item_taxes['total'] ?? array() );

					foreach ( $line_item['refund_tax'] as $refund_tax ) {
						if ( ! isset( $refund_tax['id'], $refund_tax['refund_total'] ) ) {
							return new WP_Error( 'invalid_line_item', __( 'Tax id and refund_total are required.', 'woocommerce' ) );
						}
						$tax_id           = $refund_tax['id'];
						$tax_refund_total = $refund_tax['refund_total'];

						if ( ! in_array( $tax_id, $allowed_tax_ids, true ) ) {
							return new WP_Error(
								'invalid_line_item',
								sprintf(
								/* translators: %s: tax IDs */
									__( 'Line item tax not found. Must be: %s.', 'woocommerce' ),
									implode( ', ', $allowed_tax_ids )
								)
							);
						}

						if ( $item_taxes['total'][ $tax_id ] < $tax_refund_total ) {
							return new WP_Error(
								'invalid_refund_amount',
								sprintf(
								/* translators: %s: tax total */
									__( 'Refund tax total cannot be greater than the line item tax total (%s).', 'woocommerce' ),
									$item_taxes['total'][ $tax_id ]
								)
							);
						}
					}
				}
			}
		}

		return true;
	}
}
