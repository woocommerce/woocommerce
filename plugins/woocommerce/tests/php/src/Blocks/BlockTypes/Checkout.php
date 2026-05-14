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
	 * Returns whether the current theme is treated as a block theme by wp_is_block_theme().
	 *
	 * Tests inspect this rather than calling the function directly so they can short-circuit
	 * if the test environment ever flips defaults.
	 *
	 * @return bool
	 */
	private function is_block_theme(): bool {
		return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
	}

	/**
	 * Notices added to the session via wc_add_notice() should be cleared on the initial
	 * (non-POST) render of the Checkout block in classic themes, so they do not persist
	 * across requests and re-surface in Store API responses (RSMAPGJ-446 / woo#62855).
	 *
	 * @return void
	 */
	public function test_render_clears_stale_session_notices_on_classic_themes() {
		if ( $this->is_block_theme() ) {
			$this->markTestSkipped( 'This regression only affects classic themes.' );
		}

		// Ensure no POST state leaks in from another test.
		$saved_post = $_POST;
		$_POST      = array();

		wc_clear_notices();
		wc_add_notice( 'You cannot add another "Exclusive Product" to your cart.', 'error' );
		$this->assertSame( 1, wc_notice_count( 'error' ), 'Precondition: error notice should be queued.' );

		$checkout = new CheckoutMock( $this->asset_api, $this->registry, $this->integration_registry, 'checkout-mock' );
		$checkout->call_render( array(), '<div class="wp-block-woocommerce-checkout"></div>', null );

		$this->assertSame( 0, wc_notice_count( 'error' ), 'Stale error notice should be cleared on initial render.' );

		$_POST = $saved_post;
	}

	/**
	 * During form submissions (POST requests) notices added by the current request must
	 * be preserved so the client can surface them. The render path therefore must not
	 * clear notices when $_POST is non-empty.
	 *
	 * @return void
	 */
	public function test_render_preserves_notices_during_post_requests() {
		if ( $this->is_block_theme() ) {
			$this->markTestSkipped( 'Only the classic-theme branch performs explicit clearing.' );
		}

		$saved_post = $_POST;
		$_POST      = array( 'woocommerce-process-checkout-nonce' => 'placeholder' );

		wc_clear_notices();
		wc_add_notice( 'A notice added during this request.', 'error' );

		$checkout = new CheckoutMock( $this->asset_api, $this->registry, $this->integration_registry, 'checkout-mock' );
		$checkout->call_render( array(), '<div class="wp-block-woocommerce-checkout"></div>', null );

		$this->assertSame( 1, wc_notice_count( 'error' ), 'Notices added during a POST request should be preserved.' );

		wc_clear_notices();
		$_POST = $saved_post;
	}
}
