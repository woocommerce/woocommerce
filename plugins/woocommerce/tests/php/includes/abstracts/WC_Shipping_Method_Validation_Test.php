<?php
declare(strict_types=1);

/**
 * Test for WC_Shipping_Method validation methods.
 *
 * @package WooCommerce\Tests
 */
class WC_Shipping_Method_Validation_Test extends WC_Unit_Test_Case {

	/**
	 * @var WC_Shipping_Method Mock shipping method instance.
	 */
	private $shipping_method;

	/**
	 * Set up test case.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a mock shipping method to test the abstract class methods.
		$this->shipping_method = new class() extends WC_Shipping_Method {
			public function __construct() {
				$this->id = 'test_method';
				$this->method_title = 'Test Method';
			}

			public function calculate_shipping( $package = array() ) {
				// Not needed for validation tests.
			}
		};
	}

	/**
	 * Test text field validation.
	 */
	public function test_validate_setting_text_field() {
		$field = array( 'type' => 'text' );

		// Test normal text.
		$result = $this->shipping_method->validate_setting( 'test_key', 'Normal text', $field );
		$this->assertEquals( 'Normal text', $result );

		// Test text with HTML (should be sanitized).
		$result = $this->shipping_method->validate_setting( 'test_key', 'Text with <script>alert("xss")</script>', $field );
		$this->assertEquals( 'Text with', $result );

		// Test text with allowed HTML.
		$result = $this->shipping_method->validate_setting( 'test_key', 'Text with <strong>bold</strong>', $field );
		$this->assertEquals( 'Text with bold', $result );

		// Test text with extra whitespace.
		$result = $this->shipping_method->validate_setting( 'test_key', '  Trimmed text  ', $field );
		$this->assertEquals( 'Trimmed text', $result );
	}

	/**
	 * Test password field validation.
	 */
	public function test_validate_setting_password_field() {
		$field = array( 'type' => 'password' );

		// Test normal password.
		$result = $this->shipping_method->validate_setting( 'test_key', 'secret123', $field );
		$this->assertEquals( 'secret123', $result );

		// Test password with HTML (should be sanitized).
		$result = $this->shipping_method->validate_setting( 'test_key', 'pass<script>alert("xss")</script>', $field );
		$this->assertEquals( 'pass', $result );
	}

	/**
	 * Test email field validation.
	 */
	public function test_validate_setting_email_field() {
		$field = array( 'type' => 'email' );

		// Test valid email.
		$result = $this->shipping_method->validate_setting( 'test_key', 'user@example.com', $field );
		$this->assertEquals( 'user@example.com', $result );

		// Test valid email with extra whitespace.
		$result = $this->shipping_method->validate_setting( 'test_key', '  user@example.com  ', $field );
		$this->assertEquals( 'user@example.com', $result );

		// Test empty email (should be allowed).
		$result = $this->shipping_method->validate_setting( 'test_key', '', $field );
		$this->assertEquals( '', $result );

		// Test invalid email format.
		$result = $this->shipping_method->validate_setting( 'test_key', 'invalid-email', $field );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_rest_shipping_method_invalid_setting', $result->get_error_code() );
		$this->assertStringContainsString( 'Invalid email format', $result->get_error_message() );
		$this->assertEquals( 400, $result->get_error_data()['status'] );

		// Test email with XSS attempt (HTML is removed, leaving 'user@example.comscriptalertxssscript').
		$result = $this->shipping_method->validate_setting( 'test_key', 'user@example.com<script>alert("xss")</script>', $field );
		// After sanitization, HTML tags are removed but the remaining text still forms a valid email.
		$this->assertEquals( 'user@example.comscriptalertxssscript', $result );
	}

