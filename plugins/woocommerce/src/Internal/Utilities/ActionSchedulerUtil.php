<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\WooCommerce\Enums\ActionSchedulerFeature;
use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Compatibility layer over whichever copy of Action Scheduler is loaded.
 *
 * Many plugins bundle Action Scheduler, and the copy that gets loaded is not always the newest one
 * registered, nor the one WooCommerce bundles. The functions answering our calls can therefore be older
 * than expected and lack features core relies on, such as the `unique` and `priority` arguments.
 *
 * In essence this class replaces as_enqueue_async_action(), as_schedule_single_action(),
 * as_schedule_recurring_action(), as_schedule_cron_action() and as_has_scheduled_action(). The wrappers
 * emulate or drop what the loaded copy cannot do. Scheduling calls that pass neither `unique` nor
 * `priority` can still go to Action Scheduler directly. The class also reports what is loaded, probes
 * the features core relies on ({@see ActionSchedulerFeature}) and says when the loaded copy is ready.
 *
 * The rest of the API has kept the same signature since Action Scheduler 2.1.0 and can be called
 * directly: as_unschedule_action(), as_unschedule_all_actions(), as_next_scheduled_action(),
 * as_get_scheduled_actions() and as_get_datetime_object(). The exception is as_supports(), which only
 * exists from 3.9.3. Use {@see ActionSchedulerUtil::supports()} instead.
 *
 * Every probe and call goes through {@see LegacyProxy}, so tests can simulate an older loaded copy.
 */
class ActionSchedulerUtil {

	/**
	 * The priority Action Scheduler assigns when none is given.
	 *
	 * @var int
	 */
	private const DEFAULT_PRIORITY = 10;

	/**
	 * The main file of the Action Scheduler copy bundled with WooCommerce, relative to WC_ABSPATH.
	 *
	 * @var string
	 */
	private const BUNDLED_PLUGIN_FILE = 'packages/action-scheduler/action-scheduler.php';

	/**
	 * The features {@see self::is_risky()} probes, in order. Kept here rather than in the enum
	 * because the list changes as core comes to rely on more of Action Scheduler.
	 *
	 * @var string[]
	 */
	private const PROBED_FEATURES = array(
		ActionSchedulerFeature::UNIQUE,
		ActionSchedulerFeature::PRIORITY,
		ActionSchedulerFeature::UNIQUE_ARGS,
		ActionSchedulerFeature::HAS_SCHEDULED_ACTION,
		ActionSchedulerFeature::ASYNC,
	);

	/**
	 * Per-request memo of feature probes, keyed by ActionSchedulerFeature value.
	 *
	 * @var array<string, bool>
	 */
	private static array $supports = array();

	/**
	 * Per-request memo of the loaded copy's version, or null while unknown.
	 *
	 * @var string|null
	 */
	private static ?string $loaded_version = null;

	/**
	 * Per-request memo of the bundled copy's version, or null while unread.
	 *
	 * @var string|null
	 */
	private static ?string $bundled_version = null;

	/**
	 * Per-request memo of the loaded copy's source, or null while unresolved.
	 *
	 * @var array|null
	 */
	private static ?array $loaded_source = null;

	/**
	 * Hooks for which a dropped priority has already been logged in this request.
	 *
	 * @var array<string, true>
	 */
	private static array $logged_priority_drops = array();

	/**
	 * Is the Action Scheduler procedural API loaded?
	 *
	 * True once a copy has initialized and defined the `as_*()` functions, which exist since 2.1.0.
	 * Before that, every other method reports "unknown" or "unsupported", and the wrappers refuse
	 * to schedule.
	 *
	 * @since 11.3.0
	 *
	 * @return bool
	 */
	public static function is_loaded(): bool {
		return self::function_exists( 'as_schedule_single_action' );
	}

	/**
	 * The version of the loaded Action Scheduler copy, read from its plugin file header.
	 *
	 * This is the copy answering calls, which can be older than the highest registered version.
	 *
	 * @since 11.3.0
	 *
	 * @return string|null The version, or null when no copy is loaded or its header cannot be read.
	 */
	public static function get_loaded_version(): ?string {
		if ( null === self::$loaded_version ) {
			$path = self::get_loaded_path();
			if ( null === $path ) {
				return null;
			}
			self::$loaded_version = self::read_version_header( $path . 'action-scheduler.php' );
		}

		return self::$loaded_version;
	}

