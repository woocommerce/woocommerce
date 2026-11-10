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
}
