<?php
/**
 * Unit tests for the Core argument checks. Running under the WordPress-free unit
 * bootstrap proves they stay in the Core zone.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Support;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ArgumentValidator;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\InvalidInputException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;
use Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\AssertsInvalidInput;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ArgumentValidator
 */
class ArgumentValidatorTest extends TestCase {

	use AssertsInvalidInput;

	/**
	 * @testdox A valid value is returned normalized.
	 * @dataProvider provide_valid_values
	 *
	 * @param string            $method   Validator method.
	 * @param array<int, mixed> $args     Validator arguments.
	 * @param mixed             $expected Normalized value.
	 */
	public function test_a_valid_value_is_returned_normalized( string $method, array $args, $expected ): void {
		$this->assertSame( $expected, ArgumentValidator::$method( ...$args ) );
	}

	/**
	 * @return array<string, array{0: string, 1: array<int, mixed>, 2: mixed}>
	 */
	public function provide_valid_values(): array {
		return array(
			'currency'              => array( 'validate_currency', array( 'EUR' ), 'EUR' ),
			'null currency'         => array( 'validate_currency', array( null ), null ),
			'string'                => array( 'validate_string', array( 'status', 'active' ), 'active' ),
			'non-empty string'      => array( 'validate_non_empty_string', array( 'extension_slug', 'acme' ), 'acme' ),
			'nullable string'       => array( 'validate_nullable_string', array( 'title', null ), null ),
			'nullable id as digits' => array( 'validate_nullable_id', array( 'order_id', '42' ), 42 ),
			'nullable id null'      => array( 'validate_nullable_id', array( 'order_id', null ), null ),
			'nullable date object'  => array(
				'validate_nullable_date',
				array( 'start_gmt', new DateTimeImmutable( '2026-01-01 03:00:00', new DateTimeZone( 'Europe/Berlin' ) ) ),
				'2026-01-01 02:00:00',
			),
			'nullable date string'  => array( 'validate_nullable_date', array( 'start_gmt', '2026-02-28 00:00:00' ), '2026-02-28 00:00:00' ),
			'money'                 => array( 'validate_money', array( 'tax_total', '1.5' ), '1.50000000' ),
			'money null is zero'    => array( 'validate_money', array( 'tax_total', null ), '0.00000000' ),
			'list of arrays'        => array( 'validate_list_of_arrays', array( 'items', array( array( 'name' => 'a' ) ) ), array( array( 'name' => 'a' ) ) ),
			'positive int'          => array( 'validate_positive_int', array( 'claim_seconds', 900 ), 900 ),
			'positive int digits'   => array( 'validate_positive_int', array( 'claim_seconds', '60' ), 60 ),
			'non-negative int zero' => array( 'validate_non_negative_int', array( 'offset', 0 ), 0 ),
			'non-negative digits'   => array( 'validate_non_negative_int', array( 'offset', '7' ), 7 ),
			'id list'               => array( 'validate_id_list', array( 'ids', array( 3, '4' ) ), array( 3, 4 ) ),
			'empty id list'         => array( 'validate_id_list', array( 'ids', array() ), array() ),
			'string as list'        => array( 'validate_string_list', array( 'status', 'active' ), array( 'active' ) ),
			'string list'           => array( 'validate_string_list', array( 'status', array( 'active', 'archived' ) ), array( 'active', 'archived' ) ),
			'nullable array'        => array( 'validate_nullable_array', array( 'billing_policy', array( 'period' => 'month' ) ), array( 'period' => 'month' ) ),
			'nullable array null'   => array( 'validate_nullable_array', array( 'billing_policy', null ), null ),
			'addresses'             => array(
				'validate_contract_addresses',
				array( array( 'billing' => array( 'first_name' => 'Ada' ) ) ),
				array( 'billing' => array( 'first_name' => 'Ada' ) ),
			),
		);
	}

	/**
	 * @testdox An invalid value is refused with its code, field and params.
	 * @dataProvider provide_invalid_values
	 *
	 * @param string               $method Validator method.
	 * @param array<int, mixed>    $args   Validator arguments.
	 * @param string               $code   Expected error code.
	 * @param string               $field  Expected field.
	 * @param array<string, mixed> $params Expected params.
	 */
	public function test_an_invalid_value_is_refused_with_its_code( string $method, array $args, string $code, string $field, array $params ): void {
		$this->assert_invalid_input(
			static function () use ( $method, $args ): void {
				ArgumentValidator::$method( ...$args );
			},
			$code,
			$field,
			$params
		);
	}

	/**
	 * @testdox The English message quotes the field and says what the value must be.
	 */
	public function test_the_english_message_quotes_the_field(): void {
		$e = $this->assert_invalid_input(
			static function (): void {
				ArgumentValidator::validate_positive_int( 'claim_seconds', 0 );
			},
			ValidationErrorCode::OUT_OF_BOUNDS,
			'claim_seconds',
			array( 'min' => 1 )
		);
		$this->assertSame( '"claim_seconds" must be a positive integer.', $e->getMessage() );

		$e = $this->assert_invalid_input(
			static function (): void {
				ArgumentValidator::validate_id_list( 'ids', array( 3, 0 ) );
			},
			ValidationErrorCode::OUT_OF_BOUNDS,
			'ids.1',
			array( 'min' => 1 )
		);
		$this->assertSame( '"ids.1" must be a positive integer.', $e->getMessage() );
	}