	/**
	 * The highest Action Scheduler version registered with the version registry.
	 *
	 * The registry only knows which copies registered, not which one initialized, so this is for
	 * display next to {@see self::get_loaded_version()}, not for feature detection. When the two
	 * differ, a newer copy is installed but not the one answering calls.
	 *
	 * @since 11.3.0
	 *
	 * @return string|null The version, or null before `plugins_loaded` or when nothing registered.
	 */
	public static function get_highest_registered_version(): ?string {
		$registry = self::get_version_registry();
		$latest   = null === $registry ? null : $registry->latest_version();

		return is_string( $latest ) && '' !== $latest ? $latest : null;
	}

	/**
	 * The version of the Action Scheduler copy bundled with WooCommerce, read from its plugin file header.
	 *
	 * @since 11.3.0
	 *
	 * @return string|null The version, or null when the header cannot be read.
	 */
	public static function get_bundled_version(): ?string {
		if ( null === self::$bundled_version ) {
			self::$bundled_version = self::read_version_header( WC_ABSPATH . self::BUNDLED_PLUGIN_FILE );
		}

		return self::$bundled_version;
	}

	/**
	 * The directory of the loaded Action Scheduler copy, normalized and with a trailing slash.
	 *
	 * @since 11.3.0
	 *
	 * @return string|null The directory, or null when no copy has initialized.
	 */
	public static function get_loaded_path(): ?string {
		if ( ! self::has_initialized_copy() ) {
			return null;
		}

		$path = self::proxy()->call_static( 'ActionScheduler', 'plugin_path', '' );

		return is_string( $path ) && '' !== $path ? trailingslashit( wp_normalize_path( $path ) ) : null;
	}

	/**
	 * Which plugin, mu-plugin or theme the loaded Action Scheduler copy belongs to.
	 *
	 * Resolves the loaded directory against WooCommerce's own package, the mu-plugins directory, the
	 * plugins directory (reading that one plugin's headers) and the active theme, in that order.
	 *
	 * @since 11.3.0
	 *
	 * @return array {
	 *     @type string $type     One of 'woocommerce', 'mu-plugin', 'plugin', 'theme' or 'other'.
	 *     @type string $name     The display name of the owner, or an empty string when unknown.
	 *     @type string $basename The plugin basename, mu-plugin directory or theme slug, or an empty string.
	 * }
	 */
	public static function get_loaded_source(): array {
		if ( null === self::$loaded_source ) {
			$path = self::get_loaded_path();
			if ( null === $path ) {
				return self::source( 'other', '', '' );
			}
			self::$loaded_source = self::resolve_source( $path );
		}

		return self::$loaded_source;
	}

	/**
	 * Every Action Scheduler copy that registered with the version registry, keyed by directory.
	 *
	 * @since 11.3.0
	 *
	 * @return array<string, string> Directory => version. Empty before Action Scheduler 3.9.1, which added the registry's source tracking.
	 */
	public static function get_registered_sources(): array {
		$registry = self::get_version_registry();
		// PHPStan sees the bundled copy, where the method exists; copies before 3.9.1 lack it.
		// @phpstan-ignore-next-line function.alreadyNarrowedType -- see comment above.
		if ( null === $registry || ! method_exists( $registry, 'get_sources' ) ) {
			return array();
		}

		$sources = $registry->get_sources();

		return is_array( $sources ) ? $sources : array();
	}

