<?php

namespace Automattic\WooCommerce\OrderSourceAttribution\Test;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Orders\SourceAttributionController;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Closure;
use WC_Logger;
use WC_Order;
use WP_UnitTestCase;

class AttributionFieldsTest extends WP_UnitTestCase {

	protected SourceAttributionController $attribution_fields_class;

	/**
	 * This method is called before the first test of this test class is run.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
	}

	/**
	 * Sets up the fixture, for example, open a network connection.
	 *
	 * This method is called before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->attribution_fields_class = $this->getMockBuilder( SourceAttributionController::class )->getMock();

		/** @var MockableLegacyProxy $legacy_proxy */
		$legacy_proxy = wc_get_container()->get( LegacyProxy::class );

		$feature_mock = $this->getMockBuilder( FeaturesController::class )
			->onlyMethods( array( 'feature_is_enabled' ) )
			->getMock();
		$feature_mock->method( 'feature_is_enabled' )
			->with( 'order_source_attribution' )
			->willReturn( true );

		$logger_mock = $this->getMockBuilder( WC_Logger::class )
			->onlyMethods( array( 'log' ) )
			->getMock();

		$this->attribution_fields_class->init( $legacy_proxy, $feature_mock, $logger_mock );
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
				'source_type'     => '',
				'source'          => '',
				'expected_output' => 'None',
			),
		);

		$anon_test = Closure::bind(
			function( $order ) {
				$this->output_origin_column( $order );
			},
			$this->attribution_fields_class,
			$this->attribution_fields_class
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
