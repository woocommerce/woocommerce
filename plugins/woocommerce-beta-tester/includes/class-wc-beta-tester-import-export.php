<?php
/**
 * Beta Tester settings export class
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Beta_Tester_Settings_Export Main Class.
 */
class WC_Beta_Tester_Import_Export {
	/**
	 * @var string WordPress ajax hook
	 */
	protected const AJAX_HOOK = 'wc_beta_tester_export_settings';

	/**
	 * @var string WordPress nonce action
	 */
	protected const NONCE_ACTION = 'wc-beta-tester-import-settings';

	/**
	 * @var string WordPress import action
	 */
	protected const IMPORT_ACTION = 'wc-beta-tester-import';

	/**
	 * @var string WordPress import capability
	 */
	protected const IMPORT_CAP = 'install_plugins';

	/**
	 * @var string WordPress import file name
	 */
	protected const IMPORT_FILENAME = 'woocommerce-settings-json';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->add_hooks();
	}

	/**
	 * Hook into WordPress.
	 */
	public function add_hooks() {
		add_action( 'admin_menu', array( $this, 'add_to_menu' ), 55 );
		add_action( 'wp_ajax_' . static::AJAX_HOOK, array( $this, 'export_settings' ) );
	}
	/**
	 * Add options page to menu
	 */
	public function add_to_menu() {
		add_submenu_page( 'plugins.php', __( 'WooCommerce Tester Import/Export Settings', 'woocommerce-beta-tester' ), __( 'WC Import/Export Settings', 'woocommerce-beta-tester' ), static::IMPORT_CAP, 'wc-beta-tester-settings', array( $this, 'settings_page_html' ) );
	}

	/**
	 * Output settings HTML
	 */
	public function settings_page_html() {
		if ( ! current_user_can( static::IMPORT_CAP ) ) {
			return;
		}

		$settings = $this->maybe_import_settings();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=wc_beta_tester_export_settings' ) ) ?>" class="button-primary"><?php
				/* translators Export WooCommerce settings button text. */
				esc_html_e( 'Export WooCommerce Settings', 'woocommerce-beta-tester' );
			?></a>
			<hr />
			<form method="POST" enctype="multipart/form-data">
				<?php wp_nonce_field( static::NONCE_ACTION ); ?>
				<input type="hidden" name="action" value="<?php echo static::IMPORT_ACTION; ?>" />
				<button type="submit" class="button-primary"><?php
				/* translators Import WooCommerce settings button text. */
				esc_html_e( 'Import WooCommerce Settings', 'woocommerce-beta-tester' );
				?></button>
				<input type="file" name="<?php echo static::IMPORT_FILENAME; ?>" />
			</form>
		</div>
		<?php
		echo $settings;
	}

	/**
	 * Export settings in json format.
	 */
	public function export_settings() {
		$filename = sprintf( 'woocommerce-settings-%s.json', gmdate( 'Ymdgi' ) );
		wc_set_time_limit( 0 );
		wc_nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		echo wp_json_encode( $this->get_settings() );
		exit;
	}

	/**
	 * Import settings in json format if submitted.
	 */
	public function maybe_import_settings() {
		$return_string = '';
		if ( empty( $_POST ) || empty( $_POST['action'] ) || $_POST['action'] !== static::IMPORT_ACTION ) {
			return $return_string;
		}

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], static::NONCE_ACTION ) ) {
			return __( 'Invalid submission', 'woocommerce-beta-tester' );
		}

		if ( ! empty( $_FILES[ static::IMPORT_FILENAME ] ) ) {
			$tmp_file = $_FILES[ static::IMPORT_FILENAME ]['tmp_name'];
			if ( is_readable( $tmp_file ) ) {
				$maybe_json = file_get_contents( $tmp_file );
				$settings = json_decode( $maybe_json, true );
				if ( $settings !== null ) {
					foreach ( $this->get_setting_list() as $option_name ) {
						if ( ! isset( $settings[ $option_name ] ) ) {
							continue;
						}
						$setting = maybe_unserialize( $settings[ $option_name ] );
						update_option( $option_name, $setting );
					}
					// @todo: implement as notice
					$return_string = __( 'Settings Updated', 'woocommerce-beta-tester' );
				}
			}
		}
		return $return_string;
	}

	/**
	 * Get an array of the WooCommerce related settings.
	 */
	protected function get_settings() {
		$settings = array();
		if ( current_user_can( 'manage_woocommerce' ) ) {
			foreach ( $this->get_setting_list() as $option_name ) {
				$setting = get_option( $option_name );
				$settings[ $option_name ] = is_string( $setting ) ? $setting : serialize( $setting );
			}
		}
		return $settings;
	}

	/**
	 * Get the WooCommerce settings list keys.
	 */
	private function get_setting_list() {
		require_once( dirname(__FILE__ ) . '/wc-beta-tester-settings-list.php');
		return wc_beta_tester_setting_list();
	}
}
