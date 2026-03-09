<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Admin_Settings.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Admin_Settings_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Should preserve percent-encoded sequences in password fields.
	 */
	public function test_save_fields_preserves_percent_encoded_chars_in_password_fields(): void {
		$option_name = 'test_password_with_percent';
		$password    = 'NlP4%EcCx}Na';
		$options     = array(
			array(
				'id'   => $option_name,
				'type' => 'password',
			),
		);
		$data        = array(
			$option_name => $password,
		);

		WC_Admin_Settings::save_fields( $options, $data );

		$this->assertSame( $password, get_option( $option_name ), 'Password with %E sequence should be preserved' );

		delete_option( $option_name );
	}

	/**
	 * @testdox Should strip HTML tags from password field values.
	 */
	public function test_save_fields_strips_html_tags_from_password_fields(): void {
		$option_name = 'test_password_html_strip';
		$options     = array(
			array(
				'id'   => $option_name,
				'type' => 'password',
			),
		);
		$data        = array(
			$option_name => '<b>bold</b>secret%E0pass',
		);

		WC_Admin_Settings::save_fields( $options, $data );

		$this->assertSame( 'boldsecret%E0pass', get_option( $option_name ), 'HTML tags should be stripped but percent sequences preserved' );

		delete_option( $option_name );
	}

	/**
	 * @testdox Should trim whitespace from password field values.
	 */
	public function test_save_fields_trims_whitespace_from_password_fields(): void {
		$option_name = 'test_password_trim';
		$options     = array(
			array(
				'id'   => $option_name,
				'type' => 'password',
			),
		);
		$data        = array(
			$option_name => '  my%20password  ',
		);

		WC_Admin_Settings::save_fields( $options, $data );

		$this->assertSame( 'my%20password', get_option( $option_name ), 'Password should be trimmed but percent sequences preserved' );

		delete_option( $option_name );
	}

	/**
	 * @testdox Should still sanitize text fields with wc_clean as before.
	 */
	public function test_save_fields_still_sanitizes_text_fields(): void {
		$option_name = 'test_text_field';
		$options     = array(
			array(
				'id'   => $option_name,
				'type' => 'text',
			),
		);
		$data        = array(
			$option_name => '<b>bold</b> text',
		);

		WC_Admin_Settings::save_fields( $options, $data );

		$this->assertSame( 'bold text', get_option( $option_name ), 'Text fields should still go through wc_clean' );

		delete_option( $option_name );
	}
}
