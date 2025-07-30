<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentUtils;

/**
 * FulfillmentUtilsTest class.
 */
class FulfillmentUtilsTest extends \WC_Unit_Test_Case {
	/**
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Tear down the test environment.
	 */
	public static function tearDownAfterClass(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		parent::tearDownAfterClass();
	}

	/**
	 * Test that plugins can extend the order fulfillment statuses.
	 */
	public function test_order_fulfillment_statuses_extension() {
		add_filter(
			'woocommerce_fulfillment_order_fulfillment_statuses',
			function ( $statuses ) {
				$statuses['custom_status'] = __( 'Custom Status', 'woocommerce' );
				return $statuses;
			}
		);

		$statuses = FulfillmentUtils::get_order_fulfillment_statuses();

		// Check that the default statuses are present.
		$this->assertArrayHasKey( 'unfulfilled', $statuses );
		$this->assertArrayHasKey( 'fulfilled', $statuses );
		$this->assertArrayHasKey( 'partially_fulfilled', $statuses );

		// Check that a custom status added by a plugin is present.
		$this->assertArrayHasKey( 'custom_status', $statuses );
	}

	/**
	 * Test that the get_fulfillment_statuses method returns the correct statuses.
	 */
	public function test_get_fulfillment_statuses() {
		add_filter(
			'woocommerce_fulfillment_fulfillment_statuses',
			function ( $statuses ) {
				$statuses['custom_status'] = array(
					'label'            => __( 'Custom Status', 'woocommerce' ),
					'is_fulfilled'     => false,
					'background_color' => '#f0f0f0',
					'text_color'       => '#000000',
				);
				return $statuses;
			}
		);

		$fulfillment_statuses = FulfillmentUtils::get_fulfillment_statuses();
		$this->assertArrayHasKey( 'unfulfilled', $fulfillment_statuses );
		$this->assertArrayHasKey( 'fulfilled', $fulfillment_statuses );
		$this->assertArrayHasKey( 'custom_status', $fulfillment_statuses );
		$this->assertEquals( 'Custom Status', $fulfillment_statuses['custom_status']['label'] );
	}
}
