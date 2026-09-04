<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Services;

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Error;
use Throwable;
use WC_Logger_Interface;

/**
 * Reports which push notification drivers are installed and whether push
 * notifications can be sent through them.
 *
 * A "driver" is a mechanism through which the mobile apps can receive
 * notifications: the legacy Jetpack Sync flow, or the remote push notification
 * proxy provided by this module. For each driver this resolves the dependencies
 * it needs (Jetpack connection, feature flag, Jetpack Sync state) into
 * `connected`, `enabled`, and `available` flags, and determines which driver the
 * site prefers.
 *
 * @since 11.2.0
 */
class DriverAvailabilityService {
	/**
	 * Driver ID for the legacy Jetpack Sync notification flow.
	 */
	const DRIVER_JETPACK_SYNC = 'jetpack-sync';

	/**
	 * Driver ID for the remote push notification proxy provided by this module.
	 */
	const DRIVER_REMOTE_PROXY = 'remote-push-notification-proxy';

	/**
	 * The Jetpack Sync settings class, checked/called defensively as the
	 * package is only present at runtime when Jetpack Sync is installed.
	 */
	const JETPACK_SYNC_SETTINGS_CLASS = 'Automattic\\Jetpack\\Sync\\Settings';

	/**
	 * The Jetpack plugin's main class.
	 *
	 * The Jetpack Sync driver needs the Jetpack plugin itself, not merely the
	 * `jetpack-sync` package. Other plugins bundle that package without Jetpack,
	 * and on those stores the package is present while nothing can actually
	 * deliver a notification through it.
	 */
	const JETPACK_PLUGIN_CLASS = 'Jetpack';

	/**
	 * Drivers in precedence order: the first available one is the preferred
	 * driver. The remote proxy is preferred over Jetpack Sync when both are
	 * available.
	 */
	const DRIVER_PRECEDENCE = array(
		self::DRIVER_REMOTE_PROXY,
		self::DRIVER_JETPACK_SYNC,
	);

	/**
	 * Identifies the blog connection check, which the remote proxy driver uses.
	 */
	private const CHECK_BLOG_CONNECTION = 'blog';

	/**
	 * Identifies the user connection check, which the Jetpack Sync driver uses.
	 */
	private const CHECK_USER_CONNECTION = 'user';

	/**
	 * Identifies the Jetpack Sync enabled check.
	 */
	private const CHECK_SYNC_ENABLED = 'sync_enabled';

	/**
	 * Identifies the remote proxy enabled check, which reads a filter.
	 */
	private const CHECK_PROXY_ENABLED = 'proxy_enabled';

	/**
	 * Connection checks that threw rather than answering, keyed by check.
	 *
	 * Distinguishes "the merchant has not connected Jetpack", which is
	 * actionable by the merchant, from "the connection state could not be
	 * determined", which is not. Both otherwise present as `connected: false`.
	 *
	 * Recorded per check rather than as one flag because the drivers ask
	 * different questions: the remote proxy needs a blog connection and Jetpack
	 * Sync needs a connected owner. Either can fail while the other answers, so
	 * a failure must only affect the flag it actually relates to, which is
	 * reported as null.
	 *
	 * Reset at the start of {@see self::get_status()}. The container shares one
	 * instance of this service, so state left here would otherwise outlive the
	 * request that produced it and go stale in a long-lived process.
	 *
	 * @var array<string, bool>
	 */
	private array $failed_checks = array();

