<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentUtils;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Providers as ShippingProviders;
use Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers\FulfillmentsHelper;

/**
 * ShippingProvidersTest class.
 *
 * This class tests the shipping providers configuration.
 */
class ShippingProvidersTest extends \WP_UnitTestCase {

	/**
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		FulfillmentsHelper::create_tables();
	}

	/**
	 * Test that the shipping providers configuration returns the correct instances.
	 */
	public function test_shipping_providers_configuration(): void {
		FulfillmentsHelper::enable_fulfillments();

		$shipping_providers = FulfillmentUtils::get_shipping_providers();
		$this->assertNotEmpty( $shipping_providers, 'Expected at least one registered shipping provider.' );

		foreach ( $shipping_providers as $key => $provider ) {
			$this->assertInstanceOf(
				ShippingProviders\AbstractShippingProvider::class,
				$provider,
				sprintf( 'Shipping provider %s is not an instance of AbstractShippingProvider.', $key )
			);
			$this->assertNotEmpty(
				$provider->get_key(),
				sprintf( 'Shipping provider %s does not have a valid key.', $key )
			);
			$this->assertEquals(
				$key,
				$provider->get_key(),
				sprintf( 'Shipping provider key %s does not match the expected key %s.', $provider->get_key(), $key )
			);
		}
	}
}
