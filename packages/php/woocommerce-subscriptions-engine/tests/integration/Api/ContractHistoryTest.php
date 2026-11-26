<?php
/**
 * Integration tests for the contract history methods of the Contracts facade.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api;

use EngineIntegrationTestCase;
use InvalidArgumentException;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\HistoryEntryView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ArgumentValidator;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;
use Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\AssertsValidationErrors;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts
 */
class ContractHistoryTest extends EngineIntegrationTestCase {

	use AssertsValidationErrors;

	private const EXTENSION_SLUG = 'acme-subs';

	/**
	 * A stored contract id of {@see self::EXTENSION_SLUG}.
	 *
	 * @var int
	 */
	private $id;

	public function setUp(): void {
		parent::setUp();
		$this->id = $this->create_released( array( 'extension_slug' => self::EXTENSION_SLUG ) )->get_id();
	}

	/**
	 * @testdox add_history_entry() with the caller's lock returns a view that matches a fresh read.
	 */
	public function test_a_locked_add_returns_the_entry(): void {
		$cycle = $this->add_cycle_locked(
			$this->id,
			array(
				'starts_at_gmt' => '2026-07-15 00:00:00',
				'ends_at_gmt'   => '2026-08-15 00:00:00',
				'currency'      => 'USD',
			)
		);
		$this->assertNotNull( $cycle );
		$lock = $this->take_lock( $this->id, 'renew' );

		$view = Contracts::add_history_entry(
			$this->id,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'type'           => 'renewal-paid',
				'cycle_id'       => (string) $cycle->get_id(),
				'payload'        => array( 'actor' => array( 'role' => 'system' ) ),
				'creation_key'   => 'renewal-paid-' . $cycle->get_id(),
			),
			$lock
		);

