<?php
/**
 * Tests for the Pixel_Builder class.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use WorDBless\BaseTestCase;
use WP_Error;

/**
 * Tests for the Pixel_Builder class.
 */
class Pixel_Builder_Test extends BaseTestCase {

	/**
	 * Test that TRACKS_PIXEL_URL constant is correct.
	 */
	public function test_tracks_pixel_url_constant(): void {
		$this->assertSame( 'https://pixel.wp.com/t.gif', Pixel_Builder::TRACKS_PIXEL_URL );
	}

	/**
	 * Test that CH_PIXEL_URL constant is correct.
	 */
	public function test_ch_pixel_url_constant(): void {
		$this->assertSame( 'https://pixel.wp.com/w.gif', Pixel_Builder::CH_PIXEL_URL );
	}

	/**
	 * Test that BROWSER_TYPE constant is correct.
	 */
	public function test_browser_type_constant(): void {
		$this->assertSame( 'php-agent', Pixel_Builder::BROWSER_TYPE );
	}

	/**
	 * Test build_timestamp returns a numeric string.
	 */
	public function test_build_timestamp_returns_numeric_string(): void {
		$timestamp = Pixel_Builder::build_timestamp();

		$this->assertIsString( $timestamp );
		$this->assertMatchesRegularExpression( '/^\d+$/', $timestamp );

		// Timestamp should be approximately current time in milliseconds.
		$expected = round( microtime( true ) * 1000 );
		$actual   = (float) $timestamp;

		// Allow 1 second tolerance.
		$this->assertEqualsWithDelta( $expected, $actual, 1000 );
	}

	/**
	 * Test add_request_timestamp_and_nocache appends correct parameters.
	 */
	public function test_add_request_timestamp_and_nocache(): void {
		$pixel  = 'https://pixel.wp.com/t.gif?_en=test_event';
		$result = Pixel_Builder::add_request_timestamp_and_nocache( $pixel );

		$this->assertStringStartsWith( $pixel . '&_rt=', $result );
		$this->assertStringEndsWith( '&_=_', $result );

		// Extract the timestamp.
		$matches = array();
		$this->assertSame( 1, preg_match( '/&_rt=(\d+)&/', $result, $matches ) );
		$this->assertArrayHasKey( 1, $matches );
		$this->assertNotEmpty( $matches[1] );
	}

	/**
	 * Test event_name_is_valid with valid event names.
	 *
	 * @dataProvider valid_event_names_provider
	 * @param string $event_name The event name to test.
	 */
	#[DataProvider( 'valid_event_names_provider' )]
	public function test_event_name_is_valid_with_valid_names( string $event_name ): void {
		$this->assertTrue( Pixel_Builder::event_name_is_valid( $event_name ) );
	}

	/**
	 * Data provider for valid event names.
	 *
	 * @return array
	 */
	public static function valid_event_names_provider(): array {
		return array(
			'simple event'         => array( 'woocommerceanalytics_checkout_started' ),
			'short prefix'         => array( 'woo_event' ),
			'numeric prefix'       => array( 'test123_event' ),
			'underscores in event' => array( 'wcadmin_product_view_click' ),
			'multiple underscores' => array( 'woo_my_custom_event_name' ),
			'numbers in event'     => array( 'woo_event123' ),
		);
	}

	/**
	 * Test event_name_is_valid with invalid event names.
	 *
	 * @dataProvider invalid_event_names_provider
	 * @param string $event_name The event name to test.
	 */
	#[DataProvider( 'invalid_event_names_provider' )]
	public function test_event_name_is_valid_with_invalid_names( string $event_name ): void {
		$this->assertFalse( Pixel_Builder::event_name_is_valid( $event_name ) );
	}

	/**
	 * Data provider for invalid event names.
	 *
	 * @return array
	 */
	public static function invalid_event_names_provider(): array {
		return array(
			'no underscore at all' => array( 'checkoutstarted' ),
			'uppercase letters'    => array( 'Woo_Event' ),
			'spaces'               => array( 'woo_my event' ),
			'special characters'   => array( 'woo_event@test' ),
			'empty string'         => array( '' ),
			'only prefix'          => array( 'woo_' ),
			'hyphen instead'       => array( 'woo-event' ),
		);
	}

