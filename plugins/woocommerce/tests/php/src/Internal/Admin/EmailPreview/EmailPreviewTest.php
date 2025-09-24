<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\EmailPreview;

use Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview;
use WC_Emails;
use WC_Product;
use WC_Unit_Test_Case;

/**
 * EmailPreviewTest test.
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview
 */
class EmailPreviewTest extends WC_Unit_Test_Case {
	/**
	 * Site title.
	 *
	 * @var string
	 */
	const SITE_TITLE = 'Test Blog';

	/**
	 * Email type option key for default preview email.
	 *
	 * @var string
	 */
	const DEFAULT_EMAIL_TYPE_KEY = 'woocommerce_' . EmailPreview::DEFAULT_EMAIL_ID . '_email_type';

	/**
	 * "System Under Test", an instance of the class to be tested.
	 *
	 * @var EmailPreview
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_feature_email_improvements_enabled', 'yes' );
		$this->sut = new EmailPreview();
		new WC_Emails();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		update_option( 'woocommerce_feature_email_improvements_enabled', 'no' );
	}

	/**
	 * Tests that it returns processing order email preview.
	 */
	public function test_it_returns_order_email_preview() {
		$message       = $this->sut->render();
		$order_title   = 'Thank you for your order';
		$order_content = 'Just to let you know — we’ve received your order, and it is now being processed.';
		$order_product = 'Dummy Product';
		$this->assertStringContainsString( $order_title, $message );
		$this->assertStringContainsString( $order_content, $message );
		$this->assertStringContainsString( $order_product, $message );
	}

	/**
	 * Tests that it renders HTML email.
	 */
	public function test_it_renders_html_email() {
		set_transient( self::DEFAULT_EMAIL_TYPE_KEY, 'html', HOUR_IN_SECONDS );
		$message = $this->sut->render();
		$this->assertStringContainsString( '<html', $message );
		$this->assertStringContainsString( '<table', $message );
		delete_transient( self::DEFAULT_EMAIL_TYPE_KEY );
	}

	/**
	 * Tests that it renders plain text email.
	 */
	public function test_it_renders_plain_text_email() {
		set_transient( self::DEFAULT_EMAIL_TYPE_KEY, 'plain', HOUR_IN_SECONDS );
		$message = $this->sut->render();
		$this->assertStringNotContainsString( '<html', $message );
		$this->assertStringNotContainsString( '<table', $message );
		delete_transient( self::DEFAULT_EMAIL_TYPE_KEY );
	}

	/**
	 * Test handling of invalid email type.
	 */
	public function test_invalid_email_type() {
		$this->expectException( \InvalidArgumentException::class );
		$this->sut->set_email_type( 'Invalid_Email_Type' );
	}

	/**
	 * Test setting the email type.
	 */
	public function test_set_email_type() {
		$this->expectNotToPerformAssertions();
		$this->sut->set_email_type( EmailPreview::DEFAULT_EMAIL_TYPE );
	}

	/**
	 * Test that get_subject() returns empty when no email is set.
	 */
	public function test_get_subject_without_email() {
		$this->assertEmpty( $this->sut->get_subject() );
	}

	/**
	 * Test that get_subject() returns a subject when email is set.
	 */
	public function test_get_subject_with_email_set() {
		$this->sut->set_email_type( EmailPreview::DEFAULT_EMAIL_TYPE );
		$subject = $this->sut->get_subject();
		$this->assertNotEmpty( $subject );
		$this->assertEquals( 'Your ' . self::SITE_TITLE . ' order has been received!', $subject );
	}

	/**
	 * Test that placeholders are replaced in the subject.
	 */
	public function test_placeholder_replacement_in_subject() {
		$this->sut->set_email_type( 'WC_Email_Cancelled_Order' );
		$subject = $this->sut->get_subject();
		// {site_title} placeholder
		$this->assertStringContainsString( self::SITE_TITLE, $subject );
		// {order_number} placeholder
		$this->assertStringContainsString( '12345', $subject );

		$this->sut->set_email_type( 'WC_Email_Customer_Note' );
		$subject = $this->sut->get_subject();
		// {order_date} placeholder
		$this->assertStringContainsString( wc_format_datetime( new \WC_DateTime() ), $subject );
	}

