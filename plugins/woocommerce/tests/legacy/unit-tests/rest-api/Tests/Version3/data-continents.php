<?php
/**
 * Tests for the Data Continents REST API.
 *
 * @package WooCommerce\Tests\API
 */

/**
 * Class Data_Continents.
 */
class Data_Continents extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Data_Continents_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/data/continents', $routes );
		$this->assertArrayHasKey( '/wc/v3/data/continents/(?P<location>[\w-]+)', $routes );
	}

	/**
	 * Test getting a single continent.
	 */
	public function test_get_continent() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/data/continents/eu' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'EU', $data['code'] );
		$this->assertEquals( 'Europe', $data['name'] );
		$this->assertNotEmpty( $data['countries'] );
	}

	/**
	 * Country `name` must be the country's display name, not the currency name
	 * derived from locale-info.php (regression test for #31853 / RSMAPGJ-391).
	 */
	public function test_country_name_is_not_currency_name() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/data/continents/eu' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$countries = WC()->countries->get_countries();

		$asserted_any = false;
		foreach ( $data['countries'] as $country ) {
			$this->assertArrayHasKey( 'code', $country );
			$this->assertArrayHasKey( 'name', $country );

			// The country's `name` must equal the canonical country name from WC_Countries,
			// not e.g. the currency name like "Euro".
			if ( isset( $countries[ $country['code'] ] ) ) {
				$this->assertSame(
					$countries[ $country['code'] ],
					$country['name'],
					sprintf( 'Country %s should have its country name, not the currency name.', $country['code'] )
				);
				$asserted_any = true;
			}

			$this->assertNotEquals(
				'Euro',
				$country['name'],
				sprintf( 'Country %s name must not be the currency name "Euro".', $country['code'] )
			);
		}

		$this->assertTrue( $asserted_any, 'Expected to assert country names against WC_Countries for at least one country.' );
	}

	/**
	 * Locale-derived fields (currency_code, decimal_sep, etc.) must still be present.
	 */
	public function test_locale_fields_are_present_for_known_country() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/data/continents/eu' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$ad = null;
		foreach ( $data['countries'] as $country ) {
			if ( 'AD' === $country['code'] ) {
				$ad = $country;
				break;
			}
		}

		$this->assertNotNull( $ad, 'Expected AD to be present in the EU continent response.' );
		$this->assertSame( 'EUR', $ad['currency_code'] );
		$this->assertSame( 'right_space', $ad['currency_pos'] );
		$this->assertSame( 'cm', $ad['dimension_unit'] );
		$this->assertSame( 'kg', $ad['weight_unit'] );
		$this->assertArrayNotHasKey( 'singular', $ad, 'Currency `singular` field must not leak into the country response.' );
		$this->assertArrayNotHasKey( 'plural', $ad, 'Currency `plural` field must not leak into the country response.' );
	}

	/**
	 * Test getting all continents.
	 */
	public function test_get_continents() {
		wp_set_current_user( $this->user );

		$response   = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/data/continents' ) );
		$continents = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertNotEmpty( $continents );

		$codes = wp_list_pluck( $continents, 'code' );
		$this->assertContains( 'EU', $codes );
	}

	/**
	 * Test that an invalid continent returns 404.
	 */
	public function test_get_continent_invalid() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/data/continents/zz' ) );

		$this->assertEquals( 404, $response->get_status() );
	}
}
