<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\WCAdminUser;
use WC_Unit_Test_Case;

/**
 * Tests for the WCAdminUser class.
 */
class WCAdminUserTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WCAdminUser
	 */
	private $sut;

	/**
	 * Value of $wp_rest_additional_fields before the test ran.
	 *
	 * @var array
	 */
	private $additional_fields_backup = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// No base class snapshots this global, so keep our own copy.
		$this->additional_fields_backup = $GLOBALS['wp_rest_additional_fields'] ?? array();

		$this->sut = new WCAdminUser();
		$this->sut->register_user_data();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			$GLOBALS['wp_rest_additional_fields'] = $this->additional_fields_backup;
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should include the WooCommerce user fields even when rest_api_init has not fired.
	 */
	public function test_get_user_data_includes_woocommerce_fields_without_rest_api_init(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// A wp-admin request never fires rest_api_init, so the global the users controller
		// reads its extra fields from is empty.
		$GLOBALS['wp_rest_additional_fields'] = array();

		$user_data = WCAdminUser::get_user_data();

		$this->assertArrayHasKey( 'woocommerce_meta', $user_data, 'currentUserData should carry woocommerce_meta.' );
		$this->assertArrayHasKey( 'is_super_admin', $user_data, 'currentUserData should carry is_super_admin.' );
		$this->assertArrayHasKey( 'variable_product_tour_shown', $user_data['woocommerce_meta'], 'woocommerce_meta should carry the registered user data fields.' );
	}

	/**
	 * @testdox Should return the WooCommerce user fields for the current user in the $context context.
	 * @testWith ["view"]
	 *           ["edit"]
	 *
	 * @param string $context The request context.
	 */
	public function test_returns_woocommerce_fields_for_own_user( string $context ): void {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		WCAdminUser::update_user_data_field( $user_id, 'variable_product_tour_shown', 'yes' );
		wp_set_current_user( $user_id );

		$data = $this->get_user_response_data( $user_id, $context );

		$this->assertFalse( $data['is_super_admin'] ?? null, 'is_super_admin should be returned for the current user.' );
		$this->assertSame( 'yes', $data['woocommerce_meta']['variable_product_tour_shown'] ?? null, 'woocommerce_meta should be returned for the current user.' );
	}

	/**
	 * @testdox Should omit the WooCommerce user fields of another user for a current user with role '$role' in the $context context.
	 * @testWith ["administrator", "view"]
	 *           ["", "view"]
	 *           ["subscriber", "edit"]
	 *
	 * @param string $role    The role of the current user, empty for no logged in user.
	 * @param string $context The request context.
	 */
	public function test_omits_woocommerce_fields_for_other_users_when_not_allowed( string $role, string $context ): void {
		$other_user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( '' === $role ? 0 : self::factory()->user->create( array( 'role' => $role ) ) );

		$data = $this->get_user_response_data( $other_user_id, $context );

		$this->assertArrayNotHasKey( 'is_super_admin', $data, 'is_super_admin should not be returned for another user.' );
		$this->assertArrayNotHasKey( 'woocommerce_meta', $data, 'woocommerce_meta should not be returned for another user.' );
	}

	/**
	 * @testdox Should return the WooCommerce user fields of another user in the edit context for a current user with the '$capability' capability.
	 * @testWith ["list_users"]
	 *           ["edit_users"]
	 *
	 * @param string $capability The capability granted to the current user.
	 */
	public function test_returns_woocommerce_fields_for_other_users_in_edit_context_with_capability( string $capability ): void {
		// A customer, since WooCommerce doesn't let non-administrators edit administrators.
		$other_user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		WCAdminUser::update_user_data_field( $other_user_id, 'variable_product_tour_shown', 'yes' );
		$current_user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new \WP_User( $current_user_id ) )->add_cap( $capability );
		wp_set_current_user( $current_user_id );

		$data = $this->get_user_response_data( $other_user_id, 'edit' );

		$this->assertFalse( $data['is_super_admin'] ?? null, 'is_super_admin should be returned for another user.' );
		$this->assertSame( 'yes', $data['woocommerce_meta']['variable_product_tour_shown'] ?? null, 'woocommerce_meta should be returned for another user.' );
	}

	/**
	 * Get the data the users REST endpoint would return for a user.
	 *
	 * @param int    $user_id The id of the user to get the data for.
	 * @param string $context The request context.
	 * @return array The response data.
	 */
	private function get_user_response_data( int $user_id, string $context ): array {
		$request = new \WP_REST_Request( 'GET', '/wp/v2/users/' . $user_id );
		$request->set_param( 'context', $context );

		return ( new \WP_REST_Users_Controller() )->prepare_item_for_response( new \WP_User( $user_id ), $request )->get_data();
	}
}
