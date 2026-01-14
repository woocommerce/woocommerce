<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PushNotifications;

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Logger;
use Exception;

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
	 * Loads the push notifications class.
	 *
	 * @return void
	 *
	 * @since 10.4.0
	 */
	public function register(): void {
		if ( ! $this->should_be_enabled() ) {
			return;
		}

		add_action( 'init', array( $this, 'register_post_types' ) );

		// Library endpoints and scheduled tasks will be registered here.
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
	 * Push notifications require both the feature flag to be enabled and
	 * Jetpack to be connected. Memoize the value so we only check once per
	 * request.
	 *
	 * @return bool
	 *
	 * @since 10.4.0
	 */
	public function should_be_enabled(): bool {
		if ( null !== $this->enabled ) {
			return $this->enabled;
		}

		if ( ! FeaturesUtil::feature_is_enabled( self::FEATURE_NAME ) ) {
			$this->enabled = false;
			return $this->enabled;
		}

		try {
			$proxy = wc_get_container()->get( LegacyProxy::class );

			$this->enabled = (
				class_exists( JetpackConnectionManager::class )
				&& $proxy->get_instance_of( JetpackConnectionManager::class )->is_connected()
			);
		} catch ( Exception $e ) {
			$logger = wc_get_container()->get( LegacyProxy::class )->call_function( 'wc_get_logger' );

			if ( $logger instanceof WC_Logger ) {
				$logger->error(
					'Error determining if PushNotifications feature should be enabled: ' . $e->getMessage()
				);
			}

			$this->enabled = false;
		}

		return $this->enabled;
	}
}
