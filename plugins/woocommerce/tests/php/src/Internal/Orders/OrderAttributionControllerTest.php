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
			function( $order ) {
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
}
