<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\RestApi;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\RestApi\POSStaffController;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Integration tests for GET /wc/pos/v1/staff under the pos_staff role + preset meta POS model.
 */
class POSStaffControllerTest extends WC_REST_Unit_Test_Case {

	private const ROUTE = '/wc/pos/v1/staff';

	/**
	 * Register the controller's routes against the live REST server.
	 *
	 * Routes are normally wired via the `woocommerce_rest_api_get_rest_namespaces` filter
	 * when the REST server boots. WC_REST_Unit_Test_Case::setUp() rebuilds the global
	 * REST server every test and re-fires `rest_api_init`, which wipes any routes
	 * registered earlier — so we re-register per test rather than once per class.
	 */
	public function setUp(): void {
		parent::setUp();
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
	 * @testdox Should return 403 for a subscriber with a POS preset assigned.
	 *
	 * POS-only users never call this endpoint directly — the device admin reads
	 * the staff list on their behalf. So even though a subscriber with `_woocommerce_pos_preset`
	 * = pos_cashier has POS access, the endpoint requires `manage_woocommerce`
	 * — WordPress returns 403 for an authenticated user without the cap.
	 */
	public function test_pos_only_user_is_denied(): void {
		$cashier = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $cashier, Capabilities::POS_PRESET_CASHIER );
		wp_set_current_user( $cashier );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );

		$this->assertSame( 403, $response->get_status() );