	/**
	 * Test prop_name_is_valid with valid property names.
	 *
	 * @dataProvider valid_prop_names_provider
	 * @param string $prop_name The property name to test.
	 */
	#[DataProvider( 'valid_prop_names_provider' )]
	public function test_prop_name_is_valid_with_valid_names( string $prop_name ): void {
		$this->assertTrue( Pixel_Builder::prop_name_is_valid( $prop_name ) );
	}

	/**
	 * Data provider for valid property names.
	 *
	 * @return array
	 */
	public static function valid_prop_names_provider(): array {
		return array(
			'simple name'         => array( 'event_name' ),
			'underscore prefix'   => array( '_en' ),
			'all lowercase'       => array( 'productid' ),
			'numbers'             => array( 'prop123' ),
			'underscore and nums' => array( '_ts' ),
			'long name'           => array( 'this_is_a_very_long_property_name' ),
		);
	}

	/**
	 * Test prop_name_is_valid with invalid property names.
	 *
	 * @dataProvider invalid_prop_names_provider
	 * @param string $prop_name The property name to test.
	 */
	#[DataProvider( 'invalid_prop_names_provider' )]
	public function test_prop_name_is_valid_with_invalid_names( string $prop_name ): void {
		$this->assertFalse( Pixel_Builder::prop_name_is_valid( $prop_name ) );
	}

	/**
	 * Data provider for invalid property names.
	 *
	 * @return array
	 */
	public static function invalid_prop_names_provider(): array {
		return array(
			'uppercase letters'  => array( 'EventName' ),
			'starts with number' => array( '123prop' ),
			'hyphen'             => array( 'event-name' ),
			'spaces'             => array( 'event name' ),
			'special characters' => array( 'event@name' ),
			'empty string'       => array( '' ),
		);
	}

	/**
	 * Test build_tracks_url with valid properties.
	 */
	public function test_build_tracks_url_with_valid_properties(): void {
		$properties = array(
			'_en' => 'woocommerceanalytics_checkout_started',
			'_ts' => '1234567890123',
			'_ut' => 'anon',
			'_ui' => 'test_visitor_id',
		);

		$result = Pixel_Builder::build_tracks_url( $properties );

		$this->assertIsString( $result );
		$this->assertStringStartsWith( Pixel_Builder::TRACKS_PIXEL_URL . '?', $result );
		$this->assertStringContainsString( '_en=woocommerceanalytics_checkout_started', $result );
		$this->assertStringContainsString( 'browser_type=php-agent', $result );
	}

	/**
	 * Test build_tracks_url returns WP_Error when event name is missing.
	 */
	public function test_build_tracks_url_returns_error_without_event_name(): void {
		$properties = array(
			'_ts' => '1234567890123',
		);

		$result = Pixel_Builder::build_tracks_url( $properties );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_event', $result->get_error_code() );
	}

	/**
	 * Test build_tracks_url returns WP_Error with invalid event name.
	 */
	public function test_build_tracks_url_returns_error_with_invalid_event_name(): void {
		$properties = array(
			'_en' => 'InvalidEventName',
		);

		$result = Pixel_Builder::build_tracks_url( $properties );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_event_name', $result->get_error_code() );
	}

	/**
	 * Test build_ch_url builds ClickHouse pixel URL.
	 */
	public function test_build_ch_url_with_valid_properties(): void {
		$properties = array(
			'_en' => 'woocommerceanalytics_checkout_started',
			'_ts' => '1234567890123',
		);

		$result = Pixel_Builder::build_ch_url( $properties );

		$this->assertIsString( $result );
		$this->assertStringStartsWith( Pixel_Builder::CH_PIXEL_URL . '?', $result );
		$this->assertStringContainsString( '_en=woocommerceanalytics_checkout_started', $result );
	}

	/**
	 * Test validate_and_sanitize adds timestamp if missing.
	 */
	public function test_validate_and_sanitize_adds_timestamp(): void {
		$properties = array(
			'_en' => 'woocommerceanalytics_test_event',
		);

		$result = Pixel_Builder::validate_and_sanitize( $properties );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( '_ts', $result );
		$this->assertMatchesRegularExpression( '/^\d+$/', $result['_ts'] );
	}

