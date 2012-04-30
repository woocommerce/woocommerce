<?php
/**
 * WooCommerce Integration class
 * 
 * Extended by individual integrations to offer additional functionality.
 *
 * @class 		WC_Integration
 * @package		WooCommerce
 * @category	Integrations
 * @author		WooThemes
 */
class WC_Integration extends WC_Settings_API {
	
	/**
	 * Admin Options
	 *
	 * Setup the gateway settings screen.
	 * Override this in your gateway.
	 *
	 * @since 1.0.0
	 */
	function admin_options() { ?>
		
		<h3><?php echo isset( $this->method_title ) ? $this->method_title : __( 'Settings', 'woocommerce' ) ; ?></h3>
		
		<?php echo isset( $this->method_description ) ? wpautop( $this->method_description ) : ''; ?>
		
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>
		
		<!-- Section -->
		<div><input type="hidden" name="section" value="<?php echo $this->id; ?>" /></div>
		
		<?php
	}

}