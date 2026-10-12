<?php
/**
 * Integration tests for the DueDispatcher (find, claim, notify).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Dispatch;

use DateTimeImmutable;
use DateTimeZone;
use EngineIntegrationTestCase;
use RuntimeException;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Dispatch\DueDispatcher;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership\ConsumerRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Dispatch\DueDispatcher
 */
class DueDispatcherTest extends EngineIntegrationTestCase {

	private const OWNER = 'engine-tests';

	private const SCHEDULE_CHECK_OPTION = 'woocommerce_subscriptions_engine_due_dispatch_scheduled_check';

	/**
	 * The System Under Test.
	 *
	 * @var DueDispatcher
	 */
	private $sut;

	/**
	 * Calls the recording handler received, in order.
	 *
	 * @var array<int, array{contract: ContractView, context: array<string, mixed>}>
	 */
	private $calls = array();

	public function set_up(): void {
		parent::set_up();
		ConsumerRegistry::reset();
		$this->calls = array();
		$this->sut   = new DueDispatcher();
	}

	public function tear_down(): void {
		as_unschedule_all_actions( DueDispatcher::HOOK, array(), DueDispatcher::GROUP );
		ConsumerRegistry::reset();
		parent::tear_down();
	}

	/**
	 * @testdox Overlapping ticks at the same moment notify a due contract once.
	 */
	public function test_overlapping_ticks_notify_once(): void {
		$this->register_recording_handler();
		$this->create_due_contract();

		$this->assertSame( 1, $this->sut->run_batch( $this->now() ) );
		$this->assertSame( 0, $this->sut->run_batch( $this->now() ), 'The second tick finds the contract claimed.' );
		$this->assertCount( 1, $this->calls );
	}

	/**
	 * @testdox A contract another dispatcher already claimed is not selected by the scan.
	 */
	public function test_a_contract_claimed_before_the_scan_is_not_notified(): void {
		$this->register_recording_handler();
		$id = $this->create_due_contract();

		$this->assertTrue( ( new ContractRepository() )->claim_for_dispatch( $id, '2026-02-15 00:00:00', DueDispatcher::CLAIM_TTL_SECONDS ) );

		$this->assertSame( 0, $this->sut->run_batch( $this->now() ) );
		$this->assertSame( array(), $this->calls );
	}

	/**
	 * @testdox A contract whose claim is lost between the scan and the claim is skipped.
	 */
	public function test_a_claim_lost_after_the_scan_is_skipped(): void {
		$first  = $this->create_due_contract( '2026-02-14 00:00:00' );
		$second = $this->create_due_contract( '2026-02-15 00:00:00' );
		ConsumerRegistry::register(
			self::OWNER,
			array(
				'contract_due_handler' => function ( ContractView $contract, array $context ) use ( $first, $second ): void {
					$this->calls[] = array(
						'contract' => $contract,
						'context'  => $context,
					);
					if ( $first === $contract->get_id() ) {
						// Another writer moves the second contract after the scan read it.
						Contracts::update( $second, array( 'next_payment_gmt' => '2026-02-14 12:00:00' ) );
					}
				},
			)
		);

		$this->assertSame( 1, $this->sut->run_batch( $this->now() ), 'Only the first contract is notified.' );
		$this->assertCount( 1, $this->calls );
		$this->assertSame( $first, $this->calls[0]['contract']->get_id() );
		$this->assertNull( $this->stored_claim( $second ), 'The lost claim wrote nothing.' );
	}

	/**
	 * @testdox A handler that leaves the due moment is re-notified only after the claim expires.
	 */
	public function test_an_idle_handler_is_renotified_after_the_claim_expires(): void {
		$this->register_recording_handler();
		$this->create_due_contract();

		$this->pin_db_clock( '2026-02-15 00:00:00' );
		$this->sut->run_batch( $this->now() );
		$this->pin_db_clock( '2026-02-15 00:14:59' );
		$this->sut->run_batch( $this->now() );
		$this->assertCount( 1, $this->calls, 'Not re-notified within the claim TTL.' );

		$this->pin_db_clock( '2026-02-15 00:15:00' );
		$this->sut->run_batch( $this->now() );
		$this->assertCount( 2, $this->calls, 'Re-notified once the claim expired on the database clock.' );
	}