		$this->assertInstanceOf( HistoryEntryView::class, $view );
		$this->assertSame( $this->id, $view->get_contract_id() );
		$this->assertSame( $cycle->get_id(), $view->get_cycle_id(), 'A digit-string id is cast.' );
		$this->assertSame( 'renewal-paid', $view->get_type() );
		$this->assertSame( array( 'actor' => array( 'role' => 'system' ) ), $view->get_payload() );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $view->get_date_created_gmt() );
		$this->assertEquals( array( $view ), Contracts::get_history( $this->id ) );
	}

	/**
	 * @testdox add_history_entry() without a lock lands while another operation holds the contract's lock.
	 */
	public function test_an_unlocked_add_is_not_blocked_by_a_held_lock(): void {
		$this->take_lock( $this->id, 'renew' );

		$view = Contracts::add_history_entry(
			$this->id,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'type'           => 'note',
				'payload'        => array( 'text' => 'Called the customer.' ),
			)
		);

		$this->assertInstanceOf( HistoryEntryView::class, $view );
		$this->assertSame( 'renew', $this->get_lock_holder( $this->id )['operation'] );
	}

	/**
	 * @testdox add_history_entry() reports every shape error at once and writes nothing.
	 */
	public function test_every_shape_error_is_reported_at_once(): void {
		$this->assert_validation_errors(
			function (): void {
				Contracts::add_history_entry(
					$this->id,
					array(
						'extension_slug' => 'Acme',
						'type'           => 'Renewal Paid',
						'cycle_id'       => 'x',
						'payload'        => 'text',
						'creation_key'   => 5,
					)
				);
			},
			array(
				array( ValidationErrorCode::INVALID_PATTERN, 'extension_slug', array( 'pattern' => ArgumentValidator::SLUG_PATTERN ) ),
				array( ValidationErrorCode::INVALID_PATTERN, 'type', array( 'pattern' => ArgumentValidator::SLUG_PATTERN ) ),
				array( ValidationErrorCode::INVALID_TYPE, 'cycle_id', array( 'type' => 'integer' ) ),
				array( ValidationErrorCode::INVALID_TYPE, 'payload', array( 'type' => 'object' ) ),
				array( ValidationErrorCode::INVALID_TYPE, 'creation_key', array( 'type' => 'string' ) ),
			)
		);

		$this->assertSame( array(), Contracts::get_history( $this->id ) );
	}

	/**
	 * @testdox add_history_entry() reports missing required keys and an out-of-bounds cycle id.
	 */
	public function test_missing_required_keys_are_reported(): void {
		$this->assert_validation_errors(
			function (): void {
				Contracts::add_history_entry( $this->id, array( 'cycle_id' => 0 ) );
			},
			array(
				array( ValidationErrorCode::REQUIRED, 'extension_slug' ),
				array( ValidationErrorCode::REQUIRED, 'type' ),
				array( ValidationErrorCode::OUT_OF_BOUNDS, 'cycle_id', array( 'min' => 1 ) ),
			)
		);
	}

	/**
	 * @testdox add_history_entry() reports the entity's errors together: a blank creation key and a payload that cannot be JSON-encoded.
	 */
	public function test_entity_errors_are_reported_together(): void {
		$this->assert_validation_errors(
			function (): void {
				Contracts::add_history_entry(
					$this->id,
					array(
						'extension_slug' => self::EXTENSION_SLUG,
						'type'           => 'note',
						'payload'        => array( 'text' => "\xB1\x31" ),
						'creation_key'   => '  ',
					)
				);
			},
			array(
				array( ValidationErrorCode::INVALID_VALUE, 'creation_key' ),
				array( ValidationErrorCode::INVALID_VALUE, 'payload' ),
			)
		);

		$this->assertSame( array(), Contracts::get_history( $this->id ) );
	}

	/**
	 * @testdox add_history_entry() ignores unknown keys with a notice and stores the known ones.
	 */
	public function test_unknown_keys_are_ignored_with_a_notice(): void {
		$this->setExpectedIncorrectUsage( Contracts::class . '::add_history_entry' );

		$view = Contracts::add_history_entry(
			$this->id,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'type'           => 'note',
				'actor'          => 'merchant',
			)
		);

		$this->assertInstanceOf( HistoryEntryView::class, $view );
		$this->assertSame( 'note', $view->get_type() );
		$this->assertNull( $view->get_payload(), 'The unknown key is not stored.' );
	}

	/**
	 * @testdox add_history_entry() returns null for a taken creation key.
	 */
	public function test_a_taken_creation_key_returns_null(): void {
		$args = array(
			'extension_slug' => self::EXTENSION_SLUG,
			'type'           => 'created',
			'creation_key'   => 'created',
		);

		$this->assertInstanceOf( HistoryEntryView::class, Contracts::add_history_entry( $this->id, $args ) );
		$this->assertNull( Contracts::add_history_entry( $this->id, $args ) );
		$this->assertCount( 1, Contracts::get_history( $this->id ) );
	}

	/**
	 * @testdox add_history_entry() refuses a contract of another extension and a lock of another extension.
	 */
	public function test_another_extensions_contract_or_lock_is_refused(): void {
		$other = $this->create_released( array( 'extension_slug' => 'another-extension' ) )->get_id();
		$args  = array(
			'extension_slug' => self::EXTENSION_SLUG,
			'type'           => 'held',
		);

		try {
			Contracts::add_history_entry( $other, $args );
			$this->fail( 'Expected a contract of another extension to be refused.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertStringContainsString( "has id {$other}", $e->getMessage() );
		}

		$lock = $this->take_lock( $other, 'hold' );
		$this->expectException( InvalidArgumentException::class );
		Contracts::add_history_entry( $other, $args, $lock );
	}

	/**
	 * @testdox get_history() pages newest first with limit and offset.
	 */
	public function test_get_history_pages_newest_first(): void {
		foreach ( array( 'a', 'b', 'c' ) as $type ) {
			Contracts::add_history_entry(
				$this->id,
				array(
					'extension_slug' => self::EXTENSION_SLUG,
					'type'           => $type,
				)
			);
		}

		$this->assertSame( array( 'c', 'b', 'a' ), $this->types( Contracts::get_history( $this->id ) ) );
		$page = Contracts::get_history(
			$this->id,
			array(
				'limit'  => 1,
				'offset' => '1',
			)
		);
		$this->assertSame( array( 'b' ), $this->types( $page ) );
		$this->assertSame( array(), Contracts::get_history( 999999 ) );
	}

	/**
	 * @testdox get_history() takes no owner: any caller reads the history of another extension's contract.
	 */
	public function test_get_history_reads_another_extensions_contract(): void {
		$other = $this->create_released( array( 'extension_slug' => 'another-extension' ) )->get_id();
		Contracts::add_history_entry(
			$other,
			array(
				'extension_slug' => 'another-extension',
				'type'           => 'note',
			)
		);
		Contracts::add_history_entry(
			$this->id,
			array(
				'extension_slug' => self::EXTENSION_SLUG,
				'type'           => 'held',
			)
		);

		$this->assertSame( array( 'note' ), $this->types( Contracts::get_history( $other ) ) );
		$this->assertSame( array( 'held' ), $this->types( Contracts::get_history( $this->id ) ), 'Each contract reads only its own entries.' );
	}

	/**
	 * @testdox get_history() reports a negative limit and offset together.
	 */
	public function test_get_history_reports_invalid_paging(): void {
		$this->assert_validation_errors(
			function (): void {
				Contracts::get_history(
					$this->id,
					array(
						'limit'  => -1,
						'offset' => -2,
					)
				);
			},
			array(
				array( ValidationErrorCode::OUT_OF_BOUNDS, 'limit', array( 'min' => 0 ) ),
				array( ValidationErrorCode::OUT_OF_BOUNDS, 'offset', array( 'min' => 0 ) ),
			)
		);
	}

	/**
	 * @testdox get_history() ignores unknown keys with a notice.
	 */
	public function test_get_history_ignores_unknown_keys(): void {
		$this->setExpectedIncorrectUsage( Contracts::class . '::get_history' );

		$this->assertSame( array(), Contracts::get_history( $this->id, array( 'per_page' => 5 ) ) );
	}

	/**
	 * @testdox delete_history() deletes every entry of the contract under the caller's lock and returns the count.
	 */
	public function test_delete_history_deletes_under_the_lock(): void {
		$other = $this->create_released( array( 'extension_slug' => self::EXTENSION_SLUG ) )->get_id();
		$this->add_entries( $this->id, array( 'a', 'b' ) );
		$this->add_entries( $other, array( 'c' ) );
		$lock = $this->take_lock( $this->id, 'erase' );

		$this->assertSame( 2, Contracts::delete_history( $this->id, array( 'extension_slug' => self::EXTENSION_SLUG ), $lock ) );
		$this->assertSame( array(), Contracts::get_history( $this->id ) );
		$this->assertSame( array( 'c' ), $this->types( Contracts::get_history( $other ) ) );
		$this->assertSame( 0, Contracts::delete_history( $this->id, array( 'extension_slug' => self::EXTENSION_SLUG ), $lock ), 'A second delete finds nothing.' );
		$this->assertSame( 'erase', $this->get_lock_holder( $this->id )['operation'], 'The caller keeps its lock.' );
	}

	/**
	 * @testdox delete_history() refuses a lock for another contract or for another extension and deletes nothing.
	 */
	public function test_delete_history_refuses_a_lock_for_another_contract_or_extension(): void {
		$other   = $this->create_released( array( 'extension_slug' => self::EXTENSION_SLUG ) )->get_id();
		$foreign = $this->create_released( array( 'extension_slug' => 'another-extension' ) )->get_id();
		$this->add_entries( $this->id, array( 'a' ) );
		$this->add_entries( $foreign, array( 'b' ), 'another-extension' );
		$args = array( 'extension_slug' => self::EXTENSION_SLUG );

		try {
			Contracts::delete_history( $this->id, $args, $this->take_lock( $other, 'erase' ) );
			$this->fail( 'Expected a lock for another contract to be refused.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertStringContainsString( "not contract {$this->id}", $e->getMessage() );
		}

		try {
			Contracts::delete_history( $foreign, $args, $this->take_lock( $foreign, 'erase' ) );
			$this->fail( 'Expected a lock of another extension to be refused.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertStringContainsString( 'another extension', $e->getMessage() );
		}

		$this->assertCount( 1, Contracts::get_history( $this->id ) );
		$this->assertCount( 1, Contracts::get_history( $foreign ) );
	}

	/**
	 * @testdox delete_history() reports a missing, ill-typed or malformed extension_slug and deletes nothing.
	 * @dataProvider provide_invalid_delete_owners
	 *
	 * @param array<string, mixed> $args     Arguments.
	 * @param array<int, mixed>    $expected Expected error.
	 */
	public function test_delete_history_reports_an_invalid_owner( array $args, array $expected ): void {
		$this->add_entries( $this->id, array( 'a' ) );
		$lock = $this->take_lock( $this->id, 'erase' );

		$this->assert_validation_errors(
			function () use ( $args, $lock ): void {
				Contracts::delete_history( $this->id, $args, $lock );
			},
			array( $expected )
		);

		$this->assertCount( 1, Contracts::get_history( $this->id ) );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>, 1: array<int, mixed>}>
	 */
	public function provide_invalid_delete_owners(): array {
		return array(
			'missing'   => array( array(), array( ValidationErrorCode::REQUIRED, 'extension_slug' ) ),
			'not text'  => array( array( 'extension_slug' => 5 ), array( ValidationErrorCode::INVALID_TYPE, 'extension_slug', array( 'type' => 'string' ) ) ),
			'malformed' => array( array( 'extension_slug' => 'Acme Subs' ), array( ValidationErrorCode::INVALID_PATTERN, 'extension_slug', array( 'pattern' => ArgumentValidator::SLUG_PATTERN ) ) ),
		);
	}

	/**
	 * @testdox delete_history() ignores unknown keys with a notice.
	 */
	public function test_delete_history_ignores_unknown_keys(): void {
		$this->setExpectedIncorrectUsage( Contracts::class . '::delete_history' );
		$this->add_entries( $this->id, array( 'a', 'b' ) );
		$args = array(
			'extension_slug' => self::EXTENSION_SLUG,
			'type'           => 'a',
		);

		$this->assertSame( 2, Contracts::delete_history( $this->id, $args, $this->take_lock( $this->id, 'erase' ) ), 'The unknown type filter is ignored.' );
	}

	/**
	 * Add entries of the given types to a contract, without a lock.
	 *
	 * @param int                $contract_id    Contract id.
	 * @param array<int, string> $types          Entry types, oldest first.
	 * @param string             $extension_slug The contract's owner.
	 */
	private function add_entries( int $contract_id, array $types, string $extension_slug = self::EXTENSION_SLUG ): void {
		foreach ( $types as $type ) {
			$view = Contracts::add_history_entry(
				$contract_id,
				array(
					'extension_slug' => $extension_slug,
					'type'           => $type,
				)
			);
			$this->assertNotNull( $view );
		}
	}

	/**
	 * The types of entry views, in order.
	 *
	 * @param array<int, HistoryEntryView> $views Views.
	 * @return array<int, string>
	 */
	private function types( array $views ): array {
		return array_map(
			static function ( HistoryEntryView $view ): string {
				return $view->get_type();
			},
			$views
		);
	}
}
