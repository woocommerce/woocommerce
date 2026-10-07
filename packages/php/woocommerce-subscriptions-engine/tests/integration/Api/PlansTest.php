<?php
/**
 * Integration tests for the Plans write facade.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api;

use EngineIntegrationTestCase;
use InvalidArgumentException;
use RuntimeException;
use WP_Error;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Plans;
use Automattic\WooCommerce\SubscriptionsEngine\Api\PlanValidationException;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\PlanView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\StatusRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\Plans
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\PlanValidationException
 */
class PlansTest extends EngineIntegrationTestCase {

	private const OWNER = 'acme-subs';

	private const HOOK = 'woocommerce_subscriptions_engine_validate_plan';

	public function tear_down(): void {
		remove_all_actions( self::HOOK );
		StatusRegistry::reset();
		parent::tear_down();
	}

	/**
	 * Create a plan with defaults.
	 *
	 * @param array<string, mixed> $overrides Arg overrides.
	 */
	private function create( array $overrides = array() ): int {
		return Plans::create(
			array_merge(
				array(
					'extension_slug' => self::OWNER,
					'name'           => 'Monthly',
					'billing_policy' => array(
						'period'   => 'month',
						'interval' => 1,
					),
				),
				$overrides
			)
		);
	}

	/**
	 * Load a stored plan.
	 *
	 * @param int $id Plan id.
	 */
	private function stored( int $id ): Plan {
		$plan = ( new PlanRepository() )->find( $id );
		$this->assertInstanceOf( Plan::class, $plan );

		return $plan;
	}

	/**
	 * Number of stored plans.
	 */
	private function plan_count(): int {
		return ( new PlanRepository() )->count();
	}

	public function test_create_stores_the_fields_and_returns_the_id(): void {
		$id = $this->create(
			array(
				'name'            => '  Box  ',
				'pricing_policy'  => array( 'policies' => array() ),
				'delivery_policy' => array( 'anchor' => 1 ),
			)
		);

		$plan = $this->stored( $id );
		$this->assertSame( 'Box', $plan->get_name() );
		$this->assertSame( self::OWNER, $plan->get_extension_slug() );
		$this->assertSame( PlanStatus::ACTIVE, $plan->get_status() );
		$this->assertSame(
			array(
				'period'   => 'month',
				'interval' => 1,
			),
			$plan->get_billing_policy()
		);
		$this->assertSame( array( 'policies' => array() ), $plan->get_pricing_policy() );
		$this->assertSame( array( 'anchor' => 1 ), $plan->get_delivery_policy() );
	}

	public function test_create_without_policies_stores_nulls(): void {
		$plan = $this->stored(
			Plans::create(
				array(
					'extension_slug' => self::OWNER,
					'name'           => 'Bare',
				)
			)
		);

		$this->assertNull( $plan->get_billing_policy() );
		$this->assertNull( $plan->get_pricing_policy() );
		$this->assertNull( $plan->get_delivery_policy() );
	}

