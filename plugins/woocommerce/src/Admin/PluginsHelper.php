<?php
/**
 * PluginsHelper
 *
 * Helper class for the site's plugins.
 */

namespace Automattic\WooCommerce\Admin;

use ActionScheduler;
use ActionScheduler_DBStore;
use ActionScheduler_QueueRunner;
use Automatic_Upgrader_Skin;
use Automattic\WooCommerce\Admin\PluginsInstallLoggers\AsyncPluginsInstallLogger;
use Automattic\WooCommerce\Admin\PluginsInstallLoggers\PluginsInstallLogger;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;
use Automattic\WooCommerce\Utilities\PluginUtil;
use Plugin_Upgrader;
use WC_Helper;
use WC_Helper_Updater;
use WP_Error;
use WP_Upgrader;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Class PluginsHelper
 */
class PluginsHelper {

	/**
	 * Subscription notices in Woo screens are shown in clear priority order, first
	 * expired, and if those don't exist, expiring, and finally if none of those exist,
	 * then missing. This keeps track of whether we can show the next set of notices.
	 *
	 * @var bool
	 */
	public static $subscription_usage_notices_already_shown = false;

	/**
	 * The URL for the WooCommerce subscription page.
	 */
	const WOO_SUBSCRIPTION_PAGE_URL = 'https://woocommerce.com/my-account/my-subscriptions/';

	/**
	 * The URL for the WooCommerce.com cart page.
	 */
	const WOO_CART_PAGE_URL = 'https://woocommerce.com/cart/';

	/**
	 * The URL for the WooCommerce.com add payment method page.
	 */
	const WOO_ADD_PAYMENT_METHOD_URL = 'https://woocommerce.com/my-account/add-payment-method/';

	/**
	 * Meta key for dismissing expired subscription notices.
	 */
	const DISMISS_EXPIRED_SUBS_NOTICE = 'woo_subscription_expired_notice_dismiss';

	/**
	 * Meta key for dismissing expiring subscription notices
	 */
	const DISMISS_EXPIRING_SUBS_NOTICE = 'woo_subscription_expiring_notice_dismiss';

	/**
	 * Meta key for dismissing missing subscription notices
	 */
	const DISMISS_MISSING_SUBS_NOTICE = 'woo_subscription_missing_notice_dismiss';

	/**
	 * Meta key for dismissing disconnected notice
	 */
	const DISMISS_DISCONNECT_NOTICE = 'woo_disconnect_notice_dismiss';

