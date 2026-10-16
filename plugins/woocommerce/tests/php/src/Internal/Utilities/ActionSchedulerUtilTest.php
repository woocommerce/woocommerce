<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Enums\ActionSchedulerFeature;
use Automattic\WooCommerce\Internal\Utilities\ActionSchedulerUtil;
use WC_Unit_Test_Case;

/**
 * Tests for the Internal\Utilities\ActionSchedulerUtil class.
 *
 * The bundled Action Scheduler is always loaded in the test suite. Older loaded copies are simulated
 * through the legacy proxy mocks: which functions and methods exist, how many parameters the loaded
 * `as_schedule_single_action()` declares, the loaded path, and the Action Scheduler calls themselves.
 */
class ActionSchedulerUtilTest extends WC_Unit_Test_Case {

	/**
	 * Every feature core relies on, in the order the util probes them.
	 *
	 * @var string[]
	 */
	private const FEATURES = array(
		ActionSchedulerFeature::UNIQUE,
		ActionSchedulerFeature::PRIORITY,
		ActionSchedulerFeature::UNIQUE_ARGS,
		ActionSchedulerFeature::HAS_SCHEDULED_ACTION,
		ActionSchedulerFeature::ASYNC,
	);

	private const HOOK  = 'woocommerce_action_scheduler_util_test';
	private const GROUP = 'woocommerce_action_scheduler_util_test_group';

	/**
	 * Directories of the fake Action Scheduler copies created by simulate_loaded_copy().
	 *
	 * @var string[]
	 */
	private array $fake_copies = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		ActionSchedulerUtil::reset_cache();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		as_unschedule_all_actions( self::HOOK );
		ActionSchedulerUtil::reset_cache();
		$this->reset_legacy_proxy_mocks();

		foreach ( $this->fake_copies as $dir ) {
			wp_delete_file( $dir . '/action-scheduler.php' );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			rmdir( $dir );
		}
		$this->fake_copies = array();

