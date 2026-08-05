<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsManager;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers\FulfillmentsHelper;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;
use Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers\ShippingProviderMock;
use WC_Order;

/**
 * Tests for Fulfillment object.
 */
class FulfillmentsManagerTest extends \WC_Unit_Test_Case {

	/**
	 * @var FulfillmentsManager
	 */
	private FulfillmentsManager $manager;

	/**
	 * Original value of the fulfillments feature flag.
	 *
	 * @var mixed
	 */
	private static $original_fulfillments_flag;

	/**
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$original_fulfillments_flag = get_option( 'woocommerce_feature_fulfillments_enabled' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Tear down the test environment.
	 */
	public static function tearDownAfterClass(): void {
		if ( false === self::$original_fulfillments_flag ) {
			delete_option( 'woocommerce_feature_fulfillments_enabled' );
		} else {
			update_option( 'woocommerce_feature_fulfillments_enabled', self::$original_fulfillments_flag );
		}
		parent::tearDownAfterClass();
	}

	/**
	 * Set up the test case.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->manager = wc_get_container()->get( FulfillmentsManager::class );
		$this->manager->register();
	}

	/**
	 * Test hooks.
	 */
	public function test_hooks() {
		$this->assertNotFalse( has_filter( 'woocommerce_fulfillment_translate_meta_key', array( $this->manager, 'translate_fulfillment_meta_key' ) ) );
	}

	/**
	 * Test the translate_fulfillment_meta_key method.
	 */
	public function test_translate_fulfillment_meta_key() {
		// Test with a known meta key.
		$translated_key = $this->manager->translate_fulfillment_meta_key( 'fulfillment_status' );
		$this->assertEquals( __( 'Fulfillment Status', 'woocommerce' ), $translated_key );

		// Test with an unknown meta key.
		$translated_key = $this->manager->translate_fulfillment_meta_key( 'unknown_meta_key' );
		$this->assertEquals( 'unknown_meta_key', $translated_key );
	}

	/**
	 * Test extending the translation of a fulfillment meta key.
	 */
	public function test_extend_translate_fulfillment_meta_key() {
		// Extend the translations.
		add_filter(
			'woocommerce_fulfillment_meta_key_translations',
			function ( $translations ) {
				$translations['custom_meta_key'] = __( 'Custom Meta Key', 'woocommerce' );
				return $translations;
			}
		);

		// Test the extended translation.
		$translated_key = $this->manager->translate_fulfillment_meta_key( 'custom_meta_key' );
		$this->assertEquals( __( 'Custom Meta Key', 'woocommerce' ), $translated_key );
	}

	/**
	 * Test that the filter for translating fulfillment meta keys works correctly.
	 */
	public function test_translate_fulfillment_meta_key_with_filter() {

		// Add a filter to modify the translations.
		add_filter(
			'woocommerce_fulfillment_meta_key_translations',
			function ( $translations ) {
				$translations['custom_meta_key'] = __( 'Custom Meta Key', 'woocommerce' );
				return $translations;
			}
		);

		/**
		 * Filter to translate fulfillment meta keys.
		 *
		 * @since 10.1.0
		 */
		$translated_key = apply_filters( 'woocommerce_fulfillment_translate_meta_key', 'custom_meta_key' );
		$this->assertEquals( __( 'Custom Meta Key', 'woocommerce' ), $translated_key );
	}

	/**
	 * Test that the initial shipping providers are loaded correctly.
	 */
	public function test_get_initial_shipping_providers() {
		/**
		 * Filter to get initial shipping providers
		 *
		 * @since 10.1.0
		 */
		$shipping_providers = apply_filters( 'woocommerce_fulfillment_shipping_providers', array() );
		// Check if the shipping providers are loaded correctly.
		$this->assertIsArray( $shipping_providers );
		$this->assertNotEmpty( $shipping_providers );
	}

	/**
	 * Test that the initial shipping providers can be extended with an AbstractShippingProvider instance.
	 */
	public function test_extend_initial_shipping_providers() {
		$mock_provider = new ShippingProviderMock();

		// Extend the shipping providers with an AbstractShippingProvider instance.
		$filter = function ( $providers ) use ( $mock_provider ) {
			$providers[] = $mock_provider;
			return $providers;
		};
		add_filter( 'woocommerce_fulfillment_shipping_providers', $filter );

		$shipping_providers = \Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentUtils::get_shipping_providers();

		remove_filter( 'woocommerce_fulfillment_shipping_providers', $filter );

		// Check if the mock provider is included, keyed by its key.
		$this->assertArrayHasKey( $mock_provider->get_key(), $shipping_providers );
		$this->assertInstanceOf( ShippingProviderMock::class, $shipping_providers[ $mock_provider->get_key() ] );
		$this->assertEquals( 'Mock Shipping Provider', $shipping_providers[ $mock_provider->get_key() ]->get_name() );
	}

