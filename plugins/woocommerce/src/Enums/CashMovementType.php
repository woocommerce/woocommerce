<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Cash movement types.
 *
 * @since 11.3.0
 */
final class CashMovementType {

	/**
	 * Cash in the drawer when the session opened.
	 */
	public const OPENING_FLOAT = 'opening_float';

	/**
	 * Cash received for a paid order.
	 */
	public const CASH_SALE = 'cash_sale';

	/**
	 * Cash returned for an order refund.
	 */
	public const CASH_REFUND = 'cash_refund';

	/**
	 * Cash added to the drawer outside a sale.
	 */
	public const PAID_IN = 'paid_in';

	/**
	 * Cash taken from the drawer outside a refund.
	 */
	public const PAID_OUT = 'paid_out';
}
