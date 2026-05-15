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
	 * URL to download the nightly build zip from GitHub.
	 */
	const NIGHTLY_DOWNLOAD_URL = 'https://github.com/woocommerce/woocommerce/releases/download/nightly/woocommerce-trunk-nightly.zip';

	/**
	 * URL to fetch nightly release info from GitHub API.
	 */
	const NIGHTLY_VERSION_URL = 'https://api.github.com/repos/woocommerce/woocommerce/releases/tags/nightly';

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
	 * GitHub nightly data
	 *
	 * @var object
	 */
	private $nightly_data;

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
		delete_site_transient( 'wc_beta_tester_nightly_data' );
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
			'plugin_file'        => WC_PLUGIN_BASENAME,
			'slug'               => 'woocommerce',
			'proper_folder_name' => 'woocommerce',
			'api_url'            => 'https://api.wordpress.org/plugins/info/1.0/woocommerce.json',
			'repo_url'           => 'https://wordpress.org/plugins/woocommerce/',
		);

		add_filter( "plugin_action_links_{$this->plugin_name}", array( $this, 'plugin_action_links' ), 10, 1 );
		add_filter( 'auto_update_plugin', array( $this, 'auto_update_woocommerce' ), 100, 2 );

		// Always add source selection filter for folder renaming (needed for nightly/GitHub downloads).
		add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 3 );

		if ( 'stable' !== $this->get_settings()->channel ) {
			// Priority 99 to run after WC_Helper_Updater (priority 21) which may
			// otherwise overwrite our changes to the update transient.
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'api_check' ), 99 );
			add_filter( 'plugins_api_result', array( $this, 'plugins_api_result' ), 10, 3 );
		}

		// Track when WooCommerce is updated so we can detect new nightly builds.
		add_action( 'upgrader_process_complete', array( $this, 'on_upgrade_complete' ), 10, 2 );

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
	 * Get New Version from WPorg or GitHub (for nightly)
	 *
	 * @since 1.0
	 * @return string|false $version the version number or false on failure.
	 */
	public function get_latest_channel_release() {
		$tagged_version = get_site_transient( md5( $this->plugin_config['slug'] ) . '_latest_tag' );
		$channel        = $this->get_settings()->channel;

		if ( $this->overrule_transients() || empty( $tagged_version ) ) {

			// Handle nightly channel separately - it doesn't come from WP.org.
			// Use date-based version (YYYY.MM.DD-nightly) showing when the build was created.
			// This format satisfies WooCommerce's requirement for X.Y.Z version format.
			if ( 'nightly' === $channel ) {
				$asset_timestamp = $this->get_nightly_asset_timestamp();
				$nightly_date    = $asset_timestamp ? gmdate( 'Y.m.d', strtotime( $asset_timestamp ) ) : gmdate( 'Y.m.d' );
				$tagged_version  = $nightly_date . '-nightly';
				set_site_transient( md5( $this->plugin_config['slug'] ) . '_latest_tag', $tagged_version, HOUR_IN_SECONDS );
				return $tagged_version;
			}

			// Existing WP.org logic for beta/rc/stable channels.
			$data = $this->get_wporg_data();

			if ( ! $data ) {
				return false;
			}

			$versions = (array) $data->versions;

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
	 * Get nightly release data from GitHub API.
	 *
	 * @since 3.1.0
	 * @return object|false The nightly data or false on failure.
	 */
	public function get_nightly_data() {
		if ( ! empty( $this->nightly_data ) ) {
			return $this->nightly_data;
		}

		$nightly_data = get_site_transient( 'wc_beta_tester_nightly_data' );

		if ( $this->overrule_transients() || empty( $nightly_data ) ) {
			$response = wp_remote_get(
				self::NIGHTLY_VERSION_URL,
				array(
					'headers' => array(
						'Accept' => 'application/vnd.github.v3+json',
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$nightly_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( empty( $nightly_data ) || isset( $nightly_data->message ) ) {
				return false;
			}

			// Cache for 1 hour (nightlies update daily, but we check more frequently).
			set_site_transient( 'wc_beta_tester_nightly_data', $nightly_data, HOUR_IN_SECONDS );
		}

		// Store the data in this class instance for future calls.
		$this->nightly_data = $nightly_data;

		return $nightly_data;
	}

	/**
	 * Get plugin download URL.
	 *
	 * @since 1.0
	 * @param string $version The version.
	 * @return string
	 */
	public function get_download_url( $version ) {
		// Handle nightly builds from GitHub.
		if ( self::is_nightly_version( $version ) ) {
			return self::NIGHTLY_DOWNLOAD_URL;
		}

		// Handle WP.org versions.
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
		$plugin_data    = $this->get_plugin_data();
		$current_version = $plugin_data['Version'];
		$new_version    = $this->get_latest_channel_release();

		if ( ! $new_version ) {
			return $transient;
		}

		// Check if an update is available.
		if ( 'nightly' === $this->get_settings()->channel ) {
			// For nightly channel, check if the nightly release has been updated
			// since we last installed it (using the asset's updated_at timestamp).
			$update = $this->is_nightly_update_available();
		} else {
			// Standard version comparison for other channels.
			$update = version_compare( $new_version, $current_version, '>' );
		}

		if ( ! $update ) {
			return $transient;
		}

		// Remove from no_update if present (WordPress may have put it there
		// because the .org version is lower than our installed dev version).
		if ( isset( $transient->no_update['woocommerce/woocommerce.php'] ) ) {
			unset( $transient->no_update['woocommerce/woocommerce.php'] );
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

		$warning = '';

		if ( self::is_nightly_version( $new_version ) ) {
			$warning = __( '<h1><span>&#9888;</span>This is a nightly development build<span>&#9888;</span></h1>', 'woocommerce-beta-tester' );
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

		// Set the changelog URL based on version type.
		if ( self::is_nightly_version( $new_version ) ) {
			$changelog_url = 'https://github.com/woocommerce/woocommerce/releases/tag/nightly';
		} else {
			$changelog_url = 'https://github.com/woocommerce/woocommerce/blob/' . $response->version . '/readme.txt';
		}

		$response->sections['changelog'] = sprintf(
			'<p><a target="_blank" href="%s">' . __( 'Read the changelog and find out more about the release on GitHub.', 'woocommerce-beta-tester' ) . '</a></p>',
			$changelog_url
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

		// Get the folder name of the extracted plugin (e.g., 'woocommerce' or 'woocommerce-woocommerce-abc123').
		$source_folder_name = basename( untrailingslashit( $source ) );

		// Handle GitHub downloads that extract to non-standard folder names.
		// Other GitHub downloads extract to 'woocommerce-woocommerce-{hash}/'.
		// Only rename if the extracted folder name itself needs correction.
		// Nightly builds already extract to 'woocommerce/' so no rename needed.
		if ( strpos( $source_folder_name, 'woocommerce-woocommerce-' ) === 0 ) {
			$corrected_source = trailingslashit( $remote_source ) . trailingslashit( $this->plugin_config['proper_folder_name'] );

			if ( $wp_filesystem->move( $source, $corrected_source, true ) ) {
				return $corrected_source;
			} else {
				return new WP_Error( 'move_failed', 'Could not move ' . $source . ' to ' . $corrected_source );
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
		return ! self::is_beta_version( $version_str ) && ! self::is_rc_version( $version_str ) && ! self::is_nightly_version( $version_str );
	}

	/**
	 * Return true if version string is a nightly version.
	 *
	 * @since 3.1.0
	 * @param string $version_str Version string.
	 * @return bool
	 */
	protected static function is_nightly_version( $version_str ) {
		// Nightly versions are either:
		// - Contains '-nightly' (e.g., '2026.05.15-nightly' used in the update system)
		// - Version strings containing '-dev' (e.g., '10.9.0-dev' from installed builds)
		return strpos( $version_str, '-nightly' ) !== false || strpos( $version_str, '-dev' ) !== false;
	}

	/**
	 * Check if a nightly update is available by comparing the GitHub release
	 * asset's updated_at timestamp to when we last installed a nightly.
	 *
	 * @since 3.1.0
	 * @return bool True if a newer nightly is available.
	 */
	protected function is_nightly_update_available() {
		$nightly_data = $this->get_nightly_data();

		if ( ! $nightly_data || empty( $nightly_data->assets ) ) {
			return false;
		}

		// Find the zip asset's updated_at timestamp.
		$asset_updated_at = null;
		foreach ( $nightly_data->assets as $asset ) {
			if ( isset( $asset->name ) && 'woocommerce-trunk-nightly.zip' === $asset->name ) {
				$asset_updated_at = isset( $asset->updated_at ) ? $asset->updated_at : null;
				break;
			}
		}

		if ( ! $asset_updated_at ) {
			// Can't determine, offer update to be safe.
			return true;
		}

		// Get the timestamp of when we last installed a nightly.
		$last_installed = get_option( 'wc_beta_tester_nightly_installed_at' );

		if ( ! $last_installed ) {
			// Never installed a nightly via this plugin, offer update.
			return true;
		}

		// Compare timestamps - offer update if asset is newer.
		return strtotime( $asset_updated_at ) > strtotime( $last_installed );
	}

	/**
	 * Store the timestamp when a nightly build was installed.
	 * Called after successful nightly installation.
	 *
	 * @since 3.1.0
	 * @param string $timestamp ISO 8601 timestamp of the installed nightly asset.
	 */
	public function set_nightly_installed_timestamp( $timestamp ) {
		update_option( 'wc_beta_tester_nightly_installed_at', $timestamp );
	}

	/**
	 * Get the current nightly asset's updated_at timestamp from GitHub.
	 *
	 * @since 3.1.0
	 * @return string|null ISO 8601 timestamp or null if not available.
	 */
	public function get_nightly_asset_timestamp() {
		$nightly_data = $this->get_nightly_data();

		if ( ! $nightly_data || empty( $nightly_data->assets ) ) {
			return null;
		}

		foreach ( $nightly_data->assets as $asset ) {
			if ( isset( $asset->name ) && 'woocommerce-trunk-nightly.zip' === $asset->name ) {
				return isset( $asset->updated_at ) ? $asset->updated_at : null;
			}
		}

		return null;
	}

	/**
	 * Callback for upgrader_process_complete action.
	 * Stores the nightly timestamp when WooCommerce is updated via standard updater.
	 *
	 * @since 3.1.0
	 * @param WP_Upgrader $upgrader WP_Upgrader instance.
	 * @param array       $options  Array of update data.
	 */
	public function on_upgrade_complete( $upgrader, $options ) {
		// Only handle plugin updates.
		if ( 'update' !== $options['action'] || 'plugin' !== $options['type'] ) {
			return;
		}

		// Check if WooCommerce was updated.
		$wc_updated = false;
		if ( isset( $options['plugins'] ) && is_array( $options['plugins'] ) ) {
			$wc_updated = in_array( 'woocommerce/woocommerce.php', $options['plugins'], true );
		} elseif ( isset( $options['plugin'] ) ) {
			$wc_updated = 'woocommerce/woocommerce.php' === $options['plugin'];
		}

		if ( ! $wc_updated ) {
			return;
		}

		// If we're on nightly channel, store the asset timestamp.
		if ( 'nightly' === $this->get_settings()->channel ) {
			$nightly_timestamp = $this->get_nightly_asset_timestamp();
			if ( $nightly_timestamp ) {
				$this->set_nightly_installed_timestamp( $nightly_timestamp );
			}
		}
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
