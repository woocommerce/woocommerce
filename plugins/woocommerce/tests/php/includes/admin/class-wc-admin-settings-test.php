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
	 * @testdox Should resolve field values from the saved option name while preserving existing value precedence.
	 *
	 * @dataProvider output_fields_value_data
	 *
	 * @param string $option_name  Option name to store.
	 * @param mixed  $option_value Stored option value.
	 * @param array  $field        Field definition.
	 * @param string $expected     Expected rendered field value.
	 */
	public function test_output_fields_resolves_field_values( string $option_name, $option_value, array $field, string $expected ): void {
		$this->option_names_to_clean[] = $option_name;
		update_option( $option_name, $option_value );

		ob_start();
		try {
			WC_Admin_Settings::output_fields( array( $field ) );
		} finally {
			$output = ob_get_clean();
		}

		$this->assertStringContainsString( 'value="' . esc_attr( $expected ) . '"', $output );
	}

	/**
	 * Data provider for test_output_fields_resolves_field_values().
	 *
	 * @return array<string, array{string, mixed, array<string, mixed>, string}>
	 */
	public static function output_fields_value_data(): array {
		return array(
			'nested field name' => array(
				'test_output_fields_nested',
				array( 'enabled' => 'saved nested value' ),
				array(
					'id'         => 'test_output_fields_nested_enabled',
					'field_name' => 'test_output_fields_nested[enabled]',
					'type'       => 'text',
					'default'    => 'default value',
				),
				'saved nested value',
			),
			'id fallback'       => array(
				'test_output_fields_id',
				'saved scalar value',
				array(
					'id'      => 'test_output_fields_id',
					'type'    => 'text',
					'default' => 'default value',
				),
				'saved scalar value',
			),
			'explicit value'    => array(
				'test_output_fields_explicit',
				'saved option value',
				array(
					'id'    => 'test_output_fields_explicit',
					'type'  => 'text',
					'value' => 'explicit field value',
				),
				'explicit field value',
			),
		);
	}

	/**
	 * @testdox Should resolve nested option names without fataling on malformed ones.
	 *
	 * @dataProvider get_option_name_shape_data
	 *
	 * @param string $option_name Option name to look up.
	 * @param mixed  $expected    Expected resolved value.
	 */
	public function test_get_option_resolves_name_shapes( string $option_name, $expected ): void {
		$this->option_names_to_clean[] = 'test_get_option_nested';
		$this->option_names_to_clean[] = 'test_get_option_scalar';
		$this->option_names_to_clean[] = 'test_get_option_malformed_';
		$this->option_names_to_clean[] = 'test_get_option_object';
		update_option(
			'test_get_option_nested',
			array(
				'key'        => 'one level',
				'deep'       => array( 'leaf' => 'two levels' ),
				'list'       => array( 'x', 'y' ),
				'spaced key' => 'form decoded',
			)
		);
		update_option( 'test_get_option_scalar', 'abc' );
		// parse_str() turns the trailing bracket into an underscore, so this is the base it derives.
		update_option( 'test_get_option_malformed_', 'mangled base' );
		update_option( 'test_get_option_object', new ArrayObject( array( 'k' => 'array access' ) ) );

		$this->assertSame( $expected, WC_Admin_Settings::get_option( $option_name, 'DEFAULT' ) );
	}

	/**
	 * Data provider for test_get_option_resolves_name_shapes().
	 *
	 * @return array<string, array{string, mixed}>
	 */
	public static function get_option_name_shape_data(): array {
		return array(
			'single level'             => array( 'test_get_option_nested[key]', 'one level' ),
			'two levels resolve leaf'  => array( 'test_get_option_nested[deep][leaf]', 'two levels' ),
			'array value preserved'    => array( 'test_get_option_nested[list]', array( 'x', 'y' ) ),
			'multi value append'       => array( 'test_get_option_nested[list][]', array( 'x', 'y' ) ),
			'explicit numeric index'   => array( 'test_get_option_nested[list][0]', 'x' ),
			'append syntax on base'    => array(
				'test_get_option_nested[]',
				array(
					'key'        => 'one level',
					'deep'       => array( 'leaf' => 'two levels' ),
					'list'       => array( 'x', 'y' ),
					'spaced key' => 'form decoded',
				),
			),
			'plus encoded key'         => array( 'test_get_option_nested[spaced+key]', 'form decoded' ),
			'encoded closing bracket'  => array( 'test_get_option_nested[deep%5Dx]', array( 'leaf' => 'two levels' ) ),
			'percent encoded key'      => array( 'test_get_option_nested[spaced%20key]', 'form decoded' ),
			'keys after the base only' => array( 'test_get_option_nested[deep]extra[leaf]', array( 'leaf' => 'two levels' ) ),
			'array access container'   => array( 'test_get_option_object[k]', 'array access' ),
			'no parsable base'         => array( '[key]', 'DEFAULT' ),
			'unterminated bracket'     => array( 'test_get_option_malformed[', 'mangled base' ),
			'missing key'              => array( 'test_get_option_nested[absent]', 'DEFAULT' ),
			'missing intermediate key' => array( 'test_get_option_nested[absent][leaf]', 'DEFAULT' ),
			'depth beyond stored'      => array( 'test_get_option_nested[key][deeper]', 'DEFAULT' ),
			'missing option'           => array( 'test_get_option_absent[key]', 'DEFAULT' ),
			'no string offset read'    => array( 'test_get_option_scalar[0]', 'DEFAULT' ),
			'empty name'               => array( '', 'DEFAULT' ),
		);
	}

	/**
	 * @testdox Should return the default for a non-scalar option name instead of fataling.
	 */
	public function test_get_option_returns_default_for_non_scalar_name(): void {
		$this->assertSame( 'DEFAULT', WC_Admin_Settings::get_option( array( 'unexpected' ), 'DEFAULT' ) );
	}

	/**
	 * @testdox Should render a non-scalar field name using the field ID rather than fataling.
	 */
	public function test_output_fields_falls_back_to_id_for_non_scalar_field_name(): void {
		$this->option_names_to_clean[] = 'test_output_fields_bad_name';
		update_option( 'test_output_fields_bad_name', 'saved by id' );

		ob_start();
		try {
			WC_Admin_Settings::output_fields(
				array(
					array(
						'id'         => 'test_output_fields_bad_name',
						'field_name' => array( 'unexpected' ),
						'type'       => 'text',
						'default'    => 'default value',
					),
				)
			);
		} finally {
			$output = ob_get_clean();
		}

		$this->assertStringContainsString( 'value="saved by id"', $output );
		$this->assertStringContainsString( 'name="test_output_fields_bad_name"', $output );
	}

	/**
	 * @testdox Should treat an empty field name as absent and fall back to the field ID.
	 */
	public function test_output_fields_falls_back_to_id_for_empty_field_name(): void {
		$this->option_names_to_clean[] = 'test_output_fields_empty_name';
		update_option( 'test_output_fields_empty_name', 'saved by id' );

		ob_start();
		try {
			WC_Admin_Settings::output_fields(
				array(
					array(
						'id'         => 'test_output_fields_empty_name',
						'field_name' => '',
						'type'       => 'text',
						'default'    => 'default value',
					),
				)
			);
		} finally {
			$output = ob_get_clean();
		}

		$this->assertStringContainsString( 'value="saved by id"', $output );
		$this->assertStringContainsString( 'name="test_output_fields_empty_name"', $output );
	}

	/**
	 * @testdox Should save under the field ID rather than fataling when the field name is unusable.
	 *
	 * @dataProvider unusable_field_name_data
	 *
	 * @param mixed $field_name Unusable field name supplied by a field definition.
	 */
	public function test_save_fields_falls_back_to_id_for_unusable_field_name( $field_name ): void {
		$this->option_names_to_clean[] = 'test_save_fields_bad_name';

		WC_Admin_Settings::save_fields(
			array(
				array(
					'id'         => 'test_save_fields_bad_name',
					'field_name' => $field_name,
					'type'       => 'text',
				),
			),
			array( 'test_save_fields_bad_name' => 'posted value' )
		);

		$this->assertSame( 'posted value', get_option( 'test_save_fields_bad_name' ) );
	}

	/**
	 * Data provider for test_save_fields_falls_back_to_id_for_unusable_field_name().
	 *
	 * @return array<string, array{mixed}>
	 */
	public static function unusable_field_name_data(): array {
		return array(
			'array'        => array( array( 'unexpected' ) ),
			'empty string' => array( '' ),
			'null'         => array( null ),
		);
	}

	/**
	 * @testdox Should round trip a multi-value nested field without collapsing it to one element.
	 */
	public function test_save_fields_then_get_option_round_trip_multi_value(): void {
		$this->option_names_to_clean[] = 'test_round_trip_multi';
		$field                         = array(
			'id'         => 'test_round_trip_multi_choices',
			'field_name' => 'test_round_trip_multi[choices][]',
			'type'       => 'multiselect',
			'options'    => array(
				'x' => 'X',
				'y' => 'Y',
			),
		);

		WC_Admin_Settings::save_fields(
			array( $field ),
			array( 'test_round_trip_multi' => array( 'choices' => array( 'x', 'y' ) ) )
		);

		$this->assertSame(
			array( 'x', 'y' ),
			WC_Admin_Settings::get_option( 'test_round_trip_multi[choices][]', 'DEFAULT' ),
			'A [] field name must resolve to the whole stored array, not its first element'
		);
	}

	/**
	 * @testdox Should resolve to no option name when the field ID is unusable, rather than naming one.
	 *
	 * @dataProvider unusable_field_id_data
	 *
	 * @param mixed $id Unusable field ID supplied by a field definition.
	 */
	public function test_save_fields_writes_nothing_for_an_unusable_field_id( $id ): void {
		$this->setExpectedIncorrectUsage( 'WC_Admin_Settings::save_fields' );
		$this->option_names_to_clean[] = 'Array';

		$saved = WC_Admin_Settings::save_fields(
			array(
				array(
					'id'   => $id,
					'type' => 'text',
				),
			),
			array( 'anything' => 'posted value' )
		);

		$this->assertTrue( $saved, 'save_fields() still reports it ran' );
		$this->assertFalse(
			get_option( 'Array' ),
			'A non-scalar ID must not be cast into an option literally named Array'
		);
	}

	/**
	 * Data provider for test_save_fields_writes_nothing_for_an_unusable_field_id().
	 *
	 * @return array<string, array{mixed}>
	 */
	public static function unusable_field_id_data(): array {
		return array(
			'array'  => array( array( 'unexpected' ) ),
			'object' => array( new stdClass() ),
		);
	}

	/**
	 * @testdox Should skip a malformed bracket name on save rather than fataling the whole request.
	 */
	public function test_save_fields_skips_malformed_bracket_names(): void {
		$this->setExpectedIncorrectUsage( 'WC_Admin_Settings::save_fields' );
		$this->option_names_to_clean[] = 'test_save_fields_alongside';

		$saved = WC_Admin_Settings::save_fields(
			array(
				array(
					'id'         => 'test_save_fields_malformed',
					'field_name' => 'test_save_fields_malformed[',
					'type'       => 'text',
				),
				array(
					'id'   => 'test_save_fields_alongside',
					'type' => 'text',
				),
			),
			array(
				'test_save_fields_malformed' => 'ignored',
				'test_save_fields_alongside' => 'saved anyway',
			)
		);

		$this->assertTrue( $saved );
		$this->assertSame(
			'saved anyway',
			get_option( 'test_save_fields_alongside' ),
			'A malformed name must not take down the other fields on the same screen'
		);
	}

	/**
	 * @testdox Should resolve the same location on save and on read for every supported name shape.
	 *
	 * @dataProvider round_trip_shape_data
	 *
	 * @param string $field_name Field name declared by the definition.
	 * @param string $option     Option the value lands in.
	 * @param array  $posted     Posted data keyed as the browser would send it.
	 * @param mixed  $expected   Value the read path should return.
	 */
	public function test_save_and_read_resolve_the_same_location( string $field_name, string $option, array $posted, $expected ): void {
		$this->option_names_to_clean[] = $option;

		WC_Admin_Settings::save_fields(
			array(
				array(
					'id'         => 'test_symmetry_field',
					'field_name' => $field_name,
					'type'       => 'text',
				),
			),
			$posted
		);

		$this->assertSame(
			$expected,
			WC_Admin_Settings::get_option( $field_name, 'DEFAULT' ),
			sprintf( 'Read and write disagree about where %s lives', $field_name )
		);
	}

	/**
	 * Data provider for test_save_and_read_resolve_the_same_location().
	 *
	 * Each row saves through save_fields() and reads back through get_option(), so the two
	 * independent bracket parsers are compared against each other rather than each being
	 * checked against a pre-seeded option.
	 *
	 * @return array<string, array{string, string, array<string, mixed>, mixed}>
	 */
	public static function round_trip_shape_data(): array {
		return array(
			'plain name'      => array(
				'test_symmetry_plain',
				'test_symmetry_plain',
				array( 'test_symmetry_plain' => 'plain value' ),
				'plain value',
			),
			'single level'    => array(
				'test_symmetry_one[key]',
				'test_symmetry_one',
				array( 'test_symmetry_one' => array( 'key' => 'nested value' ) ),
				'nested value',
			),
			'two levels'      => array(
				'test_symmetry_two[outer][inner]',
				'test_symmetry_two',
				array( 'test_symmetry_two' => array( 'outer' => array( 'inner' => 'deep value' ) ) ),
				'deep value',
			),
			'encoded key'     => array(
				'test_symmetry_encoded[spaced+key]',
				'test_symmetry_encoded',
				array( 'test_symmetry_encoded' => array( 'spaced key' => 'encoded value' ) ),
				'encoded value',
			),
			'encoded bracket' => array(
				'test_symmetry_bracket[key%5Dx]',
				'test_symmetry_bracket',
				array( 'test_symmetry_bracket' => array( 'key' => 'bracket value' ) ),
				'bracket value',
			),
		);
	}

	/**
	 * @testdox Should render an empty name when neither the field name nor the ID can name an option.
	 */
	public function test_output_fields_renders_no_name_when_nothing_can_name_an_option(): void {
		ob_start();
		try {
			WC_Admin_Settings::output_fields(
				array(
					array(
						'id'         => array( 'unexpected' ),
						'field_name' => array( 'also unexpected' ),
						'type'       => 'text',
						'default'    => 'default value',
					),
				)
			);
		} finally {
			$output = ob_get_clean();
		}

		$this->assertStringContainsString( 'name=""', $output );
		$this->assertStringContainsString( 'value="default value"', $output );
	}

	/**
	 * @testdox Should accept a field name object that defines __toString(), as PHP coerced before.
	 */
	public function test_output_fields_accepts_a_stringable_field_name(): void {
		$this->option_names_to_clean[] = 'test_stringable_name';
		update_option( 'test_stringable_name', 'saved via stringable' );

		ob_start();
		try {
			WC_Admin_Settings::output_fields(
				array(
					array(
						'id'         => 'test_stringable_id',
						'field_name' => new WC_Admin_Settings_Test_Stringable_Name(),
						'type'       => 'text',
						'default'    => 'default value',
					),
				)
			);
		} finally {
			$output = ob_get_clean();
		}

		$this->assertStringContainsString( 'value="saved via stringable"', $output );
	}

	/**
	 * @testdox Should render the value a nested field just saved, exercising both parsing paths.
	 */
	public function test_save_fields_then_output_fields_round_trip(): void {
		$this->option_names_to_clean[] = 'test_round_trip';
		$field                         = array(
			'id'         => 'test_round_trip_enabled',
			'field_name' => 'test_round_trip[enabled]',
			'type'       => 'text',
			'default'    => 'default value',
		);

		WC_Admin_Settings::save_fields(
			array( $field ),
			array( 'test_round_trip' => array( 'enabled' => 'round trip value' ) )
		);

		$this->assertSame(
			array( 'enabled' => 'round trip value' ),
			get_option( 'test_round_trip' ),
			'save_fields() should persist under the field_name-derived option and key'
		);

		ob_start();
		try {
			WC_Admin_Settings::output_fields( array( $field ) );
		} finally {
			$output = ob_get_clean();
		}

		$this->assertStringContainsString( 'value="round trip value"', $output );
	}

	/**
	 * Clean up options after each test to ensure test isolation even on assertion failure.
	 */
	public function tearDown(): void {
		foreach ( $this->option_names_to_clean as $option_name ) {
			delete_option( $option_name );
		}
		$this->option_names_to_clean = array();
		unset( $_POST['_wpnonce'], $_POST['save'], $_POST['wc_settings_ui_redirect_to'], $_REQUEST['_wpnonce'] );
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
	 * @testdox Should redirect to the requested Settings UI destination after saving.
	 */
	public function test_save_redirects_to_settings_ui_destination(): void {
		$redirect_to = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=bacs' );
		$this->prepare_settings_save_request( $redirect_to );

		$intercept_redirect = function ( string $location ) use ( $redirect_to ): string {
			$this->assertSame( $redirect_to, $location );
			throw new RuntimeException( 'wp_redirect intercepted.' );
		};
		add_filter( 'wp_redirect', $intercept_redirect );

		try {
			$this->expectException( RuntimeException::class );
			$this->expectExceptionMessage( 'wp_redirect intercepted.' );

			WC_Admin_Settings::save();
		} finally {
			remove_filter( 'wp_redirect', $intercept_redirect );
		}
	}

	/**
	 * @testdox Should not redirect after a standard settings save without a Settings UI destination.
	 */
	public function test_save_does_not_redirect_without_settings_ui_destination(): void {
		$this->prepare_settings_save_request();

		$redirect_attempted = false;
		$intercept_redirect = function ( string $location ) use ( &$redirect_attempted ): string {
			$redirect_attempted = true;
			throw new RuntimeException( 'Unexpected redirect to ' . esc_url_raw( $location ) . '.' );
		};
		add_filter( 'wp_redirect', $intercept_redirect );

		try {
			WC_Admin_Settings::save();
		} finally {
			remove_filter( 'wp_redirect', $intercept_redirect );
		}

		$this->assertFalse( $redirect_attempted );
	}

	/**
	 * @testdox Should ignore unsafe Settings UI redirect destinations after saving.
	 */
	public function test_save_ignores_unsafe_settings_ui_destination(): void {
		$this->prepare_settings_save_request( 'https://example.invalid/wp-admin/admin.php?page=wc-settings' );

		$redirect_attempted = false;
		$intercept_redirect = function ( string $location ) use ( &$redirect_attempted ): string {
			$redirect_attempted = true;
			throw new RuntimeException( 'Unexpected redirect to ' . esc_url_raw( $location ) . '.' );
		};
		add_filter( 'wp_redirect', $intercept_redirect );

		try {
			WC_Admin_Settings::save();
		} finally {
			remove_filter( 'wp_redirect', $intercept_redirect );
		}

		$this->assertFalse( $redirect_attempted );
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

	/**
	 * Prepare globals used by WC_Admin_Settings::save().
	 *
	 * @param string|null $redirect_to Requested redirect target, or null to omit the Settings UI redirect field.
	 */
	private function prepare_settings_save_request( ?string $redirect_to = null ): void {
		global $current_tab;

		$current_tab = 'settings_ui_redirect_test';
		$this->login_as_administrator();

		$nonce = wp_create_nonce( 'woocommerce-settings' );

		$_POST['_wpnonce']    = $nonce;
		$_POST['save']        = 'Save changes';
		$_REQUEST['_wpnonce'] = $nonce;

		if ( null !== $redirect_to ) {
			$_POST['wc_settings_ui_redirect_to'] = $redirect_to;
		}
	}
}

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- Test fixture for the Stringable field name case.
/**
 * Field name object used by test_output_fields_accepts_a_stringable_field_name().
 */
class WC_Admin_Settings_Test_Stringable_Name {

	/**
	 * Render as the option name.
	 *
	 * @return string
	 */
	public function __toString() {
		return 'test_stringable_name';
	}
}
