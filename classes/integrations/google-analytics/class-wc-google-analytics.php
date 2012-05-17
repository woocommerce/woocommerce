<?php
/**
 * Google Analytics Integration
 * 
 * Allows tracking code to be inserted into store pages.
 *
 * @class 		WC_Google_Analytics
 * @package		WooCommerce
 * @category	Integrations
 * @author		WooThemes
 */
class WC_Google_Analytics extends WC_Integration {
		
	public function __construct() { 
        $this->id					= 'google_analytics';
        $this->method_title     	= __( 'Google Analytics', 'woocommerce' );
        $this->method_description	= __( 'Google Analytics is a free service offered by Google that generates detailed statistics about the visitors to a website.', 'woocommerce' );
		
		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->ga_id 							= $this->settings['ga_id'];
		$this->ga_standard_tracking_enabled 	= $this->settings['ga_standard_tracking_enabled'];
		$this->ga_ecommerce_tracking_enabled 	= $this->settings['ga_ecommerce_tracking_enabled'];
		
		// Actions
		add_action( 'woocommerce_update_options_integration_google_analytics', array( &$this, 'process_admin_options') );
		
		// Tracking code
		add_action( 'wp_footer', array( &$this, 'google_tracking_code' ) );
		add_action( 'woocommerce_thankyou', array( &$this, 'ecommerce_tracking_code' ) );
    } 
    
	/**
     * Initialise Settings Form Fields
     */
    function init_form_fields() {
    
    	$this->form_fields = array( 
			'ga_id' => array(  
				'title' 			=> __('Google Analytics ID', 'woocommerce'),
				'description' 		=> __('Log into your google analytics account to find your ID. e.g. <code>UA-XXXXX-X</code>', 'woocommerce'),
				'type' 				=> 'text',
		    	'default' 			=> get_option('woocommerce_ga_id') // Backwards compat
			),
			'ga_standard_tracking_enabled' => array(  
				'title' 			=> __('Tracking code', 'woocommerce'),
				'label' 			=> __('Add tracking code to your site\'s footer. You don\'t need to enable this if using a 3rd party analytics plugin.', 'woocommerce'),
				'type' 				=> 'checkbox',
				'checkboxgroup'		=> 'start',
				'default' 			=> get_option('woocommerce_ga_standard_tracking_enabled') ? get_option('woocommerce_ga_standard_tracking_enabled') : 'no'  // Backwards compat
			),
			'ga_ecommerce_tracking_enabled' => array(
				'label' 			=> __('Add eCommerce tracking code to the thankyou page', 'woocommerce'),
				'type' 				=> 'checkbox',
				'checkboxgroup'		=> 'end',
				'default' 			=> get_option('woocommerce_ga_ecommerce_tracking_enabled') ? get_option('woocommerce_ga_ecommerce_tracking_enabled') : 'no'  // Backwards compat
			)
		);
		
    } // End init_form_fields()
    
	/**
	 * Google Analytics standard tracking
	 **/
	function google_tracking_code() {
		global $woocommerce;
		
		if ( is_admin() || current_user_can('manage_options') || $this->ga_standard_tracking_enabled == "no" ) return;
		
		$tracking_id = $this->ga_id;
		
		if ( ! $tracking_id ) return;
		
		$loggedin 	= ( is_user_logged_in() ) ? 'yes' : 'no';
		if ( is_user_logged_in() ) {
			$user_id 		= get_current_user_id();
			$current_user 	= get_user_by('id', $user_id);
			$username 		= $current_user->user_login;
		} else {
			$user_id 		= '';
			$username 		= __('Guest', 'woocommerce');
		}
		?>
		<script type="text/javascript">
		
			var _gaq = _gaq || [];
			_gaq.push(
				['_setAccount', '<?php echo $tracking_id; ?>'],
				['_setCustomVar', 1, 'logged-in', '<?php echo $loggedin; ?>', 1],
				['_setCustomVar', 2, 'user-id', '<?php echo $user_id; ?>', 1],
				['_setCustomVar', 3, 'username', '<?php echo $username; ?>', 1],
				['_trackPageview']
			);
			
			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();
	
		</script>
		<?php
	}
	
	/**
	 * Google Analytics eCommerce tracking
	 **/
	function ecommerce_tracking_code( $order_id ) {
		global $woocommerce;
		
		if ( is_admin() || current_user_can('manage_options') || $this->ga_ecommerce_tracking_enabled == "no" ) return;
		
		$tracking_id = $this->ga_id;
		
		if ( ! $tracking_id ) return;
		
		// Doing eCommerce tracking so unhook standard tracking from the footer
		remove_action('wp_footer', array( &$this, 'google_tracking_code' ) );
		
		// Get the order and output tracking code
		$order = new WC_Order($order_id);
		
		$loggedin 	= (is_user_logged_in()) ? 'yes' : 'no';
		if (is_user_logged_in()) {
			$user_id 		= get_current_user_id();
			$current_user 	= get_user_by('id', $user_id);
			$username 		= $current_user->user_login;
		} else {
			$user_id 		= '';
			$username 		= __('Guest', 'woocommerce');
		}
		?>
		<script type="text/javascript">
			var _gaq = _gaq || [];
			
			_gaq.push(
				['_setAccount', '<?php echo $tracking_id; ?>'],
				['_setCustomVar', 1, 'logged-in', '<?php echo $loggedin; ?>', 1],
				['_setCustomVar', 2, 'user-id', '<?php echo $user_id; ?>', 1],
				['_setCustomVar', 3, 'username', '<?php echo $username; ?>', 1],
				['_trackPageview']
			);
			
			_gaq.push(['_addTrans',
				'<?php echo $order_id; ?>',           		// order ID - required
				'<?php bloginfo('name'); ?>',  				// affiliation or store name
				'<?php echo $order->order_total; ?>',   	// total - required
				'<?php echo $order->get_total_tax(); ?>',   // tax
				'<?php echo $order->get_shipping(); ?>',	// shipping
				'<?php echo $order->billing_city; ?>',      // city
				'<?php echo $order->billing_state; ?>',     // state or province
				'<?php echo $order->billing_country; ?>'    // country
			]);
			
			// Order items
			<?php if ($order->get_items()) foreach($order->get_items() as $item) : $_product = $order->get_product_from_item( $item ); ?>
				_gaq.push(['_addItem',
					'<?php echo $order_id; ?>',           	// order ID - required
					'<?php if (!empty($_product->sku)) 
								echo __('SKU:', 'woocommerce') . ' ' . $_product->sku; 
						   else 
								echo $_product->id;
					?>', // SKU/code - required
					'<?php echo $item['name']; ?>',        	// product name
					'<?php if (isset($_product->variation_data)){
								echo woocommerce_get_formatted_variation( $_product->variation_data, true ); 
						   } else {
								$out = array();
								$categories = get_the_terms($_product->id, 'product_cat');
								if ( $categories ) {
									foreach ( $categories as $category ){
										$out[] = $category->name;
									}
								}
								echo join( "/", $out);
						   }
					 ?>',   // category or variation
					'<?php echo ($item['line_total']/$item['qty']); ?>',         // unit price - required
					'<?php echo $item['qty']; ?>'           // quantity - required
				]);
			<?php endforeach; ?>
			
			_gaq.push(['_trackTrans']); 					// submits transaction to the Analytics servers
			
			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();
		</script>
		<?php
	}
}

/**
 * Add the integration to WooCommerce
 **/
function add_google_analytics_integration( $integrations ) {
	$integrations[] = 'WC_Google_Analytics'; return $integrations;
}
add_filter('woocommerce_integrations', 'add_google_analytics_integration' );
