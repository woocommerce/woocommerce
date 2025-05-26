<?php
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Tests\Blocks\Mocks\CartCheckoutUtilsMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\CartMock;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;
/**
 * Tests for the Cart block type
 *
 * @since $VID:$
 */
class Cart extends \WP_UnitTestCase {
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
	 * Set up the test.
	 */
	public function setUp(): void {
		$this->asset_api            = Package::container()->get( API::class );
		$this->registry             = new AssetDataRegistryMock( $this->asset_api );
		$this->integration_registry = new IntegrationRegistry();
	}

	/**
	 * We ensure deep sort works with all sort of arrays.
	 */
	public function test_deep_sort_with_accents() {
		$test_array_1 = array(
			'0',
			'1',
			array( '2', '3' ),
		);
		$test_array_2 = array(
			array( '0', '1' ),
			'2',
			'3',
		);
		$this->assertEquals( $test_array_1, CartCheckoutUtilsMock::deep_sort_test( $test_array_1 ), '' );
		$this->assertEquals( $test_array_2, CartCheckoutUtilsMock::deep_sort_test( $test_array_2 ), '' );
	}

		/**
		 * Test that the default shipping defining address fields are included in the registry data.
		 *
		 * @return void
		 */
	public function test_default_shipping_fields_in_registry() {
		$cart = new CartMock( $this->asset_api, $this->registry, $this->integration_registry, 'cart-mock' );
		$cart->mock_enqueue_data();

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
}