	/**
	 * @testdox A handler that rewrites the due moment releases the claim and is notified on the next tick.
	 */
	public function test_moving_the_due_moment_releases_the_claim(): void {
		ConsumerRegistry::register(
			self::OWNER,
			array(
				'contract_due_handler' => function ( ContractView $contract, array $context ): void {
					$this->calls[] = array(
						'contract' => $contract,
						'context'  => $context,
					);
					Contracts::update( $contract->get_id(), array( 'next_payment_gmt' => '2026-02-16 00:00:00' ) );
				},
			)
		);
		$this->create_due_contract();

		$this->sut->run_batch( $this->now()->modify( '+1 day' ) );
		$this->sut->run_batch( $this->now()->modify( '+1 day +1 second' ) );

		$this->assertCount( 2, $this->calls, 'The new due moment is notified without waiting for the claim TTL.' );
		$this->assertEquals( new DateTimeImmutable( '2026-02-16 00:00:00', new DateTimeZone( 'UTC' ) ), $this->calls[1]['context']['due_at'] );
	}

	/**
	 * @testdox A handler that clears the due moment is never notified again.
	 */
	public function test_clearing_the_due_moment_stops_notifications(): void {
		ConsumerRegistry::register(
			self::OWNER,
			array(
				'contract_due_handler' => function ( ContractView $contract, array $context ): void {
					$this->calls[] = array(
						'contract' => $contract,
						'context'  => $context,
					);
					Contracts::update( $contract->get_id(), array( 'next_payment_gmt' => null ) );
				},
			)
		);
		$this->create_due_contract();

		$this->sut->run_batch( $this->now() );
		$this->sut->run_batch( $this->now()->modify( '+1 day' ) );

		$this->assertCount( 1, $this->calls );
	}

	/**
	 * @testdox A throwing handler is logged, keeps its claim and does not stop the batch.
	 */
	public function test_a_throwing_handler_is_logged_and_the_batch_continues(): void {
		$failing = $this->create_due_contract( '2026-02-14 00:00:00' );
		$healthy = $this->create_due_contract( '2026-02-15 00:00:00' );
		ConsumerRegistry::register(
			self::OWNER,
			array(
				'contract_due_handler' => function ( ContractView $contract, array $context ) use ( $failing ): void {
					if ( $failing === $contract->get_id() ) {
						throw new RuntimeException( 'Handler failure.' );
					}
					$this->calls[] = array(
						'contract' => $contract,
						'context'  => $context,
					);
				},
			)
		);

		$errors = $this->capture_engine_log(
			'error',
			array( 'contract_id' => $failing ),
			function (): void {
				$this->assertSame( 1, $this->sut->run_batch( $this->now() ), 'Only the healthy contract counts as notified.' );
			}
		);

		$this->assertNotSame( array(), $errors, 'The failure is logged with the contract id.' );
		$this->assertCount( 1, $this->calls );
		$this->assertSame( $healthy, $this->calls[0]['contract']->get_id() );
		$this->assertNotNull( $this->stored_claim( $failing ), 'The failing contract keeps its claim (back-off).' );
	}

	/**
	 * @testdox An owner registered without a handler is not dispatched and its contracts stay untouched.
	 */
	public function test_an_owner_without_a_handler_is_not_dispatched(): void {
		ConsumerRegistry::register( self::OWNER );
		$id = $this->create_due_contract();

		$this->assertSame( 0, $this->sut->run_batch( $this->now() ) );
		$this->assertNull( $this->stored_claim( $id ), 'No claim is taken on a contract nobody handles.' );
	}

