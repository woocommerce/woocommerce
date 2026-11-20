<?php
/**
 * Integration tests for the owner slug format check at every facade entry point.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api;

use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLock;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Plans;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ValidationException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;

/**
 * An `extension_slug` outside `[a-z0-9_-]{1,64}` is refused before any engine table is read or
 * written, at every facade that takes an owner.
 *
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\Plans
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ArgumentValidator
 */
class OwnerSlugValidationTest extends EngineIntegrationTestCase {

	/**
	 * A valid owner slug.
	 */
	private const OWNER = 'acme-subs';

	/**
	 * Id of a contract of {@see self::OWNER}.
	 *
	 * @var int
	 */
	private $contract_id;

	/**
	 * Id of a plan of {@see self::OWNER}.
	 *
	 * @var int
	 */
	private $plan_id;

	/**
	 * Id of a pending cycle of the contract.
	 *
	 * @var int
	 */
	private $cycle_id;

	/**
	 * The lock taken when the contract was created, still held.
	 *
	 * @var ContractLock
	 */
	private $lock;

	/**
	 * Create a contract (kept locked) and a plan of the valid owner.
	 */
	public function setUp(): void {
		parent::setUp();

		$created = Contracts::create( array( 'extension_slug' => self::OWNER ) );
		$this->assertIsArray( $created );
		$this->contract_id = (int) $created['contract']->get_id();
		$this->lock        = $created['lock'];

		$cycle = Contracts::add_cycle( $this->contract_id, $this->cycle_args( self::OWNER ), $this->lock );
		$this->assertNotNull( $cycle );
		$this->cycle_id = (int) $cycle->get_id();

		$this->plan_id = $this->make_plan( array( 'extension_slug' => self::OWNER ) );
	}

	/**
	 * Release the contract lock.
	 */
	public function tearDown(): void {
		$this->lock->release();

		parent::tearDown();
	}

	/**
	 * @testdox $entry refuses an invalid owner slug with invalid_pattern before any engine query.
	 * @dataProvider provide_entry_points
	 *
	 * @param string $entry Entry point name.
	 */
	public function test_an_invalid_owner_slug_is_refused_before_any_query( string $entry ): void {
		foreach ( array( 'lite ', ' lite', 'LITE', 'li te', 'lité', str_repeat( 'a', 65 ) ) as $slug ) {
			$queries = $this->count_engine_queries(
				function () use ( $entry, $slug ): void {
					$this->assert_refused_with_pattern_error( $entry, $slug );
				}
			);

			$this->assertSame( 0, $queries, sprintf( '%s with "%s" ran an engine query.', $entry, $slug ) );
		}
	}