	/**
	 * Meta key for dismissing connected notice
	 */
	const DISMISS_CONNECT_NOTICE = 'woo_connect_notice_dismiss';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_plugins_install_callback', array( __CLASS__, 'install_plugins' ), 10, 2 );
		add_action( 'woocommerce_plugins_install_and_activate_async_callback', array( __CLASS__, 'install_and_activate_plugins_async_callback' ), 10, 3 );
		add_action( 'woocommerce_plugins_activate_callback', array( __CLASS__, 'activate_plugins' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_show_connect_notice_in_plugin_list' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_scripts_for_connect_notice' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_scripts_for_notices_in_plugins' ) );
	}

	/**
	 * Get the path to the plugin file relative to the plugins directory from the plugin slug.
	 *
	 * E.g. 'woocommerce' returns 'woocommerce/woocommerce.php'
	 *
	 * @param string $slug Plugin slug to get path for.
	 *
	 * @return string|false The plugin path or false if the plugin is not installed.
	 */
	public static function get_plugin_path_from_slug( $slug ) {
		$plugins = get_plugins();

		if ( strstr( $slug, '/' ) ) {
			// The slug is already a plugin path.
			return $slug;
		}

		foreach ( $plugins as $plugin_path => $data ) {
			$path_parts = explode( '/', $plugin_path );
			if ( $path_parts[0] === $slug ) {
				return $plugin_path;
			}
		}

		return false;
	}

	/**
	 * Get an array of installed plugin slugs.
	 *
	 * @return array
	 */
	public static function get_installed_plugin_slugs() {
		return array_map(
			function ( $plugin_path ) {
				$path_parts = explode( '/', $plugin_path );

				return $path_parts[0];
			},
			array_keys( get_plugins() )
		);
	}

	/**
	 * Get an array of installed plugins with their file paths as a key value pair.
	 *
	 * @return array
	 */
	public static function get_installed_plugins_paths() {
		$plugins           = get_plugins();
		$installed_plugins = array();

		foreach ( $plugins as $path => $plugin ) {
			$path_parts                 = explode( '/', $path );
			$slug                       = $path_parts[0];
			$installed_plugins[ $slug ] = $path;
		}

		return $installed_plugins;
	}

	/**
	 * Get an array of active plugin slugs.
	 *
	 * The list will include both network active and site active plugins.
	 *
	 * @return array The list of active plugin slugs.
	 */
	public static function get_active_plugin_slugs() {
		return array_unique(
			array_map(
				function ( $absolute_path ) {
					// Make the path relative to the plugins directory.
					$plugin_path = str_replace( WP_PLUGIN_DIR . '/', '', $absolute_path );

					// Split the path to get the plugin slug (aka the directory name).
					$path_parts = explode( '/', $plugin_path );

					return $path_parts[0];
				},
				// Use this method as it is the most bulletproof way to get the active plugins.
				wc_get_container()->get( PluginUtil::class )->get_all_active_valid_plugins()
			)
		);
	}

	/**
	 * Checks if a plugin is installed.
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory or the plugin directory name.
	 *
	 * @return bool
	 */
	public static function is_plugin_installed( $plugin ) {
		$plugin_path = self::get_plugin_path_from_slug( $plugin );

		return $plugin_path ? array_key_exists( $plugin_path, get_plugins() ) : false;
	}

	/**
	 * Checks if a plugin is active.
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory or the plugin directory name.
	 *
	 * @return bool
	 */
	public static function is_plugin_active( $plugin ) {
		$plugin_path = self::get_plugin_path_from_slug( $plugin );

		return $plugin_path && \is_plugin_active( $plugin_path );
	}

	/**
	 * Get plugin data.
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory or the plugin directory name.
	 *
	 * @return array|false
	 */
	public static function get_plugin_data( $plugin ) {
		$plugin_path = self::get_plugin_path_from_slug( $plugin );
		$plugins     = get_plugins();

		return isset( $plugins[ $plugin_path ] ) ? $plugins[ $plugin_path ] : false;
	}

	/**
	 * Install an array of plugins.
	 *
	 * @param array                     $plugins Plugins to install.
	 * @param PluginsInstallLogger|null $logger an optional logger.
	 * @param string|null               $source place where the request is coming from.
	 *
	 * @return array
	 */
	public static function install_plugins( $plugins, ?PluginsInstallLogger $logger = null, ?string $source = null ) {
		/**
		 * Filter the list of plugins to install.
		 *
		 * @param array $plugins A list of the plugins to install.
		 *
		 * @since 6.4.0
		 */
		$plugins = apply_filters( 'woocommerce_admin_plugins_pre_install', $plugins );

		if ( empty( $plugins ) || ! is_array( $plugins ) ) {
			return new WP_Error(
				'woocommerce_plugins_invalid_plugins',
				__( 'Plugins must be a non-empty array.', 'woocommerce' )
			);
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		include_once ABSPATH . '/wp-admin/includes/admin.php';
		include_once ABSPATH . '/wp-admin/includes/plugin-install.php';
		include_once ABSPATH . '/wp-admin/includes/plugin.php';
		include_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . '/wp-admin/includes/class-plugin-upgrader.php';

		$existing_plugins   = self::get_installed_plugins_paths();
		$installed_plugins  = array();
		$results            = array();
		$time               = array();
		$errors             = new WP_Error();
		$install_start_time = time();

		foreach ( $plugins as $plugin ) {
			$slug = sanitize_key( $plugin );
			$logger && $logger->install_requested( $plugin );

			if ( isset( $existing_plugins[ $slug ] ) ) {
				$installed_plugins[] = $plugin;
				$logger && $logger->installed( $plugin, 0 );
				continue;
			}

			$start_time = microtime( true );

			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $slug,
					'fields' => array(
						'sections' => false,
					),
				)
			);

			if ( is_wp_error( $api ) ) {
				$properties = array(
					'error_message'     => sprintf(
						// translators: %s: plugin slug (example: woocommerce-services).
						__(
							'The requested plugin `%s` could not be installed. Plugin API call failed.',
							'woocommerce'
						),
						$slug
					),
					'api_error_message' => $api->get_error_message(),
					'slug'              => $slug,
				);
				wc_admin_record_tracks_event( 'install_plugin_error', $properties );

				/**
				 * Action triggered when a plugin API call failed.
				 *
				 * @param string $slug The plugin slug.
				 * @param WP_Error $api The API response.
				 *
				 * @since 6.4.0
				 */
				do_action( 'woocommerce_plugins_install_api_error', $slug, $api );

				$error_message = sprintf(
				/* translators: %s: plugin slug (example: woocommerce-services) */
					__( 'The requested plugin `%s` could not be installed. Plugin API call failed.', 'woocommerce' ),
					$slug
				);

				$errors->add( $plugin, $error_message );
				$logger && $logger->add_error( $plugin, $error_message );

				continue;
			}

			/**
			 * Action triggered before a plugin is installed.
			 *
			 * @since 9.8
			 */
			do_action( 'woocommerce_plugins_install_before', $slug, $source );

			$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
			$result   = $upgrader->install( $api->download_link );
			// result can be false or WP_Error.
			$results[ $plugin ] = $result;
			$time[ $plugin ]    = round( ( microtime( true ) - $start_time ) * 1000 );

			if ( is_wp_error( $result ) || is_null( $result ) ) {
				$properties = array(
					'error_message'         => sprintf(
						/* translators: %s: plugin slug (example: woocommerce-services) */
						__(
							'The requested plugin `%s` could not be installed.',
							'woocommerce'
						),
						$slug
					),
					'slug'                  => $slug,
					'api_version'           => $api->version,
					'api_download_link'     => $api->download_link,
					'upgrader_skin_message' => implode( ',', $upgrader->skin->get_upgrade_messages() ),
					'result'                => is_wp_error( $result ) ? $result->get_error_message() : 'null',
				);
				wc_admin_record_tracks_event( 'install_plugin_error', $properties );

				/**
				 * Action triggered when a plugin installation fails.
				 *
				 * @param string $slug The plugin slug.
				 * @param object $api The plugin API object.
				 * @param WP_Error|null $result The result of the plugin installation.
				 * @param Plugin_Upgrader $upgrader The plugin upgrader.
				 *
				 * @since 6.4.0
				 */
				do_action( 'woocommerce_plugins_install_error', $slug, $api, $result, $upgrader );

				$install_error_message = sprintf(
				/* translators: %s: plugin slug (example: woocommerce-services) */
					__( 'The requested plugin `%s` could not be installed. Upgrader install failed.', 'woocommerce' ),
					$slug
				);
				$errors->add(
					$plugin,
					$install_error_message
				);
				$logger && $logger->add_error( $plugin, $install_error_message );

				continue;
			}

			$installed_plugins[] = $plugin;
			$logger && $logger->installed( $plugin, $time[ $plugin ] );

			/**
			 * Action triggered after a plugin is installed.
			 *
			 * @since 9.8
			 */
			do_action( 'woocommerce_plugins_install_after', $slug, $source );
		}

		$data = array(
			'installed' => $installed_plugins,
			'results'   => $results,
			'errors'    => $errors,
			'time'      => $time,
		);

		$logger && $logger->complete( array_merge( $data, array( 'start_time' => $install_start_time ) ) );

		return $data;
	}

