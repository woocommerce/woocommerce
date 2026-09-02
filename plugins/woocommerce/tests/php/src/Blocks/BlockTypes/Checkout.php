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
	 * Sequence for unique Checkout mock block names.
	 *
	 * @var int
	 */
	private $checkout_mock_sequence = 0;

	/**
	 * Set up the test. Creates a AssetDataRegistryMock.
	 *
	 * @return void
	 * @throws \Exception If the API class is not registered with container.
	 */
	protected function setUp(): void {
		parent::setUp();

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
	 * @testdox Exposes whether store shipping and ordinary shipping methods are available.
	 */
	public function test_enqueue_data_exposes_shipping_topology(): void {
		global $wpdb;

		$original_ship_to_countries = get_option( 'woocommerce_ship_to_countries', false );
		$original_pickup_settings   = get_option( 'woocommerce_pickup_location_settings', false );
		$original_method_states     = $wpdb->get_results(
			"SELECT instance_id, is_enabled FROM {$wpdb->prefix}woocommerce_shipping_zone_methods",
			ARRAY_A
		);
		$shipping_zone              = new \WC_Shipping_Zone();

		try {
			foreach ( $original_method_states as $method_state ) {
				$wpdb->update(
					"{$wpdb->prefix}woocommerce_shipping_zone_methods",
					array( 'is_enabled' => '0' ),
					array( 'instance_id' => $method_state['instance_id'] )
				);
			}

			update_option( 'woocommerce_ship_to_countries', 'all' );
			update_option(
				'woocommerce_pickup_location_settings',
				array(
					'enabled'    => 'yes',
					'title'      => 'Pickup',
					'cost'       => '',
					'tax_status' => 'taxable',
				)
			);

			$shipping_zone->set_zone_name( 'Checkout asset-data test' );
			$shipping_zone->save();
			$shipping_zone->add_shipping_method( 'flat_rate' );
			$this->flush_shipping_method_cache();

			$data = $this->get_checkout_asset_data();
			$this->assertFalse(
				\WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/checkout-shipping-topology-1' ),
				'The generated Checkout mock block should be unregistered after collecting asset data.'
			);
			$this->assertTrue( $data['shippingMethodsExist'], 'An enabled ordinary shipping method should be exposed.' );
			$this->assertTrue( $data['shippingEnabled'], 'Shipping should be exposed as enabled when the store ships.' );

			$shipping_zone->delete( true );
			$this->flush_shipping_method_cache();

			$data = $this->get_checkout_asset_data();
			$this->assertFalse(
				\WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/checkout-shipping-topology-2' ),
				'The generated Checkout mock block should be unregistered after collecting asset data.'
			);
			$this->assertFalse( $data['shippingMethodsExist'], 'Local pickup without an ordinary shipping method should not count as ordinary shipping.' );
			$this->assertTrue( $data['shippingEnabled'], 'Store shipping remains enabled for the pickup-only topology.' );

			update_option( 'woocommerce_ship_to_countries', 'disabled' );
			$this->flush_shipping_method_cache();

			$data = $this->get_checkout_asset_data();
			$this->assertFalse(
				\WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/checkout-shipping-topology-3' ),
				'The generated Checkout mock block should be unregistered after collecting asset data.'
			);
			$this->assertFalse( $data['shippingMethodsExist'], 'No ordinary shipping method should be exposed when none is configured.' );
			$this->assertFalse( $data['shippingEnabled'], 'Globally disabled store shipping should be exposed.' );
		} finally {
			if ( $shipping_zone->get_id() ) {
				$shipping_zone->delete( true );
			}

			foreach ( $original_method_states as $method_state ) {
				$wpdb->update(
					"{$wpdb->prefix}woocommerce_shipping_zone_methods",
					array( 'is_enabled' => $method_state['is_enabled'] ),
					array( 'instance_id' => $method_state['instance_id'] )
				);
			}

			false === $original_ship_to_countries
				? delete_option( 'woocommerce_ship_to_countries' )
				: update_option( 'woocommerce_ship_to_countries', $original_ship_to_countries );
			false === $original_pickup_settings
				? delete_option( 'woocommerce_pickup_location_settings' )
				: update_option( 'woocommerce_pickup_location_settings', $original_pickup_settings );
			$this->flush_shipping_method_cache();
		}
	}

	/**
	 * Get data registered by a fresh Checkout block instance.
	 *
	 * @return array<string, mixed>
	 */
	private function get_checkout_asset_data(): array {
		$registry            = new AssetDataRegistryMock( $this->asset_api );
		$block_type_registry = \WP_Block_Type_Registry::get_instance();
		++$this->checkout_mock_sequence;
		$block_name      = 'checkout-shipping-topology-' . $this->checkout_mock_sequence;
		$full_block_name = 'woocommerce/' . $block_name;

		try {
			$checkout = new CheckoutMock(
				$this->asset_api,
				$registry,
				$this->integration_registry,
				$block_name
			);
			$checkout->mock_enqueue_data();

			return $registry->get();
		} finally {
			if ( $block_type_registry->is_registered( $full_block_name ) ) {
				$block_type_registry->unregister( $full_block_name );
			}
		}
	}

	/**
	 * Flush cached shipping-method state after changing topology.
	 */
	private function flush_shipping_method_cache(): void {
		\WC_Cache_Helper::get_transient_version( 'shipping', true );
		delete_transient( 'wc_shipping_method_count' );
		WC()->shipping()->load_shipping_methods();
	}

	/**
	 * Overrides the WC logger.
	 *
	 * @return mixed
	 */
	public function override_wc_logger() {
		return $this->mock_logger;
	}
}
