<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\CashSessions;

defined( 'ABSPATH' ) || exit;

/**
 * Why the cash drawer was opened.
 *
 * Kept under the cash sessions namespace instead of `src/Enums` while the API contract is a draft.
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

	/**
	 * All values.
	 *
	 * @since 11.3.0
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return array(
			self::CASH_SALE,
			self::CASH_REFUND,
			self::NO_SALE,
			self::TEST,
			self::PAID_IN,
			self::PAID_OUT,
			self::COUNT,
			self::UNKNOWN,
		);
	}
}
