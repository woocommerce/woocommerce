<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\CheckoutMock;

/**
 * Tests for the Checkout block type
 *
 * @since $VID:$
 */
class Checkout extends \WP_UnitTestCase {
	/**
	 * @var AssetDataRegistryMock The asset data registry mock.
	 */
	private $registry;

	/**
	 * @var IntegrationRegistry The integration registry, not used, but required to set up a Checkout block.
	 */
	private $integration_registry;

	/**
	 * @var Api The asset API, not used, but required to set up a Checkout block.
	 */
	private $asset_api;

	/**
	 * Mock logger instance.
	 *
	 * @var \WC_Logger_Interface $mock_logger
	 */
	private $mock_logger;

	/**
	 * Set up the test. Creates a AssetDataRegistryMock.
	 *
	 * @return void
	 * @throws \Exception If the API class is not registered with container.
	 */
	protected function setUp(): void {
		$this->asset_api            = Package::container()->get( API::class );
		$this->registry             = new AssetDataRegistryMock( $this->asset_api );
		$this->integration_registry = new IntegrationRegistry();
		$this->mock_logger          = $this->getMockBuilder( \WC_Logger_Interface::class )->getMock();
		add_filter(
			'woocommerce_logging_class',
			array( $this, 'override_wc_logger' )
		);
	}