	/**
	 * Builds the driver status: the installed drivers with their connected,
	 * enabled, and available flags, and which driver the site prefers.
	 *
	 * Property names are snake_case per REST convention; the driver identifiers
	 * used as keys within `installed_drivers` are kebab-case slugs.
	 *
	 * A flag is null when its check could not be performed, as distinct from false
	 * meaning the check ran and answered no.
	 *
	 * @return array{installed_drivers: array<string, array{connected: bool|null, enabled: bool|null, available: bool}>, preferred_driver: string|null}
	 *
	 * @since 11.2.0
	 */
	public function get_status(): array {
		$this->failed_checks = array();

		$installed_drivers = array();

		if ( $this->is_jetpack_sync_installed() ) {
			$installed_drivers[ self::DRIVER_JETPACK_SYNC ] = $this->driver_status(
				$this->resolve( $this->has_user_connection(), self::CHECK_USER_CONNECTION ),
				$this->resolve( $this->is_jetpack_sync_enabled(), self::CHECK_SYNC_ENABLED )
			);
		}

		// The remote proxy driver ships with this module, so it is always present.
		$installed_drivers[ self::DRIVER_REMOTE_PROXY ] = $this->driver_status(
			$this->resolve( $this->has_blog_connection(), self::CHECK_BLOG_CONNECTION ),
			$this->resolve( $this->is_remote_proxy_enabled(), self::CHECK_PROXY_ENABLED )
		);

		return array(
			'installed_drivers' => $installed_drivers,
			'preferred_driver'  => $this->get_preferred_driver( $installed_drivers ),
		);
	}

	/**
	 * Builds a single driver's status flags. A driver is available (usable now)
	 * only when it is both connected and enabled.
	 *
	 * A driver is only available when both flags are definitively true. An
	 * undetermined flag is not usable, so null makes the driver unavailable in the
	 * same way false does.
	 *
	 * @param bool|null $connected Whether the driver's underlying connection is present, or null if undetermined.
	 * @param bool|null $enabled   Whether the driver itself is switched on, or null if undetermined.
	 * @return array{connected: bool|null, enabled: bool|null, available: bool}
	 */
	private function driver_status( ?bool $connected, ?bool $enabled ): array {
		return array(
			'connected' => $connected,
			'enabled'   => $enabled,
			'available' => true === $connected && true === $enabled,
		);
	}

	/**
	 * Reports a check's result as null when the check could not be performed.
	 *
	 * The check methods return false on failure so that the gating callers, such
	 * as {@see self::is_remote_proxy_available()}, stay boolean and fail closed.
	 * The status response wants the distinction, so it is recovered here.
	 *
	 * @param bool   $result    The value the check returned.
	 * @param string $check_key The check to look up in {@see self::$failed_checks}.
	 * @return bool|null
	 */
	private function resolve( bool $result, string $check_key ): ?bool {
		return empty( $this->failed_checks[ $check_key ] ) ? $result : null;
	}

	/**
	 * Determines whether push notifications can currently be sent through the
	 * remote proxy driver: the feature must be enabled and Jetpack connected.
	 * This is the proxy driver's availability, which also gates the module.
	 *
	 * @return bool
	 *
	 * @since 11.2.0
	 */
	public function is_remote_proxy_available(): bool {
		return $this->is_remote_proxy_enabled() && $this->has_blog_connection();
	}

	/**
	 * Determines which driver the site prefers: the first available driver in
	 * {@see self::DRIVER_PRECEDENCE} order (remote proxy before Jetpack Sync).
	 *
	 * This is the site's preference, not a statement about what is delivering
	 * notifications to a given app. That depends on the app version and on
	 * whether its token registered successfully, neither of which is known here.
	 *
	 * @param array<string, array{connected: bool|null, enabled: bool|null, available: bool}> $installed_drivers The installed drivers.
	 * @return string|null The preferred driver id, or null when none are available.
	 */
	private function get_preferred_driver( array $installed_drivers ): ?string {
		foreach ( self::DRIVER_PRECEDENCE as $driver ) {
			if ( ! empty( $installed_drivers[ $driver ]['available'] ) ) {
				return $driver;
			}
		}

		return null;
	}

	/**
	 * Determines whether the remote proxy driver is enabled (i.e. the feature is
	 * not disabled via the filter), a dependency of that driver.
	 *
	 * @return bool
	 *
	 * @since 11.2.0
	 */
	protected function is_remote_proxy_enabled(): bool {
		try {
			return $this->read_disabled_filter();
		} catch ( Throwable $e ) {
			$this->failed_checks[ self::CHECK_PROXY_ENABLED ] = true;

			$this->log_throwable( 'Error reading the push notifications disabled filter', $e );

			// Fail closed: an unreadable filter must not enable the feature.
			return false;
		}
	}

