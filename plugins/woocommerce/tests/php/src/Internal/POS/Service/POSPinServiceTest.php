<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Service;

use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use Automattic\WooCommerce\Internal\StoreActors\ActorAccessRepository;
use Automattic\WooCommerce\Internal\StoreActors\ActorRepository;
use Automattic\WooCommerce\Tests\Internal\StoreActors\Concerns\EnablesActorsFeature;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * @since 10.9.0
 * @group pos-actors
 */
class POSPinServiceTest extends WC_Unit_Test_Case {

	use EnablesActorsFeature;

	private POSPinService $pin;
	private ActorRepository $actors;
	private ActorAccessRepository $access;

	public function setUp(): void {
		parent::setUp();
		$this->install_actor_tables();
		$this->pin    = wc_get_container()->get( POSPinService::class );
		$this->actors = wc_get_container()->get( ActorRepository::class );
		$this->access = wc_get_container()->get( ActorAccessRepository::class );

		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( 'DELETE FROM ' . $this->access->get_table_name() );
		$wpdb->query( 'DELETE FROM ' . $this->actors->get_table_name() );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	private function make_actor_with_access(): int {
		$id = $this->actors->insert( array( 'display_name' => 'Alex' ) );
		$this->access->insert(
			array(
				'actor_id'           => $id,
				'access_profile_key' => 'pos_cashier',
			)
		);
		return $id;
	}

	public function test_set_then_verify_roundtrip(): void {
		$id = $this->make_actor_with_access();
		$this->assertTrue( $this->pin->set_pin( $id, '1234' ) );

		$record = $this->pin->get_public_pin_record( $id );
		$this->assertNotNull( $record );
		$this->assertSame( POSPinService::ALGO, $record['algo'] );
		$this->assertSame( POSPinService::ITERATIONS, $record['iterations'] );
		$this->assertTrue( $this->pin->verify_pin( '1234', $record ) );
		$this->assertFalse( $this->pin->verify_pin( '9999', $record ) );
	}

	public function test_set_rejects_invalid_format(): void {
		$id     = $this->make_actor_with_access();
		$result = $this->pin->set_pin( $id, 'abcd' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertFalse( $this->pin->has_pin( $id ) );
	}

	public function test_set_requires_access_row(): void {
		$id     = $this->actors->insert( array( 'display_name' => 'Orphan' ) );
		$result = $this->pin->set_pin( $id, '1234' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_actor_no_access', $result->get_error_code() );
	}

	public function test_delete_clears_credential(): void {
		$id = $this->make_actor_with_access();
		$this->pin->set_pin( $id, '1234' );
		$this->assertTrue( $this->pin->has_pin( $id ) );

		$this->assertTrue( $this->pin->delete_pin( $id ) );
		$this->assertFalse( $this->pin->has_pin( $id ) );
		$this->assertNull( $this->pin->get_public_pin_record( $id ) );
	}

	public function test_set_rejects_pin_already_in_use_by_another_active_staff(): void {
		$alice = $this->make_actor_with_access();
		$bob   = $this->make_actor_with_access();

		$this->assertTrue( $this->pin->set_pin( $alice, '1234' ) );

		$result = $this->pin->set_pin( $bob, '1234' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_pin_in_use', $result->get_error_code() );
		$this->assertFalse( $this->pin->has_pin( $bob ) );
	}

	public function test_set_pin_allows_reusing_own_pin_on_self_update(): void {
		$id = $this->make_actor_with_access();
		$this->assertTrue( $this->pin->set_pin( $id, '1234' ) );
		// Re-setting the same PIN on the same actor must not collide with itself.
		$this->assertTrue( $this->pin->set_pin( $id, '1234' ) );
	}

	public function test_set_pin_ignores_inactive_access_rows(): void {
		$alice = $this->make_actor_with_access();
		$bob   = $this->make_actor_with_access();
		$this->pin->set_pin( $alice, '1234' );

		// Mark Alice's POS access inactive.
		$access = $this->access->get_for_actor( $alice );
		$this->access->update( (int) $access['access_id'], array( 'status' => ActorAccessRepository::STATUS_INACTIVE ) );

		// Bob can now reuse Alice's old PIN.
		$this->assertTrue( $this->pin->set_pin( $bob, '1234' ) );
	}

	public function test_find_actor_with_pin_returns_match(): void {
		$alice = $this->make_actor_with_access();
		$bob   = $this->make_actor_with_access();
		$this->pin->set_pin( $alice, '1111' );
		$this->pin->set_pin( $bob, '2222' );

		$this->assertSame( $alice, $this->pin->find_actor_with_pin( '1111' ) );
		$this->assertSame( $bob, $this->pin->find_actor_with_pin( '2222' ) );
		$this->assertNull( $this->pin->find_actor_with_pin( '9999' ) );
	}
}
