<?php
/**
 * Integration tests for the public Contracts write facade.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api;

use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use EngineIntegrationTestCase;
use InvalidArgumentException;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Subscriptions;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\CycleView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\StatusRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts
 */
class ContractsTest extends EngineIntegrationTestCase {

	private const OWNER = 'acme-subs';

	public function tear_down(): void {
		StatusRegistry::reset();
		parent::tear_down();
	}

	/**
	 * Read a contract back as a view, asserting it exists.
	 *
	 * @param int $id Contract id.
	 */
	private function view( int $id ): ContractView {
		$view = Subscriptions::get( $id );
		$this->assertInstanceOf( ContractView::class, $view );

		return $view;
	}

	/**
	 * Read a contract back as the entity, asserting it exists.
	 *
	 * @param int $id Contract id.
	 */
	private function entity( int $id ): Contract {
		$contract = ( new ContractRepository() )->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );

		return $contract;
	}

	public function test_an_owner_only_create_is_an_empty_draft(): void {
		$id = Contracts::create( array( 'owner' => self::OWNER ) );

		$view = $this->view( $id );
		$this->assertSame( ContractStatus::DRAFT, $view->get_status() );
		$this->assertSame( self::OWNER, $view->get_owner() );
		$this->assertNull( $view->get_customer_id() );
		$this->assertNull( $view->get_currency() );
		$this->assertNull( $view->get_selling_plan_id() );
		$this->assertNull( $view->get_start_gmt() );
		$this->assertNull( $view->get_next_payment_gmt() );
		$this->assertSame( '0.00000000', $view->get_billing_total() );
		$this->assertSame( '0.00000000', $view->get_tax_total() );
		$this->assertSame( array(), $view->get_items() );
		$this->assertNull( $view->get_plan_snapshot() );
	}

	public function test_create_stores_every_field(): void {
		$id = Contracts::create(
			array(
				'owner'                => self::OWNER,
				'status'               => ContractStatus::ACTIVE,
				'customer_id'          => '12',
				'currency'             => 'EUR',
				'selling_plan_id'      => 3,
				'origin_order_id'      => 4,
				'payment_method'       => 'dummy',
				'payment_method_title' => 'Dummy',
				'payment_token_id'     => 5,
				'start_gmt'            => '2026-01-01 00:00:00',
				'next_payment_gmt'     => '2026-02-01 00:00:00',
				'schedule_source'      => Contract::SCHEDULE_SOURCE_GATEWAY,
				'billing_total'        => 20,
				'discount_total'       => '1.5',
				'shipping_total'       => 5.25,
				'tax_total'            => '2',
				'items'                => array(
					array(
						'item_name'  => 'Coffee',
						'product_id' => 9,
						'quantity'   => '2',
						'total'      => '20',
					),
				),
				'addresses'            => array(
					'billing' => array(
						'first_name' => 'Ada',
						'country'    => 'PT',
					),
				),
			)
		);

		$view = $this->view( $id );
		$this->assertSame( ContractStatus::ACTIVE, $view->get_status() );
		$this->assertSame( 12, $view->get_customer_id() );
		$this->assertSame( 'EUR', $view->get_currency() );
		$this->assertSame( 3, $view->get_selling_plan_id() );
		$this->assertSame( 4, $view->get_origin_order_id() );
		$this->assertSame( 'dummy', $view->get_payment_method() );
		$this->assertSame( 'Dummy', $view->get_payment_method_title() );
		$this->assertSame( 5, $view->get_payment_token_id() );
		$this->assertSame( '2026-01-01 00:00:00', $view->get_start_gmt() );
		$this->assertSame( '2026-02-01 00:00:00', $view->get_next_payment_gmt() );
		$this->assertSame( Contract::SCHEDULE_SOURCE_GATEWAY, $view->get_schedule_source() );
		$this->assertSame( '20.00000000', $view->get_billing_total() );
		$this->assertSame( '1.50000000', $view->get_discount_total() );
		$this->assertSame( '5.25000000', $view->get_shipping_total() );
		$this->assertSame( '2.00000000', $view->get_tax_total() );

		$items = $view->get_items();
		$this->assertIsArray( $items );
		$this->assertCount( 1, $items );
		$this->assertSame( 'Coffee', $items[0]['item_name'] );

		$addresses = $view->get_addresses();
		$this->assertIsArray( $addresses );
		$this->assertSame( 'Ada', $addresses['billing']['first_name'] ?? null );
	}

	public function test_datetime_objects_are_stored_as_utc_strings(): void {
		$id = Contracts::create(
			array(
				'owner'            => self::OWNER,
				'start_gmt'        => new DateTimeImmutable( '2026-01-01 02:00:00', new DateTimeZone( 'Europe/Lisbon' ) ),
				'next_payment_gmt' => new DateTimeImmutable( '2026-07-01 02:00:00', new DateTimeZone( 'Europe/Lisbon' ) ),
			)
		);

		$view = $this->view( $id );
		$this->assertSame( '2026-01-01 02:00:00', $view->get_start_gmt() );
		$this->assertSame( '2026-07-01 01:00:00', $view->get_next_payment_gmt() );
	}

	public function test_an_unknown_key_is_rejected(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'unknown key "custmer_id"' );

		Contracts::create(
			array(
				'owner'      => self::OWNER,
				'custmer_id' => 1,
			)
		);
	}

	/**
	 * @dataProvider provide_bad_owners
	 *
	 * @param array<string, mixed> $args Create args.
	 */
	public function test_a_missing_or_empty_owner_is_rejected( array $args ): void {
		$this->expectException( InvalidArgumentException::class );

		Contracts::create( $args );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public function provide_bad_owners(): array {
		return array(
			'missing'    => array( array() ),
			'empty'      => array( array( 'owner' => '' ) ),
			'not string' => array( array( 'owner' => 5 ) ),
		);
	}

	/**
	 * @dataProvider provide_invalid_fields
	 *
	 * @param array<string, mixed> $fields Invalid fields.
	 */
	public function test_invalid_values_are_rejected_without_a_write( array $fields ): void {
		$before = Subscriptions::count();

		try {
			Contracts::create( array_merge( array( 'owner' => self::OWNER ), $fields ) );
			$this->fail( 'Expected an InvalidArgumentException.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertSame( $before, Subscriptions::count(), 'Nothing is written.' );
		}
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public function provide_invalid_fields(): array {
		return array(
			'unregistered status'    => array( array( 'status' => 'nonsense' ) ),
			'lowercase currency'     => array( array( 'currency' => 'usd' ) ),
			'long currency'          => array( array( 'currency' => 'USDX' ) ),
			'zero customer'          => array( array( 'customer_id' => 0 ) ),
			'negative plan'          => array( array( 'selling_plan_id' => -3 ) ),
			'non-numeric token'      => array( array( 'payment_token_id' => 'abc' ) ),
			'malformed date'         => array( array( 'start_gmt' => '2026-01-01' ) ),
			'impossible date'        => array( array( 'end_gmt' => '2026-02-30 00:00:00' ) ),
			'non-numeric money'      => array(
				array(
					'currency'      => 'USD',
					'billing_total' => 'ten',
				),
			),
			'money without currency' => array( array( 'billing_total' => '10' ) ),
			'bad schedule source'    => array( array( 'schedule_source' => 'cron' ) ),
			'non-string method'      => array( array( 'payment_method' => 5 ) ),
			'items not a list'       => array( array( 'items' => array( 'a' => array() ) ) ),
			'unknown item key'       => array( array( 'items' => array( array( 'price' => '1' ) ) ) ),
			'unknown address type'   => array( array( 'addresses' => array( 'home' => array() ) ) ),
			'unknown address key'    => array( array( 'addresses' => array( 'billing' => array( 'zip' => '1' ) ) ) ),
			'plan snapshot scalar'   => array( array( 'plan_snapshot' => 'x' ) ),
			'items snapshot scalar'  => array( array( 'items_snapshot' => array( 'x' ) ) ),
		);
	}

	public function test_money_without_currency_is_rejected_on_update(): void {
		$id = Contracts::create( array( 'owner' => self::OWNER ) );

		$this->expectException( InvalidArgumentException::class );

		Contracts::update( $id, array( 'billing_total' => '10' ) );
	}

	public function test_a_null_money_value_resets_to_zero_without_a_currency(): void {
		$id = Contracts::create( array( 'owner' => self::OWNER ) );

		$this->assertTrue( Contracts::update( $id, array( 'billing_total' => null ) ) );
		$this->assertSame( '0.00000000', $this->view( $id )->get_billing_total() );
	}

	public function test_the_currency_cannot_be_cleared_while_a_total_is_non_zero(): void {
		$id = Contracts::create(
			array(
				'owner'         => self::OWNER,
				'currency'      => 'USD',
				'billing_total' => '10',
			)
		);

		try {
			Contracts::update( $id, array( 'currency' => null ) );
			$this->fail( 'Expected an InvalidArgumentException.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertSame( 'USD', $this->view( $id )->get_currency() );
		}

		// Clearing the totals in the same call is allowed.
		$this->assertTrue(
			Contracts::update(
				$id,
				array(
					'currency'      => null,
					'billing_total' => null,
				)
			)
		);
		$this->assertNull( $this->view( $id )->get_currency() );
	}

	public function test_progressive_update_builds_the_contract(): void {
		$customer = self::factory()->user->create();
		$this->assertIsInt( $customer );

		$id = Contracts::create( array( 'owner' => self::OWNER ) );

		$this->assertTrue( Contracts::update( $id, array( 'customer_id' => $customer ) ) );
		$this->assertTrue(
			Contracts::update(
				$id,
				array(
					'currency'      => 'USD',
					'billing_total' => '19.99',
				)
			)
		);
		$this->assertTrue( Contracts::update( $id, array( 'status' => ContractStatus::ACTIVE ) ) );

		$view = $this->view( $id );
		$this->assertSame( $customer, $view->get_customer_id() );
		$this->assertSame( 'USD', $view->get_currency() );
		$this->assertSame( '19.99000000', $view->get_billing_total() );
		$this->assertSame( ContractStatus::ACTIVE, $view->get_status() );
		$this->assertSame( self::OWNER, $view->get_owner() );
	}

	public function test_update_keeps_unmentioned_payment_fields(): void {
		$id = Contracts::create(
			array(
				'owner'                => self::OWNER,
				'payment_method'       => 'dummy',
				'payment_method_title' => 'Dummy',
				'payment_token_id'     => 5,
			)
		);

		Contracts::update( $id, array( 'payment_token_id' => 6 ) );

		$view = $this->view( $id );
		$this->assertSame( 'dummy', $view->get_payment_method() );
		$this->assertSame( 'Dummy', $view->get_payment_method_title() );
		$this->assertSame( 6, $view->get_payment_token_id() );
	}

	public function test_update_does_not_revert_a_concurrent_write_to_other_fields(): void {
		global $wpdb;

		$id = Contracts::create(
			array(
				'owner'            => self::OWNER,
				'status'           => ContractStatus::ACTIVE,
				'next_payment_gmt' => '2026-02-01 00:00:00',
			)
		);

		// A concurrent writer (e.g. a cancel compare-and-set) lands after the facade
		// read the contract and before its own write.
		$table    = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$injected = false;
		$race     = static function ( string $query ) use ( &$injected, $table, $id, $wpdb ): string {
			if ( ! $injected && 0 === strpos( $query, "UPDATE `{$table}`" ) ) {
				$injected = true;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table,
					array(
						'status'           => ContractStatus::CANCELLED,
						'next_payment_gmt' => null,
					),
					array( 'id' => $id )
				);
			}

			return $query;
		};
		add_filter( 'query', $race );

		try {
			$this->assertTrue( Contracts::update( $id, array( 'payment_method_title' => 'X' ) ) );
		} finally {
			remove_filter( 'query', $race );
		}

		$this->assertTrue( $injected );
		$view = $this->view( $id );
		$this->assertSame( 'X', $view->get_payment_method_title() );
		$this->assertSame( ContractStatus::CANCELLED, $view->get_status() );
		$this->assertNull( $view->get_next_payment_gmt() );
	}

	public function test_items_and_addresses_are_replaced_as_a_whole(): void {
		$id = Contracts::create(
			array(
				'owner'     => self::OWNER,
				'items'     => array( array( 'item_name' => 'Coffee' ), array( 'item_name' => 'Tea' ) ),
				'addresses' => array(
					'billing'  => array( 'city' => 'Lisbon' ),
					'shipping' => array( 'city' => 'Porto' ),
				),
			)
		);

		Contracts::update(
			$id,
			array(
				'items'     => array( array( 'item_name' => 'Cocoa' ) ),
				'addresses' => array( 'billing' => array( 'city' => 'Faro' ) ),
			)
		);

		$view  = $this->view( $id );
		$items = $view->get_items();
		$this->assertIsArray( $items );
		$this->assertSame( array( 'Cocoa' ), array_column( $items, 'item_name' ) );
		$addresses = $view->get_addresses();
		$this->assertIsArray( $addresses );
		$this->assertSame( array( 'billing' ), array_keys( $addresses ) );
		$this->assertSame( 'Faro', $addresses['billing']['city'] ?? null );
	}

	public function test_null_clears_nullable_fields(): void {
		$id = Contracts::create(
			array(
				'owner'            => self::OWNER,
				'selling_plan_id'  => 3,
				'next_payment_gmt' => '2026-02-01 00:00:00',
			)
		);

		Contracts::update(
			$id,
			array(
				'selling_plan_id'  => null,
				'next_payment_gmt' => null,
			)
		);

		$view = $this->view( $id );
		$this->assertNull( $view->get_selling_plan_id() );
		$this->assertNull( $view->get_next_payment_gmt() );
	}

	public function test_update_on_an_unknown_contract_returns_false(): void {
		$this->assertFalse( Contracts::update( 999999, array( 'status' => ContractStatus::ACTIVE ) ) );
	}

	public function test_owner_is_not_an_update_key(): void {
		$id = Contracts::create( array( 'owner' => self::OWNER ) );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'unknown key "owner"' );

		Contracts::update( $id, array( 'owner' => 'other' ) );
	}

	public function test_snapshot_payloads_are_stored_and_copy_forwarded(): void {
		$plan  = array(
			'selling_plan_id' => 3,
			'name'            => 'Monthly',
			'billing_policy'  => array(
				'period'   => 'month',
				'interval' => 1,
			),
		);
		$items = array( array( 'item_name' => 'Coffee' ) );

		$id = Contracts::create(
			array(
				'owner'          => self::OWNER,
				'plan_snapshot'  => $plan,
				'items_snapshot' => $items,
			)
		);

		$this->assertSame( $plan, $this->view( $id )->get_plan_snapshot() );
		$plan_snapshot_id  = $this->entity( $id )->get_plan_snapshot_id();
		$items_snapshot_id = $this->entity( $id )->get_items_snapshot_id();
		$this->assertNotNull( $plan_snapshot_id );
		$this->assertNotNull( $items_snapshot_id );

		// The same payload keeps the snapshot.
		Contracts::update( $id, array( 'plan_snapshot' => $plan ) );
		$this->assertSame( $plan_snapshot_id, $this->entity( $id )->get_plan_snapshot_id() );

		// A changed payload repoints the plan snapshot only.
		$plan['name'] = 'Monthly (renamed)';
		Contracts::update( $id, array( 'plan_snapshot' => $plan ) );
		$this->assertNotSame( $plan_snapshot_id, $this->entity( $id )->get_plan_snapshot_id() );
		$this->assertSame( $items_snapshot_id, $this->entity( $id )->get_items_snapshot_id() );
		$this->assertSame( $plan, $this->view( $id )->get_plan_snapshot() );
	}

	public function test_a_registered_extension_status_is_accepted(): void {
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, 'paused-by-merchant' );

		$id = Contracts::create(
			array(
				'owner'  => self::OWNER,
				'status' => 'paused-by-merchant',
			)
		);

		$this->assertSame( 'paused-by-merchant', $this->view( $id )->get_status() );
	}

	/**
	 * Create a contract carrying a currency and snapshots, ready for cycles.
	 */
	private function contract_with_snapshots(): int {
		return Contracts::create(
			array(
				'owner'          => self::OWNER,
				'currency'       => 'USD',
				'plan_snapshot'  => array(
					'selling_plan_id' => 3,
					'billing_policy'  => array(
						'period'   => 'month',
						'interval' => 1,
					),
				),
				'items_snapshot' => array( array( 'item_name' => 'Coffee' ) ),
			)
		);
	}

	/**
	 * Fetch a stored cycle by id.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $cycle_id    Cycle id.
	 */
	private function cycle( int $contract_id, int $cycle_id ): Cycle {
		foreach ( ( new ContractRepository() )->find_cycle_history( $contract_id ) as $cycle ) {
			if ( $cycle->get_id() === $cycle_id ) {
				return $cycle;
			}
		}

		$this->fail( "Cycle {$cycle_id} not found." );
	}

	/**
	 * Minimal valid cycle args.
	 *
	 * @param array<string, mixed> $overrides Extra or replacement keys.
	 * @return array<string, mixed>
	 */
	private function cycle_args( array $overrides = array() ): array {
		return array_merge(
			array(
				'status'        => CycleStatus::BILLED,
				'starts_at_gmt' => '2026-01-01 00:00:00',
				'ends_at_gmt'   => '2026-02-01 00:00:00',
			),
			$overrides
		);
	}

	public function test_the_first_cycle_takes_the_chain_and_contract_defaults(): void {
		$id       = $this->contract_with_snapshots();
		$contract = $this->entity( $id );

		$cycle_id = Contracts::add_cycle( $id, $this->cycle_args( array( 'order_id' => 77 ) ) );
		$cycle    = $this->cycle( $id, $cycle_id );

		$this->assertSame( Cycle::KIND_BILLING, $cycle->get_kind() );
		$this->assertSame( 1, $cycle->get_sequence_no() );
		$this->assertSame( 1, $cycle->get_count() );
		$this->assertSame( 'USD', $cycle->get_currency() );
		$this->assertSame( '0.00000000', $cycle->get_expected_total() );
		$this->assertSame( 77, $cycle->get_order_id() );
		$this->assertSame( self::OWNER, $cycle->get_extension_slug() );
		$this->assertSame( $contract->get_plan_snapshot_id(), $cycle->get_plan_snapshot_id() );
		$this->assertSame( $contract->get_items_snapshot_id(), $cycle->get_items_snapshot_id() );
	}

	public function test_the_next_cycle_defaults_to_the_next_position(): void {
		$id = $this->contract_with_snapshots();
		Contracts::add_cycle( $id, $this->cycle_args() );

		$cycle_id = Contracts::add_cycle(
			$id,
			$this->cycle_args(
				array(
					'status'         => CycleStatus::PENDING,
					'starts_at_gmt'  => '2026-02-01 00:00:00',
					'ends_at_gmt'    => '2026-03-01 00:00:00',
					'expected_total' => '19.99',
				)
			)
		);
		$cycle    = $this->cycle( $id, $cycle_id );

		$this->assertSame( 2, $cycle->get_sequence_no() );
		$this->assertSame( 2, $cycle->get_count() );
		$this->assertSame( '19.99000000', $cycle->get_expected_total() );
	}

	public function test_a_non_counting_cycle_takes_a_null_count(): void {
		$id = $this->contract_with_snapshots();

		$cycle_id = Contracts::add_cycle( $id, $this->cycle_args( array( 'count' => null ) ) );

		$this->assertNull( $this->cycle( $id, $cycle_id )->get_count() );
	}

	public function test_a_taken_position_is_refused(): void {
		$id = $this->contract_with_snapshots();
		Contracts::add_cycle( $id, $this->cycle_args() );

		$this->expectException( DomainException::class );

		Contracts::add_cycle( $id, $this->cycle_args( array( 'sequence_no' => 1 ) ) );
	}

	/**
	 * @dataProvider provide_invalid_cycle_args
	 *
	 * @param array<string, mixed> $args Cycle args.
	 */
	public function test_invalid_cycle_args_are_rejected( array $args ): void {
		$id = $this->contract_with_snapshots();

		$this->expectException( InvalidArgumentException::class );

		Contracts::add_cycle( $id, $args );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public function provide_invalid_cycle_args(): array {
		return array(
			'missing status'       => array(
				array(
					'starts_at_gmt' => '2026-01-01 00:00:00',
					'ends_at_gmt'   => '2026-02-01 00:00:00',
				),
			),
			'unregistered status'  => array(
				array(
					'status'        => 'nonsense',
					'starts_at_gmt' => '2026-01-01 00:00:00',
					'ends_at_gmt'   => '2026-02-01 00:00:00',
				),
			),
			'missing starts_at'    => array(
				array(
					'status'      => CycleStatus::BILLED,
					'ends_at_gmt' => '2026-02-01 00:00:00',
				),
			),
			'missing ends_at'      => array(
				array(
					'status'        => CycleStatus::BILLED,
					'starts_at_gmt' => '2026-01-01 00:00:00',
				),
			),
			'unknown key'          => array(
				array(
					'status'        => CycleStatus::BILLED,
					'starts_at_gmt' => '2026-01-01 00:00:00',
					'ends_at_gmt'   => '2026-02-01 00:00:00',
					'reason'        => 'x',
				),
			),
			'zero sequence number' => array(
				array(
					'status'        => CycleStatus::BILLED,
					'starts_at_gmt' => '2026-01-01 00:00:00',
					'ends_at_gmt'   => '2026-02-01 00:00:00',
					'sequence_no'   => 0,
				),
			),
		);
	}

	public function test_a_cycle_needs_a_currency(): void {
		$id = Contracts::create( array( 'owner' => self::OWNER ) );

		$this->expectException( InvalidArgumentException::class );

		Contracts::add_cycle( $id, $this->cycle_args() );
	}

	public function test_an_explicit_currency_serves_a_contract_without_one(): void {
		$id = Contracts::create( array( 'owner' => self::OWNER ) );

		$cycle_id = Contracts::add_cycle( $id, $this->cycle_args( array( 'currency' => 'EUR' ) ) );

		$this->assertSame( 'EUR', $this->cycle( $id, $cycle_id )->get_currency() );
	}

	public function test_an_unknown_contract_is_rejected(): void {
		$this->expectException( InvalidArgumentException::class );

		Contracts::add_cycle( 999999, $this->cycle_args() );
	}

	public function test_explicit_snapshot_payloads_attach_to_the_cycle_only(): void {
		$id     = $this->contract_with_snapshots();
		$before = $this->entity( $id );

		$cycle_id = Contracts::add_cycle(
			$id,
			$this->cycle_args(
				array(
					'plan_snapshot'  => array(
						'selling_plan_id' => 3,
						'name'            => 'Different',
					),
					'items_snapshot' => array( array( 'item_name' => 'Tea' ) ),
				)
			)
		);
		$cycle    = $this->cycle( $id, $cycle_id );
		$after    = $this->entity( $id );

		$this->assertNotNull( $cycle->get_plan_snapshot_id() );
		$this->assertNotSame( $before->get_plan_snapshot_id(), $cycle->get_plan_snapshot_id() );
		$this->assertNotSame( $before->get_items_snapshot_id(), $cycle->get_items_snapshot_id() );
		$this->assertSame( $before->get_plan_snapshot_id(), $after->get_plan_snapshot_id() );
		$this->assertSame( $before->get_items_snapshot_id(), $after->get_items_snapshot_id() );
	}

	public function test_get_history_returns_the_appended_cycle_as_a_view(): void {
		$id       = $this->contract_with_snapshots();
		$cycle_id = Contracts::add_cycle( $id, $this->cycle_args( array( 'order_id' => 77 ) ) );

		$history = Subscriptions::get_history( $id );

		$this->assertCount( 1, $history );
		$this->assertInstanceOf( CycleView::class, $history[0] );
		$this->assertSame( $cycle_id, $history[0]->get_id() );
		$this->assertSame( CycleStatus::BILLED, $history[0]->get_status() );
		$this->assertSame( 77, $history[0]->get_order_id() );
	}

	public function test_meta_keeps_several_values_under_one_key(): void {
		$id = Contracts::create( array( 'owner' => self::OWNER ) );

		$this->assertIsInt( Contracts::add_meta( $id, 'note', 'one' ) );
		$this->assertIsInt( Contracts::add_meta( $id, 'note', array( 'two' ) ) );

		$this->assertSame( array( 'one', array( 'two' ) ), Contracts::get_meta( $id, 'note' ) );
		$this->assertSame( 'one', Contracts::get_meta( $id, 'note', true ) );
		$this->assertSame( array( 'note' => array( 'one', array( 'two' ) ) ), Contracts::get_meta( $id ) );
	}

	public function test_a_unique_meta_add_refuses_an_existing_key(): void {
		$id = Contracts::create( array( 'owner' => self::OWNER ) );
		Contracts::add_meta( $id, 'note', 'one' );

		$this->assertNull( Contracts::add_meta( $id, 'note', 'two', true ) );
		$this->assertSame( array( 'one' ), Contracts::get_meta( $id, 'note' ) );
	}

	public function test_update_meta_with_a_previous_value_and_delete_one_value(): void {
		$id = Contracts::create( array( 'owner' => self::OWNER ) );
		Contracts::add_meta( $id, 'note', 'one' );
		Contracts::add_meta( $id, 'note', 'two' );

		$this->assertTrue( Contracts::update_meta( $id, 'note', 'three', 'two' ) );
		$this->assertSame( array( 'one', 'three' ), Contracts::get_meta( $id, 'note' ) );

		$this->assertTrue( Contracts::delete_meta( $id, 'note', 'one' ) );
		$this->assertSame( array( 'three' ), Contracts::get_meta( $id, 'note' ) );
	}

	public function test_meta_writes_to_an_unknown_contract_write_nothing(): void {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		$this->assertNull( Contracts::add_meta( 999999, 'note', 'one' ) );
		$this->assertFalse( Contracts::update_meta( 999999, 'note', 'one' ) );
		$this->assertFalse( Contracts::delete_meta( 999999, 'note' ) );
		$this->assertSame( '', Contracts::get_meta( 999999, 'note', true ) );
		$this->assertSame( array(), Contracts::get_meta( 999999, 'note' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertSame( $before, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) );
	}

	public function test_an_empty_meta_key_is_rejected(): void {
		$id = Contracts::create( array( 'owner' => self::OWNER ) );

		$this->expectException( InvalidArgumentException::class );

		Contracts::add_meta( $id, '', 'one' );
	}

	public function test_meta_survives_a_contract_update(): void {
		$id = Contracts::create( array( 'owner' => self::OWNER ) );
		Contracts::add_meta( $id, 'note', 'kept' );

		Contracts::update(
			$id,
			array(
				'status' => ContractStatus::ACTIVE,
				'items'  => array( array( 'item_name' => 'Coffee' ) ),
			)
		);

		$this->assertSame( array( 'kept' ), Contracts::get_meta( $id, 'note' ) );
	}
}
