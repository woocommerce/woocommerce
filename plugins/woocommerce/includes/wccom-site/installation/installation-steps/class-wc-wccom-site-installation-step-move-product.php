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
