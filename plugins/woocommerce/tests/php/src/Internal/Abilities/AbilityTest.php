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
	 * @testdox Should pass converted input to the permission callback when check_permissions() is called directly.
	 */
	public function test_check_permissions_converts_the_input(): void {
		$result = $this->get_ability()->check_permissions(
			array(
				'flag'  => 'false',
				'count' => '5',
			)
		);

		$this->assertTrue( $result );
		$this->assertSame( false, $this->received['permission']['flag'] );
		$this->assertSame( 5, $this->received['permission']['count'] );
	}

	/**
	 * @testdox Should refuse invalid input in check_permissions() and not run the permission callback.
	 */
	public function test_check_permissions_refuses_invalid_input(): void {
		$result = $this->get_ability()->check_permissions( array( 'flag' => 'maybe' ) );

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
}