	/**
	 * Callback registered by OnboardingPlugins::install_and_activate_async.
	 *
	 * It is used to call install_plugins and activate_plugins with a custom logger.
	 *
	 * @param array       $plugins A list of plugins to install.
	 * @param string      $job_id An unique job I.D.
	 * @param string|null $source The source of the request.
	 *
	 * @return bool
	 */
	public static function install_and_activate_plugins_async_callback( array $plugins, string $job_id, ?string $source = null ) {
		$option_name = 'woocommerce_onboarding_plugins_install_and_activate_async_' . $job_id;
		$logger      = new AsyncPluginsInstallLogger( $option_name );
		self::install_plugins( $plugins, $logger, $source );
		self::activate_plugins( $plugins, $logger );
		return true;
	}

	/**
	 * Schedule plugin installation.
	 *
	 * @param array $plugins Plugins to install.
	 *
	 * @return string Job ID.
	 */
	public static function schedule_install_plugins( $plugins ) {
		if ( empty( $plugins ) || ! is_array( $plugins ) ) {
			return new WP_Error(
				'woocommerce_plugins_invalid_plugins',
				__( 'Plugins must be a non-empty array.', 'woocommerce' ),
				404
			);
		}

		$job_id = uniqid();
		WC()->queue()->schedule_single( time() + 5, 'woocommerce_plugins_install_callback', array( $plugins ) );

		return $job_id;
	}