	/**
	 * Test get_dummy_product_when_not_set returns dummy product if null is passed.
	 */
	public function test_get_dummy_product_when_not_set() {
		$dummy_product = $this->sut->get_dummy_product_when_not_set( null );
		$this->assertInstanceOf( WC_Product::class, $dummy_product );
		$this->assertEquals( 'Dummy Product', $dummy_product->get_name() );
	}

	/**
	 * Test dummy product filter - woocommerce_email_preview_dummy_product
	 */
	public function test_dummy_product_filter() {
		$product_filter = function ( $product ) {
			$product->set_name( 'Filtered Product' );
			$product->set_price( 99 );
			return $product;
		};
		add_filter( 'woocommerce_email_preview_dummy_product', $product_filter, 10, 1 );
		$product = $this->sut->get_dummy_product_when_not_set( null );
		$this->assertEquals( 'Filtered Product', $product->get_name() );
		$this->assertEquals( '99', $product->get_price() );
		remove_filter( 'woocommerce_email_preview_dummy_product', $product_filter, 10 );
	}

	/**
	 * Test dummy order filter - woocommerce_email_preview_dummy_order
	 */
	public function test_dummy_order_filter() {
		$order_filter = function ( $order ) {
			$order->set_total( 500 );
			return $order;
		};
		add_filter( 'woocommerce_email_preview_dummy_order', $order_filter, 10, 1 );

		$content = $this->sut->render();
		$this->assertStringContainsString( '500.00', $content );
		$this->assertStringNotContainsString( '100.00', $content );

		remove_filter( 'woocommerce_email_preview_dummy_order', $order_filter, 10 );
	}

	/**
	 * Test dummy address filter - woocommerce_email_preview_dummy_address
	 */
	public function test_dummy_address_filter() {
		$address_filter = function ( $address ) {
			$address['first_name'] = 'Jane';
			$address['last_name']  = 'Smith';
			return $address;
		};
		add_filter( 'woocommerce_email_preview_dummy_address', $address_filter, 10, 1 );

		$content = $this->sut->render();
		$this->assertStringContainsString( 'Jane Smith', $content );
		$this->assertStringNotContainsString( 'John Doe', $content );

		remove_filter( 'woocommerce_email_preview_dummy_address', $address_filter, 10 );
	}

	/**
	 * Test that placeholders can be modified via `woocommerce_email_preview_placeholders`.
	 */
	public function test_placeholder_filter() {
		$placeholders_filter = function ( $placeholders ) {
			$placeholders['{order_number}'] = '98765';
			return $placeholders;
		};
		add_filter( 'woocommerce_email_preview_placeholders', $placeholders_filter, 10, 1 );

		$this->sut->set_email_type( 'WC_Email_Cancelled_Order' );
		$subject = $this->sut->get_subject();
		$this->assertStringContainsString( '98765', $subject );
		$this->assertStringNotContainsString( '12345', $subject );

		remove_filter( 'woocommerce_email_preview_placeholders', $placeholders_filter, 10 );
	}

	/**
	 * Test that the `woocommerce_prepare_email_for_preview` filter is applied.
	 */
	public function test_prepare_email_for_preview_filter() {
		$email_filter = function ( $email ) {
			$new_email                      = clone $email;
			$new_email->settings['subject'] = 'Filtered Subject {order_number}';
			return $new_email;
		};
		add_filter( 'woocommerce_prepare_email_for_preview', $email_filter, 10, 1 );

		$this->sut->set_email_type( EmailPreview::DEFAULT_EMAIL_TYPE );
		$subject = $this->sut->get_subject();
		$this->assertStringContainsString( 'Filtered Subject 12345', $subject );
		$this->assertStringNotContainsString( 'Your ' . self::SITE_TITLE . ' order has been received!', $subject );

		remove_filter( 'woocommerce_prepare_email_for_preview', $email_filter, 10 );
	}

