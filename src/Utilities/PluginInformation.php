<?php
/**
 * Plugin information for status report.
 *
 * @package WooCommerce/Utilities
 */

namespace WooCommerce\RestApi\Utilities;

/**
 * PluginInformation class.
 */
class PluginInformation {

	/**
	 * Use WP API to lookup latest updates for plugins. WC_Helper injects updates for premium plugins.
	 *
	 * @var array
	 */
	protected $available_updates;

	/**
	 * Constructor.
	 */
	public function __construct() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/update.php';

		// Use WP API to lookup latest updates for plugins. WC_Helper injects updates for premium plugins.
		$this->available_updates = get_plugin_updates();
	}

	/**
	 * Get formatted active plugin data.
	 *
	 * @return array
	 */
	public function get_active_plugin_data() {
		return array_map( [ $this, 'format_plugin_data' ], array_map( [ $this, 'get_plugin_data' ], $this->get_active_plugins() ) );
	}

	/**
	 * Get formatted inactive plugin data.
	 *
	 * @return array
	 */
	public function get_inactive_plugin_data() {
		return array_map( [ $this, 'format_plugin_data' ], array_map( [ $this, 'get_plugin_data' ], $this->get_inactive_plugins() ) );
	}

	/**
	 * Get a list of Dropins and MU plugins.
	 *
	 * @since 3.6.0
	 * @return array
	 */
	public function get_dropin_and_mu_plugin_data() {
		$plugins               = [
			'dropins'    => [],
			'mu_plugins' => [],
		];

		foreach ( get_dropins() as $plugin => $data ) {
			$data['plugin_file']  = $plugin;
			$plugins['dropins'][] = $this->format_plugin_data( $data );
		}

		foreach ( get_mu_plugins() as $plugin => $data ) {
			$data['plugin_file']     = $plugin;
			$plugins['mu_plugins'][] = $this->format_plugin_data( $data );
		}

		return $plugins;
	}

	/**
	 * Get a list of plugins active on the site.
	 *
	 * @return array
	 */
	protected function get_active_plugins() {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins            = array_merge( $active_plugins, $network_activated_plugins );
		}

		return $active_plugins;
	}

	/**
	 * Get a list of inplugins active on the site.
	 *
	 * @return array
	 */
	protected function get_inactive_plugins() {
		$plugins          = get_plugins();
		$active_plugins   = $this->get_active_plugins();
		$inactive_plugins = [];

		foreach ( $plugins as $plugin => $data ) {
			if ( in_array( $plugin, $active_plugins, true ) ) {
				continue;
			}
			$inactive_plugins[] = $plugin;
		}

		return $inactive_plugins;
	}

	/**
	 * Undocumented function
	 *
	 * @param string $plugin Plugin directory/file.
	 * @return array Data.
	 */
	protected function get_plugin_data( $plugin ) {
		$data                = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
		$data['plugin_file'] = $plugin;
		return $data;
	}

	/**
	 * Format plugin data, including data on updates, into a standard format.
	 *
	 * @param array $data Plugin data.
	 * @return array Formatted data.
	 */
	protected function format_plugin_data( $data ) {
		$version_latest = $data['Version'];

		// Find latest version.
		if ( isset( $this->available_updates[ $data['plugin_file'] ]->update->new_version ) ) {
			$version_latest = $this->available_updates[ $data['plugin_file'] ]->update->new_version;
		}

		return array(
			'plugin'            => $data['plugin_file'],
			'name'              => $data['Name'],
			'version'           => $data['Version'],
			'version_latest'    => $version_latest,
			'url'               => $data['PluginURI'],
			'author_name'       => $data['AuthorName'],
			'author_url'        => esc_url_raw( $data['AuthorURI'] ),
			'network_activated' => $data['Network'],
		);
	}
}