	/**
	 * Activate the requested plugins.
	 *
	 * @param array                     $plugins Plugins.
	 * @param PluginsInstallLogger|null $logger Logger.
	 *
	 * @return WP_Error|array Plugin Status
	 */
	public static function activate_plugins( $plugins, ?PluginsInstallLogger $logger = null ) {
		if ( empty( $plugins ) || ! is_array( $plugins ) ) {
			return new WP_Error(
				'woocommerce_plugins_invalid_plugins',
				__( 'Plugins must be a non-empty array.', 'woocommerce' ),
				404
			);
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		// the mollie-payments-for-woocommerce plugin calls `WP_Filesystem()` during it's activation hook, which crashes without this include.
		require_once ABSPATH . 'wp-admin/includes/file.php';

		/**
		 * Filter the list of plugins to activate.
		 *
		 * @param array $plugins A list of the plugins to activate.
		 *
		 * @since 6.4.0
		 */
		$plugins = apply_filters( 'woocommerce_admin_plugins_pre_activate', $plugins );

		$plugin_paths      = self::get_installed_plugins_paths();
		$errors            = new WP_Error();
		$activated_plugins = array();

		foreach ( $plugins as $plugin ) {
			$slug = $plugin;
			$path = isset( $plugin_paths[ $slug ] ) ? $plugin_paths[ $slug ] : false;

			if ( ! $path ) {
				/* translators: %s: plugin slug (example: woocommerce-services) */
				$message = sprintf( __( 'The requested plugin `%s`. is not yet installed.', 'woocommerce' ), $slug );
				$errors->add(
					$plugin,
					$message
				);
				$logger && $logger->add_error( $plugin, $message );
				continue;
			}

			$result = activate_plugin( $path );
			if ( ! is_plugin_active( $path ) ) {
				/**
				 * Action triggered when a plugin activation fails.
				 *
				 * @param string $slug The plugin slug.
				 * @param null|WP_Error $result The result of the plugin activation.
				 *
				 * @since 6.4.0
				 */
				do_action( 'woocommerce_plugins_activate_error', $slug, $result );

				/* translators: %s: plugin slug (example: woocommerce-services) */
				$message = sprintf( __( 'The requested plugin `%s` could not be activated.', 'woocommerce' ), $slug );
				$errors->add(
					$plugin,
					$message
				);
				$logger && $logger->add_error( $plugin, $message );

				continue;
			}

			$activated_plugins[] = $plugin;
			$logger && $logger->activated( $plugin );
		}

		$data = array(
			'activated' => $activated_plugins,
			'active'    => self::get_active_plugin_slugs(),
			'errors'    => $errors,
		);

		return $data;
	}

	/**
	 * Schedule plugin activation.
	 *
	 * @param array $plugins Plugins to activate.
	 *
	 * @return string Job ID.
	 */
	public static function schedule_activate_plugins( $plugins ) {
		if ( empty( $plugins ) || ! is_array( $plugins ) ) {
			return new WP_Error(
				'woocommerce_plugins_invalid_plugins',
				__( 'Plugins must be a non-empty array.', 'woocommerce' ),
				404
			);
		}

		$job_id = uniqid();
		WC()->queue()->schedule_single(
			time() + 5,
			'woocommerce_plugins_activate_callback',
			array( $plugins, $job_id )
		);

		return $job_id;
	}

	/**
	 * Installation status.
	 *
	 * @param int $job_id Job ID.
	 *
	 * @return array Job data.
	 */
	public static function get_installation_status( $job_id = null ) {
		$actions = WC()->queue()->search(
			array(
				'hook'    => 'woocommerce_plugins_install_callback',
				'search'  => $job_id,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		return self::get_action_data( $actions );
	}

	/**
	 * Gets the plugin data for the first action.
	 *
	 * @param array $actions Array of AS actions.
	 *
	 * @return array Array of action data.
	 */
	public static function get_action_data( $actions ) {
		$data = array();

		foreach ( $actions as $action_id => $action ) {
			$store  = new ActionScheduler_DBStore();
			$args   = $action->get_args();
			$data[] = array(
				'job_id'  => $args[1],
				'plugins' => $args[0],
				'status'  => $store->get_status( $action_id ),
			);
		}

		return $data;
	}

	/**
	 * Activation status.
	 *
	 * @param int $job_id Job ID.
	 *
	 * @return array Array of action data.
	 */
	public static function get_activation_status( $job_id = null ) {
		$actions = WC()->queue()->search(
			array(
				'hook'    => 'woocommerce_plugins_activate_callback',
				'search'  => $job_id,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		return self::get_action_data( $actions );
	}

	/**
	 * Show notices to connect to woocommerce.com for unconnected store in the plugin list.
	 *
	 * @return void
	 */
	public static function maybe_show_connect_notice_in_plugin_list() {
		if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
			return;
		}

		$notice_type = WC_Helper_Updater::get_woo_connect_notice_type();

		if ( 'none' === $notice_type ) {
			return;
		}

		$notice_string = '';

		if ( 'long' === $notice_type ) {
			$notice_string .= __( 'Your store might be at risk as you are running old versions of WooCommerce plugins.', 'woocommerce' );
			$notice_string .= ' ';
		}

		$connect_page_url = add_query_arg(
			array(
				'page'         => 'wc-admin',
				'tab'          => 'my-subscriptions',
				'path'         => rawurlencode( '/extensions' ),
				'utm_source'   => 'pu',
				'utm_campaign' => 'pu_setting_screen_connect',
			),
			admin_url( 'admin.php' )
		);

		$notice_string .= sprintf(
			/* translators: %s: Connect page URL */
			__( '<a id="woo-connect-notice-url" href="%s">Connect your store</a> to WooCommerce.com to get updates and streamlined support for your subscriptions.', 'woocommerce' ),
			esc_url( $connect_page_url )
		);

		echo '<div class="woo-connect-notice notice notice-error is-dismissible">
	    		<p class="widefat">' . wp_kses_post( $notice_string ) . '</p>
	    	</div>';
	}

	/**
	 * Enqueue scripts for connect notice in WooCommerce settings page.
	 *
	 * @return void
	 */
	public static function maybe_enqueue_scripts_for_connect_notice() {
		if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
			return;
		}

		$notice_type = WC_Helper_Updater::get_woo_connect_notice_type();

		if ( 'none' === $notice_type ) {
			return;
		}

		WCAdminAssets::register_script( 'wp-admin-scripts', 'woo-connect-notice' );
		wp_enqueue_script( 'woo-connect-notice' );
	}

	/**
	 * Enqueue scripts for notices in plugin list page.
	 *
	 * @return void
	 */
	public static function maybe_enqueue_scripts_for_notices_in_plugins() {
		if ( 'plugins' !== get_current_screen()->id ) {
			return;
		}

		WCAdminAssets::register_script( 'wp-admin-scripts', 'woo-plugin-update-connect-notice' );
		WCAdminAssets::register_script( 'wp-admin-scripts', 'woo-enable-autorenew' );
		WCAdminAssets::register_script( 'wp-admin-scripts', 'woo-renew-subscription' );
		wp_enqueue_script( 'woo-plugin-update-connect-notice' );
		wp_enqueue_script( 'woo-enable-autorenew' );
		wp_enqueue_script( 'woo-renew-subscription' );
		wp_enqueue_script( 'woo-purchase-subscription' );
	}

	/**
	 * Show notice about to expired subscription on WC settings page.
	 *
	 * @return void
	 */
	public static function maybe_show_expired_subscriptions_notice() {

		if ( ! WC_Helper::is_site_connected() ) {
			return;
		}

		if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
			return;
		}

		$notice = self::get_expired_subscription_notice();

		if ( isset( $notice['description'] ) ) {
			echo '<div id="woo-subscription-expired-notice" class="woo-subscription-expired-notice woo-subscription-notices notice notice-error is-dismissible" data-dismissnonce="' . esc_attr( wp_create_nonce( 'dismiss_notice' ) ) . '">
	    		<p class="widefat">' . wp_kses_post( $notice['description'] ) . '</p>
	    	</div>';
		}
	}

	/**
	 * Show notice about to expiring subscription on WC settings page.
	 *
	 * @return void
	 */
	public static function maybe_show_expiring_subscriptions_notice() {
		if ( ! WC_Helper::is_site_connected() ) {
			return;
		}

		if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
			return;
		}

		$notice = self::get_expiring_subscription_notice();

		if ( isset( $notice['description'] ) ) {
			echo '<div id="woo-subscription-expiring-notice" class="woo-subscription-expiring-notice woo-subscription-notices notice notice-error is-dismissible" data-dismissnonce="' . esc_attr( wp_create_nonce( 'dismiss_notice' ) ) . '">
	    		<p class="widefat">' . wp_kses_post( $notice['description'] ) . '</p>
	    	</div>';
		}
	}

	/**
	 * Enqueue scripts for woo subscription notice.
	 *
	 * @return void
	 */
	public static function maybe_enqueue_scripts_for_subscription_notice() {
		if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
			return;
		}

		WCAdminAssets::register_script( 'wp-admin-scripts', 'woo-subscriptions-notice' );
		wp_enqueue_script( 'woo-subscriptions-notice' );
	}

