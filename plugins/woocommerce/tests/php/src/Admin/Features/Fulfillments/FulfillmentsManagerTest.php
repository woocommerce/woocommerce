<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsManager;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentUtils;
use Automattic\WooCommerce\Enums\OrderStatus;
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
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		FulfillmentsHelper::create_tables();
	}

	/**
	 * Set up the test case.
	 */
	public function setUp(): void {
		parent::setUp();
		FulfillmentsHelper::enable_fulfillments();
		$this->manager = wc_get_container()->get( FulfillmentsManager::class );
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

	/**
	 * @testdox The "Completed order" email check is hooked when fulfillments are active.
	 */
	public function test_completed_order_email_hook_is_registered(): void {
		$this->assertNotFalse(
			has_filter( 'woocommerce_email_enabled_customer_completed_order', array( $this->manager, 'maybe_suppress_completed_order_email' ) ),
			'The "Completed order" email check should be hooked.'
		);
	}

	/**
	 * @testdox The "Completed order" email still sends for a fulfilled order when nothing opts out of it.
	 */
	public function test_completed_order_email_sends_for_fulfilled_order_by_default(): void {
		$order = $this->create_order_with_fulfilled_quantity( 4 );

		$this->assertSame( 'fulfilled', $order->get_meta( '_fulfillment_status', true ), 'The order should be fully fulfilled.' );
		$this->assertTrue( $this->complete_order_and_email_sent( $order ), 'Both emails should send unless a store opts out.' );
	}

	/**
	 * @testdox The "Completed order" email is skipped for a fulfilled order when a store opts out of it.
	 */
	public function test_completed_order_email_is_skipped_for_fulfilled_order_when_opted_out(): void {
		$order = $this->create_order_with_fulfilled_quantity( 4 );
		$this->opt_out_of_the_completed_order_email();

		$this->assertFalse( $this->complete_order_and_email_sent( $order ), 'A fully fulfilled order should skip the email.' );
	}

	/**
	 * @testdox The "Completed order" email still sends for a partially fulfilled order when a store opts out of it.
	 */
	public function test_completed_order_email_sends_for_partially_fulfilled_order_when_opted_out(): void {
		$order = $this->create_order_with_fulfilled_quantity( 1 );
		$this->opt_out_of_the_completed_order_email();

		$this->assertSame( 'partially_fulfilled', $order->get_meta( '_fulfillment_status', true ), 'The order should be partially fulfilled.' );
		$this->assertTrue( $this->complete_order_and_email_sent( $order ), 'An order with items left to ship should keep the email.' );
	}

	/**
	 * @testdox The "Completed order" email still sends for an order without fulfillments when a store opts out of it.
	 */
	public function test_completed_order_email_sends_without_fulfillments_when_opted_out(): void {
		$order = OrderHelper::create_order( get_current_user_id(), \WC_Helper_Product::create_simple_product() );
		$this->opt_out_of_the_completed_order_email();

		$this->assertTrue( $this->complete_order_and_email_sent( $order ), 'An order with no fulfillments should keep the email.' );
	}

	/**
	 * @testdox The "Completed order" email still sends for an order whose fulfillments are all unfulfilled.
	 */
	public function test_completed_order_email_sends_for_unfulfilled_order_when_opted_out(): void {
		$order = $this->create_order_with_fulfilled_quantity( 4, false );
		$this->opt_out_of_the_completed_order_email();

		$this->assertSame( 'unfulfilled', $order->get_meta( '_fulfillment_status', true ), 'The order should be unfulfilled.' );
		$this->assertTrue( $this->complete_order_and_email_sent( $order ), 'An order whose shipments have not gone out should keep the email.' );
	}

	/**
	 * @testdox Opting out never turns the "Completed order" email back on when the merchant disabled it.
	 */
	public function test_completed_order_email_stays_off_when_the_merchant_disabled_it(): void {
		// Partially fulfilled, so the email would be reported as enabled again if the merchant's
		// setting were not checked first.
		$order = $this->create_order_with_fulfilled_quantity( 1 );
		$this->opt_out_of_the_completed_order_email();

		$this->assertFalse( $this->complete_order_and_email_sent( $order, 'no' ), 'A disabled email should stay disabled.' );
	}

	/**
	 * @testdox Only true opts out of the "Completed order" email, so a callback returning something else still sends it.
	 */
	public function test_completed_order_email_sends_when_the_filter_returns_a_truthy_value(): void {
		$order = $this->create_order_with_fulfilled_quantity( 4 );
		add_filter( 'woocommerce_fulfillment_suppress_completed_order_email', fn() => 'no' );

		$this->assertTrue( $this->complete_order_and_email_sent( $order ), 'Only true should skip the email.' );
	}

	/**
	 * @testdox The "Completed order" email still sends when an item was added after the order was fulfilled.
	 */
	public function test_completed_order_email_sends_when_an_item_was_added_after_the_order_was_fulfilled(): void {
		$order = $this->create_order_with_fulfilled_quantity( 4 );
		$this->opt_out_of_the_completed_order_email();

		$order->add_product( \WC_Helper_Product::create_simple_product(), 1 );
		$order->save();

		$this->assertSame( 'fulfilled', $order->get_meta( '_fulfillment_status', true ), 'The stored status should still say fulfilled, which is what makes this worth checking.' );
		$this->assertTrue( $this->complete_order_and_email_sent( $order ), 'An order holding an item that was never fulfilled should keep the email.' );
	}

	/**
	 * @testdox The "Completed order" email still sends when another request added an item to the fulfilled order.
	 */
	public function test_completed_order_email_sends_when_another_request_added_an_item(): void {
		$order      = $this->create_order_with_fulfilled_quantity( 4 );
		$held_order = new WC_Order( $order->get_id() );

		// Order items are read on first use, so this is what makes the held instance keep its own
		// copy of them, as any caller that looked at the order before completing it would.
		$this->assertCount( 1, $held_order->get_items(), 'The held instance starts with the order as it was.' );

		// As a REST request editing the order would: an item that no fulfillment covers is added
		// through another instance, after the one completing the order had read its items.
		$edited_order = new WC_Order( $order->get_id() );
		$edited_order->add_product( \WC_Helper_Product::create_simple_product(), 1 );
		$edited_order->save();

		$this->opt_out_of_the_completed_order_email();

		$this->assertCount( 2, wc_get_order( $order->get_id() )->get_items(), 'The stored order should hold the added item.' );
		$this->assertTrue( $this->complete_order_and_email_sent( $held_order ), 'An order holding an item that was added elsewhere should keep the email.' );
	}

	/**
	 * @testdox The "Completed order" email still sends when the order's fulfillments cannot be read.
	 */
	public function test_completed_order_email_sends_when_the_fulfillments_cannot_be_read(): void {
		$order = $this->create_order_with_fulfilled_quantity( 4 );
		$this->opt_out_of_the_completed_order_email();

		// Named rather than removed, because an unknown data store silently falls back to the one
		// for orders instead of failing.
		add_filter(
			'woocommerce_data_stores',
			function ( $stores ) {
				$stores['order-fulfillment'] = 'A_Fulfillments_Data_Store_That_Is_Not_Installed';
				return $stores;
			}
		);

		$this->assertTrue( $this->complete_order_and_email_sent( $order ), 'A fulfillment status that cannot be read should not cost the customer the email.' );
	}

	/**
	 * @testdox The "Completed order" email is skipped for a fulfilled order when the caller completes an instance read before the fulfillment.
	 */
	public function test_completed_order_email_is_skipped_for_an_order_read_before_the_fulfillment(): void {
		$order = OrderHelper::create_order( get_current_user_id(), \WC_Helper_Product::create_simple_product() );

		// Stands for the instance a caller holds while it saves the last fulfillment and then
		// completes the order. It is constructed directly so the order cache cannot refresh it.
		$held_order = new WC_Order( $order->get_id() );

		$this->fulfill_quantity( $order, 4 );
		$this->opt_out_of_the_completed_order_email();

		$this->assertSame( 'no_fulfillments', FulfillmentUtils::get_order_fulfillment_status( $held_order ), 'The held instance should still carry the status it was read with.' );
		$this->assertFalse( $this->complete_order_and_email_sent( $held_order ), 'A fully fulfilled order should skip the email even when the caller holds an older instance.' );
	}

	/**
	 * @testdox The "Completed order" email still sends when the caller completes an instance read before the fulfillment was deleted.
	 */
	public function test_completed_order_email_sends_for_an_order_read_before_the_fulfillment_was_deleted(): void {
		$order       = OrderHelper::create_order( get_current_user_id(), \WC_Helper_Product::create_simple_product() );
		$fulfillment = $this->fulfill_quantity( $order, 4 );

		// Read while the order was fulfilled, so the instance keeps a status the merchant has since
		// undone. It is constructed directly so the order cache cannot refresh it.
		$held_order = new WC_Order( $order->get_id() );

		$fulfillment->delete();
		$this->opt_out_of_the_completed_order_email();

		$this->assertSame( 'fulfilled', FulfillmentUtils::get_order_fulfillment_status( $held_order ), 'The held instance should still carry the status it was read with.' );
		$this->assertTrue( $this->complete_order_and_email_sent( $held_order ), 'An order whose fulfillment was deleted should keep its email.' );
	}

	/**
	 * @testdox Sending the "Completed order" email again for a fulfilled order still reaches the customer.
	 */
	public function test_completed_order_email_sends_on_a_resend_of_a_fulfilled_order(): void {
		$order = $this->create_order_with_fulfilled_quantity( 4 );
		$this->opt_out_of_the_completed_order_email();

		$this->assertTrue( $this->resend_completed_order_email( $order ), 'A resend should reach the customer, so that the store is not told about a send that never happened.' );
	}

	/**
	 * @testdox Opting out leaves the "Completed order" email alone when the email has no order, as on the settings screen.
	 */
	public function test_completed_order_email_is_untouched_without_an_order(): void {
		$order = $this->create_order_with_fulfilled_quantity( 4 );
		$this->opt_out_of_the_completed_order_email();

		// Asked while the order is being completed, so that the check runs rather than bailing out
		// because no email is being sent.
		$enabled = null;
		$probe   = function () use ( &$enabled ) {
			$enabled = $this->manager->maybe_suppress_completed_order_email( true, null );
		};
		add_action( 'woocommerce_order_status_completed', $probe );
		try {
			$order->update_status( OrderStatus::COMPLETED );
		} finally {
			remove_action( 'woocommerce_order_status_completed', $probe );
		}

		$this->assertTrue( $enabled, 'Without an order the email setting should be reported as it is.' );
	}

	/**
	 * @testdox The order is passed to the suppression filter so a callback can decide per order.
	 */
	public function test_suppression_filter_receives_the_order(): void {
		$order           = $this->create_order_with_fulfilled_quantity( 4 );
		$received_orders = array();

		add_filter(
			'woocommerce_fulfillment_suppress_completed_order_email',
			function ( $suppress, $filtered_order ) use ( &$received_orders ) {
				$received_orders[] = $filtered_order;
				return $suppress;
			},
			10,
			2
		);

		$this->complete_order_and_email_sent( $order );

		$this->assertNotEmpty( $received_orders, 'The filter should run for the email.' );
		$this->assertSame( $order, $received_orders[0], 'The filter should receive the instance the email carries.' );
	}

	/**
	 * @testdox A deferred "Completed order" email still sends for a fulfilled order when nothing opts out of it.
	 */
	public function test_deferred_completed_order_email_sends_for_fulfilled_order_by_default(): void {
		$order = $this->create_order_with_fulfilled_quantity( 4 );

		$this->assertTrue( $this->send_queued_completed_order_email( $order ), 'A deferred email should send unless a store opts out.' );
	}

	/**
	 * @testdox The "Completed order" email is skipped for a fulfilled order when it is sent from the deferred email queue.
	 */
	public function test_deferred_completed_order_email_is_skipped_for_fulfilled_order_when_opted_out(): void {
		$order = $this->create_order_with_fulfilled_quantity( 4 );
		$this->opt_out_of_the_completed_order_email();

		$this->assertFalse( $this->send_queued_completed_order_email( $order ), 'A store that defers its emails should skip this one too.' );
	}

	/**
	 * Opt the store out of the "Completed order" email for fully fulfilled orders.
	 */
	private function opt_out_of_the_completed_order_email(): void {
		add_filter( 'woocommerce_fulfillment_suppress_completed_order_email', '__return_true' );
	}

	/**
	 * Mark the order complete and report whether the "Completed order" email went out.
	 *
	 * @param WC_Order $order   The order to complete.
	 * @param string   $setting The merchant's enabled setting for the email.
	 * @return bool Whether the email was sent.
	 */
	private function complete_order_and_email_sent( WC_Order $order, string $setting = 'yes' ): bool {
		// Pinned off so the email is sent in this request instead of being queued for later.
		add_filter( 'woocommerce_defer_transactional_emails', '__return_false' );

		return $this->completed_order_email_sent(
			function () use ( $order ) {
				$order->update_status( OrderStatus::COMPLETED );
			},
			$setting
		);
	}

	/**
	 * Send the "Completed order" email again for an order, as the REST order action and the
	 * order-send-email ability both do, and report whether it went out.
	 *
	 * @param WC_Order $order The order to send the email for.
	 * @return bool Whether the email was sent.
	 */
	private function resend_completed_order_email( WC_Order $order ): bool {
		return $this->completed_order_email_sent(
			function () use ( $order ) {
				do_action( 'woocommerce_order_status_completed_notification', $order->get_id(), $order );
			}
		);
	}

	/**
	 * Send the order's "Completed order" email the way the deferred email queue does, and report
	 * whether it went out.
	 *
	 * @param WC_Order $order The order to send the email for.
	 * @return bool Whether the email was sent.
	 */
	private function send_queued_completed_order_email( WC_Order $order ): bool {
		return $this->completed_order_email_sent(
			function () use ( $order ) {
				do_action( 'woocommerce_send_queued_transactional_email', 'woocommerce_order_status_completed', array( $order->get_id(), $order ), '' );
			}
		);
	}

	/**
	 * Run the given dispatch and report whether the "Completed order" email went out.
	 *
	 * @param callable $dispatch The call that should send the email.
	 * @param string   $setting  The merchant's enabled setting for the email.
	 * @return bool Whether the email was sent.
	 */
	private function completed_order_email_sent( callable $dispatch, string $setting = 'yes' ): bool {
		// Hook-driven emails are off in tests, so nothing would be sent here whatever the
		// suppression decides.
		$this->enable_transactional_emails();

		$email          = WC()->mailer()->get_emails()['WC_Email_Customer_Completed_Order'];
		$email->enabled = $setting;

		$sent    = false;
		$capture = function ( $result, $email_id ) use ( &$sent ) {
			if ( 'customer_completed_order' === $email_id ) {
				$sent = (bool) $result;
			}
		};
		add_action( 'woocommerce_email_sent', $capture, 10, 2 );

		try {
			$dispatch();
		} finally {
			remove_action( 'woocommerce_email_sent', $capture, 10 );
		}

		return $sent;
	}

	/**
	 * Create an order of four units and fulfill the given quantity of them.
	 *
	 * @param int  $quantity Quantity to put in the fulfillment.
	 * @param bool $fulfilled Whether the fulfillment itself has shipped.
	 * @return WC_Order The order, re-read so it carries the recalculated fulfillment status.
	 */
	private function create_order_with_fulfilled_quantity( int $quantity, bool $fulfilled = true ): WC_Order {
		$order = OrderHelper::create_order( get_current_user_id(), \WC_Helper_Product::create_simple_product() );
		$this->fulfill_quantity( $order, $quantity, $fulfilled );

		return wc_get_order( $order->get_id() );
	}

	/**
	 * Create a fulfillment holding the given quantity of each item in the order.
	 *
	 * @param WC_Order $order     The order to fulfill.
	 * @param int      $quantity  Quantity to put in the fulfillment.
	 * @param bool     $fulfilled Whether the fulfillment itself has shipped.
	 * @return Fulfillment The fulfillment that was created.
	 */
	private function fulfill_quantity( WC_Order $order, int $quantity, bool $fulfilled = true ): Fulfillment {
		$items = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$items[] = array(
				'item_id' => $item_id,
				'qty'     => $quantity,
			);
		}

		return FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type'  => WC_Order::class,
				'entity_id'    => $order->get_id(),
				'status'       => $fulfilled ? 'fulfilled' : 'unfulfilled',
				'is_fulfilled' => $fulfilled,
			),
			array( '_items' => $items )
		);
	}
}
