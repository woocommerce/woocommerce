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
	 * Test that the default shipping defining address fields are included in the registry data.
	 *
	 * @return void
	 */
	public function test_default_shipping_fields_in_registry() {
		$checkout = new CheckoutMock( $this->asset_api, $this->registry, $this->integration_registry, 'checkout-mock' );
		$checkout->mock_enqueue_data();

		$data_from_registry = $this->registry->get();
		$this->assertArrayHasKey( 'addressFieldsForShippingRates', $data_from_registry );
		// Assert that this contains the following fields needed for shipping rates.
		$this->assertContains( 'state', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertContains( 'country', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertContains( 'postcode', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertContains( 'city', $data_from_registry['addressFieldsForShippingRates'] );
		// Assert that this not contains the following fields not needed for shipping rates.
		$this->assertNotContains( 'address_1', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertNotContains( 'address_2', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertNotContains( 'first_name', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertNotContains( 'last_name', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertNotContains( 'company', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertNotContains( 'phone', $data_from_registry['addressFieldsForShippingRates'] );
	}

	/**
	 * Test that the shipping fields filter is applied correctly when valid array of strings is provided.
	 *
	 * @return void
	 */
	public function test_valid_shipping_fields_filter() {
		$checkout = new CheckoutMock( $this->asset_api, $this->registry, $this->integration_registry, 'checkout-mock' );

		// Check that the shipping fields filter does not warn when applied with an array of strings.
		add_filter(
			'woocommerce_address_fields_for_shipping_rates',
			function () {
				return [ 'address_1', 'address_2' ];
			}
		);
		$checkout->mock_enqueue_data();

		// Verify no warnings were logged.
		$this->mock_logger->expects( $this->never() )
							->method( 'warning' );

		// Verify the data was added to the registry.
		$data_from_registry = $this->registry->get();
		$this->assertArrayHasKey( 'addressFieldsForShippingRates', $data_from_registry );
		$this->assertContains( 'address_1', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertContains( 'address_2', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertContains( 'state', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertContains( 'country', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertContains( 'postcode', $data_from_registry['addressFieldsForShippingRates'] );

		remove_all_filters( 'woocommerce_address_fields_for_shipping_rates' );
	}

	/**
	 * Test that the shipping fields filter warns correctly when mixed array of string/non-string is provided.
	 *
	 * @return void
	 */
	public function test_invalid_mixed_array_shipping_fields_filter() {
		$checkout = new CheckoutMock( $this->asset_api, $this->registry, $this->integration_registry, 'checkout-mock' );

		// Check that the shipping fields filter does warn when applied with an array of mixed strings and non-strings..
		add_filter(
			'woocommerce_address_fields_for_shipping_rates',
			function () {
				return [ 'address_1', 'address_2', [] ];
			}
		);

		// Verify warning was logged for non-string value.
		$this->mock_logger->expects( $this->once() )
							->method( 'warning' )
							->with(
								$this->stringContains( 'Address fields for shipping rates values must be strings. Non-string value removed: Array at index 2' )
							);

		$checkout->mock_enqueue_data();

		// Verify only valid strings were added to the registry.
		$data_from_registry = $this->registry->get();
		$this->assertArrayHasKey( 'addressFieldsForShippingRates', $data_from_registry );
		$this->assertContains( 'address_1', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertContains( 'address_2', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertContains( 'country', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertContains( 'postcode', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertContains( 'state', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertNotContains( [], $data_from_registry['addressFieldsForShippingRates'] );

		remove_all_filters( 'woocommerce_address_fields_for_shipping_rates' );
	}

	/**
	 * Test that when a non-array value is provided to the filter, it's ignored and only default shipping fields are kept.
	 *
	 * @return void
	 */
	public function test_non_array_filter_value_keeps_default_fields() {
		$checkout = new CheckoutMock( $this->asset_api, $this->registry, $this->integration_registry, 'checkout-mock' );

		// Add filter with non-array value.
		add_filter(
			'woocommerce_address_fields_for_shipping_rates',
			function () {
				return 'not-an-array';
			}
		);

		$this->mock_logger->expects( $this->once() )
							->method( 'warning' )
							->with(
								$this->stringContains( 'Address fields for shipping rates must be an array of strings.' )
							);

		$checkout->mock_enqueue_data();

		// Verify only default shipping fields are present.
		$data_from_registry = $this->registry->get();
		$this->assertArrayHasKey( 'addressFieldsForShippingRates', $data_from_registry );
		$this->assertContains( 'state', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertContains( 'country', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertContains( 'postcode', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertContains( 'city', $data_from_registry['addressFieldsForShippingRates'] );
		$this->assertNotContains( 'not-an-array', $data_from_registry['addressFieldsForShippingRates'] );

		remove_all_filters( 'woocommerce_address_fields_for_shipping_rates' );
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
