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
		unset( $_POST['_wpnonce'], $_POST['save'], $_REQUEST['_wpnonce'] );
		unset( $GLOBALS['current_tab'] );
		wp_set_current_user( 0 );
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
	 * @testdox Should preserve HTML-like characters in password field values.
	 *
	 * Password fields use minimal sanitization (trim + stripslashes only) to avoid corrupting
	 * passwords and API keys, matching WC_Settings_API::validate_password_field(). Characters
	 * like '<' and '>' are valid in secrets and must not be stripped or escaped.
	 */
	public function test_save_fields_preserves_html_like_chars_in_password_fields(): void {
		$option_name                   = 'test_password_html_preserve';
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

		$this->assertSame( '<b>bold</b>secret%E0pass', get_option( $option_name ), 'HTML-like characters should be preserved in password fields' );
	}

	/**
	 * @testdox Should preserve a lone '<' in password field values without truncation.
	 *
	 * PHP's strip_tags() treats a lone '<' as the start of a malformed HTML tag and drops
	 * everything from the '<' onward (e.g. "abc<def" becomes "abc"). Password fields must
	 * not use strip_tags() or wp_strip_all_tags() for this reason.
	 */
	public function test_save_fields_preserves_lone_less_than_in_password_fields(): void {
		$option_name                   = 'test_password_lone_lt';
		$this->option_names_to_clean[] = $option_name;
		$options                       = array(
			array(
				'id'   => $option_name,
				'type' => 'password',
			),
		);
		$data                          = array(
			$option_name => 'pass<word123',
		);

		WC_Admin_Settings::save_fields( $options, $data );

		$this->assertSame( 'pass<word123', get_option( $option_name ), 'A lone < must not truncate the password' );
	}

	/**
	 * @testdox Should preserve literal backslashes in password field values.
	 *
	 * $raw_value is already wp_unslash()ed before reaching the password case,
	 * so no additional stripslashes() should be applied — doing so would strip
	 * legitimate backslashes from API keys and secrets.
	 */
	public function test_save_fields_preserves_backslashes_in_password_fields(): void {
		$option_name                   = 'test_password_backslash';
		$this->option_names_to_clean[] = $option_name;
		$password                      = 'abc\\def';
		$options                       = array(
			array(
				'id'   => $option_name,
				'type' => 'password',
			),
		);
		// save_fields() calls wp_unslash() on $data values, matching how it handles $_POST.
		// WordPress adds magic quotes to $_POST via wp_magic_quotes(), so we must wp_slash()
		// to simulate real form submission — otherwise wp_unslash() eats real backslashes.
		$data = array(
			$option_name => wp_slash( $password ),
		);

		WC_Admin_Settings::save_fields( $options, $data );

		$this->assertSame( $password, get_option( $option_name ), 'Literal backslashes must not be stripped from passwords' );
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
		$other_option                  = 'test_other_field';
		$this->option_names_to_clean[] = $option_name;
		$this->option_names_to_clean[] = $other_option;
		$original_password             = 'existing%25secret';
		update_option( $option_name, $original_password );

		$options = array(
			array(
				'id'   => $option_name,
				'type' => 'password',
			),
			array(
				'id'   => $other_option,
				'type' => 'text',
			),
		);
		// $data includes another field but intentionally omits the password field.
		$data = array( $other_option => 'some value' );

		WC_Admin_Settings::save_fields( $options, $data );

		$this->assertSame( $original_password, get_option( $option_name ), 'Existing password should not be overwritten when field is absent from POST data' );
	}

	/**
	 * @testdox Should ignore array values for password fields and preserve the existing option.
	 */
	public function test_save_fields_ignores_array_value_for_password_field(): void {
		$option_name                   = 'test_password_array_injection';
		$this->option_names_to_clean[] = $option_name;
		$original_password             = 'existing_secret';
		update_option( $option_name, $original_password );

		$options = array(
			array(
				'id'   => $option_name,
				'type' => 'password',
			),
		);
		$data    = array( $option_name => array( 'injected' ) );

		WC_Admin_Settings::save_fields( $options, $data );

		$this->assertSame( $original_password, get_option( $option_name ), 'Array values should be rejected and existing password preserved' );
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

	/**
	 * @testdox Should label radio settings from their visible title.
	 */
	public function test_output_fields_labels_radio_setting_from_visible_title(): void {
		$options = array(
			array(
				'id'       => 'test_radio_setting',
				'title'    => 'Radio title',
				'type'     => 'radio',
				'value'    => 'abc',
				'options'  => array(
					'abc' => 'First option',
					'xyz' => 'Second option',
				),
				'desc_tip' => 'Radio help',
			),
		);

		ob_start();
		try {
			WC_Admin_Settings::output_fields( $options );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$document       = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		$loaded         = $document->loadHTML( '<table>' . $output . '</table>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$this->assertTrue( $loaded, 'The radio setting output should be valid enough for DOM parsing.' );

		$xpath = new DOMXPath( $document );

		$header      = '//th[contains(concat(" ", normalize-space(@class), " "), " titledesc ")]';
		$radio_title = $header . '/span[contains(concat(" ", normalize-space(@class), " "), " wc-settings-radio-title ")]';
		$title_text  = $radio_title . '/span[@id="test_radio_setting-title"]';
		$radio       = '//td[contains(concat(" ", normalize-space(@class), " "), " forminp-radio ")]';

		$this->assertSame( 0, $xpath->query( $header . '/label[@for="test_radio_setting"]' )->length );
		$this->assertSame( 0, $xpath->query( $radio_title . '[@id]' )->length );
		$this->assertSame( 1, $xpath->query( $title_text . '[normalize-space(.)="Radio title"]' )->length );
		$this->assertSame( 1, $xpath->query( $radio_title . '/span[contains(concat(" ", normalize-space(@class), " "), " woocommerce-help-tip ")][@aria-label="Radio help"]' )->length );
		$this->assertSame( 1, $xpath->query( $radio . '/fieldset[@aria-labelledby="test_radio_setting-title"]' )->length );
		$this->assertSame( 0, $xpath->query( $radio . '/fieldset/legend' )->length );
		$this->assertSame( 2, $xpath->query( $radio . '//input[@type="radio"]' )->length );
	}

	/**
	 * @testdox Should preserve supported checkbox description tooltip markup and remove unsupported markup.
	 */
	public function test_output_fields_normalizes_checkbox_description_tooltip_html(): void {
		$options = array(
			array(
				'id'       => 'test_checkbox_tooltip',
				'title'    => 'Checkbox title',
				'type'     => 'checkbox',
				'value'    => 'no',
				'desc_tip' => true,
				'desc'     => 'Use <strong>supported</strong> <em onclick="unsupported">formatting</em><script>unsupported</script><iframe src="https://example.com">unsupported</iframe>.',
			),
		);

		ob_start();
		try {
			WC_Admin_Settings::output_fields( $options );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$document       = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		$loaded         = $document->loadHTML( '<table>' . $output . '</table>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$this->assertTrue( $loaded, 'The checkbox setting output should be valid enough for DOM parsing.' );

		$xpath       = new DOMXPath( $document );
		$description = '//td[contains(concat(" ", normalize-space(@class), " "), " forminp-checkbox ")]/fieldset/p[contains(concat(" ", normalize-space(@class), " "), " description ")]';

		$this->assertSame( 1, $xpath->query( $description . '//strong[normalize-space(.)="supported"]' )->length );
		$this->assertSame( 1, $xpath->query( $description . '//em[normalize-space(.)="formatting"]' )->length );
		$this->assertSame( 0, $xpath->query( $description . '//*[@onclick]' )->length );
		$this->assertSame( 0, $xpath->query( $description . '//script | ' . $description . '//iframe' )->length );
	}

	/**
	 * @testdox Should keep single-page-select attributes inside their rendered contexts.
	 */
	public function test_output_fields_escapes_single_select_page_attributes(): void {
		$css   = "width: 12px;' onfocus='alert(1)";
		$class = "safe-class' data-pwned='1";
		$this->factory->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		$options = array(
			array(
				'id'    => 'test_page_setting',
				'title' => 'Page setting',
				'type'  => 'single_select_page',
				'value' => 0,
				'css'   => $css,
				'class' => $class,
			),
		);

		ob_start();
		try {
			WC_Admin_Settings::output_fields( $options );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$document       = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		$loaded         = $document->loadHTML( '<table>' . $output . '</table>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$this->assertTrue( $loaded, 'The setting output should remain valid enough for DOM parsing.' );

		$select = ( new DOMXPath( $document ) )->query( '//select[@id="test_page_setting"]' )->item( 0 );
		$this->assertInstanceOf( DOMElement::class, $select );
		$this->assertSame( $css, $select->getAttribute( 'style' ) );
		$this->assertSame( $class, $select->getAttribute( 'class' ) );
		$this->assertFalse( $select->hasAttribute( 'onfocus' ) );
		$this->assertFalse( $select->hasAttribute( 'data-pwned' ) );
	}

	/**
	 * @testdox Should not emit a shared "-title" ID for radio settings that have no ID.
	 */
	public function test_output_fields_does_not_cross_label_id_less_radio_settings(): void {
		$options = array(
			array(
				'title'   => 'First radio',
				'type'    => 'radio',
				'value'   => 'a',
				'options' => array( 'a' => 'First option' ),
			),
			array(
				'title'   => 'Second radio',
				'type'    => 'radio',
				'value'   => 'b',
				'options' => array( 'b' => 'Second option' ),
			),
		);

		ob_start();
		try {
			WC_Admin_Settings::output_fields( $options );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$document       = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		$loaded         = $document->loadHTML( '<table>' . $output . '</table>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$this->assertTrue( $loaded, 'The radio setting output should be valid enough for DOM parsing.' );

		$xpath = new DOMXPath( $document );

		$radio_title = '//th[contains(concat(" ", normalize-space(@class), " "), " titledesc ")]/span[contains(concat(" ", normalize-space(@class), " "), " wc-settings-radio-title ")]';
		$fieldset    = '//td[contains(concat(" ", normalize-space(@class), " "), " forminp-radio ")]/fieldset';

		// Both rows render, but neither emits the shared "-title" ID or an aria-labelledby pointing at it.
		$this->assertSame( 2, $xpath->query( $radio_title )->length );
		$this->assertSame( 2, $xpath->query( $fieldset )->length );
		$this->assertSame( 0, $xpath->query( $radio_title . '/span[@id="-title"]' )->length );
		$this->assertSame( 0, $xpath->query( $radio_title . '/span[@id]' )->length );
		$this->assertSame( 0, $xpath->query( $fieldset . '[@aria-labelledby]' )->length );
		// Visible titles are still rendered for both groups.
		$this->assertSame( 1, $xpath->query( $radio_title . '/span[normalize-space(.)="First radio"]' )->length );
		$this->assertSame( 1, $xpath->query( $radio_title . '/span[normalize-space(.)="Second radio"]' )->length );
	}

	/**
	 * @testdox Should treat a non-string radio setting ID as no ID rather than a shared or malformed "-title".
	 */
	public function test_output_fields_normalizes_non_string_radio_ids(): void {
		// Explicit field names isolate title-ID normalization from input naming.
		$options = array(
			array(
				'title'   => 'String zero ID radio',
				'type'    => 'radio',
				'id'      => '0',
				'value'   => 'd',
				'options' => array( 'd' => 'Fourth option' ),
			),
			array(
				'title'      => 'Boolean ID radio',
				'type'       => 'radio',
				'id'         => false,
				'field_name' => 'boolean_id_radio',
				'value'      => 'a',
				'options'    => array( 'a' => 'First option' ),
			),
			array(
				'title'      => 'Array ID radio',
				'type'       => 'radio',
				'id'         => array( 'unexpected' ),
				'field_name' => 'array_id_radio',
				'value'      => 'b',
				'options'    => array( 'b' => 'Second option' ),
			),
			array(
				'title'      => 'Object ID radio',
				'type'       => 'radio',
				'id'         => new stdClass(),
				'field_name' => 'object_id_radio',
				'value'      => 'c',
				'options'    => array( 'c' => 'Third option' ),
			),
		);

		ob_start();
		try {
			WC_Admin_Settings::output_fields( $options );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$document       = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		$loaded         = $document->loadHTML( '<table>' . $output . '</table>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$this->assertTrue( $loaded, 'The radio setting output should be valid enough for DOM parsing.' );

		$xpath = new DOMXPath( $document );

		$radio_title = '//th[contains(concat(" ", normalize-space(@class), " "), " titledesc ")]/span[contains(concat(" ", normalize-space(@class), " "), " wc-settings-radio-title ")]';
		$fieldset    = '//td[contains(concat(" ", normalize-space(@class), " "), " forminp-radio ")]/fieldset';

		// All four rows render, with the string zero ID preserved and the non-string IDs omitted.
		$this->assertSame( 4, $xpath->query( $radio_title )->length );
		$this->assertSame( 4, $xpath->query( $fieldset )->length );
		$this->assertSame( 0, $xpath->query( $radio_title . '/span[@id="-title"]' )->length );
		$this->assertSame( 1, $xpath->query( $radio_title . '/span[@id]' )->length );
		$this->assertSame( 1, $xpath->query( $fieldset . '[@aria-labelledby]' )->length );
		$this->assertSame( 1, $xpath->query( $radio_title . '/span[@id="0-title"]' )->length );
		$this->assertSame( 1, $xpath->query( $fieldset . '[@aria-labelledby="0-title"]' )->length );
		// Visible titles are still rendered for every group.
		$this->assertSame( 1, $xpath->query( $radio_title . '/span[normalize-space(.)="String zero ID radio"]' )->length );
		$this->assertSame( 1, $xpath->query( $radio_title . '/span[normalize-space(.)="Boolean ID radio"]' )->length );
		$this->assertSame( 1, $xpath->query( $radio_title . '/span[normalize-space(.)="Array ID radio"]' )->length );
		$this->assertSame( 1, $xpath->query( $radio_title . '/span[normalize-space(.)="Object ID radio"]' )->length );
	}
}
