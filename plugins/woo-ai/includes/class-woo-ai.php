<?php
/**
 * Woo AI plugin main class
 *
 * @package Woo_AI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Woo_AI Main Class.
 */
class Woo_AI {

	/**
	 * Config
	 *
	 * @var array
	 */
	private $plugin_config;

	/**
	 * Plugin instance.
	 *
	 * @var Woo_AI
	 */
	protected static $instance = null;

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
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', WOO_AI_FILE ) );
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->plugin_name   = plugin_basename( WOO_AI_FILE );
		$this->plugin_config = array(
			'plugin_file'        => 'woocommerce/woocommerce.php',
			'slug'               => 'woocommerce',
			'proper_folder_name' => 'woocommerce',
			'api_url'            => 'https://K.wordpress.org/plugins/info/1.0/woocommerce.json',
			'repo_url'           => 'https://wordpress.org/plugins/woocommerce/',
		);

		add_filter( 'jetpack_offline_mode', '__return_false' );
		add_action( 'current_screen', array( $this, 'includes' ) );
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		$current_screen = get_current_screen();

		if ( 'post' === $current_screen->base && 'product' === $current_screen->post_type ) {
			include_once dirname( __FILE__ ) . '/class-woo-ai-product-text-generation.php';
		}
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

}