	/**
	 * Construct the subscription notice data based on user subscriptions data.
	 *
	 * @param array  $all_subs all subscription data.
	 * @param array  $subs_to_show filtered subscriptions as condition.
	 * @param int    $total total subscription count.
	 * @param array  $messages message.
	 * @param string $type type of notice, whether it is for expiring or expired subscription.
	 * @return array notice data to return. Contains type, parsed_message and product_id (can be a single value or an array).
	 */
	public static function get_subscriptions_notice_data( array $all_subs, array $subs_to_show, int $total, array $messages, string $type ) {
		$utm_campaign = 'expired' === $type ?
				'pu_settings_screen_renew' :
				( 'missing' === $type ? 'pu_settings_screen_purchase' : 'pu_settings_screen_enable_autorenew' );

		if ( 1 < $total ) {
			$hyperlink_url = add_query_arg(
				array(
					'utm_source'   => 'pu',
					'utm_campaign' => $utm_campaign,

				),
				self::WOO_SUBSCRIPTION_PAGE_URL
			);

			$parsed_message = sprintf(
				$messages['different_subscriptions'],
				esc_attr( $total ),
				esc_url( $hyperlink_url ),
				esc_attr( $total ),
			);

			// All product ids.
			$product_ids = array_map(
				function ( $sub ) {
					return $sub['product_id'];
				},
				$subs_to_show
			);

			return array(
				'type'           => 'different_subscriptions',
				'parsed_message' => $parsed_message,
				'product_id'     => $product_ids,
			);
		}

		$subscription = reset( $subs_to_show );
		$product_id   = $subscription['product_id'];
		// check if $all_subs has multiple subs for this product.
		$has_multiple_subs_for_product = 1 < count(
			array_filter(
				$all_subs,
				function ( $sub ) use ( $product_id ) {
					return $product_id === $sub['product_id'];
				}
			)
		);

		$message_key      = $has_multiple_subs_for_product ? 'multiple_manage' : 'single_manage';
		$renew_string     = __( 'Renew', 'woocommerce' );
		$subscribe_string = __( 'Subscribe', 'woocommerce' );
		if ( isset( $subscription['product_regular_price'] ) ) {
			/* translators: 1: Product price */
			$renew_string = sprintf( __( 'Renew for %1$s', 'woocommerce' ), $subscription['product_regular_price'] );
		}
		$expiry_date   = date_i18n( 'F jS', $subscription['expires'] );
		$hyperlink_url = add_query_arg(
			array(
				'product_id'   => $product_id,
				'type'         => $type,
				'utm_source'   => 'pu',
				'utm_campaign' => $utm_campaign,

			),
			self::WOO_SUBSCRIPTION_PAGE_URL
		);

		// Construct message based on template for multiple_manage or single_manage, parameter used:
		// 1. Product name
		// 2. Expiry date
		// 3. URL to My Subscriptions page with extra params
		// 4. Renew string.
		if ( isset( $messages[ $message_key ] ) ) {
			$parsed_message = sprintf(
				$messages[ $message_key ],
				esc_attr( $subscription['product_name'] ),
				esc_attr( $expiry_date ),
				esc_url( $hyperlink_url ),
				// Show subscribe for missing subscriptions, renew otherwise.
				'missing' === $type ? esc_attr( $subscribe_string ) : esc_attr( $renew_string ),
			);

			return array(
				'type'           => $message_key,
				'parsed_message' => $parsed_message,
				'product_id'     => $product_id,
			);
		}

		return array(
			'type'           => 'invalid',
			'parsed_message' => '',
			'product_id'     => '',
		);
	}