	public function test_create_accepts_a_registered_extension_status(): void {
		StatusRegistry::register( StatusRegistry::KIND_PLAN, 'seasonal' );

		$this->assertSame( 'seasonal', $this->stored( $this->create( array( 'status' => 'seasonal' ) ) )->get_status() );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public function provide_invalid_create_args(): array {
		return array(
			'unregistered status' => array( array( 'status' => 'seasonal' ) ),
			'non-string status'   => array( array( 'status' => 5 ) ),
			'empty name'          => array( array( 'name' => '' ) ),
			'whitespace name'     => array( array( 'name' => '   ' ) ),
			'non-string name'     => array( array( 'name' => 12 ) ),
			'list policy'         => array( array( 'pricing_policy' => array( 'a', 'b' ) ) ),
			'scalar policy'       => array( array( 'billing_policy' => 'monthly' ) ),
			'missing slug'        => array( array( 'extension_slug' => null ) ),
			'empty slug'          => array( array( 'extension_slug' => '' ) ),
		);
	}

	/**
	 * @dataProvider provide_invalid_create_args
	 *
	 * @param array<string, mixed> $overrides Invalid overrides.
	 */
	public function test_create_rejects_invalid_args_and_stores_nothing( array $overrides ): void {
		$before = $this->plan_count();

		try {
			$this->create( $overrides );
			$this->fail( 'Expected InvalidArgumentException.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertNotInstanceOf( PlanValidationException::class, $e );
		}

		$this->assertSame( $before, $this->plan_count() );
	}

	public function test_create_requires_a_name(): void {
		$this->expectException( InvalidArgumentException::class );

		Plans::create( array( 'extension_slug' => self::OWNER ) );
	}

	public function test_an_unknown_create_key_is_ignored_with_a_notice(): void {
		$this->setExpectedIncorrectUsage( Plans::class . '::create' );
		$messages = array();
		add_action(
			'doing_it_wrong_run',
			static function ( $function_name, $message ) use ( &$messages ): void {
				$messages[] = $message;
			},
			10,
			2
		);

		$id = $this->create( array( 'sort_order' => 1 ) );

		$this->assertSame( array( 'Unknown key "sort_order" ignored.' ), $messages );
		$this->assertSame( 'Monthly', $this->stored( $id )->get_name(), 'The known keys beside the unknown one are written.' );
	}

	public function test_update_replaces_a_policy_wholesale_and_null_clears(): void {
		$id = $this->create(
			array(
				'pricing_policy' => array(
					'a' => 1,
					'b' => 2,
				),
			)
		);

		$this->assertTrue( Plans::update( $id, array( 'pricing_policy' => array( 'c' => 3 ) ) ) );
		$plan = $this->stored( $id );
		$this->assertSame( array( 'c' => 3 ), $plan->get_pricing_policy() );
		$this->assertSame(
			array(
				'period'   => 'month',
				'interval' => 1,
			),
			$plan->get_billing_policy(),
			'An omitted policy keeps its stored payload.'
		);

		$this->assertTrue( Plans::update( $id, array( 'pricing_policy' => null ) ) );
		$this->assertNull( $this->stored( $id )->get_pricing_policy() );
	}

	public function test_status_only_update(): void {
		$id = $this->create();

		$this->assertTrue( Plans::update( $id, array( 'status' => PlanStatus::ARCHIVED ) ) );

		$plan = $this->stored( $id );
		$this->assertSame( PlanStatus::ARCHIVED, $plan->get_status() );
		$this->assertSame( 'Monthly', $plan->get_name() );
	}

	public function test_update_of_a_missing_plan_returns_false(): void {
		$this->assertFalse( Plans::update( 999999, array( 'name' => 'Nope' ) ) );
	}

	public function test_update_rejects_a_non_positive_id(): void {
		$this->expectException( InvalidArgumentException::class );

		Plans::update( 0, array( 'name' => 'Nope' ) );
	}

	public function test_an_unknown_update_key_is_ignored_with_a_notice(): void {
		$id = $this->create();
		$this->setExpectedIncorrectUsage( Plans::class . '::update' );

		$this->assertTrue(
			Plans::update(
				$id,
				array(
					'sort_order' => 1,
					'name'       => 'Changed',
				)
			)
		);

		$this->assertSame( 'Changed', $this->stored( $id )->get_name(), 'The known key beside the unknown one is written.' );
	}

	public function test_extension_slug_is_not_an_update_key(): void {
		$id = $this->create();
		$this->setExpectedIncorrectUsage( Plans::class . '::update' );

		$this->assertTrue(
			Plans::update(
				$id,
				array(
					'extension_slug' => 'other',
					'name'           => 'Changed',
				)
			)
		);

		$plan = $this->stored( $id );
		$this->assertSame( self::OWNER, $plan->get_extension_slug(), 'The extension slug is not rewritten.' );
		$this->assertSame( 'Changed', $plan->get_name() );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public function provide_invalid_update_args(): array {
		return array(
			'unregistered status' => array( array( 'status' => 'seasonal' ) ),
			'non-string status'   => array( array( 'status' => 5 ) ),
			'empty name'          => array( array( 'name' => '' ) ),
			'whitespace name'     => array( array( 'name' => '   ' ) ),
			'non-string name'     => array( array( 'name' => 12 ) ),
			'list policy'         => array( array( 'pricing_policy' => array( 'a', 'b' ) ) ),
			'scalar policy'       => array( array( 'billing_policy' => 'monthly' ) ),
			'valid then invalid'  => array(
				array(
					'name'   => 'Changed',
					'status' => 'seasonal',
				),
			),
		);
	}

	/**
	 * @dataProvider provide_invalid_update_args
	 *
	 * @param array<string, mixed> $args Invalid update args.
	 */
	public function test_update_rejects_invalid_args_and_leaves_the_plan_unchanged( array $args ): void {
		$id     = $this->create();
		$before = $this->stored( $id )->to_storage();

		try {
			Plans::update( $id, $args );
			$this->fail( 'Expected InvalidArgumentException.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertNotInstanceOf( PlanValidationException::class, $e );
		}

		$this->assertSame( $before, $this->stored( $id )->to_storage() );
	}

	public function test_update_accepts_the_stored_status_after_it_is_unregistered(): void {
		StatusRegistry::register( StatusRegistry::KIND_PLAN, 'seasonal' );
		$id = $this->create( array( 'status' => 'seasonal' ) );
		StatusRegistry::reset();
		$this->assertFalse( PlanStatus::is_registered( 'seasonal' ) );

		$this->assertTrue(
			Plans::update(
				$id,
				array(
					'status' => 'seasonal',
					'name'   => 'Renamed',
				)
			)
		);

		$plan = $this->stored( $id );
		$this->assertSame( 'seasonal', $plan->get_status() );
		$this->assertSame( 'Renamed', $plan->get_name() );
	}

	public function test_update_writes_only_the_present_fields(): void {
		global $wpdb;

		$id = $this->create();

		// A concurrent writer renames the plan while the owner validates a status change.
		add_action(
			self::HOOK,
			static function () use ( $wpdb, $id ): void {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS ), array( 'name' => 'Renamed elsewhere' ), array( 'id' => $id ) );
			}
		);

		$this->assertTrue( Plans::update( $id, array( 'status' => PlanStatus::ARCHIVED ) ) );

		$plan = $this->stored( $id );
		$this->assertSame( PlanStatus::ARCHIVED, $plan->get_status() );
		$this->assertSame( 'Renamed elsewhere', $plan->get_name(), 'The status-only update must not write the name it read.' );
	}

	public function test_update_with_no_fields_validates_and_writes_nothing(): void {
		global $wpdb;

		$id    = $this->create();
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET date_updated_gmt = %s WHERE id = %d", '2020-01-01 00:00:00', $id ) );

		$validated = 0;
		add_action(
			self::HOOK,
			static function () use ( &$validated ): void {
				++$validated;
			}
		);

		$this->assertTrue( Plans::update( $id, array() ) );
		$this->assertSame( 1, $validated, 'An empty update still runs the owner validation.' );
		$this->assertSame( '2020-01-01 00:00:00', $this->stored( $id )->get_date_updated_gmt(), 'An empty update writes nothing.' );

		$this->assertTrue( Plans::update( $id, array( 'name' => 'Renamed' ) ) );
		$this->assertNotSame( '2020-01-01 00:00:00', $this->stored( $id )->get_date_updated_gmt(), 'A field update bumps the update time.' );
	}

	public function test_validate_action_receives_the_would_be_state_on_create(): void {
		$seen = array();
		add_action(
			self::HOOK,
			static function ( $errors, $plan, $owner ) use ( &$seen ): void {
				$seen[] = array( $errors, $plan, $owner );
			},
			10,
			3
		);

		$this->create( array( 'name' => 'Seen' ) );

		$this->assertCount( 1, $seen );
		$this->assertInstanceOf( WP_Error::class, $seen[0][0] );
		$this->assertInstanceOf( PlanView::class, $seen[0][1] );
		$this->assertSame( 0, $seen[0][1]->get_id() );
		$this->assertSame( 'Seen', $seen[0][1]->get_name() );
		$this->assertSame( self::OWNER, $seen[0][1]->get_extension_slug() );
		$this->assertSame( self::OWNER, $seen[0][2] );
	}

	public function test_validate_action_receives_the_would_be_state_on_update(): void {
		$id   = $this->create();
		$seen = null;
		add_action(
			self::HOOK,
			static function ( $errors, $plan ) use ( &$seen ): void {
				unset( $errors );
				$seen = $plan;
			},
			10,
			2
		);

		Plans::update(
			$id,
			array(
				'name'           => 'Renamed',
				'pricing_policy' => array( 'x' => 1 ),
			)
		);

		$this->assertInstanceOf( PlanView::class, $seen );
		$this->assertSame( $id, $seen->get_id() );
		$this->assertSame( 'Renamed', $seen->get_name() );
		$this->assertSame( array( 'x' => 1 ), $seen->get_pricing_policy() );
	}

	public function test_an_added_error_refuses_the_create_with_its_codes(): void {
		$before = $this->plan_count();
		add_action(
			self::HOOK,
			static function ( $errors ): void {
				$errors->add( 'acme_bad_pricing', 'Pricing is wrong.', array( 'status' => 400 ) );
			}
		);
		add_action(
			self::HOOK,
			static function ( $errors ): void {
				$errors->add( 'acme_bad_billing', 'Billing is wrong.' );
			}
		);

		try {
			$this->create();
			$this->fail( 'Expected PlanValidationException.' );
		} catch ( PlanValidationException $e ) {
			$this->assertSame( array( 'acme_bad_pricing', 'acme_bad_billing' ), $e->get_errors()->get_error_codes() );
			$this->assertSame( array( 'status' => 400 ), $e->get_errors()->get_error_data( 'acme_bad_pricing' ) );
			$this->assertSame( 'Pricing is wrong. Billing is wrong.', $e->getMessage() );
		}

		$this->assertSame( $before, $this->plan_count() );
	}

	public function test_an_added_error_refuses_the_update(): void {
		$id = $this->create();
		add_action(
			self::HOOK,
			static function ( $errors ): void {
				$errors->add( 'acme_no', 'No.' );
			}
		);

		try {
			Plans::update( $id, array( 'name' => 'Refused' ) );
			$this->fail( 'Expected PlanValidationException.' );
		} catch ( PlanValidationException $e ) {
			$this->assertSame( array( 'acme_no' ), $e->get_errors()->get_error_codes() );
		}

		$this->assertSame( 'Monthly', $this->stored( $id )->get_name() );
	}

	public function test_a_throwing_callback_throws_a_runtime_exception_and_stores_nothing(): void {
		$before = $this->plan_count();
		add_action(
			self::HOOK,
			static function (): void {
				throw new \LogicException( 'boom' );
			}
		);

		try {
			$this->create();
			$this->fail( 'Expected RuntimeException.' );
		} catch ( RuntimeException $e ) {
			$this->assertInstanceOf( \LogicException::class, $e->getPrevious() );
		}

		$this->assertSame( $before, $this->plan_count() );
	}

	public function test_meta_methods_round_trip(): void {
		$id = $this->create();

		$this->assertIsInt( Plans::add_meta( $id, 'note', 'one' ) );
		$this->assertNull( Plans::add_meta( $id, 'note', 'two', true ) );
		$this->assertTrue( Plans::update_meta( $id, 'flag', array( 'a' => 1 ) ) );
		$this->assertSame( array( 'one' ), Plans::get_meta( $id, 'note' ) );
		$this->assertSame( array( 'a' => 1 ), Plans::get_meta( $id, 'flag', true ) );
		$this->assertSame(
			array(
				'note' => array( 'one' ),
				'flag' => array( array( 'a' => 1 ) ),
			),
			Plans::get_meta( $id )
		);
		$this->assertTrue( Plans::delete_meta( $id, 'note' ) );
		$this->assertSame( '', Plans::get_meta( $id, 'note', true ) );
	}

	public function test_meta_writes_on_a_missing_plan_return_null_or_false(): void {
		$this->assertNull( Plans::add_meta( 999999, 'note', 'x' ) );
		$this->assertFalse( Plans::update_meta( 999999, 'note', 'x' ) );
		$this->assertFalse( Plans::delete_meta( 999999, 'note' ) );
		$this->assertSame( array(), Plans::get_meta( 999999 ) );
	}

	public function test_an_empty_meta_key_is_rejected(): void {
		$this->expectException( InvalidArgumentException::class );

		Plans::add_meta( $this->create(), '', 'x' );
	}
}
