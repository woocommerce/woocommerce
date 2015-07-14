<?php
/**
 * Plugin Name: WooCommerce Beta Tester
 * Plugin URI: https://github.com/woothemes/woocommerce-beta-tester
 * Description: Run bleeding edge versions of WooCommerce from our Github repo. This will replace your installed version of WooCommerce with the latest tagged release on Github - use with caution, and not on production sites. You have been warned.
 * Version: 1.0.0
 * Author: Mike Jolley
 * Author URI: http://woothemes.com/
 * Requires at least: 4.2
 * Tested up to: 4.2
 *
 * Based on WP_GitHub_Updater by Joachim Kudish.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Beta_Tester' ) ) :

	/**
	 * WC_Beta_Tester Main Class
	 */
	class WC_Beta_Tester {

		/** Config */
		private $config = array();

		/** Github Data */
		protected static $_instance = null;

		/**
		 * Main Instance
		 */
		public static function instance() {
			return self::$_instance = is_null( self::$_instance ) ? new self() : self::$_instance;
		}

		/**
		 * Ran on activation to flush update cache
		 */
		public static function activate() {
			delete_site_transient( 'update_plugins' );
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			if ( is_admin() ) {
				$this->config = array(
					'slug'               => 'woocommerce/woocommerce.php',
					'proper_folder_name' => 'woocommerce',
					'api_url'            => 'https://api.github.com/repos/woothemes/woocommerce',
					'github_url'         => 'https://github.com/woothemes/woocommerce',
					'requires'           => '4.2',
					'tested'             => '4.2',
					'readme'             => 'readme.txt'
				);
				$this->set_update_args();

					var_dump($this->config);

				add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'api_check' ) );
				add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 10, 3 );
				add_filter( 'upgrader_post_install', array( $this, 'upgrader_post_install' ), 10, 3 );
			}
		}

		/**
		 * Update args
		 * @return array
		 */
		public function set_update_args() {
			$plugin_data                    = $this->get_plugin_data();
			$this->config[ 'plugin_name' ]  = $plugin_data['Name'];
			$this->config[ 'version' ]      = $plugin_data['Version'];
			$this->config[ 'author' ]       = $plugin_data['Author'];
			$this->config[ 'homepage' ]     = $plugin_data['PluginURI'];
			$this->config[ 'new_version' ]  = $this->get_latest_tag();
			$this->config[ 'last_updated' ] = $this->get_date();
			$this->config[ 'description' ]  = $this->get_description();
			$this->config[ 'zip_url' ]      = 'https://github.com/woothemes/woocommerce/zipball/' . $this->config[ 'new_version' ];
		}

		/**
		 * Check wether or not the transients need to be overruled and API needs to be called for every single page load
		 *
		 * @return bool overrule or not
		 */
		public function overrule_transients() {
			return ( defined( 'WC_BETA_TESTER_FORCE_UPDATE' ) && WC_BETA_TESTER_FORCE_UPDATE );
		}

		/**
		 * Get New Version from GitHub
		 *
		 * @since 1.0
		 * @return int $version the version number
		 */
		public function get_latest_tag() {
			$tagged_version = get_site_transient( md5( $this->config['slug'] ) . '_latest_tag' );

			if ( $this->overrule_transients() || empty( $tagged_version ) ) {

				$raw_response = $this->remote_get( trailingslashit( $this->config['api_url'] ) . 'tags' );

				if ( is_wp_error( $raw_response ) ) {
					return false;
				}

				$tags = json_decode( $raw_response['body'] );

				if ( is_array( $tags ) ) {
					$latest_tag     = $tags[0];
					$tagged_version = $latest_tag->name;
				}

				// refresh every 6 hours
				if ( ! empty( $tagged_version ) ) {
					set_site_transient( md5( $this->config['slug'] ) . '_latest_tag', $tagged_version, 60*60*6 );
				}
			}

			return $tagged_version;
		}

		/**
		 * Interact with GitHub
		 *
		 * @param string $query
		 *
		 * @since 1.6
		 * @return mixed
		 */
		public function remote_get( $query ) {
			return wp_remote_get( $query );
		}

		/**
		 * Get GitHub Data from the specified repository
		 *
		 * @since 1.0
		 * @return array $github_data the data
		 */
		public function get_github_data() {
			if ( ! empty( $this->github_data ) ) {
				$github_data = $this->github_data;
			} else {
				$github_data = get_site_transient( md5( $this->config['slug'] ) . '_github_data' );

				if ( $this->overrule_transients() || ( ! isset( $github_data ) || ! $github_data || '' == $github_data ) ) {
					$github_data = $this->remote_get( $this->config['api_url'] );

					if ( is_wp_error( $github_data ) ) {
						return false;
					}

					$github_data = json_decode( $github_data['body'] );

					// refresh every 6 hours
					set_site_transient( md5( $this->config['slug'] ) . '_github_data', $github_data, 60*60*6 );
				}

				// Store the data in this class instance for future calls
				$this->github_data = $github_data;
			}

			return $github_data;
		}
		/**
		 * Get update date
		 *
		 * @since 1.0
		 * @return string $date the date
		 */
		public function get_date() {
			$_date = $this->get_github_data();
			return ( ! empty( $_date->updated_at ) ) ? date( 'Y-m-d', strtotime( $_date->updated_at ) ) : false;
		}

		/**
		 * Get plugin description
		 *
		 * @since 1.0
		 * @return string $description the description
		 */
		public function get_description() {
			$_description = $this->get_github_data();
			return ( ! empty( $_description->description ) ) ? $_description->description : false;
		}

		/**
		 * Get Plugin data
		 *
		 * @since 1.0
		 * @return object $data the data
		 */
		public function get_plugin_data() {
			return get_plugin_data( WP_PLUGIN_DIR . '/' . $this->config['slug'] );
		}

		/**
		 * Hook into the plugin update check and connect to GitHub
		 *
		 * @since 1.0
		 * @param object  $transient the plugin data transient
		 * @return object $transient updated plugin data transient
		 */
		public function api_check( $transient ) {
			// Check if the transient contains the 'checked' information
			// If not, just return its value without hacking it
			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			// Clear our transient
			delete_site_transient( md5( $this->config['slug'] ) . '_latest_tag' );

			// Update tags
			$this->set_update_args();

			// check the version and decide if it's new
			$update = version_compare( $this->config['new_version'], $this->config['version'] );

			if ( 1 === $update ) {
				$response              = new stdClass;
				$response->new_version = $this->config['new_version'];
				$response->slug        = $this->config['proper_folder_name'];
				$response->url         = $this->config['github_url'];
				$response->package     = $this->config['zip_url'];

				// If response is false, don't alter the transient
				if ( false !== $response ) {
					$transient->response[ $this->config['slug'] ] = $response;
				}
			}

			return $transient;
		}

		/**
		 * Get Plugin info
		 *
		 * @since 1.0
		 * @param bool    $false  always false
		 * @param string  $action the API function being performed
		 * @param object  $args   plugin arguments
		 * @return object $response the plugin info
		 */
		public function get_plugin_info( $false, $action, $response ) {
			// Check if this call API is for the right plugin
			if ( ! isset( $response->slug ) || $response->slug != $this->config['slug'] ) {
				return false;
			}

			$response->slug          = $this->config['slug'];
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
		 * Upgrader/Updater
		 * Move & activate the plugin, echo the update message
		 *
		 * @since 1.0
		 * @param boolean $true       always true
		 * @param mixed   $hook_extra not used
		 * @param array   $result     the result of the move
		 * @return array $result the result of the move
		 */
		public function upgrader_post_install( $true, $hook_extra, $result ) {
			global $wp_filesystem;

			// Move & Activate
			$proper_destination    = WP_PLUGIN_DIR . '/' . $this->config['proper_folder_name'];
			$wp_filesystem->move( $result['destination'], $proper_destination );
			$result['destination'] = $proper_destination;
			$activate              = activate_plugin( WP_PLUGIN_DIR . '/' . $this->config['slug'] );

			// Output the update message
			$fail    = __( 'The plugin has been updated, but could not be reactivated. Please reactivate it manually.' );
			$success = __( 'Plugin reactivated successfully.' );
			echo is_wp_error( $activate ) ? $fail : $success;
			return $result;
		}
	}

	register_activation_hook( __FILE__, array( 'WC_Beta_Tester', 'activate' ) );

	add_action( 'admin_init', array( 'WC_Beta_Tester', 'instance' ) );

endif;
