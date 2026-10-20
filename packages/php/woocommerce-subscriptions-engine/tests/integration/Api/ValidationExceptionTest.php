<?php
/**
 * Integration tests for the facade validation exception and its message map.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api;

use EngineIntegrationTestCase;
use InvalidArgumentException;
use ReflectionClass;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ValidationException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\InvalidInputException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\ValidationException
 */
class ValidationExceptionTest extends EngineIntegrationTestCase {

	/**
	 * Params a message for each code needs.
	 */
	private const SAMPLE_PARAMS = array(
		ValidationErrorCode::REQUIRED       => array(),
		ValidationErrorCode::INVALID_TYPE   => array( 'type' => 'string' ),
		ValidationErrorCode::OUT_OF_BOUNDS  => array( 'min' => 1 ),
		ValidationErrorCode::NOT_IN_ENUM    => array( 'allowed' => array( 'day', 'week' ) ),
		ValidationErrorCode::INVALID_VALUE  => array(),
		ValidationErrorCode::NOT_REGISTERED => array( 'value' => 'seasonal' ),
	);

	/**
	 * Translated message for a Core error.
	 *
	 * @param string               $code   Error code.
	 * @param string               $field  Field.
	 * @param array<string, mixed> $params Params.
	 */
	private static function message_for( string $code, string $field, array $params = array() ): string {
		$e = ValidationException::from_core( new InvalidInputException( $code, $field, 'English.', $params ) );

		return $e->get_errors()->get_error_message( $code );
	}

	/**
	 * @testdox Every error code has its own message, and no message names the field.
	 */
	public function test_every_error_code_has_a_message(): void {
		$codes   = ( new ReflectionClass( ValidationErrorCode::class ) )->getConstants();
		$generic = self::message_for( 'acme_unknown', 'name' );

		$this->assertNotEmpty( $codes );
		foreach ( $codes as $code ) {
			$this->assertIsString( $code );
			$this->assertArrayHasKey( $code, self::SAMPLE_PARAMS, sprintf( 'Add sample params and a message for "%s".', $code ) );

			$message = self::message_for( $code, 'acme_field', self::SAMPLE_PARAMS[ $code ] );

			$this->assertStringNotContainsString( 'acme_field', $message, $code );
			if ( ValidationErrorCode::INVALID_VALUE !== $code ) {
				$this->assertNotSame( $generic, $message, sprintf( '"%s" falls back to the generic message.', $code ) );
			}
		}
	}

	/**
	 * @testdox The messages interpolate the params.
	 */
	public function test_the_messages_interpolate_the_params(): void {
		$this->assertSame( 'This field is required.', self::message_for( ValidationErrorCode::REQUIRED, 'name' ) );
		$this->assertSame( 'Must be of type integer.', self::message_for( ValidationErrorCode::INVALID_TYPE, 'interval', array( 'type' => 'integer' ) ) );
		$this->assertSame( 'This value is not valid.', self::message_for( ValidationErrorCode::INVALID_VALUE, 'currency' ) );
		$this->assertSame( '"seasonal" is not registered.', self::message_for( ValidationErrorCode::NOT_REGISTERED, 'status', array( 'value' => 'seasonal' ) ) );
	}

	/**
	 * @testdox not_in_enum lists the allowed values with translated separators.
	 */
	public function test_not_in_enum_lists_the_allowed_values_with_translated_separators(): void {
		$allowed = array( 'allowed' => array( 'day', 'week', 'month', 'year' ) );

		$this->assertSame( wp_sprintf( 'Must be one of %l.', $allowed['allowed'] ), self::message_for( ValidationErrorCode::NOT_IN_ENUM, 'period', $allowed ) );
		$this->assertSame( 'Must be one of day, week, month, and year.', self::message_for( ValidationErrorCode::NOT_IN_ENUM, 'period', $allowed ) );

		add_filter(
			'gettext',
			static function ( $translation, $text, $domain ) {
				if ( 'default' !== $domain ) {
					return $translation;
				}
				if ( '%1$s, %2$s' === $text ) {
					return '%1$s; %2$s';
				}

				return '%1$s, and %2$s' === $text ? '%1$s or %2$s' : $translation;
			},
			10,
			3
		);

		$this->assertSame( 'Must be one of day; week; month or year.', self::message_for( ValidationErrorCode::NOT_IN_ENUM, 'period', $allowed ) );
	}

	/**
	 * @testdox out_of_bounds picks its message by the bounds present.
	 */
	public function test_out_of_bounds_picks_its_message_by_the_bounds_present(): void {
		$this->assertSame( 'Must be greater than or equal to 1.', self::message_for( ValidationErrorCode::OUT_OF_BOUNDS, 'limit', array( 'min' => 1 ) ) );
		$this->assertSame( 'Must be less than or equal to 2.', self::message_for( ValidationErrorCode::OUT_OF_BOUNDS, 'min_cycles', array( 'max' => 2 ) ) );
		$this->assertSame(
			'Must be between 0 and 100.',
			self::message_for(
				ValidationErrorCode::OUT_OF_BOUNDS,
				'value',
				array(
					'min' => 0,
					'max' => 100,
				)
			)
		);
	}

	/**
	 * @testdox An unknown code, or params a message cannot use, get the generic message.
	 */
	public function test_an_unknown_code_gets_the_generic_message(): void {
		$this->assertSame( 'This value is not valid.', self::message_for( 'acme_unknown', 'name' ) );
		$this->assertSame( 'This value is not valid.', self::message_for( ValidationErrorCode::OUT_OF_BOUNDS, 'limit' ) );
		$this->assertSame( 'This value is not valid.', self::message_for( ValidationErrorCode::INVALID_TYPE, 'name', array( 'type' => 3 ) ) );
		$this->assertSame( 'This value is not valid.', self::message_for( ValidationErrorCode::NOT_IN_ENUM, 'period', array( 'allowed' => 'day' ) ) );
	}

	/**
	 * @testdox from_core keeps the English message, chains the Core error and carries field and params data.
	 *
	 * @throws ValidationException Thrown and caught as an InvalidArgumentException.
	 */
	public function test_from_core_keeps_the_english_message_and_chains_the_core_error(): void {
		$core = new InvalidInputException( ValidationErrorCode::NOT_REGISTERED, 'status', 'Plan: status "<b>" is not registered.', array( 'value' => '<b>' ) );

		try {
			throw ValidationException::from_core( $core );
		} catch ( InvalidArgumentException $caught ) {
			$e = $caught;
		}

		$this->assertInstanceOf( ValidationException::class, $e, 'Callers catching InvalidArgumentException keep working.' );
		$this->assertSame( esc_html( $core->getMessage() ), $e->getMessage() );
		$this->assertSame( $core, $e->getPrevious() );
		$this->assertSame( array( ValidationErrorCode::NOT_REGISTERED ), $e->get_errors()->get_error_codes() );
		$this->assertSame(
			array(
				'field'  => 'status',
				'params' => array( 'value' => '<b>' ),
			),
			$e->get_errors()->get_error_data( ValidationErrorCode::NOT_REGISTERED )
		);
	}

	/**
	 * @testdox The message is translated in the woocommerce text domain.
	 */
	public function test_the_message_is_translated(): void {
		add_filter(
			'gettext',
			static function ( $translation, $text, $domain ) {
				return 'woocommerce' === $domain && 'This field is required.' === $text ? 'Ce champ est obligatoire.' : $translation;
			},
			10,
			3
		);

		$this->assertSame( 'Ce champ est obligatoire.', self::message_for( ValidationErrorCode::REQUIRED, 'name' ) );
	}
}
