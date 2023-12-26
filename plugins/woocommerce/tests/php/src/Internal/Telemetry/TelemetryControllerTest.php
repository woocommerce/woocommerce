<?php
/**
 * Test the API controller class that handles the telemetry REST endpoints.
 *
 * @package WooCommerce\Admin\Tests\Internal\Telemetry
 */

namespace Automattic\WooCommerce\Tests\Internal\Telemetry;

use Nette\Utils\DateTime;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Telemetry API controller test.
 *
 * @class TelemetryControllerTest.
 */
class TelemetryControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-telemetry/tracker';

	const MOBILE_USAGE_OPTION_KEY = 'woocommerce_mobile_app_usage';

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->useAdmin();
		$this->resetMobileUsageData();
	}

	/**
	 * Use a user with administrator role.
	 *
	 * @return void
	 */
	public function useAdmin() {
		// Register an administrator user and log in.
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Use a user without any permissions.
	 *
	 * @return void
	 */
	public function useWithoutUser() {
		wp_set_current_user( null );
	}

	/**
	 * Use a user without any permissions.
	 *
	 * @return void
	 */
	public function resetMobileUsageData() {
		update_option( self::MOBILE_USAGE_OPTION_KEY, null );
	}

	/**
	 * Request to the telemetry endpoint.
	 *
	 * @param array $body_params Parameters in the request body.
	 * @return mixed
	 */
	private function request( $body_params ) {
		$request = new WP_REST_Request( 'POST', self::ENDPOINT );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body_params( $body_params );
		$response = $this->server->dispatch( $request );

		return $response;
	}

	/**
	 * Test to confirm that null is returned on success.
	 *
	 * @return void
	 */
	public function test_response_is_null_on_success() {
		// When.
		$data = $this->request(
			array(
				'platform'          => 'ios',
				'version'           => '14.8',
				'installation_date' => '2023-08-08T03:38:50Z',
			)
		)->get_data();

		// Then.
		$this->assertNull( $data );
	}

	/**
	 * Test 401 error is thrown when there is no logged in user.
	 *
	 * @return void
	 */
	public function test_401_is_returned_without_logged_in_user() {
		// Given.
		$this->useWithoutUser();

		// When.
		$response = $this->request(
			array(
				'platform' => 'ios',
				'version'  => '14.8',
			)
		);

		// Then.
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test to confirm all the data fields are set for the first time.
	 *
	 * @return void
	 */
	public function test_WCTracker_mobile_usage_data_fields_are_all_set_for_the_first_time() {
		// When.
		$this->request(
			array(
				'platform'          => 'ios',
				'version'           => '14.7',
				'installation_date' => '2023-08-08T03:38:50Z',
			)
		)->get_data();

		// Then.
		$new_data = get_option( self::MOBILE_USAGE_OPTION_KEY );
		// The installation date should be converted to the GMT date format 'c' to match other date fields.
		$this->assertEquals( '2023-08-08T03:38:50+00:00', $new_data['ios']['installation_date'] );
		$this->assertEquals( '14.7', $new_data['ios']['version'] );
		$this->assertEquals( $new_data['ios']['last_used'], $new_data['ios']['first_used'] );
		$this->assertFalse( isset( $new_data['android'] ) );
	}

	/**
	 * Test to confirm that `installation_date` is only set once per platform.
	 *
	 * @return void
	 */
	public function test_WCTracker_mobile_usage_installation_date_is_not_overwritten_when_it_was_previously_set() {
		// Given.
		$existing_data = array(
			'ios' => array(
				'version'           => '14.8',
				'installation_date' => '2023-08-08T03:38:50+00:00',
			),
		);
		update_option( self::MOBILE_USAGE_OPTION_KEY, $existing_data );

		// When.
		$this->request(
			array(
				'platform'          => 'ios',
				'version'           => '14.7',
				'installation_date' => '2023-03-08T03:38:50Z',
			)
		)->get_data();

		// Then.
		$new_data = get_option( self::MOBILE_USAGE_OPTION_KEY );
		$this->assertEquals( '2023-08-08T03:38:50+00:00', $new_data['ios']['installation_date'] );
		$this->assertFalse( isset( $new_data['android'] ) );
	}

	/**
	 * Test to confirm that `installation_date` is not set when it is not included in the request.
	 *
	 * @return void
	 */
	public function test_WCTracker_mobile_usage_installation_date_is_not_set_when_it_is_not_in_the_request() {
		// Given.
		$existing_data = array(
			'ios' => array(
				'version' => '14.8',
			),
		);
		update_option( self::MOBILE_USAGE_OPTION_KEY, $existing_data );

		// When.
		$this->request(
			array(
				'platform' => 'ios',
				'version'  => '14.7',
			)
		)->get_data();

		// Then.
		$new_data = get_option( self::MOBILE_USAGE_OPTION_KEY );
		$this->assertTrue( isset( $new_data['ios'] ) );
		$this->assertFalse( isset( $new_data['ios']['installation_date'] ) );
		$this->assertFalse( isset( $new_data['android'] ) );
	}

	/**
	 * Test to confirm that `first_used` is only set once per platform.
	 *
	 * @return void
	 */
	public function test_WCTracker_mobile_usage_first_used_is_not_overwritten_when_it_was_previously_set() {
		// Given.
		$existing_data = array(
			'ios' => array(
				'version'    => '14.8',
				'first_used' => '2023-08-08T14:58:33+00:00',
			),
		);
		update_option( self::MOBILE_USAGE_OPTION_KEY, $existing_data );

		// When.
		$this->request(
			array(
				'platform' => 'ios',
				'version'  => '14.9',
			)
		)->get_data();

		// Then.
		$new_data = get_option( self::MOBILE_USAGE_OPTION_KEY );
		$this->assertEquals( '2023-08-08T14:58:33+00:00', $new_data['ios']['first_used'] );
		$this->assertFalse( isset( $new_data['android'] ) );
	}

	/**
	 * Test to confirm that `last_used` and `version` are updated when the new data has a higher version.
	 *
	 * @return void
	 */
	public function test_WCTracker_mobile_usage_last_used_and_version_are_updated_when_they_were_previously_set_and_version_is_higher() {
		// Given.
		$existing_data = array(
			'ios' => array(
				'version'   => '14.8',
				'last_used' => '2023-08-06T14:58:33+00:00',
			),
		);
		update_option( self::MOBILE_USAGE_OPTION_KEY, $existing_data );

		// When.
		$this->request(
			array(
				'platform' => 'ios',
				'version'  => '14.9',
			)
		)->get_data();

		// Then.
		$new_data = get_option( self::MOBILE_USAGE_OPTION_KEY );
		$this->assertEquals( '14.9', $new_data['ios']['version'] );
		$this->assertGreaterThan( new \DateTime( '2023-08-06T14:58:33+00:00' ), new \DateTime( $new_data['ios']['last_used'] ) );
		$this->assertFalse( isset( $new_data['android'] ) );
	}

	/**
	 * Test to confirm that `last_used` and `version` are not updated when the new data has a lower version.
	 *
	 * @return void
	 */
	public function test_WCTracker_mobile_usage_last_used_and_version_are_not_updated_when_they_were_previously_set_and_version_is_lower() {
		// Given.
		$existing_data = array(
			'ios' => array(
				'version'   => '14.8',
				'last_used' => '2023-08-06T14:58:33+00:00',
			),
		);
		update_option( self::MOBILE_USAGE_OPTION_KEY, $existing_data );

		// When.
		$this->request(
			array(
				'platform' => 'ios',
				'version'  => '14.7',
			)
		)->get_data();

		// Then.
		$new_data = get_option( self::MOBILE_USAGE_OPTION_KEY );
		$this->assertEquals( '14.8', $new_data['ios']['version'] );
		$this->assertEquals( '2023-08-06T14:58:33+00:00', $new_data['ios']['last_used'] );
		$this->assertFalse( isset( $new_data['android'] ) );
	}
}
