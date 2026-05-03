<?php
/**
 * Test for the email class.
 * @package WooCommerce\Tests\Emails
 */

use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * WC_Tests_WC_Emails.
 *
 * @covers \WC_Email
 */
class WC_Tests_WC_Emails extends WC_Unit_Test_Case {


	/**
	 * Setup tests.
	 */
	public function setUp(): void {
		parent::setUp();

		// Load email classes.
		$emails = new WC_Emails();
		$emails->init();
	}

	/**
	 * Test get and set items.
	 */
	public function test_style_inline() {
		$email = new WC_Email();

		// Test HTML email with inline styles.
		$email->email_type = 'html';

		// Set some content to get converted.
		$result = $email->style_inline( '<p class="text">Hello World!</p>' );

		ob_start();
		include WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/sample-email.html';
		$expected = ob_get_clean();

		$this->assertEquals( $expected, $result );

		// Test plain text email.
		$email->email_type = 'plain';

		// Set some content to get converted.
		$result   = $email->style_inline( '<p class="text">Hello World!</p>' );
		$expected = '<p class="text">Hello World!</p>';

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test that we remove elements with style display none from html mails.
	 */
	public function test_remove_display_none_elements() {
		$email             = new WC_Email();
		$email->email_type = 'html';
		$str_present       = 'Should be present!';
		$str_removed       = 'Should be removed!';
		$result            = $email->style_inline( "<div><div class='text'>$str_present</div><div style='display: none'>$str_removed</div> </div>" );
		$this->assertTrue( false !== strpos( $result, $str_present ) );
		$this->assertTrue( false === strpos( $result, $str_removed ) );
	}

	/**
	 * Test that headers are properly generated.
	 */
	public function test_headers() {
		$email             = new WC_Email();
		$email->id         = 'headers_test';
		$email->email_type = 'html';

		$result = $email->get_headers();
		$this->assertTrue( false !== strpos( $result, 'Content-Type: text/html' ) );
		$this->assertTrue( false === strpos( $result, 'Cc:' ) );
		$this->assertTrue( false === strpos( $result, 'Bcc:' ) );
	}

	/**
	 * Test that headers are properly generated in email improvements.
	 */
	public function test_headers_with_enabled_email_improvements() {
		$features_controller = wc_get_container()->get( FeaturesController::class );
		$original_value      = $features_controller->feature_is_enabled( 'email_improvements' );
		$features_controller->change_feature_enable( 'email_improvements', true );

		$email             = new WC_Email();
		$email->id         = 'headers_test';
		$email->email_type = 'plain';
		$email->cc         = '   cc@example.com, invalid$&*^&%_email,      valid@email.com    ';
		$email->bcc        = '     invalid value, header should be skipped     ';

		$result = $email->get_headers();
		$this->assertTrue( false !== strpos( $result, 'Content-Type: text/plain' ) );
		$this->assertTrue( false !== strpos( $result, 'Cc: cc@example.com, valid@email.com' ) );
		$this->assertTrue( false === strpos( $result, 'Bcc:' ) );

		$features_controller->change_feature_enable( 'email_improvements', $original_value );
	}

	/**
	 * Test basic placeholder replacement in format_string.
	 */
	public function test_format_string_basic_placeholders() {
		$email               = new WC_Email();
		$email->placeholders = array(
			'{site_title}'   => 'Test Store',
			'{site_address}' => 'teststore.com',
		);
		$this->assertEquals(
			'Welcome to Test Store at teststore.com!',
			$email->format_string( 'Welcome to {site_title} at {site_address}!' )
		);
	}

	/**
	 * Test legacy blogname placeholder in format_string.
	 */
	public function test_format_string_legacy_blogname() {
		$email = new WC_Email();
		$this->assertEquals(
			'Welcome to ' . $email->get_blogname(),
			$email->format_string( 'Welcome to {blogname}' )
		);
	}

	/**
	 * Test legacy find/replace arrays in format_string.
	 */
	public function test_format_string_legacy_find_replace() {
		$email          = new WC_Email();
		$email->find    = array( '{order_number}' );
		$email->replace = array( '12345' );
		$this->assertEquals(
			'Order: 12345',
			$email->format_string( 'Order: {order_number}' )
		);
	}

	/**
	 * Test mixed legacy and new placeholders in format_string.
	 */
	public function test_format_string_mixed_placeholders() {
		$email               = new WC_Email();
		$email->placeholders = array(
			'{customer_name}' => 'John Doe',
		);
		$email->find         = array( '{order_date}' );
		$email->replace      = array( '2024-01-01' );
		$this->assertEquals(
			'Dear John Doe, your order from 2024-01-01 has been received',
			$email->format_string( 'Dear {customer_name}, your order from {order_date} has been received' )
		);
	}

	/**
	 * Test filter modification in format_string.
	 */
	public function test_format_string_filter_modification() {
		$email = new WC_Email();

		add_filter(
			'woocommerce_email_format_string_replace',
			function ( $replace ) {
				$replace['customer-name'] = 'Jane Smith';
				return $replace;
			},
			10
		);
		add_filter(
			'woocommerce_email_format_string_find',
			function ( $find ) {
				$find['customer-name'] = '{customer_name}';
				return $find;
			},
			10
		);

		$result = $email->format_string( 'Hello {customer_name}' );
		$this->assertEquals( 'Hello Jane Smith', $result );

		// Clean up filters.
		remove_all_filters( 'woocommerce_email_format_string_replace' );
		remove_all_filters( 'woocommerce_email_format_string_find' );
	}

	/**
	 * Test legacy blogname placeholder with filter override in format_string.
	 * Note: blogname is a special case in that it is not a placeholder, but a string when in the scope of the filters.
	 * So for users wanting to support this replacement string, they need to use both filters.
	 */
	public function test_format_string_legacy_blogname_filter_override() {
		$email = new WC_Email();

		add_filter(
			'woocommerce_email_format_string_replace',
			function ( $replace ) {
				$replace['blogname'] = 'My Custom Shop Name';
				return $replace;
			},
			10
		);
		add_filter(
			'woocommerce_email_format_string_find',
			function ( $find ) {
				$find['blogname'] = '{blogname}';
				return $find;
			},
			10
		);

		$this->assertEquals(
			'Welcome to My Custom Shop Name!',
			$email->format_string( 'Welcome to {blogname}!' ),
			'Legacy filters should be able to override the default blogname replacement but cannot due to a bug'
		);

		// Clean up filters.
		remove_all_filters( 'woocommerce_email_format_string_replace' );
		remove_all_filters( 'woocommerce_email_format_string_find' );
	}

	/**
	 * Test main filter override in format_string.
	 */
	public function test_format_string_main_filter_override() {
		$email = new WC_Email();

		add_filter(
			'woocommerce_email_format_string',
			function () {
				return 'Completely overridden';
			},
			10
		);

		$this->assertEquals(
			'Completely overridden',
			$email->format_string( 'Original text with {customer_name}' )
		);

		// Clean up.
		remove_all_filters( 'woocommerce_email_format_string' );
	}

	/**
	 * Test empty string handling in format_string.
	 */
	public function test_format_string_empty_string() {
		$email = new WC_Email();
		$this->assertEquals( '', $email->format_string( '' ) );
	}

	/**
	 * Test string without placeholders in format_string.
	 */
	public function test_format_string_no_placeholders() {
		$email = new WC_Email();
		$this->assertEquals(
			'Just a regular string',
			$email->format_string( 'Just a regular string' )
		);
	}

	/**
	 * Test partial/invalid placeholders in format_string.
	 */
	public function test_format_string_partial_placeholders() {
		$email               = new WC_Email();
		$email->placeholders = array(
			'{test}' => 'TEST',
		);
		$this->assertEquals(
			'Partial {test TEST {test_invalid}',
			$email->format_string( 'Partial {test {test} {test_invalid}' )
		);
	}

	/**
	 * Test multiple occurrences of same placeholder in format_string.
	 */
	public function test_format_string_repeated_placeholders() {
		$email               = new WC_Email();
		$email->placeholders = array(
			'{repeat}' => 'REPLACED',
		);
		$this->assertEquals(
			'REPLACED REPLACED REPLACED',
			$email->format_string( '{repeat} {repeat} {repeat}' )
		);
	}

	/**
	 * Test null input handling in format_string.
	 */
	public function test_format_string_null_input() {
		$email = new WC_Email();
		$this->assertEquals( '', $email->format_string( null ) );
	}

	/**
	 * Test reply-to getters return correct values.
	 */
	public function test_reply_to_getters() {
		update_option( 'woocommerce_email_reply_to_enabled', 'yes' );
		update_option( 'woocommerce_email_reply_to_name', 'Support Team' );
		update_option( 'woocommerce_email_reply_to_address', 'support@example.com' );

		$email = new WC_Email();

		$this->assertTrue( $email->get_reply_to_enabled() );
		$this->assertEquals( 'Support Team', $email->get_reply_to_name() );
		$this->assertEquals( 'support@example.com', $email->get_reply_to_address() );

		// Clean up.
		delete_option( 'woocommerce_email_reply_to_enabled' );
		delete_option( 'woocommerce_email_reply_to_name' );
		delete_option( 'woocommerce_email_reply_to_address' );
	}

	/**
	 * Test reply-to getters when disabled.
	 */
	public function test_reply_to_getters_when_disabled() {
		update_option( 'woocommerce_email_reply_to_enabled', 'no' );

		$email = new WC_Email();

		$this->assertFalse( $email->get_reply_to_enabled() );

		// Clean up.
		delete_option( 'woocommerce_email_reply_to_enabled' );
	}

	/**
	 * Test reply-to getters sanitize values.
	 */
	public function test_reply_to_getters_sanitize() {
		update_option( 'woocommerce_email_reply_to_name', '<script>alert("xss")</script>Support' );
		update_option( 'woocommerce_email_reply_to_address', '  support@example.com  ' );

		$email = new WC_Email();

		$this->assertEquals( 'Support', $email->get_reply_to_name() );
		$this->assertEquals( 'support@example.com', $email->get_reply_to_address() );

		// Clean up.
		delete_option( 'woocommerce_email_reply_to_name' );
		delete_option( 'woocommerce_email_reply_to_address' );
	}

	/**
	 * Test headers include custom reply-to when enabled.
	 */
	public function test_headers_with_custom_reply_to() {
		update_option( 'woocommerce_email_reply_to_enabled', 'yes' );
		update_option( 'woocommerce_email_reply_to_name', 'Support Team' );
		update_option( 'woocommerce_email_reply_to_address', 'support@example.com' );

		$email             = new WC_Email();
		$email->id         = 'customer_processing_order';
		$email->email_type = 'html';

		$result = $email->get_headers();

		$this->assertStringContainsString( 'Reply-to: Support Team <support@example.com>', $result );

		// Clean up.
		delete_option( 'woocommerce_email_reply_to_enabled' );
		delete_option( 'woocommerce_email_reply_to_name' );
		delete_option( 'woocommerce_email_reply_to_address' );
	}

	/**
	 * Test headers use from name when reply-to name is empty.
	 */
	public function test_headers_reply_to_fallback_to_from_name() {
		update_option( 'woocommerce_email_reply_to_enabled', 'yes' );
		update_option( 'woocommerce_email_reply_to_name', '' );
		update_option( 'woocommerce_email_reply_to_address', 'support@example.com' );
		update_option( 'woocommerce_email_from_name', 'My Store' );

		$email             = new WC_Email();
		$email->id         = 'customer_processing_order';
		$email->email_type = 'html';

		$result = $email->get_headers();

		$this->assertStringContainsString( 'Reply-to: My Store <support@example.com>', $result );

		// Clean up.
		delete_option( 'woocommerce_email_reply_to_enabled' );
		delete_option( 'woocommerce_email_reply_to_name' );
		delete_option( 'woocommerce_email_reply_to_address' );
		delete_option( 'woocommerce_email_from_name' );
	}

	/**
	 * Test admin notifications still use customer billing email as reply-to.
	 */
	public function test_headers_admin_notifications_use_customer_email() {
		update_option( 'woocommerce_email_reply_to_enabled', 'yes' );
		update_option( 'woocommerce_email_reply_to_name', 'Support Team' );
		update_option( 'woocommerce_email_reply_to_address', 'support@example.com' );

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order->set_billing_email( 'customer@example.com' );
		$order->set_billing_first_name( 'John' );
		$order->set_billing_last_name( 'Doe' );
		$order->save();

		$email             = new WC_Email();
		$email->id         = 'new_order';
		$email->email_type = 'html';
		$email->object     = $order;

		$result = $email->get_headers();

		// Should use customer email, not custom reply-to.
		$this->assertStringContainsString( 'Reply-to: John Doe <customer@example.com>', $result );
		$this->assertStringNotContainsString( 'support@example.com', $result );

		// Clean up.
		delete_option( 'woocommerce_email_reply_to_enabled' );
		delete_option( 'woocommerce_email_reply_to_name' );
		delete_option( 'woocommerce_email_reply_to_address' );
	}

	/**
	 * Test headers don't include reply-to when disabled.
	 */
	public function test_headers_without_custom_reply_to() {
		update_option( 'woocommerce_email_reply_to_enabled', 'no' );
		update_option( 'woocommerce_email_from_name', 'My Store' );
		update_option( 'woocommerce_email_from_address', 'store@example.com' );

		$email             = new WC_Email();
		$email->id         = 'customer_processing_order';
		$email->email_type = 'html';

		$result = $email->get_headers();

		// Should use from address as reply-to.
		$this->assertStringContainsString( 'Reply-to: My Store <store@example.com>', $result );

		// Clean up.
		delete_option( 'woocommerce_email_reply_to_enabled' );
		delete_option( 'woocommerce_email_from_name' );
		delete_option( 'woocommerce_email_from_address' );
	}

	/**
	 * Test reply-to filters are applied.
	 */
	public function test_reply_to_filters() {
		update_option( 'woocommerce_email_reply_to_enabled', 'yes' );
		update_option( 'woocommerce_email_reply_to_name', 'Support' );
		update_option( 'woocommerce_email_reply_to_address', 'support@example.com' );

		add_filter(
			'woocommerce_email_reply_to_enabled',
			function () {
				return false;
			}
		);

		add_filter(
			'woocommerce_email_reply_to_name',
			function () {
				return 'Filtered Name';
			}
		);

		add_filter(
			'woocommerce_email_reply_to_address',
			function () {
				return 'filtered@example.com';
			}
		);

		$email = new WC_Email();

		$this->assertFalse( $email->get_reply_to_enabled() );
		$this->assertEquals( 'Filtered Name', $email->get_reply_to_name() );
		$this->assertEquals( 'filtered@example.com', $email->get_reply_to_address() );

		// Clean up.
		remove_all_filters( 'woocommerce_email_reply_to_enabled' );
		remove_all_filters( 'woocommerce_email_reply_to_name' );
		remove_all_filters( 'woocommerce_email_reply_to_address' );
		delete_option( 'woocommerce_email_reply_to_enabled' );
		delete_option( 'woocommerce_email_reply_to_name' );
		delete_option( 'woocommerce_email_reply_to_address' );
	}

	/**
	 * Test headers don't include reply-to when address is invalid.
	 */
	public function test_headers_with_invalid_reply_to_address() {
		update_option( 'woocommerce_email_reply_to_enabled', 'yes' );
		update_option( 'woocommerce_email_reply_to_name', 'Support' );
		update_option( 'woocommerce_email_reply_to_address', 'invalid-email' );
		update_option( 'woocommerce_email_from_name', 'My Store' );
		update_option( 'woocommerce_email_from_address', 'store@example.com' );

		$email             = new WC_Email();
		$email->id         = 'customer_processing_order';
		$email->email_type = 'html';

		$result = $email->get_headers();

		// Should fallback to from address since reply-to address is invalid.
		$this->assertStringContainsString( 'Reply-to: My Store <store@example.com>', $result );
		$this->assertStringNotContainsString( 'invalid-email', $result );

		// Clean up.
		delete_option( 'woocommerce_email_reply_to_enabled' );
		delete_option( 'woocommerce_email_reply_to_name' );
		delete_option( 'woocommerce_email_reply_to_address' );
		delete_option( 'woocommerce_email_from_name' );
		delete_option( 'woocommerce_email_from_address' );
	}
}
