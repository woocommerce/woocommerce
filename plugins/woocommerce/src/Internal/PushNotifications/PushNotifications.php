<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PushNotifications;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Controllers\NotificationPreferencesRestController;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushNotificationRestController;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushNotificationStatusRestController;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushTokenRestController;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Services\DriverAvailabilityService;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationRetryHandler;
use Automattic\WooCommerce\Internal\PushNotifications\Services\PendingNotificationStore;
use Automattic\WooCommerce\Internal\PushNotifications\Triggers\NewOrderNotificationTrigger;
use Automattic\WooCommerce\Internal\PushNotifications\Triggers\NewReviewNotificationTrigger;
use Automattic\WooCommerce\Internal\PushNotifications\Triggers\StockNotificationRecoveryHandler;
use Automattic\WooCommerce\Internal\PushNotifications\Triggers\StockNotificationTrigger;

/**
 * WC Push Notifications
 *
 * Class for setting up the WooCommerce-driven push notifications.
 *
 * @since 10.4.0
 */
class PushNotifications {
	/**
	 * Feature name for the push notifications feature.
	 */
	const FEATURE_NAME = 'push_notifications';

	/**
	 * Roles that can receive push notifications.
	 *
	 * This will be used to gate functionality access to just these roles.
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
	 * Registers initialisation tasks to the `init` hook.
	 *
	 * @return void
	 *
	 * @since 10.4.0
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'on_init' ) );
	}

	/**
	 * Loads the push notifications class.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 */
	public function on_init(): void {
		// Registered ahead of the enablement check, so the status endpoint stays
		// available when push notifications are disabled and clients can discover
		// the state and fall back if needed.
		wc_get_container()->get( PushNotificationStatusRestController::class )->register();

		if ( ! $this->should_be_enabled() ) {
			return;
		}

		$this->register_post_types();

		wc_get_container()->get( PendingNotificationStore::class )->register();

		( new PushTokenRestController() )->register();
		( new PushNotificationRestController() )->register();
		( new NotificationPreferencesRestController() )->register();
		( new NewOrderNotificationTrigger() )->register();
		( new NewReviewNotificationTrigger() )->register();
		( new StockNotificationTrigger() )->register();
		( new StockNotificationRecoveryHandler() )->register();

		wc_get_container()->get( NotificationProcessor::class )->register();
		wc_get_container()->get( NotificationRetryHandler::class )->register();
	}

	/**
	 * Registers the push token custom post type.
	 *
	 * @since 10.5.0
	 * @return void
	 */
	public function register_post_types(): void {
		register_post_type(
			PushToken::POST_TYPE,
			array(
				'labels'             => array(
					'name'          => __( 'Push Tokens', 'woocommerce' ),
					'singular_name' => __( 'Push Token', 'woocommerce' ),
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'show_in_menu'       => false,
				'query_var'          => false,
				'rewrite'            => false,
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'supports'           => array( 'author' ),
				'can_export'         => false,
				'delete_with_user'   => true,
			)
		);
	}

	/**
	 * Determines if local push notification functionality should be enabled.
	 * The module runs on the remote proxy driver, so it is enabled exactly when
	 * that driver can send (feature not disabled and Jetpack connected). Memoize
	 * the value so we only check once per request.
	 *
	 * @return bool
	 *
	 * @since 10.4.0
	 */
	public function should_be_enabled(): bool {
		if ( null !== $this->enabled ) {
			return $this->enabled;
		}

		$this->enabled = wc_get_container()->get( DriverAvailabilityService::class )->is_remote_proxy_available();

		return $this->enabled;
	}
}