	/**
	 * Does the loaded copy lack a feature core relies on?
	 *
	 * True as well when no copy is loaded, since then every feature is missing. Which feature is
	 * missing is answered by {@see self::supports()}.
	 *
	 * @since 11.3.0
	 *
	 * @return bool
	 */
	public static function is_risky(): bool {
		foreach ( self::PROBED_FEATURES as $feature ) {
			if ( ! self::supports( $feature ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Does the loaded copy support a feature?
	 *
	 * Presence is probed on the loaded symbols. `unique` and `priority` are probed by reflecting the
	 * parameters of the loaded `as_schedule_single_action()`, which is the only way to see the loaded
	 * copy rather than the registry. Answers are memoized per request once a copy is loaded.
	 *
	 * Most callers should use the wrappers and accept how they degrade. Ask this directly only when
	 * the degraded behaviour is not acceptable. For example, the deferred tracking of partial downloads
	 * in `WC_Download_Handler::track_download()` schedules a unique action with args from concurrent
	 * range requests, where the emulated check could double-count, so it should refuse to track when
	 * `UNIQUE` or `UNIQUE_ARGS` is unavailable instead of emulating.
	 *
	 * @since 11.3.0
	 *
	 * @param string $feature An ActionSchedulerFeature value.
	 *
	 * @return bool False for an unknown feature, and for every feature while no copy is loaded.
	 */
	public static function supports( string $feature ): bool {
		if ( ! self::is_loaded() ) {
			return false;
		}

		if ( ! array_key_exists( $feature, self::$supports ) ) {
			self::$supports[ $feature ] = self::probe( $feature );
		}

		return self::$supports[ $feature ];
	}

	/**
	 * Can the loaded copy accept scheduling calls right now?
	 *
	 * Every copy initializes its data store on `init` at priority 1 (or at once, when it loads after
	 * `init` has fired), and calls before that return 0 or hit the wrong store. Copies from 3.6.0
	 * fire `action_scheduler_init` at that point. Older copies cannot say, so for them ready means
	 * `init` has passed priority 1. `ActionScheduler::is_initialized()` is no help: before 3.6.0 it
	 * is set when `ActionScheduler::init()` runs on `plugins_loaded`, not when the store is ready.
	 *
	 * @since 11.3.0
	 *
	 * @return bool
	 */
	public static function is_ready(): bool {
		if ( ! self::is_loaded() ) {
			return false;
		}

		if ( self::did_action( 'action_scheduler_init' ) ) {
			return true;
		}

		if ( ! self::did_action( 'init' ) ) {
			return false;
		}

		return ! self::proxy()->call_function( 'doing_action', 'init' ) || self::current_init_priority() > 1;
	}

	/**
	 * Run a callback now if the loaded copy is ready, otherwise once it becomes ready.
	 *
	 * Deferred callbacks run on `action_scheduler_init` (copies from 3.6.0) or on `init` at priority
	 * 2 (older copies finish initializing at priority 1), whichever comes first, and only once. The
	 * `init` fallback is also registered while `init` is running at priority 0 or 1, which WP_Hook
	 * honours by re-sorting the running iteration. A copy older than 3.6.0 that loads after `init`
	 * has finished never triggers either, and the callback is lost.
	 *
	 * @since 11.3.0
	 *
	 * @param callable $callback The callback to run. It receives no arguments.
	 *
	 * @return void
	 */
	public static function when_ready( callable $callback ): void {
		if ( self::is_ready() ) {
			$callback();
			return;
		}

		$pending  = true;
		$run_once = function () use ( &$pending, $callback ): void {
			if ( $pending && self::is_ready() ) {
				$pending = false;
				$callback();
			}
		};

		self::proxy()->call_function( 'add_action', 'action_scheduler_init', $run_once );

		if ( ! self::did_action( 'init' ) || self::proxy()->call_function( 'doing_action', 'init' ) ) {
			self::proxy()->call_function( 'add_action', 'init', $run_once, 2 );
		}
	}

	/**
	 * Enqueue an action to run as soon as possible, like `as_enqueue_async_action()`.
	 *
	 * On a loaded copy without `as_enqueue_async_action()` (before 3.0.0), the action is scheduled
	 * for now with `as_schedule_single_action()` instead, which runs it slightly later.
	 * See {@see self::schedule_single()} for how `$unique` and `$priority` degrade.
	 *
	 * @since 11.3.0
	 *
	 * @param string $hook     The hook to trigger.
	 * @param array  $args     Arguments to pass when the hook triggers.
	 * @param string $group    The group to assign this job to.
	 * @param bool   $unique   Whether the action should be unique.
	 * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
	 *
	 * @return int The action ID. Zero if the action was not scheduled, including when a unique action already exists.
	 */
	public static function enqueue_async( string $hook, array $args = array(), string $group = '', bool $unique = false, int $priority = self::DEFAULT_PRIORITY ): int {
		if ( self::supports( ActionSchedulerFeature::ASYNC ) ) {
			return self::schedule( __FUNCTION__, 'as_enqueue_async_action', array(), $hook, $args, $group, $unique, $priority );
		}

		return self::schedule( __FUNCTION__, 'as_schedule_single_action', array( time() ), $hook, $args, $group, $unique, $priority );
	}

	/**
	 * Schedule an action to run once at some time in the future, like `as_schedule_single_action()`.
	 *
	 * Same arguments and return value as the Action Scheduler function, with these degradations on
	 * an older loaded copy:
	 *
	 * - `$unique` on a copy without it (before 3.5.0), or with non-empty `$args` on a copy that
	 *   checks uniqueness by hook alone (before 4.0.0): emulated by checking
	 *   {@see self::has_scheduled_action()} first, which returns 0 without scheduling when a match
	 *   exists. The check is not atomic, so two concurrent requests can both schedule; on copies
	 *   before 3.0.0 it sees pending actions only, so an action can be scheduled again while a
	 *   previous run is in progress; and with an empty `$group` it matches a pending action in any
	 *   group, where native unique matches only ungrouped actions.
	 * - `$priority` on a copy without it (before 3.6.0): dropped, so the action takes the default
	 *   priority, with a debug-level log entry (source `action-scheduler`) once per request per hook.
	 *
	 * When no copy is loaded, nothing is scheduled: returns 0 and raises a doing-it-wrong notice.
	 * When a copy is loaded but not ready yet, the call is not deferred; see {@see self::when_ready()}.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $timestamp When the job will run.
	 * @param string $hook      The hook to trigger.
	 * @param array  $args      Arguments to pass when the hook triggers.
	 * @param string $group     The group to assign this job to.
	 * @param bool   $unique    Whether the action should be unique.
	 * @param int    $priority  Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
	 *
	 * @return int The action ID. Zero if the action was not scheduled, including when a unique action already exists.
	 */
	public static function schedule_single( int $timestamp, string $hook, array $args = array(), string $group = '', bool $unique = false, int $priority = self::DEFAULT_PRIORITY ): int {
		return self::schedule( __FUNCTION__, 'as_schedule_single_action', array( $timestamp ), $hook, $args, $group, $unique, $priority );
	}

	/**
	 * Schedule a recurring action, like `as_schedule_recurring_action()`.
	 *
	 * See {@see self::schedule_single()} for how `$unique` and `$priority` degrade on an older loaded copy.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $timestamp When the first instance of the job will run.
	 * @param int    $interval  How long to wait between runs, in seconds.
	 * @param string $hook      The hook to trigger.
	 * @param array  $args      Arguments to pass when the hook triggers.
	 * @param string $group     The group to assign this job to.
	 * @param bool   $unique    Whether the action should be unique.
	 * @param int    $priority  Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
	 *
	 * @return int The action ID. Zero if the action was not scheduled, including when a unique action already exists.
	 */
	public static function schedule_recurring( int $timestamp, int $interval, string $hook, array $args = array(), string $group = '', bool $unique = false, int $priority = self::DEFAULT_PRIORITY ): int {
		return self::schedule( __FUNCTION__, 'as_schedule_recurring_action', array( $timestamp, $interval ), $hook, $args, $group, $unique, $priority );
	}

	/**
	 * Schedule an action that recurs on a cron-like schedule, like `as_schedule_cron_action()`.
	 *
	 * See {@see self::schedule_single()} for how `$unique` and `$priority` degrade on an older loaded copy.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $timestamp The first instance of the action will be scheduled on or after this time.
	 * @param string $schedule  A cron-link schedule string.
	 * @param string $hook      The hook to trigger.
	 * @param array  $args      Arguments to pass when the hook triggers.
	 * @param string $group     The group to assign this job to.
	 * @param bool   $unique    Whether the action should be unique.
	 * @param int    $priority  Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
	 *
	 * @return int The action ID. Zero if the action was not scheduled, including when a unique action already exists.
	 */
	public static function schedule_cron( int $timestamp, string $schedule, string $hook, array $args = array(), string $group = '', bool $unique = false, int $priority = self::DEFAULT_PRIORITY ): int {
		return self::schedule( __FUNCTION__, 'as_schedule_cron_action', array( $timestamp, $schedule ), $hook, $args, $group, $unique, $priority );
	}

	/**
	 * Is a matching action currently scheduled (pending or in-progress)?
	 *
	 * Prefers `as_has_scheduled_action`, which only exists since Action Scheduler 3.3.0. Falls back
	 * to the much older `as_next_scheduled_action`, which also reports in-progress actions from 3.0.0;
	 * copies before that see pending actions only - an accepted trade.
	 *
	 * Reports false when Action Scheduler is not loaded at all, indistinguishable from "nothing is
	 * scheduled", and raises a doing-it-wrong notice so the condition is visible under debugging.
	 * A caller that treats a negative answer as licence to discard state should check
	 * {@see self::can_check_scheduled_actions()} first.
	 *
	 * @since 11.2.0
	 *
	 * @param string     $hook  The hook of the action.
	 * @param array|null $args  Args that have been passed to the action. Null matches any args.
	 * @param string     $group The group the action is assigned to.
	 *
	 * @return bool True if a matching action is scheduled, false otherwise.
	 */
	public static function has_scheduled_action( string $hook, ?array $args = null, string $group = '' ): bool {
		if ( self::supports( ActionSchedulerFeature::HAS_SCHEDULED_ACTION ) ) {
			return (bool) self::proxy()->call_function( 'as_has_scheduled_action', $hook, $args, $group );
		}

		if ( self::function_exists( 'as_next_scheduled_action' ) ) {
			// Returns the timestamp of the next pending action, or true for an in-progress one, hence the cast.
			return (bool) self::proxy()->call_function( 'as_next_scheduled_action', $hook, $args, $group );
		}

		wc_doing_it_wrong(
			__METHOD__,
			'Action Scheduler is not loaded, so scheduled actions cannot be checked. Call this after Action Scheduler has initialized.',
			'11.2.0'
		);

		return false;
	}

	/**
	 * Can {@see self::has_scheduled_action()} actually query Action Scheduler, or would it report false
	 * only because neither function it relies on is loaded?
	 *
	 * @since 11.2.0
	 *
	 * @return bool True if a scheduled-action check can be answered, false otherwise.
	 */
	public static function can_check_scheduled_actions(): bool {
		return self::function_exists( 'as_has_scheduled_action' ) || self::function_exists( 'as_next_scheduled_action' );
	}

	/**
	 * Forget every memoized answer, so the next call probes the loaded copy again.
	 *
	 * @internal For tests that simulate different loaded copies within one request.
	 *
	 * @since 11.3.0
	 *
	 * @return void
	 */
	public static function reset_cache(): void {
		self::$supports              = array();
		self::$loaded_version        = null;
		self::$bundled_version       = null;
		self::$loaded_source         = null;
		self::$logged_priority_drops = array();
	}

	/**
	 * Schedule through the loaded copy, applying the degradations documented on {@see self::schedule_single()}.
	 *
	 * @param string $method       The public wrapper being served, for the doing-it-wrong notice.
	 * @param string $function_name The Action Scheduler function to call.
	 * @param array  $leading_args The arguments the function takes before the hook (timestamp, interval, schedule).
	 * @param string $hook         The hook to trigger.
	 * @param array  $args         Arguments to pass when the hook triggers.
	 * @param string $group        The group to assign this job to.
	 * @param bool   $unique       Whether the action should be unique.
	 * @param int    $priority     The requested priority.
	 *
	 * @return int The action ID, or 0.
	 */
	private static function schedule( string $method, string $function_name, array $leading_args, string $hook, array $args, string $group, bool $unique, int $priority ): int {
		if ( ! self::is_loaded() ) {
			wc_doing_it_wrong(
				__CLASS__ . '::' . $method,
				'Action Scheduler is not loaded, so the action cannot be scheduled. Call this after Action Scheduler has initialized.',
				'11.3.0'
			);

			return 0;
		}

		$native_unique = $unique && self::supports( ActionSchedulerFeature::UNIQUE ) && ( empty( $args ) || self::supports( ActionSchedulerFeature::UNIQUE_ARGS ) );

		if ( $unique && ! $native_unique && self::has_scheduled_action( $hook, $args, $group ) ) {
			return 0;
		}

		$call_args = array_merge( $leading_args, array( $hook, $args, $group ) );

		if ( self::supports( ActionSchedulerFeature::UNIQUE ) ) {
			$call_args[] = $native_unique;
		}

		if ( self::supports( ActionSchedulerFeature::PRIORITY ) ) {
			$call_args[] = $priority;
		} elseif ( self::DEFAULT_PRIORITY !== $priority ) {
			self::log_dropped_priority( $hook, $priority );
		}

		return (int) self::proxy()->call_function( $function_name, ...$call_args );
	}

	/**
	 * Record, once per request per hook, that a requested priority could not be applied.
	 *
	 * @param string $hook     The hook of the action.
	 * @param int    $priority The priority that was requested.
	 *
	 * @return void
	 */
	private static function log_dropped_priority( string $hook, int $priority ): void {
		if ( isset( self::$logged_priority_drops[ $hook ] ) ) {
			return;
		}
		self::$logged_priority_drops[ $hook ] = true;

		self::proxy()->call_function( 'wc_get_logger' )->debug(
			sprintf(
				'Priority %1$d for action "%2$s" was dropped: the loaded Action Scheduler (%3$s) does not support priorities, so the action was scheduled at the default priority.',
				$priority,
				$hook,
				self::get_loaded_version() ?? 'unknown version'
			),
			array( 'source' => 'action-scheduler' )
		);
	}

	/**
	 * Probe one feature on the loaded copy.
	 *
	 * @param string $feature An ActionSchedulerFeature value.
	 *
	 * @return bool
	 */
	private static function probe( string $feature ): bool {
		switch ( $feature ) {
			case ActionSchedulerFeature::UNIQUE:
				return self::count_scheduling_parameters() >= 5;
			case ActionSchedulerFeature::PRIORITY:
				return self::count_scheduling_parameters() >= 6;
			case ActionSchedulerFeature::UNIQUE_ARGS:
				$version = self::get_loaded_version();
				return self::supports( ActionSchedulerFeature::UNIQUE ) && null !== $version && version_compare( $version, '4.0.0', '>=' );
			case ActionSchedulerFeature::HAS_SCHEDULED_ACTION:
				return self::function_exists( 'as_has_scheduled_action' );
			case ActionSchedulerFeature::ASYNC:
				return self::function_exists( 'as_enqueue_async_action' );
		}

		return false;
	}

	/**
	 * How many parameters the loaded `as_schedule_single_action()` declares: 4 before 3.5.0, 5 from 3.5.0, 6 from 3.6.0.
	 *
	 * @return int
	 */
	private static function count_scheduling_parameters(): int {
		return self::proxy()->get_instance_of( \ReflectionFunction::class, 'as_schedule_single_action' )->getNumberOfParameters();
	}

	/**
	 * Has any copy run `ActionScheduler::init()`, which sets the plugin path and loads the functions?
	 *
	 * A copy older than 2.1.0 defines the `wc_*()` functions instead of the `as_*()` ones; it still
	 * has a version and a path worth reporting.
	 *
	 * @return bool
	 */
	private static function has_initialized_copy(): bool {
		return self::function_exists( 'as_schedule_single_action' ) || self::function_exists( 'wc_schedule_single_action' );
	}

	/**
	 * The version registry, when its class is loaded and `plugins_loaded` has run, so that it is complete.
	 *
	 * @return \ActionScheduler_Versions|null
	 */
	private static function get_version_registry(): ?\ActionScheduler_Versions {
		if ( ! self::did_action( 'plugins_loaded' ) || ! self::proxy()->call_function( 'class_exists', 'ActionScheduler_Versions' ) ) {
			return null;
		}

		$registry = self::proxy()->call_static( 'ActionScheduler_Versions', 'instance' );

		return $registry instanceof \ActionScheduler_Versions ? $registry : null;
	}

	/**
	 * Read the `Version` header of a plugin file.
	 *
	 * @param string $file The absolute path of the file.
	 *
	 * @return string|null The version, or null when the file is unreadable or has no version header.
	 */
	private static function read_version_header( string $file ): ?string {
		if ( ! is_readable( $file ) ) {
			return null;
		}

		$data    = get_file_data( $file, array( 'Version' => 'Version' ) );
		$version = trim( (string) ( $data['Version'] ?? '' ) );

		return '' === $version ? null : $version;
	}

	/**
	 * Resolve which plugin, mu-plugin or theme a directory belongs to.
	 *
	 * @param string $path The loaded copy's directory, normalized and with a trailing slash.
	 *
	 * @return array See {@see self::get_loaded_source()}.
	 */
	private static function resolve_source( string $path ): array {
		if ( null !== self::relative_to( $path, WC_ABSPATH . 'packages/action-scheduler' ) ) {
			return self::source( 'woocommerce', 'WooCommerce', plugin_basename( WC_PLUGIN_FILE ) );
		}

		$path = self::map_real_path( $path );

		$relative = self::relative_to( $path, WPMU_PLUGIN_DIR );
		if ( null !== $relative ) {
			$directory = (string) strtok( $relative, '/' );

			return self::source( 'mu-plugin', $directory, $directory );
		}

		$relative = self::relative_to( $path, WP_PLUGIN_DIR );
		if ( null !== $relative ) {
			$directory = (string) strtok( $relative, '/' );
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			foreach ( (array) self::proxy()->call_function( 'get_plugins', '/' . $directory ) as $file => $data ) {
				if ( ! empty( $data['Name'] ) ) {
					return self::source( 'plugin', (string) $data['Name'], $directory . '/' . $file );
				}
			}

			return self::source( 'plugin', $directory, $directory );
		}

		$themes = array(
			get_stylesheet() => get_stylesheet_directory(),
			get_template()   => get_template_directory(),
		);
		foreach ( $themes as $slug => $directory ) {
			if ( null !== self::relative_to( $path, $directory ) ) {
				$theme = wp_get_theme( $slug );

				return self::source( 'theme', $theme->exists() ? (string) $theme->get( 'Name' ) : $slug, $slug );
			}
		}

		return self::source( 'other', '', '' );
	}

	/**
	 * Map a real path back to the symlinked plugin path WordPress knows it by, as plugin_basename() does.
	 *
	 * `ActionScheduler::plugin_path()` comes from `__FILE__`, which PHP resolves to the real path, while
	 * the plugin directory constants are not resolved. `$wp_plugin_paths` records the mapping for every
	 * active plugin and mu-plugin.
	 *
	 * @param string $path A normalized path with a trailing slash.
	 *
	 * @return string
	 */
	private static function map_real_path( string $path ): string {
		$plugin_paths = self::proxy()->get_global( 'wp_plugin_paths' );
		if ( ! is_array( $plugin_paths ) ) {
			return $path;
		}

		arsort( $plugin_paths );
		foreach ( $plugin_paths as $dir => $real_dir ) {
			$real_dir = trailingslashit( wp_normalize_path( (string) $real_dir ) );
			if ( 0 === strpos( $path, $real_dir ) ) {
				return trailingslashit( wp_normalize_path( (string) $dir ) ) . substr( $path, strlen( $real_dir ) );
			}
		}

		return $path;
	}

	/**
	 * The priority `init` is currently running at, or 0 when it is not running.
	 *
	 * @return int
	 */
	private static function current_init_priority(): int {
		$wp_filter = self::proxy()->get_global( 'wp_filter' );
		$hook      = is_array( $wp_filter ) && isset( $wp_filter['init'] ) ? $wp_filter['init'] : null;
		$priority  = is_object( $hook ) && is_callable( array( $hook, 'current_priority' ) ) ? $hook->current_priority() : false;

		return is_numeric( $priority ) ? (int) $priority : 0;
	}

	/**
	 * The part of a path below a directory, or null when the path is not inside it.
	 *
	 * @param string $path      A normalized path with a trailing slash.
	 * @param string $directory The directory, in any form.
	 *
	 * @return string|null
	 */
	private static function relative_to( string $path, string $directory ): ?string {
		$directory = trailingslashit( wp_normalize_path( $directory ) );

		return 0 === strpos( $path, $directory ) ? (string) substr( $path, strlen( $directory ) ) : null;
	}

	/**
	 * Build a source descriptor.
	 *
	 * @param string $type     The source type.
	 * @param string $name     The display name.
	 * @param string $basename The plugin basename, mu-plugin directory or theme slug.
	 *
	 * @return array
	 */
	private static function source( string $type, string $name, string $basename ): array {
		return array(
			'type'     => $type,
			'name'     => $name,
			'basename' => $basename,
		);
	}

	/**
	 * Has an action fired, asked through the proxy so tests can simulate request phases?
	 *
	 * @param string $hook The action hook.
	 *
	 * @return bool
	 */
	private static function did_action( string $hook ): bool {
		return (bool) self::proxy()->call_function( 'did_action', $hook );
	}

	/**
	 * Does a function exist, asked through the proxy so tests can simulate an older copy?
	 *
	 * @param string $function_name The function name.
	 *
	 * @return bool
	 */
	private static function function_exists( string $function_name ): bool {
		return (bool) self::proxy()->call_function( 'function_exists', $function_name );
	}

	/**
	 * The legacy proxy every probe and Action Scheduler call goes through.
	 *
	 * @return LegacyProxy
	 */
	private static function proxy(): LegacyProxy {
		return wc_get_container()->get( LegacyProxy::class );
	}
}
