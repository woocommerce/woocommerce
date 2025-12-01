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
	 * The old checkout page ID.
	 *
	 * @var int $original_checkout_page_id
	 */
	private $original_checkout_page_id;

	/**
	 * The new checkout page ID.
	 *
	 * @var int $block_checkout_page_id
	 */
	private $block_checkout_page_id;


	/**
	 * Mock logger instance.
	 *
	 * @var \WC_Logger_Interface $mock_logger
	 */
	private $mock_logger;

	/**
	 * Backup WC instance.
	 *
	 * @var \WC $backup_wc
	 */
	private $backup_wc;

	/**
	 * Initialize the registry instance.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Setup mock logger.
		$this->mock_logger = $this->getMockBuilder( \WC_Logger_Interface::class )->getMock();
		add_filter(
			'woocommerce_logging_class',
			array( $this, 'override_wc_logger' )
		);

		// Backup WC instance.
		$this->backup_wc = WC();

		// Local pickup only works with the checkout block.
		$this->original_checkout_page_id = get_option( 'woocommerce_checkout_page_id' );
		$this->block_checkout_page_id    = $this->factory->post->create(
			array(
				'post_type'    => 'page',
				'post_title'   => 'Checkout',
				'post_content' => '<!-- wp:woocommerce/checkout /-->',
				'post_status'  => 'publish',
			)
		);
		update_option( 'woocommerce_checkout_page_id', $this->block_checkout_page_id );

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
	 * Tear down the test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		global $woocommerce;

		update_option( 'woocommerce_checkout_page_id', $this->original_checkout_page_id );
		wp_delete_post( $this->block_checkout_page_id );
		remove_filter( 'woocommerce_logging_class', array( $this, 'override_wc_logger' ) );
		$woocommerce = $this->backup_wc;
		parent::tearDown();
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

	/**
	 * Test that register_local_pickup handles missing WC()->shipping and other dependencies gracefully.
	 */
	public function test_register_local_pickup_also_handles_missing_dependencies() {
		// Test that the method does not throw exceptions without missing dependencies.
		$this->shipping_controller->register_local_pickup();
		$this->assertTrue( true, 'Method did not throw exceptions without missing dependencies' );

		// Test that the method does not throw exceptions with missing shipping.
		WC()->shipping = null;
		$this->shipping_controller->register_local_pickup();
		$this->assertTrue( true, 'Method did not throw exceptions with missing shipping' );

		// Test that the error is logged when WC()->shipping->register_shipping_method is not available.
		$this->mock_logger->expects( $this->once() )
					->method( 'error' )
					->with(
						'Error registering pickup location: WC()->shipping->register_shipping_method is not available',
						array( 'source' => 'shipping-controller' )
					);

		// Test that the method does not throw exceptions with missing WC object.
		global $woocommerce;
		$incomplete_wc = new \stdClass(); // Object without shipping property.
		$woocommerce   = $incomplete_wc;

		$this->shipping_controller->register_local_pickup();
		$this->assertTrue( true, 'Method did not throw exceptions with missing WC object' );
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
