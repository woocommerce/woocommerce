<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi;

use Automattic\WooCommerce\Internal\POS\StoreApi\POSSessionHandler;
use WC_Unit_Test_Case;

/**
 * Tests for POSSessionHandler.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\POSSessionHandler
 */
class POSSessionHandlerTest extends WC_Unit_Test_Case {

	/**
	 * @testdox generate_customer_id ignores authenticated user and returns a guest-style id.
	 */
	public function test_generate_customer_id_is_guest_even_when_user_logged_in(): void {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$handler = new POSSessionHandler();
		$id      = $handler->generate_customer_id();

		$this->assertIsString( $id );
		$this->assertSame( 't_', substr( $id, 0, 2 ) );
		$this->assertNotSame( (string) $admin_id, $id );

		// Calling again must yield a fresh id — every transaction gets its own.
		$this->assertNotSame( $id, $handler->generate_customer_id() );

		wp_delete_user( $admin_id );
	}
}
