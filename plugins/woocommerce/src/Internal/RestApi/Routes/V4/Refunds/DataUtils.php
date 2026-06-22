<?php
/**
 * DataUtils class file.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\NumberUtil;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
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
			// A line item is processable when it has an ID and at least one of
			// quantity or refund_total. The legacy v3-style form may omit
			// quantity entirely; in that case qty=0 is recorded on the refund,
			// matching v3 semantics ("refunded $X of this line without consuming
			// specific units"). Dollar accounting via get_remaining_refund_amount
			// still bounds subsequent refunds, so per-unit looseness here does
			// not enable over-refunding.
			if ( ! isset( $line_item['line_item_id'] ) ) {
				continue;
			}
			if ( ! isset( $line_item['quantity'] ) && ! isset( $line_item['refund_total'] ) ) {
				continue;
			}

			// refund_tax presence is the discriminator for how refund_total is interpreted:
			// when refund_tax is absent, refund_total is tax-inclusive and the tax portion is
			// split out below; when refund_tax is present, refund_total is the tax-exclusive
			// subtotal and is stored as-is, with the supplied taxes added on top.
			//
			// If no explicit refund_tax provided, extract tax from the tax-inclusive
			// refund_total. Skip when refund_total is also missing — there's nothing
			// to extract tax from. The split is by the line's own stored total/tax
			// ratio via split_inclusive_by_stored_ratio(), the same method the preview
			// uses, so the stored refund matches what build_refund_preview() showed.
			if ( ! isset( $line_item['refund_tax'] ) && isset( $line_item['refund_total'] ) ) {
				$original_item = $order->get_item( $line_item['line_item_id'] );
				if ( $original_item instanceof WC_Order_Item_Product || $original_item instanceof WC_Order_Item_Shipping || $original_item instanceof WC_Order_Item_Fee ) {
					$split = $this->split_inclusive_by_stored_ratio( (float) $line_item['refund_total'], $original_item, wc_get_price_decimals() );

					// Leave a tax-free line untouched: refund_total stays the full
					// (tax-exclusive == tax-inclusive) amount and no refund_tax is set.
					if ( ! empty( $split['taxes'] ) ) {
						$line_item['refund_tax']   = $this->convert_proportional_taxes_to_schema_format( $split['taxes'] );
						$line_item['refund_total'] = $split['subtotal'];
					}
				}
			}

			// Default qty=0 when quantity was omitted (legacy v3-style explicit
			// refund_total path). Default refund_total=0 defensively; in practice
			// validate_line_items ensures one of them is set by this point.
			$prepared_line_items[ $line_item['line_item_id'] ] = array(
				'qty'          => $line_item['quantity'] ?? 0,
				'refund_total' => $line_item['refund_total'] ?? 0,
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
	 * Calculate the gross refund amount from line items (schema format).
	 *
	 * Sums refund_total plus any explicit refund_tax. This yields the tax-inclusive gross
	 * for both forms: when refund_tax is omitted, refund_total is already tax-inclusive (and
	 * there is no refund_tax to add); when refund_tax is supplied, refund_total is the
	 * tax-exclusive subtotal and the taxes are added on top.
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
			// is_numeric() (not !empty) — an explicit refund_total of 0 can be part
			// of a valid tax-only refund and must be included in the gross sum.
			if ( isset( $line_item['refund_total'] ) && is_numeric( $line_item['refund_total'] ) ) {
				$amount += $line_item['refund_total'];
			}

			if ( ! empty( $line_item['refund_tax'] ) && is_array( $line_item['refund_tax'] ) ) {
				foreach ( $line_item['refund_tax'] as $tax ) {
					if ( isset( $tax['refund_total'] ) && is_numeric( $tax['refund_total'] ) ) {
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
		// Reject non-refundable order statuses up front, mirroring the preview path
		// so create and preview agree on which orders accept refunds.
		if ( ! in_array( $order->get_status(), self::REFUNDABLE_STATUSES, true ) ) {
			return new WP_Error(
				'order_not_refundable',
				__( 'This order cannot be refunded.', 'woocommerce' ),
				array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
			);
		}

		// Reject a fully-refunded order up front with the same code/status the
		// preview path returns, so a fully-refunded order is rejected identically
		// by both endpoints rather than via the controller's later
		// refund_exceeds_remaining guard.
		if ( (float) $order->get_remaining_refund_amount() <= 0 ) {
			return new WP_Error(
				'order_not_refundable',
				__( 'This order has already been fully refunded.', 'woocommerce' ),
				array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
			);
		}

		// Precompute refunded quantities/totals once so the over-refund check
		// below caps against remaining refundable quantity, not the original.
		$refund_data = $this->compute_refunded_quantities_and_totals( $order );

		$seen_ids = array();
		foreach ( $line_items as $line_item ) {
			$line_item_id = $line_item['line_item_id'] ?? null;

			if ( ! $line_item_id ) {
				return new WP_Error(
					'missing_line_item_id',
					__( 'Line item ID is required.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}

			// Reject duplicate line items: each is validated against the same remaining
			// snapshot, so repeating an ID would let the per-line cap pass twice for the
			// same line. Callers must combine a line into a single entry.
			if ( isset( $seen_ids[ $line_item_id ] ) ) {
				return new WP_Error(
					'duplicate_line_item',
					__( 'Each line item may appear only once per request.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}
			$seen_ids[ $line_item_id ] = true;

			$item = $order->get_item( $line_item_id );

			// Validate item exists and belongs to the order.
			if ( ! $item || $item->get_order_id() !== $order->get_id() ) {
				return new WP_Error(
					'line_item_not_found',
					__( 'Line item not found.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}

			if ( ! $item instanceof \WC_Order_Item_Product && ! $item instanceof \WC_Order_Item_Fee && ! $item instanceof \WC_Order_Item_Shipping ) {
				return new WP_Error(
					'unsupported_item_type',
					__( 'Line item is not a product, fee, or shipping line.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}

			// Quantity is required only when the client omits refund_total — the
			// auto-compute path needs a real quantity to derive the unit price.
			// When refund_total is provided explicitly (legacy v3-style path),
			// quantity is informational and can be missing/zero, matching the
			// original v4 schema's `default: 0` behavior.
			$refund_total_missing = ! array_key_exists( 'refund_total', $line_item ) || null === $line_item['refund_total'];

			// Reject the ambiguous "auto-computed refund_total + explicit refund_tax"
			// combination. Auto-compute writes a tax-inclusive value; the
			// converter then skips tax extraction because refund_tax is set,
			// and calculate_refund_amount double-counts the tax. The client
			// must either supply refund_total explicitly (and may then supply
			// refund_tax to override the auto-extracted split) or let the
			// server handle taxes (omit both).
			if ( $refund_total_missing && isset( $line_item['refund_tax'] ) ) {
				return new WP_Error(
					'invalid_line_item',
					__( 'refund_tax cannot be combined with an auto-computed refund_total. Provide refund_total explicitly when supplying refund_tax.', 'woocommerce' )
				);
			}

			if ( $refund_total_missing && ( ! isset( $line_item['quantity'] ) || ! is_int( $line_item['quantity'] ) || $line_item['quantity'] < 1 ) ) {
				return new WP_Error(
					'missing_quantity_or_refund_total',
					__( 'Line item quantity must be a positive integer when refund_total is omitted.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}

			// When refund_total is provided, a supplied quantity is informational, but it must
			// still be a non-negative integer so it round-trips cleanly onto the refund line —
			// a negative or fractional value would be stored verbatim as the line qty. 0 (or an
			// omitted quantity) means "dollars only". This mirrors the integer/range checks the
			// preview path applies before branching on item type.
			if ( ! $refund_total_missing && isset( $line_item['quantity'] ) && ( ! is_int( $line_item['quantity'] ) || $line_item['quantity'] < 0 ) ) {
				return new WP_Error(
					'invalid_quantity',
					__( 'Line item quantity must be a non-negative integer.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}

			// Auto-compute requires a non-zero source quantity to derive the unit
			// price from. If the client omitted refund_total (or sent null) and the
			// source product has zero quantity, surface a clear error rather than
			// letting the request slip into the misleading "must be greater than
			// zero" branch downstream.
			if ( $refund_total_missing && $item instanceof \WC_Order_Item_Product && 0 === $item->get_quantity() ) {
				return new WP_Error(
					'invalid_line_item',
					sprintf(
						/* translators: %d: line item id */
						__( 'Cannot auto-compute refund for line item %d: source quantity is zero. Provide an explicit refund_total.', 'woocommerce' ),
						(int) $line_item_id
					)
				);
			}

			// Validate refund quantity does not exceed remaining refundable
			// quantity for this line. compute_refunded_quantities_and_totals
			// returns negative values for already-refunded units (matches the
			// convention used by validate_preview_line_items), so adding to
			// $item->get_quantity() yields the remaining count.
			// Only fires when a quantity was provided — the legacy
			// explicit-refund_total path may omit it.
			if ( isset( $line_item['quantity'] ) && $item instanceof \WC_Order_Item_Product ) {
				$remaining_qty = $item->get_quantity() + ( $refund_data['qtys'][ $line_item_id ] ?? 0 );
				if ( $line_item['quantity'] > $remaining_qty ) {
					return new WP_Error(
						'quantity_exceeds_refundable',
						sprintf(
							/* translators: %d: remaining refundable quantity */
							__( 'Line item quantity cannot be greater than the remaining refundable quantity (%d).', 'woocommerce' ),
							$remaining_qty
						),
						array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
					);
				}
			} elseif ( isset( $line_item['quantity'] ) && $line_item['quantity'] > 1 ) {
				return new WP_Error(
					'invalid_quantity',
					__( 'Shipping and fee line items must be refunded with quantity of 1.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}

			// Validate refund total against the remaining refundable amount for this
			// line (including tax), subtracting any prior partial refunds. Rounds both
			// sides to currency precision and uses abs() so the cap matches
			// validate_preview_line_items() exactly — a previewed amount that is
			// accepted (or rejected) there behaves the same way here.
			if ( isset( $line_item['refund_total'] ) ) {
				$price_decimals    = wc_get_price_decimals();
				$signed_line_total = (float) $item->get_total() + (float) $item->get_total_tax();

				// Reject a refund_total whose sign is opposite the line: you cannot refund
				// a positive amount from a discount line, or a negative amount from a normal
				// line. Without this, abs() in the cap below would let a wrong-sign value
				// pass and be stored (e.g. a negative refund_total on a positive line in a
				// mixed-line request whose total stays positive). A gross line refund that
				// rounds to 0 is rejected below, so create and preview stay aligned for the
				// tax-inclusive form while explicit tax-only create requests remain valid.
				if ( (float) $line_item['refund_total'] * $signed_line_total < 0 ) {
					return new WP_Error(
						'invalid_refund_total',
						__( 'Refund total has the wrong sign for this line item.', 'woocommerce' ),
						array( 'status' => WP_Http::BAD_REQUEST )
					);
				}

				// Cap and zero-check the GROSS line refund against the line's tax-inclusive
				// total. When an explicit refund_tax breakdown is supplied, refund_total is
				// the tax-exclusive (net) subtotal and the tax is added on top (core Woo
				// semantics — see RefundSchema); without it, refund_total is already
				// tax-inclusive, so the gross equals refund_total. Capping the net alone
				// would let a client push the overage into refund_tax and over-refund the
				// line. Preview has no refund_tax field, so its (refund_total-only) cap stays
				// equivalent for the inclusive form.
				$line_refund_gross = (float) $line_item['refund_total'];
				if ( ! empty( $line_item['refund_tax'] ) && is_array( $line_item['refund_tax'] ) ) {
					foreach ( $line_item['refund_tax'] as $tax ) {
						$line_refund_gross += (float) ( $tax['refund_total'] ?? 0 );
					}
				}

				// Reject a gross line refund that rounds to zero. A zero line refund is a
				// no-op that would otherwise be stored as an empty qty:0 refund line.
				if ( 0.0 === (float) NumberUtil::round( $line_refund_gross, $price_decimals ) ) {
					return new WP_Error(
						'invalid_refund_total',
						__( 'refund_total must be a number greater than zero.', 'woocommerce' ),
						array( 'status' => WP_Http::BAD_REQUEST )
					);
				}

				$item_total_with_tax = abs( $signed_line_total );
				$abs_refund_total    = abs( $line_refund_gross );

				// Mirror the preview path's three distinct over-refund errors (same
				// codes, messages, and 422 status) so create and preview reject the
				// same input identically. An over-refund is a well-formed but
				// unprocessable request, so 422 — not 400 — is the correct status,
				// matching the order-level cap the controller already returns.
				if ( $abs_refund_total > NumberUtil::round( $item_total_with_tax, $price_decimals ) ) {
					return new WP_Error(
						'refund_total_exceeds_line',
						sprintf(
							/* translators: %s: line item total including tax */
							__( 'refund_total cannot exceed the line item total including tax (%s).', 'woocommerce' ),
							wc_format_decimal( $item_total_with_tax, $price_decimals )
						),
						array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
					);
				}

				$refunded_total  = abs( (float) ( $refund_data['totals'][ $line_item_id ] ?? 0.0 ) );
				$remaining_total = $item_total_with_tax - $refunded_total;
				if ( $remaining_total <= 0 ) {
					return new WP_Error(
						'line_item_already_refunded',
						__( 'This line item has already been fully refunded.', 'woocommerce' ),
						array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
					);
				}
				if ( $abs_refund_total > NumberUtil::round( $remaining_total, $price_decimals ) ) {
					return new WP_Error(
						'refund_total_exceeds_remaining',
						sprintf(
							/* translators: %s: remaining refundable amount */
							__( 'refund_total cannot exceed the remaining refundable amount for this line item (%s).', 'woocommerce' ),
							wc_format_decimal( $remaining_total, $price_decimals )
						),
						array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
					);
				}
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

						$price_decimals = wc_get_price_decimals();
						$stored_tax     = (float) $item_taxes['total'][ $tax_id ];
						$requested_tax  = (float) $tax_refund_total;

						// Reject a refund_tax whose sign is opposite the stored tax bucket: you
						// cannot refund a positive tax from a negative (discount) bucket or vice
						// versa. Mirrors the refund_total wrong-sign guard. Compare on absolute
						// magnitudes below so a negative bucket is capped the same way a positive
						// one is — a signed `<` admits an over-refund of a negative bucket and
						// rejects a valid partial one. An explicit 0 is allowed (a no-op).
						if ( $requested_tax * $stored_tax < 0 ) {
							return new WP_Error(
								'invalid_refund_amount',
								__( 'Refund tax total has the wrong sign for this line item.', 'woocommerce' ),
								array( 'status' => WP_Http::BAD_REQUEST )
							);
						}

						// Cap against the remaining tax for this bucket, subtracting any tax
						// already refunded for this tax id on prior refunds — not the original
						// line tax — so sequential refunds cannot over-refund a single bucket.
						// $already_refunded_tax is accumulated as a positive magnitude
						// (compute_refunded_quantities_and_totals() uses abs()), so compare it
						// against the stored bucket's magnitude. Round both sides to currency
						// precision: the accumulator is built from repeated float additions, so
						// an unrounded compare could reject or admit an exactly-correct amount by
						// a sub-cent residue.
						$already_refunded_tax = (float) ( $refund_data['tax_totals'][ $line_item_id ][ $tax_id ] ?? 0.0 );
						$remaining_tax        = abs( $stored_tax ) - $already_refunded_tax;
						if ( abs( $requested_tax ) > NumberUtil::round( $remaining_tax, $price_decimals ) ) {
							return new WP_Error(
								'invalid_refund_amount',
								sprintf(
								/* translators: %s: remaining refundable tax total */
									__( 'Refund tax total cannot be greater than the remaining refundable tax for this line item (%s).', 'woocommerce' ),
									wc_format_decimal( $remaining_tax, $price_decimals )
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
	 * Split a tax-inclusive amount into net subtotal and per-tax-ID tax amounts using
	 * the line item's own stored total/tax ratio.
	 *
	 * Splitting by the line's actual stored proportion — rather than re-deriving tax
	 * from the order tax item's rate percent — returns exactly what was charged. It
	 * stays correct when the stored tax is not an exact rate% of net (manually edited
	 * tax, or a rate that changed after the order) and when a taxed line's rate
	 * resolves to zero. Preview ({@see build_refund_preview()}) and create
	 * ({@see convert_line_items_to_internal_format()}) share this method so a previewed
	 * split always matches the split stored on the created refund.
	 *
	 * Per-ID amounts are rounded and the subtotal is derived as amount - sum(tax), so
	 * the invariant subtotal + total_tax == amount holds exactly at $dp precision.
	 *
	 * @param float                                                          $amount Tax-inclusive amount to split. Rounded to $dp before splitting.
	 * @param WC_Order_Item_Product|WC_Order_Item_Shipping|WC_Order_Item_Fee $item   Order item supplying the stored total/tax ratio.
	 * @param int                                                            $dp     Price decimal places.
	 * @return array{subtotal: float, total_tax: float, taxes: array<int, float>} Net subtotal, summed tax, and per-tax-ID amounts.
	 *
	 * @since 10.9.0
	 */
	protected function split_inclusive_by_stored_ratio( float $amount, $item, int $dp ): array {
		$amount       = NumberUtil::round( $amount, $dp );
		$stored_total = (float) $item->get_total();

		// Keep only non-zero numeric stored taxes (positive or negative). A negative-tax
		// discount fee must retain its breakdown; a zero entry contributes nothing.
		$stored_taxes = array_filter(
			$item->get_taxes()['total'] ?? array(),
			function ( $t ) {
				return is_numeric( $t ) && 0.0 !== (float) $t;
			}
		);

		$stored_tax_total = array_sum( array_map( 'floatval', $stored_taxes ) );
		$stored_with_tax  = $stored_total + $stored_tax_total;

		// Fallback used whenever the stored data can't yield a sane proportional split:
		// treat the whole amount as net (no tax) and log for observability.
		$unsplittable = function ( string $reason ) use ( $amount, $item ) {
			wc_get_logger()->warning(
				sprintf(
					'Refund tax split: cannot split tax for item %d on order %d (%s).',
					(int) $item->get_id(),
					(int) $item->get_order_id(),
					$reason
				),
				array( 'source' => 'wc-v4-refunds' )
			);
			return array(
				'subtotal'  => $amount,
				'total_tax' => 0.0,
				'taxes'     => array(),
			);
		};

		// No tax on the line: the whole amount is net (not an error, no log).
		if ( empty( $stored_taxes ) ) {
			return array(
				'subtotal'  => $amount,
				'total_tax' => 0.0,
				'taxes'     => array(),
			);
		}

		// A zero-value line (stored total nets to zero while a tax was charged) can't be
		// split proportionally — avoid division by zero.
		if ( 0.0 === (float) $stored_with_tax ) {
			return $unsplittable( 'stored total incl. tax is zero' );
		}

		// Scale each stored tax by the share of the line being refunded.
		$taxes = array();
		foreach ( $stored_taxes as $tax_id => $stored_tax ) {
			$taxes[ (int) $tax_id ] = NumberUtil::round( $amount * ( (float) $stored_tax / $stored_with_tax ), $dp );
		}
		$total_tax = NumberUtil::round( array_sum( $taxes ), $dp );

		// Sanity clamp: the tax portion of a tax-inclusive amount can never exceed the
		// amount itself. A larger value means the stored total/tax nearly cancel (e.g. a
		// near-zero inclusive total from manually edited data), which would explode the
		// ratio. Fall back rather than emit a nonsensical negative subtotal.
		if ( abs( $total_tax ) > abs( $amount ) ) {
			return $unsplittable( 'stored total and tax nearly cancel' );
		}

		$subtotal = NumberUtil::round( $amount - $total_tax, $dp );

		return array(
			'subtotal'  => $subtotal,
			'total_tax' => $total_tax,
			'taxes'     => $taxes,
		);
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
	 * Round every caller-supplied refund_total to currency precision.
	 *
	 * Applied at the entry of both the preview and create flows so a value the client
	 * sends is validated, summed, split, and stored at the same precision. A previewed
	 * amount therefore always matches the created refund to the cent. A missing or null
	 * refund_total (the auto-compute form) is left untouched — those are computed later
	 * and already rounded by {@see compute_line_item_refund_total()}.
	 *
	 * @param array $line_items Line items in schema format.
	 * @return array Line items with numeric refund_total values rounded to wc_get_price_decimals().
	 *
	 * @since 10.9.0
	 */
	public function normalize_refund_totals( array $line_items ): array {
		$price_decimals = wc_get_price_decimals();
		foreach ( $line_items as $key => $line_item ) {
			if ( isset( $line_item['refund_total'] ) && is_numeric( $line_item['refund_total'] ) ) {
				$line_items[ $key ]['refund_total'] = NumberUtil::round( (float) $line_item['refund_total'], $price_decimals );
			}
		}
		return $line_items;
	}

	/**
	 * Fill in refund_total for any line item that omits it, computing the value from
	 * the order item's unit price × quantity via compute_line_item_refund_total().
	 *
	 * Items that already have refund_total (including an explicit 0) are left
	 * untouched so validation can decide whether the explicit amount is valid.
	 * Items where refund_total is omitted OR is explicitly null are treated as
	 * "compute it for me". Items that can't be resolved (missing line_item_id,
	 * item not on order, invalid quantity, unsupported item type, product with
	 * zero source quantity) are also left untouched — validate_line_items surfaces
	 * the right error for those cases.
	 *
	 * Auto-computed values are tax-inclusive, matching the convention enforced by
	 * the existing converter (convert_line_items_to_internal_format extracts tax
	 * from a tax-inclusive refund_total).
	 *
	 * @param array    $line_items Line items from the request (schema format).
	 *                             Each item: array{line_item_id?: int, quantity?: int,
	 *                             refund_total?: float|int|null, refund_tax?: array<int, mixed>}.
	 * @param WC_Order $order      The order being refunded.
	 * @return array The line items with refund_total populated where possible (same shape as input).
	 *
	 * @since 10.9.0
	 */
	public function fill_missing_refund_totals( array $line_items, WC_Order $order ): array {
		// Round caller-supplied amounts up front so explicit values are stored at the
		// same precision the preview validated and showed. Computed values below are
		// already rounded by compute_line_item_refund_total().
		$line_items = $this->normalize_refund_totals( $line_items );

		foreach ( $line_items as $key => $line_item ) {
			// Treat a missing key and an explicit `null` value the same — both mean
			// "compute it for me". An explicit `0` is caller-supplied input, so leave
			// it untouched and let validation decide whether the gross line refund is valid.
			if ( array_key_exists( 'refund_total', $line_item ) && null !== $line_item['refund_total'] ) {
				continue;
			}

			// Skip auto-compute when the client also supplied an explicit
			// refund_tax. Auto-compute writes a tax-inclusive refund_total, but
			// the converter then skips tax extraction whenever refund_tax is
			// already present — and calculate_refund_amount would add both,
			// inflating the total by the tax amount. Leave refund_total unset;
			// validate_line_items rejects this ambiguous combination with a
			// clear error.
			if ( isset( $line_item['refund_tax'] ) ) {
				continue;
			}

			$line_item_id = $line_item['line_item_id'] ?? null;
			$quantity     = $line_item['quantity'] ?? null;
			if ( ! $line_item_id || ! is_int( $quantity ) || $quantity < 1 ) {
				continue;
			}

			$item = $order->get_item( $line_item_id );
			if ( ! $item || ! ( $item instanceof WC_Order_Item_Product || $item instanceof WC_Order_Item_Shipping || $item instanceof WC_Order_Item_Fee ) ) {
				continue;
			}

			// A product whose source line has zero quantity has no unit price to
			// derive a refund from. Skip so validate_line_items surfaces a clear
			// 'invalid_line_item' error to the API consumer instead of letting a
			// silent 0.0 propagate into the misleading "must be greater than zero"
			// branch downstream.
			if ( $item instanceof WC_Order_Item_Product && 0 === $item->get_quantity() ) {
				continue;
			}

			$line_items[ $key ]['refund_total'] = $this->compute_line_item_refund_total( $item, $quantity );
		}

		return $line_items;
	}

	/**
	 * Build a refund preview showing authoritative totals and breakdowns.
	 *
	 * Callers must invoke {@see validate_preview_line_items()} first — this
	 * method assumes inputs have been validated and throws on missing items.
	 *
	 * Each line item must have 'line_item_id' and at least one of 'quantity'
	 * (positive int) or 'refund_total' (positive tax-inclusive float). When
	 * 'refund_total' is present and positive it is used directly; otherwise the
	 * total is computed from quantity via {@see compute_line_item_refund_total()}.
	 *
	 * @param WC_Order $order      The order being previewed for refund.
	 * @param array    $line_items Line items. Each: array{line_item_id: int, quantity?: int, refund_total?: float}.
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
			// When the caller provides an explicit refund_total (partial-amount form) use it
			// directly. The quantity-based form computes the tax-inclusive total from unit price.
			// A non-zero check (not > 0) mirrors validate_preview_line_items(), which accepts a
			// negative refund_total for a negative discount line and rejects a present-but-zero
			// one before this method runs — so a signed value is honoured rather than falling
			// through to the (possibly absent) quantity.
			$refund_total_with_tax = isset( $line_item['refund_total'] ) && is_numeric( $line_item['refund_total'] ) && 0.0 !== (float) $line_item['refund_total']
				? NumberUtil::round( (float) $line_item['refund_total'], $price_decimals )
				: $this->compute_line_item_refund_total( $item, (int) $line_item['quantity'] );

			// Split by the line's own stored total/tax ratio so the preview reflects what
			// was actually charged and matches the split create stores (both call this).
			$split    = $this->split_inclusive_by_stored_ratio( $refund_total_with_tax, $item, $price_decimals );
			$subtotal = $split['subtotal'];
			$tax      = $split['total_tax'];

			$item_data = array(
				'id'       => $line_item['line_item_id'],
				'quantity' => $line_item['quantity'] ?? null,
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

		$seen_ids = array();
		foreach ( $line_items as $line_item ) {
			$line_item_id = $line_item['line_item_id'] ?? null;
			if ( ! $line_item_id ) {
				return new WP_Error(
					'missing_line_item_id',
					__( 'Line item ID is required.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}

			// Reject duplicate line items: each is validated against the same remaining
			// snapshot, so repeating an ID would let the per-line cap pass twice for the
			// same line and double-count it in the preview breakdown.
			if ( isset( $seen_ids[ $line_item_id ] ) ) {
				return new WP_Error(
					'duplicate_line_item',
					__( 'Each line item may appear only once per request.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}
			$seen_ids[ $line_item_id ] = true;

			// A bad line_item_id reference (not on the order, or an unsupported type) is a
			// malformed request, returned as 400 to match the create endpoint's handling
			// of the same conditions.
			$item = $order->get_item( $line_item_id );
			if ( ! $item || $item->get_order_id() !== $order->get_id() ) {
				return new WP_Error(
					'line_item_not_found',
					__( 'Line item not found.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}

			if ( ! $item instanceof WC_Order_Item_Product && ! $item instanceof WC_Order_Item_Fee && ! $item instanceof WC_Order_Item_Shipping ) {
				return new WP_Error(
					'unsupported_item_type',
					__( 'Line item is not a product, fee, or shipping line.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}

			// A present refund_total may be negative (a discount/credit line) but must be a
			// non-zero number with the same sign as the line — validated below, mirroring
			// validate_line_items() so create and preview accept and reject the same input.
			// A null refund_total means "use the quantity form" (isset() is false for null).
			$has_refund_total = isset( $line_item['refund_total'] );
			if ( $has_refund_total && ! is_numeric( $line_item['refund_total'] ) ) {
				return new WP_Error(
					'invalid_refund_total',
					__( 'refund_total must be a number greater than zero.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}

			$has_quantity = isset( $line_item['quantity'] ) && is_int( $line_item['quantity'] ) && $line_item['quantity'] >= 1;

			if ( ! $has_quantity && ! $has_refund_total ) {
				return new WP_Error(
					'missing_quantity_or_refund_total',
					__( 'Either a positive integer quantity or a numeric refund_total is required.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}

			$price_decimals    = wc_get_price_decimals();
			$signed_line_total = (float) $item->get_total() + (float) $item->get_total_tax();

			// Validate an explicit refund_total. Mirrors validate_line_items() exactly (sign,
			// zero, and the three over-refund caps) so a previewed amount that is accepted or
			// rejected here behaves identically at create.
			if ( $has_refund_total ) {
				$refund_total = (float) $line_item['refund_total'];

				// Reject a refund_total whose sign is opposite the line: you cannot refund a
				// positive amount from a discount line, or a negative amount from a normal line.
				if ( $refund_total * $signed_line_total < 0 ) {
					return new WP_Error(
						'invalid_refund_total',
						__( 'Refund total has the wrong sign for this line item.', 'woocommerce' ),
						array( 'status' => WP_Http::BAD_REQUEST )
					);
				}

				// Reject a refund_total that rounds to zero — a no-op the create path also rejects.
				if ( 0.0 === (float) NumberUtil::round( $refund_total, $price_decimals ) ) {
					return new WP_Error(
						'invalid_refund_total',
						__( 'refund_total must be a number greater than zero.', 'woocommerce' ),
						array( 'status' => WP_Http::BAD_REQUEST )
					);
				}

				$item_total_with_tax = abs( $signed_line_total );
				$abs_refund_total    = abs( $refund_total );
				if ( $abs_refund_total > NumberUtil::round( $item_total_with_tax, $price_decimals ) ) {
					return new WP_Error(
						'refund_total_exceeds_line',
						sprintf(
							/* translators: %s: line item total including tax */
							__( 'refund_total cannot exceed the line item total including tax (%s).', 'woocommerce' ),
							wc_format_decimal( $item_total_with_tax, $price_decimals )
						),
						array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
					);
				}

				// Cap against the remaining refundable amount for this line.
				// compute_refunded_quantities_and_totals() tracks tax-inclusive totals
				// for all item types so the comparison is consistent.
				$refunded_total  = abs( (float) ( $refund_data['totals'][ $line_item_id ] ?? 0.0 ) );
				$remaining_total = $item_total_with_tax - $refunded_total;
				if ( $remaining_total <= 0 ) {
					return new WP_Error(
						'line_item_already_refunded',
						__( 'This line item has already been fully refunded.', 'woocommerce' ),
						array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
					);
				}
				if ( $abs_refund_total > NumberUtil::round( $remaining_total, $price_decimals ) ) {
					return new WP_Error(
						'refund_total_exceeds_remaining',
						sprintf(
							/* translators: %s: remaining refundable amount */
							__( 'refund_total cannot exceed the remaining refundable amount for this line item (%s).', 'woocommerce' ),
							wc_format_decimal( $remaining_total, $price_decimals )
						),
						array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
					);
				}
			}

			// Validate a supplied quantity whenever present — even alongside refund_total —
			// matching validate_line_items(). Skipping this when refund_total was given let a
			// preview accept a quantity the create path then rejects.
			if ( $has_quantity ) {
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
				} elseif ( 1 !== $quantity ) {
					// Shipping and fee lines carry a single refundable unit.
					return new WP_Error(
						'invalid_quantity',
						__( 'Shipping and fee line items must be refunded with quantity of 1.', 'woocommerce' ),
						array( 'status' => WP_Http::BAD_REQUEST )
					);
				}
			}

			// Amount-from-quantity cap: when the amount is derived from quantity (no explicit
			// refund_total), cap the computed tax-inclusive amount against the remaining line
			// amount for every item type. Mirrors create, which auto-fills refund_total from
			// quantity and then applies the same cap — so a product with prior amount-only
			// refunds (units still uncounted) can no longer preview an over-refund.
			if ( $has_quantity && ! $has_refund_total ) {
				$refunded_total  = abs( (float) ( $refund_data['totals'][ $line_item_id ] ?? 0.0 ) );
				$remaining_total = abs( $signed_line_total ) - $refunded_total;
				if ( $remaining_total <= 0 ) {
					return new WP_Error(
						'line_item_already_refunded',
						__( 'This line item has already been fully refunded.', 'woocommerce' ),
						array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
					);
				}

				$requested_total = abs( $this->compute_line_item_refund_total( $item, $line_item['quantity'] ) );
				if ( $requested_total > NumberUtil::round( $remaining_total, $price_decimals ) ) {
					return new WP_Error(
						'refund_total_exceeds_remaining',
						sprintf(
							/* translators: %s: remaining refundable amount */
							__( 'refund_total cannot exceed the remaining refundable amount for this line item (%s).', 'woocommerce' ),
							wc_format_decimal( $remaining_total, $price_decimals )
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
	 * @return array{qtys: array<int, int>, totals: array<int, float>, tax_totals: array<int, array<int, float>>}
	 */
	public function compute_refunded_quantities_and_totals( WC_Order $order ): array {
		$qtys       = array();
		$totals     = array();
		$tax_totals = array();

		// Accumulate the already-refunded tax per original item, keyed by tax rate
		// id, as a positive amount. Refund line items store taxes as negatives, so
		// flip the sign. Lets the per-tax-id cap subtract prior refunds.
		$add_refunded_taxes = function ( $refunded_item, int $original_id ) use ( &$tax_totals ) {
			$taxes = $refunded_item->get_taxes();
			foreach ( (array) ( $taxes['total'] ?? array() ) as $tax_id => $amount ) {
				$tax_totals[ $original_id ][ $tax_id ] = ( $tax_totals[ $original_id ][ $tax_id ] ?? 0.0 ) + abs( (float) $amount );
			}
		};

		foreach ( $order->get_refunds() as $refund ) {
			/**
			 * Refunded product line items.
			 *
			 * @var \WC_Order_Item_Product[] $refunded_line_items
			 */
			$refunded_line_items = $refund->get_items( 'line_item' );
			foreach ( $refunded_line_items as $refunded_item ) {
				$original_id            = absint( $refunded_item->get_meta( '_refunded_item_id' ) );
				$qtys[ $original_id ]   = ( $qtys[ $original_id ] ?? 0 ) + $refunded_item->get_quantity();
				$totals[ $original_id ] = ( $totals[ $original_id ] ?? 0.0 ) + ( (float) $refunded_item->get_total() + (float) $refunded_item->get_total_tax() ) * -1;
				$add_refunded_taxes( $refunded_item, $original_id );
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
				$add_refunded_taxes( $refunded_item, $original_id );
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
				$add_refunded_taxes( $refunded_item, $original_id );
			}
		}

		return array(
			'qtys'       => $qtys,
			'totals'     => $totals,
			'tax_totals' => $tax_totals,
		);
	}
}
