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

if ( ! class_exists( 'WC_Settings_General' ) ) :

/**
 * WC_Admin_Settings_General
 */
class WC_Settings_General extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'general';
		$this->label = __( 'General', 'woocommerce' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
		add_action( 'woocommerce_admin_field_frontend_styles', array( $this, 'frontend_styles_setting' ) );
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		$currency_code_options = get_woocommerce_currencies();

		foreach ( $currency_code_options as $code => $name )
			$currency_code_options[ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';

		return apply_filters( 'woocommerce_general_settings', array(

			array( 'title' => __( 'General Options', 'woocommerce' ), 'type' => 'title', 'desc' => '', 'id' => 'general_options' ),

			array(
				'title' 	=> __( 'Base Location', 'woocommerce' ),
				'desc' 		=> __( 'This is the base location for your business. Tax rates will be based on this country.', 'woocommerce' ),
				'id' 		=> 'woocommerce_default_country',
				'css' 		=> 'min-width:350px;',
				'default'	=> 'GB',
				'type' 		=> 'single_select_country',
				'desc_tip'	=>  true,
			),

			array(
				'title' 	=> __( 'Currency', 'woocommerce' ),
				'desc' 		=> __( "This controls what currency prices are listed at in the catalog and which currency gateways will take payments in.", 'woocommerce' ),
				'id' 		=> 'woocommerce_currency',
				'css' 		=> 'min-width:350px;',
				'default'	=> 'GBP',
				'type' 		=> 'select',
				'class'		=> 'chosen_select',
				'desc_tip'	=>  true,
				'options'   => $currency_code_options
			),

			array(
				'title' => __( 'Allowed Countries', 'woocommerce' ),
				'desc' 		=> __( 'These are countries that you are willing to ship to.', 'woocommerce' ),
				'id' 		=> 'woocommerce_allowed_countries',
				'default'	=> 'all',
				'type' 		=> 'select',
				'class'		=> 'chosen_select',
				'css' 		=> 'min-width: 350px;',
				'desc_tip'	=>  true,
				'options' => array(
					'all'  => __( 'All Countries', 'woocommerce' ),
					'specific' => __( 'Specific Countries', 'woocommerce' )
				)
			),

			array(
				'title' => __( 'Specific Countries', 'woocommerce' ),
				'desc' 		=> '',
				'id' 		=> 'woocommerce_specific_allowed_countries',
				'css' 		=> '',
				'default'	=> '',
				'type' 		=> 'multi_select_countries'
			),

			array(
				'title' => __( 'Store Notice', 'woocommerce' ),
				'desc' 		=> __( 'Enable site-wide store notice text', 'woocommerce' ),
				'id' 		=> 'woocommerce_demo_store',
				'default'	=> 'no',
				'type' 		=> 'checkbox'
			),

			array(
				'title' => __( 'Store Notice Text', 'woocommerce' ),
				'desc' 		=> '',
				'id' 		=> 'woocommerce_demo_store_notice',
				'default'	=> __( 'This is a demo store for testing purposes &mdash; no orders shall be fulfilled.', 'woocommerce' ),
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'autoload'  => false
			),

			array( 'type' => 'sectionend', 'id' => 'general_options'),

			array(	'title' => __( 'Cart, Checkout and Accounts', 'woocommerce' ), 'type' => 'title', 'id' => 'checkout_account_options' ),

			array(
				'title' => __( 'Coupons', 'woocommerce' ),
				'desc'          => __( 'Enable the use of coupons', 'woocommerce' ),
				'id'            => 'woocommerce_enable_coupons',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'desc_tip'		=>  __( 'Coupons can be applied from the cart and checkout pages.', 'woocommerce' ),
				'autoload'      => false
			),

			array(
				'title' => __( 'Checkout', 'woocommerce' ),
				'desc' 		=> __( 'Enable guest checkout', 'woocommerce' ),
				'desc_tip'	=>  __( 'Allows customers to checkout without creating an account.', 'woocommerce' ),
				'id' 		=> 'woocommerce_enable_guest_checkout',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup'	=> 'start',
				'autoload'  => false
			),

			array(
				'desc' 		=> __( 'Force secure checkout', 'woocommerce' ),
				'id' 		=> 'woocommerce_force_ssl_checkout',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> '',
				'show_if_checked' => 'option',
				'desc_tip'	=>  __( 'Force SSL (HTTPS) on the checkout pages (an SSL Certificate is required).', 'woocommerce' ),
			),

			array(
				'desc' 		=> __( 'Un-force HTTPS when leaving the checkout', 'woocommerce' ),
				'id' 		=> 'woocommerce_unforce_ssl_checkout',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> 'end',
				'show_if_checked' => 'yes',
			),

			array(
				'title' => __( 'Registration', 'woocommerce' ),
				'desc' 		=> __( 'Enable registration on the "Checkout" page', 'woocommerce' ),
				'id' 		=> 'woocommerce_enable_signup_and_login_from_checkout',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> 'start',
				'autoload'      => false
			),

			array(
				'desc' 		=> __( 'Enable registration on the "My Account" page', 'woocommerce' ),
				'id' 		=> 'woocommerce_enable_myaccount_registration',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> '',
				'autoload'      => false
			),

			array(
				'desc' 		=> __( 'Automatically generate username from customer email', 'woocommerce' ),
				'id' 		=> 'woocommerce_registration_generate_username',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> '',
				'autoload'      => false
			),

			array(
				'desc' 		=> __( 'Automatically generate customer password', 'woocommerce' ),
				'id' 		=> 'woocommerce_registration_generate_password',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> 'end',
				'autoload'      => false
			),

			array( 'type' => 'sectionend', 'id' => 'checkout_account_options'),

			array(	'title' => __( 'Styles and Scripts', 'woocommerce' ), 'type' => 'title', 'id' => 'script_styling_options' ),

			array(
				'type' 		=> 'frontend_styles'
			),

			array(
				'title' => __( 'Scripts', 'woocommerce' ),
				'desc' 	=> __( 'Enable Lightbox', 'woocommerce' ),
				'id' 		=> 'woocommerce_enable_lightbox',
				'default'	=> 'yes',
				'desc_tip'	=> __( 'Include WooCommerce\'s lightbox. Product gallery images and the add review form will open in a lightbox.', 'woocommerce' ),
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> 'start'
			),

			array(
				'desc' 		=> __( 'Enable enhanced country select boxes', 'woocommerce' ),
				'id' 		=> 'woocommerce_enable_chosen',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> 'end',
				'desc_tip'	=> __( 'This will enable a script allowing the country fields to be searchable.', 'woocommerce' ),
				'autoload'  => false
			),

			array( 'type' => 'sectionend', 'id' => 'script_styling_options'),

			array(	'title' => __( 'Downloadable Products', 'woocommerce' ), 'type' => 'title', 'id' => 'digital_download_options' ),

			array(
				'title' => __( 'File Download Method', 'woocommerce' ),
				'desc' 		=> __( 'Forcing downloads will keep URLs hidden, but some servers may serve large files unreliably. If supported, <code>X-Accel-Redirect</code>/ <code>X-Sendfile</code> can be used to serve downloads instead (server requires <code>mod_xsendfile</code>).', 'woocommerce' ),
				'id' 		=> 'woocommerce_file_download_method',
				'type' 		=> 'select',
				'class'		=> 'chosen_select',
				'css' 		=> 'min-width:300px;',
				'default'	=> 'force',
				'desc_tip'	=>  true,
				'options' => array(
					'force'  	=> __( 'Force Downloads', 'woocommerce' ),
					'xsendfile' => __( 'X-Accel-Redirect/X-Sendfile', 'woocommerce' ),
					'redirect'  => __( 'Redirect only', 'woocommerce' ),
				),
				'autoload'      => false
			),

			array(
				'title' => __( 'Access Restriction', 'woocommerce' ),
				'desc' 		=> __( 'Downloads require login', 'woocommerce' ),
				'id' 		=> 'woocommerce_downloads_require_login',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
				'desc_tip'	=> __( 'This setting does not apply to guest purchases.', 'woocommerce' ),
				'checkboxgroup'		=> 'start',
				'autoload'      => false
			),

			array(
				'desc' 		=> __( 'Grant access to downloadable products after payment', 'woocommerce' ),
				'id' 		=> 'woocommerce_downloads_grant_access_after_payment',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'desc_tip'	=> __( 'Enable this option to grant access to downloads when orders are "processing", rather than "completed".', 'woocommerce' ),
				'checkboxgroup'		=> 'end',
				'autoload'      => false
			),

			array( 'type' => 'sectionend', 'id' => 'digital_download_options' ),

		)); // End general settings
	}

	/**
	 * Output the frontend styles settings.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_styles_setting() {
		?><tr valign="top" class="woocommerce_frontend_css_colors">
			<th scope="row" class="titledesc">
				<?php _e( 'Styles', 'woocommerce' ); ?>
			</th>
		    <td class="forminp"><?php

				$base_file		= WC()->plugin_path() . '/assets/css/woocommerce-base.less';
				$css_file		= WC()->plugin_path() . '/assets/css/woocommerce.css';

				if ( is_writable( $base_file ) && is_writable( $css_file ) ) {

					// Get settings
					$colors = array_map( 'esc_attr', (array) get_option( 'woocommerce_frontend_css_colors' ) );

					// Defaults
					if ( empty( $colors['primary'] ) ) $colors['primary'] = '#ad74a2';
					if ( empty( $colors['secondary'] ) ) $colors['secondary'] = '#f7f6f7';
					if ( empty( $colors['highlight'] ) ) $colors['highlight'] = '#85ad74';
					if ( empty( $colors['content_bg'] ) ) $colors['content_bg'] = '#ffffff';
		            if ( empty( $colors['subtext'] ) ) $colors['subtext'] = '#777777';

					// Show inputs
		    		$this->color_picker( __( 'Primary', 'woocommerce' ), 'woocommerce_frontend_css_primary', $colors['primary'], __( 'Call to action buttons/price slider/layered nav UI', 'woocommerce' ) );
		    		$this->color_picker( __( 'Secondary', 'woocommerce' ), 'woocommerce_frontend_css_secondary', $colors['secondary'], __( 'Buttons and tabs', 'woocommerce' ) );
		    		$this->color_picker( __( 'Highlight', 'woocommerce' ), 'woocommerce_frontend_css_highlight', $colors['highlight'], __( 'Price labels and Sale Flashes', 'woocommerce' ) );
		    		$this->color_picker( __( 'Content', 'woocommerce' ), 'woocommerce_frontend_css_content_bg', $colors['content_bg'], __( 'Your themes page background - used for tab active states', 'woocommerce' ) );
		    		$this->color_picker( __( 'Subtext', 'woocommerce' ), 'woocommerce_frontend_css_subtext', $colors['subtext'], __( 'Used for certain text and asides - breadcrumbs, small text etc.', 'woocommerce' ) );

		    	} else {
		    		echo '<span class="description">' . __( 'To edit colours <code>woocommerce/assets/css/woocommerce-base.less</code> and <code>woocommerce.css</code> need to be writable. See <a href="http://codex.wordpress.org/Changing_File_Permissions">the Codex</a> for more information.', 'woocommerce' ) . '</span>';
		    	}

		    ?></td>
		</tr><?php
	}

	/**
	 * Output a colour picker input box.
	 *
	 * @access public
	 * @param mixed $name
	 * @param mixed $id
	 * @param mixed $value
	 * @param string $desc (default: '')
	 * @return void
	 */
	function color_picker( $name, $id, $value, $desc = '' ) {
		echo '<div class="color_box"><strong><img class="help_tip" data-tip="' . esc_attr( $desc ) . '" src="' . WC()->plugin_url() . '/assets/images/help.png" height="16" width="16" /> ' . esc_html( $name ) . '</strong>
	   		<input name="' . esc_attr( $id ). '" id="' . esc_attr( $id ) . '" type="text" value="' . esc_attr( $value ) . '" class="colorpick" /> <div id="colorPickerDiv_' . esc_attr( $id ) . '" class="colorpickdiv"></div>
	    </div>';
	}

	/**
	 * Save settings
	 */
	public function save() {
		$settings = $this->get_settings();

		WC_Admin_Settings::save_fields( $settings );

		if ( isset( $_POST['woocommerce_frontend_css_primary'] ) ) {

			// Save settings
			$primary 		= ( ! empty( $_POST['woocommerce_frontend_css_primary'] ) ) ? woocommerce_format_hex( $_POST['woocommerce_frontend_css_primary'] ) : '';
			$secondary 		= ( ! empty( $_POST['woocommerce_frontend_css_secondary'] ) ) ? woocommerce_format_hex( $_POST['woocommerce_frontend_css_secondary'] ) : '';
			$highlight 		= ( ! empty( $_POST['woocommerce_frontend_css_highlight'] ) ) ? woocommerce_format_hex( $_POST['woocommerce_frontend_css_highlight'] ) : '';
			$content_bg 	= ( ! empty( $_POST['woocommerce_frontend_css_content_bg'] ) ) ? woocommerce_format_hex( $_POST['woocommerce_frontend_css_content_bg'] ) : '';
			$subtext 		= ( ! empty( $_POST['woocommerce_frontend_css_subtext'] ) ) ? woocommerce_format_hex( $_POST['woocommerce_frontend_css_subtext'] ) : '';

			$colors = array(
				'primary' 		=> $primary,
				'secondary' 	=> $secondary,
				'highlight' 	=> $highlight,
				'content_bg' 	=> $content_bg,
				'subtext' 		=> $subtext
			);

			$old_colors = get_option( 'woocommerce_frontend_css_colors' );
			update_option( 'woocommerce_frontend_css_colors', $colors );

			if ( $old_colors != $colors )
				woocommerce_compile_less_styles();
		}
	}

}

endif;

return new WC_Settings_General();