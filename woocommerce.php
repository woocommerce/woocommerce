<?php
/**
 * Plugin Name: WooCommerce
 * Plugin URI: http://www.woothemes.com/woocommerce/
 * Description: An e-commerce toolkit that helps you sell anything. Beautifully.
 * Version: 2.1-bleeding
 * Author: WooThemes
 * Author URI: http://woothemes.com
 * Requires at least: 3.5
 * Tested up to: 3.5
 *
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 *
 * @package WooCommerce
 * @category Core
 * @author WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooCommerce' ) ) :

/**
 * Main WooCommerce Class
 *
 * @class WooCommerce
 * @version	2.1.0
 * @since 1.4
 * @package	WooCommerce
 * @author WooThemes
 */
final class WooCommerce {
	/**
	 * @var WooCommerce The single instance of WooCommerce
	 * @since 2.1
	 */
	private static $_instance = null;

	/**
	 * @var string
	 */
	public $version = '2.1-bleeding';

	/**
	 * @var WC_Query
	 */
	public $query;

	/**
	 * @var WC_Customer
	 */
	public $customer;

	/**
	 * @var WC_Product_Factory
	 */
	public $product_factory;

	/**
	 * @var WC_Cart
	 */
	public $cart;

	/**
	 * @var WC_Countries
	 */
	public $countries;

	/**
	 * @var WC_Email
	 */
	public $woocommerce_email;

	/**
	 * @var WC_Checkout
	 */
	public $checkout;

    /**
    * @var Helpers blank array
    */
    public $helpers = array();


	/**
	 * @var WC_Integrations
	 */
	public $integrations;

