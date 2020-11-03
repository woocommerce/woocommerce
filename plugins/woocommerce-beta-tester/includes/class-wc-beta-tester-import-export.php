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
		add_submenu_page( 'woocommerce', __( 'WooCommerce Settings Import/Export', 'woocommerce-beta-tester' ), __( 'Settings Import/Export', 'woocommerce-beta-tester' ), 'manage_woocommerce', 'wc-beta-tester-import-export', array( $this, 'settings_page_html' ) );
	}

	/**
	 * Output settings HTML
	 */
	public function settings_page_html() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=wc_beta_tester_export_settings' ) ) ?>" class="button-primary"><?php
				/* translators Download WooCommerce settings button text. */
				esc_html_e( 'Download WooCommerce Settings', 'woocommerce-beta-tester' );
			?></a>
			</form>
		</div>
		<?php
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
	 * Get an array of the WooCommerce related settings.
	 */
	protected function get_settings() {
		$settings = array();
		if ( current_user_can( 'manage_woocommerce' ) ) {
			require_once(dirname(__FILE__) . '/wc-beta-tester-settings-list.php');
			foreach (wp_beta_tester_setting_list() as $option_name) {
				$settings[$option_name] = get_option($option_name);
			}
		}
		return $settings;
	}
}
