<?php
/**
 * LegacyStatusOrder test double.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

/**
 * An order class that keeps its own rules in update_status(), like an extension that has not adapted.
 */
class LegacyStatusOrder extends \WC_Order {
	/**
	 * Recorded update_status() calls.
	 *
	 * @var array
	 */
	public static $calls = array();

	/**
	 * Record the call and run the parent.
	 *
	 * @param string $new_status New status.
	 * @param string $note       Note.
	 * @param bool   $manual     Manual change.
	 * @return bool
	 */
	public function update_status( $new_status, $note = '', $manual = false ) {
		self::$calls[] = array( $new_status, $note, $manual );
		return parent::update_status( $new_status, $note, $manual );
	}
}
