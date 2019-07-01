<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Helper_Product_Install Class
 *
 * Contains functionalities to install product via helper connection.
 */
class WC_Helper_Product_Install {
	/**
	 * Default state.
	 *
	 * @var array
	 */
	private static $default_state = array(
		'status'       => 'idle',
		'steps'        => array(),
		'current_step' => array(),
	);

	/**
	 * Represents product step state.
	 *
	 * @var array
	 */
	private static $default_step_state = array(
		'last_step'     => '',
		'last_error'    => '',
		'download_path' => '',
		'unpacked_path' => '',
	);

	/**
	 * Product install steps. Each step is a method name in this class that
	 * will be passed with product ID arg \WP_Upgrader instance.
	 *
	 * @var array
	 */
	private static $install_steps = array(
		'check_subscription',
		'download_product',
		'unpack_product',
		'move_product',
		'activate_product',
	);

	/**
	 * Get the product install state.
	 *
	 * @param string $key Key in state data. If empty key is passed array of
	 *                    state will be returned.
	 *
	 * @return array Product install state.
	 */
	public static function get_state( $key = '' ) {
		$state = WC_Helper_Options::get( 'product_install', self::$default_state );
		if ( ! empty( $key ) ) {
			return isset( $state[ $key ] ) ? $state[ $key ] : null;
		}

		return $state;
	}

	/**
	 * Update the product install state.
	 *
	 * @param string $key   Key in state data.
	 * @param mixed  $value Value.
	 */
	public static function update_state( $key, $value ) {
		$state = WC_Helper_Options::get( 'product_install', self::$default_state );

		$state[ $key ] = $value;
		WC_Helper_Options::update( 'product_install', $state );
	}

	/**
	 * Reset product isntall state.
	 */
	public static function reset_state() {
		WC_Helper_Options::update( 'product_install', self::$default_state );
	}

	/**
	 * Install a given product IDs.
	 *
	 * @param array $products List of product IDs.
	 *
	 * @return array State.
	 */
	public static function install( $products ) {
		$state  = self::get_state();
		$status = ! empty( $state['status'] ) ? $state['status'] : '';
		if ( 'in-progress' === $status ) {
			return $state;
		}

		self::update_state( 'status', 'in-progress' );

		$steps = array_fill_keys( $products, self::$default_step_state );
		self::update_state( 'steps', $steps );

		// TODO: async install? i.e. queue the job via Action Scheduler.

		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		WP_Filesystem();
		$upgrader = new WP_Upgrader( new Automatic_Upgrader_Skin() );
		wp_clean_plugins_cache();

		foreach ( $products as $product_id ) {
			self::install_product( $product_id, $upgrader );
		}

		return self::get_state();
	}

	/**
	 * Install a single product given its ID.
	 *
	 * @param int          $product_id Product ID.
	 * @param \WP_Upgrader $upgrader   Core class to handle installation.
	 */
	private static function install_product( $product_id, $upgrader ) {
		foreach ( self::$install_steps as $step ) {
			self::do_install_step( $product_id, $step, $upgrader );
		}
	}

	/**
	 * Perform product installation step.
	 *
	 * @param int          $product_id Product ID.
	 * @param string       $step       Installation step.
	 * @param \WP_Upgrader $upgrader   Core class to handle installation.
	 */
	private static function do_install_step( $product_id, $step, $upgrader ) {
		$state_steps = self::get_state( 'steps' );
		if ( empty( $state_steps[ $product_id ] ) ) {
			$state_steps[ $product_id ] = self::$default_step_state;
		}

		if ( ! empty( $state_steps[ $product_id ]['last_error'] ) ) {
			return;
		}

		$state_steps[ $product_id ]['last_step'] = $step;

		self::update_state( 'current_step', array(
			'product_id' => $product_id,
			'step'       => $step,
		) );

		$result = call_user_func( array( __CLASS__, $step ), $product_id, $upgrader );
		if ( is_wp_error( $result ) ) {
			$state_steps[ $product_id ]['last_error'] = $result->get_error_message();
		}
		if ( 'download_product' === $step && ! is_wp_error( $result ) ) {
			$state_steps[ $product_id ]['download_path'] = $result;
		}
		if ( 'unpack_product' === $step && ! is_wp_error( $result ) ) {
			$state_steps[ $product_id ]['unpacked_path'] = $result;
		}

		self::update_state( 'steps', $state_steps );
	}

	/**
	 * Check subscription of a given product ID.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool|\WP_Error
	 */
	private static function check_subscription( $product_id ) {
		if ( ! WC_Helper::has_product_subscription( $product_id ) ) {
			return new WP_Error( 'missing_subscription', __( 'Missing product subscription', 'woocommerce' ) );
		}

		return true;
	}

	/**
	 * Download product by its ID and returns the path of the zip package.
	 *
	 * @param int          $product_id Product ID.
	 * @param \WP_Upgrader $upgrader   Core class to handle installation.
	 *
	 * @return \WP_Error|string
	 */
	private static function download_product( $product_id, $upgrader ) {
		// TODO: make sure wporg plugin/theme is supported.
		$updates = WC_Helper_Updater::get_update_data();
		if ( empty( $updates[ $product_id ]['package'] ) ) {
			return new WP_Error( 'missing_product_package', __( 'Could not found product package.', 'woocommerce' ) );
		}

		return $upgrader->download_package( $updates[ $product_id ]['package'] );
	}

	/**
	 * Unpack downloaded product.
	 *
	 * @param int          $product_id Product ID.
	 * @param \WP_Upgrader $upgrader   Core class to handle installation.
	 */
	private static function unpack_product( $product_id, $upgrader ) {
		$steps = self::get_state( 'steps' );
		if ( empty( $steps[ $product_id ]['download_path'] ) ) {
			return new WP_Error( 'missing_download_path', __( 'Could not found download path.', 'woocommerce' ) );
		}

		return $upgrader->unpack_package( $steps[ $product_id ]['download_path'], true );

	}

	/**
	 * Move product to plugins directory.
	 *
	 * @param int          $product_id Product ID.
	 * @param \WP_Upgrader $upgrader   Core class to handle installation.
	 */
	private static function move_product( $product_id, $upgrader ) {
		$steps = self::get_state( 'steps' );
		if ( empty( $steps[ $product_id ]['unpacked_path'] ) ) {
			return new WP_Error( 'missing_unpacked_path', __( 'Could not found unpacked path.', 'woocommerce' ) );
		}

		// TODO: handle theme.

		return $upgrader->install_package( array(
			'source'        => $steps[ $product_id ]['unpacked_path'],
			'destination'   => WP_PLUGIN_DIR,
			'clear_working' => true,
			'hook_extra'    => array(
				'type'   => 'plugin',
				'action' => 'install',
			)
		) );
	}

	/**
	 * Activate product given its product ID.
	 *
	 * @param int $product_id Product ID.
	 */
	private static function activate_product( $product_id ) {
		$plugins = wp_list_filter(
			WC_Helper::get_local_woo_plugins(),
			array(
				'_product_id' => $product_id,
			)
		);

		$filename = is_array( $plugins ) && ! empty( $plugins )
			? key( $plugins )
			: '';

		if ( empty( $filename ) ) {
			return new WP_Error( 'unknown_filename', __( 'Unknown product filename.', 'woocommerce' ) );
		}

		// TODO: theme activation support

		return activate_plugin( $filename );
	}
}
