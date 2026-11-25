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
