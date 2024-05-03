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
	protected function setUp(): void {
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
}
