<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications;

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushTokenRestController;

/**
 * WC Push Notifications
 *
 * Class for setting up the WooCommerce-driven push notifications.
 */
class PushNotifications {
	/**
	 * Roles that can receive push notifications.
	 */
	const ROLES_WITH_PUSH_NOTIFICATIONS_ENABLED = array(
		'administrator',
		'shop_manager',
	);

	/**
	 * 'Memoized' enablement flag.
	 *
	 * @var bool|null
	 */
	private ?bool $enabled = null;

	/**
	 * Loads the push notifications class.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->should_be_enabled() ) {
			return;
		}

		wc_get_container()->get( PushTokenRestController::class )->register();
	}

	/**
	 * Determines if local push notification functionality should be enabled. It
	 * should be enabled if Jetpack is connected.
	 *
	 * @return bool
	 */
	public function should_be_enabled(): bool {
		if ( null === $this->enabled ) {
			$proxy = wc_get_container()->get( LegacyProxy::class );

			if (
				! class_exists( JetpackConnectionManager::class )
				|| ! $proxy->get_instance_of( JetpackConnectionManager::class )->is_connected()
			) {
				$this->enabled = false;
			} else {
				$this->enabled = true;
			}
		}

		return $this->enabled;
	}
}
