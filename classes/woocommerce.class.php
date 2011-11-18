<?php
/**
 * Contains the main functions for WooCommerce, stores variables, and handles error messages
 *
 * @class 		woocommerce
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class woocommerce {
	
	var $_cache;
	var $errors = array(); // Stores store errors
	var $messages = array(); // Stores store messages
	var $attribute_taxonomies; // Stores the attribute taxonomies used in the store
	var $plugin_url;
	var $plugin_path;
	
	// Class instances
	var $query;
	var $customer;
	var $shipping;
	var $cart;
	var $payment_gateways;
	var $countries;
		
	/** constructor */
	function __construct() {
		
		// Load class instances
		$this->query			= &new woocommerce_query();				// Query class, handles front-end queries and loops
		$this->customer 		= &new woocommerce_customer();			// Customer class, sorts out session data such as location
		$this->shipping 		= &new woocommerce_shipping();			// Shipping class. loads and stores shipping methods
		$this->cart 			= &new woocommerce_cart();				// Cart class, stores the cart contents
		$this->payment_gateways = &new woocommerce_payment_gateways();	// Payment gateways class. loads and stores payment methods
		$this->countries 		= &new woocommerce_countries();			// Countries class
		$this->log			 	= &new woocommerce_logger();			// Logger class
		
		// Load messages
		$this->load_messages();
		
		// Hooks
		add_filter('wp_redirect', array(&$this, 'redirect'), 1, 2);
		add_action( 'woocommerce_before_single_product', array(&$this, 'show_messages'), 10);
		add_action( 'woocommerce_before_shop_loop', array(&$this, 'show_messages'), 10);

		// Queue shipping and payment gateways
		add_action('plugins_loaded', array( &$this->shipping, 'init' ), 1); 			// Load shipping methods - some may be added by plugins
		add_action('plugins_loaded', array( &$this->payment_gateways, 'init' ), 1); 	// Load payment methods - some may be added by plugins
	}

    /*-----------------------------------------------------------------------------------*/
	/* Helper functions */
	/*-----------------------------------------------------------------------------------*/ 

		/**
		 * Get the plugin url
		 */
		function plugin_url() { 
			if($this->plugin_url) return $this->plugin_url;
			
			if (is_ssl()) :
				return $this->plugin_url = str_replace('http://', 'https://', WP_PLUGIN_URL) . "/" . plugin_basename( dirname(dirname(__FILE__))); 
			else :
				return $this->plugin_url = WP_PLUGIN_URL . "/" . plugin_basename( dirname(dirname(__FILE__))); 
			endif;
		}
		
		/**
		 * Get the plugin path
		 */
		function plugin_path() { 	
			if($this->plugin_path) return $this->plugin_path;
			return $this->plugin_path = WP_PLUGIN_DIR . "/" . plugin_basename( dirname(dirname(__FILE__))); 
		 }
		 
		/**
		 * Return the URL with https if SSL is on
		 */
		function force_ssl( $url ) { 	
			if (is_ssl()) $url = str_replace('http:', 'https:', $url);
			return $url;
		 }
		
		/**
		 * Get an image size
		 *
		 * Variable is filtered by woocommerce_get_image_size_{image_size}
		 */
		function get_image_size( $image_size ) {
			$return = '';
			switch ($image_size) :
				case "shop_thumbnail_image_width" : $return = get_option('woocommerce_thumbnail_image_width'); break;
				case "shop_thumbnail_image_height" : $return = get_option('woocommerce_thumbnail_image_height'); break;
				case "shop_catalog_image_width" : $return = get_option('woocommerce_catalog_image_width'); break;
				case "shop_catalog_image_height" : $return = get_option('woocommerce_catalog_image_height'); break;
				case "shop_single_image_width" : $return = get_option('woocommerce_single_image_width'); break;
				case "shop_single_image_height" : $return = get_option('woocommerce_single_image_height'); break;
			endswitch;
			return apply_filters( 'woocommerce_get_image_size_'.$image_size, $return );
		}
	
    /*-----------------------------------------------------------------------------------*/
	/* Messages */
	/*-----------------------------------------------------------------------------------*/ 
    
	    /**
		 * Load Messages
		 */
		function load_messages() { 
			if (isset($_SESSION['errors'])) $this->errors = $_SESSION['errors'];
			if (isset($_SESSION['messages'])) $this->messages = $_SESSION['messages'];
			
			unset($_SESSION['messages']);
			unset($_SESSION['errors']);
		}

		/**
		 * Add an error
		 */
		function add_error( $error ) { $this->errors[] = $error; }
		
		/**
		 * Add a message
		 */
		function add_message( $message ) { $this->messages[] = $message; }
		
		/** Clear messages and errors from the session data */
		function clear_messages() {
			$this->errors = $this->messages = array();
			unset($_SESSION['messages']);
			unset($_SESSION['errors']);
		}
		
		/**
		 * Get error count
		 */
		function error_count() { return sizeof($this->errors); }
		
		/**
		 * Get message count
		 */
		function message_count() { return sizeof($this->messages); }
		
		/**
		 * Output the errors and messages
		 */
		function show_messages() {
		
			if (isset($this->errors) && sizeof($this->errors)>0) :
				echo '<div class="woocommerce_error">'.$this->errors[0].'</div>';
				$this->clear_messages();
				return true;
			elseif (isset($this->messages) && sizeof($this->messages)>0) :
				echo '<div class="woocommerce_message">'.$this->messages[0].'</div>';
				$this->clear_messages();
				return true;
			else :
				return false;
			endif;
		}
		
		/**
		 * Redirection hook which stores messages into session data
		 *
		 * @param   location
		 * @param   status
		 * @return  location
		 */
		function redirect( $location, $status ) {
			$_SESSION['errors'] = $this->errors;
			$_SESSION['messages'] = $this->messages;
			return $location;
		}
		
    /*-----------------------------------------------------------------------------------*/
	/* Attributes */
	/*-----------------------------------------------------------------------------------*/ 
	
	    /**
		 * Get attribute taxonomies
		 */
		function get_attribute_taxonomies() { 
			global $wpdb;
			if (!$this->attribute_taxonomies) :
				$this->attribute_taxonomies = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies;");
			endif;
			return $this->attribute_taxonomies;
		}
	    
	    /**
		 * Get a product attributes name
		 */
		function attribute_taxonomy_name( $name ) { 
			return 'pa_'.sanitize_title($name);
		}
		
		/**
		 * Get a product attributes label
		 */
		function attribute_label( $name ) { 
			global $wpdb;
			
			if (strstr( $name, 'pa_' )) :
				$name = str_replace( 'pa_', '', sanitize_title( $name ) );
	
				$label = $wpdb->get_var( $wpdb->prepare( "SELECT attribute_label FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE attribute_name = %s;", $name ) );
				
				if ($label) return $label; else return ucfirst($name);
			else :
				return $name;
			endif;
	
			
		}
		
    /*-----------------------------------------------------------------------------------*/
	/* Coupons */
	/*-----------------------------------------------------------------------------------*/ 
		
		/**
		 * Get coupon types
		 */
		function get_coupon_discount_types() { 
			if (!isset($this->coupon_discount_types)) :
				$this->coupon_discount_types = apply_filters('woocommerce_coupon_discount_types', array(
	    			'fixed_cart' 	=> __('Cart Discount', 'woothemes'),
	    			'percent' 		=> __('Cart % Discount', 'woothemes'),
	    			'fixed_product'	=> __('Product Discount', 'woothemes'),
	    			'percent_product'	=> __('Product % Discount', 'woothemes')
	    		));
    		endif;
    		return $this->coupon_discount_types;
    	}
    	
    	/**
		 * Get a coupon type's name
		 */
		function get_coupon_discount_type( $type = '' ) { 
			$types = (array) $this->get_coupon_discount_types();
			if (isset($types[$type])) return $types[$type];
    	}
	
    /*-----------------------------------------------------------------------------------*/
	/* Nonce Field Helpers */
	/*-----------------------------------------------------------------------------------*/ 
	
		/**
		 * Return a nonce field
		 */
		function nonce_field ($action, $referer = true , $echo = true) { return wp_nonce_field('woocommerce-' . $action, '_n', $referer, $echo); }
		
		/**
		 * Return a url with a nonce appended
		 */
		function nonce_url ($action, $url = '') { return add_query_arg( '_n', wp_create_nonce( 'woocommerce-' . $action ), $url); }
		
		/**
		 * Check a nonce and sets woocommerce error in case it is invalid
		 * To fail silently, set the error_message to an empty string
		 * 
		 * @param 	string $name the nonce name
		 * @param	string $action then nonce action
		 * @param   string $method the http request method _POST, _GET or _REQUEST
		 * @param   string $error_message custom error message, or false for default message, or an empty string to fail silently
		 * 
		 * @return   bool
		 */
		function verify_nonce($action, $method='_POST', $error_message = false) {
			
			$name = '_n';
			$action = 'woocommerce-' . $action;
			
			if( $error_message === false ) $error_message = __('Action failed. Please refresh the page and retry.', 'woothemes'); 
			
			if(!in_array($method, array('_GET', '_POST', '_REQUEST'))) $method = '_POST';
			
			if ( isset($_REQUEST[$name]) && wp_verify_nonce($_REQUEST[$name], $action) ) return true;
			
			if( $error_message ) $this->add_error( $error_message );
			
			return false;
		}
	
    /*-----------------------------------------------------------------------------------*/
	/* Cache Helpers */
	/*-----------------------------------------------------------------------------------*/ 
	
		/**
		 * Cache API
		 */
		function cache ( $id, $data, $args=array() ) {
	
			if( ! isset($this->_cache[ $id ]) ) $this->_cache[ $id ] = array();
			
			if( empty($args) ) $this->_cache[ $id ][0] = $data;
			else $this->_cache[ $id ][ serialize($args) ] = $data;
			
			return $data;
			
		}
		function cache_get ( $id, $args=array() ) {
	
			if( ! isset($this->_cache[ $id ]) ) return null;
			
			if( empty($args) && isset($this->_cache[ $id ][0]) ) return $this->_cache[ $id ][0];
			elseif ( isset($this->_cache[ $id ][ serialize($args) ] ) ) return $this->_cache[ $id ][ serialize($args) ];
		}
		
		/**
		 * Shortcode cache
		 */
		function shortcode_wrapper($function, $atts=array()) {
			if( $content = $this->cache_get( $function . '-shortcode', $atts ) ) return $content;
			
			ob_start();
			call_user_func($function, $atts);
			return $this->cache( $function . '-shortcode', ob_get_clean(), $atts);
		}
		
		
    /*-----------------------------------------------------------------------------------*/
	/* Transients */
	/*-----------------------------------------------------------------------------------*/ 
	
		/**
		 * Clear Product Transients
		 */
		function clear_product_transients( $post_id = 0 ) {
			global $wpdb;
			
			delete_transient('woocommerce_products_onsale');
			delete_transient('woocommerce_hidden_product_ids');
			delete_transient('woocommerce_hidden_from_search_product_ids');
			
			$wpdb->query("DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_woocommerce_unfiltered_product_ids_%')");
			$wpdb->query("DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_woocommerce_layered_nav_count_%')");

			if ($post_id>0) :
				$post_id = (int) $post_id;
				delete_transient('woocommerce_product_total_stock_'.$post_id);
				delete_transient('woocommerce_product_children_ids_'.$post_id);
			else :
				$wpdb->query("DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_woocommerce_product_children_ids_%')");
				$wpdb->query("DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_woocommerce_product_total_stock_%')");
			endif;
		}
	
}