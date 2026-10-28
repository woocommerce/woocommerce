<?php

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Integrations\WPConsentAPI;
use Automattic\WooCommerce\Internal\Orders\OrderAttributionController;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Closure;
use WC_Logger;
use WC_Order;
use WP_UnitTestCase;

/**
 * Tests for OrderAttributionControllerTest.
 *
 * @since 8.5.0
 */
class OrderAttributionControllerTest extends WP_UnitTestCase {

	/**
	 * The class instance being tested.
	 *
	 * @var OrderAttributionController
	 */
	protected OrderAttributionController $attribution_class;

	/**
	 * Sets up the fixture, for example, open a network connection.
	 *
	 * This method is called before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->attribution_class = new OrderAttributionController();

		/** @var MockableLegacyProxy $legacy_proxy */
		$legacy_proxy = wc_get_container()->get( LegacyProxy::class );

		$feature_mock = $this->getMockBuilder( FeaturesController::class )
			->onlyMethods( array( 'feature_is_enabled' ) )
			->getMock();
		$feature_mock->method( 'feature_is_enabled' )
			->with( 'order_attribution' )
			->willReturn( true );

		$wp_consent_mock = $this->getMockBuilder( WPConsentAPI::class )
			->onlyMethods( array( 'register' ) )
			->getMock();

		$logger_mock = $this->getMockBuilder( WC_Logger::class )
			->onlyMethods( array( 'log' ) )
			->getMock();