	/**
	 * @return array<string, array{0: string, 1: array<int, mixed>, 2: string, 3: string, 4: array<string, mixed>}>
	 */
	public function provide_invalid_values(): array {
		$type_string  = array( 'type' => 'string' );
		$type_integer = array( 'type' => 'integer' );
		$type_array   = array( 'type' => 'array' );
		$type_object  = array( 'type' => 'object' );
		$min_one      = array( 'min' => 1 );

		return array(
			'currency'              => array( 'validate_currency', array( 'eur' ), ValidationErrorCode::INVALID_VALUE, 'currency', array() ),
			'currency newline'      => array( 'validate_currency', array( "EUR\n" ), ValidationErrorCode::INVALID_VALUE, 'currency', array() ),
			'string'                => array( 'validate_string', array( 'status', 5 ), ValidationErrorCode::INVALID_TYPE, 'status', $type_string ),
			'nullable string'       => array( 'validate_nullable_string', array( 'title', 5 ), ValidationErrorCode::INVALID_TYPE, 'title', $type_string ),
			'non-empty not string'  => array( 'validate_non_empty_string', array( 'extension_slug', 5 ), ValidationErrorCode::INVALID_TYPE, 'extension_slug', $type_string ),
			'non-empty null'        => array( 'validate_non_empty_string', array( 'extension_slug', null ), ValidationErrorCode::REQUIRED, 'extension_slug', array() ),
			'non-empty empty'       => array( 'validate_non_empty_string', array( 'extension_slug', '' ), ValidationErrorCode::REQUIRED, 'extension_slug', array() ),
			'positive int type'     => array( 'validate_positive_int', array( 'claim_seconds', null ), ValidationErrorCode::INVALID_TYPE, 'claim_seconds', $type_integer ),
			'positive int zero'     => array( 'validate_positive_int', array( 'claim_seconds', 0 ), ValidationErrorCode::OUT_OF_BOUNDS, 'claim_seconds', $min_one ),
			'nullable id type'      => array( 'validate_nullable_id', array( 'order_id', "5\n" ), ValidationErrorCode::INVALID_TYPE, 'order_id', $type_integer ),
			'nullable id zero'      => array( 'validate_nullable_id', array( 'order_id', 0 ), ValidationErrorCode::OUT_OF_BOUNDS, 'order_id', $min_one ),
			'non-negative type'     => array( 'validate_non_negative_int', array( 'offset', 1.5 ), ValidationErrorCode::INVALID_TYPE, 'offset', $type_integer ),
			'non-negative negative' => array( 'validate_non_negative_int', array( 'offset', -1 ), ValidationErrorCode::OUT_OF_BOUNDS, 'offset', array( 'min' => 0 ) ),
			'id list map'           => array( 'validate_id_list', array( 'ids', array( 'a' => 3 ) ), ValidationErrorCode::INVALID_TYPE, 'ids', $type_array ),
			'id list entry zero'    => array( 'validate_id_list', array( 'ids', array( 3, 0 ) ), ValidationErrorCode::OUT_OF_BOUNDS, 'ids.1', $min_one ),
			'id list entry null'    => array( 'validate_id_list', array( 'ids', array( null ) ), ValidationErrorCode::INVALID_TYPE, 'ids.0', $type_integer ),
			'string list not list'  => array( 'validate_string_list', array( 'status', 5 ), ValidationErrorCode::INVALID_TYPE, 'status', $type_array ),
			'string list empty'     => array( 'validate_string_list', array( 'status', '' ), ValidationErrorCode::REQUIRED, 'status', array() ),
			'string list entry'     => array( 'validate_string_list', array( 'status', array( 'active', '' ) ), ValidationErrorCode::REQUIRED, 'status.1', array() ),
			'string list entry int' => array( 'validate_string_list', array( 'status', array( 5 ) ), ValidationErrorCode::INVALID_TYPE, 'status.0', $type_string ),
			'nullable array'        => array( 'validate_nullable_array', array( 'billing_policy', 'monthly' ), ValidationErrorCode::INVALID_TYPE, 'billing_policy', $type_object ),
			'list of arrays map'    => array( 'validate_list_of_arrays', array( 'items', array( 'a' => array() ) ), ValidationErrorCode::INVALID_TYPE, 'items', $type_array ),
			'list of arrays entry'  => array( 'validate_list_of_arrays', array( 'items', array( 'a' ) ), ValidationErrorCode::INVALID_TYPE, 'items.0', $type_object ),
			'money'                 => array( 'validate_money', array( 'tax_total', 'ten' ), ValidationErrorCode::INVALID_TYPE, 'tax_total', array( 'type' => 'number' ) ),
			'nullable date'         => array( 'validate_nullable_date', array( 'start_gmt', '2026-02-30 00:00:00' ), ValidationErrorCode::INVALID_VALUE, 'start_gmt', array() ),
			'addresses not array'   => array( 'validate_contract_addresses', array( 'home' ), ValidationErrorCode::INVALID_TYPE, 'addresses', $type_object ),
			'address unknown type'  => array(
				'validate_contract_addresses',
				array( array( 'home' => array( 'first_name' => 'Ada' ) ) ),
				ValidationErrorCode::NOT_IN_ENUM,
				'addresses.home',
				array( 'allowed' => array( 'billing', 'shipping' ) ),
			),
			'address not array'     => array( 'validate_contract_addresses', array( array( 'billing' => 'Ada' ) ), ValidationErrorCode::INVALID_TYPE, 'addresses.billing', $type_object ),
		);
	}

