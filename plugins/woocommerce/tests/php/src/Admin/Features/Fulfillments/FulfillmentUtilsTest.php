<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentUtils;
use Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers\FulfillmentsHelper;

/**
 * FulfillmentUtilsTest class.
 */
class FulfillmentUtilsTest extends \WC_Unit_Test_Case {
	/**
	 * Original value of the fulfillments feature flag.
	 *
	 * @var mixed
	 */
	private static $original_fulfillments_flag;

	/**
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$original_fulfillments_flag = get_option( 'woocommerce_feature_fulfillments_enabled' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Tear down the test environment.
	 */
	public static function tearDownAfterClass(): void {
		if ( false === self::$original_fulfillments_flag ) {
			delete_option( 'woocommerce_feature_fulfillments_enabled' );
		} else {
			update_option( 'woocommerce_feature_fulfillments_enabled', self::$original_fulfillments_flag );
		}
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

	/**
	 * @testdox resolve_provider_name returns slug for known providers.
	 */
	public function test_resolve_provider_name_returns_slug_for_known_providers(): void {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => 123 ),
			array(
				'_items'             => array(
					array(
						'item_id' => 1,
						'qty'     => 1,
					),
				),
				'_shipment_provider' => 'usps',
				'_provider_name'     => 'USPS',
			)
		);

		$reloaded = new Fulfillment( $fulfillment->get_id() );

		$this->assertSame( 'usps', FulfillmentUtils::resolve_provider_name( $reloaded ) );
	}

	/**
	 * @testdox resolve_provider_name returns display name for custom providers.
	 */
	public function test_resolve_provider_name_returns_display_name_for_custom_providers(): void {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => 123 ),
			array(
				'_items'             => array(
					array(
						'item_id' => 1,
						'qty'     => 1,
					),
				),
				'_shipment_provider' => 'other',
				'_provider_name'     => 'My Custom Carrier',
			)
		);

		$reloaded = new Fulfillment( $fulfillment->get_id() );

		$this->assertSame( 'My Custom Carrier', FulfillmentUtils::resolve_provider_name( $reloaded ) );
	}

	/**
	 * @testdox resolve_provider_name falls back to display name when slug is empty (auto-lookup case).
	 */
	public function test_resolve_provider_name_falls_back_to_display_name_when_slug_empty(): void {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => 123 ),
			array(
				'_items'         => array(
					array(
						'item_id' => 1,
						'qty'     => 1,
					),
				),
				'_provider_name' => 'UPS',
			)
		);

		$reloaded = new Fulfillment( $fulfillment->get_id() );

		$this->assertSame( 'UPS', FulfillmentUtils::resolve_provider_name( $reloaded ) );
	}

	/**
	 * @testdox resolve_provider_name returns empty string when no provider info is set.
	 */
	public function test_resolve_provider_name_returns_empty_when_no_provider_info(): void {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => 123 )
		);

		$reloaded = new Fulfillment( $fulfillment->get_id() );

		$this->assertSame( '', FulfillmentUtils::resolve_provider_name( $reloaded ) );
	}
}
