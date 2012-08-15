<?php
/**
 * ShareDaddy Integration
 *
 * Enables ShareDaddy integration.
 *
 * @class 		WC_ShareDaddy
 * @extends		WC_Integration
 * @version		1.6.4
 * @package		WooCommerce/Classes/Integrations
 * @author 		WooThemes
 */
class WC_ShareDaddy extends WC_Integration {

	/**
	 * Init and hook in the integration.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
        $this->id					= 'sharedaddy';
        $this->method_title     	= __( 'ShareDaddy', 'woocommerce' );
        $this->method_description	= __( 'ShareDaddy is a sharing plugin bundled with JetPack.', 'woocommerce' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->enabled 	= $this->settings['enabled'];

		// Actions
		add_action( 'woocommerce_update_options_integration_sharedaddy', array( &$this, 'process_admin_options') );

		// Share widget
		add_action( 'woocommerce_share', array( &$this, 'sharedaddy_code') );
    }


    /**
     * Initialise Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
				'title' 		=> __( 'Output ShareDaddy button?', 'woocommerce' ),
				'description' 	=> __( 'Enable this option to show the ShareDaddy button on the product page.', 'woocommerce' ),
				'type' 			=> 'checkbox',
				'default' 		=> get_option('woocommerce_sharedaddy') ? get_option('woocommerce_sharedaddy') : 'no'
			)
		);

    }


    /**
     * Output share code.
     *
     * @access public
     * @return void
     */
    function sharedaddy_code() {
    	global $post;

    	if ( $this->enabled == 'yes' && function_exists('sharing_display') ) {

    		?><div class="social"><?php echo sharing_display(); ?></div><?php

    	}
    }

}


/**
 * Add the integration to WooCommerce.
 *
 * @package		WooCommerce/Classes/Integrations
 * @access public
 * @param array $integrations
 * @return array
 */
function add_sharedaddy_integration( $integrations ) {
	if ( class_exists('jetpack') )
		$integrations[] = 'WC_ShareDaddy';
	return $integrations;
}

add_filter('woocommerce_integrations', 'add_sharedaddy_integration' );