	/**
	 * Reads the disable filter.
	 *
	 * Split out so the filter call, which runs arbitrary third-party callbacks, sits
	 * behind the same guard as every other foreign call in this class. It is reached
	 * on every request through {@see self::is_remote_proxy_available()}, so an
	 * escaping throwable here is a fatal on every page load rather than one bad
	 * endpoint response.
	 *
	 * @return bool
	 */
	private function read_disabled_filter(): bool {
		return ! wc_string_to_bool(
			/**
			 * Filters whether enhanced push notifications should be disabled.
			 *
			 * The feature was previously controlled by a now-deprecated feature
			 * flag. It is now enabled by default for all compatible users, but this
			 * filter lets a store force it off (e.g. to fall back to Jetpack Sync
			 * if something isn't working). The feature also requires a Jetpack
			 * connection, which is checked separately.
			 *
			 * @since 10.9.2
			 *
			 * @param bool $disabled Whether enhanced push notifications are disabled. Defaults to false.
			 */
			apply_filters( 'woocommerce_enhanced_push_notifications_disabled', false )
		);
	}

	/**
	 * Determines whether the Jetpack Sync driver is installed.
	 *
	 * Requires the Jetpack plugin as well as the sync package: the package alone
	 * can be supplied by an unrelated plugin, in which case there is no Jetpack
	 * Sync flow for the apps to fall back to.
	 *
	 * @return bool
	 *
	 * @since 11.2.0
	 */
	protected function is_jetpack_sync_installed(): bool {
		return $this->class_is_present( self::JETPACK_PLUGIN_CLASS )
			&& $this->class_is_present( self::JETPACK_SYNC_SETTINGS_CLASS );
	}

	/**
	 * Whether a class is loadable.
	 *
	 * A seam so the driver-detection rule can be tested across every combination of
	 * plugin and package presence. Neither class exists in the test environment, so
	 * without this a test can only assert the implementation against itself.
	 *
	 * @param string $class_name The fully qualified class name.
	 * @return bool
	 *
	 * @since 11.2.0
	 */
	protected function class_is_present( string $class_name ): bool {
		return class_exists( $class_name );
	}

	/**
	 * Determines whether Jetpack Sync is enabled. Treated as not enabled when
	 * the package isn't installed, or when the package throws.
	 *
	 * The call reaches third-party package code, so it is guarded: an `Error`
	 * from an incompatible Jetpack would otherwise escape
	 * {@see \Automattic\WooCommerce\Internal\RestApiControllerBase::run()},
	 * which catches `Exception` only, and turn an endpoint whose purpose is to
	 * stay reachable into a 500.
	 *
	 * @return bool
	 *
	 * @since 11.2.0
	 */
	protected function is_jetpack_sync_enabled(): bool {
		$is_sync_enabled = array( self::JETPACK_SYNC_SETTINGS_CLASS, 'is_sync_enabled' );

		if ( ! is_callable( $is_sync_enabled ) ) {
			// class_exists() already passed, so the class is present but the method
			// is not: an incompatible Jetpack Sync rather than a merchant choice.
			// Recorded so this reports as undetermined rather than a definitive no.
			$this->failed_checks[ self::CHECK_SYNC_ENABLED ] = true;

			return false;
		}

		try {
			return (bool) call_user_func( $is_sync_enabled );
		} catch ( Throwable $e ) {
			$this->failed_checks[ self::CHECK_SYNC_ENABLED ] = true;

			$this->log_throwable( 'Error determining Jetpack Sync state for push notifications', $e );

			return false;
		}
	}

