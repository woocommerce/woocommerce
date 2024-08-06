<?php
/**
 * Beta Tester plugin main class
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Beta_Tester Main Class.
 */
class WC_Beta_Tester {

	/**
	 * Config
	 *
	 * @var array
	 */
	private $plugin_config;

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin instance.
	 *
	 * @var WC_Beta_Tester
	 */
	protected static $instance = null;

	/**
	 * WP.org data
	 *
	 * @var object
	 */
	private $wporg_data;

	/**
	 * Main Instance.
	 */
	public static function instance() {
		self::$instance = is_null( self::$instance ) ? new self() : self::$instance;

		return self::$instance;
	}

	/**
	 * Ran on activation to flush update cache
	 */
	public static function activate() {
		delete_site_transient( 'update_plugins' );
		delete_site_transient( 'woocommerce_latest_tag' );
	}

	/**
	 * Get plugin settings.
	 *
	 * @return object
	 */
	public static function get_settings() {
		$settings = (object) wp_parse_args(
			get_option( 'wc_beta_tester_options', array() ),
			array(
				'channel'     => 'beta',
				'auto_update' => false,
			)
		);

		$settings->channel     = $settings->channel;
		$settings->auto_update = (bool) $settings->auto_update;

		return $settings;
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', WC_BETA_TESTER_FILE ) );
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->plugin_name   = plugin_basename( WC_BETA_TESTER_FILE );
		$this->plugin_config = array(
			'plugin_file'        => 'woocommerce/woocommerce.php',
			'slug'               => 'woocommerce',
			'proper_folder_name' => 'woocommerce',
			'api_url'            => 'https://api.wordpress.org/plugins/info/1.0/woocommerce.json',
			'repo_url'           => 'https://wordpress.org/plugins/woocommerce/',
		);

		add_filter( "plugin_action_links_{$this->plugin_name}", array( $this, 'plugin_action_links' ), 10, 1 );
		add_filter( 'auto_update_plugin', array( $this, 'auto_update_woocommerce' ), 100, 2 );

		if ( 'stable' !== $this->get_settings()->channel ) {
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'api_check' ) );
			add_filter( 'plugins_api_result', array( $this, 'plugins_api_result' ), 10, 3 );
			add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 3 );
		}

		$this->includes();
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		include_once dirname( __FILE__ ) . '/class-wc-beta-tester-admin-menus.php';
		include_once dirname( __FILE__ ) . '/class-wc-beta-tester-admin-assets.php';
	}

	/**
	 * Check whether or not the transients need to be overruled and API needs to be called for every single page load
	 *
	 * @return bool overrule or not
	 */
	public function overrule_transients() {
		return defined( 'WC_BETA_TESTER_FORCE_UPDATE' ) && WC_BETA_TESTER_FORCE_UPDATE;
	}

	/**
	 * Checks if a given version is a pre-release.
	 *
	 * @param string $version Version to compare.
	 * @return bool
	 */
	public function is_prerelease( $version ) {
		return preg_match( '/(.*)?-(beta|rc)(.*)/', $version );
	}

	/**
	 * Get New Version from WPorg
	 *
	 * @since 1.0
	 * @return int $version the version number
	 */
	public function get_latest_channel_release() {
		$tagged_version = get_site_transient( md5( $this->plugin_config['slug'] ) . '_latest_tag' );

		if ( $this->overrule_transients() || empty( $tagged_version ) ) {

			$data = $this->get_wporg_data();

			$latest_version = $data->version;
			$versions       = (array) $data->versions;
			$channel        = $this->get_settings()->channel;

			foreach ( $versions as $version => $download_url ) {
				if ( 'trunk' === $version ) {
					continue;
				}
				switch ( $channel ) {
					case 'stable':
						if ( $this->is_in_stable_channel( $version ) ) {
							$tagged_version = $version;
						}
						break;
					case 'rc':
						if ( $this->is_in_rc_channel( $version ) ) {
							$tagged_version = $version;
						}
						break;
					case 'beta':
						if ( $this->is_in_beta_channel( $version ) ) {
							$tagged_version = $version;
						}
						break;
				}
			}

			// Refresh every 6 hours.
			if ( ! empty( $tagged_version ) ) {
				set_site_transient( md5( $this->plugin_config['slug'] ) . '_latest_tag', $tagged_version, HOUR_IN_SECONDS * 6 );
			}
		}

		return $tagged_version;
	}

	/**
	 * Get Data from .org API.
	 *
	 * @since 1.0
	 * @return array $wporg_data The data.
	 */
	public function get_wporg_data() {
		if ( ! empty( $this->wporg_data ) ) {
			return $this->wporg_data;
		}

		$wporg_data = get_site_transient( md5( $this->plugin_config['slug'] ) . '_wporg_data' );

		if ( $this->overrule_transients() || ( ! isset( $wporg_data ) || ! $wporg_data || '' === $wporg_data ) ) {
			$wporg_data = wp_remote_get( $this->plugin_config['api_url'] );

			if ( is_wp_error( $wporg_data ) ) {
				return false;
			}

			$wporg_data = json_decode( $wporg_data['body'] );

			// Refresh every 6 hours.
			set_site_transient( md5( $this->plugin_config['slug'] ) . '_wporg_data', $wporg_data, HOUR_IN_SECONDS * 6 );
		}

		// Store the data in this class instance for future calls.
		$this->wporg_data = $wporg_data;

		return $wporg_data;
	}

	/**
	 * Get plugin download URL.
	 *
	 * @since 1.0
	 * @param string $version The version.
	 * @return string
	 */
	public function get_download_url( $version ) {
		$data = $this->get_wporg_data();

		if ( empty( $data->versions->$version ) ) {
			return false;
		}

		return $data->versions->$version;
	}

	/**
	 * Get Plugin data.
	 *
	 * @since 1.0
	 * @return object $data The data.
	 */
	public function get_plugin_data() {
		return get_plugin_data( WP_PLUGIN_DIR . '/' . $this->plugin_config['plugin_file'] );
	}

	/**
	 * Hook into the plugin update check and connect to WPorg.
	 *
	 * @since 1.0
	 * @param object $transient The plugin data transient.
	 * @return object $transient Updated plugin data transient.
	 */
	public function api_check( $transient ) {
		// Clear our transient.
		delete_site_transient( md5( $this->plugin_config['slug'] ) . '_latest_tag' );

		// Get version data.
		$plugin_data = $this->get_plugin_data();
		$version     = $plugin_data['Version'];
		$new_version = $this->get_latest_channel_release();

		// check the version and decide if it's new.
		$update = version_compare( $new_version, $version, '>' );

		if ( ! $update ) {
			return $transient;
		}

		// Populate response data.
		if ( ! isset( $transient->response['woocommerce/woocommerce.php'] ) ) {
			$transient->response['woocommerce/woocommerce.php'] = (object) $this->plugin_config;
		}

		$transient->response['woocommerce/woocommerce.php']->new_version = $new_version;
		$transient->response['woocommerce/woocommerce.php']->zip_url     = $this->get_download_url( $new_version );
		$transient->response['woocommerce/woocommerce.php']->package     = $this->get_download_url( $new_version );

		return $transient;
	}

	/**
	 * Filters the Plugin Installation API response results.
	 *
	 * @param object|WP_Error $response Response object or WP_Error.
	 * @param string          $action The type of information being requested from the Plugin Installation API.
	 * @param object          $args Plugin API arguments.
	 * @return object
	 */
	public function plugins_api_result( $response, $action, $args ) {
		// Check if this call API is for the right plugin.
		if ( ! isset( $response->slug ) || $response->slug !== $this->plugin_config['slug'] ) {
			return $response;
		}

		$new_version = $this->get_latest_channel_release();

		if ( version_compare( $response->version, $new_version, '=' ) ) {
			return $response;
		}

		if ( $this->is_beta_version( $new_version ) ) {
			$warning = __( '<h1><span>&#9888;</span>This is a beta release<span>&#9888;</span></h1>', 'woocommerce-beta-tester' );
		}

		if ( $this->is_rc_version( $new_version ) ) {
			$warning = __( '<h1><span>&#9888;</span>This is a pre-release version<span>&#9888;</span></h1>', 'woocommerce-beta-tester' );
		}

		// If we are returning a different version than the stable tag on .org, manipulate the returned data.
		$response->version       = $new_version;
		$response->download_link = $this->get_download_url( $new_version );

		$response->sections['changelog'] = sprintf(
			'<p><a target="_blank" href="%s">' . __( 'Read the changelog and find out more about the release on GitHub.', 'woocommerce-beta-tester' ) . '</a></p>',
			'https://github.com/woocommerce/woocommerce/blob/' . $response->version . '/readme.txt'
		);

		foreach ( $response->sections as $key => $section ) {
			$response->sections[ $key ] = $warning . $section;
		}

		return $response;
	}

	/**
	 * Rename the downloaded zip
	 *
	 * @param string      $source        File source location.
	 * @param string      $remote_source Remote file source location.
	 * @param WP_Upgrader $upgrader      WordPress Upgrader instance.
	 * @return string
	 */
	public function upgrader_source_selection( $source, $remote_source, $upgrader ) {
		global $wp_filesystem;

		if ( strstr( $source, '/woocommerce-woocommerce-' ) ) {
			$corrected_source = trailingslashit( $remote_source ) . trailingslashit( $this->plugin_config['proper_folder_name'] );

			if ( $wp_filesystem->move( $source, $corrected_source, true ) ) {
				return $corrected_source;
			} else {
				return new WP_Error();
			}
		}

		return $source;
	}

	/**
	 * Enable auto updates for WooCommerce.
	 *
	 * @param bool   $update Should this autoupdate.
	 * @param object $plugin Plugin being checked.
	 * @return bool
	 */
	public function auto_update_woocommerce( $update, $plugin ) {
		if ( true === $this->get_settings()->auto_update && 'woocommerce' === $plugin->slug ) {
			return true;
		} else {
			return $update;
		}
	}

	/**
	 * Return true if version string is a beta version.
	 *
	 * @param string $version_str Version string.
	 * @return bool
	 */
	protected static function is_beta_version( $version_str ) {
		return strpos( $version_str, 'beta' ) !== false;
	}

	/**
	 * Return true if version string is a Release Candidate.
	 *
	 * @param string $version_str Version string.
	 * @return bool
	 */
	protected static function is_rc_version( $version_str ) {
		return strpos( $version_str, 'rc' ) !== false;
	}

	/**
	 * Return true if version string is a stable version.
	 *
	 * @param string $version_str Version string.
	 * @return bool
	 */
	protected static function is_stable_version( $version_str ) {
		return ! self::is_beta_version( $version_str ) && ! self::is_rc_version( $version_str );
	}

	/**
	 * Return true if release's version string belongs to beta channel, i.e.
	 * if it's beta, rc or stable release.
	 *
	 * @param string $version_str Version string of the release.
	 * @return bool
	 */
	protected static function is_in_beta_channel( $version_str ) {
		return self::is_beta_version( $version_str ) || self::is_rc_version( $version_str ) || self::is_stable_version( $version_str );
	}

	/**
	 * Return true if release's version string belongs to release candidate channel, i.e.
	 * if it's rc or stable release.
	 *
	 * @param string $version_str Version string of the release.
	 * @return bool
	 */
	protected static function is_in_rc_channel( $version_str ) {
		return self::is_rc_version( $version_str ) || self::is_stable_version( $version_str );
	}

	/**
	 * Return true if release's version string belongs to stable channel, i.e.
	 * if it's stable release and not a beta or rc.
	 *
	 * @param string $version_str Version string of the release.
	 * @return bool
	 */
	protected static function is_in_stable_channel( $version_str ) {
		return self::is_stable_version( $version_str );
	}

	/**
	 * Return available versions from wp.org tags belonging to selected channel.
	 *
	 * @param string $channel Filter versions by channel: all|beta|rc|stable.
	 * @return array(string)
	 */
	public function get_tags( $channel = 'all' ) {
		$data     = $this->get_wporg_data();
		$releases = (array) $data->versions;

		unset( $releases['trunk'] );

		$releases = array_keys( $releases );
		foreach ( $releases as $index => $version ) {
			if ( version_compare( $version, '3.6', '<' ) ) {
				unset( $releases[ $index ] );
			}
		}

		if ( 'beta' === $channel ) {
			$releases = array_filter( $releases, array( __CLASS__, 'is_in_beta_channel' ) );
		} elseif ( 'rc' === $channel ) {
			$releases = array_filter( $releases, array( __CLASS__, 'is_in_rc_channel' ) );
		} elseif ( 'stable' === $channel ) {
			$releases = array_filter( $releases, array( __CLASS__, 'is_in_stable_channel' ) );
		}

		return $releases;
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param   mixed $links Plugin Action links.
	 * @return  array
	 */
	public function plugin_action_links( $links ) {
		$action_links = array(
			'switch-version' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'plugins.php?page=wc-beta-tester-version-picker' ) ),
				esc_html__( 'Switch versions', 'woocommerce-beta-tester' )
			),
			'settings'       => sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'plugins.php?page=wc-beta-tester' ) ),
				esc_html__( 'Settings', 'woocommerce-beta-tester' )
			),
		);

		return array_merge( $action_links, $links );
	}
}
