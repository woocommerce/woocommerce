<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\RestApi;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\RestApi\POSStaffController;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use WC_Install;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Integration tests for GET /wc-pos/v1/staff.
 */
class POSStaffControllerTest extends WC_REST_Unit_Test_Case {

	private const ROUTE = '/wc-pos/v1/staff';

	/**
	 * Set up roles + route registration once for the whole class.
	 *
	 * Routes are normally wired via the `woocommerce_rest_api_get_rest_namespaces` filter
	 * when the REST server boots. In test mode the filter has already fired by the time
	 * we get here, so we register the controller's routes directly against the live REST
	 * server.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		WC_Install::create_roles();
		wc_get_container()->get( POSStaffController::class )->register_routes();
	}

	/**
	 * @testdox Should return 401 for an anonymous request.
	 */
	public function test_anonymous_request_returns_401(): void {
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * @testdox Should return 401 for a pos_cashier user (lacks manage_pos_staff).
	 */
	public function test_pos_cashier_is_denied(): void {
		$cashier = self::factory()->user->create( array( 'role' => Capabilities::ROLE_CASHIER ) );
		wp_set_current_user( $cashier );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );

		$this->assertSame( 401, $response->get_status() );

		wp_delete_user( $cashier );
	}

	/**
	 * @testdox Should return 401 for a pos_manager user (lacks manage_pos_staff in M1).
	 */
	public function test_pos_manager_is_denied(): void {
		$manager = self::factory()->user->create( array( 'role' => Capabilities::ROLE_MANAGER ) );
		wp_set_current_user( $manager );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );

		$this->assertSame( 401, $response->get_status() );

		wp_delete_user( $manager );
	}

	/**
	 * @testdox Should return 200 and a staff list for a manage_woocommerce user.
	 */
	public function test_admin_can_list_staff(): void {
		$admin   = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$cashier = self::factory()->user->create(
			array(
				'role'         => Capabilities::ROLE_CASHIER,
				'display_name' => 'A Cashier',
			)
		);
		$manager = self::factory()->user->create(
			array(
				'role'         => Capabilities::ROLE_MANAGER,
				'display_name' => 'Z Manager',
			)
		);

		// Give the manager a PIN to confirm the pin record round-trips.
		wc_get_container()->get( POSPinService::class )->set_pin( $manager, '1234' );

		wp_set_current_user( $admin );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'staff', $data );

		$user_ids = array_column( $data['staff'], 'user_id' );
		$this->assertContains( $admin, $user_ids, 'Admin should appear in the staff list.' );
		$this->assertContains( $cashier, $user_ids, 'Cashier should appear in the staff list.' );
		$this->assertContains( $manager, $user_ids, 'Manager should appear in the staff list.' );

		$by_id = array();
		foreach ( $data['staff'] as $entry ) {
			$by_id[ $entry['user_id'] ] = $entry;
		}

		$this->assertNull( $by_id[ $cashier ]['pin'], 'Cashier without PIN should report pin: null.' );
		$this->assertIsArray( $by_id[ $manager ]['pin'], 'Manager with PIN should report a pin record.' );
		$this->assertSame( POSPinService::ALGO, $by_id[ $manager ]['pin']['algo'] );
		$this->assertSame( POSPinService::ITERATIONS, $by_id[ $manager ]['pin']['iterations'] );

		$this->assertArrayHasKey( 'capabilities', $by_id[ $cashier ] );
		$this->assertTrue( $by_id[ $cashier ]['capabilities']['view_pos'] ?? false );

		wp_delete_user( $admin );
		wp_delete_user( $cashier );
		wp_delete_user( $manager );
	}

	/**
	 * @testdox Should sort staff entries by display_name ascending for deterministic offline diffing.
	 */
	public function test_staff_list_is_sorted_by_display_name(): void {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$a     = self::factory()->user->create(
			array( 'role' => Capabilities::ROLE_CASHIER, 'display_name' => 'Aaron' )
		);
		$z     = self::factory()->user->create(
			array( 'role' => Capabilities::ROLE_CASHIER, 'display_name' => 'Zoe' )
		);

		wp_set_current_user( $admin );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$data     = $response->get_data();

		$names = array_column( $data['staff'], 'display_name' );
		$sorted = $names;
		sort( $sorted );
		$this->assertSame( $sorted, $names, 'Staff list must be sorted by display_name ASC.' );

		wp_delete_user( $admin );
		wp_delete_user( $a );
		wp_delete_user( $z );
	}

	/**
	 * @testdox Should exclude users without view_pos capability.
	 */
	public function test_users_without_view_pos_are_excluded(): void {
		$admin    = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$customer = self::factory()->user->create( array( 'role' => 'customer' ) );

		wp_set_current_user( $admin );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$data     = $response->get_data();

		$user_ids = array_column( $data['staff'], 'user_id' );
		$this->assertNotContains(
			$customer,
			$user_ids,
			'A customer-role user without view_pos must not appear in the staff list.'
		);

		wp_delete_user( $admin );
		wp_delete_user( $customer );
	}
}
