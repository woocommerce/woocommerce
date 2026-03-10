<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Admin_Settings.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Admin_Settings_Test extends WC_Unit_Test_Case {

	/**
	 * Option names used in tests, cleaned up in tearDown().
	 *
	 * @var string[]
	 */
	private array $option_names_to_clean = array();

	/**
	 * Clean up options after each test to ensure test isolation even on assertion failure.
	 */
	public function tearDown(): void {
		foreach ( $this->option_names_to_clean as $option_name ) {
			delete_option( $option_name );
		}
		$this->option_names_to_clean = array();
		parent::tearDown();
	}

	/**
	 * @testdox Should preserve percent-encoded sequences in password fields.
	 */
	public function test_save_fields_preserves_percent_encoded_chars_in_password_fields(): void {
		$option_name                   = 'test_password_with_percent';
		$this->option_names_to_clean[] = $option_name;
		$password                      = 'NlP4%EcCx}Na';
		$options                       = array(
			array(
				'id'   => $option_name,
				'type' => 'password',
			),
		);
		$data                          = array(
			$option_name => $password,
		);

		WC_Admin_Settings::save_fields( $options, $data );

		$this->assertSame( $password, get_option( $option_name ), 'Password with %Ec sequence should be preserved' );
	}

	/**
	 * @testdox Should strip HTML tags from password field values.
	 */
	public function test_save_fields_strips_html_tags_from_password_fields(): void {
		$option_name                   = 'test_password_html_strip';
		$this->option_names_to_clean[] = $option_name;
		$options                       = array(
			array(
				'id'   => $option_name,
				'type' => 'password',
			),
		);
		$data                          = array(
			$option_name => '<b>bold</b>secret%E0pass',
		);

		WC_Admin_Settings::save_fields( $options, $data );

		$this->assertSame( 'boldsecret%E0pass', get_option( $option_name ), 'HTML tags should be stripped but percent sequences preserved' );
	}

	/**
	 * @testdox Should trim whitespace from password field values.
	 */
	public function test_save_fields_trims_whitespace_from_password_fields(): void {
		$option_name                   = 'test_password_trim';
		$this->option_names_to_clean[] = $option_name;
		$options                       = array(
			array(
				'id'   => $option_name,
				'type' => 'password',
			),
		);
		$data                          = array(
			$option_name => '  my%20password  ',
		);

		WC_Admin_Settings::save_fields( $options, $data );

		$this->assertSame( 'my%20password', get_option( $option_name ), 'Password should be trimmed but percent sequences preserved' );
	}

	/**
	 * @testdox Should not overwrite an existing password option when the field is absent from POST data.
	 */
	public function test_save_fields_does_not_overwrite_missing_password_field(): void {
		$option_name                   = 'test_password_missing';
		$this->option_names_to_clean[] = $option_name;
		$original_password             = 'existing%25secret';
		update_option( $option_name, $original_password );

		$options = array(
			array(
				'id'   => $option_name,
				'type' => 'password',
			),
		);
		// $data intentionally omits $option_name to simulate a missing field.
		$data = array();

		WC_Admin_Settings::save_fields( $options, $data );

		$this->assertSame( $original_password, get_option( $option_name ), 'Existing password should not be overwritten when field is absent from POST data' );
	}

	/**
	 * @testdox Should still sanitize text fields with wc_clean as before.
	 */
	public function test_save_fields_still_sanitizes_text_fields(): void {
		$option_name                   = 'test_text_field';
		$this->option_names_to_clean[] = $option_name;
		$options                       = array(
			array(
				'id'   => $option_name,
				'type' => 'text',
			),
		);
		$data                          = array(
			$option_name => '<b>bold</b> text',
		);

		WC_Admin_Settings::save_fields( $options, $data );

		$this->assertSame( 'bold text', get_option( $option_name ), 'Text fields should still go through wc_clean' );
	}
}
