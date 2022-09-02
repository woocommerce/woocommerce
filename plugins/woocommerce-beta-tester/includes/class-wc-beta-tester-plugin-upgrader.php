<?php
/**
 * WooCommerce Beta Tester Plugin Upgrader
 *
 * Class that extends the WP Core Plugin_Upgrader found in core to do version switch.
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Plugin_Upgrader' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
}


/**
 * Class WC_Beta_Tester_Plugin_Upgrader
 */
class WC_Beta_Tester_Plugin_Upgrader extends Plugin_Upgrader {

	/**
	 * Switch plugin version.
	 *
	 * @param string $plugin Plugin we're switching.
	 * @param array  $args Args.
	 *
	 * @return array|bool|\WP_Error
	 */
	public function switch_version( $plugin, $args = array() ) {
		$defaults    = array(
			'clear_update_cache' => true,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->upgrade_strings();

		$plugin_version = $this->skin->options['version'];

		$download_url = WC_Beta_Tester::instance()->get_download_url( $plugin_version );

		add_filter( 'upgrader_pre_install', array( $this, 'deactivate_plugin_before_upgrade' ), 10, 2 );
		add_filter( 'upgrader_clear_destination', array( $this, 'delete_old_plugin' ), 10, 4 );

		$this->run(
			array(
				'package'           => $download_url,
				'destination'       => WP_PLUGIN_DIR,
				'clear_destination' => true,
				'clear_working'     => true,
				'hook_extra'        => array(
					'plugin' => $plugin,
					'type'   => 'plugin',
					'action' => 'update',
				),
			)
		);

		// Cleanup our hooks, in case something else does a upgrade on this connection.
		remove_filter( 'upgrader_pre_install', array( $this, 'deactivate_plugin_before_upgrade' ) );
		remove_filter( 'upgrader_clear_destination', array( $this, 'delete_old_plugin' ) );

		if ( ! $this->result || is_wp_error( $this->result ) ) {
			return $this->result;
		}

		// Force refresh of plugin update information.
		wp_clean_plugins_cache( $parsed_args['clear_update_cache'] );

		return true;
	}

}
