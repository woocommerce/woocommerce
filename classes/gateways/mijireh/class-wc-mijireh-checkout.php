<?php
/**
 * Mijireh Checkout Gateway
 *
 * Provides WooCommerce with Mijireh Checkout integration.
 *
 * @class 		WC_Mijireh_Checkout
 * @extends		WC_Payment_Gateway
 * @version		1.6.4
 * @package		WooCommerce/Classes/Payment
 * @author 		Mijireh
 */
class WC_Mijireh_Checkout extends WC_Payment_Gateway {

	/** @var string Access key for mijireh */
	var $access_key;

    /**
     * Constructor for the gateway.
     *
     * @access public
     * @return void
     */
	public function __construct() {
		global $woocommerce;

		$this->id 			= 'mijireh_checkout';
		$this->method_title = __( 'Mijireh Checkout', 'woocommerce' );
		$this->icon 		= apply_filters( 'woocommerce_mijireh_checkout_icon', $woocommerce->plugin_url() . '/classes/gateways/mijireh/assets/images/credit_cards.png' );
		$this->has_fields = false;

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->access_key 	= $this->settings['access_key'];
		$this->title 		= $this->settings['title'];
		$this->description 	= $this->settings['description'];

		if ( $this->enabled && is_admin() ) {
			$this->install_slurp_page();

			// Hooks
			add_action( 'add_meta_boxes', array( &$this, 'add_page_slurp_meta' ) );
			add_action( 'wp_ajax_page_slurp', array( &$this, 'page_slurp' ) );
		}

		// Save options
		add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );

