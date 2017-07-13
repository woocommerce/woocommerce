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

	protected $relative_upgrade_type = ''; // One of: 'unknown', 'major', 'minor', 'patch'.
	protected $upgrade_notice = '';
	protected $new_version = '';
	protected $untested_extensions = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'extra_plugin_headers', array( $this, 'enable_wc_plugin_headers' ) );
		add_action( 'in_plugin_update_message-woocommerce/woocommerce.php', array( $this, 'in_plugin_update_message' ), 10, 2 );
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
	 * Show plugin changes. Code adapted from W3 Total Cache.
	 *
	 * @param array $args
	 */
	public function in_plugin_update_message( $args, $response ) {

		$this->new_version = $response->new_version;
		$this->upgrade_notice = $this->get_upgrade_notice( $response->new_version );
		$this->relative_upgrade_type = $this->get_upgrade_type( $args['Version'], $response->new_version );
		$this->untested_plugins = $this->get_untested_plugins( $response->new_version );

		if ( ! empty( $this->untested_plugins ) && ( 'major' == $this->relative_upgrade_type || 'minor' == $this->relative_upgrade_type ) ) {
			$this->upgrade_notice .= $this->get_extensions_inline_warning();
		}

		if ( ! empty( $this->untested_plugins ) && 'major' === $this->relative_upgrade_type ) {
			$this->upgrade_notice .= $this->get_extensions_modal_warning();
			add_action( 'admin_footer', array( $this, 'js' ) );
		}

		echo apply_filters( 'woocommerce_in_plugin_update_message', wp_kses_post( $this->upgrade_notice ) );
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

	/*
	|--------------------------------------------------------------------------
	| Message Helpers
	|--------------------------------------------------------------------------
	|
	| Methods for getting messages.
	*/

	protected function get_extensions_inline_warning() {
		$plugins = $this->untested_plugins;
		$new_version = $this->new_version;

		ob_start();
		include( 'views/html-notice-untested-extensions-inline.php' );
		return ob_get_clean();
	}

	protected function get_extensions_modal_warning() {
		$plugins = $this->untested_plugins;
		$new_version = $this->new_version;

		//ob_start();
	}

	protected function get_upgrade_notice( $version ) {
		$transient_name = 'wc_upgrade_notice_' . $version;

		//if ( false === ( $upgrade_notice = get_transient( $transient_name ) ) ) {
			//$response = wp_safe_remote_get( 'https://plugins.svn.wordpress.org/woocommerce/trunk/readme.txt' );
			$response = wp_safe_remote_get( 'http://local.wordpress.dev/wp-content/plugins/woocommerce/readme.txt' );
			if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {
				$upgrade_notice = $this->parse_update_notice( $response['body'], $version );
			//	set_transient( $transient_name, $upgrade_notice, 1/*DAY_IN_SECONDS*/ );
			}
		//}

		return $upgrade_notice;
	}


	/**
	 * Parse update notice from readme file.
	 *
	 * @param  string $content
	 * @param  string $new_version
	 * @return string
	 */
	private function parse_update_notice( $content, $new_version ) {
		// Output Upgrade Notice.
		$matches        = null;
		$regexp         = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( $new_version ) . '\s*=|$)~Uis';
		$upgrade_notice = '';

		if ( preg_match( $regexp, $content, $matches ) ) {
			$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );

			// Convert the full version strings to minor versions.
			$notice_version_parts  = explode( '.', trim( $matches[1] ) );
			$current_version_parts = explode( '.', WC_VERSION );

			if ( 3 !== sizeof( $notice_version_parts ) ) {
				return;
			}

			$notice_version  = $notice_version_parts[0] . '.' . $notice_version_parts[1];
			$current_version = $current_version_parts[0] . '.' . $current_version_parts[1];

			// Check the latest stable version and ignore trunk.
			if ( version_compare( $current_version, $notice_version, '<' ) ) {

				$upgrade_notice .= '</p><p class="wc_plugin_upgrade_notice">';

				foreach ( $notices as $index => $line ) {
					$upgrade_notice .= preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line );
				}
			}
		}

		return wp_kses_post( $upgrade_notice );
	}

	/*
	|--------------------------------------------------------------------------
	| Data Helpers
	|--------------------------------------------------------------------------
	|
	| Methods for getting & manipulating data.
	*/

	protected function get_upgrade_type( $current_version, $upgrade_version ) {
		$current_parts = explode( '.', $current_version );
		$upgrade_parts = explode( '.', $upgrade_version );

		if ( 3 !== count( $current_parts ) || 3 !== count( $upgrade_parts ) ) {
			return 'unknown';
		}
		if ( (int) $current_parts[0] < (int) $upgrade_parts[0] ) {
			return 'major';
		}
		if ( (int) $current_parts[1] < (int) $upgrade_parts[1] ) {
			return 'minor';
		}
		if ( (int) $current_parts[2] < (int) $upgrade_parts[2] ) {
			return 'patch';
		}
		return 'unknown';
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
