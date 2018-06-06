<?php
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

			if ( self::_has_active_subscription( $plugin['_product_id'] ) ) {
				$item['package'] = $data['package'];
			}

			if ( version_compare( $plugin['Version'], $data['version'], '<' ) ) {
				$transient->response[ $filename ] = (object) $item;
				unset( $transient->no_update[ $filename ] );
			} else {
				$transient->no_update[ $filename ] = (object) $item;
				unset( $transient->response[ $filename ] );
			}
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

			if ( self::_has_active_subscription( $theme['_product_id'] ) ) {
				$item['package'] = $data['package'];
			}

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
		foreach ( WC_Helper::get_subscriptions() as $subscription ) {
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

		// Scan local themes
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
	 * Run an update check API call.
	 *
	 * The call is cached based on the payload (product ids, file ids). If
	 * the payload changes, the cache is going to miss.
	 *
	 * @return array Update data for each requested product.
	 */
	private static function _update_check( $payload ) {
		ksort( $payload );
		$hash = md5( json_encode( $payload ) );

		$cache_key = '_woocommerce_helper_updates';
		if ( false !== ( $data = get_transient( $cache_key ) ) ) {
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

		$request = WC_Helper_API::post(
			'update-check', array(
				'body'          => json_encode( array( 'products' => $payload ) ),
				'authenticated' => true,
			)
		);

		if ( wp_remote_retrieve_response_code( $request ) !== 200 ) {
			$data['errors'][] = 'http-error';
		} else {
			$data['products'] = json_decode( wp_remote_retrieve_body( $request ), true );
		}

		set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );
		return $data['products'];
	}

	/**
	 * Check for an active subscription.
	 *
	 * Checks a given product id against all subscriptions on
	 * the current site. Returns true if at least one active
	 * subscription is found.
	 *
	 * @param int $product_id The product id to look for.
	 *
	 * @return bool True if active subscription found.
	 */
	private static function _has_active_subscription( $product_id ) {
		if ( ! isset( $auth ) ) {
			$auth = WC_Helper_Options::get( 'auth' );
		}

		if ( ! isset( $subscriptions ) ) {
			$subscriptions = WC_Helper::get_subscriptions();
		}

		if ( empty( $auth['site_id'] ) || empty( $subscriptions ) ) {
			return false;
		}

		// Check for an active subscription.
		foreach ( $subscriptions as $subscription ) {
			if ( $subscription['product_id'] != $product_id ) {
				continue;
			}

			if ( in_array( absint( $auth['site_id'] ), $subscription['connections'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the number of products that have updates.
	 *
	 * @return int The number of products with updates.
	 */
	public static function get_updates_count() {
		$cache_key = '_woocommerce_helper_updates_count';
		if ( false !== ( $count = get_transient( $cache_key ) ) ) {
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

			if ( version_compare( $plugin['Version'], $update_data[ $plugin['_product_id'] ]['version'], '<' ) ) {
				$count++;
			}
		}

		// Scan local themes.
		foreach ( WC_Helper::get_local_woo_themes() as $theme ) {
			if ( empty( $update_data[ $theme['_product_id'] ] ) ) {
				continue;
			}

			if ( version_compare( $theme['Version'], $update_data[ $theme['_product_id'] ]['version'], '<' ) ) {
				$count++;
			}
		}

		set_transient( $cache_key, $count, 12 * HOUR_IN_SECONDS );
		return $count;
	}

	/**
	 * Return the updates count markup.
	 *
	 * @return string Updates count markup, empty string if no updates avairable.
	 */
	public static function get_updates_count_html() {
		$count = self::get_updates_count();
		if ( ! $count ) {
			return '';
		}

		$count_html = sprintf( '<span class="update-plugins count-%d"><span class="update-count">%d</span></span>', $count, number_format_i18n( $count ) );
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
}

WC_Helper_Updater::load();