	/**
	 * Get formatted notice information for expiring subscription.
	 *
	 * @param boolean $allowed_link whether the notice description should include a link.
	 * @return array notice information.
	 */
	public static function get_expiring_subscription_notice( $allowed_link = true ) {
		if ( ! WC_Helper::is_site_connected() ) {
			return array();
		}

		if ( self::$subscription_usage_notices_already_shown ) {
			return array();
		}

		if ( ! self::should_show_notice( self::DISMISS_EXPIRING_SUBS_NOTICE ) ) {
			return array();
		}

		$subscriptions          = WC_Helper::get_subscription_list_data();
		$expiring_subscriptions = array_filter(
			$subscriptions,
			function ( $sub ) {
				return ( ! empty( $sub['local']['installed'] ) && ! empty( $sub['product_key'] ) )
						&& ( $sub['active'] || empty( $sub['connections'] ) ) // Active on current site or not connected to any sites.
						&& $sub['expiring']
						&& ! $sub['autorenew'];
			},
		);

		if ( ! $expiring_subscriptions ) {
			return array();
		}

		$total_expiring_subscriptions = count( $expiring_subscriptions );

		// Don't show missing notice if there are expiring subscriptions.
		self::$subscription_usage_notices_already_shown = true;

		// When payment method is missing on WooCommerce.com.
		$helper_notices = WC_Helper::get_notices();
		if ( ! empty( $helper_notices['missing_payment_method_notice'] ) ) {
			return self::get_missing_payment_method_notice( $allowed_link, $total_expiring_subscriptions );
		}

		// Payment method is available but there are expiring subscriptions.
		$notice_data = self::get_subscriptions_notice_data(
			$subscriptions,
			$expiring_subscriptions,
			$total_expiring_subscriptions,
			array(
				/* translators: 1) product name 2) expiry date 3) URL to My Subscriptions page */
				'single_manage'           => __( 'Your subscription for <strong>%1$s</strong> expires on %2$s. <a href="%3$s">Enable auto-renewal</a> to continue receiving updates and streamlined support.', 'woocommerce' ),
				/* translators: 1) product name 2) expiry date 3) URL to My Subscriptions page */
				'multiple_manage'         => __( 'One of your subscriptions for <strong>%1$s</strong> expires on %2$s. <a href="%3$s">Enable auto-renewal</a> to continue receiving updates and streamlined support.', 'woocommerce' ),
				/* translators: 1) total expiring subscriptions 2) URL to My Subscriptions page */
				'different_subscriptions' => __( 'You have <strong>%1$s Woo extension subscriptions</strong> expiring soon. <a href="%2$s">Enable auto-renewal</a> to continue receiving updates and streamlined support.', 'woocommerce' ),
			),
			'expiring',
		);

		$button_link = add_query_arg(
			array(
				'utm_source'   => 'pu',
				'utm_campaign' => 'pu_in_apps_screen_enable_autorenew',
			),
			self::WOO_SUBSCRIPTION_PAGE_URL
		);
		if ( in_array( $notice_data['type'], array( 'single_manage', 'multiple_manage' ), true ) ) {
			$button_link = add_query_arg(
				array(
					'product_id' => $notice_data['product_id'],
					'type'       => 'expiring',
				),
				$button_link
			);
		}

		return array(
			'description' => $allowed_link ? $notice_data['parsed_message'] : preg_replace( '#<a.*?>(.*?)</a>#i', '\1', $notice_data['parsed_message'] ),
			'button_text' => __( 'Enable auto-renewal', 'woocommerce' ),
			'button_link' => $button_link,
		);
	}