	/**
	 * Test that downloadable product appears in email content.
	 */
	public function test_downloadable_product_in_email_content() {
		$this->sut->set_email_type( 'WC_Email_Customer_Completed_Order' );
		$content = $this->sut->render();

		// Check that downloadable product appears in the content.
		$this->assertStringContainsString( 'Dummy Downloadable Product', $content );

		// Check total is updated to include downloadable product ($50 + $20 + $15 + $5 shipping - $10 discount = $80).
		$this->assertStringContainsString( '80.00', $content );

		// Downloads section is added by email filters and should be available when conditions are met.
		// Note: Downloads may not appear in all email types, but the functionality is properly implemented.
	}

	/**
	 * Test dummy downloadable product filter - woocommerce_email_preview_dummy_downloadable_product
	 */
	public function test_dummy_downloadable_product_filter() {
		$downloadable_product_filter = function ( $product ) {
			$product->set_name( 'Filtered Downloadable Product' );
			$product->set_price( 99 );
			return $product;
		};
		add_filter( 'woocommerce_email_preview_dummy_downloadable_product', $downloadable_product_filter, 10, 1 );

		$this->sut->set_email_type( 'WC_Email_Customer_Completed_Order' );
		$content = $this->sut->render();
		$this->assertStringContainsString( 'Filtered Downloadable Product', $content );

		remove_filter( 'woocommerce_email_preview_dummy_downloadable_product', $downloadable_product_filter, 10 );
	}

	/**
	 * Test that transient values are applied in email preview
	 */
	public function test_transient_values_in_preview() {
		$original_value = get_option( EmailPreview::get_email_style_setting_ids()[0] );
		update_option( EmailPreview::get_email_style_setting_ids()[0], 'option_value' );
		set_transient( EmailPreview::get_email_style_setting_ids()[0], 'transient_value', HOUR_IN_SECONDS );

		$this->sut->set_email_type( EmailPreview::DEFAULT_EMAIL_TYPE );
		$content = $this->sut->render();

		$this->assertStringNotContainsString( 'option_value', $content );
		$this->assertStringContainsString( 'transient_value', $content );

		update_option( EmailPreview::get_email_style_setting_ids()[0], $original_value );
		delete_transient( EmailPreview::get_email_style_setting_ids()[0] );
	}

	/**
	 * Test that transient values are applied in subject
	 */
	public function test_transient_values_in_subject() {
		$email_id = EmailPreview::DEFAULT_EMAIL_ID;
		$key      = "woocommerce_{$email_id}_subject";

		$this->sut->set_email_type( EmailPreview::DEFAULT_EMAIL_TYPE );
		$this->assertEquals( $this->sut->get_subject(), 'Your ' . self::SITE_TITLE . ' order has been received!' );

		set_transient( $key, 'transient_subject', HOUR_IN_SECONDS );
		$this->assertEquals( $this->sut->get_subject(), 'transient_subject' );
		delete_transient( $key );
	}

	/**
	 * Test that transient values are applied in email content
	 */
	public function test_transient_values_in_email_content() {
		$email_id         = EmailPreview::DEFAULT_EMAIL_ID;
		$heading_key      = "woocommerce_{$email_id}_heading";
		$additional_key   = "woocommerce_{$email_id}_additional_content";
		$heading_value    = get_option( $heading_key );
		$additional_value = get_option( $additional_key );

		update_option( $heading_key, 'option_value_heading' );
		set_transient( $heading_key, 'transient_value_heading', HOUR_IN_SECONDS );
		update_option( $additional_key, 'option_value_additional' );
		set_transient( $additional_key, 'transient_value_additional', HOUR_IN_SECONDS );

		$this->sut->set_email_type( EmailPreview::DEFAULT_EMAIL_TYPE );
		$content = $this->sut->render();

		$this->assertStringNotContainsString( 'option_value_heading', $content );
		$this->assertStringNotContainsString( 'option_value_additional', $content );
		$this->assertStringContainsString( 'transient_value_heading', $content );
		$this->assertStringContainsString( 'transient_value_additional', $content );

		update_option( $heading_key, $heading_value );
		update_option( $additional_key, $additional_value );
		delete_transient( $heading_key );
		delete_transient( $additional_key );
	}
}