		$this->attribution_class->init( $legacy_proxy, $feature_mock, $wp_consent_mock, $logger_mock );
	}

	/**
	 * Tears down the fixture, for example, close a network connection.
	 *
	 * This method is called after each test to reset static state.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Reset the static flag between tests using reflection.
		$reflection = new \ReflectionClass( OrderAttributionController::class );
		$property   = $reflection->getProperty( 'is_stamp_html_called' );
		$property->setAccessible( true );
		$property->setValue( null, false );

		parent::tearDown();
	}

	/**
	 * Tests the output_origin_column method.
	 *
	 * @return void
	 */
	public function test_output_origin_column() {

		// Define the expected output for each test case.
		$test_cases = array(
			array(
				'source_type'     => 'utm',
				'source'          => 'example',
				'expected_output' => 'Source: Example',
			),
			array(
				'source_type'     => 'organic',
				'source'          => 'example',
				'expected_output' => 'Organic: Example',
			),
			array(
				'source_type'     => 'referral',
				'source'          => 'example',
				'expected_output' => 'Referral: Example',
			),
			array(
				'source_type'     => 'typein',
				'source'          => '(direct)',
				'expected_output' => 'Direct',
			),
			array(
				'source_type'     => 'admin',
				'source'          => '',
				'expected_output' => 'Web admin',
			),
			array(
				'source_type'     => 'mobile_app',
				'source'          => '',
				'expected_output' => 'Mobile app',
			),
			array(
				'source_type'     => 'pos',
				'source'          => '',
				'expected_output' => 'Point of Sale',
			),
			array(
				'source_type'     => '',
				'source'          => '',
				'expected_output' => 'Unknown',
			),
		);

		$anon_test = Closure::bind(
			function ( $order ) {
				$this->output_origin_column( $order );
			},
			$this->attribution_class,
			$this->attribution_class
		);

		foreach ( $test_cases as $test_case ) {
			// Create a mock WC_Order object.
			$order = $this->getMockBuilder( WC_Order::class )
				->onlyMethods( array( 'get_meta' ) )
				->getMock();
			$order->method( 'get_meta' )
				->willReturnOnConsecutiveCalls( $test_case['source_type'], $test_case['source'] );

			// Capture the output.
			ob_start();
			$anon_test( $order );
			$output = ob_get_clean();

			$this->assertEquals( $test_case['expected_output'], $output );
		}
	}

	/**
	 * Data provider for test_output_origin_column_uses_filtered_label.
	 *
	 * @return array[] Source type, source, filtered label, expected output.
	 */
	public function provider_filtered_origin_labels(): array {
		return array(
			'plain label, custom type'          => array( 'trade-show', '', 'Trade show', 'Trade show' ),
			// A placeholder is still filled with the formatted source, which is "Unknown" for a custom type.
			'%s placeholder, custom type'       => array( 'trade-show', 'booth-12', 'Trade show: %s', 'Trade show: Unknown' ),
			'positional placeholder'            => array( 'trade-show', '', 'Trade show: %1$s', 'Trade show: Unknown' ),
			'width-qualified placeholder'       => array( 'trade-show', '', '[%10s]', '[   Unknown]' ),
			'left-aligned width placeholder'    => array( 'trade-show', '', '[%-10s]', '[Unknown   ]' ),
			'space flag with width placeholder' => array( 'trade-show', '', '[% 10s]', '[   Unknown]' ),
			'custom padding placeholder'        => array( 'trade-show', '', "[%'.10s]", '[...Unknown]' ),
			'empty label falls back'            => array( 'trade-show', '', '', 'Unknown' ),
			'plain label, built-in type'        => array( 'utm', 'example', 'Campaign', 'Campaign' ),
			// A lone percent sign is literal text and must not reach sprintf().
			'lone percent sign'                 => array( 'trade-show', '', '100% organic', '100% organic' ),
			'percent followed by a space and s' => array( 'trade-show', '', '50% sale', '50% sale' ),
			// An escaped percent sign keeps sprintf() semantics, as every label had before.
			'escaped percent sign'              => array( 'trade-show', '', '100%% organic', '100% organic' ),
			'escaped percent before s'          => array( 'trade-show', '', 'Save %%s today', 'Save %s today' ),
			'escaped percent and a placeholder' => array( 'trade-show', '', '100%% from %s', '100% from Unknown' ),
		);
	}

	/**
	 * @testdox A filtered label is used as the whole origin unless it is a sprintf() format string.
	 * @dataProvider provider_filtered_origin_labels
	 *
	 * @param string $source_type     Source type meta value.
	 * @param string $source          Source meta value.
	 * @param string $label           Label returned by the filter.
	 * @param string $expected_output Expected column output.
	 */
	public function test_output_origin_column_uses_filtered_label( string $source_type, string $source, string $label, string $expected_output ): void {
		$anon_test = Closure::bind(
			function ( $order ) {
				$this->output_origin_column( $order );
			},
			$this->attribution_class,
			$this->attribution_class
		);

		$order = $this->getMockBuilder( WC_Order::class )
			->onlyMethods( array( 'get_meta' ) )
			->getMock();
		$order->method( 'get_meta' )
			->willReturnOnConsecutiveCalls( $source_type, $source );

		$label_filter = function () use ( $label ) {
			return $label;
		};
		add_filter( 'wc_order_attribution_origin_label', $label_filter );

		ob_start();
		$anon_test( $order );
		$output = ob_get_clean();

		remove_filter( 'wc_order_attribution_origin_label', $label_filter );

		$this->assertSame( $expected_output, $output );
	}

	/**
	 * Tests that stamp_html_element outputs the correct HTML element.
	 *
	 * @return void
	 */
	public function test_stamp_html_element_outputs_correct_html() {
		ob_start();
		$this->attribution_class->stamp_html_element();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<wc-order-attribution-inputs>', $output );
		$this->assertStringContainsString( '</wc-order-attribution-inputs>', $output );
	}

	/**
	 * Tests that stamp_html_element respects the single-output filter.
	 *
	 * @return void
	 */
	public function test_stamp_html_element_respects_single_output_filter() {
		// Enable single-output mode via filter.
		add_filter( 'wc_order_attribution_allow_multiple_elements', '__return_false' );

		ob_start();
		$this->attribution_class->stamp_html_element();
		$this->attribution_class->stamp_html_element(); // Second call should be suppressed.
		$output = ob_get_clean();

		// Should only contain one instance.
		$this->assertEquals( 1, substr_count( $output, '<wc-order-attribution-inputs>' ) );

		remove_filter( 'wc_order_attribution_allow_multiple_elements', '__return_false' );
	}

	/**
	 * Tests that stamp_html_element allows multiple outputs by default.
	 *
	 * @return void
	 */
	public function test_stamp_html_element_allows_multiple_outputs_by_default() {
		ob_start();
		$this->attribution_class->stamp_html_element();
		$this->attribution_class->stamp_html_element(); // Second call should also output.
		$output = ob_get_clean();

		// Should contain two instances.
		$this->assertEquals( 2, substr_count( $output, '<wc-order-attribution-inputs>' ) );
	}

	/**
	 * Tests that the deprecated method calls the main method correctly.
	 *
	 * @return void
	 */
	public function test_deprecated_method_calls_main_method() {
		$this->setExpectedDeprecated( 'Automattic\WooCommerce\Internal\Orders\OrderAttributionController::stamp_checkout_html_element_once' );

		ob_start();
		$this->attribution_class->stamp_checkout_html_element_once();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<wc-order-attribution-inputs>', $output );
		$this->assertStringContainsString( '</wc-order-attribution-inputs>', $output );
	}

	/**
	 * Tests that the static flag is reset between test cases.
	 *
	 * This test ensures our tearDown properly resets the static state.
	 *
	 * @return void
	 */
	public function test_static_flag_isolation_between_tests() {
		// Enable single-output mode.
		add_filter( 'wc_order_attribution_allow_multiple_elements', '__return_false' );

		// First call should output.
		ob_start();
		$this->attribution_class->stamp_html_element();
		$output1 = ob_get_clean();
		$this->assertStringContainsString( '<wc-order-attribution-inputs>', $output1 );

		// Second call should be suppressed.
		ob_start();
		$this->attribution_class->stamp_html_element();
		$output2 = ob_get_clean();
		$this->assertEmpty( $output2 );

		remove_filter( 'wc_order_attribution_allow_multiple_elements', '__return_false' );
	}

	/**
	 * Tests that the static flag is reset on each on_init call (simulating new requests).
	 *
	 * This ensures proper behavior in persistent PHP environments like PHP-FPM.
	 *
	 * @return void
	 */
	public function test_static_flag_resets_on_each_request() {
		// Enable single-output mode.
		add_filter( 'wc_order_attribution_allow_multiple_elements', '__return_false' );

		// Simulate first request - output should work.
		$this->attribution_class->on_init();
		ob_start();
		$this->attribution_class->stamp_html_element();
		$output1 = ob_get_clean();
		$this->assertStringContainsString( '<wc-order-attribution-inputs>', $output1 );

		// Simulate second request - on_init resets the flag.
		$this->attribution_class->on_init();
		ob_start();
		$this->attribution_class->stamp_html_element();
		$output2 = ob_get_clean();
		$this->assertStringContainsString( '<wc-order-attribution-inputs>', $output2, 'Output should work on second request after on_init reset' );

		// Within same request, second call should be suppressed.
		ob_start();
		$this->attribution_class->stamp_html_element();
		$output3 = ob_get_clean();
		$this->assertEmpty( $output3, 'Second call within same request should be suppressed' );

		remove_filter( 'wc_order_attribution_allow_multiple_elements', '__return_false' );
	}

	/**
	 * @testdox Should pass the tracking field paths to sourcebuster.js as cookie fields by default.
	 */
	public function test_cookie_fields_default_to_tracking_field_paths(): void {
		$cookie_fields = $this->get_localized_script_data()['params']['cookieFields'];

		$this->assertSame(
			array(
				'current.typ',
				'current_add.rf',
				'current.cmp',
				'current.src',
				'current.mdm',
				'current.cnt',
				'current.id',
				'current.trm',
				'current.plt',
				'current.fmt',
				'current.tct',
				'current_add.ep',
				'current_add.fd',
				'session.pgs',
				'udata.vst',
				'udata.uag',
			),
			$cookie_fields,
			'Cookie fields should match the paths of the default tracking fields.'
		);
	}

	/**
	 * @testdox Should derive the cookie fields from the filtered tracking fields.
	 */
	public function test_cookie_fields_follow_filtered_tracking_fields(): void {
		$filter = function ( $fields ) {
			unset( $fields['utm_content'] );
			$fields['first_source'] = 'first.src';
			return $fields;
		};
		add_filter( 'wc_order_attribution_tracking_fields', $filter );
		$set_fields = new \ReflectionMethod( OrderAttributionController::class, 'set_fields_and_prefix' );
		$set_fields->setAccessible( true );
		$set_fields->invoke( $this->attribution_class );

		$data = $this->get_localized_script_data();

		$this->assertNotContains( 'current.cnt', $data['params']['cookieFields'], 'A removed tracking field should not be a cookie field.' );
		$this->assertContains( 'first.src', $data['params']['cookieFields'], 'An added tracking field should be a cookie field.' );
		$this->assertSame( 'first.src', $data['fields']['first_source'], 'The tracking fields should still be passed to the script.' );
	}

	/**
	 * Data provider for test_cookie_fields_filter.
	 *
	 * @return array[]
	 */
	public function provider_cookie_fields_filter(): array {
		return array(
			'added first.* paths'        => array( array( 'current.typ', 'first.typ', 'first.src', 'first.mdm' ), array( 'current.typ', 'first.typ', 'first.src', 'first.mdm' ) ),
			'empty array'                => array( array(), array() ),
			'duplicates and non-strings' => array( array( 'current.typ', 42, 'current.typ', null, 'session.pgs' ), array( 'current.typ', 'session.pgs' ) ),
		);
	}

	/**
	 * @testdox Should fall back to the default cookie fields when the filter returns only non-strings.
	 */
	public function test_cookie_fields_filter_ignores_non_string_list(): void {
		$default = $this->get_localized_script_data()['params']['cookieFields'];
		add_filter(
			'wc_order_attribution_cookie_fields',
			function () {
				return array( 42, null );
			}
		);

		$this->assertSame( $default, $this->get_localized_script_data()['params']['cookieFields'] );
	}

	/**
	 * @testdox Should not store empty or (none) values submitted from the cookies.
	 */
	public function test_source_values_skip_empty_and_none_values(): void {
		$raw_values = array_fill_keys( $this->attribution_class->get_field_names(), '' );

		$raw_values['source_type']  = 'utm';
		$raw_values['utm_source']   = 'newsletter';
		$raw_values['utm_campaign'] = '(none)';

		$method = new \ReflectionMethod( OrderAttributionController::class, 'get_source_values' );
		$method->setAccessible( true );

		$this->assertSame(
			array(
				'source_type' => 'utm',
				'utm_source'  => 'newsletter',
			),
			$method->invoke( $this->attribution_class, $raw_values )
		);
	}

	/**
	 * @testdox Should pass the filtered cookie fields to sourcebuster.js.
	 *
	 * @dataProvider provider_cookie_fields_filter
	 *
	 * @param array $filtered The value returned by the filter.
	 * @param array $expected The expected cookie fields.
	 */
	public function test_cookie_fields_filter( array $filtered, array $expected ): void {
		add_filter(
			'wc_order_attribution_cookie_fields',
			function () use ( $filtered ) {
				return $filtered;
			}
		);

		$this->assertSame( $expected, $this->get_localized_script_data()['params']['cookieFields'] );
	}

	/**
	 * @testdox Should fall back to the default cookie fields when the filter does not return an array.
	 */
	public function test_cookie_fields_filter_ignores_non_array(): void {
		$default = $this->get_localized_script_data()['params']['cookieFields'];
		add_filter( 'wc_order_attribution_cookie_fields', '__return_false' );

		$this->assertSame( $default, $this->get_localized_script_data()['params']['cookieFields'] );
	}

	/**
	 * Enqueue the frontend scripts and return the data localized for `wc-order-attribution`.
	 *
	 * @return array
	 */
	private function get_localized_script_data(): array {
		wp_deregister_script( 'wc-order-attribution' );
		wp_deregister_script( 'sourcebuster-js' );

		$method = new \ReflectionMethod( OrderAttributionController::class, 'enqueue_scripts_and_styles' );
		$method->setAccessible( true );
		$method->invoke( $this->attribution_class );

		$data = wp_scripts()->get_data( 'wc-order-attribution', 'data' );
		wp_deregister_script( 'wc-order-attribution' );
		wp_deregister_script( 'sourcebuster-js' );

		$this->assertIsString( $data, 'The script should have localized data.' );
		$this->assertSame( 1, preg_match( '/var wc_order_attribution = (.*);/', $data, $matches ), 'The script should localize wc_order_attribution.' );

		return json_decode( $matches[1], true );
	}
}
