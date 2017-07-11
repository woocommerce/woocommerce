<?php
/**
 * Manages WooCommerce plugin updating on the plugins screen.
 *
 * @author      Automattic
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Plugin_Updates Class.
 */
class WC_Admin_Plugin_Updates {

	const VERSION_REQUIRED_HEADER = 'WC requires at least';
	const VERSION_TESTED_HEADER = 'WC tested up to';

	public function __construct() {
		wp_cache_delete( 'plugins', 'plugins' );
		add_filter( 'extra_plugin_headers', array( $this, 'enable_wc_plugin_headers' ) );
		add_action( 'in_plugin_update_message-woocommerce/woocommerce.php', array( $this, 'output_plugin_warnings' ), 11, 2 );
	}

	public function enable_wc_plugin_headers( $headers ) {
		$headers['WCRequires'] = self::VERSION_REQUIRED_HEADER;
		$headers['WCTested'] =  self::VERSION_TESTED_HEADER;
		return $headers;
	}

	public function output_plugin_warnings( $data, $response ) {
		$untested = $this->get_untested_plugins( $response->new_version );

		foreach ( $untested as $plugin ) {
			echo $plugin['Name'] . ' ' . $plugin[ self::VERSION_TESTED_HEADER ] . '<br/>';
		}

	}

	protected function get_untested_plugins( $version ) {
		$extensions = $this->get_plugins_with_header( self::VERSION_TESTED_HEADER );
		$untested = array();

		foreach ( $extensions as $file => $plugin ) {
			if ( version_compare( $plugin[ self::VERSION_TESTED_HEADER ], $version, '<' ) ) {
				$untested[ $file ] = $plugin;
			}
		}

		return $untested;
	}

	protected function get_plugins_with_header( $header ) {
		$plugins = get_plugins();
		$matches = array();

		foreach ( $plugins as $file => $plugin ) {
			if ( ! empty ( $plugin[ $header ] ) ) {
				$matches[ $file ] = $plugin;
			}
		}

		return $matches;
	}
}
new WC_Admin_Plugin_Updates();
