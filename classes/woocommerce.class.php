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
	
	private static $_instance;
	private static $_cache;
	
	public static $errors = array(); // Stores store errors
	public static $messages = array(); // Stores store messages
	public static $attribute_taxonomies; // Stores the attribute taxonomies used in the store
	public static $plugin_url;
	public static $plugin_path;
	public static $query;
		
	/** constructor */
	private function __construct() {
		
		// Vars
		if (isset($_SESSION['errors'])) self::$errors = $_SESSION['errors'];
		if (isset($_SESSION['messages'])) self::$messages = $_SESSION['messages'];
		
		unset($_SESSION['messages']);
		unset($_SESSION['errors']);
		
		// Query vars
		self::$query = array(
			'unfiltered_product_ids' 	=> array(), // Unfilted product ids (before layered nav etc)
			'filtered_product_ids' 		=> array(), // Filted product ids (after layered nav)
			'post__in' 					=> array(), // Product id's that match the layered nav + price filter
			'meta_query' 				=> '', // The meta query for the page
			'layered_nav_post__in' 		=> array(), // posts matching layered nav only
			'layered_nav_product_ids' 	=> array() // Stores posts matching layered nav, so price filter can find max price in view
		);
		
		// Hooks
		add_filter('wp_redirect', array(&$this, 'redirect'), 1, 2);
	}
	
	/** get */
	public static function get() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }
    
    /**
	 * Get attribute taxonomies
	 */
	public static function get_attribute_taxonomies() { 
		global $wpdb;
		if (!self::$attribute_taxonomies) :
			self::$attribute_taxonomies = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies;");
		endif;
		return self::$attribute_taxonomies;
	}
    
    /**
	 * Get a product attributes name
	 */
	public static function attribute_name( $name ) { 
		return 'pa_'.sanitize_title($name);
	}
	
	/**
	 * Get a product attributes label
	 */
	public static function attribute_label( $name ) { 
		global $wpdb;
		
		$name = $wpdb->prepare(sanitize_title($name));

		$label = $wpdb->get_var("SELECT attribute_label FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE attribute_name = '$name';");

		if ($label) return $label; else return ucfirst($name);
	}
	
	/**
	 * Get the plugin url
	 */
	public static function plugin_url() { 
		if(self::$plugin_url) return self::$plugin_url;
		
		if (is_ssl()) :
			return self::$plugin_url = str_replace('http://', 'https://', WP_PLUGIN_URL) . "/" . plugin_basename( dirname(dirname(__FILE__))); 
		else :
			return self::$plugin_url = WP_PLUGIN_URL . "/" . plugin_basename( dirname(dirname(__FILE__))); 
		endif;
	}
	
	/**
	 * Get the plugin path
	 */
	public static function plugin_path() { 	
		if(self::$plugin_path) return self::$plugin_path;
		return self::$plugin_path = WP_PLUGIN_DIR . "/" . plugin_basename( dirname(dirname(__FILE__))); 
	 }
	 
	/**
	 * Return the URL with https if SSL is on
	 */
	public static function force_ssl( $url ) { 	
		if (is_ssl()) $url = str_replace('http:', 'https:', $url);
		return $url;
	 }
	
	/**
	 * Get an image size
	 *
	 * Variable is filtered by woocommerce_get_image_size_{image_size}
	 */
	public static function get_image_size( $image_size ) {
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
	
	/**
	 * Add an error
	 */
	function add_error( $error ) { self::$errors[] = $error; }
	
	/**
	 * Add a message
	 */
	function add_message( $message ) { self::$messages[] = $message; }
	
	/** Clear messages and errors from the session data */
	function clear_messages() {
		self::$errors = self::$messages = array();
		unset($_SESSION['messages']);
		unset($_SESSION['errors']);
	}
	
	/**
	 * Get error count
	 */
	function error_count() { return sizeof(self::$errors); }
	
	/**
	 * Get message count
	 */
	function message_count() { return sizeof(self::$messages); }
	
	/**
	 * Output the errors and messages
	 *
	 * @return   bool
	 */
	public static function show_messages() {
	
		if (isset(self::$errors) && sizeof(self::$errors)>0) :
			echo '<div class="woocommerce_error">'.self::$errors[0].'</div>';
			self::clear_messages();
			return true;
		elseif (isset(self::$messages) && sizeof(self::$messages)>0) :
			echo '<div class="woocommerce_message">'.self::$messages[0].'</div>';
			self::clear_messages();
			return true;
		else :
			return false;
		endif;
	}
	
	/**
	 * Return a nonce field
	 */
	public static function nonce_field ($action, $referer = true , $echo = true) { return wp_nonce_field('woocommerce-' . $action, '_n', $referer, $echo); }
	
	/**
	 * Return a url with a nonce appended
	 */
	public static function nonce_url ($action, $url = '') { return add_query_arg( '_n', wp_create_nonce( 'woocommerce-' . $action ), $url); }
	
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
	public static function verify_nonce($action, $method='_POST', $error_message = false) {
		
		$name = '_n';
		$action = 'woocommerce-' . $action;
		
		if( $error_message === false ) $error_message = __('Action failed. Please refresh the page and retry.', 'woothemes'); 
		
		if(!in_array($method, array('_GET', '_POST', '_REQUEST'))) $method = '_POST';
		
		if ( isset($_REQUEST[$name]) && wp_verify_nonce($_REQUEST[$name], $action) ) return true;
		
		if( $error_message ) woocommerce::add_error( $error_message );
		
		return false;
		
	}
	
	/**
	 * Redirection hook which stores messages into session data
	 *
	 * @param   location
	 * @param   status
	 * @return  location
	 */
	function redirect( $location, $status ) {
		$_SESSION['errors'] = self::$errors;
		$_SESSION['messages'] = self::$messages;
		return $location;
	}
	
	/**
	 * Shortcode cache
	 */
	static public function shortcode_wrapper($function, $atts=array()) {
		if( $content = woocommerce::cache_get( $function . '-shortcode', $atts ) ) return $content;
		
		ob_start();
		call_user_func($function, $atts);
		return woocommerce::cache( $function . '-shortcode', ob_get_clean(), $atts);
	}
	
	/**
	 * Cache API
	 */
	public static function cache ( $id, $data, $args=array() ) {

		if( ! isset(self::$_cache[ $id ]) ) self::$_cache[ $id ] = array();
		
		if( empty($args) ) self::$_cache[ $id ][0] = $data;
		else self::$_cache[ $id ][ serialize($args) ] = $data;
		
		return $data;
		
	}
	public static function cache_get ( $id, $args=array() ) {

		if( ! isset(self::$_cache[ $id ]) ) return null;
		
		if( empty($args) && isset(self::$_cache[ $id ][0]) ) return self::$_cache[ $id ][0];
		elseif ( isset(self::$_cache[ $id ][ serialize($args) ] ) ) return self::$_cache[ $id ][ serialize($args) ];
		
	}
}