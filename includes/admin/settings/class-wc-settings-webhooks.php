<?php
/**
 * WooCommerce Webhooks Settings
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Settings_Webhooks' ) ) :

/**
 * WC_Settings_Webhooks
 */
class WC_Settings_Webhooks extends WC_Settings_Page {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id    = 'webhooks';
		$this->label = __( 'Webhooks', 'woocommerce' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_form_method_tab_' . $this->id, array( $this, 'form_method' ) );

		$this->notices();
	}

	/**
	 * Form method
	 *
	 * @param  string $method
	 *
	 * @return string
	 */
	public function form_method( $method ) {
		return 'get';
	}

	/**
	 * Notices.
	 */
	protected function notices() {
		if ( isset( $_GET['trashed'] ) ) {
			$trashed = absint( $_GET['trashed'] );

			WC_Admin_Settings::add_message( sprintf( _n( '1 webhook moved to the Trash.', '%d webhooks moved to the Trash.', $trashed, 'woocommerce' ), $trashed ) );
		}

		if ( isset( $_GET['untrashed'] ) ) {
			$untrashed = absint( $_GET['untrashed'] );

			WC_Admin_Settings::add_message( sprintf( _n( '1 webhook restored from the Trash.', '%d webhooks restored from the Trash.', $untrashed, 'woocommerce' ), $untrashed ) );
		}

		if ( isset( $_GET['deleted'] ) ) {
			$deleted = absint( $_GET['deleted'] );

			WC_Admin_Settings::add_message( sprintf( _n( '1 webhook permanently deleted.', '%d webhooks permanently deleted.', $deleted, 'woocommerce' ), $deleted ) );
		}
	}

	/**
	 * Table list output
	 */
	protected function table_list_output() {
		$webhooks_table_list = new WC_Admin_Webhooks_Table_List();
		$webhooks_table_list->prepare_items();

		echo '<input type="hidden" name="page" value="wc-settings" />';
		echo '<input type="hidden" name="tab" value="webhooks" />';

		$webhooks_table_list->views();
		$webhooks_table_list->display();
	}

	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;

		// Hide the save button
		$GLOBALS['hide_save_button'] = true;

		// Display the table list.
		$this->table_list_output();
	}
}

endif;

return new WC_Settings_Webhooks();
