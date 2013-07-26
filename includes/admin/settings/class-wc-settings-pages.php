<?php
/**
 * WooCommerce General Settings
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Settings_Pages' ) ) :

/**
 * WC_Settings_Pages
 */
class WC_Settings_Pages extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'pages';
		$this->label = __( 'Pages', 'woocommerce' );

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
		// Get shop page
		$shop_page_id = woocommerce_get_page_id('shop');

		$base_slug = ($shop_page_id > 0 && get_page( $shop_page_id )) ? get_page_uri( $shop_page_id ) : 'shop';

		$woocommerce_prepend_shop_page_to_products_warning = '';

		if ( $shop_page_id > 0 && sizeof(get_pages("child_of=$shop_page_id")) > 0 )
			$woocommerce_prepend_shop_page_to_products_warning = ' <mark class="notice">' . __( 'Note: The shop page has children - child pages will not work if you enable this option.', 'woocommerce' ) . '</mark>';

		return apply_filters('woocommerce_page_settings', array(

			array(
				'title' => __( 'Page Setup', 'woocommerce' ),
				'type' => 'title',
				'desc' => sprintf( __( 'Set up core WooCommerce pages here, for example the base page. The base page can also be used in your %sproduct permalinks%s.', 'woocommerce' ), '<a target="_blank" href="' . admin_url( 'options-permalink.php' ) . '">', '</a>' ),
				'id' => 'page_options'
			),

			array(
				'title' => __( 'Shop Base Page', 'woocommerce' ),
				'desc' 		=> __( 'This sets the base page of your shop - this is where your product archive will be.', 'woocommerce' ),
				'id' 		=> 'woocommerce_shop_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true
			),

			array(
				'title' => __( 'Terms Page', 'woocommerce' ),
				'desc' 		=> __( 'If you define a "Terms" page the customer will be asked if they accept them when checking out.', 'woocommerce' ),
				'id' 		=> 'woocommerce_terms_page_id',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'type' 		=> 'single_select_page',
				'desc_tip'	=> true,
				'autoload'  => false
			),

			array( 'type' => 'sectionend', 'id' => 'page_options' ),

			array( 'title' => __( 'Shop Pages', 'woocommerce' ), 'type' => 'title', 'desc' => __( 'The following pages need selecting so that WooCommerce knows where they are. These pages should have been created upon installation of the plugin, if not you will need to create them.', 'woocommerce' ), 'id' => 'shop_page_options' ),

			array(
				'title' => __( 'Cart Page', 'woocommerce' ),
				'desc' 		=> __( 'Page contents: [woocommerce_cart]', 'woocommerce' ),
				'id' 		=> 'woocommerce_cart_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array(
				'title' => __( 'Checkout Page', 'woocommerce' ),
				'desc' 		=> __( 'Page contents: [woocommerce_checkout]', 'woocommerce' ),
				'id' 		=> 'woocommerce_checkout_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array(
				'title' => __( 'My Account Page', 'woocommerce' ),
				'desc' 		=> __( 'Page contents: [woocommerce_my_account]', 'woocommerce' ),
				'id' 		=> 'woocommerce_myaccount_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array(
				'title' => __( 'Logout Page', 'woocommerce' ),
				'desc' 		=> __( 'Parent: "My Account"', 'woocommerce' ),
				'id' 		=> 'woocommerce_logout_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array( 'type' => 'sectionend', 'id' => 'shop_page_options'),

			array( 'title' => __( 'Endpoints', 'woocommerce' ), 'type' => 'title', 'desc' => __( 'The following endpoints are used on the frontend for certain pages.', 'woocommerce' ), 'id' => 'endpoint_options' ),

			array(
				'title' => __( 'Checkout &rarr; Pay', 'woocommerce' ),
				'desc' 		=> __( 'Endpoint for the Checkout &rarr; Pay page', 'woocommerce' ),
				'id' 		=> 'woocommerce_checkout_pay_endpoint',
				'type' 		=> 'text',
				'default'	=> 'order-pay',
				'desc_tip'	=> true,
			),

			array(
				'title' => __( 'Checkout &rarr; Order Received', 'woocommerce' ),
				'desc' 		=> __( 'Endpoint for the Checkout &rarr; Pay page', 'woocommerce' ),
				'id' 		=> 'woocommerce_checkout_order_received_endpoint',
				'type' 		=> 'text',
				'default'	=> 'order-received',
				'desc_tip'	=> true,
			),

			array(
				'title' => __( 'My Account &rarr; View Order', 'woocommerce' ),
				'desc' 		=> __( 'Endpoint for the My Account &rarr; View Order page', 'woocommerce' ),
				'id' 		=> 'woocommerce_myaccount_view_order_endpoint',
				'type' 		=> 'text',
				'default'	=> 'view-order',
				'desc_tip'	=> true,
			),

			array(
				'title' => __( 'My Account &rarr; Edit Account', 'woocommerce' ),
				'desc' 		=> __( 'Endpoint for the My Account &rarr; Edit Account page', 'woocommerce' ),
				'id' 		=> 'woocommerce_myaccount_edit_account_endpoint',
				'type' 		=> 'text',
				'default'	=> 'edit-account',
				'desc_tip'	=> true,
			),

			array(
				'title' => __( 'My Account &rarr; Edit Address', 'woocommerce' ),
				'desc' 		=> __( 'Endpoint for the My Account &rarr; Edit Address page', 'woocommerce' ),
				'id' 		=> 'woocommerce_myaccount_edit_address_endpoint',
				'type' 		=> 'text',
				'default'	=> 'edit-address',
				'desc_tip'	=> true,
			),

			array(
				'title' => __( 'My Account &rarr; Lost Password', 'woocommerce' ),
				'desc' 		=> __( 'Endpoint for the My Account &rarr; Lost Password page', 'woocommerce' ),
				'id' 		=> 'woocommerce_myaccount_lost_password_endpoint',
				'type' 		=> 'text',
				'default'	=> 'lost-password',
				'desc_tip'	=> true,
			),

			array( 'type' => 'sectionend', 'id' => 'endpoint_options'),

		)); // End pages settings
	}
}

endif;

return new WC_Settings_Pages();