	/**
	 * Get formatted notice information for expired subscription.
	 *
	 * @param boolean $allowed_link whether the notice description should include a link.
	 * @return array notice information.
	 */
	public static function get_expired_subscription_notice( $allowed_link = true ) {
		if ( ! WC_Helper::is_site_connected() ) {
			return array();
		}

		if ( ! self::should_show_notice( self::DISMISS_EXPIRED_SUBS_NOTICE ) ) {
			return array();
		}

		$subscriptions         = WC_Helper::get_subscription_list_data();
		$expired_subscriptions = array_filter(
			$subscriptions,
			function ( $sub ) {
				return ( ! empty( $sub['local']['installed'] ) && ! empty( $sub['product_key'] ) )
						&& ( $sub['active'] || empty( $sub['connections'] ) ) // Active on current site or not connected to any sites.
						&& $sub['expired']
						&& ! $sub['lifetime'];
			},
		);

		if ( ! $expired_subscriptions ) {
			return array();
		}

		$total_expired_subscriptions                    = count( $expired_subscriptions );
		self::$subscription_usage_notices_already_shown = true;

		$notice_data = self::get_subscriptions_notice_data(
			$subscriptions,
			$expired_subscriptions,
			$total_expired_subscriptions,
			array(
				/* translators: 1) product name 3) URL to My Subscriptions page 4) Renew product price string */
				'single_manage'           => __( 'Your subscription for <strong>%1$s</strong> expired. <a href="%3$s">%4$s</a> to continue receiving updates and streamlined support.', 'woocommerce' ),
				/* translators: 1) product name 3) URL to My Subscriptions page 4) Renew product price string */
				'multiple_manage'         => __( 'One of your subscriptions for <strong>%1$s</strong> has expired. <a href="%3$s">%4$s</a> to continue receiving updates and streamlined support.', 'woocommerce' ),
				/* translators: 1) total expired subscriptions 2) URL to My Subscriptions page */
				'different_subscriptions' => __( 'You have <strong>%1$s Woo extension subscriptions</strong> that expired. <a href="%2$s">Renew</a> to continue receiving updates and streamlined support.', 'woocommerce' ),
			),
			'expired',
		);

		$button_link = add_query_arg(
			array(
				'add-to-cart'  => $notice_data['product_id'],
				'utm_source'   => 'pu',
				'utm_campaign' => $allowed_link ? 'pu_settings_screen_renew' : 'pu_in_apps_screen_renew',
			),
			self::WOO_CART_PAGE_URL
		);

		if ( in_array( $notice_data['type'], array( 'single_manage', 'multiple_manage' ), true ) ) {
			$button_link = add_query_arg(
				array(
					'add-to-cart' => $notice_data['product_id'],
				),
				$button_link
			);
		}

		return array(
			'description' => $allowed_link ? $notice_data['parsed_message'] : preg_replace( '#<a.*?>(.*?)</a>#i', '\1', $notice_data['parsed_message'] ),
			'button_text' => __( 'Renew', 'woocommerce' ),
			'button_link' => $button_link,
		);
	}

	/**
	 * Get formatted notice information for missing subscription.
	 *
	 * @return array notice information.
	 */
	public static function get_missing_subscription_notice() {
		if ( ! WC_Helper::is_site_connected() ) {
			return array();
		}

		if ( self::$subscription_usage_notices_already_shown ) {
			return array();
		}

		if ( ! self::should_show_notice( self::DISMISS_MISSING_SUBS_NOTICE ) ) {
			return array();
		}

		$subscriptions         = WC_Helper::get_subscription_list_data();
		$missing_subscriptions = array_filter(
			$subscriptions,
			function ( $sub ) {
				return ( ! empty( $sub['local']['installed'] ) && empty( $sub['product_key'] ) );
			},
		);

		// Remove WUM from missing subscriptions list.
		$missing_subscriptions = array_filter(
			$missing_subscriptions,
			function ( $sub ) {
				return 'woo-update-manager' !== $sub['zip_slug'];
			}
		);

		if ( ! $missing_subscriptions ) {
			return array();
		}

		$total_missing_subscriptions = count( $missing_subscriptions );

		$notice_data = self::get_subscriptions_notice_data(
			$subscriptions,
			$missing_subscriptions,
			$total_missing_subscriptions,
			array(
				/* translators: 1) product name */
				'single_manage'           => __( 'You don\'t have a subscription for <strong>%1$s</strong>. Subscribe to receive updates and streamlined support.', 'woocommerce' ),
				/* translators: 1) total expired subscriptions */
				'different_subscriptions' => __( 'You don\'t have subscriptions for <strong>%1$s Woo extensions</strong>. Subscribe to receive updates and streamlined support.', 'woocommerce' ),
			),
			'missing',
		);

		$button_link = add_query_arg(
			array(
				'add-to-cart'  => $notice_data['product_id'],
				'utm_source'   => 'pu',
				'utm_campaign' => 'pu_in_apps_screen_purchase',
			),
			self::WOO_CART_PAGE_URL
		);

		if ( in_array( $notice_data['type'], array( 'single_manage', 'multiple_manage' ), true ) ) {
			$button_link = add_query_arg(
				array(
					'add-to-cart' => $notice_data['product_id'],
				),
				$button_link
			);
		}

		$button_text = __( 'Subscribe', 'woocommerce' );

		return array(
			'description' => $notice_data['parsed_message'],
			'button_text' => $button_text,
			'button_link' => $button_link,
		);
	}