	/**
	 * Test that the fulfillment status hooks are initialized correctly.
	 */
	public function test_init_fulfillment_status_hooks() {
		$this->assertNotFalse( has_action( 'woocommerce_fulfillment_after_create', array( $this->manager, 'update_order_fulfillment_status_on_fulfillment_update' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_fulfillment_after_update', array( $this->manager, 'update_order_fulfillment_status_on_fulfillment_update' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_fulfillment_after_delete', array( $this->manager, 'update_order_fulfillment_status_on_fulfillment_update' ) ) );
	}

	/**
	 * Test that the fulfillment status is updated on fulfillment creation.
	 */
	public function test_update_order_fulfillment_status_on_fulfillment_updates() {
		$fulfillments = array();
		$product      = \WC_Helper_Product::create_simple_product();
		$order        = OrderHelper::create_order( get_current_user_id(), $product );
		$this->assertEmpty( $order->get_meta( '_fulfillment_status' ) );

		$create_count_before = did_action( 'woocommerce_fulfillment_after_create' );
		$fulfillments[]      = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type'  => WC_Order::class,
				'entity_id'    => $order->get_id(),
				'status'       => 'unfulfilled',
				'is_fulfilled' => false,
			),
			array(
				'_items' => array(
					array(
						'item_id' => $product->get_id(),
						'qty'     => 1,
					),
				),
			)
		);
		$this->assertGreaterThan( $create_count_before, did_action( 'woocommerce_fulfillment_after_create' ) );
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'unfulfilled', $order->get_meta( '_fulfillment_status', true ) );

		$update_count_before = did_action( 'woocommerce_fulfillment_after_update' );
		$fulfillments[0]->set_status( 'fulfilled' );
		$fulfillments[0]->save();

		$this->assertGreaterThan( $update_count_before, did_action( 'woocommerce_fulfillment_after_update' ) );
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'partially_fulfilled', $order->get_meta( '_fulfillment_status' ) );

		$delete_count_before = did_action( 'woocommerce_fulfillment_after_delete' );
		$fulfillments[0]->delete();
		$this->assertGreaterThan( $delete_count_before, did_action( 'woocommerce_fulfillment_after_delete' ) );
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( '', $order->get_meta( '_fulfillment_status' ) );
	}

	/**
	 * Test that the tracking number can be parsed correctly.
	 */
	public function test_try_parse_tracking_number_nominal() {
		$tracking_number = '1234567890';

		/**
		 * TestingContainer instance.
		 *
		 * @var TestingContainer $container
		 */
		$container     = wc_get_container();
		$mock_provider = $this->getMockBuilder( ShippingProviderMock::class )->onlyMethods( array( 'try_parse_tracking_number' ) )->getMock();
		$container->replace( ShippingProviderMock::class, $mock_provider );
		add_filter(
			'woocommerce_fulfillment_shipping_providers',
			function ( $providers ) {
				$providers = array(
					'custom_provider' => ShippingProviderMock::class,
				);
				return $providers;
			}
		);

		$mock_provider->expects( $this->once() )
			->method( 'try_parse_tracking_number' )
			->willReturn(
				array(
					'url' => 'https://example.com/track?number=' . $tracking_number,
				)
			);

		// Test with a valid tracking number.
		$parsed_number = $this->manager->try_parse_tracking_number( $tracking_number, 'US', 'CA' );
		$this->assertEquals( $tracking_number, $parsed_number['tracking_number'] );
		$this->assertEquals( 'mock_shipping_provider', $parsed_number['shipping_provider'] );
		$this->assertEquals( 'https://example.com/track?number=' . $tracking_number, $parsed_number['tracking_url'] );
	}

	/**
	 * Test tracking number parsing without any matches.
	 */
	public function test_try_parse_tracking_number_no_match() {
		$tracking_number = '1234567890';

		/**
		 * TestingContainer instance.
		 *
		 * @var TestingContainer $container
		 */
		$container     = wc_get_container();
		$mock_provider = $this->getMockBuilder( ShippingProviderMock::class )->onlyMethods( array( 'try_parse_tracking_number' ) )->getMock();
		$container->replace( ShippingProviderMock::class, $mock_provider );
		add_filter(
			'woocommerce_fulfillment_shipping_providers',
			function ( $providers ) {
				$providers = array(
					'custom_provider' => ShippingProviderMock::class,
				);
				return $providers;
			}
		);

		$mock_provider->expects( $this->once() )
			->method( 'try_parse_tracking_number' )
			->willReturn( null );

		// Test with a valid tracking number.
		$parsed_number = $this->manager->try_parse_tracking_number( $tracking_number, 'US', 'CA' );
		$this->assertEquals( array(), $parsed_number );
	}

	/**
	 * @testdox Should register order deletion hooks.
	 */
	public function test_order_deletion_hooks_registered(): void {
		$this->assertNotFalse( has_action( 'woocommerce_before_delete_order', array( $this->manager, 'delete_order_fulfillments' ) ) );
		$this->assertNotFalse( has_action( 'before_delete_post', array( $this->manager, 'delete_order_fulfillments' ) ) );
	}

	/**
	 * @testdox Should delete fulfillments when an order is permanently deleted.
	 */
	public function test_delete_order_fulfillments_on_order_deletion(): void {
		global $wpdb;

		$product = \WC_Helper_Product::create_simple_product();
		$order   = OrderHelper::create_order( get_current_user_id(), $product );

		FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type' => WC_Order::class,
				'entity_id'   => $order->get_id(),
				'status'      => 'unfulfilled',
			),
			array(
				'_items' => array(
					array(
						'item_id' => 1,
						'qty'     => 1,
					),
				),
			)
		);

		$order_id = $order->get_id();

		$fulfillments_before = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}wc_order_fulfillments WHERE entity_type = %s AND entity_id = %s",
				WC_Order::class,
				$order_id
			)
		);
		$this->assertSame( '1', $fulfillments_before, 'Fulfillment should exist before deletion' );

		$order->delete( true );

		$fulfillments_after = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}wc_order_fulfillments WHERE entity_type = %s AND entity_id = %d",
				WC_Order::class,
				$order_id
			)
		);
		$this->assertSame( '0', $fulfillments_after, 'Fulfillments should be deleted after order deletion' );
	}

	/**
	 * @testdox Should register the custom shipping providers filter hook.
	 */
	public function test_custom_shipping_providers_hook_registered(): void {
		$this->assertNotFalse( has_filter( 'woocommerce_fulfillment_shipping_providers', array( $this->manager, 'get_custom_shipping_providers' ) ) );
	}

	/**
	 * @testdox Should load custom shipping providers from the taxonomy into the providers list.
	 */
	public function test_get_custom_shipping_providers_loads_taxonomy_terms(): void {
		if ( ! taxonomy_exists( 'wc_fulfillment_shipping_provider' ) ) {
			register_taxonomy( 'wc_fulfillment_shipping_provider', array() );
		}

		$term = wp_insert_term( 'Test Custom Provider', 'wc_fulfillment_shipping_provider', array( 'slug' => 'test-custom-provider' ) );
		$this->assertNotWPError( $term );

		update_term_meta( $term['term_id'], 'tracking_url_template', 'https://example.com/track?id=__PLACEHOLDER__' );
		update_term_meta( $term['term_id'], 'icon', 'https://example.com/icon.png' );

		$providers = $this->manager->get_custom_shipping_providers( array() );

		$this->assertNotEmpty( $providers );

		$found = false;
		foreach ( $providers as $provider ) {
			if ( $provider instanceof \Automattic\WooCommerce\Admin\Features\Fulfillments\Providers\CustomShippingProvider && 'test-custom-provider' === $provider->get_key() ) {
				$found = true;
				$this->assertSame( 'Test Custom Provider', $provider->get_name() );
				$this->assertSame( 'https://example.com/icon.png', $provider->get_icon() );
				$this->assertSame( 'https://example.com/track?id=ABC123', $provider->get_tracking_url( 'ABC123' ) );
				break;
			}
		}

		$this->assertTrue( $found, 'Custom provider should be loaded from taxonomy' );

		wp_delete_term( $term['term_id'], 'wc_fulfillment_shipping_provider' );
	}

	/**
	 * @testdox Should return existing providers when no custom providers exist.
	 */
	public function test_get_custom_shipping_providers_returns_existing_when_no_terms(): void {
		$existing = array( 'some_provider' );

		$result = $this->manager->get_custom_shipping_providers( $existing );

		$this->assertContains( 'some_provider', $result );
	}

	/**
	 * Test tracking number parsing without any shipping providers.
	 */
	public function test_try_parse_tracking_number_no_providers() {
		$tracking_number = '1234567890';

		add_filter(
			'woocommerce_fulfillment_shipping_providers',
			function ( $providers ) {
				unset( $providers );
				return array();
			}
		);

		// Test with a valid tracking number.
		$parsed_number = $this->manager->try_parse_tracking_number( $tracking_number, 'US', 'CA' );
		$this->assertEquals( array(), $parsed_number );
	}

	/**
	 * @testdox Email template tracking hooks are registered for all fulfillment email types.
	 */
	public function test_email_template_tracking_hooks_are_registered(): void {
		$email_ids = array(
			'customer_fulfillment_created',
			'customer_fulfillment_updated',
			'customer_fulfillment_deleted',
		);

		foreach ( $email_ids as $email_id ) {
			$this->assertNotFalse(
				has_action( 'woocommerce_update_options_email_' . $email_id ),
				"Tracking hook should be registered for woocommerce_update_options_email_{$email_id}"
			);
		}
	}
}
