<?php
/**
 * Unit tests for the HistoryEntry entity.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use ReflectionClass;
use ReflectionMethod;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\HistoryEntry;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ArgumentValidator;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;
use Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\AssertsInvalidInput;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\HistoryEntry
 */
class HistoryEntryTest extends TestCase {

	use AssertsInvalidInput;

	/**
	 * @testdox create() builds an unsaved entry from the given attributes.
	 */
	public function test_create_builds_an_unsaved_entry(): void {
		$entry = HistoryEntry::create(
			array(
				'id'               => 99,
				'contract_id'      => 7,
				'cycle_id'         => 3,
				'type'             => 'renewal-paid',
				'creation_key'     => 'renewal-paid-3',
				'payload'          => array( 'actor' => array( 'role' => 'system' ) ),
				'date_created_gmt' => '2026-01-01 00:00:00',
			)
		);

		$this->assertNull( $entry->get_id() );
		$this->assertNull( $entry->get_date_created_gmt() );
		$this->assertSame( 7, $entry->get_contract_id() );
		$this->assertSame( 3, $entry->get_cycle_id() );
		$this->assertSame( 'renewal-paid', $entry->get_type() );
		$this->assertSame( 'renewal-paid-3', $entry->get_creation_key() );
		$this->assertSame( array( 'actor' => array( 'role' => 'system' ) ), $entry->get_payload() );
	}

	/**
	 * @testdox create() reports every invalid attribute at once, each with its code and field.
	 */
	public function test_create_reports_every_error_at_once(): void {
		$this->assert_invalid_inputs(
			static function (): void {
				HistoryEntry::create(
					array(
						'contract_id'  => 7,
						'type'         => 'Not A Slug',
						'creation_key' => '   ',
						'payload'      => array( 'amount' => NAN ),
					)
				);
			},
			array(
				array( ValidationErrorCode::INVALID_PATTERN, 'type', array( 'pattern' => ArgumentValidator::SLUG_PATTERN ) ),
				array( ValidationErrorCode::INVALID_VALUE, 'creation_key', array() ),
				array( ValidationErrorCode::INVALID_VALUE, 'payload', array() ),
			)
		);
	}

	/**
	 * @testdox create() refuses an invalid $field value with $code.
	 * @dataProvider provide_invalid_attributes
	 *
	 * @param array<string, mixed> $args  Entry attributes.
	 * @param string               $code  Expected error code.
	 * @param string               $field Expected field.
	 */
	public function test_create_refuses_an_invalid_attribute( array $args, string $code, string $field ): void {
		$this->assert_invalid_inputs(
			static function () use ( $args ): void {
				HistoryEntry::create( $args + array( 'contract_id' => 7 ) );
			},
			array( array( $code, $field ) )
		);
	}

	/**
	 * @return array<string, array{0: array<string, mixed>, 1: string, 2: string}>
	 */
	public function provide_invalid_attributes(): array {
		return array(
			'missing type'          => array( array(), ValidationErrorCode::REQUIRED, 'type' ),
			'empty type'            => array( array( 'type' => '' ), ValidationErrorCode::REQUIRED, 'type' ),
			'non-string type'       => array( array( 'type' => 5 ), ValidationErrorCode::INVALID_TYPE, 'type' ),
			'over-long type'        => array( array( 'type' => str_repeat( 'a', 65 ) ), ValidationErrorCode::INVALID_PATTERN, 'type' ),
			'malformed type'        => array( array( 'type' => 'held!' ), ValidationErrorCode::INVALID_PATTERN, 'type' ),
			'non-array payload'     => array(
				array(
					'type'    => 'note',
					'payload' => 'text',
				),
				ValidationErrorCode::INVALID_TYPE,
				'payload',
			),
			'non-encodable payload' => array(
				array(
					'type'    => 'note',
					'payload' => array( 'text' => "\xB1\x31" ),
				),
				ValidationErrorCode::INVALID_VALUE,
				'payload',
			),
			'non-string key'        => array(
				array(
					'type'         => 'note',
					'creation_key' => 5,
				),
				ValidationErrorCode::INVALID_TYPE,
				'creation_key',
			),
			'blank key'             => array(
				array(
					'type'         => 'note',
					'creation_key' => '',
				),
				ValidationErrorCode::INVALID_VALUE,
				'creation_key',
			),
			'over-long key'         => array(
				array(
					'type'         => 'note',
					'creation_key' => str_repeat( 'k', 101 ),
				),
				ValidationErrorCode::INVALID_VALUE,
				'creation_key',
			),
		);
	}

	/**
	 * @testdox The creation key is trimmed of surrounding whitespace.
	 */
	public function test_create_trims_the_creation_key(): void {
		$entry = HistoryEntry::create(
			array(
				'contract_id'  => 7,
				'type'         => 'created',
				'creation_key' => " created \n",
			)
		);

		$this->assertSame( 'created', $entry->get_creation_key() );
	}

	/**
	 * @testdox The payload is stored as JSON and decoded on hydration.
	 */
	public function test_payload_round_trips_through_storage(): void {
		$payload = array(
			'text'   => 'Café ☕',
			'nested' => array( 1, 2, array( 'deep' => true ) ),
		);
		$entry   = HistoryEntry::create(
			array(
				'contract_id' => 7,
				'type'        => 'note',
				'payload'     => $payload,
			)
		);

		$stored = $entry->to_storage();
		$this->assertIsString( $stored['payload'] );

		$stored['id']               = 5;
		$stored['date_created_gmt'] = '2026-01-01 00:00:00';
		$hydrated                   = HistoryEntry::from_storage( $stored );

		$this->assertSame( 5, $hydrated->get_id() );
		$this->assertSame( '2026-01-01 00:00:00', $hydrated->get_date_created_gmt() );
		$this->assertSame( $payload, $hydrated->get_payload() );
	}

	/**
	 * @testdox A null payload stays null in storage and on hydration.
	 */
	public function test_null_payload_stays_null(): void {
		$entry = HistoryEntry::create(
			array(
				'contract_id' => 7,
				'type'        => 'held',
			)
		);

		$this->assertNull( $entry->to_storage()['payload'] );
		$this->assertNull( HistoryEntry::from_storage( $entry->to_storage() )->get_payload() );
	}

	/**
	 * @testdox from_storage() does not validate stored values.
	 */
	public function test_from_storage_does_not_validate(): void {
		$entry = HistoryEntry::from_storage(
			array(
				'id'          => '4',
				'contract_id' => '7',
				'type'        => 'Not A Slug',
				'payload'     => 'not json',
			)
		);

		$this->assertSame( 4, $entry->get_id() );
		$this->assertSame( 'Not A Slug', $entry->get_type() );
		$this->assertNull( $entry->get_payload() );
	}

	/**
	 * @testdox The entity is immutable: it has no setters.
	 */
	public function test_the_entity_has_no_setters(): void {
		$methods = ( new ReflectionClass( HistoryEntry::class ) )->getMethods( ReflectionMethod::IS_PUBLIC );
		foreach ( $methods as $method ) {
			$this->assertStringStartsNotWith( 'set_', $method->getName() );
		}
	}
}
