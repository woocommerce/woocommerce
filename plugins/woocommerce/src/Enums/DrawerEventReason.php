<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Why the cash drawer was opened.
 *
 * @since 11.3.0
 */
final class DrawerEventReason {

	/**
	 * A cash sale.
	 */
	public const CASH_SALE = 'cash_sale';

	/**
	 * A cash refund.
	 */
	public const CASH_REFUND = 'cash_refund';

	/**
	 * Opened without a sale.
	 */
	public const NO_SALE = 'no_sale';

	/**
	 * A hardware test.
	 */
	public const TEST = 'test';

	/**
	 * Cash added outside a sale.
	 */
	public const PAID_IN = 'paid_in';

	/**
	 * Cash taken out outside a refund.
	 */
	public const PAID_OUT = 'paid_out';

	/**
	 * Counting cash.
	 */
	public const COUNT = 'count';

	/**
	 * An observation not linked to an app action.
	 */
	public const UNKNOWN = 'unknown';
}
