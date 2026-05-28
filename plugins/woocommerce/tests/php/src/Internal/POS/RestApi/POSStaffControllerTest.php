<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\RestApi;

use Automattic\WooCommerce\Internal\POS\Actors\AccessProfileRegistry;
use Automattic\WooCommerce\Internal\POS\RestApi\POSStaffController;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use Automattic\WooCommerce\Internal\StoreActors\ActorAccessRepository;
use Automattic\WooCommerce\Internal\StoreActors\ActorRepository;
use Automattic\WooCommerce\Tests\Internal\StoreActors\Concerns\EnablesActorsFeature;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * @since 10.9.0
 * @group pos-actors
 */
class POSStaffControllerTest extends WC_REST_Unit_Test_Case {

	use EnablesActorsFeature;

	private const ROUTE = '/wc/pos/v1/staff';

	private ActorRepository $actors;
	private ActorAccessRepository $access;
	private POSPinService $pin;

	public function setUp(): void {
		parent::setUp();
		$this->install_actor_tables();
		$this->actors = wc_get_container()->get( ActorRepository::class );
		$this->access = wc_get_container()->get( ActorAccessRepository::class );
		$this->pin    = wc_get_container()->get( POSPinService::class );

		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( 'DELETE FROM ' . $this->access->get_table_name() );
		$wpdb->query( 'DELETE FROM ' . $this->actors->get_table_name() );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Register the route for this test run.
		wc_get_container()->get( POSStaffController::class )->register_routes();

		// Administrator already holds manage_woocommerce by default; no extra grant needed.
		$admin = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
	}

	private function make_actor( array $actor_data = array(), array $access_data = array() ): int {
		$id = $this->actors->insert( $actor_data + array( 'display_name' => 'Test' ) );
		$this->access->insert(
			$access_data + array(
				'actor_id'           => $id,
				'access_profile_key' => AccessProfileRegistry::PROFILE_CASHIER,
			)
		);
		return $id;
	}

	public function test_get_staff_returns_flat_array_with_inlined_permissions(): void {
		$id = $this->make_actor( array( 'display_name' => 'Alex Cashier' ) );
		$this->pin->set_pin( $id, '1234' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayNotHasKey( 'actors', $data );
		$this->assertCount( 1, $data );

		$staff = $data[0];
		$this->assertSame( $id, $staff['id'] );
		$this->assertSame( 'Alex Cashier', $staff['display_name'] );
		$this->assertNull( $staff['wp_user_id'] );
		$this->assertNull( $staff['email'] );

		$this->assertSame( AccessProfileRegistry::PROFILE_CASHIER, $staff['pos_access']['profile_key'] );
		$this->assertSame( 'Cashier', $staff['pos_access']['profile_name'] );

		$perms = $staff['pos_access']['permissions'];
		$this->assertIsArray( $perms );
		$this->assertSame( AccessProfileRegistry::ACCESS_ALLOW, $perms[ AccessProfileRegistry::TAG_PROCESS_SALES ] );
		$this->assertSame( AccessProfileRegistry::ACCESS_APPROVAL_REQUIRED, $perms[ AccessProfileRegistry::TAG_REFUND_ORDERS ] );
		$this->assertSame( AccessProfileRegistry::ACCESS_DENY, $perms[ AccessProfileRegistry::TAG_MANAGE_POS_STAFF ] );

		$cred = $staff['pos_access']['credential'];
		$this->assertNotNull( $cred );
		$this->assertSame( POSPinService::ALGO, $cred['algo'] );
		$this->assertNotEmpty( $cred['hash'] );
	}

	public function test_staff_without_active_access_is_filtered_out(): void {
		// Actor exists but with inactive access.
		$id     = $this->actors->insert( array( 'display_name' => 'Inactive Access' ) );
		$access = $this->access->insert(
			array(
				'actor_id'           => $id,
				'access_profile_key' => AccessProfileRegistry::PROFILE_CASHIER,
				'status'             => ActorAccessRepository::STATUS_INACTIVE,
			)
		);
		$this->assertGreaterThan( 0, $access );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array(), $response->get_data() );
	}

	public function test_unauthenticated_caller_is_rejected(): void {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$this->assertGreaterThanOrEqual( 400, $response->get_status() );
	}

	public function test_shop_manager_can_read_actors(): void {
		// shop_manager holds manage_woocommerce by default.
		$manager = $this->factory()->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $manager );
		$this->make_actor();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$this->assertSame( 200, $response->get_status() );
	}

	public function test_subscriber_is_forbidden(): void {
		$sub = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $sub );
		$this->make_actor();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$this->assertGreaterThanOrEqual( 400, $response->get_status() );
	}
}
