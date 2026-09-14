<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\BlockTypes\Cart;
use Automattic\WooCommerce\Blocks\BlockTypes\Checkout;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;
use WP_UnitTestCase_Base;

/**
 * Tests for incompatibleExtensions data registration in Cart and Checkout blocks.
 */
class CartCheckoutIncompatibleExtensionsTest extends \WP_UnitTestCase {

	/**
	 * Asset data registry mock.
	 *
	 * @var AssetDataRegistryMock
	 */
	private $asset_data_registry;

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * Customer user ID.
	 *
	 * @var int
	 */
	private $customer_id;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$asset_api                 = Package::container()->get( Api::class );
		$this->asset_data_registry = new AssetDataRegistryMock( $asset_api );

		$this->admin_id    = WP_UnitTestCase_Base::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->customer_id = WP_UnitTestCase_Base::factory()->user->create( array( 'role' => 'customer' ) );
	}

	/**
	 * Clean up after the test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		wp_set_current_user( 0 );
		wp_delete_user( $this->admin_id );
		wp_delete_user( $this->customer_id );
		parent::tearDown();
	}

	/**
	 * Creates a Cart block instance for testing.
	 *
	 * @return Cart
	 */
	private function create_cart_block(): Cart {
		$asset_api            = Package::container()->get( Api::class );
		$integration_registry = new IntegrationRegistry();

		// Create an anonymous subclass that skips initialization and exposes enqueue_data.
		return new class( $asset_api, $this->asset_data_registry, $integration_registry ) extends Cart {
			/**
			 * Skip block registration for unit tests.
			 */
			protected function initialize() {}

			/**
			 * Expose enqueue_data for testing.
			 *
			 * @param array $attributes Block attributes.
			 * @return void
			 */
			public function test_enqueue_data( array $attributes = array() ): void {
				$this->enqueue_data( $attributes );
			}

			/**
			 * Mock is_block_editor to return false (simulate frontend).
			 *
			 * @return bool
			 */
			protected function is_block_editor(): bool {
				return false;
			}
		};
	}

	/**
	 * Creates a Checkout block instance for testing.
	 *
	 * @return Checkout
	 */
	private function create_checkout_block(): Checkout {
		$asset_api            = Package::container()->get( Api::class );
		$integration_registry = new IntegrationRegistry();

		// Create an anonymous subclass that skips initialization and exposes enqueue_data.
		return new class( $asset_api, $this->asset_data_registry, $integration_registry ) extends Checkout {
			/**
			 * Skip block registration for unit tests.
			 */
			protected function initialize() {}

			/**
			 * Expose enqueue_data for testing.
			 *
			 * @param array $attributes Block attributes.
			 * @return void
			 */
			public function test_enqueue_data( array $attributes = array() ): void {
				$this->enqueue_data( $attributes );
			}

			/**
			 * Mock is_block_editor to return false (simulate frontend).
			 *
			 * @return bool
			 */
			protected function is_block_editor(): bool {
				return false;
			}
		};
	}

	/**
	 * Test that incompatibleExtensions is registered for admin users on Cart frontend.
	 *
	 * This test verifies that admins have access to the incompatibleExtensions data
	 * on the frontend. We only check that the key exists (capability check),
	 * not the specific plugin data (that's FeaturesUtil's responsibility).
	 *
	 * @return void
	 */
	public function test_cart_registers_incompatible_extensions_for_admin(): void {
		wp_set_current_user( $this->admin_id );

		$cart = $this->create_cart_block();
		$cart->test_enqueue_data();

		$data = $this->asset_data_registry->get();

		$this->assertArrayHasKey( 'incompatibleExtensions', $data );
		$this->assertIsArray( $data['incompatibleExtensions'] );
	}

	/**
	 * Test that incompatibleExtensions is NOT registered for customer users on Cart frontend.
	 *
	 * @return void
	 */
	public function test_cart_does_not_register_incompatible_extensions_for_customer(): void {
		wp_set_current_user( $this->customer_id );

		$cart = $this->create_cart_block();
		$cart->test_enqueue_data();

		$data = $this->asset_data_registry->get();

		$this->assertArrayNotHasKey( 'incompatibleExtensions', $data );
	}

	/**
	 * Test that incompatibleExtensions is NOT registered for logged out users on Cart frontend.
	 *
	 * @return void
	 */
	public function test_cart_does_not_register_incompatible_extensions_for_guest(): void {
		wp_set_current_user( 0 );

		$cart = $this->create_cart_block();
		$cart->test_enqueue_data();

		$data = $this->asset_data_registry->get();

		$this->assertArrayNotHasKey( 'incompatibleExtensions', $data );
	}

	/**
	 * Test that incompatibleExtensions is registered for admin users on Checkout frontend.
	 *
	 * This test verifies that admins have access to the incompatibleExtensions data
	 * on the frontend. We only check that the key exists (capability check),
	 * not the specific plugin data (that's FeaturesUtil's responsibility).
	 *
	 * @return void
	 */
	public function test_checkout_registers_incompatible_extensions_for_admin(): void {
		wp_set_current_user( $this->admin_id );

		$checkout = $this->create_checkout_block();
		$checkout->test_enqueue_data();

		$data = $this->asset_data_registry->get();

		$this->assertArrayHasKey( 'incompatibleExtensions', $data );
		$this->assertIsArray( $data['incompatibleExtensions'] );
	}

	/**
	 * Test that incompatibleExtensions is NOT registered for customer users on Checkout frontend.
	 *
	 * @return void
	 */
	public function test_checkout_does_not_register_incompatible_extensions_for_customer(): void {
		wp_set_current_user( $this->customer_id );

		$checkout = $this->create_checkout_block();
		$checkout->test_enqueue_data();

		$data = $this->asset_data_registry->get();

		$this->assertArrayNotHasKey( 'incompatibleExtensions', $data );
	}

	/**
	 * Test that incompatibleExtensions is NOT registered for logged out users on Checkout frontend.
	 *
	 * @return void
	 */
	public function test_checkout_does_not_register_incompatible_extensions_for_guest(): void {
		wp_set_current_user( 0 );

		$checkout = $this->create_checkout_block();
		$checkout->test_enqueue_data();

		$data = $this->asset_data_registry->get();

		$this->assertArrayNotHasKey( 'incompatibleExtensions', $data );
	}
}
