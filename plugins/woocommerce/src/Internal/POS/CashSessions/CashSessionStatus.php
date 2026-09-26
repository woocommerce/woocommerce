<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\CashSessions;

defined( 'ABSPATH' ) || exit;

/**
 * Cash session statuses.
 *
 * Kept under the cash sessions namespace instead of `src/Enums` while the API contract is a draft.
 *
 * @since 11.3.0
 */
final class CashSessionStatus {

	/**
	 * The session accepts movements.
	 */
	public const OPEN = 'open';

	/**
	 * The session is counted and immutable.
	 */
	public const CLOSED = 'closed';

	/**
	 * All values.
	 *
	 * @since 11.3.0
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return array(
			self::OPEN,
			self::CLOSED,
		);
	}
}
