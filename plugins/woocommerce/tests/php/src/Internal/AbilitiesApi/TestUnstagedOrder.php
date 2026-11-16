<?php
/**
 * TestUnstagedOrder class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

/**
 * An extension's order class that overrides update_status() and not stage_status().
 */
class TestUnstagedOrder extends \WC_Order {

	/**
	 * Mark the order, then change the status.
	 *
	 * @param string $new_status    Status to change the order to.
	 * @param string $note          Optional note.
	 * @param bool   $manual_update Whether a person made the change.
	 * @return bool
	 */
	public function update_status( $new_status, $note = '', $manual_update = false ) {
		$this->update_meta_data( '_test_parent_rule', 'yes' );
		return parent::update_status( $new_status, $note, $manual_update );
	}
}
