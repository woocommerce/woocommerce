<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\CheckoutMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\PaymentTokenMock;

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
	 * The BACS gateway's enabled flag before a test switched it on, or null when untouched.
	 *
	 * @var string|null
	 */
	private $bacs_enabled_before_test;

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

		// Each CheckoutMock registers this block type; the registry is process state that no base class resets.
		if ( \WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/checkout-mock' ) ) {
			unregister_block_type( 'woocommerce/checkout-mock' );
		}

		// Gateway instances are process state that no base class resets.
		if ( null !== $this->bacs_enabled_before_test ) {
			WC()->payment_gateways()->payment_gateways()['bacs']->enabled = $this->bacs_enabled_before_test;
			$this->bacs_enabled_before_test                               = null;
		}
	}

	/**
	 * Log in a customer who owns one custom-type saved token for the enabled BACS gateway.
	 *
	 * @return void
	 */
	private function log_in_customer_with_custom_saved_token(): void {
		$bacs                           = WC()->payment_gateways()->payment_gateways()['bacs'];
		$this->bacs_enabled_before_test = $bacs->enabled;
		$bacs->enabled                  = 'yes';

		add_filter(
			'woocommerce_payment_token_class',
			static function ( $class_name, $type ) {
				return 'checkout_test' === $type ? PaymentTokenMock::class : $class_name;
			},
			10,
			2
		);

		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );

		$token = new PaymentTokenMock();
		$token->set_token( 'checkout-test-token' );
		$token->set_gateway_id( 'bacs' );
		$token->set_user_id( $user_id );
		$token->save();
	}

	/**
	 * @testdox Should hydrate saved methods with the token display name and without account actions.
	 */
	public function test_hydrate_customer_payment_methods_adds_display_name_and_omits_actions(): void {
		$this->log_in_customer_with_custom_saved_token();

		$later_filter_saw_actions = false;
		add_filter(
			'woocommerce_payment_methods_list_item',
			static function ( $item ) use ( &$later_filter_saw_actions ) {
				$later_filter_saw_actions = array_key_exists( 'actions', $item );
				return $item;
			},
			20
		);

		$checkout = new CheckoutMock( $this->asset_api, $this->registry, $this->integration_registry, 'checkout-mock' );
		$checkout->mock_hydrate_customer_payment_methods();

		$hydrated = $this->registry->get()['customerPaymentMethods']['checkout_test'][0];
		$this->assertSame( 'Checkout test account ending in 9876', $hydrated['display_name'], 'Checkout should receive the token display name.' );
		$this->assertIsInt( $hydrated['tokenId'], 'Checkout should still receive the token ID.' );
		$this->assertArrayNotHasKey( 'actions', $hydrated, 'Checkout should not receive My Account management actions.' );
		$this->assertTrue( $later_filter_saw_actions, 'Later item filters should still see the complete account list item.' );

		$account_item = wc_get_customer_saved_methods_list( get_current_user_id() )['checkout_test'][0];
		$this->assertArrayHasKey( 'actions', $account_item, 'My Account lists built afterwards should keep their actions.' );
		$this->assertArrayNotHasKey( 'display_name', $account_item, 'Checkout enrichment should not leak into later My Account lists.' );
	}

	/**
	 * @testdox Should remove its temporary list item filter when a later item filter throws.
	 */
	public function test_hydrate_customer_payment_methods_removes_filter_when_item_filter_throws(): void {
		$this->log_in_customer_with_custom_saved_token();

		$throwing_filter = static function () {
			throw new \RuntimeException( 'Item filter failed.' );
		};
		add_filter( 'woocommerce_payment_methods_list_item', $throwing_filter, 20 );

		$checkout = new CheckoutMock( $this->asset_api, $this->registry, $this->integration_registry, 'checkout-mock' );
		$thrown   = null;
		try {
			$checkout->mock_hydrate_customer_payment_methods();
		} catch ( \RuntimeException $exception ) {
			$thrown = $exception;
		}
		remove_filter( 'woocommerce_payment_methods_list_item', $throwing_filter, 20 );

		$this->assertInstanceOf( \RuntimeException::class, $thrown, 'The item filter exception should propagate.' );

		$account_item = wc_get_customer_saved_methods_list( get_current_user_id() )['checkout_test'][0];
		$this->assertArrayNotHasKey( 'display_name', $account_item, 'Checkout enrichment must not leak into later My Account lists after an exception.' );
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
}