		wp_delete_user( $cashier );
	}

	/**
	 * @testdox Should return 200 and a staff list for an administrator.
	 */
	public function test_admin_can_list_staff(): void {
		$admin     = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$pos_admin = self::factory()->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'M Admin',
			)
		);
		$cashier   = self::factory()->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'A Cashier',
			)
		);
		$manager   = self::factory()->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'Z Manager',
			)
		);

		Capabilities::set_pos_preset( $pos_admin, Capabilities::POS_PRESET_ADMIN );
		Capabilities::set_pos_preset( $cashier, Capabilities::POS_PRESET_CASHIER );
		Capabilities::set_pos_preset( $manager, Capabilities::POS_PRESET_MANAGER );

		// PINs are mandatory at the admin form layer, so every listed staff member
		// must have one in the wire payload too.
		$pin_service = wc_get_container()->get( POSPinService::class );
		$pin_service->set_pin( $pos_admin, '1111' );
		$pin_service->set_pin( $cashier, '2222' );
		$pin_service->set_pin( $manager, '3333' );

		wp_set_current_user( $admin );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );

		$by_id = array();
		foreach ( $data as $entry ) {
			$by_id[ $entry['user_id'] ] = $entry;
		}

		$this->assertArrayNotHasKey(
			$admin,
			$by_id,
			'A wp administrator without an explicit POS preset must not appear in the staff list.'
		);
		$this->assertArrayHasKey( $pos_admin, $by_id, 'pos_admin should appear via _woocommerce_pos_preset meta.' );
		$this->assertArrayHasKey( $cashier, $by_id, 'Cashier should appear via _woocommerce_pos_preset meta.' );
		$this->assertArrayHasKey( $manager, $by_id, 'Manager should appear via _woocommerce_pos_preset meta.' );

		$this->assertSame( Capabilities::POS_PRESET_ADMIN, $by_id[ $pos_admin ]['preset'] );
		$this->assertSame( Capabilities::POS_PRESET_CASHIER, $by_id[ $cashier ]['preset'] );
		$this->assertSame( Capabilities::POS_PRESET_MANAGER, $by_id[ $manager ]['preset'] );

		$this->assertIsArray( $by_id[ $cashier ]['pin'], 'Cashier with PIN should report a pin record.' );
		$this->assertIsArray( $by_id[ $manager ]['pin'], 'Manager with PIN should report a pin record.' );
		$this->assertSame( POSPinService::ALGO, $by_id[ $manager ]['pin']['algo'] );
		$this->assertSame( POSPinService::ITERATIONS, $by_id[ $manager ]['pin']['iterations'] );

		$this->assertArrayHasKey( 'capabilities', $by_id[ $cashier ] );
		$this->assertTrue( $by_id[ $cashier ]['capabilities'][ Capabilities::CAP_PROCESS_SALES ] ?? false );
		$this->assertArrayNotHasKey( Capabilities::CAP_ISSUE_REFUNDS, $by_id[ $cashier ]['capabilities'] );

		$this->assertTrue( $by_id[ $manager ]['capabilities'][ Capabilities::CAP_ISSUE_REFUNDS ] ?? false );
		$this->assertArrayNotHasKey( Capabilities::CAP_MANAGE_STAFF, $by_id[ $manager ]['capabilities'] );

		$this->assertTrue( $by_id[ $pos_admin ]['capabilities'][ Capabilities::CAP_MANAGE_STAFF ] ?? false );

		wp_delete_user( $admin );
		wp_delete_user( $pos_admin );
		wp_delete_user( $cashier );
		wp_delete_user( $manager );
	}

	/**
	 * @testdox Should sort staff entries by display_name ascending for deterministic offline diffing.
	 */
	public function test_staff_list_is_sorted_by_display_name(): void {
		$admin       = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$pin_service = wc_get_container()->get( POSPinService::class );
		$a           = self::factory()->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'Aaron',
			)
		);
		$z           = self::factory()->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'Zoe',
			)
		);
		Capabilities::set_pos_preset( $a, Capabilities::POS_PRESET_CASHIER );
		Capabilities::set_pos_preset( $z, Capabilities::POS_PRESET_CASHIER );
		$pin_service->set_pin( $a, '1010' );
		$pin_service->set_pin( $z, '2020' );

		wp_set_current_user( $admin );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$data     = $response->get_data();

		$names  = array_column( $data, 'display_name' );
		$sorted = $names;
		sort( $sorted );
		$this->assertSame( $sorted, $names, 'Staff list must be sorted by display_name ASC.' );

		wp_delete_user( $admin );
		wp_delete_user( $a );
		wp_delete_user( $z );
	}

	/**
	 * @testdox Should exclude pos_staff users that do not have a PIN set.
	 *
	 * PIN is the sole operator credential at the till, so the controller filters
	 * out any pos_staff user without a stored PIN — the wire payload must
	 * guarantee `pin` is non-null for every listed row.
	 */
	public function test_users_without_pin_are_excluded(): void {
		$admin       = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$with_pin    = self::factory()->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'With PIN',
			)
		);
		$without_pin = self::factory()->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'No PIN',
			)
		);

		Capabilities::set_pos_preset( $with_pin, Capabilities::POS_PRESET_CASHIER );
		Capabilities::set_pos_preset( $without_pin, Capabilities::POS_PRESET_CASHIER );

		$pin_service = wc_get_container()->get( POSPinService::class );
		$pin_service->set_pin( $with_pin, '7777' );

		wp_set_current_user( $admin );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$data     = $response->get_data();

		$user_ids = array_column( $data, 'user_id' );
		$this->assertContains( $with_pin, $user_ids, 'Staff with a PIN must be listed.' );
		$this->assertNotContains( $without_pin, $user_ids, 'Staff without a PIN must be filtered out.' );

		foreach ( $data as $entry ) {
			$this->assertNotNull( $entry['pin'] ?? null, 'Every listed entry must have a non-null pin.' );
			$this->assertIsArray( $entry['pin'], 'Pin must be a record array, never null.' );
		}

		wp_delete_user( $admin );
		wp_delete_user( $with_pin );
		wp_delete_user( $without_pin );
	}

	/**
	 * @testdox Should exclude users without a POS preset — including administrators.
	 */
	public function test_users_without_pos_preset_are_excluded(): void {
		$admin        = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$shop_manager = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		$customer     = self::factory()->user->create( array( 'role' => 'customer' ) );

		wp_set_current_user( $admin );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$data     = $response->get_data();

		$user_ids = array_column( $data, 'user_id' );
		$this->assertNotContains( $admin, $user_ids );
		$this->assertNotContains( $shop_manager, $user_ids );
		$this->assertNotContains( $customer, $user_ids );

		wp_delete_user( $admin );
		wp_delete_user( $shop_manager );
		wp_delete_user( $customer );
	}
}
