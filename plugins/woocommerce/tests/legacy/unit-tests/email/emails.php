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
}
