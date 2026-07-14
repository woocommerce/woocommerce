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
	 * @testdox Should deny an editor from updating pickup location settings.
	 */
	public function test_editor_cannot_update_settings(): void {
		wp_set_current_user( $this->editor_id );

		$result = $this->sut->update_settings_permissions_check(
			new \WP_REST_Request( 'POST', '/wc/v3/pickup-locations' )
		);

		$this->assertWPError( $result, 'An editor should not be allowed to edit pickup location settings.' );
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
	 * @testdox Should drop incomplete locations and default missing keys so admin hydration never hits undefined indexes.
	 */
	public function test_update_settings_drops_incomplete_locations(): void {
		wp_set_current_user( $this->shop_manager_id );

		$request = new \WP_REST_Request( 'POST', '/wc/v3/pickup-locations' );
		$request->set_param(
			'pickup_locations',
			array(
				// Empty object: dropped.
				array(),
				// Nameless: dropped.
				array( 'name' => '' ),
				// Name only: kept, other keys defaulted.
				array( 'name' => 'Warehouse' ),
			)
		);

		$this->sut->update_settings( $request );

		$saved = get_option( 'pickup_location_pickup_locations' );

		$this->assertCount( 1, $saved, 'Only the named location should be persisted.' );
		$this->assertSame(
			array(
				'name'    => 'Warehouse',
				'address' => array(),
				'details' => '',
				'enabled' => false,
			),
			$saved[0],
			'A kept location must always carry every key so hydrate_client_settings() never reads a missing index.'
		);
	}

	/**
	 * @testdox Should default missing address sub-keys when a partial address is provided.
	 */
	public function test_update_settings_defaults_partial_address_keys(): void {
		wp_set_current_user( $this->shop_manager_id );

		$request = new \WP_REST_Request( 'POST', '/wc/v3/pickup-locations' );
		$request->set_param(
			'pickup_locations',
			array(
				array(
					'name'    => 'Warehouse',
					// Only country supplied; the rest must be defaulted so
					// ShippingController::filter_taxable_address() never reads a
					// missing index once country is set.
					'address' => array( 'country' => 'US' ),
				),
			)
		);

		$this->sut->update_settings( $request );

		$saved = get_option( 'pickup_location_pickup_locations' );

		$this->assertSame(
			array(
				'address_1' => '',
				'city'      => '',
				'state'     => '',
				'postcode'  => '',
				'country'   => 'US',
			),
			$saved[0]['address'],
			'A provided address must carry every sub-key so downstream readers never hit undefined indexes.'
		);
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

	// -------------------------------------------------------------------------
	// Sanitization tests (defense in depth against stored XSS)
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should strip HTML/script from the method settings title before saving.
	 */
	public function test_update_settings_sanitizes_html_in_title(): void {
		wp_set_current_user( $this->shop_manager_id );

		$request = new \WP_REST_Request( 'POST', '/wc/v3/pickup-locations' );
		$request->set_param(
			'pickup_location_settings',
			array(
				'enabled'    => 'yes',
				'title'      => '<script>alert(1)</script>Pickup',
				'tax_status' => 'taxable',
				'cost'       => '',
			)
		);

		$this->sut->update_settings( $request );

		$saved = get_option( 'woocommerce_pickup_location_settings' );

		$this->assertIsArray( $saved );
		$this->assertArrayHasKey( 'title', $saved );
		$this->assertStringNotContainsString( '<script', $saved['title'], 'Script tag must be stripped from saved title.' );
		$this->assertStringNotContainsString( 'alert(1)', $saved['title'], 'Inline script payload must not survive sanitization.' );
		$this->assertStringContainsString( 'Pickup', $saved['title'], 'Plain text portion of the title should be preserved.' );
	}

	/**
	 * @testdox Should preserve safe HTML in location details but strip scripts.
	 */
	public function test_update_settings_preserves_safe_html_in_details(): void {
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
				'details' => '<strong>Hours:</strong> 9-5 <a href="https://example.com">Map</a><script>alert(1)</script>',
				'enabled' => true,
			),
		);

		$request = new \WP_REST_Request( 'POST', '/wc/v3/pickup-locations' );
		$request->set_param( 'pickup_locations', $locations );

		$this->sut->update_settings( $request );

		$saved = get_option( 'pickup_location_pickup_locations' );

		$this->assertIsArray( $saved );
		$this->assertArrayHasKey( 0, $saved );
		$this->assertArrayHasKey( 'details', $saved[0] );
		$this->assertStringContainsString( '<strong>', $saved[0]['details'], 'wp_kses_post should keep <strong>.' );
		$this->assertStringContainsString( '<a ', $saved[0]['details'], 'wp_kses_post should keep <a> tags.' );
		$this->assertStringNotContainsString( '<script', $saved[0]['details'], '<script> must be stripped by wp_kses_post.' );
		// wp_kses_post() removes the executable <script> tag but keeps its inner
		// text as harmless plain text, so assert the executable element is gone
		// rather than the (now inert) "alert(1)" string.
		$this->assertStringNotContainsString( '<script>alert(1)</script>', $saved[0]['details'], 'Executable script element must not survive sanitization.' );
	}

	/**
	 * @testdox Should strip HTML/script from pickup location name and address fields.
	 */
	public function test_update_settings_sanitizes_html_in_location_fields(): void {
		wp_set_current_user( $this->shop_manager_id );

		$locations = array(
			array(
				'name'    => '<script>alert(1)</script>Main Store',
				'address' => array(
					'address_1' => '<img src=x onerror=alert(1)>123 Main St',
					'city'      => 'Anytown',
					'state'     => 'CA',
					'postcode'  => '90210',
					'country'   => 'US',
				),
				'details' => '',
				'enabled' => true,
			),
		);

		$request = new \WP_REST_Request( 'POST', '/wc/v3/pickup-locations' );
		$request->set_param( 'pickup_locations', $locations );

		$this->sut->update_settings( $request );

		$saved = get_option( 'pickup_location_pickup_locations' );

		$this->assertIsArray( $saved );
		$this->assertArrayHasKey( 0, $saved );
		$this->assertStringNotContainsString( '<script', $saved[0]['name'], 'Script tag must be stripped from saved location name.' );
		$this->assertStringNotContainsString( 'alert(1)', $saved[0]['name'], 'Inline script payload must not survive location name sanitization.' );
		$this->assertStringContainsString( 'Main Store', $saved[0]['name'], 'Plain text portion of the location name should be preserved.' );

		$this->assertArrayHasKey( 'address_1', $saved[0]['address'] );
		$this->assertStringNotContainsString( '<img', $saved[0]['address']['address_1'], 'HTML tags must be stripped from address fields.' );
		$this->assertStringNotContainsString( 'onerror', $saved[0]['address']['address_1'], 'Event handler payload must not survive address sanitization.' );
		$this->assertStringContainsString( '123 Main St', $saved[0]['address']['address_1'], 'Plain text portion of the address should be preserved.' );
	}

	/**
	 * @testdox Should preserve math expressions in cost while stripping HTML.
	 */
	public function test_update_settings_preserves_cost_formula(): void {
		wp_set_current_user( $this->shop_manager_id );

		$request = new \WP_REST_Request( 'POST', '/wc/v3/pickup-locations' );
		$request->set_param(
			'pickup_location_settings',
			array(
				'enabled'    => 'yes',
				'title'      => 'Local Pickup',
				'tax_status' => 'taxable',
				'cost'       => '<script>alert(1)</script>5 + 1.50',
			)
		);

		$this->sut->update_settings( $request );

		$saved = get_option( 'woocommerce_pickup_location_settings' );

		$this->assertIsArray( $saved );
		$this->assertArrayHasKey( 'cost', $saved );
		$this->assertStringNotContainsString( '<script', $saved['cost'], '<script> must be stripped from cost.' );
		$this->assertStringNotContainsString( 'alert(1)', $saved['cost'], 'Inline script payload must not survive cost sanitization.' );
		$this->assertStringContainsString( '5 + 1.50', $saved['cost'], 'Math formula syntax must be preserved in cost — must not be coerced to float.' );
	}
}
