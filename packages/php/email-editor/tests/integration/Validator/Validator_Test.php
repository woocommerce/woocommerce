<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Validator;

/**
 * Integration test for the Validator, which accepts or rejects saved email data. It uses the
 * WordPress REST schema functions, so it needs WordPress.
 */
class Validator_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var Validator
	 */
	private Validator $sut;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new Validator();
	}

	/**
	 * Test it accepts values of the exact type and returns them.
	 *
	 * @dataProvider valid_value_provider
	 * @param Schema $schema   Schema.
	 * @param mixed  $value    Value.
	 * @param mixed  $expected Value the validator returns.
	 */
	public function testItAcceptsValidValues( Schema $schema, $value, $expected ): void {
		$this->assertSame( $expected, $this->sut->validate( $schema, $value ) );
	}

	/**
	 * Valid values.
	 *
	 * @return array<string, array{Schema, mixed, mixed}>
	 */
	public function valid_value_provider(): array {
		return array(
			'integer'                  => array( Builder::integer(), 5, 5 ),
			'whole float as number'    => array( Builder::number(), 2.0, 2.0 ),
			'boolean'                  => array( Builder::boolean(), false, false ),
			'null for nullable'        => array( Builder::string()->nullable(), null, null ),
			'object'                   => array( Builder::object( array( 'name' => Builder::string() ) ), array( 'name' => 'Ada' ), array( 'name' => 'Ada' ) ),
			'stdClass object'          => array( Builder::object( array( 'name' => Builder::string() ) ), (object) array( 'name' => 'Ada' ), array( 'name' => 'Ada' ) ),
			'first matching anyOf'     => array( Builder::any_of( array( Builder::integer(), Builder::string() ) ), 'text', 'text' ),
			'single matching oneOf'    => array( Builder::one_of( array( Builder::integer(), Builder::string() ) ), 7, 7 ),
			'additional property type' => array( Builder::object()->additionalProperties( Builder::integer() ), array( 'any' => 1 ), array( 'any' => 1 ) ),
		);
	}

	/**
	 * Test it rejects values WordPress would otherwise coerce or accept.
	 *
	 * @dataProvider invalid_value_provider
	 * @param Schema $schema Schema.
	 * @param mixed  $value  Value.
	 * @param string $param  Parameter path the error message names.
	 */
	public function testItRejectsInvalidValues( Schema $schema, $value, string $param ): void {
		try {
			$this->sut->validate( $schema, $value );
			$this->fail( 'Expected a validation error.' );
		} catch ( Validation_Exception $e ) {
			$this->assertStringContainsString( $param . ' ', $e->get_wp_error()->get_error_message(), 'The error names the parameter.' );
		}
	}

	/**
	 * Invalid values.
	 *
	 * @return array<string, array{Schema, mixed, string}>
	 */
	public function invalid_value_provider(): array {
		$nested = Builder::object( array( 'a' => Builder::array( Builder::integer() ) ) );
		return array(
			'numeric string for integer'  => array( Builder::integer(), '5', 'value' ),
			'float for integer'           => array( Builder::integer(), 5.5, 'value' ),
			'numeric string for number'   => array( Builder::number(), '1.5', 'value' ),
			'string for boolean'          => array( Builder::boolean(), 'true', 'value' ),
			'null for non-nullable'       => array( Builder::string(), null, 'value' ),
			'object with a numeric key'   => array(
				Builder::object(),
				array(
					'a' => 1,
					2,
				),
				'value',
			),
			'nested value names its path' => array( $nested, array( 'a' => array( 1, 'x' ) ), 'value[a][1]' ),
			'additional property type'    => array( Builder::object()->additionalProperties( Builder::integer() ), array( 'any' => 'x' ), 'value[any]' ),
			'no matching anyOf'           => array( Builder::any_of( array( Builder::integer(), Builder::boolean() ) ), 'text', 'value' ),
			'two matching oneOf'          => array( Builder::one_of( array( Builder::number(), Builder::integer() ) ), 7, 'value' ),
		);
	}

	/**
	 * Test the builder produces the REST schema array.
	 */
	public function testBuilderProducesSchemaArray(): void {
		$schema = Builder::object( array( 'subject' => Builder::string()->nullable() ) )->required()->to_array();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertSame( array( 'string', 'null' ), $schema['properties']['subject']['type'] );
		$this->assertTrue( $schema['required'] );
	}
}
