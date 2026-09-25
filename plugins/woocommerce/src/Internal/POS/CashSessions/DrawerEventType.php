<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\CashSessions;

defined( 'ABSPATH' ) || exit;

/**
 * Cash drawer audit event types. None of them changes cash totals.
 *
 * Kept under the cash sessions namespace instead of `src/Enums` while the API contract is a draft.
 *
 * @since 11.3.0
 */
final class DrawerEventType {

	/**
	 * The app sent an open command; not proof that the drawer opened.
	 */
	public const OPEN_REQUESTED = 'open_requested';

	/**
	 * Hardware feedback confirmed that the drawer opened.
	 */
	public const OPENED = 'opened';

	/**
	 * The open command or the hardware reported a failure.
	 */
	public const OPEN_FAILED = 'open_failed';

	/**
	 * All values.
	 *
	 * @since 11.3.0
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return array(
			self::OPEN_REQUESTED,
			self::OPENED,
			self::OPEN_FAILED,
		);
	}
}
