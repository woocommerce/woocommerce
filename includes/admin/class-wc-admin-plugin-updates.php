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

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'extra_plugin_headers', array( $this, 'enable_wc_plugin_headers' ) );
		add_action( 'in_plugin_update_message-woocommerce/woocommerce.php', array( $this, 'output_untested_plugin_warnings' ), 11, 2 );
		add_action( 'admin_footer', array( $this, 'js' ) );
	}

	public function js() {
		?>
		<script>
			(function($){
				var $update_box = $( '#woocommerce-update' );
				var $update_link = $update_box.find('a.update-link');
				var update_url = $update_link.attr( 'href' );

				$update_link.removeClass( 'update-link' );
				$update_link.addClass( 'thickbox open-plugin-details-modal' ); // 'open-plugin-details-modal' class is required in all plugin page thickboxes to prevent JS errors on close. See wp-admin/js/plugin-install.js.
				$update_link.attr( 'href', '#TB_inline&height=600&width=550&inlineId=wc-untested-extensions-notice' );

			})(jQuery);
		</script>
		<?php
	}

	/**
	 * Read in WooCommerce headers when reading plugin headers.
	 *
	 * @param array $headers
	 * @return array $headers
	 */
	public function enable_wc_plugin_headers( $headers ) {
		$headers['WCRequires'] = self::VERSION_REQUIRED_HEADER;
		$headers['WCTested'] =  self::VERSION_TESTED_HEADER;
		return $headers;
	}

	/**
	 * Output a warning message if plugins exist with a tested version lower than the WooCommerce version.
	 *
	 * @param array $data
	 * @param stdObject $response
	 */
	public function output_untested_plugin_warnings( $data, $response ) {
		$plugins = $this->get_untested_plugins( $response->new_version );
		if ( empty( $plugins ) ) {
			return;
		}

		include( 'views/html-notice-untested-extensions.php' );
	}

	/**
	 * Get plugins that have a tested version lower than the input version.
	 *
	 * @param string $version
	 * @return array of plugin info arrays
	 */
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

	/**
	 * Get plugins that have a valid value for a specific header.
	 *
	 * @param string $header
	 * @return array of plugin info arrays
	 */
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