	/**
	 * Main WooCommerce Instance
	 *
	 * Ensures only one instance of WooCommerce is loaded or can be loaded.
	 *
	 * @since 2.1
	 * @static
	 * @see WC()
	 * @return Main WooCommerce instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '2.1' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '2.1' );
	}

	/**
	 * WooCommerce Constructor.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		// Auto-load classes on demand
		if ( function_exists( "__autoload" ) )
			spl_autoload_register( "__autoload" );

		spl_autoload_register( array( $this, 'autoload' ) );

		// Define constants
		define( 'WOOCOMMERCE_PLUGIN_FILE', __FILE__ );
		define( 'WOOCOMMERCE_VERSION', $this->version );

		// Include required files
		$this->includes();

		// Init API
		$this->api = new WC_API();

		// Hooks
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
		add_filter( 'woocommerce_shipping_methods', array( $this, 'core_shipping' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'core_gateways' ) );
		add_action( 'widgets_init', array( $this, 'include_widgets' ) );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( $this, 'include_template_functions' ) );
		add_action( 'after_setup_theme', array( $this, 'compatibility' ) );

		// Loaded action
		do_action( 'woocommerce_loaded' );
	}

	/**
	 * action_links function.
	 *
	 * @access public
	 * @param mixed $links
	 * @return void
	 */
	public function action_links( $links ) {

		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=woocommerce_settings' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>',
			'<a href="http://docs.woothemes.com/documentation/plugins/woocommerce/">' . __( 'Docs', 'woocommerce' ) . '</a>',
			'<a href="http://support.woothemes.com/">' . __( 'Premium Support', 'woocommerce' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 * TODO: Need to find a permanent solution for loading/deprecating these.
	 *
	 * @access public
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {
		switch( $key ) {
			case 'payment_gateways':
				return $this->payment_gateways();
			case 'shipping':
				return $this->shipping();
			case 'template_url':
				_deprecated_argument( 'Woocommerce->template_url', '2.1', 'The "template_url" field is moved to the template helper class.' );
				return $this->get_helper( 'template' )->template_url;
			case 'plugin_url':
				_deprecated_argument( 'Woocommerce->plugin_url', '2.1', 'The "plugin_url" field is removed, please use the Woocommerce->plugin_url() function.' );
				return $this->plugin_url();
			case 'plugin_path':
				_deprecated_argument( 'Woocommerce->plugin_path', '2.1', 'The "plugin_path" field is removed, please use the Woocommerce->plugin_path() function.' );
				return $this->plugin_path();
			case 'messages':
				_deprecated_argument( 'Woocommerce->messages', '2.1', 'The "messages" field is moved to the messages helper class.' );
				return $this->get_helper( 'messages' )->messages;
			case 'errors':
				_deprecated_argument( 'Woocommerce->errors', '2.1', 'The "errors" field is moved to the messages helper class.' );
				return $this->get_helper( 'messages' )->errors;
			default:
				return false;
		}
	}

	/**
	 * Auto-load WC classes on demand to reduce memory consumption.
	 *
	 * @access public
	 * @param mixed $class
	 * @return void
	 */
	public function autoload( $class ) {

		$class = strtolower( $class );

		if ( strpos( $class, 'wc_gateway_' ) === 0 ) {

			$path = $this->plugin_path() . '/includes/gateways/' . trailingslashit( substr( str_replace( '_', '-', $class ), 11 ) );
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( is_readable( $path . $file ) ) {
				include_once( $path . $file );
				return;
			}

		} elseif ( strpos( $class, 'wc_shipping_' ) === 0 ) {

			$path = $this->plugin_path() . '/includes/shipping/' . trailingslashit( substr( str_replace( '_', '-', $class ), 12 ) );
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( is_readable( $path . $file ) ) {
				include_once( $path . $file );
				return;
			}

		} elseif ( strpos( $class, 'wc_shortcode_' ) === 0 ) {

			$path = $this->plugin_path() . '/includes/shortcodes/';
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( is_readable( $path . $file ) ) {
				include_once( $path . $file );
				return;
			}

		} elseif ( strpos( $class, 'wc_meta_box' ) === 0 ) {

			$path = $this->plugin_path() . '/includes/admin/post-types/meta-boxes/';
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( is_readable( $path . $file ) ) {
				include_once( $path . $file );
				return;
			}
		}

		if ( strpos( $class, 'wc_' ) === 0 ) {

			$path = $this->plugin_path() . '/includes/';
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( is_readable( $path . $file ) ) {
				include_once( $path . $file );
				return;
			}
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @access public
	 * @return void
	 */
	function includes() {

		include( 'includes/wc-core-functions.php' );
		include( 'includes/class-wc-install.php' );
		include( 'includes/class-wc-download-handler.php' );
		include( 'includes/class-wc-comments.php' );

		if ( is_admin() )
			include_once( 'includes/admin/class-wc-admin.php' );

		if ( defined('DOING_AJAX') )
			$this->ajax_includes();

		if ( ! is_admin() || defined('DOING_AJAX') )
			$this->frontend_includes();

		// Query class
		$this->query = include( 'includes/class-wc-query.php' );				// The main query class

		// Post types
		include_once( 'includes/class-wc-post-types.php' );					// Registers post types

		// API Class
		include_once( 'includes/class-wc-api.php' );

		// Include abstract classes
		include_once( 'includes/abstracts/abstract-wc-helper.php' );				// Helper classes
		include_once( 'includes/abstracts/abstract-wc-product.php' );			// Products
		include_once( 'includes/abstracts/abstract-wc-settings-api.php' );		// Settings API (for gateways, shipping, and integrations)
		include_once( 'includes/abstracts/abstract-wc-shipping-method.php' );	// A Shipping method
		include_once( 'includes/abstracts/abstract-wc-payment-gateway.php' ); 	// A Payment gateway
		include_once( 'includes/abstracts/abstract-wc-integration.php' );		// An integration with a service

		// Classes (used on all pages)
		include_once( 'includes/class-wc-product-factory.php' );					// Product factory
		include_once( 'includes/class-wc-countries.php' );						// Defines countries and states
		include_once( 'includes/class-wc-integrations.php' );					// Loads integrations
		include_once( 'includes/class-wc-cache-helper.php' );					// Cache Helper
		include_once( 'includes/class-wc-https.php' );							// https Helper

		// Include Core Integrations - these are included sitewide
		include_once( 'includes/integrations/google-analytics/class-wc-google-analytics.php' );
		include_once( 'includes/integrations/sharethis/class-wc-sharethis.php' );
		include_once( 'includes/integrations/sharedaddy/class-wc-sharedaddy.php' );

		// Include template hooks in time for themes to remove/modify them
		include_once( 'includes/wc-template-hooks.php' );
	}

	// Temporarily staying here until there is a better spot
	// And this needs to be optimized/sanitized as well
	public function get_helper( $id ) {
		if ( ! isset( $this->helpers[ $id ] ) ) {
			$this->helpers[ $id ] = include( 'includes/helpers/class-wc-' . $id . '-helper.php' );
		}

		return $this->helpers[ $id ];
	}

	/**
	 * Include required ajax files.
	 *
	 * @access public
	 * @return void
	 */
	public function ajax_includes() {
		include_once( 'woocommerce-ajax.php' );		// Ajax functions for admin and the front-end
	}

	/**
	 * Include required frontend files.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_includes() {
		include_once( 'includes/class-wc-frontend-scripts.php' );
		include_once( 'includes/class-wc-form-handler.php' );
		include_once( 'includes/class-wc-cart.php' );					// The main cart class
		include_once( 'includes/class-wc-tax.php' );					// Tax class
		include_once( 'includes/class-wc-customer.php' ); 			// Customer class
		include_once( 'includes/abstracts/abstract-wc-session.php' ); // Abstract for session implementations
		include_once( 'includes/class-wc-session-handler.php' );   	// WC Session class
		include_once( 'includes/class-wc-shortcodes.php' );			// Shortcodes class
	}

	/**
	 * Function used to Init WooCommerce Template Functions - This makes them pluggable by plugins and themes.
	 */
	public function include_template_functions() {
		include_once( 'includes/wc-template-functions.php' );
	}

	/**
	 * core_gateways function.
	 *
	 * @access public
	 * @param mixed $methods
	 * @return void
	 */
	function core_gateways( $methods ) {
		$methods[] = 'WC_Gateway_BACS';
		$methods[] = 'WC_Gateway_Cheque';
		$methods[] = 'WC_Gateway_COD';
		$methods[] = 'WC_Gateway_Mijireh';
		$methods[] = 'WC_Gateway_Paypal';
		return $methods;
	}


	/**
	 * core_shipping function.
	 *
	 * @access public
	 * @param mixed $methods
	 * @return void
	 */
	function core_shipping( $methods ) {
		$methods[] = 'WC_Shipping_Flat_Rate';
		$methods[] = 'WC_Shipping_Free_Shipping';
		$methods[] = 'WC_Shipping_International_Delivery';
		$methods[] = 'WC_Shipping_Local_Delivery';
		$methods[] = 'WC_Shipping_Local_Pickup';
		return $methods;
	}

	/**
	 * include_widgets function.
	 *
	 * @access public
	 * @return void
	 */
	public function include_widgets() {
		include_once( 'includes/abstracts/abstract-wc-widget.php' );
		include_once( 'includes/widgets/class-wc-widget-cart.php' );
		include_once( 'includes/widgets/class-wc-widget-products.php' );
		include_once( 'includes/widgets/class-wc-widget-layered-nav.php' );
		include_once( 'includes/widgets/class-wc-widget-layered-nav-filters.php' );
		include_once( 'includes/widgets/class-wc-widget-price-filter.php' );
		include_once( 'includes/widgets/class-wc-widget-product-categories.php' );
		include_once( 'includes/widgets/class-wc-widget-product-search.php' );
		include_once( 'includes/widgets/class-wc-widget-product-tag-cloud.php' );
		include_once( 'includes/widgets/class-wc-widget-recent-reviews.php' );
		include_once( 'includes/widgets/class-wc-widget-recently-viewed.php' );
		include_once( 'includes/widgets/class-wc-widget-top-rated-products.php' );
	}

	/**
	 * Init WooCommerce when WordPress Initialises.
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		//Before init action
		do_action( 'before_woocommerce_init' );

		// Set up localisation
		$this->load_plugin_textdomain();

		// Variables
		$this->template_url			= apply_filters( 'woocommerce_template_url', 'woocommerce/' );

		// Load class instances, can be overriden if need be
		$this->product_factory = apply_filters( 'woocommerce_instance_product_factory', new WC_Product_Factory() ); // Product Factory to create new product instances
		$this->countries       = apply_filters( 'woocommerce_instance_countries', new WC_Countries() ); // Countries class
		$this->integrations    = apply_filters( 'woocommerce_instance_integrations', new WC_Integrations() ); // Integrations class

		// Classes/actions loaded for the frontend and for ajax requests
		if ( ! is_admin() || defined('DOING_AJAX') ) {

			// Session class, handles session data for customers - can be overwritten if custom handler is needed
			$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
			$this->session = new $session_class();

			// Class instances, can be overriden if need be
			$this->cart       = apply_filters( 'woocommerce_instance_cart', new WC_Cart() ); // Cart class, stores the cart contents
			$this->customer   = apply_filters( 'woocommerce_instance_customer', new WC_Customer() ); // Customer class, handles data such as customer location
			$this->shortcodes = apply_filters( 'woocommerce_instance_shortcodes', new WC_Shortcodes() ); // Shortcodes class, controls all frontend shortcodes

			// Hooks
			add_action( 'get_header', array( $this, 'init_checkout' ) );
			add_filter( 'template_include', array( $this->get_helper( 'template' ), 'template_loader' ) );
			add_filter( 'comments_template', array( $this->get_helper( 'template' ), 'comments_template_loader' ) );
			add_filter( 'wp_redirect', array( $this, 'redirect' ), 1, 2 );
			add_action( 'wp_head', array( $this, 'generator' ) );

			add_action( 'wp_footer', array( $this->get_helper( 'inline-javascript' ), 'output_inline_js' ), 25 );
		}

		// Actions
		add_action( 'the_post', array( $this, 'setup_product_data' ) );
		add_action( 'admin_footer', array( $this->get_helper( 'inline-javascript' ), 'output_inline_js' ), 25 );

		// Email Actions
		$email_actions = array( 'woocommerce_low_stock', 'woocommerce_no_stock', 'woocommerce_product_on_backorder', 'woocommerce_order_status_pending_to_processing', 'woocommerce_order_status_pending_to_completed', 'woocommerce_order_status_pending_to_on-hold', 'woocommerce_order_status_failed_to_processing', 'woocommerce_order_status_failed_to_completed', 'woocommerce_order_status_completed', 'woocommerce_new_customer_note', 'woocommerce_created_customer' );

		foreach ( $email_actions as $action )
			add_action( $action, array( $this, 'send_transactional_email'), 10, 10 );

		// Register globals for WC environment
		$this->register_globals();

		// Init Images sizes
		$this->init_image_sizes();

		// Init action
		do_action( 'woocommerce_init' );
	}

	/**
	 * During checkout, ensure gateways and shipping classes are loaded so they can hook into the respective pages.
	 *
	 * @access public
	 * @return void
	 */
	public function init_checkout() {
		if ( is_checkout() ) {
			$this->payment_gateways();
			$this->shipping();
		}
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present
	 *
	 * @access public
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce' );

		// Admin Locale
		if ( is_admin() ) {
			load_textdomain( 'woocommerce', WP_LANG_DIR . "/woocommerce/woocommerce-admin-$locale.mo" );
			load_textdomain( 'woocommerce', "i18n/languages/woocommerce-admin-$locale.mo" );
		}

		// Frontend Locale
		load_textdomain( 'woocommerce', WP_LANG_DIR . "/woocommerce/woocommerce-$locale.mo" );

		if ( apply_filters( 'woocommerce_load_alt_locale', false ) )
			load_plugin_textdomain( 'woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . "/i18n/languages/alt" );
		else
			load_plugin_textdomain( 'woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . "/i18n/languages" );
	}

	/**
	 * Register WC environment globals.
	 *
	 * @access public
	 * @return void
	 */
	public function register_globals() {
		$GLOBALS['product'] = null;
	}

	/**
	 * When the_post is called, get product data too.
	 *
	 * @access public
	 * @param mixed $post
	 * @return WC_Product
	 */
	public function setup_product_data( $post ) {
		if ( is_int( $post ) ) $post = get_post( $post );
		if ( $post->post_type !== 'product' ) return;
		unset( $GLOBALS['product'] );
		$GLOBALS['product'] = get_product( $post );
		return $GLOBALS['product'];
	}

	/**
	 * Add Compatibility for various bits.
	 *
	 * @access public
	 * @return void
	 */
	public function compatibility() {
		// Post thumbnail support
		if ( ! current_theme_supports( 'post-thumbnails', 'product' ) ) {
			add_theme_support( 'post-thumbnails' );
			remove_post_type_support( 'post', 'thumbnail' );
			remove_post_type_support( 'page', 'thumbnail' );
		} else {
			add_post_type_support( 'product', 'thumbnail' );
		}

		// IIS
		if ( ! isset($_SERVER['REQUEST_URI'] ) ) {
			$_SERVER['REQUEST_URI'] = substr( $_SERVER['PHP_SELF'], 1 );
			if ( isset( $_SERVER['QUERY_STRING'] ) )
				$_SERVER['REQUEST_URI'].='?'.$_SERVER['QUERY_STRING'];
		}

		// NGINX Proxy
		if ( ! isset( $_SERVER['REMOTE_ADDR'] ) && isset( $_SERVER['HTTP_REMOTE_ADDR'] ) )
			$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_REMOTE_ADDR'];

		if ( ! isset( $_SERVER['HTTPS'] ) && ! empty( $_SERVER['HTTP_HTTPS'] ) )
			$_SERVER['HTTPS'] = $_SERVER['HTTP_HTTPS'];

		// Support for hosts which don't use HTTPS, and use HTTP_X_FORWARDED_PROTO
		if ( ! isset( $_SERVER['HTTPS'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' )
			$_SERVER['HTTPS'] = '1';
	}

	/**
	 * Output generator to aid debugging.
	 *
	 * @access public
	 * @return void
	 */
	public function generator() {
		echo "\n\n" . '<!-- WooCommerce Version -->' . "\n" . '<meta name="generator" content="WooCommerce ' . esc_attr( $this->version ) . '" />' . "\n\n";
	}

	/**
	 * Init images.
	 *
	 * @access public
	 * @return void
	 */
	public function init_image_sizes() {
		$shop_thumbnail = $this->get_image_size( 'shop_thumbnail' );
		$shop_catalog	= $this->get_image_size( 'shop_catalog' );
		$shop_single	= $this->get_image_size( 'shop_single' );

		add_image_size( 'shop_thumbnail', $shop_thumbnail['width'], $shop_thumbnail['height'], $shop_thumbnail['crop'] );
		add_image_size( 'shop_catalog', $shop_catalog['width'], $shop_catalog['height'], $shop_catalog['crop'] );
		add_image_size( 'shop_single', $shop_single['width'], $shop_single['height'], $shop_single['crop'] );
	}

	/** Load Instances on demand **********************************************/

	/**
	 * Get Checkout Class.
	 *
	 * @access public
	 * @return WC_Checkout
	 */
	public function checkout() {
		if ( empty( $this->checkout ) )
			$this->checkout = new WC_Checkout();

		return $this->checkout;
	}

	/**
	 * Get gateways class
	 *
	 * @access public
	 * @return WC_Payment_Gateways
	 */
	public function payment_gateways() {
		if ( empty( $this->payment_gateways ) )
			$this->payment_gateways = new WC_Payment_Gateways();

		return $this->payment_gateways;
	}

	/**
	 * Get shipping class
	 *
	 * @access public
	 * @return WC_Shipping
	 */
	public function shipping() {
		if ( empty( $this->shipping ) )
			$this->shipping = new WC_Shipping();

		return $this->shipping;
	}

	/**
	 * Get Logging Class.
	 *
	 * @access public
	 * @return WC_Logger
	 */
	public function logger() {
		return new WC_Logger();
	}

	/**
	 * Get Validation Class.
	 *
	 * @access public
	 * @return WC_Validation
	 */
	public function validation() {
		return new WC_Validation();
	}

	/**
	 * Init the mailer and call the notifications for the current filter.
	 *
	 * @access public
	 * @param array $args (default: array())
	 * @return void
	 */
	public function send_transactional_email() {
		$this->mailer();
		$args = func_get_args();
		do_action_ref_array( current_filter() . '_notification', $args );
	}

	/**
	 * Email Class.
	 *
	 * @access public
	 * @return WC_Email
	 */
	public function mailer() {
		if ( empty( $this->woocommerce_email ) ) {
			$this->woocommerce_email = new WC_Emails();
		}
		return $this->woocommerce_email;
	}

	/** Helper functions ******************************************************/

	/**
	 * Get the plugin url.
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}


	/**
	 * Get the plugin path.
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}


	/**
	 * Get Ajax URL.
	 *
	 * @access public
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}


	/**
	 * Return the WC API URL for a given request
	 *
	 * @access public
	 * @param mixed $request
	 * @param mixed $ssl (default: null)
	 * @return string
	 */
	public function api_request_url( $request, $ssl = null ) {
		if ( is_null( $ssl ) ) {
			$scheme = parse_url( get_option( 'home' ), PHP_URL_SCHEME );
		} elseif ( $ssl ) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}

		if ( get_option('permalink_structure') ) {
			return esc_url_raw( trailingslashit( home_url( '/wc-api/' . $request, $scheme ) ) );
		} else {
			return esc_url_raw( add_query_arg( 'wc-api', $request, trailingslashit( home_url( '', $scheme ) ) ) );
		}
	}

	/**
	 * Get an image size.
	 *
	 * Variable is filtered by woocommerce_get_image_size_{image_size}
	 *
	 * @access public
	 * @param mixed $image_size
	 * @return string
	 */
	public function get_image_size( $image_size ) {

		// Only return sizes we define in settings
		if ( ! in_array( $image_size, array( 'shop_thumbnail', 'shop_catalog', 'shop_single' ) ) )
			return apply_filters( 'woocommerce_get_image_size_' . $image_size, '' );

		$size = get_option( $image_size . '_image_size', array() );

		$size['width'] 	= isset( $size['width'] ) ? $size['width'] : '300';
		$size['height'] = isset( $size['height'] ) ? $size['height'] : '300';
		$size['crop'] 	= isset( $size['crop'] ) ? $size['crop'] : 1;

		return apply_filters( 'woocommerce_get_image_size_' . $image_size, $size );
	}

	/**
	 * Redirection hook which stores messages into session data.
	 *
	 * @access public
	 * @param mixed $location
	 * @param mixed $status
	 * @return string
	 */
	public function redirect( $location, $status ) {
		return apply_filters( 'woocommerce_redirect', $location );
	}

	/** Deprecated functions *********************************************************/

	// Deprecated 2.1.0 Access via the WC_Transient_Helper helper
	public function force_ssl( $content ) {
		_deprecated_function( 'Woocommerce->force_ssl', '2.1', 'WC_HTTPS::force_https_url' );
		return WC_HTTPS::force_https_url( $content );
	}

	// Deprecated 2.1.0 Access via the WC_Transient_Helper helper
	public function clear_product_transients( $post_id = 0 ) {
		_deprecated_function( 'Woocommerce->clear_product_transients', '2.1', 'wc_delete_product_transients' );
		wc_delete_product_transients( $post_id );
	}

	// Deprecated 2.1.0 Access via the WC_Inline_Javascript_Helper helper
	public function add_inline_js( $code ) {
		_deprecated_function( 'Woocommerce->add_inline_js', '2.1', 'WC_Inline_Javascript_Helper->add_inline_js' );
		$this->get_helper( 'inline-javascript' )->add_inline_js( $code );
	}

	// Deprecated 2.1.0 Access via the WC_Inline_Javascript_Helper helper
	public function output_inline_js() {
		_deprecated_function( 'Woocommerce->output_inline_js', '2.1', 'WC_Inline_Javascript_Helper->output_inline_js' );
		$this->get_helper( 'inline-javascript' )->output_inline_js();
	}

	// Deprecated 2.1.0
	public function nonce_field( $action, $referer = true , $echo = true ) {
		_deprecated_function( 'Woocommerce->nonce_field', '2.1', 'wp_nonce_field' );
		return wp_nonce_field('woocommerce-' . $action, '_wpnonce', $referer, $echo );
	}

	// Deprecated 2.1.0
	public function nonce_url( $action, $url = '' ) {
		_deprecated_function( 'Woocommerce->nonce_url', '2.1', 'wp_nonce_url' );
		return wp_nonce_url( $url , 'woocommerce-' . $action );
	}

	// Deprecated 2.1.0 Access via the WC_Nonce_Helper helper
	public function verify_nonce( $action, $method = '_POST', $error_message = false ) {
		_deprecated_function( 'Woocommerce->verify_nonce', '2.1', 'WC_Nonce_Helper->verify_nonce' );
		return wp_verify_nonce( $$_method[ '_wpnonce' ], 'woocommerce-' . $action );
	}

	// Deprecated 2.1.0 Access via the WC_Shortcode_Helper helper
	public function shortcode_wrapper( $function, $atts = array(), $wrapper = array( 'class' => 'woocommerce', 'before' => null, 'after' => null ) ) {
		_deprecated_function( 'Woocommerce->shortcode_wrapper', '2.1', 'WC_Shortcodes::shortcode_wrapper' );
		return WC_Shortcodes::shortcode_wrapper( $function, $atts, $wrapper );
	}

	// Deprecated 2.1.0 Access via the WC_Attribute_Helper helper
	public function get_attribute_taxonomies() {
		_deprecated_function( 'Woocommerce->get_attribute_taxonomies', '2.1', 'WC_Attribute_Helper->get_attribute_taxonomies' );
		return $this->get_helper( 'attribute' )->get_attribute_taxonomies();
	}

	// Deprecated 2.1.0 Access via the WC_Attribute_Helper helper
	public function attribute_taxonomy_name( $name ) {
		_deprecated_function( 'Woocommerce->attribute_taxonomy_name', '2.1', 'WC_Attribute_Helper->attribute_taxonomy_name' );
		return $this->get_helper( 'attribute' )->attribute_taxonomy_name( $name );
	}

	// Deprecated 2.1.0 Access via the WC_Attribute_Helper helper
	public function attribute_label( $name ) {
		_deprecated_function( 'Woocommerce->attribute_label', '2.1', 'WC_Attribute_Helper->attribute_label' );
		return $this->get_helper( 'attribute' )->attribute_label( $name );
	}

	// Deprecated 2.1.0 Access via the WC_Attribute_Helper helper
	public function attribute_orderby( $name ) {
		_deprecated_function( 'Woocommerce->attribute_orderby', '2.1', 'WC_Attribute_Helper->attribute_orderby' );
		return $this->get_helper( 'attribute' )->attribute_orderby( $name );
	}

	// Deprecated 2.1.0 Access via the WC_Attribute_Helper helper
	public function get_attribute_taxonomy_names() {
		_deprecated_function( 'Woocommerce->get_attribute_taxonomy_names', '2.1', 'WC_Attribute_Helper->get_attribute_taxonomy_names' );
		return $this->get_helper( 'attribute' )->get_attribute_taxonomy_names();
	}

	// Deprecated 2.1.0
	public function get_coupon_discount_types() {
		_deprecated_function( 'Woocommerce->get_coupon_discount_types', '2.1', 'wc_get_coupon_types' );
		return wc_get_coupon_types();
	}

	// Deprecated 2.1.0
	public function get_coupon_discount_type( $type = '' ) {
		_deprecated_function( 'Woocommerce->get_coupon_discount_type', '2.1', 'wc_get_coupon_type' );
		return wc_get_coupon_type( $type );
	}

	// Deprecated 2.1.0 Access via the WC_Body_Class_Helper helper
	public function add_body_class( $class ) {
		_deprecated_function( 'Woocommerce->add_body_class', '2.1' );
	}

	// Deprecated 2.1.0 Access via the WC_Body_Class_Helper helper
	public function output_body_class( $classes ) {
		_deprecated_function( 'Woocommerce->output_body_class', '2.1' );
	}

	// Deprecated 2.1.0 Access via the WC_Template_Helper helper
	public function template_loader( $template ) {
		_deprecated_function( 'Woocommerce->template_loader', '2.1', 'WC_Template_Helper->template_loader' );
		return $this->get_helper( 'template' )->template_loader( $template );
	}

	// Deprecated 2.1.0 Access via the WC_Template_Helper helper
	public function comments_template_loader( $template ) {
		_deprecated_function( 'Woocommerce->comments_template_loader', '2.1', 'WC_Template_Helper->comments_template_loader' );
		return $this->get_helper( 'template' )->comments_template_loader( $template );
	}

	// Deprecated 2.1.0
	public function add_error( $error ) {
		_deprecated_function( 'Woocommerce->add_error', '2.1', 'wc_add_error' );
		wc_add_error( $error );
	}

	// Deprecated 2.1.0
	public function add_message( $message ) {
		_deprecated_function( 'Woocommerce->add_message', '2.1', 'wc_add_message' );
		wc_add_message( $message );
	}

	// Deprecated 2.1.0
	public function clear_messages() {
		_deprecated_function( 'Woocommerce->clear_messages', '2.1', 'wc_clear_messages' );
		wc_clear_messages();
	}

	// Deprecated 2.1.0
	public function error_count() {
		_deprecated_function( 'Woocommerce->error_count', '2.1', 'wc_error_count' );
		return wc_error_count();
	}

	// Deprecated 2.1.0
	public function message_count() {
		_deprecated_function( 'Woocommerce->message_count', '2.1', 'wc_message_count' );
		return wc_message_count();
	}

	// Deprecated 2.1.0 Access via the WC_Messages_Helper helper
	public function get_errors() {
		_deprecated_function( 'Woocommerce->get_errors', '2.1', 'WC_Messages_Helper->get_errors' );
		return WC()->session->get( 'wc_errors', array() );
	}

	// Deprecated 2.1.0 Access via the WC_Messages_Helper helper
	public function get_messages() {
		_deprecated_function( 'Woocommerce->get_messages', '2.1', 'WC_Messages_Helper->get_messages' );
		return WC()->session->get( 'wc_messages', array() );
	}

	// Deprecated 2.1.0 Access via the WC_Messages_Helper helper
	public function show_messages() {
		_deprecated_function( 'Woocommerce->show_messages', '2.1', 'wc_print_messages()' );
		wc_print_messages();
	}

	// Deprecated 2.1.0 Access via the WC_Messages_Helper helper
	public function set_messages() {
		_deprecated_function( 'Woocommerce->set_messages', '2.1' );
	}
}

endif;

/**
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @since  2.1
 * @return  object WooCommerce
 */
function WC() {
	return WooCommerce::instance();
}

// Global for backwards compatibilty.
$GLOBALS['woocommerce'] = WC();
