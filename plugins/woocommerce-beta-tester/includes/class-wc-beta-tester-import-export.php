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
	 * @var array Import status message
	 */
	protected $message;

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
		add_submenu_page( 'plugins.php', __( 'WC Beta Tester Import/Export', 'woocommerce-beta-tester' ), __( 'WC Import/Export', 'woocommerce-beta-tester' ), static::IMPORT_CAP, 'wc-beta-tester-settings', array( $this, 'settings_page_html' ) );
	}

	/**
	 * Output settings HTML
	 */
	public function settings_page_html() {
		if ( ! current_user_can( static::IMPORT_CAP ) ) {
			return;
		}

		$export_url = wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_beta_tester_export_settings' ), static::NONCE_ACTION );
		$this->maybe_import_settings();

		// show error/update messages.
		if ( ! empty( $this->message ) ) {
			?>
			<div class="notice <?php
				echo ! empty( $this->message['type'] ) ? esc_attr( $this->message['type'] ) : '';
				?>"><?php echo esc_html( $this->message['message'] ); ?></div>
			<?php
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( 'Export your WooCommerce Settings. The export file should not contain any fields that identify your site or reveal secrets (eg. API keys).', 'woocommerce-beta-tester' ); ?></p>
			<a href="<?php echo esc_url( $export_url ) ?>" class="button-primary"><?php
				/* translators Export WooCommerce settings button text. */
				esc_html_e( 'Export WooCommerce Settings', 'woocommerce-beta-tester' );
			?></a>
			<hr />
			<form method="POST" enctype="multipart/form-data">
				<?php wp_nonce_field( static::NONCE_ACTION ); ?>
				<input type="hidden" name="action" value="<?php echo static::IMPORT_ACTION; ?>" />
				<p><?php esc_html_e( 'Import WooCommerce Settings exported with this tool. Some settings like store address, payment gateways, etc. will need to be configured manually.', 'woocommerce-beta-tester' ); ?></p>
				<button type="submit" class="button-primary"><?php
				/* translators Import WooCommerce settings button text. */
				esc_html_e( 'Import WooCommerce Settings', 'woocommerce-beta-tester' );
				?></button>
				<input type="file" name="<?php echo static::IMPORT_FILENAME; ?>" />
			</form>
		</div>
		<?php
	}

	/**
	 * Export settings in json format.
	 */
	public function export_settings() {
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], static::NONCE_ACTION ) ) {
			header( 'HTTP/1.1 403 Forbidden' );
			exit;
		}

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
		if ( empty( $_POST ) || empty( $_POST['action'] ) || $_POST['action'] !== static::IMPORT_ACTION ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], static::NONCE_ACTION ) ) {
			$this->add_message( __( 'Invalid submission', 'woocommerce-beta-tester' ) );
			return;
		}

		if ( empty( $_FILES[ static::IMPORT_FILENAME ] ) ) {
			$this->add_message( __( 'No file uploaded.', 'woocommerce-beta-tester' ) );
			return;
		}

		$tmp_file = $_FILES[ static::IMPORT_FILENAME ]['tmp_name'];
		if ( empty( $tmp_file ) ) {
			$this->add_message( __( 'No file uploaded.', 'woocommerce-beta-tester' ) );
			return;
		}

		if ( ! is_readable( $tmp_file ) ) {
			$this->add_message( __( 'File could not be read.', 'woocommerce-beta-tester' ) );
			return;
		}

		$maybe_json = file_get_contents( $tmp_file );
		$settings = json_decode( $maybe_json, true );
		if ( $settings !== null ) {
			foreach ( $this->get_setting_list() as $option_name ) {
				if ( ! isset( $settings[ $option_name ] ) ) {
					continue;
				}
				$setting = maybe_unserialize( $settings[ $option_name ] );
				if ( is_null( $setting ) ) {
					delete_option( $option_name );
				} else {
					update_option( $option_name, $setting );
				}
			}
			$this->add_message( __( 'Settings Imported', 'woocommerce-beta-tester' ), 'updated' );
		} else {
			$this->add_message( __( 'File did not contain well formed JSON.', 'woocommerce-beta-tester' ) );
		}
	}

	/**
	 * Get an array of the WooCommerce related settings.
	 */
	protected function get_settings() {
		$settings = array();
		if ( current_user_can( 'manage_woocommerce' ) ) {
			foreach ( $this->get_setting_list() as $option_name ) {
				$setting = get_option( $option_name );
				if ( false === $setting ) {
					$setting = null;
				}
				$settings[ $option_name ] = is_string( $setting ) ? $setting : serialize( $setting );
			}
		}
		return $settings;
	}

	/**
	 * Add a settings import status message.
	 *
	 * @param string $message Message string.
	 * @param string $type Message type. Optional. Default 'error'.
	 */
	protected function add_message( $message, $type = 'error' ) {
		$this->message = array(
			'message' => $message,
			'type'    => $type
		);
	}

	/**
	 * Get the WooCommerce settings list keys.
	 */
	private function get_setting_list() {
		require_once( dirname(__FILE__ ) . '/wc-beta-tester-settings-list.php');
		return wc_beta_tester_setting_list();
	}
}
