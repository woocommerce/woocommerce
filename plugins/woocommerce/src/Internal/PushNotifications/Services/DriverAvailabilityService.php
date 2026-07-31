<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Services;

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Proxies\LegacyProxy;
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
 * `connected`, `enabled`, and `available` flags, and determines which driver is
 * active.
 *
 * @since 11.1.0
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
	 * Drivers in precedence order: the first available one becomes the active
	 * driver. The remote proxy is preferred over Jetpack Sync when both are
	 * available.
	 */
	const DRIVER_PRECEDENCE = array(
		self::DRIVER_REMOTE_PROXY,
		self::DRIVER_JETPACK_SYNC,
	);

	/**
	 * Builds the driver status: the installed drivers with their connected,
	 * enabled, and available flags, and which driver is currently active.
	 *
	 * @return array{installed-drivers: array<string, array{connected: bool, enabled: bool, available: bool}>, active-driver: string|null}
	 *
	 * @since 11.1.0
	 */
	public function get_status(): array {
		$installed_drivers = array();

		if ( $this->is_jetpack_sync_installed() ) {
			$installed_drivers[ self::DRIVER_JETPACK_SYNC ] = $this->driver_status(
				$this->has_user_connection(),
				$this->is_jetpack_sync_enabled()
			);
		}

		// The remote proxy driver ships with this module, so it is always present.
		$installed_drivers[ self::DRIVER_REMOTE_PROXY ] = $this->driver_status(
			$this->has_blog_connection(),
			$this->is_remote_proxy_enabled()
		);

		return array(
			'installed-drivers' => $installed_drivers,
			'active-driver'     => $this->get_active_driver( $installed_drivers ),
		);
	}

	/**
	 * Builds a single driver's status flags. A driver is available (usable now)
	 * only when it is both connected and enabled.
	 *
	 * @param bool $connected Whether the driver's underlying connection is present.
	 * @param bool $enabled   Whether the driver itself is switched on (not disabled).
	 * @return array{connected: bool, enabled: bool, available: bool}
	 *
	 * @since 11.1.0
	 */
	private function driver_status( bool $connected, bool $enabled ): array {
		return array(
			'connected' => $connected,
			'enabled'   => $enabled,
			'available' => $connected && $enabled,
		);
	}

	/**
	 * Determines whether push notifications can currently be sent through the
	 * remote proxy driver: the feature must be enabled and Jetpack connected.
	 * This is the proxy driver's availability, which also gates the module.
	 *
	 * @return bool
	 *
	 * @since 11.1.0
	 */
	public function is_remote_proxy_available(): bool {
		return $this->is_remote_proxy_enabled() && $this->has_blog_connection();
	}

	/**
	 * Determines which driver is currently active: the first available driver in
	 * {@see self::DRIVER_PRECEDENCE} order (remote proxy before Jetpack Sync).
	 *
	 * @param array<string, array{connected: bool, enabled: bool, available: bool}> $installed_drivers The installed drivers.
	 * @return string|null The active driver id, or null when none are available.
	 *
	 * @since 11.1.0
	 */
	private function get_active_driver( array $installed_drivers ): ?string {
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
	 * @since 11.1.0
	 */
	protected function is_remote_proxy_enabled(): bool {
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
	 * Determines whether the Jetpack Sync package is installed.
	 *
	 * @return bool
	 *
	 * @since 11.1.0
	 */
	protected function is_jetpack_sync_installed(): bool {
		return class_exists( self::JETPACK_SYNC_SETTINGS_CLASS );
	}

	/**
	 * Determines whether Jetpack Sync is enabled. Treated as not enabled when
	 * the package isn't installed.
	 *
	 * @return bool
	 *
	 * @since 11.1.0
	 */
	protected function is_jetpack_sync_enabled(): bool {
		$is_sync_enabled = array( self::JETPACK_SYNC_SETTINGS_CLASS, 'is_sync_enabled' );

		if ( ! is_callable( $is_sync_enabled ) ) {
			return false;
		}

		return (bool) call_user_func( $is_sync_enabled );
	}

	/**
	 * Determines whether the site has an active Jetpack blog connection, which
	 * the remote proxy driver requires.
	 *
	 * @return bool
	 *
	 * @since 11.1.0
	 */
	protected function has_blog_connection(): bool {
		return $this->query_jetpack_connection(
			static fn ( JetpackConnectionManager $manager ): bool => $manager->is_connected()
		);
	}

	/**
	 * Determines whether the site has a connected Jetpack user (owner), which
	 * the Jetpack Sync driver requires.
	 *
	 * @return bool
	 *
	 * @since 11.1.0
	 */
	protected function has_user_connection(): bool {
		return $this->query_jetpack_connection(
			static fn ( JetpackConnectionManager $manager ): bool => $manager->has_connected_owner()
		);
	}

	/**
	 * Runs the given check against the Jetpack connection manager, returning
	 * false (and logging) if the connection state can't be determined.
	 *
	 * @param callable(JetpackConnectionManager): bool $check The connection check to run.
	 * @return bool
	 *
	 * @since 11.1.0
	 */
	private function query_jetpack_connection( callable $check ): bool {
		try {
			if ( ! class_exists( JetpackConnectionManager::class ) ) {
				return false;
			}

			$proxy = wc_get_container()->get( LegacyProxy::class );

			return $check( $proxy->get_instance_of( JetpackConnectionManager::class ) );
		} catch ( Throwable $e ) {
			$logger = wc_get_container()->get( LegacyProxy::class )->call_function( 'wc_get_logger' );

			if ( $logger instanceof WC_Logger_Interface ) {
				$logger->error(
					'Error determining Jetpack connection state for push notifications: ' . $e->getMessage(),
					array( 'source' => PushNotifications::FEATURE_NAME )
				);
			}

			return false;
		}
	}
}