	/**
	 * Determines whether the site has an active Jetpack blog connection, which
	 * the remote proxy driver requires.
	 *
	 * @return bool
	 *
	 * @since 11.2.0
	 */
	protected function has_blog_connection(): bool {
		return $this->query_jetpack_connection(
			self::CHECK_BLOG_CONNECTION,
			static fn ( JetpackConnectionManager $manager ): bool => $manager->is_connected()
		);
	}

	/**
	 * Determines whether the site has a connected Jetpack user (owner), which
	 * the Jetpack Sync driver requires.
	 *
	 * @return bool
	 *
	 * @since 11.2.0
	 */
	protected function has_user_connection(): bool {
		return $this->query_jetpack_connection(
			self::CHECK_USER_CONNECTION,
			static fn ( JetpackConnectionManager $manager ): bool => $manager->has_connected_owner()
		);
	}

	/**
	 * Runs the given check against the Jetpack connection manager, returning
	 * false (and logging) if the connection state can't be determined.
	 *
	 * A failure is recorded against $check_key on {@see self::$failed_checks} as
	 * well as returning false, so the driver that depends on this check can tell
	 * an unconnected store from one whose connection state could not be read.
	 *
	 * Deliberately not memoized. The container hands out one shared instance of
	 * this service, so a cached result would outlive the caller that asked for
	 * it and go stale in any long-lived process (WP-CLI, cron), where the
	 * Jetpack connection can change under it.
	 *
	 * @param string                                   $check_key Identifies the check, so a failure is recorded against the driver that depends on it.
	 * @param callable(JetpackConnectionManager): bool $check     The connection check to run.
	 * @return bool
	 */
	private function query_jetpack_connection( string $check_key, callable $check ): bool {
		// Resolved before the try so the catch below cannot re-run the call that
		// threw, which would escape uncaught.
		$proxy = wc_get_container()->get( LegacyProxy::class );

		try {
			if ( ! class_exists( JetpackConnectionManager::class ) ) {
				return false;
			}

			return $check( $proxy->get_instance_of( JetpackConnectionManager::class ) );
		} catch ( Throwable $e ) {
			$this->failed_checks[ $check_key ] = true;

			$this->log_throwable( 'Error determining Jetpack connection state for push notifications', $e, $proxy );

			return false;
		}
	}

	/**
	 * Logs a swallowed throwable, distinguishing an `Error` from an `Exception`.
	 *
	 * An `Exception` here usually means Jetpack answered unhappily; an `Error`
	 * means the call was incompatible, which is a WooCommerce problem rather
	 * than a merchant one. Both are swallowed, so the log is the only place the
	 * difference survives.
	 *
	 * @param string           $message The message describing what was being determined.
	 * @param Throwable        $e       The caught throwable.
	 * @param LegacyProxy|null $proxy  An already-resolved proxy, when the caller has one.
	 * @return void
	 */
	private function log_throwable( string $message, Throwable $e, ?LegacyProxy $proxy = null ): void {
		try {
			$this->write_log( $message, $e, $proxy );
		} catch ( Throwable $ignored ) {
			// Logging is best effort. Failing to record why something went wrong must
			// never be worse than the original failure, which callers have handled.
			return;
		}
	}

	/**
	 * Writes the log entry for a swallowed throwable.
	 *
	 * @param string           $message The message describing what was being determined.
	 * @param Throwable        $e       The caught throwable.
	 * @param LegacyProxy|null $proxy   An already-resolved proxy, when the caller has one.
	 * @return void
	 */
	private function write_log( string $message, Throwable $e, ?LegacyProxy $proxy = null ): void {
		$proxy  = $proxy ?? wc_get_container()->get( LegacyProxy::class );
		$logger = $proxy->call_function( 'wc_get_logger' );

		if ( ! $logger instanceof WC_Logger_Interface ) {
			return;
		}

		$logger->error(
			sprintf(
				'%s (%s): %s',
				$message,
				$e instanceof Error ? 'error' : 'exception',
				$e->getMessage()
			),
			array( 'source' => PushNotifications::FEATURE_NAME )
		);
	}
}
