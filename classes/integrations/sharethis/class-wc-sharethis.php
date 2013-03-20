<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * ShareThis Integration
 *
 * Enables ShareThis integration.
 *
 * @class 		WC_ShareThis
 * @extends		WC_Integration
 * @version		1.6.4
 * @package		WooCommerce/Classes/Integrations
 * @author 		WooThemes
 */
class WC_ShareThis extends WC_Integration {

	/** @var string Default code for share this */
	var $default_code;

	/**
	 * Init and hook in the integration.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
        $this->id					= 'sharethis';
        $this->method_title     	= __( 'ShareThis', 'woocommerce' );
        $this->method_description	= __( 'ShareThis offers a sharing widget which will allow customers to share links to products with their friends.', 'woocommerce' );

		$this->default_code = '<div class="social">
	<iframe src="https://www.facebook.com/plugins/like.php?href={permalink}&amp;layout=button_count&amp;show_faces=false&amp;width=100&amp;action=like&amp;colorscheme=light&amp;height=21" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:100px; height:21px;" allowTransparency="true"></iframe>
	<span class="st_twitter"></span><span class="st_email"></span><span class="st_sharethis" st_image="{image}"></span><span class="st_plusone_button"></span>
</div>';

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->publisher_id 	= $this->get_option( 'publisher_id' );
		$this->sharethis_code 	= $this->get_option( 'sharethis_code', $this->default_code );

		// Actions
		add_action( 'woocommerce_update_options_integration_sharethis', array( $this, 'process_admin_options' ) );

		// Share widget
		add_action( 'woocommerce_share', array( $this, 'sharethis_code' ) );
    }


    /**
     * Initialise Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {

    	$this->form_fields = array(
			'publisher_id' => array(
				'title' 		=> __( 'ShareThis Publisher ID', 'woocommerce' ),
				'description' 	=> sprintf( __( 'Enter your %1$sShareThis publisher ID%2$s to show social sharing buttons on product pages.', 'woocommerce' ), '<a href="http://sharethis.com/account/">', '</a>' ),
				'type' 			=> 'text',
				'default' 		=> get_option('woocommerce_sharethis')
			),
			'sharethis_code' => array(
				'title' 		=> __( 'ShareThis Code', 'woocommerce' ),
				'description' 	=> __( 'You can tweak the ShareThis code by editing this option.', 'woocommerce' ),
				'type' 			=> 'textarea',
				'default' 		=> $this->default_code
			)
		);

    }


    /**
     * Output share code.
     *
     * @access public
     * @return void
     */
    function sharethis_code() {
    	global $post;

    	if ( $this->publisher_id ) {

    		$thumbnail = ( $thumbnail_id = get_post_thumbnail_id( $post->ID ) ) ? current(wp_get_attachment_image_src( $thumbnail_id, 'large' )) : '';

    		$sharethis = ( is_ssl() ) ? 'https://ws.sharethis.com/button/buttons.js' : 'http://w.sharethis.com/button/buttons.js';

    		$sharethis_code = str_replace( '{permalink}', urlencode( get_permalink( $post->ID ) ), $this->sharethis_code );
    		if ( isset( $thumbnail ) ) $sharethis_code = str_replace( '{image}', urlencode( $thumbnail ), $sharethis_code );

    		echo $sharethis_code;

    		echo '<script type="text/javascript">var switchTo5x=true;</script><script type="text/javascript" src="' . $sharethis . '"></script>';
			echo '<script type="text/javascript">stLight.options({publisher:"' . $this->publisher_id . '"});</script>';

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
function add_sharethis_integration( $integrations ) {
	$integrations[] = 'WC_ShareThis';
	return $integrations;
}

add_filter('woocommerce_integrations', 'add_sharethis_integration' );