	/**
	 * @testdox A list check reports every failed item.
	 */
	public function test_a_list_check_reports_every_failed_item(): void {
		$this->assert_invalid_inputs(
			static function (): void {
				ArgumentValidator::validate_id_list( 'ids', array( 1, 0, 'x' ) );
			},
			array(
				array( ValidationErrorCode::OUT_OF_BOUNDS, 'ids.1', array( 'min' => 1 ) ),
				array( ValidationErrorCode::INVALID_TYPE, 'ids.2', array( 'type' => 'integer' ) ),
			)
		);
		$this->assert_invalid_inputs(
			static function (): void {
				ArgumentValidator::validate_string_list( 'status', array( '', 'active', 5 ) );
			},
			array(
				array( ValidationErrorCode::REQUIRED, 'status.0' ),
				array( ValidationErrorCode::INVALID_TYPE, 'status.2' ),
			)
		);
		$this->assert_invalid_inputs(
			static function (): void {
				ArgumentValidator::validate_list_of_arrays( 'items', array( 'a', array(), 'b' ) );
			},
			array(
				array( ValidationErrorCode::INVALID_TYPE, 'items.0' ),
				array( ValidationErrorCode::INVALID_TYPE, 'items.2' ),
			)
		);
		$this->assert_invalid_inputs(
			static function (): void {
				ArgumentValidator::validate_contract_addresses(
					array(
						'home'    => array(),
						'billing' => 'x',
					)
				);
			},
			array(
				array( ValidationErrorCode::NOT_IN_ENUM, 'addresses.home', array( 'allowed' => array( 'billing', 'shipping' ) ) ),
				array( ValidationErrorCode::INVALID_TYPE, 'addresses.billing', array( 'type' => 'object' ) ),
			)
		);
	}

	/**
	 * @testdox A value that is not a list reports only the key itself.
	 */
	public function test_a_non_list_reports_only_the_key(): void {
		$this->assert_invalid_inputs(
			static function (): void {
				ArgumentValidator::validate_id_list( 'ids', array( 'a' => 0 ) );
			},
			array( array( ValidationErrorCode::INVALID_TYPE, 'ids', array( 'type' => 'array' ) ) )
		);
	}

	/**
	 * @testdox The collector returns normalized values and throws nothing when nothing failed.
	 */
	public function test_the_collector_returns_normalized_values(): void {
		$validator = new ArgumentValidator();

		$this->assertSame( 42, $validator->get_nullable_id( 'order_id', '42' ) );
		$this->assertSame( array( 3, 4 ), $validator->get_id_list( 'ids', array( 3, '4' ) ) );
		$this->assertSame( '1.50000000', $validator->get_money( 'tax_total', '1.5' ) );
		$this->assertSame( 'EUR', $validator->get_currency( 'EUR' ) );

		$validator->throw_errors();
	}

	/**
	 * @testdox The collector returns null for a refused value and throws every error in call order.
	 */
	public function test_the_collector_throws_every_error_in_call_order(): void {
		$validator = new ArgumentValidator();

		$this->assertNull( $validator->get_string( 'name', 5 ) );
		$this->assertNull( $validator->get_positive_int( 'limit', 0 ) );
		$validator->add( new InvalidInputException( ValidationErrorCode::INVALID_VALUE, 'extension_slug', 'Not "any".' ) );
		$this->assertNull( $validator->get_nullable_array( 'billing_policy', 'monthly' ) );

		$this->assert_invalid_inputs(
			static function () use ( $validator ): void {
				$validator->throw_errors();
			},
			array(
				array( ValidationErrorCode::INVALID_TYPE, 'name', array( 'type' => 'string' ) ),
				array( ValidationErrorCode::OUT_OF_BOUNDS, 'limit', array( 'min' => 1 ) ),
				array( ValidationErrorCode::INVALID_VALUE, 'extension_slug', array() ),
				array( ValidationErrorCode::INVALID_TYPE, 'billing_policy', array( 'type' => 'object' ) ),
			)
		);
	}
}
