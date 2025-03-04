<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Blocks\Shipping;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Shipping\ShippingController;

/**
 * Unit tests for the PatternRegistry class.
 */
class ShippingControllerTest extends \WP_UnitTestCase {
	/**
	 * The registry instance.
	 *
	 * @var ShippingController $controller
	 */
	private ShippingController $shipping_controller;

	/**
	 * Initialize the registry instance.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->shipping_controller = new ShippingController(
			Package::container()->get( Api::class ),
			Package::container()->get( AssetDataRegistry::class )
		);
		WC()->customer->set_shipping_postcode( '' );
		WC()->customer->set_shipping_city( '' );
		WC()->customer->set_shipping_state( '' );
		WC()->customer->set_shipping_country( '' );
	}

	/**
	 * Test that the has_full_shipping_address method returns correctly.
	 */
	public function test_has_full_shipping_address_returns_correctly() {
		// With GB, state is not required. Test it returns false with only a country, nothing else.
		WC()->customer->set_shipping_country( 'GB' );
		$this->assertFalse( WC()->customer->has_full_shipping_address() );

		WC()->customer->set_shipping_postcode( 'PR1 4SS' );
		$this->assertFalse( WC()->customer->has_full_shipping_address() );

		WC()->customer->set_shipping_city( 'Preston' );
		$this->assertTrue( WC()->customer->has_full_shipping_address() );

		// Now switch to US, ensure that it returns false because state is not input.
		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_postcode( '90210' );
		WC()->customer->set_shipping_city( 'Beverly Hills' );
		$this->assertFalse( WC()->customer->has_full_shipping_address() );

		// Now add state, ensure that it returns true.
		WC()->customer->set_shipping_state( 'CA' );
		$this->assertTrue( WC()->customer->has_full_shipping_address() );

		// Now add a filter to set US state to optional, and UK state to required.
		add_filter(
			'woocommerce_get_country_locale',
			function ( $locale ) {
				$locale['US']['state']['required']      = false;
				$locale['GB']['state']['required']      = true;
				$locale['default']['state']['required'] = false;
				return $locale;
			}
		);

		// Unset the cached locale because this filter runs later. Typically, that sort of filter would be applied before
		// the locale is cached, but in unit tests the site is already set up before the test runs.
		unset( WC()->countries->locale );

		// Test that US state is now optional.
		WC()->customer->set_shipping_state( '' );
		$this->assertTrue( WC()->customer->has_full_shipping_address() );

		// Test that UK state is now required.
		WC()->customer->set_shipping_country( 'GB' );
		WC()->customer->set_shipping_postcode( 'PR1 4SS' );
		$this->assertFalse( WC()->customer->has_full_shipping_address() );

		// Finally test that it passes when an ordinarily optional prop filtered to be required is provided.
		WC()->customer->set_shipping_state( 'Lancashire' );
		$this->assertTrue( WC()->customer->has_full_shipping_address() );

		// Remove filter.
		remove_all_filters( 'woocommerce_get_country_locale' );
	}
}
