<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Cash drawer audit event types. None of them changes cash totals.
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
}
