<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\RestApi;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\RestApi\POSStaffController;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Integration tests for GET /wc/pos/v1/staff under the user-meta POS model.
 */
class POSStaffControllerTest extends WC_REST_Unit_Test_Case {

	private const ROUTE = '/wc/pos/v1/staff';

	/**
	 * Register the controller's routes once for the whole class.
	 *
	 * Routes are normally wired via the `woocommerce_rest_api_get_rest_namespaces` filter
	 * when the REST server boots. In test mode the filter has already fired by the time
	 * we get here, so we register the controller's routes directly against the live REST
	 * server.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
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
	 * @testdox Should return 401 for a subscriber-role user with a POS meta role.
	 *
	 * POS-only users never call this endpoint directly — the device admin reads
	 * the staff list on their behalf. So even though a subscriber with `_pos_role`
	 * = pos_cashier has POS access, the endpoint requires `manage_woocommerce`.
	 */
	public function test_pos_only_user_is_denied(): void {
		$cashier = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_role( $cashier, Capabilities::POS_ROLE_CASHIER );
		wp_set_current_user( $cashier );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );

		$this->assertSame( 401, $response->get_status() );

		wp_delete_user( $cashier );
	}

	/**
	 * @testdox Should return 200 and a staff list for an administrator.
	 */
	public function test_admin_can_list_staff(): void {
		$admin       = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$pos_admin   = self::factory()->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'M Admin',
			)
		);
		$cashier     = self::factory()->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'A Cashier',
			)
		);
		$manager     = self::factory()->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'Z Manager',
			)
		);

		Capabilities::set_pos_role( $pos_admin, Capabilities::POS_ROLE_ADMIN );
		Capabilities::set_pos_role( $cashier, Capabilities::POS_ROLE_CASHIER );
		Capabilities::set_pos_role( $manager, Capabilities::POS_ROLE_MANAGER );

		// Give the manager a PIN to confirm the pin record round-trips.
		wc_get_container()->get( POSPinService::class )->set_pin( $manager, '1234' );

		wp_set_current_user( $admin );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'staff', $data );

		$by_id = array();
		foreach ( $data['staff'] as $entry ) {
			$by_id[ $entry['user_id'] ] = $entry;
		}

		$this->assertArrayNotHasKey(
			$admin,
			$by_id,
			'A wp administrator without an explicit POS role must not appear in the staff list.'
		);
		$this->assertArrayHasKey( $pos_admin, $by_id, 'pos_admin should appear via _pos_role meta.' );
		$this->assertArrayHasKey( $cashier, $by_id, 'Cashier should appear via _pos_role meta.' );
		$this->assertArrayHasKey( $manager, $by_id, 'Manager should appear via _pos_role meta.' );

		$this->assertSame( Capabilities::POS_ROLE_ADMIN, $by_id[ $pos_admin ]['role'] );
		$this->assertSame( Capabilities::POS_ROLE_CASHIER, $by_id[ $cashier ]['role'] );
		$this->assertSame( Capabilities::POS_ROLE_MANAGER, $by_id[ $manager ]['role'] );

		$this->assertNull( $by_id[ $cashier ]['pin'], 'Cashier without PIN should report pin: null.' );
		$this->assertIsArray( $by_id[ $manager ]['pin'], 'Manager with PIN should report a pin record.' );
		$this->assertSame( POSPinService::ALGO, $by_id[ $manager ]['pin']['algo'] );
		$this->assertSame( POSPinService::ITERATIONS, $by_id[ $manager ]['pin']['iterations'] );

		$this->assertArrayHasKey( 'capabilities', $by_id[ $cashier ] );
		$this->assertTrue( $by_id[ $cashier ]['capabilities'][ Capabilities::CAP_PROCESS_SALES ] ?? false );
		$this->assertArrayNotHasKey( Capabilities::CAP_ISSUE_REFUNDS, $by_id[ $cashier ]['capabilities'] );

		$this->assertTrue( $by_id[ $manager ]['capabilities'][ Capabilities::CAP_ISSUE_REFUNDS ] ?? false );
		$this->assertArrayNotHasKey( Capabilities::CAP_MANAGE_POS_STAFF, $by_id[ $manager ]['capabilities'] );

		$this->assertTrue( $by_id[ $pos_admin ]['capabilities'][ Capabilities::CAP_MANAGE_POS_STAFF ] ?? false );

		wp_delete_user( $admin );
		wp_delete_user( $pos_admin );
		wp_delete_user( $cashier );
		wp_delete_user( $manager );
	}

	/**
	 * @testdox Should sort staff entries by display_name ascending for deterministic offline diffing.
	 */
	public function test_staff_list_is_sorted_by_display_name(): void {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$a     = self::factory()->user->create(
			array( 'role' => 'subscriber', 'display_name' => 'Aaron' )
		);
		$z     = self::factory()->user->create(
			array( 'role' => 'subscriber', 'display_name' => 'Zoe' )
		);
		Capabilities::set_pos_role( $a, Capabilities::POS_ROLE_CASHIER );
		Capabilities::set_pos_role( $z, Capabilities::POS_ROLE_CASHIER );

		wp_set_current_user( $admin );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$data     = $response->get_data();

		$names  = array_column( $data['staff'], 'display_name' );
		$sorted = $names;
		sort( $sorted );
		$this->assertSame( $sorted, $names, 'Staff list must be sorted by display_name ASC.' );

		wp_delete_user( $admin );
		wp_delete_user( $a );
		wp_delete_user( $z );
	}

	/**
	 * @testdox Should exclude users without `_pos_role` meta — including administrators.
	 */
	public function test_users_without_pos_role_are_excluded(): void {
		$admin        = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$shop_manager = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		$customer     = self::factory()->user->create( array( 'role' => 'customer' ) );

		wp_set_current_user( $admin );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$data     = $response->get_data();

		$user_ids = array_column( $data['staff'], 'user_id' );
		$this->assertNotContains( $admin, $user_ids );
		$this->assertNotContains( $shop_manager, $user_ids );
		$this->assertNotContains( $customer, $user_ids );

		wp_delete_user( $admin );
		wp_delete_user( $shop_manager );
		wp_delete_user( $customer );
	}
}
