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
}
