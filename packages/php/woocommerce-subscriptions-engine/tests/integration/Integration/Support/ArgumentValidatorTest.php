<?php
/**
 * Integration tests for the shared facade argument validators.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Support;

use DateTimeImmutable;
use DateTimeZone;
use EngineIntegrationTestCase;
use InvalidArgumentException;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\ArgumentValidator;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\ArgumentValidator
 */
class ArgumentValidatorTest extends EngineIntegrationTestCase {

	/**
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
			'string'                => array( 'validate_string', array( 'status', 'active' ), 'active' ),
			'nullable string'       => array( 'validate_nullable_string', array( 'title', null ), null ),
			'nullable id as digits' => array( 'validate_nullable_id', array( 'order_id', '42' ), 42 ),
			'nullable date object'  => array(
				'validate_nullable_date',
				array( 'start_gmt', new DateTimeImmutable( '2026-01-01 03:00:00', new DateTimeZone( 'Europe/Berlin' ) ) ),
				'2026-01-01 02:00:00',
			),
			'money'                 => array( 'validate_money', array( 'tax_total', '1.5' ), '1.50000000' ),
			'money null is zero'    => array( 'validate_money', array( 'tax_total', null ), '0.00000000' ),
			'list of arrays'        => array( 'validate_list_of_arrays', array( 'items', array( array( 'name' => 'a' ) ) ), array( array( 'name' => 'a' ) ) ),
		);
	}

	/**
	 * @dataProvider provide_invalid_values
	 *
	 * @param string            $method  Validator method.
	 * @param array<int, mixed> $args    Validator arguments.
	 * @param string            $message Expected exception message.
	 */
	public function test_an_invalid_value_is_rejected_naming_the_field( string $method, array $args, string $message ): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( $message );

		ArgumentValidator::$method( ...$args );
	}

	/**
	 * @return array<string, array{0: string, 1: array<int, mixed>, 2: string}>
	 */
	public function provide_invalid_values(): array {
		return array(
			'currency'        => array( 'validate_currency', array( 'eur' ), '"currency" must be null or a three-letter uppercase ISO-4217 code.' ),
			'string'          => array( 'validate_string', array( 'status', 5 ), '"status" must be a string.' ),
			'nullable string' => array( 'validate_nullable_string', array( 'title', 5 ), '"title" must be null or a string.' ),
			'nullable id'     => array( 'validate_nullable_id', array( 'order_id', 0 ), '"order_id" must be null or a positive integer.' ),
			'nullable date'   => array( 'validate_nullable_date', array( 'start_gmt', '2026-02-30 00:00:00' ), '"start_gmt" must be null, a DateTimeInterface, or a GMT "Y-m-d H:i:s" string.' ),
			'money'           => array( 'validate_money', array( 'tax_total', 'ten' ), '"tax_total" must be a number or a numeric string.' ),
			'list of arrays'  => array( 'validate_list_of_arrays', array( 'items', array( 'a' => array() ) ), '"items" must be a list of arrays.' ),
		);
	}

	public function test_filter_known_keys_drops_unknown_keys_with_a_notice(): void {
		$this->setExpectedIncorrectUsage( 'Acme::write' );
		$messages = array();
		add_action(
			'doing_it_wrong_run',
			static function ( $function_name, $message ) use ( &$messages ): void {
				$messages[] = $message;
			},
			10,
			2
		);

		$filtered = ArgumentValidator::filter_known_keys(
			'Acme::write',
			array(
				'known' => 1,
				'other' => 2,
			),
			array( 'known' => true ),
			'item key'
		);

		$this->assertSame( array( 'known' => 1 ), $filtered );
		$this->assertSame( array( 'Unknown item key "other" ignored.' ), $messages );
	}

	public function test_contract_items_keep_item_fields_and_drop_unknown_keys_with_a_notice(): void {
		$this->setExpectedIncorrectUsage( 'facade' );

		$rows = ArgumentValidator::validate_contract_items(
			'facade',
			array(
				array(
					'item_name' => 'Coffee',
					'price'     => '1',
				),
			)
		);

		$this->assertSame( array( array( 'item_name' => 'Coffee' ) ), $rows );
	}

	public function test_contract_addresses_reject_an_unknown_address_type(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( '"addresses" must be an array keyed "billing" / "shipping" with array values.' );

		ArgumentValidator::validate_contract_addresses( 'facade', array( 'home' => array( 'first_name' => 'Ada' ) ) );
	}

	public function test_contract_addresses_keep_address_fields_and_drop_unknown_keys_with_a_notice(): void {
		$this->setExpectedIncorrectUsage( 'facade' );

		$addresses = ArgumentValidator::validate_contract_addresses(
			'facade',
			array(
				'billing' => array(
					'first_name' => 'Ada',
					'zip'        => '1',
				),
			)
		);

		$this->assertSame( array( 'billing' => array( 'first_name' => 'Ada' ) ), $addresses );
	}
}