	/**
	 * Test validate_and_sanitize adds browser_type.
	 */
	public function test_validate_and_sanitize_adds_browser_type(): void {
		$properties = array(
			'_en' => 'woocommerceanalytics_test_event',
		);

		$result = Pixel_Builder::validate_and_sanitize( $properties );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'browser_type', $result );
		$this->assertSame( 'php-agent', $result['browser_type'] );
	}

	/**
	 * Test validate_and_sanitize removes private IP addresses.
	 */
	public function test_validate_and_sanitize_removes_private_ips(): void {
		$properties = array(
			'_en'     => 'woocommerceanalytics_test_event',
			'_via_ip' => '192.168.1.1',
		);

		$result = Pixel_Builder::validate_and_sanitize( $properties );

		$this->assertIsArray( $result );
		$this->assertArrayNotHasKey( '_via_ip', $result );
	}

	/**
	 * Test validate_and_sanitize keeps public IP addresses.
	 */
	public function test_validate_and_sanitize_keeps_public_ips(): void {
		$properties = array(
			'_en'     => 'woocommerceanalytics_test_event',
			'_via_ip' => '203.0.113.195',
		);

		$result = Pixel_Builder::validate_and_sanitize( $properties );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( '_via_ip', $result );
		$this->assertSame( '203.0.113.195', $result['_via_ip'] );
	}

	/**
	 * Test validate_and_sanitize handles indexed arrays.
	 */
	public function test_validate_and_sanitize_handles_indexed_arrays(): void {
		$properties = array(
			'_en'   => 'woocommerceanalytics_test_event',
			'items' => array( 'item1', 'item2', 'item3' ),
		);

		$result = Pixel_Builder::validate_and_sanitize( $properties );

		$this->assertIsArray( $result );
		$this->assertSame( 'item1,item2,item3', $result['items'] );
	}

	/**
	 * Test validate_and_sanitize handles associative arrays as JSON.
	 */
	public function test_validate_and_sanitize_handles_associative_arrays(): void {
		$properties = array(
			'_en'     => 'woocommerceanalytics_test_event',
			'options' => array(
				'key1' => 'value1',
				'key2' => 'value2',
			),
		);

		$result = Pixel_Builder::validate_and_sanitize( $properties );

		$this->assertIsArray( $result );
		$decoded = json_decode( $result['options'], true );
		$this->assertSame(
			array(
				'key1' => 'value1',
				'key2' => 'value2',
			),
			$decoded
		);
	}

	/**
	 * Test validate_and_sanitize handles empty arrays.
	 */
	public function test_validate_and_sanitize_handles_empty_arrays(): void {
		$properties = array(
			'_en'   => 'woocommerceanalytics_test_event',
			'items' => array(),
		);

		$result = Pixel_Builder::validate_and_sanitize( $properties );

		$this->assertIsArray( $result );
		$this->assertSame( '', $result['items'] );
	}

	/**
	 * Test validate_and_sanitize returns error for invalid property name.
	 */
	public function test_validate_and_sanitize_returns_error_for_invalid_prop_name(): void {
		$properties = array(
			'_en'         => 'woocommerceanalytics_test_event',
			'InvalidProp' => 'value',
		);

		$result = Pixel_Builder::validate_and_sanitize( $properties );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_prop_name', $result->get_error_code() );
	}

	/**
	 * Test send_pixel returns true (integration test - doesn't actually send).
	 */
	public function test_send_pixel_returns_true(): void {
		$pixel = Pixel_Builder::TRACKS_PIXEL_URL . '?_en=woocommerceanalytics_test';

		// This test just ensures the method doesn't throw and returns true.
		// The actual HTTP request is non-blocking and we can't easily verify it.
		$result = Pixel_Builder::send_pixel( $pixel );

		$this->assertTrue( $result );
	}

	/**
	 * Test send_pixels_batched with empty array returns true.
	 */
	public function test_send_pixels_batched_with_empty_array(): void {
		$result = Pixel_Builder::send_pixels_batched( array() );

		$this->assertTrue( $result );
	}

	/**
	 * Test send_pixels_batched with valid pixels returns true.
	 */
	#[IgnoreDeprecations]
	public function test_send_pixels_batched_with_valid_pixels(): void {
		$pixels = array(
			Pixel_Builder::TRACKS_PIXEL_URL . '?_en=woocommerceanalytics_test1',
			Pixel_Builder::TRACKS_PIXEL_URL . '?_en=woocommerceanalytics_test2',
		);

		$result = Pixel_Builder::send_pixels_batched( $pixels );

		$this->assertTrue( $result );
	}
}
