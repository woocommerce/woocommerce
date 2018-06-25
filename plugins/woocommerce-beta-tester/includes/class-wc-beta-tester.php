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
	private $config = array();

	/**
	 * Plugin instance.
	 *
	 * @var WC_Beta_Tester
	 */
	protected static $_instance = null;

	/**
	 * Main Instance.
	 */
	public static function instance() {
		self::$_instance = is_null( self::$_instance ) ? new self() : self::$_instance;

		return self::$_instance;
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
				'channel'     => 'stable',
				'auto_update' => false,
			)
		);

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
		$this->plugin_name = plugin_basename( WC_BETA_TESTER_FILE );

		$this->config = array(
			'plugin_file'        => 'woocommerce/woocommerce.php',
			'slug'               => 'woocommerce',
			'proper_folder_name' => 'woocommerce',
			'api_url'            => 'https://api.wordpress.org/plugins/info/1.0/woocommerce.json',
			'repo_url'           => 'https://wordpress.org/plugins/woocommerce/',
			'requires'           => '4.4',
			'tested'             => '4.9',
		);

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'api_check' ) );
		add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 3 );
		add_filter( 'auto_update_plugin', 'auto_update_woocommerce', 100, 2 );
		add_filter( 'plugins_api_result', array( $this, 'plugin_api_prerelease_info' ), 10, 3 );
		add_filter( "plugin_action_links_{$this->plugin_name}", array( $this, 'plugin_action_links' ), 10, 1 );

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
	 * Update args.
	 */
	public function set_update_args() {
		$plugin_data                  = $this->get_plugin_data();
		$this->config['plugin_name']  = $plugin_data['Name'];
		$this->config['version']      = $plugin_data['Version'];
		$this->config['author']       = $plugin_data['Author'];
		$this->config['homepage']     = $plugin_data['PluginURI'];
		$this->config['new_version']  = $this->get_latest_prerelease();
		$this->config['last_updated'] = $this->get_date();
		$this->config['description']  = $this->get_description();
		$this->config['zip_url']      = $this->get_download_url( $this->config['new_version'] );
	}

	/**
	 * Check wether or not the transients need to be overruled and API needs to be called for every single page load
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
	public function get_latest_prerelease() {
		$tagged_version = get_site_transient( md5( $this->config['slug'] ) . '_latest_tag' );

		if ( $this->overrule_transients() || empty( $tagged_version ) ) {

			$data = $this->get_wporg_data();

			$latest_version = $data->version;
			$versions       = (array) $data->versions;

			foreach ( $versions as $version => $download_url ) {
				if ( $this->is_prerelease( $version ) ) {
					$tagged_version = $version;
				}
			}

			// Refresh every 6 hours.
			if ( ! empty( $tagged_version ) ) {
				set_site_transient( md5( $this->config['slug'] ) . '_latest_tag', $tagged_version, HOUR_IN_SECONDS * 6 );
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

		$wporg_data = get_site_transient( md5( $this->config['slug'] ) . '_wporg_data' );

		if ( $this->overrule_transients() || ( ! isset( $wporg_data ) || ! $wporg_data || '' === $wporg_data ) ) {
			$wporg_data = wp_remote_get( $this->config['api_url'] );

			if ( is_wp_error( $wporg_data ) ) {
				return false;
			}

			$wporg_data = json_decode( $wporg_data['body'] );

			// Refresh every 6 hours.
			set_site_transient( md5( $this->config['slug'] ) . '_wporg_data', $wporg_data, HOUR_IN_SECONDS * 6 );
		}

		// Store the data in this class instance for future calls.
		$this->wporg_data = $wporg_data;

		return $wporg_data;
	}
	/**
	 * Get update date
	 *
	 * @since 1.0
	 * @return string $date the date
	 */
	public function get_date() {
		$data = $this->get_wporg_data();
		return ! empty( $data->last_updated ) ? date( 'Y-m-d', strtotime( $data->last_updated ) ) : false;
	}

	/**
	 * Get plugin description
	 *
	 * @since 1.0
	 * @return string $description the description
	 */
	public function get_description() {
		$data = $this->get_wporg_data();

		if ( empty( $data->sections->description ) ) {
			return false;
		}

		$data = $data->sections->description;

		if ( preg_match( '%(<p[^>]*>.*?</p>)%i', $data, $regs ) ) {
			$data = strip_tags( $regs[1] );
		}

		return $data;
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
		return get_plugin_data( WP_PLUGIN_DIR . '/' . $this->config['plugin_file'] );
	}

	/**
	 * Hook into the plugin update check and connect to WPorg.
	 *
	 * @since 1.0
	 * @param object $transient The plugin data transient.
	 * @return object $transient Updated plugin data transient.
	 */
	public function api_check( $transient ) {
		// Check if the transient contains the 'checked' information,
		// If not, just return its value without hacking it.
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Clear our transient.
		delete_site_transient( md5( $this->config['slug'] ) . '_latest_tag' );

		// Update tags.
		$this->set_update_args();

		// check the version and decide if it's new.
		$update = version_compare( $this->config['new_version'], $this->config['version'], '>' );

		if ( $update ) {
			$response              = new stdClass();
			$response->plugin      = $this->config['slug'];
			$response->new_version = $this->config['new_version'];
			$response->slug        = $this->config['slug'];
			$response->url         = $this->config['repo_url'];
			$response->package     = $this->config['zip_url'];

			// If response is false, don't alter the transient.
			if ( false !== $response ) {
				$transient->response[ $this->config['plugin_file'] ] = $response;
			}
		}

		return $transient;
	}

	/**
	 * Get Plugin info.
	 *
	 * @since 1.0
	 * @param bool   $false    Always false.
	 * @param string $action   The API function being performed.
	 * @param object $response The plugin info.
	 * @return object
	 */
	public function get_plugin_info( $false, $action, $response ) {
		// Check if this call API is for the right plugin.
		if ( ! isset( $response->slug ) || $response->slug !== $this->config['slug'] ) {
			return false;
		}

		// Update tags.
		$this->set_update_args();

		$response->slug          = $this->config['slug'];
		$response->plugin        = $this->config['slug'];
		$response->name          = $this->config['plugin_name'];
		$response->plugin_name   = $this->config['plugin_name'];
		$response->version       = $this->config['new_version'];
		$response->author        = $this->config['author'];
		$response->homepage      = $this->config['homepage'];
		$response->requires      = $this->config['requires'];
		$response->tested        = $this->config['tested'];
		$response->downloaded    = 0;
		$response->last_updated  = $this->config['last_updated'];
		$response->sections      = array( 'description' => $this->config['description'] );
		$response->download_link = $this->config['zip_url'];

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
			$corrected_source = trailingslashit( $remote_source ) . trailingslashit( $this->config['proper_folder_name'] );

			if ( $wp_filesystem->move( $source, $corrected_source, true ) ) {
				return $corrected_source;
			} else {
				return new WP_Error();
			}
		}

		return $source;
	}

	/**
	 * Add additional information to the Plugin Information version details modal.
	 *
	 * @param object|WP_Error $res    Response object or WP_Error.
	 * @param string          $action The type of information being requested from the Plugin Installation API.
	 * @param object          $args   Plugin API arguments.
	 * @return object|WP_Error
	 */
	public function plugin_api_prerelease_info( $res, $action, $args ) {
		// We only care about the plugin information action for woocommerce.
		if ( ! isset( $args->slug ) || 'woocommerce' !== $args->slug || 'plugin_information' !== $action ) {
			return $res;
		}

		// Not a pre-release, no need to do anything.
		if ( ! $this->is_prerelease( $res->version ) ) {
			return $res;
		}

		if ( isset( $res->sections['description'] ) ) {
			$res->sections['description'] = __( '<h1><span>&#9888;</span>This is a pre-release<span>&#9888;</span></h1>', 'woocommerce-beta-tester' )
				. $res->sections['description'];
		}

		$res->sections['pre-release_information']  = make_clickable( wpautop( $this->get_version_information( $res->version ) ) );
		$res->sections['pre-release_information'] .= sprintf(
			'<p><a target="_blank" href="%s">' . __( 'Read more on GitHub', 'woocommerce-beta-tester' ) . '</a></p>',
			'https://github.com/woocommerce/woocommerce/releases/tag/' . $res->version
		);

		return $res;
	}

	/**
	 * Enable auto updates for WooCommerce.
	 *
	 * @param bool   $update Should this autoupdate.
	 * @param object $plugin Plugin being checked.
	 * @return bool
	 */
	public function auto_update_woocommerce( $update, $plugin ) {
		if ( true === $this->get_settings()->auto_update && 'woocommerce' === $item->slug ) {
			return true;
		} else {
			return $update;
		}
	}

	/**
	 * Gets release information from GitHub.
	 *
	 * @param string $version Version number.
	 * @return bool|string False on error, description otherwise
	 */
	public function get_version_information( $version ) {
		$url = 'https://api.github.com/repos/woocommerce/woocommerce/releases/tags/' . $version;

		$github_data = get_site_transient( md5( $url ) . '_github_data' );

		if ( $this->overrule_transients() || empty( $github_data ) ) {
			$github_data = wp_remote_get( $url, array(
				'sslverify' => false,
			) );

			if ( is_wp_error( $github_data ) ) {
				return false;
			}

			$github_data = json_decode( $github_data['body'] );

			if ( empty( $github_data->body ) ) {
				return false;
			}

			$github_data = $github_data->body;

			// Refresh every 6 hours.
			set_site_transient( md5( $url ) . '_github_data', $github_data, HOUR_IN_SECONDS * 6 );
		}

		return $github_data;
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
				esc_url( admin_url( 'tools.php?page=wc-beta-tester-version-picker' ) ),
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
