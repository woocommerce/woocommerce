<?php
/**
 * Tests the admin-note datastore.
 *
 * @package WooCommerce\Admin\Tests\Notes
 */

use \Automattic\WooCommerce\Admin\Notes\Note;

/**
 * Class WC_Tests_Note
 */
class WC_Tests_Note extends WC_Unit_Test_Case {

	/**
	 * Tests nonce data is added to the action.
	 */
	public function test_nonce_added_to_action() {
		// Create a new note containing an action with a nonce.
		$note = new Note();
		$note->set_name( 'nonce-note' );
		$note->add_action( 'learn-more', __( 'Learn More', 'woocommerce-admin' ), 'https://example.com/', 'unactioned', true );
		$note->add_nonce_to_action( 'learn-more', 'foo', 'bar' );

		$actions = $note->get_actions();

		$this->assertSame( $actions[0]->nonce_action, 'foo' );
		$this->assertSame( $actions[0]->nonce_name, 'bar' );
	}

	/**
	 * Tests nonces don't get added to actions by default.
	 */
	public function test_nonce_not_added_to_action_by_default() {
		// Create a new note containing an action with a nonce.
		$note = new Note();
		$note->set_name( 'nonce-note' );
		$note->add_action( 'learn-more', __( 'Learn More', 'woocommerce-admin' ), 'https://example.com/', 'unactioned', true );

		$actions = $note->get_actions();

		$this->assertSame( $actions[0]->nonce_action, null );
		$this->assertSame( $actions[0]->nonce_name, null );
	}

	/**
	 * Tests exception thrown when trying to add a nonce to invalid action.
	 */
	public function test_nonce_exception_when_action_not_found() {
		$this->expectException( '\Exception' );

		$note = new Note();
		$note->set_name( 'nonce-note' );
		$note->add_action( 'learn-more', __( 'Learn More', 'woocommerce-admin' ), 'https://example.com/', 'unactioned', true );

		// Cause an exception by adding the nonce to an invalid action.
		$note->add_nonce_to_action( 'learn-mor', 'foo', 'bar' );
	}
}