	/**
	 * @testdox $entry accepts a valid owner slug.
	 * @dataProvider provide_entry_points
	 *
	 * @param string $entry Entry point name.
	 */
	public function test_a_valid_owner_slug_is_accepted( string $entry ): void {
		try {
			$this->call( $entry, self::OWNER );
		} catch ( ValidationException $e ) {
			$this->fail( sprintf( '%s refused a valid owner slug: %s', $entry, $e->getMessage() ) );
		}

		$this->addToAssertionCount( 1 );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public function provide_entry_points(): array {
		$entries = array(
			'Contracts::lock',
			'Contracts::create',
			'Contracts::update',
			'Contracts::add_cycle',
			'Contracts::transition_cycle',
			'Contracts::add_meta',
			'Contracts::update_meta',
			'Contracts::delete_meta',
			'Contracts::list_for_customer',
			'Plans::create',
			'Plans::update',
			'Plans::add_meta',
			'Plans::update_meta',
			'Plans::delete_meta',
			'Plans::list',
		);

		return array_combine(
			$entries,
			array_map(
				static function ( string $entry ): array {
					return array( $entry );
				},
				$entries
			)
		);
	}

	/**
	 * @testdox Plans::list reports an invalid slug in a list by its index.
	 */
	public function test_plans_list_reports_an_invalid_list_item_by_index(): void {
		try {
			Plans::list( array( 'extension_slug' => array( self::OWNER, 'LITE' ) ) );
			$this->fail( 'Expected a ValidationException.' );
		} catch ( ValidationException $e ) {
			$this->assertSame( array( 'extension_slug.1' ), $this->get_pattern_error_fields( $e ) );
		}
	}

	/**
	 * Call an entry point with `$slug` as the owner and otherwise valid arguments.
	 *
	 * @param string $entry Entry point name.
	 * @param string $slug  Owner slug.
	 */
	private function call( string $entry, string $slug ): void {
		switch ( $entry ) {
			case 'Contracts::lock':
				$lock = Contracts::lock( $this->contract_id, $slug, 'test' );
				if ( null !== $lock ) {
					$lock->release();
				}
				return;
			case 'Contracts::create':
				$created = Contracts::create( array( 'extension_slug' => $slug ) );
				if ( is_array( $created ) ) {
					$created['lock']->release();
				}
				return;
			case 'Contracts::update':
				Contracts::update(
					$this->contract_id,
					array(
						'extension_slug' => $slug,
						'currency'       => 'EUR',
					),
					$this->lock
				);
				return;
			case 'Contracts::add_cycle':
				Contracts::add_cycle(
					$this->contract_id,
					$this->cycle_args( $slug ),
					$this->lock
				);
				return;
			case 'Contracts::transition_cycle':
				Contracts::transition_cycle(
					$this->cycle_id,
					array(
						'extension_slug' => $slug,
						'from_status'    => CycleStatus::PENDING,
						'to_status'      => CycleStatus::BILLED,
					),
					$this->lock
				);
				return;
			case 'Contracts::add_meta':
				Contracts::add_meta( $this->contract_id, $slug, 'note', 'x', false, $this->lock );
				return;
			case 'Contracts::update_meta':
				Contracts::update_meta( $this->contract_id, $slug, 'note', 'x', null, $this->lock );
				return;
			case 'Contracts::delete_meta':
				Contracts::delete_meta( $this->contract_id, $slug, 'note', null, $this->lock );
				return;
			case 'Contracts::list_for_customer':
				Contracts::list_for_customer( 1, 20, 0, array( 'extension_slug' => $slug ) );
				return;
			case 'Plans::create':
				Plans::create(
					array(
						'extension_slug' => $slug,
						'name'           => 'Monthly',
					)
				);
				return;
			case 'Plans::update':
				Plans::update(
					$this->plan_id,
					array(
						'extension_slug' => $slug,
						'name'           => 'Renamed',
					)
				);
				return;
			case 'Plans::add_meta':
				Plans::add_meta( $this->plan_id, $slug, 'note', 'x' );
				return;
			case 'Plans::update_meta':
				Plans::update_meta( $this->plan_id, $slug, 'note', 'x' );
				return;
			case 'Plans::delete_meta':
				Plans::delete_meta( $this->plan_id, $slug, 'note' );
				return;
			case 'Plans::list':
				Plans::list( array( 'extension_slug' => $slug ) );
				return;
		}

		$this->fail( sprintf( 'Unknown entry point %s.', $entry ) );
	}

	/**
	 * Valid pending-cycle args owned by `$slug`.
	 *
	 * @param string $slug Owner slug.
	 * @return array<string, mixed>
	 */
	private function cycle_args( string $slug ): array {
		return array(
			'extension_slug' => $slug,
			'status'         => CycleStatus::PENDING,
			'starts_at_gmt'  => '2026-01-01 00:00:00',
			'ends_at_gmt'    => '2026-02-01 00:00:00',
			'currency'       => 'USD',
		);
	}

	/**
	 * Assert the entry point refuses `$slug` with an `invalid_pattern` error on `extension_slug`.
	 *
	 * @param string $entry Entry point name.
	 * @param string $slug  Owner slug.
	 */
	private function assert_refused_with_pattern_error( string $entry, string $slug ): void {
		try {
			$this->call( $entry, $slug );
		} catch ( ValidationException $e ) {
			$this->assertSame( array( 'extension_slug' ), $this->get_pattern_error_fields( $e ), sprintf( '%s with "%s".', $entry, $slug ) );
			return;
		}

		$this->fail( sprintf( '%s accepted "%s".', $entry, $slug ) );
	}

	/**
	 * Fields of the `invalid_pattern` errors a validation exception carries.
	 *
	 * @param ValidationException $e Exception.
	 * @return array<int, mixed>
	 */
	private function get_pattern_error_fields( ValidationException $e ): array {
		$fields = array();
		foreach ( $e->get_errors()->get_all_error_data( ValidationErrorCode::INVALID_PATTERN ) as $data ) {
			$fields[] = is_array( $data ) ? ( $data['field'] ?? null ) : null;
		}

		return $fields;
	}

	/**
	 * Run `$run` and count the statements it sends to engine tables.
	 *
	 * @param callable $run Code to run.
	 */
	private function count_engine_queries( callable $run ): int {
		$count = 0;
		$watch = static function ( $query ) use ( &$count ) {
			if ( is_string( $query ) && 1 === preg_match( '/wc_subscription_|wc_selling_plan/', $query ) ) {
				++$count;
			}
			return $query;
		};
		add_filter( 'query', $watch );
		try {
			$run();
		} finally {
			remove_filter( 'query', $watch );
		}

		return $count;
	}
}
