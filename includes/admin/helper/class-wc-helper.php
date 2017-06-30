<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Helper Class
 *
 * The main entry-point for all things related to the Helper.
 */
class WC_Helper {
	/**
	 * A log object returned by wc_get_logger().
	 */
	public static $log;

	/**
	 * Get an absolute path to the requested helper view.
	 *
	 * @param string $view The requested view file.
	 *
	 * @return string The absolute path to the view file.
	 */
	public static function get_view_filename( $view ) {
		return __DIR__ . "/views/$view";
	}

	/**
	 * Loads the helper class, runs on init.
	 */
	public static function load() {
		add_action( 'current_screen', array( __CLASS__, 'current_screen' ) );
		add_action( 'woocommerce_helper_output', array( __CLASS__, 'render_helper_output' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_filter( 'extra_plugin_headers', array( __CLASS__, 'extra_headers' ) );
		add_filter( 'extra_theme_headers', array( __CLASS__, 'extra_headers' ) );

		// Attempt to toggle subscription state upon plugin activation/deactivation
		add_action( 'activated_plugin', array( __CLASS__, 'activated_plugin' ) );
		add_action( 'deactivated_plugin', array( __CLASS__, 'deactivated_plugin' ) );

		// Add some nags about extension updates
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		add_filter( 'woocommerce_in_plugin_update_message', array( __CLASS__, 'in_plugin_update_message' ) );

		do_action( 'woocommerce_helper_loaded' );
	}

	/**
	 * Render the helper section content based on context.
	 */
	public static function render_helper_output() {
		$auth = WC_Helper_Options::get( 'auth' );
		$auth_user_data = WC_Helper_Options::get( 'auth_user_data' );

		// Return success/error notices.
		$notices = self::_get_return_notices();

		// No active connection.
		if ( empty( $auth['access_token'] ) ) {
			$connect_url = add_query_arg( array(
				'page' => 'wc-addons',
				'section' => 'helper',
				'wc-helper-connect' => 1,
				'wc-helper-nonce' => wp_create_nonce( 'connect' ),
			), admin_url( 'admin.php' ) );

			include( self::get_view_filename( 'html-oauth-start.php' ) );
			return;
		}
		$disconnect_url = add_query_arg( array(
			'page' => 'wc-addons',
			'section' => 'helper',
			'wc-helper-disconnect' => 1,
			'wc-helper-nonce' => wp_create_nonce( 'disconnect' ),
		), admin_url( 'admin.php' ) );

		$refresh_url = add_query_arg( array(
			'page' => 'wc-addons',
			'section' => 'helper',
			'wc-helper-refresh' => 1,
			'wc-helper-nonce' => wp_create_nonce( 'refresh' ),
		), admin_url( 'admin.php' ) );

		// Installed plugins and themes, with or without an active subscription.
		$woo_plugins = self::get_local_woo_plugins();
		$woo_themes = self::get_local_woo_themes();

		$site_id = absint( $auth['site_id'] );
		$subscriptions = self::get_subscriptions();
		$updates = WC_Helper_Updater::get_update_data();
		$subscriptions_product_ids = wp_list_pluck( $subscriptions, 'product_id' );

		foreach ( $subscriptions as &$subscription ) {
			$subscription['active'] = in_array( $site_id, $subscription['connections'] );

			$subscription['activate_url'] = add_query_arg( array(
				'page' => 'wc-addons',
				'section' => 'helper',
				'wc-helper-activate' => 1,
				'wc-helper-product-key' => $subscription['product_key'],
				'wc-helper-product-id' => $subscription['product_id'],
				'wc-helper-nonce' => wp_create_nonce( 'activate:' . $subscription['product_key'] ),
			), admin_url( 'admin.php' ) );

			$subscription['deactivate_url'] = add_query_arg( array(
				'page' => 'wc-addons',
				'section' => 'helper',
				'wc-helper-deactivate' => 1,
				'wc-helper-product-key' => $subscription['product_key'],
				'wc-helper-product-id' => $subscription['product_id'],
				'wc-helper-nonce' => wp_create_nonce( 'deactivate:' . $subscription['product_key'] ),
			), admin_url( 'admin.php' ) );

			$subscription['local'] = array(
				'installed' => false,
				'active' => false,
				'version' => null,
			);

			$subscription['update_url'] = admin_url( 'update-core.php' );

			$local = wp_list_filter( array_merge( $woo_plugins, $woo_themes ), array( '_product_id' => $subscription['product_id'] ) );

			if ( ! empty( $local ) ) {
				$local = array_shift( $local );
				$subscription['local']['installed'] = true;
				$subscription['local']['version'] = $local['Version'];

				if ( 'plugin' == $local['_type'] ) {
					if ( is_plugin_active( $local['_filename'] ) ) {
						$subscription['local']['active'] = true;
					} elseif ( is_multisite() && is_plugin_active_for_network( $local['_filename'] ) ) {
						$subscription['local']['active'] = true;
					}

					// A magic update_url.
					$subscription['update_url'] = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $local['_filename'], 'upgrade-plugin_' . $local['_filename'] );

				} elseif ( 'theme' == $local['_type'] ) {
					if ( in_array( $local['_stylesheet'], array( get_stylesheet(), get_template() ) ) ) {
						$subscription['local']['active'] = true;
					}

					// Another magic update_url.
					$subscription['update_url'] = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-theme&theme=' . $local['_stylesheet'] ), 'upgrade-theme_' . $local['_stylesheet'] );
				}
			}

			$subscription['has_update'] = false;
			if ( $subscription['local']['installed'] && ! empty( $updates[ $subscription['product_id'] ] ) ) {
				$subscription['has_update'] = version_compare( $updates[ $subscription['product_id'] ]['version'], $subscription['local']['version'], '>' );
			}

			$subscription['download_primary'] = true;
			$subscription['download_url'] = $subscription['product_url'];
			if ( ! $subscription['local']['installed'] && ! empty( $updates[ $subscription['product_id'] ] ) ) {
				$subscription['download_url'] = $updates[ $subscription['product_id'] ]['package'];
			}

			$subscription['actions'] = array();

			if ( $subscription['has_update'] && ! $subscription['expired'] ) {
				$action = array(
					/* translators: %s: version number */
					'message' => sprintf( __( 'Version %s is <strong>available</strong>.', 'woocommerce' ), esc_html( $updates[ $subscription['product_id'] ]['version'] ) ),
					'button_label' => __( 'Update', 'woocommerce' ),
					'button_url' => $subscription['update_url'],
					'status' => 'update-available',
					'icon' => 'dashicons-update',
				);

				// Subscription is not active on this site.
				if ( ! $subscription['active'] ) {
					$action['message'] .= ' ' . __( 'To enable this update you need to <strong>activate</strong> this subscription.', 'woocommerce' );
					$action['button_label'] = null;
					$action['button_url'] = null;
				}

				$subscription['actions'][] = $action;
			}

			if ( $subscription['has_update'] && $subscription['expired'] ) {
				$action = array(
					/* translators: %s: version number */
					'message' => sprintf( __( 'Version %s is <strong>available</strong>.', 'woocommerce' ), esc_html( $updates[ $subscription['product_id'] ]['version'] ) ),
					'status' => 'expired',
					'icon' => 'dashicons-info',
				);

				$action['message'] .= ' ' . __( 'To enable this update you need to <strong>purchase</strong> a new subscription.', 'woocommerce' );
				$action['button_label'] = __( 'Purchase', 'woocommerce' );
				$action['button_url'] = $subscription['product_url'];

				$subscription['actions'][] = $action;
			} elseif ( $subscription['expired'] && ! empty( $subscription['master_user_email'] ) ) {
				$action = array(
					'message' => sprintf( __( 'This subscription has expired. Contact the owner to <strong>renew</strong> the subscription to receive updates and support.', 'woocommerce' ) ),
					'status' => 'expired',
					'icon' => 'dashicons-info',
				);

				$subscription['actions'][] = $action;
			} elseif ( $subscription['expired'] ) {
				$action = array(
					'message' => sprintf( __( 'This subscription has expired. Please <strong>renew</strong> to receive updates and support.', 'woocommerce' ) ),
					'button_label' => __( 'Renew', 'woocommerce' ),
					'button_url' => 'https://woocommerce.com/my-account/my-subscriptions/',
					'status' => 'expired',
					'icon' => 'dashicons-info',
				);

				$subscription['actions'][] = $action;
			}

			if ( $subscription['expiring'] && ! $subscription['autorenew'] ) {
				$action = array(
					'message' => __( 'Subscription is <strong>expiring</strong> soon.', 'woocommerce' ),
					'button_label' => __( 'Enable auto-renew', 'woocommerce' ),
					'button_url' => 'https://woocommerce.com/my-account/my-subscriptions/',
					'status' => 'expired',
					'icon' => 'dashicons-info',
				);

				$subscription['download_primary'] = false;
				$subscription['actions'][] = $action;
			} elseif ( $subscription['expiring'] ) {
				$action = array(
					'message' => sprintf( __( 'This subscription is expiring soon. Please <strong>renew</strong> to continue receiving updates and support.', 'woocommerce' ) ),
					'button_label' => __( 'Renew', 'woocommerce' ),
					'button_url' => 'https://woocommerce.com/my-account/my-subscriptions/',
					'status' => 'expired',
					'icon' => 'dashicons-info',
				);

				$subscription['download_primary'] = false;
				$subscription['actions'][] = $action;
			}

			// Mark the first action primary.
			foreach ( $subscription['actions'] as $key => $action ) {
				if ( ! empty( $action['button_label'] ) ) {
					$subscription['actions'][ $key ]['primary'] = true;
					break;
				}
			}
		}

		// Break the by-ref.
		unset( $subscription );

		// Installed products without a subscription.
		$no_subscriptions = array();
		foreach ( array_merge( $woo_plugins, $woo_themes ) as $filename => $data ) {
			if ( in_array( $data['_product_id'], $subscriptions_product_ids ) ) {
				continue;
			}

			$data['_product_url'] = '#';
			$data['_has_update'] = false;

			if ( ! empty( $updates[ $data['_product_id'] ] ) ) {
				$data['_has_update'] = version_compare( $updates[ $data['_product_id'] ]['version'], $data['Version'], '>' );

				if ( ! empty( $updates[ $data['_product_id'] ]['url'] ) ) {
					$data['_product_url'] = $updates[ $data['_product_id'] ]['url'];
				} elseif ( ! empty( $data['PluginURI'] ) ) {
					$data['_product_url'] = $data['PluginURI'];
				}
			}

			$data['_actions'] = array();

			if ( $data['_has_update'] ) {
				$action = array(
					'message' => sprintf( __( 'Version %s is <strong>available</strong>. To enable this update you need to <strong>purchase</strong> a new subscription.', 'woocommerce' ), esc_html( $updates[ $data['_product_id'] ]['version'] ) ),
					'button_label' => __( 'Purchase', 'woocommerce' ),
					'button_url' => $data['_product_url'],
					'status' => 'expired',
					'icon' => 'dashicons-info',
				);

				$data['_actions'][] = $action;
			} else {
				$action = array(
					'message' => sprintf( __( 'To receive updates and support for this extension, you need to <strong>purchase</strong> a new subscription or <a href="%1$s" target="_blank">be added as a collaborator</a>.', 'woocommerce' ), 'https://docs.woocommerce.com/document/adding-collaborators/' ),
					'button_label' => __( 'Purchase', 'woocommerce' ),
					'button_url' => $data['_product_url'],
					'status' => 'expired',
					'icon' => 'dashicons-info',
				);

				$data['_actions'][] = $action;
			}

			$no_subscriptions[ $filename ] = $data;
		}

		// Update the user id if it came from a migrated connection.
		if ( empty( $auth['user_id'] ) ) {
			$auth['user_id'] = get_current_user_id();
			WC_Helper_Options::update( 'auth', $auth );
		}

		// Sort alphabetically
		uasort( $subscriptions, array( __CLASS__, '_sort_by_product_name' ) );
		uasort( $no_subscriptions, array( __CLASS__, '_sort_by_name' ) );

		// We have an active connection.
		include( self::get_view_filename( 'html-main.php' ) );
		return;
	}

	/**
	 * Enqueue admin scripts and styles.
	 */
	public static function admin_enqueue_scripts() {
		$screen = get_current_screen();

		if ( 'woocommerce_page_wc-addons' == $screen->id && isset( $_GET['section'] ) && 'helper' === $_GET['section'] ) {
			wp_enqueue_style( 'woocommerce-helper', WC()->plugin_url() . '/assets/css/helper.css', array(), WC_VERSION );
		}
	}

	/**
	 * Various success/error notices.
	 *
	 * Runs during admin page render, so no headers/redirects here.
	 *
	 * @return array Array pairs of message/type strings with notices.
	 */
	private static function _get_return_notices() {
		$return_status = isset( $_GET['wc-helper-status'] ) ? $_GET['wc-helper-status'] : null;
		$notices = array();

		switch ( $return_status ) {
			case 'activate-success':
				$subscription = self::_get_subscriptions_from_product_id( absint( $_GET['wc-helper-product-id'] ) );
				$notices[] = array(
					'type' => 'updated',
					/* translators: %s: product name */
					'message' => sprintf( __( '%s activated successfully. You will now receive updates for this product.', 'woocommerce' ),
						'<strong>' . esc_html( $subscription['product_name'] ) . '</strong>' ),
				);
				break;

			case 'activate-error':
				$subscription = self::_get_subscriptions_from_product_id( absint( $_GET['wc-helper-product-id'] ) );
				$notices[] = array(
					'type' => 'error',
					/* translators: %s: product name */
					'message' => sprintf( __( 'An error has occurred when activating %s. Please try again later.', 'woocommerce' ),
						'<strong>' . esc_html( $subscription['product_name'] ) . '</strong>' ),
				);
				break;

			case 'deactivate-success':
				$subscription = self::_get_subscriptions_from_product_id( absint( $_GET['wc-helper-product-id'] ) );
				$local = self::_get_local_from_product_id( absint( $_GET['wc-helper-product-id'] ) );

				/* translators: %s: product name */
				$message = sprintf( __( 'Subscription for %s deactivated successfully. You will no longer receive updates for this product.', 'woocommerce' ),
					'<strong>' . esc_html( $subscription['product_name'] ) . '</strong>' );

				if ( $local && is_plugin_active( $local['_filename'] ) && current_user_can( 'activate_plugins' ) ) {
					$deactivate_plugin_url = add_query_arg( array(
						'page' => 'wc-addons',
						'section' => 'helper',
						'wc-helper-deactivate-plugin' => 1,
						'wc-helper-product-id' => $subscription['product_id'],
						'wc-helper-nonce' => wp_create_nonce( 'deactivate-plugin:' . $subscription['product_id'] ),
					), admin_url( 'admin.php' ) );

					/* translators: %1$s: product name, %2$s: deactivate url */
					$message = sprintf( __( 'Subscription for %1$s deactivated successfully. You will no longer receive updates for this product. <a href="%2$s">Click here</a> if you wish to deactive the plugin as well.', 'woocommerce' ),
						'<strong>' . esc_html( $subscription['product_name'] ) . '</strong>', esc_url( $deactivate_plugin_url ) );
				}

				$notices[] = array(
					'message' => $message,
					'type' => 'updated',
				);
				break;

			case 'deactivate-error':
				$subscription = self::_get_subscriptions_from_product_id( absint( $_GET['wc-helper-product-id'] ) );
				$notices[] = array(
					'type' => 'error',
					/* translators: %s: product name */
					'message' => sprintf( __( 'An error has occurred when deactivating the subscription for %s. Please try again later.', 'woocommerce' ),
						'<strong>' . esc_html( $subscription['product_name'] ) . '</strong>' ),
				);
				break;

			case 'deactivate-plugin-success':
				$subscription = self::_get_subscriptions_from_product_id( absint( $_GET['wc-helper-product-id'] ) );
				$notices[] = array(
					'type' => 'updated',
					/* translators: %s: product name */
					'message' => sprintf( __( 'The extension %s has been deactivated successfully.', 'woocommerce' ),
						'<strong>' . esc_html( $subscription['product_name'] ) . '</strong>' ),
				);
				break;

			case 'deactivate-plugin-error':
				$subscription = self::_get_subscriptions_from_product_id( absint( $_GET['wc-helper-product-id'] ) );
				$notices[] = array(
					'type' => 'error',
					/* translators: %1$s: product name, %2$s: plugins screen url */
					'message' => sprintf( __( 'An error has occurred when deactivating the extension %1$s. Please proceed to the <a href="%2$s">Plugins screen</a> to deactivate it manually.', 'woocommerce' ),
						'<strong>' . esc_html( $subscription['product_name'] ) . '</strong>', admin_url( 'plugins.php' ) ),
				);
				break;

			case 'helper-connected':
				$notices[] = array(
					'message' => __( 'You have successfully connected your store to WooCommerce.com', 'woocommerce' ),
					'type' => 'updated',
				);
				break;

			case 'helper-disconnected':
				$notices[] = array(
					'message' => __( 'You have successfully disconnected your store from WooCommerce.com', 'woocommerce' ),
					'type' => 'updated',
				);
				break;

			case 'helper-refreshed':
				$notices[] = array(
					'message' => __( 'Authentication and subscription caches refreshed successfully.', 'woocommerce' ),
					'type' => 'updated',
				);
				break;
		}

		return $notices;
	}

	/**
	 * Various early-phase actions with possible redirects.
	 */
	public static function current_screen( $screen ) {
		if ( 'woocommerce_page_wc-addons' != $screen->id ) {
			return;
		}

		if ( empty( $_GET['section'] ) || 'helper' !== $_GET['section'] ) {
			return;
		}

		if ( ! empty( $_GET['wc-helper-connect'] ) ) {
			return self::_helper_auth_connect();
		}

		if ( ! empty( $_GET['wc-helper-return'] ) ) {
			return self::_helper_auth_return();
		}

		if ( ! empty( $_GET['wc-helper-disconnect'] ) ) {
			return self::_helper_auth_disconnect();
		}

		if ( ! empty( $_GET['wc-helper-refresh'] ) ) {
			return self::_helper_auth_refresh();
		}

		if ( ! empty( $_GET['wc-helper-activate'] ) ) {
			return self::_helper_subscription_activate();
		}

		if ( ! empty( $_GET['wc-helper-deactivate'] ) ) {
			return self::_helper_subscription_deactivate();
		}

		if ( ! empty( $_GET['wc-helper-deactivate-plugin'] ) ) {
			return self::_helper_plugin_deactivate();
		}
	}

	/**
	 * Initiate a new OAuth connection.
	 */
	private static function _helper_auth_connect() {
		if ( empty( $_GET['wc-helper-nonce'] ) || ! wp_verify_nonce( $_GET['wc-helper-nonce'], 'connect' ) ) {
			self::log( 'Could not verify nonce in _helper_auth_connect' );
			wp_die( 'Could not verify nonce' );
		}

		$redirect_uri = add_query_arg( array(
			'page' => 'wc-addons',
			'section' => 'helper',
			'wc-helper-return' => 1,
			'wc-helper-nonce' => wp_create_nonce( 'connect' ),
		), admin_url( 'admin.php' ) );

		$request = WC_Helper_API::post( 'oauth/request_token', array(
			'body' => array(
				'home_url' => home_url(),
				'redirect_uri' => $redirect_uri,
			),
		) );

		$code = wp_remote_retrieve_response_code( $request );

		if ( 200 !== $code ) {
			self::log( sprintf( 'Call to oauth/request_token returned a non-200 response code (%d)', $code ) );
			wp_die( 'Something went wrong' );
		}

		$secret = json_decode( wp_remote_retrieve_body( $request ) );
		if ( empty( $secret ) ) {
			self::log( sprintf( 'Call to oauth/request_token returned an invalid body: %s', wp_remote_retrieve_body( $request ) ) );
			wp_die( 'Something went wrong' );
		}

		$connect_url = add_query_arg( array(
			'home_url' => rawurlencode( home_url() ),
			'redirect_uri' => rawurlencode( $redirect_uri ),
			'secret' => rawurlencode( $secret ),
		), WC_Helper_API::url( 'oauth/authorize' ) );

		wp_redirect( esc_url_raw( $connect_url ) );
		die();
	}

	/**
	 * Return from WooCommerce.com OAuth flow.
	 */
	private static function _helper_auth_return() {
		if ( empty( $_GET['wc-helper-nonce'] ) || ! wp_verify_nonce( $_GET['wc-helper-nonce'], 'connect' ) ) {
			self::log( 'Could not verify nonce in _helper_auth_return' );
			wp_die( 'Something went wrong' );
		}

		// Bail if the user clicked deny.
		if ( ! empty( $_GET['deny'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=wc-addons&section=helper' ) );
			die();
		}

		// We do need a request token...
		if ( empty( $_GET['request_token'] ) ) {
			self::log( 'Request token not found in _helper_auth_return' );
			wp_die( 'Something went wrong' );
		}

		// Obtain an access token.
		$request = WC_Helper_API::post( 'oauth/access_token', array(
			'body' => array(
				'request_token' => $_GET['request_token'],
				'home_url' => home_url(),
			),
		) );

		$code = wp_remote_retrieve_response_code( $request );

		if ( 200 !== $code ) {
			self::log( sprintf( 'Call to oauth/access_token returned a non-200 response code (%d)', $code ) );
			wp_die( 'Something went wrong' );
		}

		$access_token = json_decode( wp_remote_retrieve_body( $request ), true );
		if ( ! $access_token ) {
			self::log( sprintf( 'Call to oauth/access_token returned an invalid body: %s', wp_remote_retrieve_body( $request ) ) );
			wp_die( 'Something went wrong' );
		}

		WC_Helper_Options::update( 'auth', array(
			'access_token' => $access_token['access_token'],
			'access_token_secret' => $access_token['access_token_secret'],
			'site_id' => $access_token['site_id'],
			'user_id' => get_current_user_id(),
			'updated' => time(),
		) );

		// Obtain the connected user info.
		if ( ! self::_flush_authentication_cache() ) {
			self::log( 'Could not obtain connected user info in _helper_auth_return' );
			WC_Helper_Options::update( 'auth', array() );
			wp_die( 'Something went wrong.' );
		}

		self::_flush_subscriptions_cache();

		// Enable tracking when connected.
		if ( class_exists( 'WC_Tracker' ) ) {
			update_option( 'woocommerce_allow_tracking', 'yes' );
			WC_Tracker::send_tracking_data( true );
		}

		wp_safe_redirect( add_query_arg( array(
			'page' => 'wc-addons',
			'section' => 'helper',
			'wc-helper-status' => 'helper-connected',
		), admin_url( 'admin.php' ) ) );
		die();
	}

	/**
	 * Disconnect from WooCommerce.com, clear OAuth tokens.
	 */
	private static function _helper_auth_disconnect() {
		if ( empty( $_GET['wc-helper-nonce'] ) || ! wp_verify_nonce( $_GET['wc-helper-nonce'], 'disconnect' ) ) {
			self::log( 'Could not verify nonce in _helper_auth_disconnect' );
			wp_die( 'Could not verify nonce' );
		}

		$redirect_uri = add_query_arg( array(
			'page' => 'wc-addons',
			'section' => 'helper',
			'wc-helper-status' => 'helper-disconnected',
		), admin_url( 'admin.php' ) );

		$result = WC_Helper_API::post( 'oauth/invalidate_token', array(
			'authenticated' => true,
		) );

		WC_Helper_Options::update( 'auth', array() );
		WC_Helper_Options::update( 'auth_user_data', array() );

		self::_flush_subscriptions_cache();
		self::_flush_updates_cache();

		// Disable tracking when disconnected.
		update_option( 'woocommerce_allow_tracking', 'no' );

		wp_safe_redirect( $redirect_uri );
		die();
	}

	/**
	 * User hit the Refresh button, clear all caches.
	 */
	private static function _helper_auth_refresh() {
		if ( empty( $_GET['wc-helper-nonce'] ) || ! wp_verify_nonce( $_GET['wc-helper-nonce'], 'refresh' ) ) {
			self::log( 'Could not verify nonce in _helper_auth_refresh' );
			wp_die( 'Could not verify nonce' );
		}

		$redirect_uri = add_query_arg( array(
			'page' => 'wc-addons',
			'section' => 'helper',
			'wc-helper-status' => 'helper-refreshed',
		), admin_url( 'admin.php' ) );

		self::_flush_authentication_cache();
		self::_flush_subscriptions_cache();
		self::_flush_updates_cache();

		wp_safe_redirect( $redirect_uri );
		die();
	}

	/**
	 * Active a product subscription.
	 */
	private static function _helper_subscription_activate() {
		$product_key = $_GET['wc-helper-product-key'];
		$product_id = absint( $_GET['wc-helper-product-id'] );

		if ( empty( $_GET['wc-helper-nonce'] ) || ! wp_verify_nonce( $_GET['wc-helper-nonce'], 'activate:' . $product_key ) ) {
			self::log( 'Could not verify nonce in _helper_subscription_activate' );
			wp_die( 'Could not verify nonce' );
		}

		$request = WC_Helper_API::post( 'activate', array(
			'authenticated' => true,
			'body' => json_encode( array(
				'product_key' => $product_key,
			) ),
		) );

		$activated = wp_remote_retrieve_response_code( $request ) === 200;
		$body = json_decode( wp_remote_retrieve_body( $request ), true );
		if ( ! $activated && ! empty( $body['code'] ) && 'already_connected' == $body['code'] ) {
			$activated = true;
		}

		// Attempt to activate this plugin.
		$local = self::_get_local_from_product_id( $product_id );
		if ( $local && 'plugin' == $local['_type'] && current_user_can( 'activate_plugins' ) && ! is_plugin_active( $local['_filename'] ) ) {
			activate_plugin( $local['_filename'] );
		}

		self::_flush_subscriptions_cache();
		$redirect_uri = add_query_arg( array(
			'page' => 'wc-addons',
			'section' => 'helper',
			'wc-helper-status' => $activated ? 'activate-success' : 'activate-error',
			'wc-helper-product-id' => $product_id,
		), admin_url( 'admin.php' ) );

		wp_safe_redirect( $redirect_uri );
		die();
	}

	/**
	 * Deactivate a product subscription.
	 */
	private static function _helper_subscription_deactivate() {
		$product_key = $_GET['wc-helper-product-key'];
		$product_id = absint( $_GET['wc-helper-product-id'] );

		if ( empty( $_GET['wc-helper-nonce'] ) || ! wp_verify_nonce( $_GET['wc-helper-nonce'], 'deactivate:' . $product_key ) ) {
			self::log( 'Could not verify nonce in _helper_subscription_deactivate' );
			wp_die( 'Could not verify nonce' );
		}

		$request = WC_Helper_API::post( 'deactivate', array(
			'authenticated' => true,
			'body' => json_encode( array(
				'product_key' => $product_key,
			) ),
		) );

		$code = wp_remote_retrieve_response_code( $request );
		$deactivated = 200 === $code;
		if ( ! $deactivated ) {
			self::log( sprintf( 'Deactivate API call returned a non-200 response code (%d)', $code ) );
		}

		self::_flush_subscriptions_cache();
		$redirect_uri = add_query_arg( array(
			'page' => 'wc-addons',
			'section' => 'helper',
			'wc-helper-status' => $deactivated ? 'deactivate-success' : 'deactivate-error',
			'wc-helper-product-id' => $product_id,
		), admin_url( 'admin.php' ) );

		wp_safe_redirect( $redirect_uri );
		die();
	}

	/**
	 * Deactivate a plugin.
	 */
	private static function _helper_plugin_deactivate() {
		$product_id = absint( $_GET['wc-helper-product-id'] );
		$deactivated = false;

		if ( empty( $_GET['wc-helper-nonce'] ) || ! wp_verify_nonce( $_GET['wc-helper-nonce'], 'deactivate-plugin:' . $product_id ) ) {
			self::log( 'Could not verify nonce in _helper_plugin_deactivate' );
			wp_die( 'Could not verify nonce' );
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( 'You are not allowed to manage plugins on this site.' );
		}

		$local = wp_list_filter( array_merge( self::get_local_woo_plugins(),
			self::get_local_woo_themes() ), array( '_product_id' => $product_id ) );

		// Attempt to deactivate this plugin or theme.
		if ( ! empty( $local ) ) {
			$local = array_shift( $local );
			if ( is_plugin_active( $local['_filename'] ) ) {
				deactivate_plugins( $local['_filename'] );
			}

			$deactivated = ! is_plugin_active( $local['_filename'] );
		}

		$redirect_uri = add_query_arg( array(
			'page' => 'wc-addons',
			'section' => 'helper',
			'wc-helper-status' => $deactivated ? 'deactivate-plugin-success' : 'deactivate-plugin-error',
			'wc-helper-product-id' => $product_id,
		), admin_url( 'admin.php' ) );

		wp_safe_redirect( $redirect_uri );
		die();
	}

	/**
	 * Get a local plugin/theme entry from product_id.
	 *
	 * @param int $product_id The product id.
	 *
	 * @return array|bool The array containing the local plugin/theme data or false.
	 */
	private static function _get_local_from_product_id( $product_id ) {
		$local = wp_list_filter( array_merge( self::get_local_woo_plugins(),
			self::get_local_woo_themes() ), array( '_product_id' => $product_id ) );

		if ( ! empty( $local ) ) {
			return array_shift( $local );
		}

		return false;
	}

	/**
	 * Get a subscription entry from product_id. If multiple subscriptions are
	 * found with the same product id and $single is set to true, will return the
	 * first one in the list, so you can use this method to get things like extension
	 * name, version, etc.
	 *
	 * @param int $product_id The product id.
	 * @param bool $single Whether to return a single subscription or all matching a product id.
	 *
	 * @return array|bool The array containing sub data or false.
	 */
	private static function _get_subscriptions_from_product_id( $product_id, $single = true ) {
		$subscriptions = wp_list_filter( self::get_subscriptions(), array( 'product_id' => $product_id ) );
		if ( ! empty( $subscriptions ) ) {
			return $single ? array_shift( $subscriptions ) : $subscriptions;
		}

		return false;
	}

	/**
	 * Additional theme style.css and plugin file headers.
	 *
	 * Format: Woo: product_id:file_id
	 */
	public static function extra_headers( $headers ) {
		$headers[] = 'Woo';
		return $headers;
	}

	/**
	 * Obtain a list of locally installed Woo extensions.
	 */
	public static function get_local_woo_plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$plugins = get_plugins();
		$woo_plugins = array();

		// Back-compat for woothemes_queue_update().
		$_compat = array();
		if ( ! empty( $GLOBALS['woothemes_queued_updates'] ) ) {
			foreach ( $GLOBALS['woothemes_queued_updates'] as $_compat_plugin ) {
				$_compat[ $_compat_plugin->file ] = array(
					'product_id' => $_compat_plugin->product_id,
					'file_id' => $_compat_plugin->file_id,
				);
			}
		}

		foreach ( $plugins as $filename => $data ) {
			if ( empty( $data['Woo'] ) && ! empty( $_compat[ $filename ] ) ) {
				$data['Woo'] = sprintf( '%d:%s', $_compat[ $filename ]['product_id'], $_compat[ $filename ]['file_id'] );
			}

			if ( empty( $data['Woo'] ) ) {
				continue;
			}

			list( $product_id, $file_id ) = explode( ':', $data['Woo'] );
			if ( empty( $product_id ) || empty( $file_id ) ) {
				continue;
			}

			$data['_filename'] = $filename;
			$data['_product_id'] = absint( $product_id );
			$data['_file_id'] = $file_id;
			$data['_type'] = 'plugin';
			$woo_plugins[ $filename ] = $data;
		}

		return $woo_plugins;
	}

	/**
	 * Get locally installed Woo themes.
	 */
	public static function get_local_woo_themes() {
		$themes = wp_get_themes();
		$woo_themes = array();

		foreach ( $themes as $theme ) {
			$header = $theme->get( 'Woo' );

			// Back-compat for theme_info.txt
			if ( ! $header ) {
				$txt = $theme->get_stylesheet_directory() . '/theme_info.txt';
				if ( is_readable( $txt ) ) {
					$txt = file_get_contents( $txt );
					$txt = preg_split( "#\s#", $txt );
					if ( count( $txt ) >= 2 ) {
						$header = sprintf( '%d:%s', $txt[0], $txt[1] );
					}
				}
			}

			if ( empty( $header ) ) {
				continue;
			}

			list( $product_id, $file_id ) = explode( ':', $header );
			if ( empty( $product_id ) || empty( $file_id ) ) {
				continue;
			}

			$data = array(
				'Name' => $theme->get( 'Name' ),
				'Version' => $theme->get( 'Version' ),
				'Woo' => $header,

				'_filename' => $theme->get_stylesheet() . '/style.css',
				'_stylesheet' => $theme->get_stylesheet(),
				'_product_id' => absint( $product_id ),
				'_file_id' => $file_id,
				'_type' => 'theme',
			);

			$woo_themes[ $data['_filename'] ] = $data;
		}

		return $woo_themes;
	}

	/**
	 * Get the connected user's subscriptions.
	 *
	 * @return array
	 */
	public static function get_subscriptions() {
		$cache_key = '_woocommerce_helper_subscriptions';
		if ( false !== ( $data = get_transient( $cache_key ) ) ) {
			return $data;
		}

		// Obtain the connected user info.
		$request = WC_Helper_API::get( 'subscriptions', array(
			'authenticated' => true,
		) );

		if ( wp_remote_retrieve_response_code( $request ) !== 200 ) {
			set_transient( $cache_key, array(), 15 * MINUTE_IN_SECONDS );
			return array();
		}

		$data = json_decode( wp_remote_retrieve_body( $request ), true );
		if ( empty( $data ) || ! is_array( $data ) ) {
			$data = array();
		}

		set_transient( $cache_key, $data, 1 * HOUR_IN_SECONDS );
		return $data;
	}

	/**
	 * Runs when any plugin is activated.
	 *
	 * Depending on the activated plugin attempts to look through available
	 * subscriptions and auto-activate one if possible, so the user does not
	 * need to visit the Helper UI at all after installing a new extension.
	 *
	 * @param string $filename The filename of the activated plugin.
	 */
	public static function activated_plugin( $filename ) {
		$plugins = self::get_local_woo_plugins();

		// Not a local woo plugin
		if ( empty( $plugins[ $filename ] ) ) {
			return;
		}

		// Make sure we have a connection.
		$auth = WC_Helper_Options::get( 'auth' );
		if ( empty( $auth ) ) {
			return;
		}

		$plugin = $plugins[ $filename ];
		$subscriptions = self::_get_subscriptions_from_product_id( $plugin['_product_id'], false );

		// No valid subscriptions for this product
		if ( empty( $subscriptions ) ) {
			return;
		}

		$subscription = null;
		foreach ( $subscriptions as $_sub ) {

			// Don't attempt to activate expired subscriptions.
			if ( $_sub['expired'] ) {
				continue;
			}

			// No more sites available in this subscription.
			if ( $_sub['sites_active'] >= $_sub['sites_max'] ) {
				continue;
			}

			// Looks good.
			$subscription = $_sub;
			break;
		}

		// No valid subscription found.
		if ( ! $subscription ) {
			return;
		}

		$request = WC_Helper_API::post( 'activate', array(
			'authenticated' => true,
			'body' => json_encode( array(
				'product_key' => $subscription['product_key'],
			) ),
		) );

		$activated = wp_remote_retrieve_response_code( $request ) === 200;
		$body = json_decode( wp_remote_retrieve_body( $request ), true );
		if ( ! $activated && ! empty( $body['code'] ) && 'already_connected' == $body['code'] ) {
			$activated = true;
		}

		if ( ! $activated ) {
			self::log( 'Could not activate a subscription upon plugin activation: ' . $$filename );
			return;
		}

		self::log( 'Auto-activated a subscripton for ' . $filename );
		self::_flush_subscriptions_cache();
	}

	/**
	 * Runs when any plugin is deactivated.
	 *
	 * When a user deactivates a plugin, attempt to deactivate any subscriptions
	 * associated with the extension.
	 *
	 * @param string $filename The filename of the deactivated plugin.
	 */
	public static function deactivated_plugin( $filename ) {
		$plugins = self::get_local_woo_plugins();

		// Not a local woo plugin
		if ( empty( $plugins[ $filename ] ) ) {
			return;
		}

		// Make sure we have a connection.
		$auth = WC_Helper_Options::get( 'auth' );
		if ( empty( $auth ) ) {
			return;
		}

		$plugin = $plugins[ $filename ];
		$subscriptions = self::_get_subscriptions_from_product_id( $plugin['_product_id'], false );
		$site_id = absint( $auth['site_id'] );

		// No valid subscriptions for this product
		if ( empty( $subscriptions ) ) {
			return;
		}

		$deactivated = 0;

		foreach ( $subscriptions as $subscription ) {
			// Don't touch subscriptions that aren't activated on this site.
			if ( ! in_array( $site_id, $subscription['connections'] ) ) {
				continue;
			}

			$request = WC_Helper_API::post( 'deactivate', array(
				'authenticated' => true,
				'body' => json_encode( array(
					'product_key' => $subscription['product_key'],
				) ),
			) );

			if ( wp_remote_retrieve_response_code( $request ) === 200 ) {
				$deactivated++;
			}
		}

		if ( $deactivated ) {
			self::log( sprintf( 'Auto-deactivated %d subscripton(s) for %s', $deactivated, $filename ) );
			self::_flush_subscriptions_cache();
		}
	}

	/**
	 * Add a note about available extension updates if Woo core has an update available.
	 */
	public static function admin_notices() {
		$screen = get_current_screen();
		if ( 'update-core' !== $screen->id ) {
			return;
		}

		// Don't nag if Woo doesn't have an update available.
		if ( ! self::_woo_core_update_available() ) {
			return;
		}

		$notice = self::_get_extensions_update_notice();
		if ( ! empty( $notice ) ) {
			echo '<div class="updated woocommerce-message"><p>' . $notice . '</p></div>';
		}
	}

	/**
	 * Add an upgrade notice if there are extensions with updates.
	 *
	 * @param string $message An existing update notice or an empty string.
	 *
	 * @return string The resulting message.
	 */
	public static function in_plugin_update_message( $message ) {
		$notice = self::_get_extensions_update_notice();
		if ( ! empty( $notice ) ) {
			$message .= '</p><p class="wc_plugin_upgrade_notice">' . $notice;
		}

		return $message;
	}

	/**
	 * Get an update notice if one or more Woo extensions has an update available.
	 *
	 * @return string|null The update notice or null if everything is up to date.
	 */
	private static function _get_extensions_update_notice() {
		$plugins = self::get_local_woo_plugins();
		$updates = WC_Helper_Updater::get_update_data();
		$available = 0;

		foreach ( $plugins as $data ) {
			if ( empty( $updates[ $data['_product_id'] ] ) ) {
				continue;
			}

			$product_id = $data['_product_id'];
			if ( version_compare( $updates[ $product_id ]['version'], $data['Version'], '>' ) ) {
				$available++;
			}
		}

		if ( ! $available ) {
			return;
		}

		/* translators: %1$s: helper url, %2$d: number of extensions */
		return sprintf( _n( 'Note: You currently have <a href="%1$s">%2$d extension</a> which should be updated first before updating WooCommerce.', 'Note: You currently have <a href="%1$s">%2$d extensions</a> which should be updated first before updating WooCommerce.', $available, 'woocommerce' ),
			admin_url( 'admin.php?page=wc-addons&section=helper' ), $available );
	}

	/**
	 * Whether WooCommerce has an update available.
	 *
	 * @return bool True if a Woo core update is available.
	 */
	private static function _woo_core_update_available() {
		$updates = get_site_transient( 'update_plugins' );
		if ( empty( $updates->response ) ) {
			return false;
		}

		if ( empty( $updates->response['woocommerce/woocommerce.php'] ) ) {
			return false;
		}

		$data = $updates->response['woocommerce/woocommerce.php'];
		if ( version_compare( WC_VERSION, $data->new_version, '>=' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Flush subscriptions cache.
	 */
	private static function _flush_subscriptions_cache() {
		delete_transient( '_woocommerce_helper_subscriptions' );
	}

	/**
	 * Flush auth cache.
	 *
	 * @access private
	 */
	public static function _flush_authentication_cache() {
		$request = WC_Helper_API::get( 'oauth/me', array(
			'authenticated' => true,
		) );

		if ( wp_remote_retrieve_response_code( $request ) !== 200 ) {
			return false;
		}

		$user_data = json_decode( wp_remote_retrieve_body( $request ), true );
		if ( ! $user_data ) {
			return false;
		}

		WC_Helper_Options::update( 'auth_user_data', array(
			'name' => $user_data['name'],
			'email' => $user_data['email'],
		) );

		return true;
	}

	/**
	 * Flush updates cache.
	 */
	private static function _flush_updates_cache() {
		WC_Helper_Updater::flush_updates_cache();
	}

	/**
	 * Sort subscriptions by the product_name.
	 *
	 * @param array $a Subscription array
	 * @param array $b Subscription array
	 *
	 * @return int
	 */
	public static function _sort_by_product_name( $a, $b ) {
		return strcmp( $a['product_name'], $b['product_name'] );
	}

	/**
	 * Sort subscriptions by the Name.
	 *
	 * @param array $a Product array
	 * @param array $b Product array
	 *
	 * @return int
	 */
	public static function _sort_by_name( $a, $b ) {
		return strcmp( $a['Name'], $b['Name'] );
	}

	/**
	 * Log a helper event.
	 *
	 * @param string $message Log message.
	 * @param string $level Optional, defaults to info, valid levels:
	 *     emergency|alert|critical|error|warning|notice|info|debug
	 */
	public static function log( $message, $level = 'info' ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		if ( ! isset( self::$log ) ) {
			self::$log = wc_get_logger();
		}

		self::$log->log( $level, $message, array( 'source' => 'helper' ) );
	}
}

WC_Helper::load();
