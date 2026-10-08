<?php
/**
 * Integration tests for the Plans facade.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api;

use DomainException;
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
		$plan = Plans::create(
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

		return $plan->get_id();
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

	/**
	 * @testdox create stores the fields and returns the view.
	 */
	public function test_create_stores_the_fields_and_returns_the_view(): void {
		$created = Plans::create(
			array(
				'extension_slug'  => self::OWNER,
				'name'            => '  Box  ',
				'billing_policy'  => array(
					'period'   => 'month',
					'interval' => 1,
				),
				'pricing_policy'  => array( 'policies' => array() ),
				'delivery_policy' => array( 'anchor' => 1 ),
			)
		);
		$id      = $created->get_id();

		$this->assertEquals( Plans::get( $id ), $created, 'The returned view matches a fresh read.' );
		$this->assertNotNull( $created->get_date_created_gmt() );

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

	/**
	 * @testdox create without policies stores nulls.
	 */
	public function test_create_without_policies_stores_nulls(): void {
		$created = Plans::create(
			array(
				'extension_slug' => self::OWNER,
				'name'           => 'Bare',
			)
		);
		$plan    = $this->stored( $created->get_id() );

		$this->assertNull( $plan->get_billing_policy() );
		$this->assertNull( $plan->get_pricing_policy() );
		$this->assertNull( $plan->get_delivery_policy() );
	}

	/**
	 * @testdox create accepts a registered extension status.
	 */
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
	 * @testdox create rejects invalid args and stores nothing.
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

	/**
	 * @testdox an entity invariant failure is reported as invalid input, on create and on update.
	 */
	public function test_an_entity_invariant_failure_is_reported_as_invalid_input(): void {
		try {
			$this->create( array( 'status' => 'nonsense' ) );
			$this->fail( 'Expected an InvalidArgumentException.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertInstanceOf( DomainException::class, $e->getPrevious() );
			$this->assertSame( $e->getPrevious()->getMessage(), $e->getMessage() );
		}

		try {
			Plans::update(
				$this->create(),
				array(
					'extension_slug' => self::OWNER,
					'name'           => ' ',
				)
			);
			$this->fail( 'Expected an InvalidArgumentException.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertInstanceOf( DomainException::class, $e->getPrevious() );
		}
	}

	/**
	 * @testdox create requires a name.
	 */
	public function test_create_requires_a_name(): void {
		$this->expectException( InvalidArgumentException::class );

		Plans::create( array( 'extension_slug' => self::OWNER ) );
	}

	/**
	 * @testdox an unknown create key is ignored with a notice.
	 */
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

	/**
	 * @testdox update replaces a policy wholesale and null clears.
	 */
	public function test_update_replaces_a_policy_wholesale_and_null_clears(): void {
		$id = $this->create(
			array(
				'pricing_policy' => array(
					'a' => 1,
					'b' => 2,
				),
			)
		);

		$this->assertInstanceOf(
			PlanView::class,
			Plans::update(
				$id,
				array(
					'extension_slug' => self::OWNER,
					'pricing_policy' => array( 'c' => 3 ),
				)
			)
		);
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

		$this->assertInstanceOf(
			PlanView::class,
			Plans::update(
				$id,
				array(
					'extension_slug' => self::OWNER,
					'pricing_policy' => null,
				)
			)
		);
		$this->assertNull( $this->stored( $id )->get_pricing_policy() );
	}

	/**
	 * @testdox a status-only update writes the status and keeps the name.
	 */
	public function test_status_only_update(): void {
		$id = $this->create();

		$this->assertInstanceOf(
			PlanView::class,
			Plans::update(
				$id,
				array(
					'extension_slug' => self::OWNER,
					'status'         => PlanStatus::ARCHIVED,
				)
			)
		);

		$plan = $this->stored( $id );
		$this->assertSame( PlanStatus::ARCHIVED, $plan->get_status() );
		$this->assertSame( 'Monthly', $plan->get_name() );
	}

	/**
	 * @testdox update of a missing plan returns null.
	 */
	public function test_update_of_a_missing_plan_returns_null(): void {
		$this->assertNull(
			Plans::update(
				999999,
				array(
					'extension_slug' => self::OWNER,
					'name'           => 'Nope',
				)
			)
		);
		$this->assertNull(
			Plans::update(
				0,
				array(
					'extension_slug' => self::OWNER,
					'name'           => 'Nope',
				)
			)
		);
	}

	/**
	 * @testdox update returns the plan as read plus the written fields, matching a fresh read (stored update time included).
	 */
	public function test_update_returns_the_view_with_the_written_fields(): void {
		$id = $this->create();

		$updated = Plans::update(
			$id,
			array(
				'extension_slug' => self::OWNER,
				'name'           => 'Renamed',
			)
		);

		$this->assertInstanceOf( PlanView::class, $updated );
		$this->assertSame( 'Renamed', $updated->get_name() );
		$this->assertEquals( Plans::get( $id ), $updated, 'The returned view matches a fresh read.' );
	}

	/**
	 * @testdox update returns null when the plan is deleted before the write.
	 */
	public function test_update_returns_null_when_the_plan_is_deleted_before_the_write(): void {
		global $wpdb;

		$id = $this->create();

		// A concurrent delete lands after the facade read the plan and before its write.
		$table    = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );
		$injected = false;
		$race     = static function ( string $query ) use ( &$injected, $table, $id, $wpdb ): string {
			if ( ! $injected && 0 === stripos( ltrim( $query ), 'UPDATE' ) && false !== strpos( $query, $table ) ) {
				$injected = true;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( $table, array( 'id' => $id ) );
			}

			return $query;
		};
		add_filter( 'query', $race );

		try {
			$updated = Plans::update(
				$id,
				array(
					'extension_slug' => self::OWNER,
					'name'           => 'Renamed',
				)
			);
		} finally {
			remove_filter( 'query', $race );
		}

		$this->assertTrue( $injected );
		$this->assertNull( $updated );
	}

	/**
	 * @testdox an unknown update key is ignored with a notice.
	 */
	public function test_an_unknown_update_key_is_ignored_with_a_notice(): void {
		$id = $this->create();
		$this->setExpectedIncorrectUsage( Plans::class . '::update' );

		$updated = Plans::update(
			$id,
			array(
				'extension_slug' => self::OWNER,
				'sort_order'     => 1,
				'name'           => 'Changed',
			)
		);
		$this->assertInstanceOf( PlanView::class, $updated );

		$this->assertSame( 'Changed', $this->stored( $id )->get_name(), 'The known key beside the unknown one is written.' );
	}

	/**
	 * @testdox the extension slug passed to update is not written.
	 */
	public function test_the_extension_slug_passed_to_update_is_not_written(): void {
		$id = $this->create();

		$queries = array();
		$capture = static function ( string $query ) use ( &$queries ): string {
			$queries[] = $query;

			return $query;
		};
		add_filter( 'query', $capture );

		try {
			$updated = Plans::update(
				$id,
				array(
					'extension_slug' => self::OWNER,
					'name'           => 'Changed',
				)
			);
		} finally {
			remove_filter( 'query', $capture );
		}

		$this->assertInstanceOf( PlanView::class, $updated );
		$this->assertSame( self::OWNER, $updated->get_extension_slug() );

		// Scoping (the WHERE side) is pinned by the cross-extension update tests; the SQL
		// check covers only what stored state cannot show: the slug is never in a SET list.
		$table   = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );
		$updates = array_values(
			array_filter(
				$queries,
				static function ( string $query ) use ( $table ): bool {
					return 0 === stripos( ltrim( $query ), 'UPDATE' ) && false !== strpos( $query, $table );
				}
			)
		);
		$this->assertNotEmpty( $updates );
		foreach ( $updates as $update ) {
			$where    = stripos( $update, ' WHERE ' );
			$set_list = false === $where ? $update : substr( $update, 0, $where );
			$this->assertDoesNotMatchRegularExpression( '/\bextension_slug\b/i', $set_list, 'The slug is not in the SET list.' );
		}

		$plan = $this->stored( $id );
		$this->assertSame( self::OWNER, $plan->get_extension_slug() );
		$this->assertSame( 'Changed', $plan->get_name() );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public function provide_invalid_update_scopes(): array {
		return array(
			'missing slug'    => array( array( 'name' => 'Changed' ) ),
			'null slug'       => array(
				array(
					'extension_slug' => null,
					'name'           => 'Changed',
				),
			),
			'empty slug'      => array(
				array(
					'extension_slug' => '',
					'name'           => 'Changed',
				),
			),
			'non-string slug' => array(
				array(
					'extension_slug' => 5,
					'name'           => 'Changed',
				),
			),
		);
	}

	/**
	 * @testdox update without a valid extension slug throws and leaves the plan unchanged.
	 * @dataProvider provide_invalid_update_scopes
	 *
	 * @param array<string, mixed> $args Update args without a valid extension slug.
	 */
	public function test_update_without_a_valid_extension_slug_throws( array $args ): void {
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

	/**
	 * @testdox update under another extension slug returns null, leaves the plan unchanged and never asks that extension to validate.
	 */
	public function test_update_under_another_extension_slug_returns_null_and_writes_nothing(): void {
		$id     = $this->create();
		$before = $this->stored( $id )->to_storage();

		$validated = 0;
		add_action(
			self::HOOK,
			static function () use ( &$validated ): void {
				++$validated;
			}
		);

		$this->assertNull(
			Plans::update(
				$id,
				array(
					'extension_slug' => 'other-extension',
					'name'           => 'Hijacked',
				)
			)
		);
		$this->assertNull( Plans::update( $id, array( 'extension_slug' => 'other-extension' ) ), 'A fieldless update is scoped too.' );

		$this->assertSame( 0, $validated, 'The validate action never sees a plan of another extension.' );
		$this->assertSame( $before, $this->stored( $id )->to_storage() );
	}

	/**
	 * @testdox an update writing identical values within the same second still returns the view.
	 */
	public function test_an_identical_update_within_the_same_second_returns_the_view(): void {
		$id   = $this->create();
		$args = array(
			'extension_slug' => self::OWNER,
			'name'           => 'Same',
		);

		// The first write may change the row; the second, within the same second, changes none.
		$views = array( Plans::update( $id, $args ), Plans::update( $id, $args ) );

		foreach ( $views as $view ) {
			$this->assertInstanceOf( PlanView::class, $view );
			$this->assertSame( 'Same', $view->get_name() );
		}
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
	 * @testdox update rejects invalid args and leaves the plan unchanged.
	 * @dataProvider provide_invalid_update_args
	 *
	 * @param array<string, mixed> $args Invalid update args.
	 */
	public function test_update_rejects_invalid_args_and_leaves_the_plan_unchanged( array $args ): void {
		$id     = $this->create();
		$before = $this->stored( $id )->to_storage();

		try {
			Plans::update( $id, array( 'extension_slug' => self::OWNER ) + $args );
			$this->fail( 'Expected InvalidArgumentException.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertNotInstanceOf( PlanValidationException::class, $e );
		}

		$this->assertSame( $before, $this->stored( $id )->to_storage() );
	}

	/**
	 * @testdox update accepts the stored status after it is unregistered.
	 */
	public function test_update_accepts_the_stored_status_after_it_is_unregistered(): void {
		StatusRegistry::register( StatusRegistry::KIND_PLAN, 'seasonal' );
		$id = $this->create( array( 'status' => 'seasonal' ) );
		StatusRegistry::reset();
		$this->assertFalse( PlanStatus::is_registered( 'seasonal' ) );

		$updated = Plans::update(
			$id,
			array(
				'extension_slug' => self::OWNER,
				'status'         => 'seasonal',
				'name'           => 'Renamed',
			)
		);
		$this->assertInstanceOf( PlanView::class, $updated );

		$plan = $this->stored( $id );
		$this->assertSame( 'seasonal', $plan->get_status() );
		$this->assertSame( 'Renamed', $plan->get_name() );
	}

	/**
	 * @testdox update writes only the present fields.
	 */
	public function test_update_writes_only_the_present_fields(): void {
		global $wpdb;

		$id = $this->create();

		// A concurrent writer renames the plan while the extension validates a status change.
		add_action(
			self::HOOK,
			static function () use ( $wpdb, $id ): void {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS ), array( 'name' => 'Renamed elsewhere' ), array( 'id' => $id ) );
			}
		);

		$this->assertInstanceOf(
			PlanView::class,
			Plans::update(
				$id,
				array(
					'extension_slug' => self::OWNER,
					'status'         => PlanStatus::ARCHIVED,
				)
			)
		);

		$plan = $this->stored( $id );
		$this->assertSame( PlanStatus::ARCHIVED, $plan->get_status() );
		$this->assertSame( 'Renamed elsewhere', $plan->get_name(), 'The status-only update must not write the name it read.' );
	}

	/**
	 * @testdox update with no fields returns the plan without validating or writing.
	 */
	public function test_update_with_no_fields_returns_the_plan_without_validating_or_writing(): void {
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

		$this->assertInstanceOf( PlanView::class, Plans::update( $id, array( 'extension_slug' => self::OWNER ) ) );
		$this->assertSame( 0, $validated, 'An empty update has nothing to validate.' );
		$this->assertSame( '2020-01-01 00:00:00', $this->stored( $id )->get_date_updated_gmt(), 'An empty update writes nothing.' );

		$this->assertInstanceOf(
			PlanView::class,
			Plans::update(
				$id,
				array(
					'extension_slug' => self::OWNER,
					'name'           => 'Renamed',
				)
			)
		);
		$this->assertNotSame( '2020-01-01 00:00:00', $this->stored( $id )->get_date_updated_gmt(), 'A field update bumps the update time.' );
	}

	/**
	 * @testdox the validate action receives the would-be state on create.
	 */
	public function test_validate_action_receives_the_would_be_state_on_create(): void {
		$seen = array();
		add_action(
			self::HOOK,
			static function ( $errors, $plan, $extension_slug ) use ( &$seen ): void {
				$seen[] = array( $errors, $plan, $extension_slug );
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

	/**
	 * @testdox the validate action receives the would-be state on update.
	 */
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
				'extension_slug' => self::OWNER,
				'name'           => 'Renamed',
				'pricing_policy' => array( 'x' => 1 ),
			)
		);

		$this->assertInstanceOf( PlanView::class, $seen );
		$this->assertSame( $id, $seen->get_id() );
		$this->assertSame( 'Renamed', $seen->get_name() );
		$this->assertSame( array( 'x' => 1 ), $seen->get_pricing_policy() );
	}

	/**
	 * @testdox an added error refuses the create with its codes.
	 */
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

	/**
	 * @testdox an added error refuses the update.
	 */
	public function test_an_added_error_refuses_the_update(): void {
		$id = $this->create();
		add_action(
			self::HOOK,
			static function ( $errors ): void {
				$errors->add( 'acme_no', 'No.' );
			}
		);

		try {
			Plans::update(
				$id,
				array(
					'extension_slug' => self::OWNER,
					'name'           => 'Refused',
				)
			);
			$this->fail( 'Expected PlanValidationException.' );
		} catch ( PlanValidationException $e ) {
			$this->assertSame( array( 'acme_no' ), $e->get_errors()->get_error_codes() );
		}

		$this->assertSame( 'Monthly', $this->stored( $id )->get_name() );
	}

	/**
	 * @testdox a throwing callback throws a runtime exception and stores nothing.
	 */
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

	/**
	 * @testdox a failed insert throws a runtime exception without a previous exception.
	 */
	public function test_a_failed_insert_throws_a_runtime_exception_without_a_previous_exception(): void {
		global $wpdb;

		$table        = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );
		$break_insert = static function ( string $query ) use ( $table ): string {
			if ( 0 === stripos( ltrim( $query ), 'INSERT' ) && false !== strpos( $query, $table ) ) {
				return 'INSERT INTO nonexistent_table_for_this_test (id) VALUES (1)';
			}

			return $query;
		};
		add_filter( 'query', $break_insert );
		$suppressed = $wpdb->suppress_errors( true );

		try {
			$this->create();
			$this->fail( 'Expected RuntimeException.' );
		} catch ( RuntimeException $e ) {
			// The REST controller reads a previous exception as "a validation callback threw".
			$this->assertNull( $e->getPrevious() );
		} finally {
			$wpdb->suppress_errors( $suppressed );
			remove_filter( 'query', $break_insert );
		}
	}

	/**
	 * @testdox the meta methods round-trip values.
	 */
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

	/**
	 * @testdox meta reads for a missing plan are empty.
	 */
	public function test_meta_reads_for_a_missing_plan_are_empty(): void {
		$this->assertFalse( Plans::delete_meta( 999999, 'note' ) );
		$this->assertSame( '', Plans::get_meta( 999999, 'note', true ) );
		$this->assertSame( array(), Plans::get_meta( 999999 ) );
	}

	/**
	 * @testdox meta writes do not look up the plan.
	 */
	public function test_meta_writes_do_not_look_up_the_plan(): void {
		$id = $this->create();
		Plans::add_meta( $id, 'note', 'one' );

		$queries = array();
		$capture = static function ( $query ) use ( &$queries ) {
			$queries[] = $query;
			return $query;
		};
		add_filter( 'query', $capture );
		try {
			Plans::add_meta( $id, 'note', 'two' );
			Plans::update_meta( $id, 'flag', 'on' );
		} finally {
			remove_filter( 'query', $capture );
		}

		$plans_table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );
		foreach ( $queries as $query ) {
			$this->assertDoesNotMatchRegularExpression( '/\\b' . preg_quote( $plans_table, '/' ) . '\\b/', $query, 'A meta write reads only the meta table.' );
		}
		$this->assertSame( array( 'one', 'two' ), Plans::get_meta( $id, 'note' ) );
		$this->assertSame( 'on', Plans::get_meta( $id, 'flag', true ) );
	}

	/**
	 * @testdox deleting a plan removes its meta.
	 */
	public function test_deleting_a_plan_removes_its_meta(): void {
		$id = $this->create();
		Plans::add_meta( $id, 'note', 'one' );

		$this->assertTrue( ( new PlanRepository() )->delete( $id ) );

		$this->assertSame( array(), Plans::get_meta( $id, 'note' ) );
	}

	/**
	 * @testdox an empty meta key is rejected.
	 */
	public function test_an_empty_meta_key_is_rejected(): void {
		$this->expectException( InvalidArgumentException::class );

		Plans::add_meta( $this->create(), '', 'x' );
	}

	/**
	 * Map views to their ids.
	 *
	 * @param array<int, PlanView> $plans Views to map.
	 * @return array<int, int>
	 */
	private static function plan_ids( array $plans ): array {
		return array_map(
			static function ( PlanView $plan ): int {
				return $plan->get_id();
			},
			$plans
		);
	}

	/**
	 * @testdox get returns a plan in any status, and null for an unknown id.
	 */
	public function test_get_returns_a_plan_in_any_status(): void {
		$archived_id = $this->create(
			array(
				'status'         => PlanStatus::ARCHIVED,
				'pricing_policy' => array( 'opaque' => true ),
			)
		);

		$plan = Plans::get( $archived_id );

		$this->assertInstanceOf( PlanView::class, $plan );
		$this->assertSame( $archived_id, $plan->get_id() );
		$this->assertSame( PlanStatus::ARCHIVED, $plan->get_status() );
		$this->assertSame( self::OWNER, $plan->get_extension_slug() );
		$this->assertSame( array( 'opaque' => true ), $plan->get_pricing_policy() );
		$this->assertNull( Plans::get( 999999 ) );
		$this->assertNull( Plans::get( 0 ) );
	}

	/**
	 * @testdox list without args returns every extension's plans in every status, oldest id first.
	 */
	public function test_list_without_args_returns_every_plan_in_id_order(): void {
		$first_id    = $this->create( array( 'name' => 'Zulu' ) );
		$archived_id = $this->create( array( 'status' => PlanStatus::ARCHIVED ) );
		$foreign_id  = $this->create( array( 'extension_slug' => 'other-extension' ) );

		$plans = Plans::list();

		$this->assertSame( array( $first_id, $archived_id, $foreign_id ), self::plan_ids( $plans ) );
		$this->assertContainsOnlyInstancesOf( PlanView::class, $plans );
	}

	/**
	 * @testdox list filters by an extension slug or a list of them, ignoring duplicate slugs.
	 */
	public function test_list_filters_by_extension_slug(): void {
		$own_id   = $this->create();
		$other_id = $this->create( array( 'extension_slug' => 'other-extension' ) );
		$this->create( array( 'extension_slug' => 'third-extension' ) );

		$this->assertSame( array( $own_id ), self::plan_ids( Plans::list( array( 'extension_slug' => self::OWNER ) ) ) );
		$this->assertSame(
			array( $own_id, $other_id ),
			self::plan_ids( Plans::list( array( 'extension_slug' => array( self::OWNER, 'other-extension' ) ) ) )
		);
		$this->assertSame( array( $own_id ), self::plan_ids( Plans::list( array( 'extension_slug' => array( self::OWNER, self::OWNER ) ) ) ) );
	}

	/**
	 * @testdox list filters by a status or a list of them.
	 */
	public function test_list_filters_by_status(): void {
		$active_id   = $this->create();
		$archived_id = $this->create( array( 'status' => PlanStatus::ARCHIVED ) );

		$this->assertSame( array( $active_id ), self::plan_ids( Plans::list( array( 'status' => PlanStatus::ACTIVE ) ) ) );
		$this->assertSame(
			array( $active_id, $archived_id ),
			self::plan_ids( Plans::list( array( 'status' => array( PlanStatus::ACTIVE, PlanStatus::ARCHIVED ) ) ) )
		);
	}

	/**
	 * @testdox list filters by ids in id order regardless of the requested order, composing with the other filters.
	 */
	public function test_list_filters_by_ids(): void {
		$first_id    = $this->create();
		$second_id   = $this->create();
		$excluded_id = $this->create();
		$archived_id = $this->create( array( 'status' => PlanStatus::ARCHIVED ) );
		$foreign_id  = $this->create( array( 'extension_slug' => 'other-extension' ) );
		$requested   = array( $archived_id, $second_id, $first_id, $foreign_id, 999999 );

		$plans = Plans::list(
			array(
				'extension_slug' => self::OWNER,
				'ids'            => $requested,
			)
		);
		$all   = self::plan_ids( $plans );
		$this->assertSame( array( $first_id, $second_id, $archived_id ), $all );
		$this->assertNotContains( $excluded_id, $all );

		$active_plans = Plans::list(
			array(
				'extension_slug' => self::OWNER,
				'ids'            => $requested,
				'status'         => PlanStatus::ACTIVE,
			)
		);
		$this->assertSame( array( $first_id, $second_id ), self::plan_ids( $active_plans ) );
	}

	/**
	 * @testdox list matches nothing for an empty list filter.
	 */
	public function test_list_matches_nothing_for_an_empty_list_filter(): void {
		$this->create();

		$this->assertSame( array(), Plans::list( array( 'ids' => array() ) ) );
		$this->assertSame( array(), Plans::list( array( 'status' => array() ) ) );
		$this->assertSame( array(), Plans::list( array( 'extension_slug' => array() ) ) );
	}

	/**
	 * @testdox list pages with limit and offset in id order.
	 */
	public function test_list_pages_with_limit_and_offset(): void {
		$this->create();
		$second_id = $this->create();
		$third_id  = $this->create();

		$plans = Plans::list(
			array(
				'limit'  => 2,
				'offset' => 1,
			)
		);
		$this->assertSame( array( $second_id, $third_id ), self::plan_ids( $plans ) );
	}

	/**
	 * @testdox list ignores an unknown key with a notice.
	 */
	public function test_list_ignores_an_unknown_key_with_a_notice(): void {
		$this->setExpectedIncorrectUsage( Plans::class . '::list' );
		$plan_id = $this->create();

		$this->assertSame( array( $plan_id ), self::plan_ids( Plans::list( array( 'orderby' => 'name' ) ) ) );
	}

	/**
	 * @testdox list rejects an invalid value.
	 * @dataProvider provide_invalid_list_args
	 *
	 * @param array<string, mixed> $args List args.
	 */
	public function test_list_rejects_an_invalid_value( array $args ): void {
		$this->expectException( InvalidArgumentException::class );

		Plans::list( $args );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public function provide_invalid_list_args(): array {
		return array(
			'empty extension slug' => array( array( 'extension_slug' => '' ) ),
			'any extension slug'   => array( array( 'extension_slug' => 'any' ) ),
			'any in a slug list'   => array( array( 'extension_slug' => array( self::OWNER, 'any' ) ) ),
			'status not a string'  => array( array( 'status' => 5 ) ),
			'id not positive'      => array( array( 'ids' => array( 1, 0 ) ) ),
			'ids not a list'       => array( array( 'ids' => array( 'a' => 1 ) ) ),
			'zero limit'           => array( array( 'limit' => 0 ) ),
			'negative offset'      => array( array( 'offset' => -1 ) ),
		);
	}
}
