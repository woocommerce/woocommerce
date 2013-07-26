<?php
/**
 * WooCommerce Inventory Settings
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Settings_Inventory' ) ) :

/**
 * WC_Settings_Inventory
 */
class WC_Settings_Inventory extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'inventory';
		$this->label = __( 'Inventory', 'woocommerce' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		return apply_filters('woocommerce_inventory_settings', array(

			array(	'title' => __( 'Inventory Options', 'woocommerce' ), 'type' => 'title', 'desc' => '', 'id' => 'inventory_options' ),

			array(
				'title' => __( 'Manage Stock', 'woocommerce' ),
				'desc' 		=> __( 'Enable stock management', 'woocommerce' ),
				'id' 		=> 'woocommerce_manage_stock',
				'default'	=> 'yes',
				'type' 		=> 'checkbox'
			),

			array(
				'title' => __( 'Hold Stock (minutes)', 'woocommerce' ),
				'desc' 		=> __( 'Hold stock (for unpaid orders) for x minutes. When this limit is reached, the pending order will be cancelled. Leave blank to disable.', 'woocommerce' ),
				'id' 		=> 'woocommerce_hold_stock_minutes',
				'type' 		=> 'number',
				'custom_attributes' => array(
					'min' 	=> 0,
					'step' 	=> 1
				),
				'css' 		=> 'width:50px;',
				'default'	=> '60',
				'autoload'  => false
			),

			array(
				'title' => __( 'Notifications', 'woocommerce' ),
				'desc' 		=> __( 'Enable low stock notifications', 'woocommerce' ),
				'id' 		=> 'woocommerce_notify_low_stock',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup' => 'start',
				'autoload'      => false
			),

			array(
				'desc' 		=> __( 'Enable out of stock notifications', 'woocommerce' ),
				'id' 		=> 'woocommerce_notify_no_stock',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup' => 'end',
				'autoload'      => false
			),

			array(
				'title' => __( 'Notification Recipient', 'woocommerce' ),
				'desc' 		=> '',
				'id' 		=> 'woocommerce_stock_email_recipient',
				'type' 		=> 'email',
				'default'	=> get_option( 'admin_email' ),
				'autoload'      => false
			),

			array(
				'title' => __( 'Low Stock Threshold', 'woocommerce' ),
				'desc' 		=> '',
				'id' 		=> 'woocommerce_notify_low_stock_amount',
				'css' 		=> 'width:50px;',
				'type' 		=> 'number',
				'custom_attributes' => array(
					'min' 	=> 0,
					'step' 	=> 1
				),
				'default'	=> '2',
				'autoload'      => false
			),

			array(
				'title' => __( 'Out Of Stock Threshold', 'woocommerce' ),
				'desc' 		=> '',
				'id' 		=> 'woocommerce_notify_no_stock_amount',
				'css' 		=> 'width:50px;',
				'type' 		=> 'number',
				'custom_attributes' => array(
					'min' 	=> 0,
					'step' 	=> 1
				),
				'default'	=> '0',
				'autoload'      => false
			),

			array(
				'title' => __( 'Out Of Stock Visibility', 'woocommerce' ),
				'desc' 		=> __( 'Hide out of stock items from the catalog', 'woocommerce' ),
				'id' 		=> 'woocommerce_hide_out_of_stock_items',
				'default'	=> 'no',
				'type' 		=> 'checkbox'
			),

			array(
				'title' => __( 'Stock Display Format', 'woocommerce' ),
				'desc' 		=> __( 'This controls how stock is displayed on the frontend.', 'woocommerce' ),
				'id' 		=> 'woocommerce_stock_format',
				'css' 		=> 'min-width:150px;',
				'default'	=> '',
				'type' 		=> 'select',
				'options' => array(
					''  			=> __( 'Always show stock e.g. "12 in stock"', 'woocommerce' ),
					'low_amount'	=> __( 'Only show stock when low e.g. "Only 2 left in stock" vs. "In Stock"', 'woocommerce' ),
					'no_amount' 	=> __( 'Never show stock amount', 'woocommerce' ),
				),
				'desc_tip'	=>  true,
			),

			array( 'type' => 'sectionend', 'id' => 'inventory_options'),

		)); // End inventory settings
	}
}

endif;

return new WC_Settings_Inventory();