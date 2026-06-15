<?php
/**
 * DataUtils class file.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Enums\OrderItemType;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\NumberUtil;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WC_Tax;
use WP_Error;
use WP_Http;

/**
 * Helper methods for the REST API.
 *
 * Class DataUtils
 *
 * @package Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds
 */
class DataUtils {
	/**
	 * Order statuses that allow refunds.
	 */
	public const REFUNDABLE_STATUSES = array(
		OrderStatus::COMPLETED,
		OrderStatus::PROCESSING,
		OrderStatus::ON_HOLD,
	);

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
	 * @param array    $line_items The line items to convert.
	 * @param WC_Order $order The order being refunded.
	 * @return array The converted line items.
	 */
	public function convert_line_items_to_internal_format( $line_items, WC_Order $order ) {
		$prepared_line_items = array();

		foreach ( $line_items as $line_item ) {
			if ( ! isset( $line_item['line_item_id'], $line_item['quantity'], $line_item['refund_total'] ) ) {
				continue;
			}

			// If no explicit refund_tax provided, extract tax from refund_total using WC_Tax.
			if ( ! isset( $line_item['refund_tax'] ) ) {
				$original_item = $order->get_item( $line_item['line_item_id'] );
				if ( $original_item ) {
					$original_taxes = $original_item->get_taxes();
					// Keep any non-zero stored tax (positive or negative). Negative-tax
					// discount fees (e.g. a -$10 fee with -$1 stored tax) must retain
					// their tax breakdown so the create side matches the preview side
					// in build_refund_preview() — filtering on `> 0` previously dropped
					// them and emitted refund_total=$line_total / refund_tax=[].
					$tax_totals = array_filter(
						$original_taxes['total'] ?? array(),
						function ( $amount ) {
							return is_numeric( $amount ) && 0.0 !== (float) $amount;
						}
					);
					$tax_ids    = array_keys( $tax_totals );

					if ( ! empty( $tax_ids ) ) {
						$tax_rates = $this->build_tax_rates_array( $order, $tax_ids );

						// Always assume refund_total includes tax - extract it using WC_Tax.
						$calculated_taxes = WC_Tax::calc_inclusive_tax(
							(float) $line_item['refund_total'],
							$tax_rates
						);

						// Round extracted taxes to display precision to match how original taxes were stored.
						// This prevents rounding errors where internal precision (6DP) differs from storage precision (2DP).
						$price_decimals   = wc_get_price_decimals();
						$calculated_taxes = array_map(
							function ( $tax ) use ( $price_decimals ) {
								return NumberUtil::round( $tax, $price_decimals );
							},
							$calculated_taxes
						);

						$line_item['refund_tax'] = $this->convert_proportional_taxes_to_schema_format(
							$calculated_taxes
						);

						// Subtract extracted tax from refund_total to get the amount excluding tax.
						$total_tax                 = array_sum( $calculated_taxes );
						$line_item['refund_total'] = NumberUtil::round( $line_item['refund_total'] - $total_tax, $price_decimals );
					}
				}
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
	 * Convert line item taxes (schema format) to internal format. This keys arrays by tax ID and has some different naming.
	 *
	 * @param array $line_item_taxes The taxes to convert.
	 * @return array The converted taxes.
	 *
	 * @since 10.9.0
	 */
	protected function convert_line_item_taxes_to_internal_format( $line_item_taxes ) {
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

		return (float) NumberUtil::round( $amount, wc_get_price_decimals() );
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

			// Validate refund total is not greater than the item total (including tax).
			$item_total_with_tax = $item->get_total() + $item->get_total_tax();
			if ( $item_total_with_tax < $line_item['refund_total'] ) {
				return new WP_Error(
					'invalid_refund_amount',
					sprintf(
						/* translators: %s: item total with tax */
						__( 'Refund total cannot be greater than the line item total including tax (%s).', 'woocommerce' ),
						$item_total_with_tax
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

	/**
	 * Convert calculated taxes (internal format) to schema format.
	 *
	 * @param array $calculated_taxes Taxes keyed by tax ID with amounts.
	 * @return array Schema format with id and refund_total keys.
	 *
	 * @since 10.9.0
	 */
	protected function convert_proportional_taxes_to_schema_format( array $calculated_taxes ): array {
		$result = array();
		foreach ( $calculated_taxes as $tax_id => $amount ) {
			$result[] = array(
				'id'           => (int) $tax_id,
				'refund_total' => $amount,
			);
		}
		return $result;
	}

	/**
	 * Build tax rate array from order tax items for use with WC_Tax calculations.
	 *
	 * @param WC_Order $order The order.
	 * @param array    $tax_ids Array of tax rate IDs that apply to an item.
	 * @return array Tax rates array formatted for WC_Tax::calc_*_tax() methods.
	 *
	 * @since 10.9.0
	 */
	protected function build_tax_rates_array( WC_Order $order, array $tax_ids ): array {
		$tax_rates = array();
		$tax_items = $order->get_items( OrderItemType::TAX );

		foreach ( $tax_ids as $tax_id ) {
			foreach ( $tax_items as $tax_item ) {
				if ( $tax_item->get_rate_id() === (int) $tax_id ) {
					$tax_rates[ $tax_id ] = array(
						'rate'     => $tax_item->get_rate_percent(),
						'label'    => $tax_item->get_label(),
						'compound' => $tax_item->is_compound() ? 'yes' : 'no',
					);
					break;
				}
			}
		}

		return $tax_rates;
	}

	/**
	 * Compute the tax-inclusive refund total for a line item at a given quantity.
	 *
	 * Precondition: $item must be one of WC_Order_Item_Product, WC_Order_Item_Shipping,
	 * WC_Order_Item_Fee, and $quantity must be a positive integer (>= 1). For
	 * shipping and fee items the quantity is informational only — the full item
	 * total is returned regardless. Callers using untrusted input should validate
	 * via {@see validate_preview_line_items()} first.
	 *
	 * @param WC_Order_Item_Product|WC_Order_Item_Shipping|WC_Order_Item_Fee $item     The order item.
	 * @param int                                                            $quantity The quantity to refund (>= 1).
	 * @return float The tax-inclusive refund total. May be negative for items with negative totals (e.g. discount fees).
	 * @throws \InvalidArgumentException When $quantity is less than 1.
	 *
	 * @since 10.9.0
	 */
	public function compute_line_item_refund_total( $item, int $quantity ): float {
		if ( $quantity < 1 ) {
			// Exception message is developer-facing only; the value is a typed int and the format is a literal string.
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \InvalidArgumentException( sprintf( 'Quantity must be >= 1, got %d.', (int) $quantity ) );
		}

		$price_decimals = wc_get_price_decimals();

		if ( $item instanceof WC_Order_Item_Product ) {
			$original_qty = $item->get_quantity();
			if ( 0 === $original_qty ) {
				wc_get_logger()->warning(
					sprintf( 'Refund preview: product item %d has zero original quantity on order %d.', $item->get_id(), $item->get_order_id() ),
					array( 'source' => 'wc-v4-refunds' )
				);
				return 0.0;
			}
			$unit_price_with_tax = ( (float) $item->get_total() + (float) $item->get_total_tax() ) / $original_qty;
			return NumberUtil::round( $unit_price_with_tax * $quantity, $price_decimals );
		}

		return NumberUtil::round( (float) $item->get_total() + (float) $item->get_total_tax(), $price_decimals );
	}

	/**
	 * Build a refund preview showing authoritative totals and breakdowns.
	 *
	 * Callers must invoke {@see validate_preview_line_items()} first — this
	 * method assumes inputs have been validated and throws on missing items.
	 *
	 * @param WC_Order $order      The order being previewed for refund.
	 * @param array    $line_items Array of line items with 'line_item_id' and 'quantity' keys.
	 * @return array The structured preview response.
	 * @throws \InvalidArgumentException When a line_item_id does not resolve to an item on the order.
	 *
	 * @since 10.9.0
	 */
	public function build_refund_preview( WC_Order $order, array $line_items ): array {
		$price_decimals = wc_get_price_decimals();
		$sections       = array(
			'products' => array(
				'items'    => array(),
				'subtotal' => 0.0,
				'tax'      => 0.0,
				'total'    => 0.0,
			),
			'shipping' => array(
				'items'    => array(),
				'subtotal' => 0.0,
				'tax'      => 0.0,
				'total'    => 0.0,
			),
			'fees'     => array(
				'items'    => array(),
				'subtotal' => 0.0,
				'tax'      => 0.0,
				'total'    => 0.0,
			),
		);

		foreach ( $line_items as $line_item ) {
			$item = $order->get_item( $line_item['line_item_id'] );
			if ( ! $item ) {
				// Exception message is developer-facing only; both values are typed ints and the format is a literal string.
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new \InvalidArgumentException( sprintf( 'Line item %d not found on order %d.', (int) $line_item['line_item_id'], (int) $order->get_id() ) );
			}

			/**
			 * Validated by validate_preview_line_items() upstream.
			 *
			 * @var WC_Order_Item_Product|WC_Order_Item_Shipping|WC_Order_Item_Fee $item
			 */
			$refund_total_with_tax = $this->compute_line_item_refund_total( $item, $line_item['quantity'] );
			$subtotal              = $refund_total_with_tax;
			$tax                   = 0.0;

			$original_taxes = $item->get_taxes();
			// Keep any non-zero stored tax (positive or negative). Negative-tax
			// discount fees (e.g. a -$10 fee with -$1 stored tax) must retain
			// their tax breakdown — filtering on `> 0` previously dropped them
			// and emitted subtotal=$line_total / tax=0 instead of the correct
			// signed split.
			$tax_totals = array_filter(
				$original_taxes['total'] ?? array(),
				function ( $amount ) {
					return is_numeric( $amount ) && 0.0 !== (float) $amount;
				}
			);

			if ( ! empty( $original_taxes['total'] ?? array() ) && empty( $tax_totals ) ) {
				wc_get_logger()->warning(
					sprintf(
						'Refund preview: tax totals filtered to empty for item %d on order %d (non-numeric or zero values).',
						(int) $line_item['line_item_id'],
						$order->get_id()
					),
					array( 'source' => 'wc-v4-refunds' )
				);
			}

			if ( ! empty( $tax_totals ) ) {
				$tax_rates        = $this->build_tax_rates_array( $order, array_keys( $tax_totals ) );
				$calculated_taxes = WC_Tax::calc_inclusive_tax( $refund_total_with_tax, $tax_rates );
				$calculated_taxes = array_map(
					function ( $t ) use ( $price_decimals ) {
						return NumberUtil::round( $t, $price_decimals );
					},
					$calculated_taxes
				);
				$tax              = NumberUtil::round( array_sum( $calculated_taxes ), $price_decimals );
				$subtotal         = NumberUtil::round( $refund_total_with_tax - $tax, $price_decimals );
			}

			$item_data = array(
				'id'       => $line_item['line_item_id'],
				'quantity' => $line_item['quantity'],
				'subtotal' => wc_format_decimal( $subtotal, $price_decimals ),
				'tax'      => wc_format_decimal( $tax, $price_decimals ),
				'total'    => wc_format_decimal( $refund_total_with_tax, $price_decimals ),
			);

			$item_data['name'] = $item->get_name();

			if ( $item instanceof WC_Order_Item_Product ) {
				$variation_id            = $item->get_variation_id();
				$item_data['product_id'] = $variation_id > 0 ? $variation_id : $item->get_product_id();
				$section_key             = 'products';
			} elseif ( $item instanceof WC_Order_Item_Shipping ) {
				$section_key = 'shipping';
			} else {
				$section_key = 'fees';
			}

			$sections[ $section_key ]['items'][]   = $item_data;
			$sections[ $section_key ]['subtotal'] += $subtotal;
			$sections[ $section_key ]['tax']      += $tax;
			$sections[ $section_key ]['total']    += $refund_total_with_tax;
		}

		$format_section = function ( array $section ) use ( $price_decimals ): array {
			return array(
				'items'    => $section['items'],
				'subtotal' => wc_format_decimal( $section['subtotal'], $price_decimals ),
				'tax'      => wc_format_decimal( $section['tax'], $price_decimals ),
				'total'    => wc_format_decimal( $section['total'], $price_decimals ),
			);
		};

		$grand_subtotal = $sections['products']['subtotal'] + $sections['shipping']['subtotal'] + $sections['fees']['subtotal'];
		$grand_tax      = $sections['products']['tax'] + $sections['shipping']['tax'] + $sections['fees']['tax'];
		$grand_total    = $sections['products']['total'] + $sections['shipping']['total'] + $sections['fees']['total'];

		return array(
			'breakdown'      => array(
				'products' => $format_section( $sections['products'] ),
				'shipping' => $format_section( $sections['shipping'] ),
				'fees'     => $format_section( $sections['fees'] ),
			),
			'subtotal'       => wc_format_decimal( $grand_subtotal, $price_decimals ),
			'tax'            => wc_format_decimal( $grand_tax, $price_decimals ),
			'total'          => wc_format_decimal( $grand_total, $price_decimals ),
			'max_refundable' => wc_format_decimal( $order->get_remaining_refund_amount(), $price_decimals ),
		);
	}

	/**
	 * Validate line items for a preview request.
	 *
	 * @param array    $line_items The line items to validate.
	 * @param WC_Order $order      The order object.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 *
	 * @since 10.9.0
	 */
	public function validate_preview_line_items( array $line_items, WC_Order $order ) {
		if ( empty( $line_items ) ) {
			return new WP_Error(
				'missing_line_items',
				__( 'At least one line item is required.', 'woocommerce' ),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}

		if ( ! in_array( $order->get_status(), self::REFUNDABLE_STATUSES, true ) ) {
			return new WP_Error(
				'order_not_refundable',
				__( 'This order cannot be refunded.', 'woocommerce' ),
				array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
			);
		}

		if ( (float) $order->get_remaining_refund_amount() <= 0 ) {
			return new WP_Error(
				'order_not_refundable',
				__( 'This order has already been fully refunded.', 'woocommerce' ),
				array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
			);
		}

		$refund_data = $this->compute_refunded_quantities_and_totals( $order );

		foreach ( $line_items as $line_item ) {
			$line_item_id = $line_item['line_item_id'] ?? null;
			if ( ! $line_item_id ) {
				return new WP_Error(
					'missing_line_item_id',
					__( 'Line item ID is required.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}

			$item = $order->get_item( $line_item_id );
			if ( ! $item || $item->get_order_id() !== $order->get_id() ) {
				return new WP_Error(
					'line_item_not_found',
					__( 'Line item not found.', 'woocommerce' ),
					array( 'status' => WP_Http::NOT_FOUND )
				);
			}

			if ( ! $item instanceof WC_Order_Item_Product && ! $item instanceof WC_Order_Item_Fee && ! $item instanceof WC_Order_Item_Shipping ) {
				return new WP_Error(
					'unsupported_item_type',
					__( 'Line item is not a product, fee, or shipping line.', 'woocommerce' ),
					array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
				);
			}

			if ( ! isset( $line_item['quantity'] ) || ! is_int( $line_item['quantity'] ) || $line_item['quantity'] < 1 ) {
				return new WP_Error(
					'invalid_quantity',
					__( 'Quantity must be a positive integer.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}
			$quantity = $line_item['quantity'];

			if ( $item instanceof WC_Order_Item_Product ) {
				$remaining_qty = $item->get_quantity() + ( $refund_data['qtys'][ $line_item_id ] ?? 0 );
				if ( $quantity > $remaining_qty ) {
					return new WP_Error(
						'quantity_exceeds_refundable',
						sprintf(
							/* translators: %d: remaining refundable quantity */
							__( 'Requested quantity exceeds remaining refundable quantity (%d).', 'woocommerce' ),
							$remaining_qty
						),
						array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
					);
				}
			}

			if ( $item instanceof WC_Order_Item_Shipping || $item instanceof WC_Order_Item_Fee ) {
				if ( 1 !== $quantity ) {
					return new WP_Error(
						'invalid_quantity',
						__( 'Shipping and fee line items must be refunded with quantity of 1.', 'woocommerce' ),
						array( 'status' => WP_Http::BAD_REQUEST )
					);
				}

				// Compare on a tax-inclusive basis: compute_line_item_refund_total() (and
				// therefore $requested_total below) already includes tax, and
				// compute_refunded_quantities_and_totals() also returns tax-inclusive
				// fee/shipping totals.
				$refunded_total  = abs( (float) ( $refund_data['totals'][ $line_item_id ] ?? 0.0 ) );
				$remaining_total = abs( (float) $item->get_total() + (float) $item->get_total_tax() ) - $refunded_total;
				if ( $remaining_total <= 0 ) {
					return new WP_Error(
						'quantity_exceeds_refundable',
						__( 'This line item has already been fully refunded.', 'woocommerce' ),
						array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
					);
				}

				// Cap against the line's remaining refundable amount. The preview
				// shape only takes quantity, so a fee/shipping line that's been
				// partially refunded cannot be previewed again at the full original
				// total — the request would over-refund and the eventual create
				// call would fail. Reject up-front with a clear error.
				$requested_total = abs( $this->compute_line_item_refund_total( $item, $quantity ) );
				if ( $requested_total > NumberUtil::round( $remaining_total, wc_get_price_decimals() ) ) {
					return new WP_Error(
						'quantity_exceeds_refundable',
						sprintf(
							/* translators: %s: remaining refundable amount */
							__( 'Requested refund exceeds the remaining refundable amount for this line item (%s).', 'woocommerce' ),
							wc_format_decimal( $remaining_total, wc_get_price_decimals() )
						),
						array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
					);
				}
			}
		}

		return true;
	}

	/**
	 * Pre-compute refund data for all line items in an order.
	 *
	 * Loads refunds once and builds lookup maps for refunded quantities and totals per item ID,
	 * avoiding repeated get_refunds() calls during serialization. Fee and shipping totals are
	 * tax-inclusive so they can be compared directly against {@see compute_line_item_refund_total()}.
	 *
	 * @param WC_Order $order Order instance.
	 * @return array{qtys: array<int, int>, totals: array<int, float>}
	 */
	public function compute_refunded_quantities_and_totals( WC_Order $order ): array {
		$qtys   = array();
		$totals = array();

		foreach ( $order->get_refunds() as $refund ) {
			/**
			 * Refunded product line items.
			 *
			 * @var \WC_Order_Item_Product[] $refunded_line_items
			 */
			$refunded_line_items = $refund->get_items( 'line_item' );
			foreach ( $refunded_line_items as $refunded_item ) {
				$original_id          = absint( $refunded_item->get_meta( '_refunded_item_id' ) );
				$qtys[ $original_id ] = ( $qtys[ $original_id ] ?? 0 ) + $refunded_item->get_quantity();
			}
			/**
			 * Refunded fee items.
			 *
			 * @var \WC_Order_Item_Fee[] $refunded_fees
			 */
			$refunded_fees = $refund->get_items( 'fee' );
			foreach ( $refunded_fees as $refunded_item ) {
				$original_id            = absint( $refunded_item->get_meta( '_refunded_item_id' ) );
				$totals[ $original_id ] = ( $totals[ $original_id ] ?? 0.0 ) + ( (float) $refunded_item->get_total() + (float) $refunded_item->get_total_tax() ) * -1;
			}
			/**
			 * Refunded shipping items.
			 *
			 * @var \WC_Order_Item_Shipping[] $refunded_shipping
			 */
			$refunded_shipping = $refund->get_items( 'shipping' );
			foreach ( $refunded_shipping as $refunded_item ) {
				$original_id            = absint( $refunded_item->get_meta( '_refunded_item_id' ) );
				$totals[ $original_id ] = ( $totals[ $original_id ] ?? 0.0 ) + ( (float) $refunded_item->get_total() + (float) $refunded_item->get_total_tax() ) * -1;
			}
		}

		return array(
			'qtys'   => $qtys,
			'totals' => $totals,
		);
	}
}
