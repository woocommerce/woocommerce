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
	 * @param string               $method  Validator method.
	 * @param array<int, mixed>    $args    Validator arguments.
	 * @param string               $code    Expected error code.
	 * @param string               $field   Expected field.
	 * @param array<string, mixed> $params  Expected params.
	 * @param string               $message Expected English message.
	 */
	public function test_an_invalid_value_is_refused_with_its_code( string $method, array $args, string $code, string $field, array $params, string $message ): void {
		$e = $this->assert_invalid_input(
			static function () use ( $method, $args ): void {
				ArgumentValidator::$method( ...$args );
			},
			$code,
			$field,
			$params
		);

		$this->assertSame( $message, $e->getMessage() );
	}

	/**
	 * @return array<string, array{0: string, 1: array<int, mixed>, 2: string, 3: string, 4: array<string, mixed>, 5: string}>
	 */
	public function provide_invalid_values(): array {
		$type_string  = array( 'type' => 'string' );
		$type_integer = array( 'type' => 'integer' );
		$type_array   = array( 'type' => 'array' );
		$type_object  = array( 'type' => 'object' );
		$min_one      = array( 'min' => 1 );

		return array(
			'currency'              => array( 'validate_currency', array( 'eur' ), ValidationErrorCode::INVALID_VALUE, 'currency', array(), '"currency" must be null or a three-letter uppercase ISO-4217 code.' ),
			'currency newline'      => array( 'validate_currency', array( "EUR\n" ), ValidationErrorCode::INVALID_VALUE, 'currency', array(), '"currency" must be null or a three-letter uppercase ISO-4217 code.' ),
			'string'                => array( 'validate_string', array( 'status', 5 ), ValidationErrorCode::INVALID_TYPE, 'status', $type_string, '"status" must be a string.' ),
			'nullable string'       => array( 'validate_nullable_string', array( 'title', 5 ), ValidationErrorCode::INVALID_TYPE, 'title', $type_string, '"title" must be null or a string.' ),
			'non-empty not string'  => array( 'validate_non_empty_string', array( 'extension_slug', 5 ), ValidationErrorCode::INVALID_TYPE, 'extension_slug', $type_string, '"extension_slug" must be a non-empty string.' ),
			'non-empty null'        => array( 'validate_non_empty_string', array( 'extension_slug', null ), ValidationErrorCode::REQUIRED, 'extension_slug', array(), '"extension_slug" must be a non-empty string.' ),
			'non-empty empty'       => array( 'validate_non_empty_string', array( 'extension_slug', '' ), ValidationErrorCode::REQUIRED, 'extension_slug', array(), '"extension_slug" must be a non-empty string.' ),
			'positive int type'     => array( 'validate_positive_int', array( 'claim_seconds', null ), ValidationErrorCode::INVALID_TYPE, 'claim_seconds', $type_integer, '"claim_seconds" must be a positive integer.' ),
			'positive int zero'     => array( 'validate_positive_int', array( 'claim_seconds', 0 ), ValidationErrorCode::OUT_OF_BOUNDS, 'claim_seconds', $min_one, '"claim_seconds" must be a positive integer.' ),
			'nullable id type'      => array( 'validate_nullable_id', array( 'order_id', "5\n" ), ValidationErrorCode::INVALID_TYPE, 'order_id', $type_integer, '"order_id" must be null or a positive integer.' ),
			'nullable id zero'      => array( 'validate_nullable_id', array( 'order_id', 0 ), ValidationErrorCode::OUT_OF_BOUNDS, 'order_id', $min_one, '"order_id" must be null or a positive integer.' ),
			'non-negative type'     => array( 'validate_non_negative_int', array( 'offset', 1.5 ), ValidationErrorCode::INVALID_TYPE, 'offset', $type_integer, '"offset" must be a non-negative integer.' ),
			'non-negative negative' => array( 'validate_non_negative_int', array( 'offset', -1 ), ValidationErrorCode::OUT_OF_BOUNDS, 'offset', array( 'min' => 0 ), '"offset" must be a non-negative integer.' ),
			'id list map'           => array( 'validate_id_list', array( 'ids', array( 'a' => 3 ) ), ValidationErrorCode::INVALID_TYPE, 'ids', $type_array, '"ids" must be a list of positive integers.' ),
			'id list entry zero'    => array( 'validate_id_list', array( 'ids', array( 3, 0 ) ), ValidationErrorCode::OUT_OF_BOUNDS, 'ids[1]', $min_one, '"ids[1]" must be a positive integer.' ),
			'id list entry null'    => array( 'validate_id_list', array( 'ids', array( null ) ), ValidationErrorCode::INVALID_TYPE, 'ids[0]', $type_integer, '"ids[0]" must be a positive integer.' ),
			'string list not list'  => array( 'validate_string_list', array( 'status', 5 ), ValidationErrorCode::INVALID_TYPE, 'status', $type_array, '"status" must be a non-empty string or a list of them.' ),
			'string list empty'     => array( 'validate_string_list', array( 'status', '' ), ValidationErrorCode::REQUIRED, 'status', array(), '"status" must be a non-empty string.' ),
			'string list entry'     => array( 'validate_string_list', array( 'status', array( 'active', '' ) ), ValidationErrorCode::REQUIRED, 'status[1]', array(), '"status[1]" must be a non-empty string.' ),
			'string list entry int' => array( 'validate_string_list', array( 'status', array( 5 ) ), ValidationErrorCode::INVALID_TYPE, 'status[0]', $type_string, '"status[0]" must be a non-empty string.' ),
			'nullable array'        => array( 'validate_nullable_array', array( 'billing_policy', 'monthly' ), ValidationErrorCode::INVALID_TYPE, 'billing_policy', $type_object, '"billing_policy" must be null or an array.' ),
			'list of arrays map'    => array( 'validate_list_of_arrays', array( 'items', array( 'a' => array() ) ), ValidationErrorCode::INVALID_TYPE, 'items', $type_array, '"items" must be a list of arrays.' ),
			'list of arrays entry'  => array( 'validate_list_of_arrays', array( 'items', array( 'a' ) ), ValidationErrorCode::INVALID_TYPE, 'items[0]', $type_object, '"items[0]" must be an array.' ),
			'money'                 => array( 'validate_money', array( 'tax_total', 'ten' ), ValidationErrorCode::INVALID_TYPE, 'tax_total', array( 'type' => 'number' ), '"tax_total" must be a number or a numeric string.' ),
			'nullable date'         => array( 'validate_nullable_date', array( 'start_gmt', '2026-02-30 00:00:00' ), ValidationErrorCode::INVALID_VALUE, 'start_gmt', array(), '"start_gmt" must be null, a DateTimeInterface, or a GMT "Y-m-d H:i:s" string.' ),
			'addresses not array'   => array( 'validate_contract_addresses', array( 'home' ), ValidationErrorCode::INVALID_TYPE, 'addresses', $type_object, '"addresses" must be an array keyed "billing" / "shipping".' ),
			'address unknown type'  => array(
				'validate_contract_addresses',
				array( array( 'home' => array( 'first_name' => 'Ada' ) ) ),
				ValidationErrorCode::NOT_IN_ENUM,
				'addresses[home]',
				array( 'allowed' => array( 'billing', 'shipping' ) ),
				'"addresses[home]" is not an address type; use "billing" or "shipping".',
			),
			'address not array'     => array( 'validate_contract_addresses', array( array( 'billing' => 'Ada' ) ), ValidationErrorCode::INVALID_TYPE, 'addresses[billing]', $type_object, '"addresses[billing]" must be an array.' ),
		);
	}
}
