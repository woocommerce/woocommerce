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
	 * @testdox Every error code has its own message that names the field.
	 */
	public function test_every_error_code_has_a_message(): void {
		$codes   = ( new ReflectionClass( ValidationErrorCode::class ) )->getConstants();
		$generic = self::message_for( 'acme_unknown', 'name' );

		$this->assertNotEmpty( $codes );
		foreach ( $codes as $code ) {
			$this->assertArrayHasKey( $code, self::SAMPLE_PARAMS, sprintf( 'Add sample params and a message for "%s".', $code ) );

			$message = self::message_for( $code, 'name', self::SAMPLE_PARAMS[ $code ] );

			$this->assertStringContainsString( 'name', $message, $code );
			if ( ValidationErrorCode::INVALID_VALUE !== $code ) {
				$this->assertNotSame( $generic, $message, sprintf( '"%s" falls back to the generic message.', $code ) );
			}
		}
	}

	/**
	 * @testdox The messages interpolate the field and params.
	 */
	public function test_the_messages_interpolate_the_params(): void {
		$this->assertSame( 'name is required.', self::message_for( ValidationErrorCode::REQUIRED, 'name' ) );
		$this->assertSame( 'interval is not of type integer.', self::message_for( ValidationErrorCode::INVALID_TYPE, 'interval', array( 'type' => 'integer' ) ) );
		$this->assertSame( 'period is not one of day, week.', self::message_for( ValidationErrorCode::NOT_IN_ENUM, 'period', self::SAMPLE_PARAMS[ ValidationErrorCode::NOT_IN_ENUM ] ) );
		$this->assertSame( 'currency is not valid.', self::message_for( ValidationErrorCode::INVALID_VALUE, 'currency' ) );
		$this->assertSame( 'status "seasonal" is not registered.', self::message_for( ValidationErrorCode::NOT_REGISTERED, 'status', array( 'value' => 'seasonal' ) ) );
	}

	/**
	 * @testdox out_of_bounds picks its message by the bounds present.
	 */
	public function test_out_of_bounds_picks_its_message_by_the_bounds_present(): void {
		$this->assertSame( 'limit must be greater than or equal to 1.', self::message_for( ValidationErrorCode::OUT_OF_BOUNDS, 'limit', array( 'min' => 1 ) ) );
		$this->assertSame( 'min_cycles must be less than or equal to 2.', self::message_for( ValidationErrorCode::OUT_OF_BOUNDS, 'min_cycles', array( 'max' => 2 ) ) );
		$this->assertSame(
			'value must be between 0 and 100.',
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
		$this->assertSame( 'name is not valid.', self::message_for( 'acme_unknown', 'name' ) );
		$this->assertSame( 'limit is not valid.', self::message_for( ValidationErrorCode::OUT_OF_BOUNDS, 'limit' ) );
		$this->assertSame( 'name is not valid.', self::message_for( ValidationErrorCode::INVALID_TYPE, 'name', array( 'type' => 3 ) ) );
	}

	/**
	 * @testdox from_core keeps the English message, chains the Core error and carries field and params data.
	 */
	public function test_from_core_keeps_the_english_message_and_chains_the_core_error(): void {
		$core = new InvalidInputException( ValidationErrorCode::NOT_REGISTERED, 'status', 'Plan: status "<b>" is not registered.', array( 'value' => '<b>' ) );

		$e = ValidationException::from_core( $core );

		$this->assertInstanceOf( InvalidArgumentException::class, $e );
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
				return 'woocommerce' === $domain && '%s is required.' === $text ? '%s est obligatoire.' : $translation;
			},
			10,
			3
		);

		$this->assertSame( 'name est obligatoire.', self::message_for( ValidationErrorCode::REQUIRED, 'name' ) );
	}
}
