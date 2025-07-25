<?php
/**
 * The update helper for WooCommerce.com plugins.
 *
 * @class WC_Helper_Updater
 * @package WooCommerce\Admin\Helper
 */

use Automattic\WooCommerce\Admin\PluginsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Helper_Updater Class
 *
 * Contains the logic to fetch available updates and hook into Core's update
 * routines to serve WooCommerce.com-provided packages.
 */
class WC_Helper_Updater {

	/**
	 * Loads the class, runs on init.
	 */
	public static function load() {
		add_action( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'transient_update_plugins' ), 21, 1 );
		add_action( 'pre_set_site_transient_update_themes', array( __CLASS__, 'transient_update_themes' ), 21, 1 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'upgrader_process_complete' ) );
		add_action( 'upgrader_pre_download', array( __CLASS__, 'block_expired_updates' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'add_hook_for_modifying_update_notices' ) );
	}

	/**
	 * Add the hook for modifying default WPCore update notices on the plugins management page.
	 */
	public static function add_hook_for_modifying_update_notices() {
		if ( ! WC_Woo_Update_Manager_Plugin::is_plugin_active() || ! WC_Helper::is_site_connected() ) {
			add_action( 'load-plugins.php', array( __CLASS__, 'setup_update_plugins_messages' ), 11 );
		}
		if ( WC_Helper::is_site_connected() ) {
			add_action( 'load-plugins.php', array( __CLASS__, 'setup_message_for_expired_and_expiring_subscriptions' ), 11 );
			add_action( 'load-plugins.php', array( __CLASS__, 'setup_message_for_plugins_without_subscription' ), 11 );
		}
	}

	/**
	 * Add the hook for modifying default WPCore update notices on the plugins management page.
	 * This is for plugins with expired or expiring subscriptions.
	 */
	public static function setup_message_for_expired_and_expiring_subscriptions() {
		foreach ( WC_Helper::get_local_woo_plugins() as $plugin ) {
			add_action( 'in_plugin_update_message-' . $plugin['_filename'], array( __CLASS__, 'display_notice_for_expired_and_expiring_subscriptions' ), 10, 2 );
		}
	}

	/**
	 * Add the hook for modifying default WPCore update notices on the plugins management page.
	 * This is for plugins without a subscription.
	 */
	public static function setup_message_for_plugins_without_subscription() {
		foreach ( WC_Helper::get_local_woo_plugins() as $plugin ) {
			add_action( 'in_plugin_update_message-' . $plugin['_filename'], array( __CLASS__, 'display_notice_for_plugins_without_subscription' ), 10, 2 );
		}
	}

	/**
	 * Runs in a cron thread, or in a visitor thread if triggered
	 * by _maybe_update_plugins(), or in an auto-update thread.
	 *
	 * @param object $transient The update_plugins transient object.
	 *
	 * @return object The same or a modified version of the transient.
	 */
	public static function transient_update_plugins( $transient ) {
		$update_data = self::get_update_data();

		foreach ( WC_Helper::get_local_woo_plugins() as $plugin ) {
			if ( empty( $update_data[ $plugin['_product_id'] ] ) ) {
				continue;
			}

			$data     = $update_data[ $plugin['_product_id'] ];
			$filename = $plugin['_filename'];

			$item = array(
				'id'             => 'woocommerce-com-' . $plugin['_product_id'],
				'slug'           => 'woocommerce-com-' . $data['slug'],
				'plugin'         => $filename,
				'new_version'    => $data['version'],
				'url'            => $data['url'],
				'package'        => '',
				'upgrade_notice' => $data['upgrade_notice'],
			);

			/**
			 * Filters the Woo plugin data before saving it in transient used for updates.
			 *
			 * @since 8.7.0
			 *
			 * @param array $item Plugin item to modify.
			 * @param array $data Subscription data fetched from Helper API for the plugin.
			 * @param int   $product_id Woo product id assigned to the plugin.
			 */
			$item = apply_filters( 'update_woo_com_subscription_details', $item, $data, $plugin['_product_id'] );

			if ( isset( $data['requires_php'] ) ) {
				$item['requires_php'] = $data['requires_php'];
			}

			if ( $transient instanceof stdClass ) {
				if ( version_compare( $plugin['Version'], $data['version'], '<' ) ) {
					$transient->response[ $filename ] = (object) $item;
					unset( $transient->no_update[ $filename ] );
				} else {
					$transient->no_update[ $filename ] = (object) $item;
					unset( $transient->response[ $filename ] );
				}
			}
		}

		if ( $transient instanceof stdClass ) {
			$translations            = self::get_translations_update_data();
			$transient->translations = array_merge( isset( $transient->translations ) ? $transient->translations : array(), $translations );
		}

		return $transient;
	}

	/**
	 * Runs on pre_set_site_transient_update_themes, provides custom
	 * packages for WooCommerce.com-hosted extensions.
	 *
	 * @param object $transient The update_themes transient object.
	 *
	 * @return object The same or a modified version of the transient.
	 */
	public static function transient_update_themes( $transient ) {
		$update_data = self::get_update_data();

		foreach ( WC_Helper::get_local_woo_themes() as $theme ) {
			if ( empty( $update_data[ $theme['_product_id'] ] ) ) {
				continue;
			}

			$data = $update_data[ $theme['_product_id'] ];
			$slug = $theme['_stylesheet'];

			$item = array(
				'theme'       => $slug,
				'new_version' => $data['version'],
				'url'         => $data['url'],
				'package'     => '',
			);

			/**
			 * Filters the Woo plugin data before saving it in transient used for updates.
			 *
			 * @since 8.7.0
			 *
			 * @param array $item Plugin item to modify.
			 * @param array $data Subscription data fetched from Helper API for the plugin.
			 * @param int   $product_id Woo product id assigned to the plugin.
			 */
			$item = apply_filters( 'update_woo_com_subscription_details', $item, $data, $theme['_product_id'] );

			if ( version_compare( $theme['Version'], $data['version'], '<' ) ) {
				$transient->response[ $slug ] = $item;
			} else {
				unset( $transient->response[ $slug ] );
				$transient->checked[ $slug ] = $data['version'];
			}
		}

		return $transient;
	}

	/**
	 * Runs on load-plugins.php, adds a hook to show a custom plugin update message for WooCommerce.com hosted plugins.
	 *
	 * @return void.
	 */
	public static function setup_update_plugins_messages() {
		$is_site_connected = WC_Helper::is_site_connected();
		foreach ( WC_Helper::get_local_woo_plugins() as $plugin ) {
			$filename = $plugin['_filename'];
			if ( $is_site_connected ) {
				add_action( 'in_plugin_update_message-' . $filename, array( __CLASS__, 'add_install_marketplace_plugin_message' ), 10, 2 );
			} else {
				add_action( 'in_plugin_update_message-' . $filename, array( __CLASS__, 'add_connect_woocom_plugin_message' ) );
			}
		}
	}

	/**
	 * Runs on in_plugin_update_message-{file-name}, show a message to connect to woocommerce.com for unconnected stores
	 *
	 * @return void.
	 */
	public static function add_connect_woocom_plugin_message() {
		$connect_page_url = add_query_arg(
			array(
				'page'         => 'wc-admin',
				'tab'          => 'my-subscriptions',
				'path'         => rawurlencode( '/extensions' ),
				'utm_source'   => 'pu',
				'utm_campaign' => 'pu_plugin_screen_connect',
			),
			admin_url( 'admin.php' )
		);

		printf(
			wp_kses(
			/* translators: 1: Woo Update Manager plugin install URL */
				__( ' <a href="%1$s" class="woocommerce-connect-your-store">Connect your store</a> to woocommerce.com to update.', 'woocommerce' ),
				array(
					'a' => array(
						'href'  => array(),
						'class' => array(),
					),
				)
			),
			esc_url( $connect_page_url ),
		);
	}

	/**
	 * Runs on in_plugin_update_message-{file-name}, show a message to install the Woo Marketplace plugin, on plugin update notification,
	 * if the Woo Marketplace plugin isn't already installed.
	 *
	 * @param object $plugin_data TAn array of plugin metadata.
	 * @param object $response  An object of metadata about the available plugin update.
	 *
	 * @return void.
	 */
	public static function add_install_marketplace_plugin_message( $plugin_data, $response ) {
		if ( ! empty( $response->package ) || WC_Woo_Update_Manager_Plugin::is_plugin_active() ) {
			return;
		}

		if ( ! WC_Woo_Update_Manager_Plugin::is_plugin_installed() ) {
			printf(
				wp_kses(
					/* translators: 1: Woo Update Manager plugin install URL */
					__( ' <a href="%1$s">Install WooCommerce.com Update Manager</a> to update.', 'woocommerce' ),
					array(
						'a' => array(
							'href' => array(),
						),
					)
				),
				esc_url( WC_Woo_Update_Manager_Plugin::generate_install_url() ),
			);
			return;
		}

		if ( ! WC_Woo_Update_Manager_Plugin::is_plugin_active() ) {
			echo esc_html_e( ' Activate WooCommerce.com Update Manager to update.', 'woocommerce' );
		}
	}

	/**
	 * Runs on in_plugin_update_message-{file-name}, show a message if plugins subscription expired or expiring soon.
	 *
	 * @param object $plugin_data An array of plugin metadata.
	 * @param object $response  An object of metadata about the available plugin update.
	 *
	 * @return void.
	 */
	public static function display_notice_for_expired_and_expiring_subscriptions( $plugin_data, $response ) {
		// Extract product ID from the response.
		$product_id = preg_replace( '/[^0-9]/', '', $response->id );

		$installed_or_unconnected = array_merge(
			WC_Helper::get_installed_subscriptions(),
			WC_Helper::get_unconnected_subscriptions()
		);

		// Product subscriptions.
		$subscriptions = wp_list_filter( $installed_or_unconnected, array( 'product_id' => $product_id ) );
		if ( empty( $subscriptions ) ) {
			return;
		}

		$expired_subscription = current(
			array_filter(
				$subscriptions,
				function ( $subscription ) {
					return ! empty( $subscription['expired'] ) && ! $subscription['lifetime'];
				}
			)
		);

		$expiring_subscription = current(
			array_filter(
				$subscriptions,
				function ( $subscription ) {
					return ! empty( $subscription['expiring'] ) && ! $subscription['autorenew'];
				}
			)
		);

		// Prepare the expiry notice based on subscription status.
		$expiry_notice = '';
		if ( ! empty( $expired_subscription ) ) {

			$renew_link = add_query_arg(
				array(
					'add-to-cart'  => $product_id,
					'utm_source'   => 'pu',
					'utm_campaign' => 'pu_plugin_screen_renew',
				),
				PluginsHelper::WOO_CART_PAGE_URL
			);

			/* translators: 1: Product regular price */
			$product_price = ! empty( $expired_subscription['product_regular_price'] ) ? sprintf( __( 'for %s ', 'woocommerce' ), esc_html( $expired_subscription['product_regular_price'] ) ) : '';

			$expiry_notice = sprintf(
			/* translators: 1: URL to My Subscriptions page 2: Product price */
				__( ' Your subscription expired, <a href="%1$s" class="woocommerce-renew-subscription">renew %2$s</a>to update.', 'woocommerce' ),
				esc_url( $renew_link ),
				$product_price
			);
		} elseif ( ! empty( $expiring_subscription ) ) {
			$renew_link = add_query_arg(
				array(
					'utm_source'   => 'pu',
					'utm_campaign' => 'pu_plugin_screen_enable_autorenew',
				),
				PluginsHelper::WOO_SUBSCRIPTION_PAGE_URL
			);

			$expiry_notice = sprintf(
			/* translators: 1: Expiry date 1: URL to My Subscriptions page */
				__( ' Your subscription expires on %1$s, <a href="%2$s" class="woocommerce-enable-autorenew">enable auto-renew</a> to continue receiving updates.', 'woocommerce' ),
				date_i18n( 'F jS', $expiring_subscription['expires'] ),
				esc_url( $renew_link )
			);
		}

		// Display the expiry notice.
		if ( ! empty( $expiry_notice ) ) {
			echo wp_kses(
				$expiry_notice,
				array(
					'a' => array(
						'href'  => array(),
						'class' => array(),
					),
				)
			);
		}
	}

	/**
	 * Runs on in_plugin_update_message-{file-name}, show a message if plugin is without a subscription.
	 * Only Woo local plugins are passed to this function.
	 *
	 * @see setup_message_for_plugins_without_subscription
	 * @param object $plugin_data An array of plugin metadata.
	 * @param object $response  An object of metadata about the available plugin update.
	 *
	 * @return void.
	 */
	public static function display_notice_for_plugins_without_subscription( $plugin_data, $response ) {
		// Extract product ID from the response.
		$product_id = preg_replace( '/[^0-9]/', '', $response->id );

		if ( WC_Helper::has_product_subscription( $product_id ) ) {
			return;
		}

		// Prepare the expiry notice based on subscription status.
		$purchase_link = add_query_arg(
			array(
				'add-to-cart'  => $product_id,
				'utm_source'   => 'pu',
				'utm_campaign' => 'pu_plugin_screen_purchase',
			),
			PluginsHelper::WOO_CART_PAGE_URL,
		);

		$notice = sprintf(
			/* translators: 1: URL to My Subscriptions page */
			__( ' You don\'t have a subscription, <a href="%1$s" class="woocommerce-purchase-subscription">subscribe</a> to update.', 'woocommerce' ),
			esc_url( $purchase_link ),
		);

		// Display the expiry notice.
		echo wp_kses(
			$notice,
			array(
				'a' => array(
					'href'  => array(),
					'class' => array(),
				),
			)
		);
	}

	/**
	 * Get update data for all plugins.
	 *
	 * @return array Update data {product_id => data}
	 * @see get_update_data
	 */
	public static function get_available_extensions_downloads_data() {
		$payload = array();

		// Scan subscriptions.
		$subscriptions = WC_Helper::get_subscriptions();

		foreach ( $subscriptions as $subscription ) {
			$payload[ $subscription['product_id'] ] = array(
				'product_id' => $subscription['product_id'],
				'file_id'    => '',
			);
		}

		// Scan local plugins which may or may not have a subscription.
		foreach ( WC_Helper::get_local_woo_plugins() as $data ) {
			if ( ! isset( $payload[ $data['_product_id'] ] ) ) {
				$payload[ $data['_product_id'] ] = array(
					'product_id' => $data['_product_id'],
				);
			}

			$payload[ $data['_product_id'] ]['file_id'] = $data['_file_id'];
		}

		return self::_update_check( $payload );
	}

	/**
	 * Get update data for all extensions.
	 *
	 * Scans through all subscriptions for the connected user, as well
	 * as all Woo extensions without a subscription, and obtains update
	 * data for each product.
	 *
	 * @return array Update data {product_id => data}
	 */
	public static function get_update_data() {
		$payload = array();

		// Scan subscriptions.
		$subscriptions = WC_Helper::get_subscriptions();

		foreach ( $subscriptions as $subscription ) {
			$payload[ $subscription['product_id'] ] = array(
				'product_id' => $subscription['product_id'],
				'file_id'    => '',
			);
		}

		// Scan local plugins which may or may not have a subscription.
		foreach ( WC_Helper::get_local_woo_plugins() as $data ) {
			if ( ! isset( $payload[ $data['_product_id'] ] ) ) {
				$payload[ $data['_product_id'] ] = array(
					'product_id' => $data['_product_id'],
				);
			}

			$payload[ $data['_product_id'] ]['file_id'] = $data['_file_id'];
		}

		// Scan local themes.
		foreach ( WC_Helper::get_local_woo_themes() as $data ) {
			if ( ! isset( $payload[ $data['_product_id'] ] ) ) {
				$payload[ $data['_product_id'] ] = array(
					'product_id' => $data['_product_id'],
				);
			}

			$payload[ $data['_product_id'] ]['file_id'] = $data['_file_id'];
		}

		return self::_update_check( $payload );
	}

	/**
	 * Get translations updates information.
	 *
	 * Scans through all subscriptions for the connected user, as well
	 * as all Woo extensions without a subscription, and obtains update
	 * data for each product.
	 *
	 * @return array Update data {product_id => data}
	 */
	public static function get_translations_update_data() {
		$payload = array();

		$installed_translations = wp_get_installed_translations( 'plugins' );

		$locales = array_values( get_available_languages() );
		/**
		 * Filters the locales requested for plugin translations.
		 *
		 * @since 3.7.0
		 * @since 4.5.0 The default value of the `$locales` parameter changed to include all locales.
		 *
		 * @param array $locales Plugin locales. Default is all available locales of the site.
		 */
		$locales = apply_filters( 'plugins_update_check_locales', $locales );
		$locales = array_unique( $locales );

		// No locales, the response will be empty, we can return now.
		if ( empty( $locales ) ) {
			return array();
		}

		// Scan local plugins which may or may not have a subscription.
		$plugins            = WC_Helper::get_local_woo_plugins();
		$active_woo_plugins = array_intersect( array_keys( $plugins ), get_option( 'active_plugins', array() ) );

		/*
		* Use only plugins that are subscribed to the automatic translations updates.
		*/
		$active_for_translations = array_filter(
			$active_woo_plugins,
			function ( $plugin ) use ( $plugins ) {
				/**
				 * Filters the plugins that are subscribed to the automatic translations updates.
				 *
				 * @since 3.7.0
				 */
				return apply_filters( 'woocommerce_translations_updates_for_' . $plugins[ $plugin ]['slug'], false );
			}
		);

		// Nothing to check for, exit.
		if ( empty( $active_for_translations ) ) {
			return array();
		}

		if ( wp_doing_cron() ) {
			$timeout = 30;
		} else {
			// Three seconds, plus one extra second for every 10 plugins.
			$timeout = 3 + (int) ( count( $active_for_translations ) / 10 );
		}

		$request_body = array(
			'locales' => $locales,
			'plugins' => array(),
		);

		foreach ( $active_for_translations as $active_plugin ) {
			$plugin                                     = $plugins[ $active_plugin ];
			$request_body['plugins'][ $plugin['slug'] ] = array( 'version' => $plugin['Version'] );
		}

		$raw_response = wp_remote_post(
			'https://translate.wordpress.com/api/translations-updates/woocommerce',
			array(
				'body'    => wp_json_encode( $request_body ),
				'headers' => array( 'Content-Type: application/json' ),
				'timeout' => $timeout,
			)
		);

		// Something wrong happened on the translate server side.
		$response_code = wp_remote_retrieve_response_code( $raw_response );
		if ( 200 !== $response_code ) {
			return array();
		}

		$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );

		// API error, api returned but something was wrong.
		if ( array_key_exists( 'success', $response ) && false === $response['success'] ) {
			return array();
		}

		$translations = array();

		foreach ( $response['data'] as $plugin_name => $language_packs ) {
			foreach ( $language_packs as $language_pack ) {
				// Maybe we have this language pack already installed so lets check revision date.
				if ( array_key_exists( $plugin_name, $installed_translations ) && array_key_exists( $language_pack['wp_locale'], $installed_translations[ $plugin_name ] ) ) {
					$installed_translation_revision_time = new DateTime( $installed_translations[ $plugin_name ][ $language_pack['wp_locale'] ]['PO-Revision-Date'] );
					$new_translation_revision_time       = new DateTime( $language_pack['last_modified'] );
					// Skip if translation language pack is not newer than what is installed already.
					if ( $new_translation_revision_time <= $installed_translation_revision_time ) {
						continue;
					}
				}
				$translations[] = array(
					'type'       => 'plugin',
					'slug'       => $plugin_name,
					'language'   => $language_pack['wp_locale'],
					'version'    => $language_pack['version'],
					'updated'    => $language_pack['last_modified'],
					'package'    => $language_pack['package'],
					'autoupdate' => true,
				);
			}
		}

		return $translations;
	}

	/**
	 * Run an update check API call.
	 *
	 * The call is cached based on the payload (product ids, file ids). If
	 * the payload changes, the cache is going to miss.
	 *
	 * @param array $payload Information about the plugin to update.
	 * @return array Update data for each requested product.
	 */
	private static function _update_check( $payload ) {
		if ( empty( $payload ) ) {
			return array();
		}
		ksort( $payload );
		$hash = md5( wp_json_encode( $payload ) );

		$cache_key = '_woocommerce_helper_updates';
		$data      = get_transient( $cache_key );
		if ( false !== $data ) {
			if ( hash_equals( $hash, $data['hash'] ) ) {
				return $data['products'];
			}
		}

		$data = array(
			'hash'     => $hash,
			'updated'  => time(),
			'products' => array(),
			'errors'   => array(),
		);

		if ( WC_Helper::is_site_connected() ) {
			$request = WC_Helper_API::post(
				'update-check',
				array(
					'body'          => wp_json_encode( array( 'products' => $payload ) ),
					'authenticated' => true,
				)
			);
		} else {
			$request = WC_Helper_API::post(
				'update-check-public',
				array(
					'body' => wp_json_encode( array( 'products' => $payload ) ),
				)
			);
		}

		if ( wp_remote_retrieve_response_code( $request ) !== 200 ) {
			$data['errors'][] = 'http-error';
		} else {
			$data['products'] = json_decode( wp_remote_retrieve_body( $request ), true );
		}

		set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );
		return $data['products'];
	}

	/**
	 * Get the number of products that have updates.
	 *
	 * @return int The number of products with updates.
	 */
	public static function get_updates_count() {
		$cache_key = '_woocommerce_helper_updates_count';
		$count     = get_transient( $cache_key );
		if ( false !== $count ) {
			return $count;
		}

		// Don't fetch any new data since this function in high-frequency.
		if ( ! get_transient( '_woocommerce_helper_subscriptions' ) ) {
			return 0;
		}

		if ( ! get_transient( '_woocommerce_helper_updates' ) ) {
			return 0;
		}

		$count       = 0;
		$update_data = self::get_update_data();

		if ( empty( $update_data ) ) {
			set_transient( $cache_key, $count, 12 * HOUR_IN_SECONDS );
			return $count;
		}

		// Scan local plugins.
		foreach ( WC_Helper::get_local_woo_plugins() as $plugin ) {
			if ( empty( $update_data[ $plugin['_product_id'] ] ) ) {
				continue;
			}

			if ( ! is_plugin_active( $plugin['_filename'] ) ) {
				continue;
			}

			if ( version_compare( $plugin['Version'], $update_data[ $plugin['_product_id'] ]['version'], '<' ) ) {
				++$count;
			}
		}

		// Scan local themes.
		foreach ( WC_Helper::get_local_woo_themes() as $theme ) {
			if ( empty( $update_data[ $theme['_product_id'] ] ) ) {
				continue;
			}

			if ( get_stylesheet() !== $theme['_stylesheet'] ) {
				continue;
			}

			if ( version_compare( $theme['Version'], $update_data[ $theme['_product_id'] ]['version'], '<' ) ) {
				++$count;
			}
		}

		set_transient( $cache_key, $count, 12 * HOUR_IN_SECONDS );

		return $count;
	}

	/**
	 * Get the update count to based on the status of the site.
	 *
	 * @return int
	 */
	public static function get_updates_count_based_on_site_status() {
		if ( ! WC_Helper::is_site_connected() ) {
			return 0;
		}

		$count = self::get_updates_count() ?? 0;
		if ( ! WC_Woo_Update_Manager_Plugin::is_plugin_installed() || ! WC_Woo_Update_Manager_Plugin::is_plugin_active() ) {
			++$count;
		}

		return $count;
	}

	/**
	 * Get the type of woo connect notice to be shown in the WC Settings and Marketplace pages.
	 * - If a store is connected to woocommerce.com or has no installed woo plugins, return 'none'.
	 * - If a store has installed woo plugins but no updates, return 'short'.
	 * - If a store has an installed woo plugin with update, return 'long'.
	 *
	 * @return string The notice type, 'none', 'short', or 'long'.
	 */
	public static function get_woo_connect_notice_type() {
		if ( WC_Helper::is_site_connected() ) {
			return 'none';
		}

		$woo_plugins = WC_Helper::get_local_woo_plugins();

		if ( empty( $woo_plugins ) ) {
			return 'none';
		}

		$update_data = self::get_update_data();

		if ( empty( $update_data ) ) {
			return 'short';
		}

		// Scan local plugins.
		foreach ( $woo_plugins as $plugin ) {
			if ( empty( $update_data[ $plugin['_product_id'] ] ) ) {
				continue;
			}

			if ( version_compare( $plugin['Version'], $update_data[ $plugin['_product_id'] ]['version'], '<' ) ) {
				return 'long';
			}
		}

		return 'short';
	}

	/**
	 * Return the updates count markup.
	 *
	 * @return string Updates count markup, empty string if no updates avairable.
	 */
	public static function get_updates_count_html() {
		$count      = self::get_updates_count_based_on_site_status();
		$count_html = sprintf( ' <span class="update-plugins count-%d"><span class="update-count">%d</span></span>', $count, number_format_i18n( $count ) );

		return $count_html;
	}

	/**
	 * Flushes cached update data.
	 */
	public static function flush_updates_cache() {
		delete_transient( '_woocommerce_helper_updates' );
		delete_transient( '_woocommerce_helper_updates_count' );
		delete_site_transient( 'update_plugins' );
		delete_site_transient( 'update_themes' );
	}

	/**
	 * Fires when a user successfully updated a theme or a plugin.
	 */
	public static function upgrader_process_complete() {
		delete_transient( '_woocommerce_helper_updates_count' );
	}

	/**
	 * Hooked into the upgrader_pre_download filter in order to better handle error messaging around expired
	 * plugin updates. Initially we were using an empty string, but the error message that no_package
	 * results in does not fit the cause.
	 *
	 * @since 4.1.0
	 * @param bool   $reply Holds the current filtered response.
	 * @param string $package The path to the package file for the update.
	 * @return false|WP_Error False to proceed with the update as normal, anything else to be returned instead of updating.
	 */
	public static function block_expired_updates( $reply, $package ) {
		// Don't override a reply that was set already.
		if ( false !== $reply ) {
			return $reply;
		}

		// Only for packages with expired subscriptions.
		if ( 0 !== strpos( $package, 'woocommerce-com-expired-' ) ) {
			return false;
		}

		return new WP_Error(
			'woocommerce_subscription_expired',
			sprintf(
				// translators: %s: URL of WooCommerce.com subscriptions tab.
				__( 'Please visit the <a href="%s" target="_blank">subscriptions page</a> and renew to continue receiving updates.', 'woocommerce' ),
				esc_url( admin_url( 'admin.php?page=wc-admin&tab=my-subscriptions&path=%2Fextensions' ) )
			)
		);
	}
}

WC_Helper_Updater::load();
