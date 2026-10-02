<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Cash session statuses.
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
}