	/**
	 * Get notice information when WCCOM connection is disconnected.
	 *
	 * @return string disconnect notice.
	 */
	public static function get_wccom_disconnected_notice() {
		if ( WC_Helper::is_site_connected() ) {
			return '';
		}

		if ( ! self::should_show_notice( self::DISMISS_DISCONNECT_NOTICE, false ) ) {
			return '';
		}

		$user_email = \WC_Helper_Options::get( 'last_disconnected_user_data' )['email'] ?? null;
		if ( empty( $user_email ) ) {
			return '';
		}

		return sprintf(
			/* translators: 1: Disconnected user email */
			__( 'Successfully disconnected from <b>%1$s</b>.', 'woocommerce' ),
			$user_email
		);
	}

	/**
	 * Get the connected status notice message.
	 *
	 * @param string $user_email the user email.
	 *
	 * @return string the connected notice message.
	 */
	public static function get_wccom_connected_notice( $user_email ) {
		if ( ! WC_Helper::is_site_connected() ) {
			return '';
		}

		if ( ! self::should_show_notice( self::DISMISS_CONNECT_NOTICE, false ) ) {
			return '';
		}

		if ( ! $user_email ) {
			return '';
		}

		return sprintf(
		/* translators: 1: Disconnected user email */
			__( 'Successfully connected to <b>%s</b>.', 'woocommerce' ),
			$user_email
		);
	}

	/**
	 * Determine whether a specific notice should be shown to the current user.
	 *
	 * @param string $dismiss_notice_meta User meta that includes the timestamp when a store notice was dismissed.
	 * @param bool   $show_after_one_month Show the notices dismissed earlier than one month.
	 * @return bool True if the notice should be shown, false otherwise.
	 */
	public static function should_show_notice( $dismiss_notice_meta, $show_after_one_month = true ) {
		// Get the current user ID.
		$user_id = get_current_user_id();

		// Get the timestamp when the notice was dismissed.
		$dismissed_timestamp = get_user_meta( $user_id, $dismiss_notice_meta, true );

		if ( ! $show_after_one_month ) {
			return empty( $dismissed_timestamp );
		}

		// If the notice was dismissed within the last month, do not show it.
		if ( ! empty( $dismissed_timestamp ) && ( time() - $dismissed_timestamp ) < 30 * DAY_IN_SECONDS ) {
			return false;
		}

		// If the notice was dismissed more than a month ago, delete the meta value and show the notice.
		if ( ! empty( $dismissed_timestamp ) ) {
			delete_user_meta( $user_id, $dismiss_notice_meta );
		}

		return true;
	}

	/**
	 * Get the notice data for missing payment method.
	 *
	 * @param bool $allowed_link whether should show link on the notice or not.
	 * @param int  $total_expiring_subscriptions total expiring subscriptions.
	 *
	 * @return array the notices data.
	 */
	public static function get_missing_payment_method_notice( $allowed_link = true, $total_expiring_subscriptions = 1 ) {
		$add_payment_method_link = add_query_arg(
			array(
				'utm_source'   => 'pu',
				'utm_campaign' => $allowed_link ? 'pu_settings_screen_add_payment_method' : 'pu_in_apps_screen_add_payment_method',
			),
			self::WOO_ADD_PAYMENT_METHOD_URL
		);
		$description             = $allowed_link
			? sprintf(
			/* translators: %s: WooCommerce.com URL to add payment method */
				_n(
					'Your WooCommerce extension subscription is missing a payment method for renewal. <a href="%s">Add a payment method</a> to ensure you continue receiving updates and streamlined support.',
					'Your WooCommerce extension subscriptions are missing a payment method for renewal. <a href="%s">Add a payment method</a> to ensure you continue receiving updates and streamlined support.',
					$total_expiring_subscriptions,
					'woocommerce'
				),
				$add_payment_method_link
			)
			: _n(
				'Your WooCommerce extension subscription is missing a payment method for renewal. Add a payment method to ensure you continue receiving updates and streamlined support.',
				'Your WooCommerce extension subscriptions are missing a payment method for renewal. Add a payment method to ensure you continue receiving updates and streamlined support.',
				$total_expiring_subscriptions,
				'woocommerce'
			);

		return array(
			'description' => $description,
			'button_text' => __( 'Add payment method', 'woocommerce' ),
			'button_link' => $add_payment_method_link,
		);
	}
}
