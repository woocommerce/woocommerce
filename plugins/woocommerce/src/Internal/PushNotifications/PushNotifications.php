<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * WC Push Notifications
 *
 * Class for setting up the WooCommerce-driven push notifications.
 */
class PushNotifications {
	/**
	 * Loads the push notifications class.
	 *
	 * @since X.X.X
	 * @return void
	 */
	public function register() {
		if ( ! $this->should_be_enabled() ) {
			return;
		}

		/**
		 * Load functionality.
		 */
	}

	/**
	 * Determines if local push notification functionality should be enabled. It
	 * should be enabled if:
	 * - Jetpack is connected
	 * - WooCommerce.com account is connected
	 *
	 * @since X.X.X
	 * @return bool
	 */
	public function should_be_enabled(): bool {
		$proxy = wc_get_container()->get( LegacyProxy::class );

		if (
			! class_exists( JetpackConnectionManager::class )
			|| ! $proxy->get_instance_of( JetpackConnectionManager::class )->is_connected()
		) {
			return false;
		}

		return true;
	}
}
