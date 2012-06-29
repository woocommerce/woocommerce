<?php
/**
 * Cash on Delivery Gateway
 * 
 * Provides a Cash on Delivery Payment Gateway.
 *
 * @class 		WC_COD
 * @package		WooCommerce
 * @category	Payment Gateways
 * @author		Patrick Garman (www.patrickgarman.com)
 */
class WC_COD extends WC_Payment_Gateway {
	
	function __construct() {
		$this->id = 'cod';
		$this->method_title = __('Cash on Delivery', 'woocommerce');
		$this->has_fields 		= false;
		
		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Define user set variables
		$this->title = $this->settings['title'];
		$this->description = $this->settings['description'];
		$this->instructions = $this->settings['instructions'];
		
		add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
		add_action('woocommerce_thankyou_cod', array(&$this, 'thankyou'));
	}
	
	function admin_options() {
		?>
		<h3><?php _e('Cash on Delivery','woocommerce'); ?></h3>
    	<p><?php _e('Have your customers pay with cash (or by other means) upon delivery.', 'woocommerce' ); ?></p>
    	<table class="form-table">
    		<?php $this->generate_settings_html(); ?>
		</table> <?php
    }
	
    function init_form_fields() {
    	$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable COD', 'woocommerce' ), 
				'label' => __( 'Enable Cash on Delivery', 'woocommerce' ), 
				'type' => 'checkbox', 
				'description' => '', 
				'default' => 'no'
			), 
			'title' => array(
				'title' => __( 'Title', 'woocommerce' ), 
				'type' => 'text', 
				'description' => __( 'Payment method title that the customer will see on your website.', 'woocommerce' ), 
				'default' => __( 'Cash on Delivery', 'woocommerce' )
			), 
			'description' => array(
				'title' => __( 'Description', 'woocommerce' ), 
				'type' => 'textarea', 
				'description' => __( 'Payment method description that the customer will see on your website.', 'woocommerce' ), 
				'default' => 'Pay with cash upon delivery.'
			), 
			'instructions' => array(
				'title' => __( 'Instructions', 'woocommerce' ), 
				'type' => 'textarea', 
				'description' => __( 'Instructions that will be added to the thank you page.', 'woocommerce' ), 
				'default' => 'Pay with cash upon delivery.'
			), 
 	   );
    }
    
    // Process the payment
	function process_payment ($order_id) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status('on-hold', __('Payment to be made upon delivery.', 'woocommerce'));
		
		// Reduce stock levels
		$order->reduce_order_stock();
		
		// Remove cart
		$woocommerce->cart->empty_cart();
		
		// Empty awaiting payment session
		unset($_SESSION['order_awaiting_payment']);
			
		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks'))))
		);
	}
	
	function thankyou() {
		if ($this->instructions!='') { echo wpautop($this->instructions); }
	}
	
}
	
// Adding Gateway to WooCommerce Gateways
function woocommerce_cod_add_gateway ($methods) { $methods[] = 'WC_COD'; return $methods; }
add_filter ('woocommerce_payment_gateways', 'woocommerce_cod_add_gateway');