		// Payment listener/API hook
		add_action( 'woocommerce_api_wc_mijireh_checkout', array( &$this, 'mijireh_notification' ) );
	}

	/**
	 * init_mijireh function.
	 *
	 * @access public
	 */
	public function init_mijireh() {
		if ( ! class_exists( 'Mijireh' ) ) {
	    	require_once 'includes/Mijireh.php';

	    	Mijireh::$access_key = $this->access_key;
	    }
	}

	/**
	 * install_slurp_page function.
	 *
	 * @access public
	 */
	public function install_slurp_page() {
	    $slurp_page_installed = get_option( 'slurp_page_installed', false );
		if ( $slurp_page_installed != 1 ) {
			if( ! get_page_by_path( 'mijireh-secure-checkout' ) ) {
				$page = array(
					'post_title' 		=> 'Mijireh Secure Checkout',
					'post_name' 		=> 'mijireh-secure-checkout',
					'post_parent' 		=> 0,
					'post_status' 		=> 'private',
					'post_type' 		=> 'page',
					'comment_status' 	=> 'closed',
					'ping_status' 		=> 'closed',
					'post_content' 		=> "<h1>Checkout</h1>\n\n{{mj-checkout-form}}",
				);
				wp_insert_post( $page );
			}
			update_option( 'slurp_page_installed', 1 );
		}
    }

    /**
     * page_slurp function.
     *
     * @access public
     * @return void
     */
    public function page_slurp() {

    	$this->init_mijireh();

		$page 	= get_page( absint( $_POST['page_id'] ) );
		$url 	= get_permalink( $page->ID );
		wp_update_post( array( 'ID' => $page->ID, 'post_status' => 'publish' ) );
		$job_id = Mijireh::slurp( $url );
		wp_update_post( array( 'ID' => $page->ID, 'post_status' => 'private' ) );
		echo $job_id;
		die;
	}

	/**
	 * mijireh_notification function.
	 *
	 * @access public
	 * @return void
	 */
	public function mijireh_notification() {
	   global $woocommerce;

		$this->init_mijireh();

		try {
		      $mj_order 	= new Mijireh_Order( esc_attr( $_GET['order_number'] ) );
		      $wc_order_id 	= $mj_order->get_meta_value( 'wc_order_id' );
		      $wc_order 	= new WC_Order( absint( $wc_order_id ) );

		      // Mark order complete
		      $wc_order->payment_complete();

		      // Empty cart and clear session
		      $woocommerce->cart->empty_cart();

		      wp_redirect( $this->get_return_url( $wc_order ) );
		      exit;

		} catch (Mijireh_Exception $e) {

			$woocommerce->add_error( __( 'Mijireh error:', 'woocommerce' ) . $e->getMessage() );

		}
	}


    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'woocommerce' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Mijireh Checkout', 'woocommerce' ),
				'default' => 'no'
				),
			'access_key' => array(
				'title' => __('Access Key', 'woocommerce'),
				'type' => 'text',
				'description' => __('The Mijireh access key for your store.', 'woocommerce'),
				'default' => ''
				),
			'title' => array(
				'title' => __( 'Title', 'woocommerce' ),
				'type' => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default' => __( 'Credit Card', 'woocommerce' )
				),
			'description' => array(
				'title' => __( 'Description', 'woocommerce' ),
				'type' => 'textarea',
				'default' => __( 'Pay securely with you credit card.', 'woocommerce' ),
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
				),
		);
    }


	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @access public
	 * @return void
	 */
  	public function admin_options() {
		?>
		<h3><?php _e('Mijireh Checkout', 'woocommerce');?></h3>

		<?php if ( empty( $this->access_key ) ) : ?>
			<div id="wc_get_started" class="mijireh">
				<span class="main"><?php _e('Get started with Mijireh Checkout', 'woocommerce'); ?></span>
				<span><a href="http://www.mijireh.com/integrations/woocommerce/">Mijireh Checkout</a> <?php _e('provides a fully PCI Compliant, secure way to collect and transmit credit card data to your payment gateway while keeping you in control of the design of your site. Mijireh supports a wide variety of payment gateways: Stripe, Authorize.net, PayPal, eWay, SagePay, Braintree, PayLeap, and more.', 'woocommerce'); ?></span>

				<p><a href="http://secure.mijireh.com/signup" target="_blank" class="button button-primary"><?php _e('Join for free', 'woocommerce'); ?></a> <a href="http://www.mijireh.com/integrations/woocommerce/" target="_blank" class="button"><?php _e('Learn more about WooCommerce and Mijireh', 'woocommerce'); ?></a></p>

			</div>
		<?php else : ?>
			<p><a href="http://www.mijireh.com/integrations/woocommerce/">Mijireh Checkout</a> <?php _e('provides a fully PCI Compliant, secure way to collect and transmit credit card data to your payment gateway while keeping you in control of the design of your site.', 'woocommerce'); ?></p>
		<?php endif; ?>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table><!--/.form-table-->
		<?php
  	}


    /**
     * Process the payment and return the result
     *
     * @access public
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {
		global $woocommerce;

		$this->init_mijireh();

		$mj_order = new Mijireh_Order();
		$wc_order = new WC_Order( $order_id );

		// add items to order
		$items = $wc_order->get_items();
		foreach($items as $item) {
			$product = $wc_order->get_product_from_item( $item );
			$mj_order->add_item( $item['name'], $wc_order->get_item_subtotal($item), $item['qty'], $product->get_sku() );
		}

		// add billing address to order
		$billing 					= new Mijireh_Address();
		$billing->first_name 		= $wc_order->billing_first_name;
		$billing->last_name 		= $wc_order->billing_last_name;
		$billing->street 			= $wc_order->billing_address_1;
		$billing->apt_suite 		= $wc_order->billing_address_2;
		$billing->city 				= $wc_order->billing_city;
		$billing->state_province 	= $wc_order->billing_state;
		$billing->zip_code 			= $wc_order->billing_postcode;
		$billing->country 			= $wc_order->billing_country;
		$billing->company 			= $wc_order->billing_company;
		$billing->phone 			= $wc_order->billing_phone;
		if ( $billing->validate() )
			$mj_order->set_billing_address( $billing );

		// add shipping address to order
		$shipping 					= new Mijireh_Address();
		$shipping->first_name 		= $wc_order->shipping_first_name;
		$shipping->last_name 		= $wc_order->shipping_last_name;
		$shipping->street 			= $wc_order->shipping_address_1;
		$shipping->apt_suite 		= $wc_order->shipping_address_2;
		$shipping->city 			= $wc_order->shipping_city;
		$shipping->state_province 	= $wc_order->shipping_state;
		$shipping->zip_code 		= $wc_order->shipping_postcode;
		$shipping->country 			= $wc_order->shipping_country;
		$shipping->company 			= $wc_order->shipping_company;
		if ( $shipping->validate() )
			$mj_order->set_shipping_address( $shipping );

		// set order name
		$mj_order->first_name 		= $wc_order->billing_first_name;
		$mj_order->last_name 		= $wc_order->billing_last_name;
		$mj_order->email 			= $wc_order->billing_email;

		// set order totals
		$mj_order->total 			= $wc_order->get_order_total();
		$mj_order->tax 				= $wc_order->get_total_tax();
		$mj_order->discount 		= $wc_order->get_total_discount();
		$mj_order->shipping 		= $wc_order->get_shipping();

		// add meta data to identify woocommerce order
		$mj_order->add_meta_data( 'wc_order_id', $order_id );

		// Set URL for mijireh payment notificatoin - use WC API
		$mj_order->return_url 		= str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Mijireh_Checkout', home_url( '/' ) ) );

		// Identify woocommerce
		$mj_order->partner_id 		= 'woo';

		try {
			$mj_order->create();
			$result = array(
				'result' => 'success',
				'redirect' => $mj_order->checkout_url
			);
			return $result;
		} catch (Mijireh_Exception $e) {
			$woocommerce->add_error( __('Mijireh error:', 'woocommerce' ) . $e->getMessage() );
		}
    }


    /**
     * add_page_slurp_meta function.
     *
     * @access public
     * @return void
     */
    public function add_page_slurp_meta() {
    	global $woocommerce;

    	if ( $this->is_slurp_page() ) {
        	wp_enqueue_style( 'mijireh_css', $woocommerce->plugin_url() . '/classes/gateways/mijireh/assets/css/mijireh.css' );
        	wp_enqueue_script( 'pusher', 'https://d3dy5gmtp8yhk7.cloudfront.net/1.11/pusher.min.js', null, false, true );
        	wp_enqueue_script( 'page_slurp', $woocommerce->plugin_url() . '/classes/gateways/mijireh/assets/js/page_slurp.js', array('jquery'), false, true );

			add_meta_box(
				'slurp_meta_box', 		// $id
				'Mijireh Page Slurp', 	// $title
				array( &$this, 'draw_page_slurp_meta_box' ), // $callback
				'page', 	// $page
				'normal', 	// $context
				'high'		// $priority
			);
		}
    }


    /**
     * is_slurp_page function.
     *
     * @access public
     * @return void
     */
    public function is_slurp_page() {
		global $post;
		$is_slurp = false;
		if ( isset( $post ) && is_object( $post ) ) {
			$content = $post->post_content;
			if ( strpos( $content, '{{mj-checkout-form}}') !== false ) {
				$is_slurp = true;
			}
		}
		return $is_slurp;
    }


    /**
     * draw_page_slurp_meta_box function.
     *
     * @access public
     * @param mixed $post
     * @return void
     */
    public function draw_page_slurp_meta_box( $post ) {
    	global $woocommerce;

    	$this->init_mijireh();

		echo "<div id='mijireh_notice' class='mijireh-info alert-message info' data-alert='alert'>";
		echo    "<h2>Slurp your custom checkout page!</h2>";
		echo    "<p>Get the page designed just how you want and when you're ready, click the button below and slurp it right up.</p>";
		echo    "<div id='slurp_progress' class='meter progress progress-info progress-striped active' style='display: none;'><div id='slurp_progress_bar' class='bar' style='width: 20%;'>Slurping...</div></div>";
		echo    "<p><a href='#' id='page_slurp' rel=". $post->ID ." class='button-primary'>Slurp This Page!</a> ";
		echo    '<a class="nobold" href="' . Mijireh::preview_checkout_link() . '" id="view_slurp" target="_new">Preview Checkout Page</a></p>';
		echo  "</div>";
    }
}


/**
 * Add the gateway to WooCommerce
 *
 * @access public
 * @package		WooCommerce/Classes/Payment
 * @param array $methods
 * @return array
 */
function add_mijireh_gateway( $methods ) {
	$methods[] = 'WC_Mijireh_Checkout';
	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_mijireh_gateway' );