	/**
	 * @testdox Contracts of an unregistered owner, or with no owner, are not dispatched.
	 */
	public function test_unregistered_and_missing_owners_are_not_dispatched(): void {
		global $wpdb;

		$this->register_recording_handler();
		$foreign  = $this->create_due_contract( '2026-02-15 00:00:00', array( 'extension_slug' => 'other-ext' ) );
		$no_owner = $this->create_due_contract();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS ), array( 'extension_slug' => null ), array( 'id' => $no_owner ) );

		$this->assertSame( 0, $this->sut->run_batch( $this->now() ) );
		$this->assertSame( array(), $this->calls );
		$this->assertNull( $this->stored_claim( $foreign ) );
	}

	/**
	 * @testdox A contract whose schedule a gateway or extension keeps is not dispatched.
	 */
	public function test_a_contract_with_another_schedule_source_is_not_dispatched(): void {
		$this->register_recording_handler();
		$id = $this->create_due_contract( '2026-02-15 00:00:00', array( 'schedule_source' => 'my_gateway' ) );

		$this->assertSame( 0, $this->sut->run_batch( $this->now() ) );
		$this->assertSame( array(), $this->calls );
		$this->assertNull( $this->stored_claim( $id ), 'No claim is taken on a contract the engine does not schedule.' );
	}

	/**
	 * @testdox The handler receives the contract view with items and the stored due moment in UTC.
	 */
	public function test_the_handler_receives_the_contract_view_and_due_at(): void {
		$this->register_recording_handler();
		$id = $this->create_due_contract();

		$this->sut->run_batch( new DateTimeImmutable( '2026-02-15 03:00:00', new DateTimeZone( 'Europe/Berlin' ) ) );

		$this->assertCount( 1, $this->calls );
		$contract = $this->calls[0]['contract'];
		$this->assertSame( $id, $contract->get_id() );
		$this->assertCount( 1, $contract->get_items() ?? array(), 'The view carries the contract items.' );

		$due_at = $this->calls[0]['context']['due_at'];
		$this->assertInstanceOf( DateTimeImmutable::class, $due_at );
		$this->assertSame( '2026-02-15 00:00:00', $due_at->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( 'UTC', $due_at->getTimezone()->getName() );
	}

	/**
	 * @testdox The dispatcher is status-blind: an on-hold contract with a due moment is notified.
	 */
	public function test_an_on_hold_contract_with_a_due_moment_is_notified(): void {
		$this->register_recording_handler();
		$this->create_due_contract( '2026-02-15 00:00:00', array( 'status' => ContractStatus::ON_HOLD ) );

		$this->assertSame( 1, $this->sut->run_batch( $this->now() ) );
		$this->assertSame( ContractStatus::ON_HOLD, $this->calls[0]['contract']->get_status() );
	}

	/**
	 * @testdox The batch limit caps notifications per tick and a non-positive limit is a no-op.
	 */
	public function test_batch_limit(): void {
		$this->register_recording_handler();
		$first = $this->create_due_contract( '2026-02-13 00:00:00' );
		$this->create_due_contract( '2026-02-14 00:00:00' );
		$this->create_due_contract( '2026-02-15 00:00:00' );

		$this->assertSame( 0, $this->sut->run_batch( $this->now(), 0 ) );
		$this->assertSame( 0, $this->sut->run_batch( $this->now(), -1 ) );
		$this->assertNull( $this->stored_claim( $first ), 'A no-op tick claims nothing.' );

		$this->assertSame( 1, $this->sut->run_batch( $this->now(), 1 ) );
		$this->assertSame( $first, $this->calls[0]['contract']->get_id(), 'The oldest-due contract goes first.' );
		$this->assertSame( 2, $this->sut->run_batch( $this->now() ) );
	}

	/**
	 * @testdox ensure_scheduled schedules only when a handler exists, is idempotent, and unschedules when the last handler-owner leaves.
	 */
	public function test_ensure_scheduled_follows_the_handler_owners(): void {
		delete_option( self::SCHEDULE_CHECK_OPTION );

		ConsumerRegistry::register( self::OWNER );
		DueDispatcher::ensure_scheduled();
		$this->assertFalse( as_next_scheduled_action( DueDispatcher::HOOK, array(), DueDispatcher::GROUP ), 'No scan without a handler.' );

		ConsumerRegistry::reset();
		$this->register_recording_handler();
		DueDispatcher::ensure_scheduled();
		$this->assertNotFalse( as_next_scheduled_action( DueDispatcher::HOOK, array(), DueDispatcher::GROUP ) );

		delete_option( self::SCHEDULE_CHECK_OPTION );
		DueDispatcher::ensure_scheduled();
		$this->assertCount( 1, as_get_scheduled_actions( array( 'hook' => DueDispatcher::HOOK, 'group' => DueDispatcher::GROUP, 'status' => 'pending' ), 'ids' ), 'A second call does not schedule a duplicate.' ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound

		ConsumerRegistry::reset();
		DueDispatcher::ensure_scheduled();
		$this->assertFalse( as_next_scheduled_action( DueDispatcher::HOOK, array(), DueDispatcher::GROUP ), 'The scan is removed once no owner has a handler.' );
		$this->assertFalse( get_option( self::SCHEDULE_CHECK_OPTION, false ) );
	}

	/**
	 * @testdox Scheduling the due scan removes the renewal scan action of earlier builds.
	 */
	public function test_scheduling_removes_the_earlier_renewal_scan_action(): void {
		delete_option( self::SCHEDULE_CHECK_OPTION );
		as_schedule_recurring_action( time() + 600, 600, 'woocommerce_subscriptions_engine_dispatch_due_renewals', array(), DueDispatcher::GROUP );
		update_option( 'woocommerce_subscriptions_engine_dispatch_scheduled_check', time() + 3600 );

		$this->register_recording_handler();
		DueDispatcher::ensure_scheduled();

		$this->assertFalse( as_next_scheduled_action( 'woocommerce_subscriptions_engine_dispatch_due_renewals', array(), DueDispatcher::GROUP ) );
		$this->assertFalse( get_option( 'woocommerce_subscriptions_engine_dispatch_scheduled_check', false ) );
	}

	/**
	 * @testdox handle_tick runs a batch at the current moment.
	 */
	public function test_handle_tick_routes_to_run_batch(): void {
		$this->register_recording_handler();
		$this->create_due_contract();

		$this->sut->register_hooks();
		do_action( DueDispatcher::HOOK ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertCount( 1, $this->calls );
	}

	/**
	 * The scan moment the fixtures are due against.
	 */
	private function now(): DateTimeImmutable {
		return new DateTimeImmutable( '2026-02-15 00:00:00', new DateTimeZone( 'UTC' ) );
	}

	/**
	 * Register the test owner with a handler that records each call.
	 */
	private function register_recording_handler(): void {
		ConsumerRegistry::register(
			self::OWNER,
			array(
				'contract_due_handler' => function ( ContractView $contract, array $context ): void {
					$this->calls[] = array(
						'contract' => $contract,
						'context'  => $context,
					);
				},
			)
		);
	}

	/**
	 * Create an active contract with one item, due at `$next_payment_gmt`.
	 *
	 * @param string               $next_payment_gmt Due moment (UTC).
	 * @param array<string, mixed> $overrides        `Contracts::create()` fields to replace.
	 * @return int The contract id.
	 */
	private function create_due_contract( string $next_payment_gmt = '2026-02-15 00:00:00', array $overrides = array() ): int {
		$contract = Contracts::create(
			array_merge(
				array(
					'extension_slug'   => self::OWNER,
					'status'           => ContractStatus::ACTIVE,
					'customer_id'      => 1,
					'currency'         => 'USD',
					'start_gmt'        => '2026-01-15 00:00:00',
					'next_payment_gmt' => $next_payment_gmt,
					'billing_total'    => '19.99',
					'items'            => array(
						array(
							'item_name'  => 'Coffee bag',
							'item_type'  => 'line_item',
							'product_id' => 200,
							'quantity'   => '1',
							'subtotal'   => '19.99',
							'total'      => '19.99',
						),
					),
				),
				$overrides
			)
		);

		return $contract->get_id();
	}

	/**
	 * The stored due-dispatch claim of a contract.
	 *
	 * @param int $id Contract id.
	 */
	private function stored_claim( int $id ): ?string {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$claim = $wpdb->get_var( $wpdb->prepare( "SELECT dispatch_claimed_until FROM {$table} WHERE id = %d", $id ) );

		return null === $claim ? null : (string) $claim;
	}
}
