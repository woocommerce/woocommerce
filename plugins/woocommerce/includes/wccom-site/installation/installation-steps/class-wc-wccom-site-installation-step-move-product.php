<?php
/**
 * Move product to the correct location.
 *
 * @package WooCommerce\WCCom
 * @since   7.7.0
 */

use WC_REST_WCCOM_Site_Installer_Error_Codes as Installer_Error_Codes;
use WC_REST_WCCOM_Site_Installer_Error as Installer_Error;

defined( 'ABSPATH' ) || exit;

/**
 * WC_WCCOM_Site_Installation_Step_Move_Product class
 */
class WC_WCCOM_Site_Installation_Step_Move_Product implements WC_WCCOM_Site_Installation_Step {
	/**
	 * The current installation state.
	 *
	 * @var WC_WCCOM_Site_Installation_State
	 */
	protected $state;

	/**
	 * Constructor.
	 *
	 * @param array $state The current installation state.
	 */
	public function __construct( $state ) {
		$this->state = $state;
	}

	/**
	 * Run the step installation process.
	 *
	 * @throws WC_REST_WCCOM_Site_Installer_Error If installation failed.
	 */
	public function run() {
		$upgrader = WC_WCCOM_Site_Installer::get_wp_upgrader();

		$destination = 'plugin' === $this->state->get_product_type()
			? WP_PLUGIN_DIR
			: get_theme_root();

		$this->check_package_requirements( $destination );

		$package = array(
			'source'        => $this->state->get_unpacked_path(),
			'destination'   => $destination,
			'clear_working' => true,
			'hook_extra'    => array(
				'type'   => $this->state->get_product_type(),
				'action' => 'install',
			),
		);

		$result = $upgrader->install_package( $package );

		/**
		 * If install package returns error 'folder_exists' treat as success.
		 */
		if ( is_wp_error( $result ) && array_key_exists( 'folder_exists', $result->errors ) ) {
			$existing_folder_path = $result->error_data['folder_exists'];
			$plugin_info          = WC_WCCOM_Site_Installer::get_plugin_info( $existing_folder_path );

			$this->state->set_installed_path( $existing_folder_path );
			$this->state->set_already_installed_plugin_info( $plugin_info );
			$this->maybe_connect_theme();

			return $this->state;
		}

		if ( is_wp_error( $result ) ) {
			throw new Installer_Error( Installer_Error_Codes::INSTALLATION_FAILED, esc_html( $result->get_error_message() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Installer_Error_Codes constant is a static string, not unescaped output.
		}

		$this->state->set_installed_path( $result['destination'] );
		$this->maybe_connect_theme();

		return $this->state;
	}

	/**
	 * Stops the install when the package needs a newer WordPress or PHP than the site runs.
	 *
	 * WP_Upgrader::install_package() doesn't run the requirement check WordPress runs on an upload, so without
	 * this the extension lands on disk and only fails when it's activated, with a generic error.
	 * A copy that's already installed is left to the existing folder_exists handling.
	 *
	 * @since 11.4.0
	 * @param string $destination The plugins or themes directory the package installs into.
	 * @return void
	 * @throws WC_REST_WCCOM_Site_Installer_Error If the site doesn't meet the package's requirements.
	 */
	protected function check_package_requirements( string $destination ): void {
		$source = $this->get_package_root( (string) $this->state->get_unpacked_path() );
		if ( '' === $source || is_dir( trailingslashit( $destination ) . basename( $source ) ) ) {
			return;
		}

		$headers = $this->get_package_headers( $source );

		if ( ! is_wp_version_compatible( $headers['RequiresWP'] ) ) {
			$this->delete_unpacked_package();
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is a static string; the message is escaped below.
			throw new Installer_Error(
				Installer_Error_Codes::INCOMPATIBLE_WP_REQUIRED_VERSION,
				esc_html(
					sprintf(
						/* translators: 1: WordPress version the extension needs, 2: WordPress version the site runs. */
						__( 'This extension needs WordPress %1$s or later, and this site runs WordPress %2$s.', 'woocommerce' ),
						$headers['RequiresWP'],
						wp_get_wp_version()
					)
				)
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		if ( ! is_php_version_compatible( $headers['RequiresPHP'] ) ) {
			$this->delete_unpacked_package();
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is a static string; the message is escaped below.
			throw new Installer_Error(
				Installer_Error_Codes::INCOMPATIBLE_PHP_REQUIRED_VERSION,
				esc_html(
					sprintf(
						/* translators: 1: PHP version the extension needs, 2: PHP version the server runs. */
						__( 'This extension needs PHP %1$s or later, and this server runs PHP %2$s.', 'woocommerce' ),
						$headers['RequiresPHP'],
						PHP_VERSION
					)
				)
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}

	/**
	 * Gets the directory that holds the plugin or theme files, the way install_package() picks it.
	 *
	 * @since 11.4.0
	 * @param string $unpacked_path The directory the package was unpacked into.
	 * @return string The directory, or an empty string when there's nothing to read.
	 */
	private function get_package_root( string $unpacked_path ): string {
		if ( '' === $unpacked_path || ! is_dir( $unpacked_path ) ) {
			return '';
		}

		$entries = array_values( array_diff( (array) scandir( $unpacked_path ), array( '.', '..' ) ) );
		if ( 1 === count( $entries ) && is_dir( trailingslashit( $unpacked_path ) . $entries[0] ) ) {
			return trailingslashit( $unpacked_path ) . $entries[0];
		}

		return untrailingslashit( $unpacked_path );
	}

	/**
	 * Reads the WordPress and PHP requirements from the package's plugin or theme headers.
	 *
	 * @since 11.4.0
	 * @param string $source The directory that holds the plugin or theme files.
	 * @return array{RequiresWP: string, RequiresPHP: string}
	 */
	private function get_package_headers( string $source ): array {
		$fields = array(
			'Name'        => 'plugin' === $this->state->get_product_type() ? 'Plugin Name' : 'Theme Name',
			'RequiresWP'  => 'Requires at least',
			'RequiresPHP' => 'Requires PHP',
		);

		$files = 'plugin' === $this->state->get_product_type()
			? array_filter( (array) glob( trailingslashit( $source ) . '*.php' ), 'is_string' )
			: array( trailingslashit( $source ) . 'style.css' );

		foreach ( $files as $file ) {
			if ( ! is_string( $file ) || ! is_file( $file ) ) {
				continue;
			}

			$headers = get_file_data( $file, $fields );
			if ( '' !== $headers['Name'] ) {
				return array(
					'RequiresWP'  => $headers['RequiresWP'],
					'RequiresPHP' => $headers['RequiresPHP'],
				);
			}
		}

		return array(
			'RequiresWP'  => '',
			'RequiresPHP' => '',
		);
	}

	/**
	 * Removes the unpacked package, as install_package() would after installing it.
	 *
	 * @since 11.4.0
	 * @return void
	 */
	private function delete_unpacked_package(): void {
		global $wp_filesystem;

		$unpacked_path = (string) $this->state->get_unpacked_path();
		if ( '' !== $unpacked_path && $wp_filesystem instanceof WP_Filesystem_Base ) {
			$wp_filesystem->delete( $unpacked_path, true );
		}
	}

	/**
	 * Connect to wccom if installing a theme
	 *
	 * @return void
	 */
	protected function maybe_connect_theme() {
		if ( 'theme' !== $this->state->get_product_type() ) {
			return;
		}

		WC_Helper::connect_theme( $this->state->get_product_id() );
	}
}
