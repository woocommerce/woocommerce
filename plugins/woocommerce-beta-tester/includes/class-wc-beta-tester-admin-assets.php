<?php
/**
 * Beta Tester Admin Assets class
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Beta_Tester_Admin_Assets Class.
 */
class WC_Beta_Tester_Admin_Assets {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Enqueue scripts.
	 */
	public function admin_scripts() {
		$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		// TODO: Handle minification later.
		$suffix       = '';

		// TODO: Figure out why the modal appears only on WC screens. Need some other dependencies?
		wp_register_script( 'wc-beta-tester-version-info', WC_Beta_Tester::instance()->plugin_url() . '/assets/js/version-information' . $suffix . '.js', array( 'wc-backbone-modal' ), WC_BETA_TESTER_VERSION );

		// TODO: Get version programmatically.
		$version = '3.4.0';

		wp_localize_script(
			'wc-beta-tester-version-info',
			'wc_beta_tester_version_info_params',
			array(
				'version'     => $version,
				'description' => WC_Beta_Tester::instance()->get_version_information( $version ),
			)
		);

		wp_enqueue_script( 'wc-beta-tester-version-info' );
	}
}

return new WC_Beta_Tester_Admin_Assets();
