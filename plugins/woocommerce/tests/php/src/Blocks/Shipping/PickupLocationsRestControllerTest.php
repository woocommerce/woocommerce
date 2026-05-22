<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Shipping;

use Automattic\WooCommerce\Blocks\Shipping\PickupLocationsRestController;
use WC_Unit_Test_Case;

/**
 * Tests for the PickupLocationsRestController class.
 */
class PickupLocationsRestControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var PickupLocationsRestController
	 */
	private $sut;

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * Shop manager user ID.
	 *
	 * @var int
	 */
	private $shop_manager_id;

	/**
	 * Editor user ID (no WooCommerce caps).
	 *
	 * @var int
	 */
	private $editor_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut             = new PickupLocationsRestController();
		$this->admin_id        = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->shop_manager_id = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		$this->editor_id       = self::factory()->user->create( array( 'role' => 'editor' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		delete_option( 'woocommerce_pickup_location_settings' );
		delete_option( 'pickup_location_pickup_locations' );
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// Permission check tests
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should allow a shop manager to update pickup location settings.
	 */
	public function test_shop_manager_can_update_settings(): void {
		wp_set_current_user( $this->shop_manager_id );

		$result = $this->sut->update_settings_permissions_check(
			new \WP_REST_Request( 'POST', '/wc/v3/pickup-locations' )
		);

		$this->assertTrue( $result, 'A shop manager should be allowed to edit pickup location settings.' );
	}

	/**
	 * @testdox Should allow an administrator to update pickup location settings.
	 */
	public function test_admin_can_update_settings(): void {
		wp_set_current_user( $this->admin_id );

		$result = $this->sut->update_settings_permissions_check(
			new \WP_REST_Request( 'POST', '/wc/v3/pickup-locations' )
		);

		$this->assertTrue( $result, 'An administrator should be allowed to edit pickup location settings.' );
	}

	/**
	 * @testdox Should deny an editor from updating pickup location settings.
	 */
	public function test_editor_cannot_update_settings(): void {
		wp_set_current_user( $this->editor_id );

		$result = $this->sut->update_settings_permissions_check(
			new \WP_REST_Request( 'POST', '/wc/v3/pickup-locations' )
		);

		$this->assertWPError( $result, 'An editor should not be allowed to edit pickup location settings.' );
	}

	/**
	 * @testdox Should deny an unauthenticated user from updating pickup location settings.
	 */
	public function test_unauthenticated_user_cannot_update_settings(): void {
		wp_set_current_user( 0 );

		$result = $this->sut->update_settings_permissions_check(
			new \WP_REST_Request( 'POST', '/wc/v3/pickup-locations' )
		);

		$this->assertWPError( $result, 'An unauthenticated user should not be allowed to edit pickup location settings.' );
	}

	// -------------------------------------------------------------------------
	// Save / response tests
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should save pickup location method settings and echo them back in the response.
	 */
	public function test_update_settings_saves_method_settings(): void {
		wp_set_current_user( $this->shop_manager_id );

		$settings = array(
			'enabled'    => 'yes',
			'title'      => 'Local Pickup',
			'tax_status' => 'taxable',
			'cost'       => '',
		);

		$request = new \WP_REST_Request( 'POST', '/wc/v3/pickup-locations' );
		$request->set_param( 'pickup_location_settings', $settings );

		$response = $this->sut->update_settings( $request );
		$data     = $response->get_data();

		$this->assertSame( $settings, $data['pickup_location_settings'], 'Response should echo back the saved method settings.' );
		$this->assertSame( $settings, get_option( 'woocommerce_pickup_location_settings' ), 'Method settings should be persisted to the database.' );
	}

	/**
	 * @testdox Should save pickup locations list and echo it back in the response.
	 */
	public function test_update_settings_saves_pickup_locations(): void {
		wp_set_current_user( $this->shop_manager_id );

		$locations = array(
			array(
				'name'    => 'Main Store',
				'address' => array(
					'address_1' => '123 Main St',
					'city'      => 'Anytown',
					'state'     => 'CA',
					'postcode'  => '90210',
					'country'   => 'US',
				),
				'details' => 'Open daily 9am-5pm',
				'enabled' => true,
			),
		);

		$request = new \WP_REST_Request( 'POST', '/wc/v3/pickup-locations' );
		$request->set_param( 'pickup_locations', $locations );

		$response = $this->sut->update_settings( $request );
		$data     = $response->get_data();

		$this->assertSame( $locations, $data['pickup_locations'], 'Response should echo back the saved locations.' );
		$this->assertSame( $locations, get_option( 'pickup_location_pickup_locations' ), 'Locations should be persisted to the database.' );
	}

	/**
	 * @testdox Should preserve existing settings when only one param is sent.
	 */
	public function test_omitted_params_are_not_overwritten(): void {
		wp_set_current_user( $this->shop_manager_id );

		$original_settings = array(
			'enabled'    => 'yes',
			'title'      => 'Local Pickup',
			'tax_status' => 'taxable',
			'cost'       => '5.00',
		);
		update_option( 'woocommerce_pickup_location_settings', $original_settings );

		$request = new \WP_REST_Request( 'POST', '/wc/v3/pickup-locations' );
		$request->set_param( 'pickup_locations', array() );

		$this->sut->update_settings( $request );

		$this->assertSame(
			$original_settings,
			get_option( 'woocommerce_pickup_location_settings' ),
			'Existing method settings should not be overwritten when only pickup_locations is sent.'
		);
	}
}
