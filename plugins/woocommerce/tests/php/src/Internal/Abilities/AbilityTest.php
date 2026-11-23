<?php
/**
 * AbilityTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\Abilities\Ability;

/**
 * Tests for the Ability class.
 */
class AbilityTest extends \WC_Unit_Test_Case {

	/**
	 * Input that the execute and permission callbacks of the test ability received.
	 *
	 * @var array
	 */
	private $received = array();

	/**
	 * Assert that an invalid output error has the retry hint once: from WooCommerce on the
	 * legacy error code, from WordPress on the codes that WordPress 7.2.1 adds.
	 *
	 * @param \WP_Error $error The invalid output error.
	 */
	private function assert_retry_hint_once( \WP_Error $error ): void {
		$message  = $error->get_error_message();
		$woo_hint = 'Check the current state before you run it again';
		if ( 'ability_invalid_output' === $error->get_error_code() ) {
			$this->assertStringContainsString( $woo_hint, $message );
		} else {
			$this->assertSame( 'ability_output_schema_mismatch', $error->get_error_code() );
			$this->assertStringContainsString( 'Check the outcome before retrying', $message );
			$this->assertStringNotContainsString( $woo_hint, $message );
		}
	}

	/**
	 * Build a test ability with a boolean, an integer, a list of unique integers and a nested boolean field.
	 *
	 * @return Ability
	 */
	private function get_ability(): Ability {
		$this->received = array();

		return new Ability(
			'test/convert-input',
			array(
				'label'               => 'Convert input',
				'description'         => 'Returns its input.',
				'category'            => 'woocommerce',
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'text'  => array( 'type' => 'string' ),
						'flag'  => array( 'type' => 'boolean' ),
						'count' => array( 'type' => 'integer' ),
						'page'  => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
						'ids'   => array(
							'type'        => 'array',
							'uniqueItems' => true,
							'items'       => array( 'type' => 'integer' ),
						),
						'items' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array( 'delete' => array( 'type' => 'boolean' ) ),
							),
						),
					),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => function ( array $input ) {
					$this->received['execute'] = $input;
					return $input;
				},
				'permission_callback' => function ( array $input ) {
					$this->received['permission'] = $input;
					return true;
				},
			)
		);
	}

	/**
	 * @testdox Should say that the callback ran when the output is invalid, so the caller checks the state before a retry.
	 */
	public function test_invalid_output_says_the_callback_ran(): void {
		$runs    = 0;
		$ability = new Ability(
			'test/invalid-output',
			array(
				'label'               => 'Invalid output',
				'description'         => 'Returns output that does not match its schema.',
				'category'            => 'woocommerce',
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => function () use ( &$runs ) {
					++$runs;
					return 'not an object';
				},
				'permission_callback' => '__return_true',
			)
		);

		$result = $ability->execute();

		$this->assertSame( 1, $runs );
		$this->assertWPError( $result );
		$this->assert_retry_hint_once( $result );
	}

	/**
	 * @testdox Should keep the original message when a read-only ability returns invalid output.
	 */
	public function test_invalid_output_of_a_read_only_ability_has_no_retry_hint(): void {
		$ability = new Ability(
			'test/invalid-output-read-only',
			array(
				'label'               => 'Invalid output',
				'description'         => 'Returns output that does not match its schema.',
				'category'            => 'woocommerce',
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => fn() => 'not an object',
				'permission_callback' => '__return_true',
				'meta'                => array( 'annotations' => array( 'readonly' => true ) ),
			)
		);

		$result = $ability->execute();

		$this->assertWPError( $result );
		$this->assertStringNotContainsString( 'may already be saved', $result->get_error_message() );
	}

	/**
	 * @testdox Should keep the message of an error that the execute callback returns.
	 */
	public function test_callback_error_has_no_retry_hint(): void {
		$ability = new Ability(
			'test/callback-error',
			array(
				'label'               => 'Callback error',
				'description'         => 'Returns its own error.',
				'category'            => 'woocommerce',
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => fn() => new \WP_Error( 'ability_invalid_output', 'The callback failed.' ),
				'permission_callback' => '__return_true',
			)
		);

		$result = $ability->execute();

		$this->assertWPError( $result );
		$this->assertSame( 'The callback failed.', $result->get_error_message() );
	}

	/**
	 * @testdox Should keep the message of an error that the wp_pre_execute_ability filter returns.
	 */
	public function test_short_circuit_error_has_no_retry_hint(): void {
		if ( version_compare( get_bloginfo( 'version' ), '7.1-alpha', '<' ) ) {
			$this->markTestSkipped( 'The wp_pre_execute_ability filter exists since WordPress 7.1.' );
		}

		$ability = new Ability(
			'test/short-circuit-error',
			array(
				'label'               => 'Short-circuit error',
				'description'         => 'Never runs its callback.',
				'category'            => 'woocommerce',
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => fn() => array(),
				'permission_callback' => '__return_true',
			)
		);

		$short_circuit = fn() => new \WP_Error( 'ability_invalid_output', 'The filter failed.' );
		add_filter( 'wp_pre_execute_ability', $short_circuit );

		$result = $ability->execute();

		remove_filter( 'wp_pre_execute_ability', $short_circuit );

		$this->assertWPError( $result );
		$this->assertSame( 'The filter failed.', $result->get_error_message() );
	}

	/**
	 * @testdox Should keep the retry hint when the output validation filter runs the same ability again.
	 */
	public function test_nested_run_in_the_output_filter_keeps_the_retry_hint(): void {
		if ( version_compare( get_bloginfo( 'version' ), '7.1-alpha', '<' ) ) {
			$this->markTestSkipped( 'The wp_ability_validate_output filter exists since WordPress 7.1.' );
		}

		$ability = new Ability(
			'test/nested-run',
			array(
				'label'               => 'Nested run',
				'description'         => 'Returns output that does not match its schema.',
				'category'            => 'woocommerce',
				'input_schema'        => array( 'type' => 'integer' ),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => fn() => 'not an object',
				'permission_callback' => '__return_true',
			)
		);

		$nested = function ( $is_valid ) use ( $ability, &$nested ) {
			remove_filter( 'wp_ability_validate_output', $nested );
			$ability->execute( 'not an integer' );
			return $is_valid;
		};
		add_filter( 'wp_ability_validate_output', $nested );

		$result = $ability->execute( 1 );

		remove_filter( 'wp_ability_validate_output', $nested );

		$this->assertWPError( $result );
		$this->assert_retry_hint_once( $result );
	}

	/**
	 * @testdox Should keep every error from the output validation filter when it adds the retry hint.
	 */
	public function test_invalid_output_keeps_every_error(): void {
		if ( version_compare( get_bloginfo( 'version' ), '7.1-alpha', '<' ) ) {
			$this->markTestSkipped( 'The wp_ability_validate_output filter exists since WordPress 7.1.' );
		}

		$filter = function ( $is_valid ) {
			$is_valid->add( 'output_policy', 'The output breaks a policy.', array( 'field' => 'name' ) );
			return $is_valid;
		};
		add_filter( 'wp_ability_validate_output', $filter );

		$ability = new Ability(
			'test/invalid-output-many-errors',
			array(
				'label'               => 'Invalid output',
				'description'         => 'Returns output that does not match its schema.',
				'category'            => 'woocommerce',
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => fn() => 'not an object',
				'permission_callback' => '__return_true',
			)
		);

		$result = $ability->execute();
		remove_filter( 'wp_ability_validate_output', $filter );

		$this->assertWPError( $result );
		$this->assertSame( 'output_policy', $result->get_error_codes()[1] ?? null );
		$this->assert_retry_hint_once( $result );
		$this->assertSame( 'The output breaks a policy.', $result->get_error_message( 'output_policy' ) );
		$data = $result->get_error_data( 'output_policy' );
		$this->assertArrayHasKey( 'field', $data );
		$this->assertSame( 'name', $data['field'] );
	}

	/**
	 * Boolean flag values that the schema accepts, with the boolean each one means.
	 *
	 * @return array<string, array{0: bool|string|int, 1: bool}>
	 */
	public function provider_boolean_flags(): array {
		return array(
			'boolean false' => array( false, false ),
			'string false'  => array( 'false', false ),
			'string zero'   => array( '0', false ),
			'integer zero'  => array( 0, false ),
			'boolean true'  => array( true, true ),
			'string true'   => array( 'true', true ),
			'string one'    => array( '1', true ),
			'integer one'   => array( 1, true ),
		);
	}

	/**
	 * @testdox Should pass a boolean field to the callbacks as a boolean.
	 * @dataProvider provider_boolean_flags
	 *
	 * @param bool|string|int $flag     Flag value the client sends.
	 * @param bool            $expected Boolean the callbacks get.
	 */
	public function test_converts_a_boolean_field( $flag, bool $expected ): void {
		$result = $this->get_ability()->execute(
			array(
				'flag'  => $flag,
				'items' => array( array( 'delete' => $flag ) ),
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( $expected, $this->received['permission']['flag'], 'The permission callback gets the converted flag.' );
		$this->assertSame( $expected, $this->received['execute']['flag'], 'The execute callback gets the converted flag.' );
		$this->assertSame( $expected, $this->received['execute']['items'][0]['delete'], 'A nested flag is converted too.' );
	}

	/**
	 * @testdox Should pass an integer field that arrives as a numeric string as an integer.
	 */
	public function test_converts_a_numeric_string_integer(): void {
		$result = $this->get_ability()->execute( array( 'count' => '5' ) );

		$this->assertNotWPError( $result );
		$this->assertSame( 5, $this->received['execute']['count'] );
	}

	/**
	 * @testdox Should refuse invalid input with the validation error and not run the callbacks.
	 */
	public function test_refuses_invalid_input(): void {
		$result = $this->get_ability()->execute( array( 'flag' => 'maybe' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
		$this->assertArrayNotHasKey( 'execute', $this->received );
	}

	/**
	 * @testdox Should refuse input that validates but cannot be converted, and not run the callbacks.
	 */
	public function test_refuses_input_that_cannot_be_converted(): void {
		$result = $this->get_ability()->execute( array( 'ids' => array( '1', '01' ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
		$this->assertArrayNotHasKey( 'permission', $this->received );
		$this->assertArrayNotHasKey( 'execute', $this->received );
	}

	/**
	 * @testdox Should pass raw input unchanged when the caller skips preparation.
	 */
	public function test_direct_permission_check_does_not_prepare_raw_input(): void {
		$sut = $this->get_ability();

		$this->assertTrue(
			$sut->check_permissions(
				array(
					'flag'  => 'false',
					'count' => '5',
				)
			)
		);
		$this->assertSame( 'false', $this->received['permission']['flag'], 'A direct permission check does not convert the boolean string.' );
		$this->assertSame( '5', $this->received['permission']['count'], 'A direct permission check does not convert the numeric string.' );
		$this->assertArrayNotHasKey( 'execute', $this->received );
	}

	/**
	 * @testdox Should prepare raw input before calling check_permissions() directly.
	 */
	public function test_prepares_input_for_a_direct_permission_check(): void {
		$sut      = $this->get_ability();
		$prepared = $sut->prepare_input(
			array(
				'flag'  => 'false',
				'count' => '5',
			)
		);

		$this->assertNotWPError( $prepared );
		$this->assertTrue( $sut->check_permissions( $prepared ) );
		$this->assertSame( false, $this->received['permission']['flag'] );
		$this->assertSame( 5, $this->received['permission']['count'] );
	}

	/**
	 * @testdox Should refuse invalid input during preparation and not run the permission callback.
	 */
	public function test_prepare_input_refuses_invalid_input(): void {
		$result = $this->get_ability()->prepare_input( array( 'flag' => 'maybe' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
		$this->assertArrayNotHasKey( 'permission', $this->received );
	}

	/**
	 * @testdox Should return input that is already converted unchanged.
	 */
	public function test_converting_twice_changes_nothing(): void {
		$ability = $this->get_ability();
		$once    = $ability->normalize_input(
			array(
				'flag'  => 'false',
				'count' => '5',
			)
		);

		$this->assertSame( $once, $ability->normalize_input( $once ) );
	}

	/**
	 * Entry points that prepare input before checking permissions.
	 *
	 * @return array<string, array{0: bool}>
	 */
	public function provider_preparation_entry_points(): array {
		return array(
			'execute'           => array( false ),
			'direct permission' => array( true ),
		);
	}

	/**
	 * @testdox Should normalize once and give the permission and execution callbacks the same input.
	 * @dataProvider provider_preparation_entry_points
	 *
	 * @param bool $direct_permission Whether to prepare input for a direct permission check.
	 */
	public function test_prepares_input_once_with_a_non_idempotent_filter( bool $direct_permission ): void {
		if ( version_compare( get_bloginfo( 'version' ), '7.1-alpha', '<' ) ) {
			$this->markTestSkipped( 'The normalization filter requires WordPress 7.1.' );
		}

		$sut   = $this->get_ability();
		$calls = 0;
		add_filter(
			'wp_ability_normalize_input',
			static function ( $input, $name ) use ( &$calls ) {
				if ( 'test/convert-input' === $name ) {
					++$calls;
					$input['text'] = htmlspecialchars( $input['text'], ENT_QUOTES, 'UTF-8', true );
				}
				return $input;
			},
			10,
			2
		);

		$input = array( 'text' => '&' );
		if ( $direct_permission ) {
			$prepared = $sut->prepare_input( $input );
			$this->assertNotWPError( $prepared );
			$this->assertTrue( $sut->check_permissions( $prepared ) );
		} else {
			$this->assertNotWPError( $sut->execute( $input ) );
			$this->assertSame( '&amp;', $this->received['execute']['text'] );
		}

		$this->assertSame( 1, $calls );
		$this->assertSame( '&amp;', $this->received['permission']['text'] );
	}

	/**
	 * @testdox Should preserve normalization errors without calling either callback.
	 * @dataProvider provider_preparation_entry_points
	 *
	 * @param bool $direct_permission Whether to prepare input for a direct permission check.
	 */
	public function test_preserves_normalization_errors( bool $direct_permission ): void {
		if ( version_compare( get_bloginfo( 'version' ), '7.1-alpha', '<' ) ) {
			$this->markTestSkipped( 'The normalization filter requires WordPress 7.1.' );
		}

		$sut   = $this->get_ability();
		$error = new \WP_Error( 'test_normalization_error', 'Cannot normalize this input.' );
		add_filter(
			'wp_ability_normalize_input',
			static function ( $input, $name ) use ( $error ) {
				return 'test/convert-input' === $name ? $error : $input;
			},
			10,
			2
		);

		$result = $direct_permission ? $sut->prepare_input( array() ) : $sut->execute( array() );

		$this->assertSame( $error, $result );
		$this->assertArrayNotHasKey( 'permission', $this->received );
		$this->assertArrayNotHasKey( 'execute', $this->received );
	}

	/**
	 * @testdox Should sanitize once when preparing input, including on older WordPress versions.
	 * @dataProvider provider_preparation_entry_points
	 *
	 * @param bool $direct_permission Whether to prepare input for a direct permission check.
	 */
	public function test_sanitizes_input_once( bool $direct_permission ): void {
		$sut = new class(
			'test/count-sanitization',
			array(
				'label'               => 'Count sanitization',
				'description'         => 'Count calls to the real sanitizer.',
				'category'            => 'woocommerce',
				'input_schema'        => array( 'type' => 'boolean' ),
				'permission_callback' => '__return_true',
				'execute_callback'    => static function ( $input ) {
					return $input;
				},
			)
		) extends Ability {
			/**
			 * Number of sanitizer calls.
			 *
			 * @var int
			 */
			public $sanitizations = 0;

			/**
			 * Count calls while retaining the actual schema sanitization.
			 *
			 * @param mixed $input Validated input.
			 * @return mixed|\WP_Error
			 */
			protected function sanitize_input( $input ) {
				++$this->sanitizations;
				return parent::sanitize_input( $input );
			}
		};

		if ( $direct_permission ) {
			$prepared = $sut->prepare_input( 'false' );
			$this->assertFalse( $prepared );
			$this->assertTrue( $sut->check_permissions( $prepared ) );
		} else {
			$this->assertFalse( $sut->execute( 'false' ) );
		}

		$this->assertSame( 1, $sut->sanitizations );
	}

	/**
	 * @testdox Should reject conversion failures in prepare_input before any callback runs.
	 */
	public function test_prepare_input_refuses_conversion_errors(): void {
		$sut    = $this->get_ability();
		$result = $sut->prepare_input( array( 'ids' => array( '1', '01' ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
		$this->assertArrayNotHasKey( 'permission', $this->received );
		$this->assertArrayNotHasKey( 'execute', $this->received );
	}

	/**
	 * @testdox Should refuse a number that converts to a value the schema does not allow, and not run the callbacks.
	 */
	public function test_refuses_a_number_that_converts_below_the_minimum(): void {
		if ( method_exists( \WP_Ability::class, 'prepare_input' ) ) {
			$this->markTestSkipped( 'WordPress prepares the input itself since 7.2.' );
		}

		$sut = $this->get_ability();

		// PHP 8.5 reports casting an out-of-range float to an integer as a deprecation.
		set_error_handler( '__return_true', E_DEPRECATED ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		try {
			$results = array( $sut->execute( array( 'page' => 2.0 ** 64 ) ), $sut->prepare_input( array( 'page' => 2.0 ** 64 ) ) );
		} finally {
			restore_error_handler();
		}

		foreach ( $results as $result ) {
			$this->assertWPError( $result );
			$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
		}
		$this->assertArrayNotHasKey( 'permission', $this->received );
		$this->assertArrayNotHasKey( 'execute', $this->received );
	}

	/**
	 * @testdox Should validate input that needs no conversion once.
	 */
	public function test_validates_input_that_needs_no_conversion_once(): void {
		if ( ! method_exists( \WP_Ability::class, 'prepare_input' ) ) {
			$this->markTestSkipped( 'WP_Ability::prepare_input() exists since WordPress 7.2.' );
		}

		$calls    = 0;
		$callback = function ( $is_valid ) use ( &$calls ) {
			++$calls;
			return $is_valid;
		};
		add_filter( 'wp_ability_validate_input', $callback );

		$result = $this->get_ability()->prepare_input( array( 'count' => 5 ) );

		remove_filter( 'wp_ability_validate_input', $callback );
		$this->assertSame( array( 'count' => 5 ), $result );
		$this->assertSame( 1, $calls );
	}
}