		parent::tearDown();
	}

	/**
	 * @testdox The bundled copy is reported as loaded, current, ready and fully featured.
	 */
	public function test_reports_the_bundled_copy_when_it_is_the_loaded_one(): void {
		$this->assertTrue( ActionSchedulerUtil::is_loaded() );
		$this->assertTrue( ActionSchedulerUtil::is_ready() );
		$this->assertTrue( ActionSchedulerUtil::can_check_scheduled_actions() );

		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+/', (string) ActionSchedulerUtil::get_bundled_version() );
		$this->assertSame( ActionSchedulerUtil::get_bundled_version(), ActionSchedulerUtil::get_loaded_version() );
		$this->assertSame( trailingslashit( wp_normalize_path( \ActionScheduler::plugin_path( '' ) ) ), ActionSchedulerUtil::get_loaded_path() );
		$this->assertSame(
			array(
				'type'     => 'woocommerce',
				'name'     => 'WooCommerce',
				'basename' => plugin_basename( WC_PLUGIN_FILE ),
			),
			ActionSchedulerUtil::get_loaded_source()
		);
		$this->assertGreaterThanOrEqual( 0, version_compare( (string) ActionSchedulerUtil::get_highest_registered_version(), (string) ActionSchedulerUtil::get_loaded_version() ) );
		$this->assertContains( ActionSchedulerUtil::get_loaded_version(), ActionSchedulerUtil::get_registered_sources() );

		$this->assertFalse( ActionSchedulerUtil::is_risky() );
		foreach ( self::FEATURES as $feature ) {
			$this->assertTrue( ActionSchedulerUtil::supports( $feature ), "The bundled copy should support $feature" );
		}
		$this->assertFalse( ActionSchedulerUtil::supports( 'no_such_feature' ) );
	}

	/**
	 * @testdox The registry answers come from `ActionScheduler_Versions`, complete only after `plugins_loaded`.
	 */
	public function test_registry_answers_come_from_the_version_registry(): void {
		$registry = new \ActionScheduler_Versions();
		$registry->register( '3.5.4', '__return_null' );
		$registry->register( '4.0.0', '__return_null' );
		$this->register_legacy_proxy_static_mocks( array( 'ActionScheduler_Versions' => array( 'instance' => fn() => $registry ) ) );

		$this->assertSame( '4.0.0', ActionSchedulerUtil::get_highest_registered_version() );
		$this->assertSame( array( __FILE__ => '4.0.0' ), ActionSchedulerUtil::get_registered_sources() );

		$this->register_legacy_proxy_function_mocks( array( 'did_action' => fn( string $hook ) => 'plugins_loaded' === $hook ? 0 : did_action( $hook ) ) );
		$this->assertNull( ActionSchedulerUtil::get_highest_registered_version() );
		$this->assertSame( array(), ActionSchedulerUtil::get_registered_sources() );
	}

	/**
	 * @testdox A loaded copy is probed for each feature and is risky when one is missing.
	 * @dataProvider provider_loaded_copies
	 *
	 * @param string   $version          The simulated loaded version.
	 * @param string[] $expected_missing The features that version lacks.
	 */
	public function test_probes_each_feature_of_a_loaded_copy( string $version, array $expected_missing ): void {
		$this->simulate_loaded_copy( $version );

		$this->assertTrue( ActionSchedulerUtil::is_loaded() );
		$this->assertSame( $version, ActionSchedulerUtil::get_loaded_version() );
		foreach ( self::FEATURES as $feature ) {
			$this->assertSame( ! in_array( $feature, $expected_missing, true ), ActionSchedulerUtil::supports( $feature ), "Support for $feature on $version" );
		}
		$this->assertSame( ! empty( $expected_missing ), ActionSchedulerUtil::is_risky() );
	}

	/**
	 * Data provider for test_probes_each_feature_of_a_loaded_copy.
	 *
	 * @return array
	 */
	public function provider_loaded_copies(): array {
		return array(
			'2.1.0: the floor'                   => array( '2.1.0', self::FEATURES ),
			'3.0.0: async only'                  => array( '3.0.0', array( ActionSchedulerFeature::UNIQUE, ActionSchedulerFeature::PRIORITY, ActionSchedulerFeature::UNIQUE_ARGS, ActionSchedulerFeature::HAS_SCHEDULED_ACTION ) ),
			'3.2.0: async only, lab fixture'     => array( '3.2.0', array( ActionSchedulerFeature::UNIQUE, ActionSchedulerFeature::PRIORITY, ActionSchedulerFeature::UNIQUE_ARGS, ActionSchedulerFeature::HAS_SCHEDULED_ACTION ) ),
			'3.5.4: unique by hook, no priority' => array( '3.5.4', array( ActionSchedulerFeature::PRIORITY, ActionSchedulerFeature::UNIQUE_ARGS ) ),
			'3.9.3: unique by hook'              => array( '3.9.3', array( ActionSchedulerFeature::UNIQUE_ARGS ) ),
			'4.1.0: everything'                  => array( '4.1.0', array() ),
		);
	}

	/**
	 * @testdox A copy older than 2.1.0 is identified but reported as unusable.
	 */
	public function test_a_copy_older_than_the_procedural_api_is_identified_but_unusable(): void {
		$this->simulate_loaded_copy( '1.5' );

		$this->assertFalse( ActionSchedulerUtil::is_loaded() );
		$this->assertSame( '1.5', ActionSchedulerUtil::get_loaded_version() );
		$this->assertTrue( ActionSchedulerUtil::is_risky() );
		$this->assertFalse( ActionSchedulerUtil::supports( ActionSchedulerFeature::UNIQUE ) );
		$this->assertFalse( ActionSchedulerUtil::is_ready() );
	}

	/**
	 * @testdox Nothing is reported or scheduled while Action Scheduler is not loaded.
	 */
	public function test_reports_nothing_and_schedules_nothing_when_action_scheduler_is_not_loaded(): void {
		$this->register_legacy_proxy_function_mocks(
			array( 'function_exists' => fn( string $name ) => 0 !== strpos( $name, 'as_' ) && 0 !== strpos( $name, 'wc_schedule' ) && function_exists( $name ) )
		);
		$this->setExpectedIncorrectUsage( ActionSchedulerUtil::class . '::has_scheduled_action' );
		$this->setExpectedIncorrectUsage( ActionSchedulerUtil::class . '::schedule_single' );

		$this->assertFalse( ActionSchedulerUtil::is_loaded() );
		$this->assertFalse( ActionSchedulerUtil::is_ready() );
		$this->assertFalse( ActionSchedulerUtil::can_check_scheduled_actions() );
		$this->assertNull( ActionSchedulerUtil::get_loaded_version() );
		$this->assertNull( ActionSchedulerUtil::get_loaded_path() );
		$this->assertSame( 'other', ActionSchedulerUtil::get_loaded_source()['type'] );
		$this->assertTrue( ActionSchedulerUtil::is_risky() );
		$this->assertFalse( ActionSchedulerUtil::supports( ActionSchedulerFeature::ASYNC ) );
		$this->assertFalse( ActionSchedulerUtil::has_scheduled_action( self::HOOK ) );
		$this->assertSame( 0, ActionSchedulerUtil::schedule_single( time() + HOUR_IN_SECONDS, self::HOOK, array(), self::GROUP, true, 5 ) );
	}

	/**
	 * @testdox Probes are memoized until the cache is reset.
	 */
	public function test_probes_are_memoized_until_reset(): void {
		$this->simulate_loaded_copy( '3.2.0' );
		$this->assertFalse( ActionSchedulerUtil::supports( ActionSchedulerFeature::UNIQUE ) );

		$this->register_legacy_proxy_class_mocks( array( \ReflectionFunction::class => fn() => $this->reflection_with_parameters( 6 ) ) );
		$this->assertFalse( ActionSchedulerUtil::supports( ActionSchedulerFeature::UNIQUE ), 'The first answer should be memoized' );

		ActionSchedulerUtil::reset_cache();
		$this->assertTrue( ActionSchedulerUtil::supports( ActionSchedulerFeature::UNIQUE ), 'After a reset the loaded copy should be probed again' );
	}

	/**
	 * @testdox The loaded copy is resolved to the plugin that bundles it.
	 */
	public function test_resolves_the_loaded_copy_to_the_plugin_that_bundles_it(): void {
		$this->simulate_loaded_copy( '3.5.4', WP_PLUGIN_DIR . '/some-plugin/vendor/woocommerce/action-scheduler' );
		$folders = array();
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_plugins' => function ( string $folder ) use ( &$folders ) {
					$folders[] = $folder;
					return array( 'some-plugin.php' => array( 'Name' => 'Some Plugin' ) );
				},
			)
		);

		$source = ActionSchedulerUtil::get_loaded_source();
		$this->assertSame( array( '/some-plugin' ), $folders, 'Only the one plugin folder should be scanned' );
		$this->assertSame(
			array(
				'type'     => 'plugin',
				'name'     => 'Some Plugin',
				'basename' => 'some-plugin/some-plugin.php',
			),
			$source
		);
	}

	/**
	 * @testdox A plugin directory with no known plugin file is reported by its directory name.
	 */
	public function test_resolves_an_unknown_plugin_directory_by_its_name(): void {
		$this->simulate_loaded_copy( '3.5.4', WP_PLUGIN_DIR . '/some-plugin/vendor/woocommerce/action-scheduler' );
		$this->register_legacy_proxy_function_mocks( array( 'get_plugins' => fn() => array() ) );

		$this->assertSame(
			array(
				'type'     => 'plugin',
				'name'     => 'some-plugin',
				'basename' => 'some-plugin',
			),
			ActionSchedulerUtil::get_loaded_source()
		);
	}

	/**
	 * @testdox A copy inside a symlinked plugin directory is resolved through the plugin's registered real path.
	 */
	public function test_resolves_a_symlinked_plugin_through_its_registered_real_path(): void {
		$this->simulate_loaded_copy( '3.5.4', '/real/location/linked-plugin/vendor/action-scheduler' );
		$this->register_legacy_proxy_global_mocks(
			array( 'wp_plugin_paths' => array( wp_normalize_path( WP_PLUGIN_DIR ) . '/linked-plugin' => '/real/location/linked-plugin' ) )
		);
		$this->register_legacy_proxy_function_mocks( array( 'get_plugins' => fn( string $folder ) => '/linked-plugin' === $folder ? array( 'linked-plugin.php' => array( 'Name' => 'Linked Plugin' ) ) : array() ) );

		$this->assertSame( '/real/location/linked-plugin/vendor/action-scheduler/', ActionSchedulerUtil::get_loaded_path(), 'The loaded path stays the real path' );
		$this->assertSame(
			array(
				'type'     => 'plugin',
				'name'     => 'Linked Plugin',
				'basename' => 'linked-plugin/linked-plugin.php',
			),
			ActionSchedulerUtil::get_loaded_source()
		);
	}

	/**
	 * @testdox The loaded copy is resolved to the mu-plugin that bundles it.
	 */
	public function test_resolves_the_loaded_copy_to_the_mu_plugin_that_bundles_it(): void {
		$this->simulate_loaded_copy( '3.5.4', WPMU_PLUGIN_DIR . '/loader/lib/action-scheduler' );

		$this->assertSame(
			array(
				'type'     => 'mu-plugin',
				'name'     => 'loader',
				'basename' => 'loader',
			),
			ActionSchedulerUtil::get_loaded_source()
		);
	}

	/**
	 * @testdox The loaded copy is resolved to the active theme that bundles it.
	 */
	public function test_resolves_the_loaded_copy_to_the_active_theme_that_bundles_it(): void {
		$theme = wp_get_theme();
		if ( ! $theme->exists() ) {
			$this->markTestSkipped( 'No theme is installed in the test environment.' );
		}

		$this->simulate_loaded_copy( '3.5.4', get_stylesheet_directory() . '/vendor/action-scheduler' );

		$this->assertSame(
			array(
				'type'     => 'theme',
				'name'     => (string) $theme->get( 'Name' ),
				'basename' => get_stylesheet(),
			),
			ActionSchedulerUtil::get_loaded_source()
		);
	}

	/**
	 * @testdox A loaded copy outside every known location is reported as "other".
	 */
	public function test_resolves_an_unknown_location_to_other(): void {
		$this->simulate_loaded_copy( '3.5.4', '/somewhere/else/action-scheduler' );

		$this->assertSame(
			array(
				'type'     => 'other',
				'name'     => '',
				'basename' => '',
			),
			ActionSchedulerUtil::get_loaded_source()
		);
	}

	/**
	 * @testdox On the bundled copy, unique and priority are honoured natively.
	 */
	public function test_schedules_a_unique_prioritized_action_on_the_bundled_copy(): void {
		$id = ActionSchedulerUtil::schedule_single( time() + HOUR_IN_SECONDS, self::HOOK, array( 'a' ), self::GROUP, true, 5 );

		$this->assertGreaterThan( 0, $id );
		$this->assertSame( 5, \ActionScheduler::store()->fetch_action( $id )->get_priority() );
		$this->assertSame(
			0,
			ActionSchedulerUtil::schedule_single( time() + HOUR_IN_SECONDS, self::HOOK, array( 'a' ), self::GROUP, true, 5 ),
			'A second unique action with the same hook, args and group should be rejected'
		);
		$this->assertGreaterThan(
			0,
			ActionSchedulerUtil::schedule_single( time() + HOUR_IN_SECONDS, self::HOOK, array( 'b' ), self::GROUP, true, 5 ),
			'A unique action that differs by args should be accepted on a copy that honours args'
		);
	}

	/**
	 * @testdox On the bundled copy, every wrapper schedules through its Action Scheduler counterpart.
	 */
	public function test_every_wrapper_schedules_on_the_bundled_copy(): void {
		$store = \ActionScheduler::store();

		$id = ActionSchedulerUtil::enqueue_async( self::HOOK, array( 'async' ), self::GROUP );
		$this->assertGreaterThan( 0, $id );
		$this->assertInstanceOf( \ActionScheduler_NullSchedule::class, $store->fetch_action( $id )->get_schedule() );

		$id = ActionSchedulerUtil::schedule_recurring( time() + HOUR_IN_SECONDS, HOUR_IN_SECONDS, self::HOOK, array( 'recurring' ), self::GROUP );
		$this->assertGreaterThan( 0, $id );
		$this->assertInstanceOf( \ActionScheduler_IntervalSchedule::class, $store->fetch_action( $id )->get_schedule() );

		$id = ActionSchedulerUtil::schedule_cron( time() + HOUR_IN_SECONDS, '0 0 * * *', self::HOOK, array( 'cron' ), self::GROUP );
		$this->assertGreaterThan( 0, $id );
		$this->assertInstanceOf( \ActionScheduler_CronSchedule::class, $store->fetch_action( $id )->get_schedule() );
	}

	/**
	 * @testdox On a copy with unique and priority, both are passed through.
	 */
	public function test_passes_unique_and_priority_natively_on_a_copy_that_supports_both(): void {
		$this->simulate_loaded_copy( '4.1.0' );
		$calls = $this->capture_scheduling_calls();

		$this->assertSame( 123, ActionSchedulerUtil::schedule_single( 1000, self::HOOK, array( 'a' ), self::GROUP, true, 5 ) );
		$this->assertSame( 123, ActionSchedulerUtil::schedule_recurring( 1000, 60, self::HOOK, array(), self::GROUP, false, 200 ) );
		$this->assertSame( 123, ActionSchedulerUtil::schedule_cron( 1000, '0 0 * * *', self::HOOK, array(), self::GROUP ) );
		$this->assertSame( 123, ActionSchedulerUtil::enqueue_async( self::HOOK, array( 'b' ), self::GROUP, true, 1 ) );

		$this->assertSame(
			array(
				array( 'as_schedule_single_action', 1000, self::HOOK, array( 'a' ), self::GROUP, true, 5 ),
				array( 'as_schedule_recurring_action', 1000, 60, self::HOOK, array(), self::GROUP, false, 200 ),
				array( 'as_schedule_cron_action', 1000, '0 0 * * *', self::HOOK, array(), self::GROUP, false, 10 ),
				array( 'as_enqueue_async_action', self::HOOK, array( 'b' ), self::GROUP, true, 1 ),
			),
			$calls()
		);
	}

	/**
	 * @testdox On a copy whose unique check ignores args, unique is emulated with and without args.
	 */
	public function test_emulates_unique_on_a_copy_whose_unique_check_ignores_args(): void {
		$this->simulate_loaded_copy( '3.9.3' );
		$calls = $this->capture_scheduling_calls();

		ActionSchedulerUtil::schedule_single( 1000, self::HOOK, array( 'a' ), self::GROUP, true, 5 );
		ActionSchedulerUtil::schedule_single( 1000, self::HOOK, array(), self::GROUP, true );

		$this->assertSame(
			array(
				array( 'as_has_scheduled_action', self::HOOK, array( 'a' ), self::GROUP ),
				array( 'as_schedule_single_action', 1000, self::HOOK, array( 'a' ), self::GROUP, false, 5 ),
				array( 'as_has_scheduled_action', self::HOOK, array(), self::GROUP ),
				array( 'as_schedule_single_action', 1000, self::HOOK, array(), self::GROUP, false, 10 ),
			),
			$calls(),
			'Unique should be emulated (checked, then passed as false) with and without args'
		);
	}

	/**
	 * @testdox Emulated unique matches args exactly, whichever order the actions are scheduled in.
	 */
	public function test_emulated_unique_matches_args_exactly_in_either_order(): void {
		$this->simulate_loaded_copy( '3.9.3' );

		$orders = array(
			'args first'  => array( array( 123 ), array() ),
			'empty first' => array( array(), array( 123 ) ),
		);
		foreach ( $orders as $name => $order ) {
			/** @var array<int, array{string, ?array, string}> $pending Filled by the mocks below. */
			$pending = array();
			$this->register_legacy_proxy_function_mocks(
				array(
					'as_has_scheduled_action'   => function ( string $hook, ?array $args, string $group ) use ( &$pending ) {
						return in_array( array( $hook, $args, $group ), $pending, true );
					},
					'as_schedule_single_action' => function ( int $timestamp, string $hook, array $args, string $group ) use ( &$pending ) {
						$pending[] = array( $hook, $args, $group );
						return count( $pending );
					},
				)
			);

			$this->assertSame( 1, ActionSchedulerUtil::schedule_single( 1000, self::HOOK, $order[0], self::GROUP, true ), "$name: the first action should be scheduled" );
			$this->assertSame( 2, ActionSchedulerUtil::schedule_single( 1000, self::HOOK, $order[1], self::GROUP, true ), "$name: an action that differs only by args should be scheduled" );
			$this->assertSame( 0, ActionSchedulerUtil::schedule_single( 1000, self::HOOK, $order[1], self::GROUP, true ), "$name: a true duplicate should be refused" );
			$this->assertCount( 2, $pending, "$name: the duplicate should not reach Action Scheduler" );
		}
	}

	/**
	 * @testdox Emulated unique declines to schedule when a matching action exists.
	 */
	public function test_emulated_unique_declines_to_schedule_when_a_matching_action_exists(): void {
		$this->simulate_loaded_copy( '3.9.3' );
		$calls = $this->capture_scheduling_calls( true );

		$this->assertSame( 0, ActionSchedulerUtil::schedule_single( 1000, self::HOOK, array( 'a' ), self::GROUP, true ) );
		$this->assertSame(
			array( array( 'as_has_scheduled_action', self::HOOK, array( 'a' ), self::GROUP ) ),
			$calls(),
			'Nothing should be scheduled when the emulated unique check finds a match'
		);

		$this->assertSame( 123, ActionSchedulerUtil::schedule_single( 1000, self::HOOK, array( 'a' ), self::GROUP ) );
		$this->assertSame( 'as_schedule_single_action', $calls()[1][0], 'A non-unique request should be scheduled without a check' );
	}

	/**
	 * @testdox On a copy without unique, unique is emulated and the argument is not passed.
	 */
	public function test_emulates_unique_on_a_copy_without_it(): void {
		$this->simulate_loaded_copy( '3.2.0' );
		$calls = $this->capture_scheduling_calls();

		$this->assertSame( 123, ActionSchedulerUtil::schedule_single( 1000, self::HOOK, array(), self::GROUP, true ) );
		$this->assertSame(
			array(
				array( 'as_next_scheduled_action', self::HOOK, array(), self::GROUP ),
				array( 'as_schedule_single_action', 1000, self::HOOK, array(), self::GROUP ),
			),
			$calls(),
			'The check should use as_next_scheduled_action on a copy without as_has_scheduled_action, and unique should not be passed'
		);

		$this->capture_scheduling_calls( true );
		$this->assertSame( 0, ActionSchedulerUtil::schedule_single( 1000, self::HOOK, array(), self::GROUP, true ) );
	}

	/**
	 * @testdox On a copy without priority, the priority is dropped and logged once per hook.
	 */
	public function test_drops_priority_on_a_copy_without_it_and_logs_once_per_hook(): void {
		$this->simulate_loaded_copy( '3.5.4' );
		$calls   = $this->capture_scheduling_calls();
		$entries = array();
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_logger' => function () use ( &$entries ) {
					return new class( $entries ) {
						/**
						 * Collected entries.
						 *
						 * @var array
						 */
						private array $entries;

						/**
						 * Constructor.
						 *
						 * @param array $entries Collected entries, by reference.
						 */
						public function __construct( array &$entries ) {
							$this->entries = &$entries;
						}

						/**
						 * Record a debug entry.
						 *
						 * @param string $message The message.
						 * @param array  $context The context.
						 */
						public function debug( string $message, array $context = array() ): void {
							$this->entries[] = array( $message, $context );
						}
					};
				},
			)
		);

		ActionSchedulerUtil::schedule_single( 1000, self::HOOK, array(), self::GROUP, false, 5 );
		ActionSchedulerUtil::schedule_single( 1000, self::HOOK, array(), self::GROUP, false, 3 );
		ActionSchedulerUtil::schedule_recurring( 1000, 60, self::HOOK . '_other', array(), self::GROUP, false, 5 );
		ActionSchedulerUtil::schedule_single( 1000, self::HOOK . '_default', array(), self::GROUP, false, 10 );

		$this->assertSame( array( 'as_schedule_single_action', 1000, self::HOOK, array(), self::GROUP, false ), $calls()[0], 'The priority should not be passed to a copy without it' );
		$this->assertCount( 2, $entries, 'One entry per hook with a non-default priority' );
		$this->assertStringContainsString( 'Priority 5 for action "' . self::HOOK . '"', $entries[0][0] );
		$this->assertStringContainsString( '3.5.4', $entries[0][0] );
		$this->assertSame( array( 'source' => 'action-scheduler' ), $entries[0][1] );
		$this->assertStringContainsString( self::HOOK . '_other', $entries[1][0] );
	}

	/**
	 * @testdox On a copy without async actions, enqueue_async schedules a single action for now.
	 */
	public function test_enqueue_async_falls_back_to_a_single_action_for_now_on_a_copy_without_async(): void {
		$this->simulate_loaded_copy( '2.1.0' );
		$calls  = $this->capture_scheduling_calls();
		$before = time();

		$this->assertSame( 123, ActionSchedulerUtil::enqueue_async( self::HOOK, array( 'a' ), self::GROUP ) );

		$call = $calls()[0];
		$this->assertSame( 'as_schedule_single_action', $call[0] );
		$this->assertEqualsWithDelta( $before, $call[1], 2 );
		$this->assertSame( array( self::HOOK, array( 'a' ), self::GROUP ), array_slice( $call, 2 ) );
	}

	/**
	 * @testdox On a copy with async actions but nothing newer, enqueue_async passes only hook, args and group.
	 */
	public function test_enqueue_async_uses_the_async_function_when_available(): void {
		$this->simulate_loaded_copy( '3.0.0' );
		$calls = $this->capture_scheduling_calls();

		$this->assertSame( 123, ActionSchedulerUtil::enqueue_async( self::HOOK, array(), self::GROUP ) );
		$this->assertSame( array( array( 'as_enqueue_async_action', self::HOOK, array(), self::GROUP ) ), $calls() );
	}

	/**
	 * @testdox On a copy without as_has_scheduled_action, has_scheduled_action falls back to as_next_scheduled_action.
	 */
	public function test_has_scheduled_action_falls_back_to_as_next_scheduled_action(): void {
		$this->simulate_loaded_copy( '3.2.0' );
		$calls = array();
		$this->register_legacy_proxy_function_mocks(
			array(
				'as_has_scheduled_action'  => function () {
					$this->fail( 'as_has_scheduled_action should not be called on a copy that lacks it' );
				},
				'as_next_scheduled_action' => function ( ...$args ) use ( &$calls ) {
					$calls[] = $args;
					return time() + HOUR_IN_SECONDS;
				},
			)
		);

		$this->assertTrue( ActionSchedulerUtil::has_scheduled_action( self::HOOK, array( 'a' ), self::GROUP ) );
		$this->assertSame( array( array( self::HOOK, array( 'a' ), self::GROUP ) ), $calls );
	}

	/**
	 * @testdox when_ready runs the callback at once on a ready copy.
	 */
	public function test_when_ready_runs_the_callback_at_once_on_a_ready_copy(): void {
		$ran = 0;
		ActionSchedulerUtil::when_ready(
			function () use ( &$ran ) {
				++$ran;
			}
		);

		$this->assertSame( 1, $ran );
	}

	/**
	 * @testdox Readiness follows the request phase: the Action Scheduler init hook, or init past priority 1 on older copies.
	 * @dataProvider provider_request_phases
	 *
	 * @param string $version  The simulated loaded version.
	 * @param array  $phase    The simulated request phase, see simulate_request_phase().
	 * @param bool   $expected Whether the copy should be reported as ready.
	 */
	public function test_is_ready_follows_the_request_phase( string $version, array $phase, bool $expected ): void {
		$this->simulate_loaded_copy( $version );
		$this->simulate_request_phase( $phase );

		$this->assertSame( $expected, ActionSchedulerUtil::is_ready() );
	}

	/**
	 * Data provider for test_is_ready_follows_the_request_phase.
	 *
	 * @return array
	 */
	public function provider_request_phases(): array {
		$before_init     = array(
			'init_started'  => false,
			'doing_init'    => false,
			'init_priority' => false,
			'as_init_fired' => false,
		);
		$init_priority_0 = array(
			'init_started'  => true,
			'doing_init'    => true,
			'init_priority' => 0,
			'as_init_fired' => false,
		);
		$init_priority_1 = array( 'init_priority' => 1 ) + $init_priority_0;
		$init_priority_2 = array( 'init_priority' => 2 ) + $init_priority_0;
		$after_init      = array(
			'init_started'  => true,
			'doing_init'    => false,
			'init_priority' => false,
			'as_init_fired' => false,
		);
		$as_init_fired   = array( 'as_init_fired' => true ) + $init_priority_1;

		return array(
			'3.2.0 on plugins_loaded'                    => array( '3.2.0', $before_init, false ),
			'3.2.0 during init priority 0'               => array( '3.2.0', $init_priority_0, false ),
			'3.2.0 during init priority 1'               => array( '3.2.0', $init_priority_1, false ),
			'3.2.0 during init priority 2'               => array( '3.2.0', $init_priority_2, true ),
			'3.2.0 after init'                           => array( '3.2.0', $after_init, true ),
			'3.5.4 during init priority 0'               => array( '3.5.4', $init_priority_0, false ),
			'3.5.4 after init'                           => array( '3.5.4', $after_init, true ),
			'4.1.0 during init priority 0, hook pending' => array( '4.1.0', $init_priority_0, false ),
			'4.1.0 during init priority 1, hook fired'   => array( '4.1.0', $as_init_fired, true ),
		);
	}

	/**
	 * @testdox when_ready defers on both hooks before init, runs once the copy is ready, and runs only once.
	 */
	public function test_when_ready_defers_until_the_copy_is_ready_and_runs_once(): void {
		$this->simulate_loaded_copy( '3.5.4' );
		$phase = array(
			'init_started'  => false,
			'doing_init'    => false,
			'init_priority' => false,
			'as_init_fired' => false,
		);
		$this->simulate_request_phase( $phase );
		$hooks = $this->capture_add_action_calls();

		$ran = 0;
		ActionSchedulerUtil::when_ready(
			function () use ( &$ran ) {
				++$ran;
			}
		);

		$this->assertSame( 0, $ran );
		$this->assertSame( array( 'action_scheduler_init', 'init' ), array_keys( $hooks() ) );
		$this->assertSame( 2, $hooks()['init']['priority'], 'The fallback should run after the data store init at priority 1' );

		$phase['init_started']  = true;
		$phase['doing_init']    = true;
		$phase['init_priority'] = 1;
		$hooks()['init']['callback']();
		$this->assertSame( 0, $ran, 'The init fallback should not run the callback while the copy is still not ready' );

		$phase['init_priority'] = 2;
		$hooks()['init']['callback']();
		$this->assertSame( 1, $ran, 'The init fallback should run the callback once the copy is ready' );

		$hooks()['action_scheduler_init']['callback']();
		$this->assertSame( 1, $ran, 'The callback should run only once' );
	}

	/**
	 * @testdox While init is running at priority 0 or 1, when_ready still registers the init fallback.
	 */
	public function test_when_ready_registers_the_init_fallback_while_init_is_running(): void {
		$this->simulate_loaded_copy( '3.2.0' );
		$phase = array(
			'init_started'  => true,
			'doing_init'    => true,
			'init_priority' => 0,
			'as_init_fired' => false,
		);
		$this->simulate_request_phase( $phase );
		$hooks = $this->capture_add_action_calls();

		$ran = 0;
		ActionSchedulerUtil::when_ready(
			function () use ( &$ran ) {
				++$ran;
			}
		);

		$this->assertSame( 0, $ran );
		$this->assertSame( array( 'action_scheduler_init', 'init' ), array_keys( $hooks() ) );
		$this->assertSame( 2, $hooks()['init']['priority'] );

		$phase['init_priority'] = 2;
		$hooks()['init']['callback']();
		$this->assertSame( 1, $ran );
	}

	/**
	 * @testdox When Action Scheduler is not loaded after init, when_ready waits for its init hook alone.
	 */
	public function test_when_ready_waits_for_the_init_hook_when_action_scheduler_loads_late(): void {
		$loaded = false;
		$this->register_legacy_proxy_function_mocks(
			array(
				'function_exists' => function ( string $name ) use ( &$loaded ) {
					return 0 === strpos( $name, 'as_' ) ? $loaded : function_exists( $name );
				},
			)
		);
		$phase = array(
			'init_started'  => true,
			'doing_init'    => false,
			'init_priority' => false,
			'as_init_fired' => false,
		);
		$this->simulate_request_phase( $phase );
		$hooks = $this->capture_add_action_calls();

		$ran = 0;
		ActionSchedulerUtil::when_ready(
			function () use ( &$ran ) {
				++$ran;
			}
		);

		$this->assertSame( 0, $ran );
		$this->assertSame( array( 'action_scheduler_init' ), array_keys( $hooks() ) );

		$loaded                 = true;
		$phase['as_init_fired'] = true;
		$hooks()['action_scheduler_init']['callback']();
		$this->assertSame( 1, $ran );
	}

	/**
	 * @testdox `has_scheduled_action` should return false when no matching action exists.
	 */
	public function test_returns_false_when_nothing_is_scheduled(): void {
		$this->assertFalse(
			ActionSchedulerUtil::has_scheduled_action( self::HOOK ),
			'An unscheduled hook should not be reported as scheduled'
		);
	}

	/**
	 * @testdox `has_scheduled_action` should return true for a pending action.
	 */
	public function test_returns_true_for_a_pending_action(): void {
		as_schedule_single_action( time() + HOUR_IN_SECONDS, self::HOOK, array(), self::GROUP );

		$this->assertTrue(
			ActionSchedulerUtil::has_scheduled_action( self::HOOK, array(), self::GROUP ),
			'A pending action should be reported as scheduled'
		);
	}

	/**
	 * @testdox `has_scheduled_action` should return true for an in-progress action.
	 */
	public function test_returns_true_for_an_in_progress_action(): void {
		$action_id = as_schedule_single_action( time() + HOUR_IN_SECONDS, self::HOOK, array(), self::GROUP );
		\ActionScheduler::store()->log_execution( $action_id );

		$this->assertTrue(
			ActionSchedulerUtil::has_scheduled_action( self::HOOK, array(), self::GROUP ),
			'An in-progress action should be reported as scheduled'
		);
	}

	/**
	 * @testdox `has_scheduled_action` should return false when only the group differs.
	 */
	public function test_returns_false_when_the_group_does_not_match(): void {
		as_schedule_single_action( time() + HOUR_IN_SECONDS, self::HOOK, array(), self::GROUP );

		$this->assertFalse(
			ActionSchedulerUtil::has_scheduled_action( self::HOOK, array(), 'some_other_group' ),
			'An action in a different group should not match'
		);
	}

	/**
	 * @testdox `has_scheduled_action` should return false when only the args differ.
	 */
	public function test_returns_false_when_the_args_do_not_match(): void {
		as_schedule_single_action( time() + HOUR_IN_SECONDS, self::HOOK, array( 'a' ), self::GROUP );

		$this->assertFalse(
			ActionSchedulerUtil::has_scheduled_action( self::HOOK, array( 'b' ), self::GROUP ),
			'An action scheduled with different args should not match'
		);
	}

	/**
	 * @testdox `has_scheduled_action` should treat null args as a wildcard.
	 */
	public function test_null_args_match_any_args(): void {
		as_schedule_single_action( time() + HOUR_IN_SECONDS, self::HOOK, array( 'a' ), self::GROUP );

		$this->assertTrue(
			ActionSchedulerUtil::has_scheduled_action( self::HOOK, null, self::GROUP ),
			'Null args should match an action scheduled with any args'
		);
	}

	/**
	 * @testdox `has_scheduled_action` should return false once the action has been unscheduled.
	 */
	public function test_returns_false_after_the_action_is_unscheduled(): void {
		as_schedule_single_action( time() + HOUR_IN_SECONDS, self::HOOK, array(), self::GROUP );
		as_unschedule_all_actions( self::HOOK, array(), self::GROUP );

		$this->assertFalse(
			ActionSchedulerUtil::has_scheduled_action( self::HOOK, array(), self::GROUP ),
			'An unscheduled action should no longer be reported as scheduled'
		);
	}

	/**
	 * With the bundled Action Scheduler the two functions answer identically, so the fallback the
	 * degrade path relies on is a no-op for every store running a current copy.
	 *
	 * @testdox The `as_next_scheduled_action` fallback agrees with `as_has_scheduled_action`.
	 * @dataProvider provider_fallback_equivalence
	 *
	 * @param array|null $scheduled_args Args to schedule the action with, or null to schedule nothing.
	 * @param array|null $queried_args   Args to query with.
	 * @param bool       $expected       Expected result.
	 */
	public function test_fallback_is_equivalent_to_the_preferred_function( ?array $scheduled_args, ?array $queried_args, bool $expected ): void {
		if ( null !== $scheduled_args ) {
			as_schedule_single_action( time() + HOUR_IN_SECONDS, self::HOOK, $scheduled_args, self::GROUP );
		}

		$this->assertSame(
			$expected,
			(bool) as_has_scheduled_action( self::HOOK, $queried_args, self::GROUP ),
			'as_has_scheduled_action should report the expected result'
		);
		$this->assertSame(
			$expected,
			(bool) as_next_scheduled_action( self::HOOK, $queried_args, self::GROUP ),
			'The as_next_scheduled_action fallback should report the same result'
		);
	}

	/**
	 * Data provider for test_fallback_is_equivalent_to_the_preferred_function.
	 *
	 * @return array
	 */
	public function provider_fallback_equivalence(): array {
		return array(
			'nothing scheduled'     => array( null, array(), false ),
			'exact args match'      => array( array( 'a' ), array( 'a' ), true ),
			'args mismatch'         => array( array( 'a' ), array( 'b' ), false ),
			'null args as wildcard' => array( array( 'a' ), null, true ),
		);
	}

	/**
	 * Make the util see a loaded Action Scheduler copy of the given version.
	 *
	 * Writes a plugin file with that version header into a temp directory and mocks, through the
	 * legacy proxy, every probe the util makes: which functions exist, how many parameters the
	 * loaded `as_schedule_single_action()` declares, and the loaded path.
	 *
	 * @param string      $version The version to simulate.
	 * @param string|null $path    The directory to report as the loaded path, or null for the temp directory.
	 */
	private function simulate_loaded_copy( string $version, ?string $path = null ): void {
		$dir        = $this->make_fake_copy( $version );
		$path       = $path ?? $dir;
		$functions  = array(
			'as_schedule_single_action' => version_compare( $version, '2.1.0', '>=' ),
			'as_next_scheduled_action'  => version_compare( $version, '2.1.0', '>=' ),
			'wc_schedule_single_action' => true,
			'as_enqueue_async_action'   => version_compare( $version, '3.0.0', '>=' ),
			'as_has_scheduled_action'   => version_compare( $version, '3.3.0', '>=' ),
		);
		$parameters = 4;
		if ( version_compare( $version, '3.6.0', '>=' ) ) {
			$parameters = 6;
		} elseif ( version_compare( $version, '3.5.0', '>=' ) ) {
			$parameters = 5;
		}

		$this->register_legacy_proxy_function_mocks( array( 'function_exists' => fn( string $name ) => $functions[ $name ] ?? function_exists( $name ) ) );
		$this->register_legacy_proxy_class_mocks( array( \ReflectionFunction::class => fn() => $this->reflection_with_parameters( $parameters ) ) );
		$this->register_legacy_proxy_static_mocks( array( 'ActionScheduler' => array( 'plugin_path' => fn( string $relative ) => $relative ? "$path/$relative" : $path ) ) );
		ActionSchedulerUtil::reset_cache();
	}

	/**
	 * Mock, through the legacy proxy, the request phase the util reads to answer is_ready().
	 *
	 * @param array $phase By reference, so tests can advance it. Keys: `init_started` (did_action('init')),
	 *                     `doing_init` (doing_action('init')), `init_priority` (WP_Hook::current_priority()
	 *                     for init, int or false) and `as_init_fired` (did_action('action_scheduler_init')).
	 */
	private function simulate_request_phase( array &$phase ): void {
		$this->register_legacy_proxy_function_mocks(
			array(
				'did_action'   => function ( string $hook ) use ( &$phase ) {
					if ( 'action_scheduler_init' === $hook ) {
						return (int) $phase['as_init_fired'];
					}
					return 'init' === $hook ? (int) $phase['init_started'] : did_action( $hook );
				},
				'doing_action' => function ( string $hook ) use ( &$phase ) {
					return 'init' === $hook ? $phase['doing_init'] : doing_action( $hook );
				},
			)
		);
		$this->register_legacy_proxy_global_mocks(
			array(
				'wp_filter' => array(
					'init' => new class( $phase ) {
						/**
						 * The simulated phase.
						 *
						 * @var array
						 */
						private array $phase;

						/**
						 * Constructor.
						 *
						 * @param array $phase The simulated phase, by reference.
						 */
						public function __construct( array &$phase ) {
							$this->phase = &$phase;
						}

						/**
						 * Mirror of WP_Hook::current_priority().
						 *
						 * @return int|false
						 */
						public function current_priority() {
							return $this->phase['init_priority'];
						}
					},
				),
			)
		);
	}

	/**
	 * Mock add_action() through the legacy proxy so registrations are recorded instead of made.
	 *
	 * @return callable Returns the recorded registrations: hook => array( 'callback' => callable, 'priority' => int ).
	 */
	private function capture_add_action_calls(): callable {
		$hooks = array();
		$this->register_legacy_proxy_function_mocks(
			array(
				'add_action' => function ( string $hook, callable $callback, int $priority = 10 ) use ( &$hooks ) {
					$hooks[ $hook ] = array(
						'callback' => $callback,
						'priority' => $priority,
					);
					return true;
				},
			)
		);

		return function () use ( &$hooks ) {
			return $hooks;
		};
	}

	/**
	 * A stand-in for the reflection of the loaded `as_schedule_single_action()`.
	 *
	 * @param int $count The number of parameters to report.
	 *
	 * @return object
	 */
	private function reflection_with_parameters( int $count ): object {
		return new class( $count ) {
			/**
			 * The number of parameters to report.
			 *
			 * @var int
			 */
			private int $count;

			/**
			 * Constructor.
			 *
			 * @param int $count The number of parameters to report.
			 */
			public function __construct( int $count ) {
				$this->count = $count;
			}

			/**
			 * Mirror of ReflectionFunction::getNumberOfParameters().
			 *
			 * @return int
			 */
			public function getNumberOfParameters(): int { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
				return $this->count;
			}
		};
	}

	/**
	 * Write a fake Action Scheduler plugin file with the given version header.
	 *
	 * @param string $version The version header value.
	 *
	 * @return string The directory containing the file, without a trailing slash.
	 */
	private function make_fake_copy( string $version ): string {
		$dir = untrailingslashit( get_temp_dir() ) . '/wc-as-util-' . wp_generate_password( 8, false );
		wp_mkdir_p( $dir );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $dir . '/action-scheduler.php', "<?php\n/**\n * Plugin Name: Action Scheduler\n * Version: {$version}\n */\n" );
		$this->fake_copies[] = $dir;

		return $dir;
	}

	/**
	 * Mock the Action Scheduler scheduling functions so calls are recorded instead of executed.
	 *
	 * @param bool $already_scheduled What the scheduled-action checks should report.
	 *
	 * @return callable Returns the recorded calls, each as the function name followed by its arguments.
	 */
	private function capture_scheduling_calls( bool $already_scheduled = false ): callable {
		$calls = array();
		$mocks = array(
			'as_has_scheduled_action'  => function ( ...$args ) use ( &$calls, $already_scheduled ) {
				$calls[] = array_merge( array( 'as_has_scheduled_action' ), $args );
				return $already_scheduled;
			},
			'as_next_scheduled_action' => function ( ...$args ) use ( &$calls, $already_scheduled ) {
				$calls[] = array_merge( array( 'as_next_scheduled_action' ), $args );
				return $already_scheduled ? time() + HOUR_IN_SECONDS : false;
			},
		);
		foreach ( array( 'as_enqueue_async_action', 'as_schedule_single_action', 'as_schedule_recurring_action', 'as_schedule_cron_action' ) as $function ) {
			$mocks[ $function ] = function ( ...$args ) use ( &$calls, $function ) {
				$calls[] = array_merge( array( $function ), $args );
				return 123;
			};
		}
		$this->register_legacy_proxy_function_mocks( $mocks );

		return function () use ( &$calls ) {
			return $calls;
		};
	}
}
