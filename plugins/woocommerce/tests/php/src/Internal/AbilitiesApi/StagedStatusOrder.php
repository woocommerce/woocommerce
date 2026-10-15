<?php
/**
 * StagedStatusOrder test double.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Abilities\SupportsStagedChanges;

/**
 * An order class that has adapted: it declares SupportsStagedChanges.
 */
class StagedStatusOrder extends \WC_Order implements SupportsStagedChanges {
	/**
	 * Recorded calls.
	 *
	 * @var array
	 */
	public static $calls = array();

	/**
	 * Record the call: the staged path must not use it.
	 *
	 * @param string $new_status New status.
	 * @param string $note       Note.
	 * @param bool   $manual     Manual change.
	 * @return bool
	 */
	public function update_status( $new_status, $note = '', $manual = false ) {
		self::$calls[] = 'update_status';
		return parent::update_status( $new_status, $note, $manual );
	}

	/**
	 * Record the call and run the parent.
	 *
	 * @param array $changes Changes.
	 * @return void
	 */
	public function stage_changes( array $changes ) {
		self::$calls[] = 'stage_changes:' . $changes['status'];
		parent::stage_changes( $changes );
	}

	/**
	 * Record the call and run the parent.
	 *
	 * @return int
	 */
	public function commit_changes() {
		self::$calls[] = 'commit_changes';
		return parent::commit_changes();
	}
}