	/**
	 * Tear down after test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
		remove_filter( 'woocommerce_logging_class', array( $this, 'override_wc_logger' ) );
	}

	/**
	 * Checks the local pickup title is updated when the Checkout block is saved.
	 * @return void
	 */
	public function test_local_pickup_title_change() {
		$page = array(
			'name'    => 'blocks-page',
			'title'   => 'Checkout',
			'content' => '',
		);

		// Sets the page as the checkout page so the code to update the setting correctly processes it.
		$page_id         = wc_create_page( $page['name'], 'woocommerce_checkout_page_id', $page['title'], $page['content'] );
		$updated_content = '<!-- wp:woocommerce/checkout {"showOrderNotes":false} --> <div class="wp-block-woocommerce-checkout is-loading"> <!-- wp:woocommerce/checkout-shipping-method-block {"localPickupText":"Changed pickup"} --> <div class="wp-block-woocommerce-checkout-shipping-method-block"></div> <!-- /wp:woocommerce/checkout-shipping-method-block --></div> <!-- /wp:woocommerce/checkout -->';
		wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => $updated_content,
			]
		);

		// Now the post was saved with an updated localPickupText attribute, the title on Local Pickup settings should be updated.
		$pickup_location_settings = LocalPickupUtils::get_local_pickup_settings( 'edit' );
		$this->assertEquals( 'Changed pickup', $pickup_location_settings['title'] );

		// Updates the pickup title with the default value.
		$updated_content = '<!-- wp:woocommerce/checkout {"showOrderNotes":false} --> <div class="wp-block-woocommerce-checkout is-loading"> <!-- wp:woocommerce/checkout-shipping-method-block {"localPickupText":"Pickup"} --> <div class="wp-block-woocommerce-checkout-shipping-method-block"></div> <!-- /wp:woocommerce/checkout-shipping-method-block --></div> <!-- /wp:woocommerce/checkout -->';
		wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => $updated_content,
			]
		);

		// Now the post was saved with an updated localPickupText attribute, the title on Local Pickup settings should be updated.
		$pickup_location_settings = LocalPickupUtils::get_local_pickup_settings( 'edit' );
		$this->assertEquals( 'Pickup', $pickup_location_settings['title'] );

		// Updates the pickup title with an empty value.
		$updated_content = '<!-- wp:woocommerce/checkout {"showOrderNotes":false} --> <div class="wp-block-woocommerce-checkout is-loading"> <!-- wp:woocommerce/checkout-shipping-method-block {"localPickupText":""} --> <div class="wp-block-woocommerce-checkout-shipping-method-block"></div> <!-- /wp:woocommerce/checkout-shipping-method-block --></div> <!-- /wp:woocommerce/checkout -->';
		wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => $updated_content,
			]
		);

		// Now the post was saved with an updated localPickupText attribute, the title on Local Pickup settings should be updated.
		$pickup_location_settings = LocalPickupUtils::get_local_pickup_settings( 'edit' );
		$this->assertEquals( 'Pickup', $pickup_location_settings['title'] );

		// Updates the pickup title back to "Changed pickup" to test AssetDataRegistry.
		$updated_content = '<!-- wp:woocommerce/checkout {"showOrderNotes":false} --> <div class="wp-block-woocommerce-checkout is-loading"> <!-- wp:woocommerce/checkout-shipping-method-block {"localPickupText":"Changed pickup"} --> <div class="wp-block-woocommerce-checkout-shipping-method-block"></div> <!-- /wp:woocommerce/checkout-shipping-method-block --></div> <!-- /wp:woocommerce/checkout -->';
		wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => $updated_content,
			]
		);

		// Create a new Checkout block class with the mocked AssetDataRegistry. This is so we can inspect it after the change.
		$checkout = new CheckoutMock( $this->asset_api, $this->registry, $this->integration_registry, 'checkout-mock' );
		$checkout->mock_enqueue_data();

		$data_from_registry = $this->registry->get();
		$this->assertEquals( 'Changed pickup', $data_from_registry['localPickupText'] );
		wp_delete_post( $page_id );
	}

	/**
	 * Overrides the WC logger.
	 *
	 * @return mixed
	 */
	public function override_wc_logger() {
		return $this->mock_logger;
	}

	/**
	 * Tests that hasCollectableMethods is set based on collectable shipping methods.
	 *
	 * @return void
	 */
	public function test_has_collectable_methods_is_set_based_on_shipping_methods() {
		// Ensure no pickup locations are configured.
		delete_option( 'pickup_location_pickup_locations' );

		// Create a new Checkout block class with the mocked AssetDataRegistry.
		$checkout = new CheckoutMock( $this->asset_api, $this->registry, $this->integration_registry, 'checkout-mock' );
		$checkout->mock_enqueue_data();

		$data_from_registry = $this->registry->get();

		// The collectableMethodIds should include at least the built-in local_pickup method.
		$this->assertArrayHasKey( 'collectableMethodIds', $data_from_registry );
		$this->assertIsArray( $data_from_registry['collectableMethodIds'] );

		// hasCollectableMethods should be a boolean.
		$this->assertArrayHasKey( 'hasCollectableMethods', $data_from_registry );
		$this->assertIsBool( $data_from_registry['hasCollectableMethods'] );

		// With no pickup locations and no custom pickup methods, hasCollectableMethods should be false.
		$this->assertFalse( $data_from_registry['hasCollectableMethods'] );
	}

	/**
	 * Tests that hasCollectableMethods is true when pickup locations are configured.
	 *
	 * @return void
	 */
	public function test_has_collectable_methods_with_pickup_locations() {
		// Configure a pickup location.
		update_option(
			'pickup_location_pickup_locations',
			array(
				array(
					'name'    => 'Test Location',
					'address' => array(
						'address_1' => '123 Test St',
						'city'      => 'Test City',
						'state'     => 'CA',
						'postcode'  => '12345',
						'country'   => 'US',
					),
					'details' => 'Test details',
					'enabled' => true,
				),
			)
		);

		// Create a new AssetDataRegistryMock to get fresh data.
		$registry = new AssetDataRegistryMock( $this->asset_api );
		$checkout = new CheckoutMock( $this->asset_api, $registry, $this->integration_registry, 'checkout-mock' );
		$checkout->mock_enqueue_data();

		$data_from_registry = $registry->get();

		// hasCollectableMethods should be true when pickup locations exist.
		$this->assertArrayHasKey( 'hasCollectableMethods', $data_from_registry );
		$this->assertTrue( $data_from_registry['hasCollectableMethods'] );

		// Clean up.
		delete_option( 'pickup_location_pickup_locations' );
	}

	/**
	 * Tests that hasCollectableMethods is true when a custom shipping method with local-pickup support exists.
	 *
	 * @return void
	 */
	public function test_has_collectable_methods_with_custom_pickup_method() {
		// Ensure no pickup locations are configured.
		delete_option( 'pickup_location_pickup_locations' );

		// Create a custom shipping method class with local-pickup support.
		// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
		$custom_pickup_class = new class() extends \WC_Shipping_Method {
			/**
			 * Constructor.
			 *
			 * @param int $instance_id Instance ID.
			 */
			public function __construct( $instance_id = 0 ) {
				$this->id           = 'test_custom_pickup';
				$this->instance_id  = absint( $instance_id );
				$this->method_title = 'Test Custom Pickup';
				$this->supports     = array( 'local-pickup' );
			}
		};
		// phpcs:enable

		// Register the custom shipping method with local-pickup support.
		add_filter(
			'woocommerce_shipping_methods',
			function ( $methods ) use ( $custom_pickup_class ) {
				$methods['test_custom_pickup'] = get_class( $custom_pickup_class );
				return $methods;
			}
		);

		// Clear the shipping method cache to pick up the new method.
		WC()->shipping()->unregister_shipping_methods();

		// Create a new AssetDataRegistryMock to get fresh data.
		$registry = new AssetDataRegistryMock( $this->asset_api );
		$checkout = new CheckoutMock( $this->asset_api, $registry, $this->integration_registry, 'checkout-mock' );
		$checkout->mock_enqueue_data();

		$data_from_registry = $registry->get();

		// The collectableMethodIds should include our custom method.
		$this->assertArrayHasKey( 'collectableMethodIds', $data_from_registry );
		$this->assertContains( 'test_custom_pickup', $data_from_registry['collectableMethodIds'] );

		// hasCollectableMethods should be true because of the custom method.
		$this->assertArrayHasKey( 'hasCollectableMethods', $data_from_registry );
		$this->assertTrue( $data_from_registry['hasCollectableMethods'] );

		// Clean up: remove the filter.
		remove_all_filters( 'woocommerce_shipping_methods' );
		WC()->shipping()->unregister_shipping_methods();
	}
}
