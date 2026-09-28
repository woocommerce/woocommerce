<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Cash movement directions.
 *
 * @since 11.3.0
 */
final class CashMovementDirection {

	/**
	 * Cash added to the drawer.
	 */
	public const IN = 'in';

	/**
	 * Cash removed from the drawer.
	 */
	public const OUT = 'out';
}
