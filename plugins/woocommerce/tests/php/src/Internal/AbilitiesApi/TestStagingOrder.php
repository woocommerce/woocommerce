<?php
/**
 * TestStagingOrder class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

/**
 * An extension's order class that refuses on-hold in update_status() and in stage_status().
 */
class TestStagingOrder extends \WC_Order {

	/**
	 * Refuse on-hold, then change the status.
	 *
	 * @param string $new_status    Status to change the order to.
	 * @param string $note          Optional note.
	 * @param bool   $manual_update Whether a person made the change.
	 * @return bool
	 * @throws \WC_Data_Exception When the order refuses the status.
	 */
	public function update_status( $new_status, $note = '', $manual_update = false ) {
		$this->refuse_on_hold( $new_status );
		return parent::update_status( $new_status, $note, $manual_update );
	}

	/**
	 * Refuse on-hold, then stage the status.
	 *
	 * @param string       $new_status    Status to change the order to.
	 * @param string|false $note          Optional note.
	 * @param bool         $manual_update Whether a person made the change.
	 * @return array
	 * @throws \WC_Data_Exception When the order refuses the status.
	 */
	public function stage_status( $new_status, $note = '', $manual_update = false ) {
		$this->refuse_on_hold( $new_status );
		return parent::stage_status( $new_status, $note, $manual_update );
	}

	/**
	 * Throw for on-hold.
	 *
	 * @param string $new_status Status to change the order to.
	 * @throws \WC_Data_Exception When the status is on-hold.
	 */
	private function refuse_on_hold( $new_status ): void {
		if ( 'on-hold' === $new_status ) {
			throw new \WC_Data_Exception( 'test_on_hold_refused', 'Unable to change this order to "on-hold".', 409 );
		}
	}
}