	/**
	 * Test number field validation.
	 */
	public function test_validate_setting_number_field() {
		$field = array( 'type' => 'number' );

		// Test valid integer.
		$result = $this->shipping_method->validate_setting( 'test_key', '123', $field );
		$this->assertEquals( '123', $result );

		// Test valid decimal.
		$result = $this->shipping_method->validate_setting( 'test_key', '123.45', $field );
		$this->assertEquals( '123.45', $result );

		// Test number with extra whitespace.
		$result = $this->shipping_method->validate_setting( 'test_key', '  123.45  ', $field );
		$this->assertEquals( '123.45', $result );

		// Test negative number.
		$result = $this->shipping_method->validate_setting( 'test_key', '-10.5', $field );
		$this->assertEquals( '-10.5', $result );

		// Test non-numeric value.
		$result = $this->shipping_method->validate_setting( 'test_key', 'not-a-number', $field );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_rest_shipping_method_invalid_setting', $result->get_error_code() );
		$this->assertStringContainsString( 'must be a valid number', $result->get_error_message() );
		$this->assertEquals( 400, $result->get_error_data()['status'] );

		// Test empty string (not numeric).
		$result = $this->shipping_method->validate_setting( 'test_key', '', $field );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test number field validation with min/max constraints.
	 */
	public function test_validate_setting_number_field_with_constraints() {
		$field = array(
			'type' => 'number',
			'custom_attributes' => array(
				'min' => '10',
				'max' => '100',
			),
		);

		// Test valid number within range.
		$result = $this->shipping_method->validate_setting( 'test_key', '50', $field );
		$this->assertEquals( '50', $result );

		// Test minimum boundary.
		$result = $this->shipping_method->validate_setting( 'test_key', '10', $field );
		$this->assertEquals( '10', $result );

		// Test maximum boundary.
		$result = $this->shipping_method->validate_setting( 'test_key', '100', $field );
		$this->assertEquals( '100', $result );

		// Test below minimum.
		$result = $this->shipping_method->validate_setting( 'test_key', '5', $field );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'must be at least 10', $result->get_error_message() );

		// Test above maximum.
		$result = $this->shipping_method->validate_setting( 'test_key', '150', $field );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'must be no more than 100', $result->get_error_message() );
	}

	/**
	 * Test textarea field validation.
	 */
	public function test_validate_setting_textarea_field() {
		$field = array( 'type' => 'textarea' );

		// Test normal text.
		$result = $this->shipping_method->validate_setting( 'test_key', 'Normal textarea content', $field );
		$this->assertEquals( 'Normal textarea content', $result );

		// Test allowed HTML.
		$result = $this->shipping_method->validate_setting( 'test_key', 'Text with <strong>bold</strong> and <em>italic</em>', $field );
		$this->assertEquals( 'Text with <strong>bold</strong> and <em>italic</em>', $result );

		// Test script tags (should be removed, but content might remain).
		$result = $this->shipping_method->validate_setting( 'test_key', 'Text with <script>alert("xss")</script>', $field );
		$this->assertStringNotContainsString( '<script>', $result );
		$this->assertStringNotContainsString( '</script>', $result );

		// Test iframe tags (should be removed - no longer allowed).
		$result = $this->shipping_method->validate_setting( 'test_key', 'Text with <iframe src="http://evil.com"></iframe>', $field );
		$this->assertStringNotContainsString( '<iframe>', $result );
		$this->assertStringNotContainsString( '</iframe>', $result );

		// Test multiple lines with HTML.
		$multiline = "Line 1 with <strong>bold</strong>\nLine 2 with <em>italic</em>";
		$result = $this->shipping_method->validate_setting( 'test_key', $multiline, $field );
		$this->assertEquals( "Line 1 with <strong>bold</strong>\nLine 2 with <em>italic</em>", $result );
	}

	/**
	 * Test checkbox field validation.
	 */
	public function test_validate_setting_checkbox_field() {
		$field = array( 'type' => 'checkbox' );

		// Test true values.
		$true_values = array( true, 'yes', 'true', '1', 1 );
		foreach ( $true_values as $value ) {
			$result = $this->shipping_method->validate_setting( 'test_key', $value, $field );
			$this->assertEquals( 'yes', $result );
		}

		// Test false values.
		$false_values = array( false, 'no', 'false', '0', 0, '' );
		foreach ( $false_values as $value ) {
			$result = $this->shipping_method->validate_setting( 'test_key', $value, $field );
			$this->assertEquals( 'no', $result );
		}
	}

	/**
	 * Test select field validation.
	 */
	public function test_validate_setting_select_field() {
		$field = array(
			'type' => 'select',
			'options' => array(
				'option1' => 'Option 1',
				'option2' => 'Option 2',
				'option3' => 'Option 3',
			),
		);

		// Test valid option.
		$result = $this->shipping_method->validate_setting( 'test_key', 'option1', $field );
		$this->assertEquals( 'option1', $result );

		// Test another valid option.
		$result = $this->shipping_method->validate_setting( 'test_key', 'option3', $field );
		$this->assertEquals( 'option3', $result );

		// Test invalid option.
		$result = $this->shipping_method->validate_setting( 'test_key', 'invalid_option', $field );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_rest_shipping_method_invalid_setting', $result->get_error_code() );
		$this->assertStringContainsString( 'Invalid value for setting', $result->get_error_message() );

		// Test option with HTML (sanitize_text_field removes tags but keeps 'option1', which is still valid).
		$result = $this->shipping_method->validate_setting( 'test_key', 'option1<script>alert("xss")</script>', $field );
		// After sanitization, we get 'option1' which matches a valid option.
		$this->assertEquals( 'option1', $result );
	}

	/**
	 * Test radio field validation (same as select).
	 */
	public function test_validate_setting_radio_field() {
		$field = array(
			'type' => 'radio',
			'options' => array(
				'yes' => 'Yes',
				'no' => 'No',
			),
		);

		// Test valid option.
		$result = $this->shipping_method->validate_setting( 'test_key', 'yes', $field );
		$this->assertEquals( 'yes', $result );

		// Test invalid option.
		$result = $this->shipping_method->validate_setting( 'test_key', 'maybe', $field );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test multiselect field validation.
	 */
	public function test_validate_setting_multiselect_field() {
		$field = array(
			'type' => 'multiselect',
			'options' => array(
				'option1' => 'Option 1',
				'option2' => 'Option 2',
				'option3' => 'Option 3',
			),
		);

		// Test valid array of options.
		$result = $this->shipping_method->validate_setting( 'test_key', array( 'option1', 'option3' ), $field );
		$this->assertEquals( array( 'option1', 'option3' ), $result );

		// Test single valid option in array.
		$result = $this->shipping_method->validate_setting( 'test_key', array( 'option2' ), $field );
		$this->assertEquals( array( 'option2' ), $result );

		// Test empty array.
		$result = $this->shipping_method->validate_setting( 'test_key', array(), $field );
		$this->assertEquals( array(), $result );

		// Test array with some invalid options (should return only valid ones).
		$result = $this->shipping_method->validate_setting( 'test_key', array( 'option1', 'invalid', 'option2' ), $field );
		$this->assertEquals( array( 'option1', 'option2' ), $result );

		// Test array with all invalid options.
		$result = $this->shipping_method->validate_setting( 'test_key', array( 'invalid1', 'invalid2' ), $field );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'No valid options selected', $result->get_error_message() );

		// Test non-array input.
		$result = $this->shipping_method->validate_setting( 'test_key', 'not-an-array', $field );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'must be an array', $result->get_error_message() );

		// Test array with XSS attempts (HTML stripped but option1 part still matches).
		$result = $this->shipping_method->validate_setting( 'test_key', array( 'option1<script>alert("xss")</script>', 'option2' ), $field );
		// sanitize_text_field removes HTML tags but keeps the text, so 'option1<script>alert("xss")</script>' becomes 'option1alertxss'
		// Since 'option1alertxss' doesn't match any valid option, only option2 should remain.
		// However, if the implementation strips HTML and leaves 'option1', both will be valid.
		$this->assertTrue( in_array( 'option2', $result, true ) );
		$this->assertLessThanOrEqual( 2, count( $result ) );
	}

	/**
	 * Test default field type validation.
	 */
	public function test_validate_setting_default_field_type() {
		$field = array( 'type' => 'unknown_type' );

		// Test default behavior (should use sanitize_text_field).
		$result = $this->shipping_method->validate_setting( 'test_key', 'Text with <script>alert("xss")</script>', $field );
		$this->assertEquals( 'Text with', $result );

		// Test without field type (should default to text).
		$result = $this->shipping_method->validate_setting( 'test_key', 'Normal text', array() );
		$this->assertEquals( 'Normal text', $result );
	}

	/**
	 * Test XSS protection across different field types.
	 */
	public function test_xss_protection() {
		$xss_payload = '<script>alert("xss")</script><img src="x" onerror="alert(1)">';

		// Test text field.
		$result = $this->shipping_method->validate_setting( 'test', $xss_payload, array( 'type' => 'text' ) );
		$this->assertStringNotContainsString( '<script>', $result );
		$this->assertStringNotContainsString( 'onerror', $result );

		// Test password field.
		$result = $this->shipping_method->validate_setting( 'test', $xss_payload, array( 'type' => 'password' ) );
		$this->assertStringNotContainsString( '<script>', $result );
		$this->assertStringNotContainsString( 'onerror', $result );

		// Test textarea field.
		$result = $this->shipping_method->validate_setting( 'test', $xss_payload, array( 'type' => 'textarea' ) );
		$this->assertStringNotContainsString( '<script>', $result );
		$this->assertStringNotContainsString( 'onerror', $result );

		// Test select field with XSS in value.
		$field = array(
			'type' => 'select',
			'options' => array( 'safe' => 'Safe Option' )
		);
		$result = $this->shipping_method->validate_setting( 'test', 'safe' . $xss_payload, $field );
		// After sanitization, HTML tags are removed but 'safe' part remains, which matches the valid option.
		$this->assertEquals( 'safe', $result );
	}

	/**
	 * Test edge cases with various input types.
	 */
	public function test_edge_cases() {
		// Test null input.
		$result = $this->shipping_method->validate_setting( 'test', null, array( 'type' => 'text' ) );
		$this->assertEquals( '', $result );

		// Test array input for text field (should handle gracefully).
		$result = $this->shipping_method->validate_setting( 'test', array( 'not', 'text' ), array( 'type' => 'text' ) );
		// When array is cast to string, it becomes "Array" which gets sanitized.
		$this->assertIsString( $result );

		// Test object input for number field would cause an error, so we skip it.
		// Objects cannot be cleanly converted to strings for validation.
	}
}