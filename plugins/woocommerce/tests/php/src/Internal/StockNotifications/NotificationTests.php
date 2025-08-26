<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\HasherHelper;

/**
 * NotificationTests data tests.
 */
class NotificationTests extends \WC_Unit_Test_Case {

	/**
	 * @after
	 */
	public function tearDown(): void {
		parent::tearDown();
		// Clean up all notifications.
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notifications" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notificationmeta" );
	}

	/**
	 * Test the product getter.
	 */
	public function test_product_getter() {

		$product      = \WC_Helper_Product::create_simple_product();
		$product2     = \WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->save();

		$notification_product = $notification->get_product();
		$this->assertInstanceOf( \WC_Product::class, $notification_product );
		$this->assertEquals( $product->get_id(), $notification_product->get_id() );

		$notification->set_product_id( $product2->get_id() );
		$notification->save();

		$notification_product = $notification->get_product();
		$this->assertEquals( $product2->get_id(), $notification_product->get_id() );
	}

	/**
	 * Test the get_product_formatted_variation_list method.
	 */
	public function test_get_product_formatted_variation_list() {

		// A mix of posted and variation attributes similar to how are formatted in WC_Cart::add_to_cart().
		// "attribute_[name]" for posted variation attributes and "attribute_pa_[name]" for product attributes (any on the variation).
		$posted_attributes = array(
			'attribute_size'      => 'small',
			'attribute_pa_colour' => 'red', // Any attribute on the variation.
		);

		$variable_product = \WC_Helper_Product::create_variation_product();
		$variation_id     = $variable_product->get_children()[0]; // This only uses the "size" as variation attribute.

		// 1. Test that the variable parent returns an empty string.
		$notification = new Notification();
		$notification->set_product_id( $variable_product->get_id() );
		$notification->set_user_email( 'test@example.com' );
		$notification->save();
		$formatted_variation_attributes = $notification->get_product_formatted_variation_list( true );
		$this->assertEquals( '', $formatted_variation_attributes );

		// 2. Test that the variation returns the formatted variation attributes.
		$notification->set_product_id( $variation_id );
		$notification->save();
		$formatted_variation_attributes = $notification->get_product_formatted_variation_list( true );
		$this->assertEquals( 'size: small', $formatted_variation_attributes );

		// 3. Test that the variation returns the formatted variation attributes with posted attributes (any attribute on the variation).
		$notification->add_meta_data( 'posted_attributes', $posted_attributes );
		$notification->save();
		$formatted_variation_attributes = $notification->get_product_formatted_variation_list( true );
		$this->assertEquals( 'size: small, colour: red', $formatted_variation_attributes );
		$formatted_variation_attributes = $notification->get_product_formatted_variation_list( false );
		$this->assertEquals( '<dl class="variation"><dt>size:</dt><dd>small</dd><dt>colour:</dt><dd>red</dd></dl>', $formatted_variation_attributes );
	}

	/**
	 * Test the get_product_permalink method.
	 */
	public function test_get_product_permalink() {
		$product      = \WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_email( 'test@example.com' );
		$notification->save();

		$permalink = $notification->get_product_permalink();
		$this->assertEquals( $product->get_permalink(), $permalink );

		$variable_product  = \WC_Helper_Product::create_variation_product();
		$variation_id      = $variable_product->get_children()[0]; // This only uses the "size" as variation attribute.
		$variation_product = wc_get_product( $variation_id );
		$notification->set_product_id( $variation_id );
		$notification->save();
		$permalink = $notification->get_product_permalink();
		$this->assertEquals( $variation_product->get_permalink(), $permalink );
	}

	/**
	 * Test the get_product_name method.
	 */
	public function test_get_product_name() {
		$product      = \WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_email( 'test@example.com' );
		$notification->save();

		$product_name = $notification->get_product_name();
		$this->assertEquals( $product->get_name(), $product_name );

		$variable_product  = \WC_Helper_Product::create_variation_product();
		$variation_id      = $variable_product->get_children()[0]; // This only uses the "size" as variation attribute.
		$variation_product = wc_get_product( $variation_id );
		$notification->set_product_id( $variation_id );
		$notification->save();
		$product_name = $notification->get_product_name();
		$this->assertEquals( $variation_product->get_name(), $product_name );
	}

	/**
	 * Test getting and checking unsubscribe key.
	 */
	public function test_get_and_check_unsubscribe_key() {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_email( 'test@example.com' );
		$notification->save();

		$key = $notification->get_unsubscribe_key( false );
		$this->assertIsString( $key );
		$this->assertTrue( $notification->check_unsubscribe_key( $key ) );
		$this->assertFalse( $notification->check_unsubscribe_key( 'invalid_key' ) );
	}

	/**
	 * Test getting and checking verification key.
	 */
	public function test_get_and_check_verification_key() {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_email( 'test@example.com' );
		$notification->save();

		$key = $notification->get_verification_key( false );
		$this->assertIsString( $key );
		$this->assertTrue( $notification->check_verification_key( $key ) );
		$this->assertFalse( $notification->check_verification_key( 'invalid_key' ) );
	}

	/**
	 * Test verification key persistence.
	 */
	public function test_verification_key_persistence() {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_email( 'test@example.com' );
		$notification->save();

		$key = $notification->get_verification_key( false );
		$this->assertIsString( $key );

		// Refetch.
		$notification = new Notification( $notification->get_id() );
		$this->assertEmpty( $notification->get_meta( 'email_link_action_key' ) );
		$this->assertFalse( $notification->check_verification_key( $key ) );

		// Re-create.
		$key2 = $notification->get_verification_key( true );
		$this->assertNotEquals( $key, $key2 );

		// Refetch.
		$notification = new Notification( $notification->get_id() );
		$this->assertNotEmpty( $notification->get_meta( 'email_link_action_key' ) );
		$this->assertTrue( $notification->check_verification_key( $key2 ) );
	}

	/**
	 * Test persistance of the unsubscribe key.
	 */
	public function test_unsubscribe_key_persistence() {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_email( 'test@example.com' );
		$notification->save();

		$key = $notification->get_unsubscribe_key( false );
		$this->assertIsString( $key );

		// Refetch.
		$notification = new Notification( $notification->get_id() );
		$this->assertEmpty( $notification->get_meta( 'email_link_action_key' ) );
		$this->assertFalse( $notification->check_unsubscribe_key( $key ) );

		// Re-create.
		$key2 = $notification->get_unsubscribe_key( true );
		$this->assertNotEquals( $key, $key2 );

		// Refetch.
		$notification = new Notification( $notification->get_id() );
		$this->assertNotEmpty( $notification->get_meta( 'email_link_action_key' ) );
		$this->assertTrue( $notification->check_unsubscribe_key( $key2 ) );
	}

	/**
	 * Test verification key expiration.
	 */
	public function test_verification_key_expiration() {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_email( 'test@example.com' );
		$notification->save();

		// Save a custom key.
		$key = time() - Config::get_verification_expiration_time_threshold() - 1 . ':' . HasherHelper::wp_fast_hash( 'test' );
		$notification->update_meta_data( 'email_link_action_key', $key );
		$notification->save();

		$this->assertFalse( $notification->check_verification_key( 'test' ) );
	}
}
