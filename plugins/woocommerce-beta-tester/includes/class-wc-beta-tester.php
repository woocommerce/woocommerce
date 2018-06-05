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
	 * Constructor
	 */
	public function __construct() {
		$this->config = array(
			'plugin_file'        => 'woocommerce/woocommerce.php',
			'slug'               => 'woocommerce',
			'proper_folder_name' => 'woocommerce',
			'api_url'            => 'https://api.github.com/repos/woocommerce/woocommerce',
			'github_url'         => 'https://github.com/woocommerce/woocommerce',
			'requires'           => '4.4',
			'tested'             => '4.9',
		);

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'api_check' ) );
		add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 3 );

		$this->includes();
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		include_once( dirname( __FILE__ ) . '/class-wc-beta-tester-admin-menus.php' );
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
		$this->config['zip_url']      = 'https://github.com/woocommerce/woocommerce/zipball/' . $this->config['new_version'];
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
	 * Get New Version from GitHub
	 *
	 * @since 1.0
	 * @return int $version the version number
	 */
	public function get_latest_prerelease() {
		$tagged_version = get_site_transient( md5( $this->config['slug'] ) . '_latest_tag' );

		if ( $this->overrule_transients() || empty( $tagged_version ) ) {

			$raw_response = wp_remote_get( trailingslashit( $this->config['api_url'] ) . 'releases' );

			if ( is_wp_error( $raw_response ) ) {
				return false;
			}

			$releases       = json_decode( $raw_response['body'] );
			$tagged_version = false;

			if ( is_array( $releases ) ) {
				foreach ( $releases as $release ) {
					if ( $release->prerelease ) {
						$tagged_version = $release->tag_name;
						break;
					}
				}
			}

			// Refresh every 6 hours.
			if ( ! empty( $tagged_version ) ) {
				set_site_transient( md5( $this->config['slug'] ) . '_latest_tag', $tagged_version, 60 * 60 * 6 );
			}
		}

		return $tagged_version;
	}

	/**
	 * Get GitHub Data from the specified repository.
	 *
	 * @since 1.0
	 * @return array $github_data The data.
	 */
	public function get_github_data() {
		if ( ! empty( $this->github_data ) ) {
			$github_data = $this->github_data;
		} else {
			$github_data = get_site_transient( md5( $this->config['slug'] ) . '_github_data' );

			if ( $this->overrule_transients() || ( ! isset( $github_data ) || ! $github_data || '' === $github_data ) ) {
				$github_data = wp_remote_get( $this->config['api_url'] );

				if ( is_wp_error( $github_data ) ) {
					return false;
				}

				$github_data = json_decode( $github_data['body'] );

				// Refresh every 6 hours.
				set_site_transient( md5( $this->config['slug'] ) . '_github_data', $github_data, 60 * 60 * 6 );
			}

			// Store the data in this class instance for future calls.
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
		return ! empty( $_date->updated_at ) ? date( 'Y-m-d', strtotime( $_date->updated_at ) ) : false;
	}

	/**
	 * Get plugin description
	 *
	 * @since 1.0
	 * @return string $description the description
	 */
	public function get_description() {
		$_description = $this->get_github_data();
		return ! empty( $_description->description ) ? $_description->description : false;
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
	 * Hook into the plugin update check and connect to GitHub.
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
			$response->url         = $this->config['github_url'];
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

	// TODO: the code below depends on merging bor0's PR #19.
	// TODO: move these out with other wp.org API related bits to extra module?
	/**
	 * Return true if version string is a beta version.
	 *
	 * @param string $version_str Version string.
	 * @return bool
	 */
	protected function is_beta_version( $version_str ) {
		return strpos( $version_str, 'beta' ) !== false;
	}

	/**
	 * Return true if version string is a Release Candidate.
	 *
	 * @param string $version_str Version string.
	 * @return bool
	 */
	protected function is_rc_version( $version_str ) {
		return strpos( $version_str, 'rc' ) !== false;
	}

	/**
	 * Return true if version string is a stable version.
	 *
	 * @param string $version_str Version string.
	 * @return bool
	 */
	protected function is_stable_version( $version_str ) {
		return ! $this->is_beta_version( $version_str ) && ! $this->is_rc_version( $version_str );
	}

	/**
	 * Return true if release's version string belongs to beta channel, i.e.
	 * if it's beta, rc or stable release.
	 *
	 * @param string $version_str Version string of the release.
	 * @return bool
	 */
	protected function is_in_beta_channel( $version_str ) {
		return $this->is_beta_version( $version_str ) || $this->is_rc_version( $version_str ) || $this->is_stable_version( $version_str );
	}

	/**
	 * Return true if release's version string belongs to release candidate channel, i.e.
	 * if it's rc or stable release.
	 *
	 * @param string $version_str Version string of the release.
	 * @return bool
	 */
	protected function is_in_rc_channel( $version_str ) {
		return $this->is_rc_version( $version_str ) || $this->is_stable_version( $version_str );
	}

	/**
	 * Return true if release's version string belongs to stable channel, i.e.
	 * if it's stable release and not a beta or rc.
	 *
	 * @param string $version_str Version string of the release.
	 * @return bool
	 */
	protected function is_in_stable_channel( $version_str ) {
		return $this->is_stable_version( $version_str );
	}

	/**
	 * Return available versions from wp.org tags belonging to selected channel.
	 *
	 * @param string $channel Filter versions by channel: all|beta|rc|stable.
	 * @return array(string)
	 */
	public function get_tags( $channel = 'all' ) {
		$data = $this->get_wporg_data();
		$releases       = (array) $data->versions;
		unset( $releases['trunk'] );

		if ( 'beta' === $channel ) {
			$releases = array_filter( $releases, array( $this, 'is_in_beta_channel' ), ARRAY_FILTER_USE_KEY );
		} elseif ( 'rc' === $channel ) {
			$releases = array_filter( $releases, array( $this, 'is_in_rc_channel' ), ARRAY_FILTER_USE_KEY );
		} elseif ( 'stable' === $channel ) {
			$releases = array_filter( $releases, array( $this, 'is_in_stable_channel' ), ARRAY_FILTER_USE_KEY );
		};

		return array_keys( $releases );
	}

	//TODO: move these to admin section, but some of the code needs to be refactored first, probably to submodules such as API interface and plugin interface
	// to be able to get a list of versions and current version.
	/**
	 * Return HTML code representation of list of WooCommerce versions for the selected channel.
	 *
	 * @param string $channel Filter versions by channel: all|beta|rc|stable.
	 * @return string
	 */
	public function get_versions_html( $channel ) {
		$tags = $this->get_tags( $channel );

		if ( ! $tags ) {
			return '';
		}

		usort( $tags, 'version_compare' );
		$tags = array_reverse( $tags );

		$versions_html           = '<ul class="wcbt-version-list">';
		$plugin_data             = $this->get_plugin_data();
		$this->config['version'] = $plugin_data['Version'];

		// Loop through versions and output in a radio list.
		foreach ( $tags as $tag_version ) {

			$versions_html .= '<li class="wcbt-version-li">';
			$versions_html .= '<label><input type="radio" value="' . esc_attr( $tag_version ) . '" name="switch_to_version">' . $tag_version;

			// Is this the current version?
			if ( $tag_version === $this->config['version'] ) {
				$versions_html .= '<span class="wcbt-current-version">' . esc_html__( 'Installed Version', 'woocommerce-beta-tester' ) . '</span>';
			}

			$versions_html .= '</label>';
			$versions_html .= '</li>';
		}

		$versions_html .= '</ul>';

		return $versions_html;
	}

	// TODO: this needs css (and maybe a bit of JS), but the idea is to display Switch version button that displays modal which contains the actual form submit,
	// similarly to WP_Rollback.
	/**
	 * Echo HTML form to switch WooCommerce versions, filtered for the selected channel.
	 *
	 * @param string $channel Filter versions by channel: all|beta|rc|stable.
	 */
	public function get_select_versions_form_html( $channel ) {
		?>
		<div class="wrap">
			<div class="wcbt-content-wrap">
				<h1><?php esc_html_e( 'Available WooCommerce versions', 'woocommerce-beta-tester' ); ?></h1>
				<form name="wcbt-select-version" class="wcbt-select-version-form" action="<?php echo esc_attr( admin_url( '/index.php' ) ); ?>">
					<div class="wcbt-versions-wrap">
						<?php echo $this->get_versions_html( $channel ); // WPCS: XSS ok. ?>
					</div>
					<div class="wcbt-submit-wrap">
						<a href="#wcbt-modal-confirm" class="magnific-popup button-primary wcbt-rollback-disabled"><?php esc_html_e( 'Switch version', 'woocommerce-beta-tester' ); ?></a>
						<input type="button" value="<?php esc_html_e( 'Cancel', 'woocommerce-beta-tester' ); ?>" class="button" onclick="location.href='<?php echo esc_attr( wp_get_referer() ); ?>';" />
					</div>
					<?php wp_nonce_field( 'wcbt_switch_version_nonce' ); ?>
					<div id="wcbt-modal-confirm" class="white-popup mfp-hide">
						<div class="wcbt-modal-inner">
							<p class="wcbt-rollback-intro"><?php esc_html_e( 'Are you sure you want to switch the version of WooCommerce plugin?', 'woocommerce-beta-tester' ); ?></p>

							<div class="wcbt-rollback-details">
								<table class="wcbt-widefat">
									<tbody>
									<tr class="alternate">
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'Installed Version:', 'woocommerce-beta-tester' ); ?></label>
										</td>
										<td><span class="wcbt-installed-version"><?php echo esc_html( $this->config['version'] ); ?></span></td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'New Version:', 'woocommerce-beta-tester' ); ?></label>
										</td>
										<td><span class="wcbt-new-version"></span></td>
									</tr>
									</tbody>
								</table>
							</div>

							<div class="wcbt-notice">
								<p><?php esc_html_e( 'Notice: We strongly recommend you perform the test on a staging site and create a complete backup of your WordPress files and database prior to performing a rollback. We are not responsible for any misuse, deletions, white screens, fatal errors, or any other issue arising from using this plugin.', 'woocommerce-beta-tester' ); ?></p>
							</div>

							<input type="submit" value="<?php esc_attr_e( 'Switch version', 'woocommerce-beta-tester' ); ?>" class="button-primary wcbt-go" />
							<a href="#" class="button wcbt-close"><?php esc_attr_e( 'Cancel', 'woocommerce-beta-tester' ); ?></a>
						</div>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

}
