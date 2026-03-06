<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentUtils;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Providers as ShippingProviders;

/**
 * ShippingProvidersTest class.
 *
 * This class tests the shipping providers configuration.
 */
class ShippingProvidersTest extends \WP_UnitTestCase {

	/**
	 * Original value of the fulfillments feature flag.
	 *
	 * @var mixed
	 */
	private $original_fulfillments_flag;

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_fulfillments_flag = get_option( 'woocommerce_feature_fulfillments_enabled' );
	}

	/**
	 * Tear down the test environment.
	 */
	public function tearDown(): void {
		if ( false === $this->original_fulfillments_flag ) {
			delete_option( 'woocommerce_feature_fulfillments_enabled' );
		} else {
			update_option( 'woocommerce_feature_fulfillments_enabled', $this->original_fulfillments_flag );
		}
		parent::tearDown();
	}

	/**
	 * Test that the shipping providers configuration returns the correct classes.
	 */
	public function test_shipping_providers_configuration(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();

		$shipping_providers = FulfillmentUtils::get_shipping_providers();

		foreach ( $shipping_providers as $key => $provider_class ) {
			$this->assertTrue(
				class_exists( $provider_class ),
				sprintf( 'Shipping provider class %s does not exist.', $provider_class )
			);

			$provider_instance = new $provider_class();
			$this->assertInstanceOf(
				ShippingProviders\AbstractShippingProvider::class,
				$provider_instance,
				sprintf( 'Shipping provider %s is not an instance of AbstractShippingProvider.', $key )
			);
			$this->assertNotEmpty(
				$provider_instance->get_key(),
				sprintf( 'Shipping provider %s does not have a valid key.', $key )
			);
			$this->assertEquals(
				$key,
				$provider_instance->get_key(),
				sprintf( 'Shipping provider key %s does not match the expected key %s.', $provider_instance->get_key(), $key )
			);
		}
	}
}
