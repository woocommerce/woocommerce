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

if ( ! class_exists( 'Woocommerce' ) ) {

/**
 * Main WooCommerce Class
 *
 * Contains the main functions for WooCommerce, stores variables, and handles error messages
 *
 * @class Woocommerce
 * @version	2.0.0
 * @since 1.4
 * @package	WooCommerce
 * @author WooThemes
 */
class Woocommerce {

	/**
	 * @var string
	 */
	public $version = '2.1-bleeding';

	/**
	 * @var string
	 */
	public $plugin_url;

	/**
	 * @var string
	 */
	public $plugin_path;

	/**
	 * @var string
	 */
	public $template_url;

	/**
	 * @var array
	 */
	public $errors = array();

	/**
	 * @var array
	 */
	public $messages = array();

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
	 * @var WC_Integrations
	 */
	public $integrations;

	/**
	 * @var array
	 */
	private $_body_classes = array();

	/**
	 * @var string
	 */
	private $_inline_js = '';


	/**
	 * WooCommerce Constructor.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		// Auto-load classes on demand
		if ( function_exists( "__autoload" ) ) {
			spl_autoload_register( "__autoload" );
    	}
		spl_autoload_register( array( $this, 'autoload' ) );

		// Define version constant
		define( 'WOOCOMMERCE_VERSION', $this->version );

		// Installation
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		// Updates
		add_action( 'admin_init', array( $this, 'update' ), 5 );

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
		add_action( 'init', array( $this, 'include_template_functions' ), 25 );
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
	 *
	 * @access public
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {

		if ( 'payment_gateways' == $key ) {
			return $this->payment_gateways();
		}

		elseif ( 'shipping' == $key ) {
			return $this->shipping();
		}

		return false;
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

			$path = $this->plugin_path() . '/classes/gateways/' . trailingslashit( substr( str_replace( '_', '-', $class ), 11 ) );
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( is_readable( $path . $file ) ) {
				include_once( $path . $file );
				return;
			}

		} elseif ( strpos( $class, 'wc_shipping_' ) === 0 ) {

			$path = $this->plugin_path() . '/classes/shipping/' . trailingslashit( substr( str_replace( '_', '-', $class ), 12 ) );
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( is_readable( $path . $file ) ) {
				include_once( $path . $file );
				return;
			}

		} elseif ( strpos( $class, 'wc_shortcode_' ) === 0 ) {

			$path = $this->plugin_path() . '/classes/shortcodes/';
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( is_readable( $path . $file ) ) {
				include_once( $path . $file );
				return;
			}
		}

		if ( strpos( $class, 'wc_' ) === 0 ) {

			$path = $this->plugin_path() . '/classes/';
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( is_readable( $path . $file ) ) {
				include_once( $path . $file );
				return;
			}
		}
	}


	/**
	 * activate function.
	 *
	 * @access public
	 * @return void
	 */
	public function activate() {
		if ( woocommerce_get_page_id( 'shop' ) < 1 )
			update_option( '_wc_needs_pages', 1 );
		$this->install();
	}

	/**
	 * update function.
	 *
	 * @access public
	 * @return void
	 */
	public function update() {
		if ( ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'woocommerce_version' ) != $this->version || get_option( 'woocommerce_db_version' ) != $this->version ) )
			$this->install();
	}

	/**
	 * upgrade function.
	 *
	 * @access public
	 * @return void
	 */
	function install() {
		include_once( 'admin/woocommerce-admin-install.php' );
		set_transient( '_wc_activation_redirect', 1, 60 * 60 );
		do_install_woocommerce();
	}


	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @access public
	 * @return void
	 */
	function includes() {
		if ( is_admin() )
			$this->admin_includes();
		if ( defined('DOING_AJAX') )
			$this->ajax_includes();
		if ( ! is_admin() || defined('DOING_AJAX') )
			$this->frontend_includes();

		// Post types
		include_once( 'classes/class-wc-post-types.php' );					// Registers post types

		// Functions
		include_once( 'woocommerce-core-functions.php' );					// Contains core functions for the front/back end

		// API Class
		include_once( 'classes/class-wc-api.php' );

		// Include abstract classes
		include_once( 'classes/abstracts/abstract-wc-helper.php' );				// Helper classes
		include_once( 'classes/abstracts/abstract-wc-product.php' );			// Products
		include_once( 'classes/abstracts/abstract-wc-settings-api.php' );		// Settings API (for gateways, shipping, and integrations)
		include_once( 'classes/abstracts/abstract-wc-shipping-method.php' );	// A Shipping method
		include_once( 'classes/abstracts/abstract-wc-payment-gateway.php' ); 	// A Payment gateway
		include_once( 'classes/abstracts/abstract-wc-integration.php' );		// An integration with a service

		// Classes (used on all pages)
		include_once( 'classes/class-wc-product-factory.php' );					// Product factory
		include_once( 'classes/class-wc-countries.php' );						// Defines countries and states
		include_once( 'classes/class-wc-integrations.php' );					// Loads integrations
		include_once( 'classes/class-wc-cache-helper.php' );					// Cache Helper

		// Include Core Integrations - these are included sitewide
		include_once( 'classes/integrations/google-analytics/class-wc-google-analytics.php' );
		include_once( 'classes/integrations/sharethis/class-wc-sharethis.php' );
		include_once( 'classes/integrations/sharedaddy/class-wc-sharedaddy.php' );
	}

	// Temporarily staying here until there is a better spot
	// And this needs to be optimized/sanitized as well
	public function get_helper( $id ) {
		if ( ! isset( $this->helpers[ $id ] ) ) {
			$this->helpers[ $id ] = include( 'classes/helpers/class-wc-' . $id . '-helper.php' );
		}

		return $this->helpers[ $id ];
	}

	/**
	 * Include required admin files.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_includes() {
		include_once( 'admin/woocommerce-admin-init.php' );			// Admin section
	}


	/**
	 * Include required ajax files.
	 *
	 * @access public
	 * @return void
	 */
	public function ajax_includes() {
		include_once( 'woocommerce-ajax.php' );						// Ajax functions for admin and the front-end
	}


	/**
	 * Include required frontend files.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_includes() {
		// Functions
		include_once( 'woocommerce-hooks.php' );						// Template hooks used on the front-end
		include_once( 'woocommerce-functions.php' );					// Contains functions for various front-end events

		// Classes
		include_once( 'classes/class-wc-query.php' );				// The main store queries
		include_once( 'classes/class-wc-cart.php' );					// The main cart class
		include_once( 'classes/class-wc-tax.php' );					// Tax class
		include_once( 'classes/class-wc-customer.php' ); 			// Customer class
		include_once( 'classes/abstracts/abstract-wc-session.php' ); // Abstract for session implementations
		include_once( 'classes/class-wc-session-handler.php' );   	// WC Session class
		include_once( 'classes/class-wc-shortcodes.php' );			// Shortcodes class
	}


	/**
	 * Function used to Init WooCommerce Template Functions - This makes them pluggable by plugins and themes.
	 *
	 * @access public
	 * @return void
	 */
	public function include_template_functions() {
		include_once( 'woocommerce-template.php' );
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
		include_once( 'classes/abstracts/abstract-wc-widget.php' );
		include_once( 'classes/widgets/class-wc-widget-cart.php' );
		include_once( 'classes/widgets/class-wc-widget-products.php' );
		include_once( 'classes/widgets/class-wc-widget-layered-nav.php' );
		include_once( 'classes/widgets/class-wc-widget-layered-nav-filters.php' );
		include_once( 'classes/widgets/class-wc-widget-price-filter.php' );
		include_once( 'classes/widgets/class-wc-widget-product-categories.php' );
		include_once( 'classes/widgets/class-wc-widget-product-search.php' );
		include_once( 'classes/widgets/class-wc-widget-product-tag-cloud.php' );
		include_once( 'classes/widgets/class-wc-widget-recent-reviews.php' );
		include_once( 'classes/widgets/class-wc-widget-recently-viewed.php' );
		include_once( 'classes/widgets/class-wc-widget-top-rated-products.php' );
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

		// Add endpoints
		add_rewrite_endpoint( 'order-pay', EP_PAGES );
		add_rewrite_endpoint( 'order-received', EP_PAGES );
		add_rewrite_endpoint( 'view-order', EP_PAGES );
		add_rewrite_endpoint( 'edit-account', EP_PAGES );

		// Load class instances
		$this->product_factory 		= new WC_Product_Factory();     // Product Factory to create new product instances
		$this->countries 			= new WC_Countries();			// Countries class
		$this->integrations			= new WC_Integrations();		// Integrations class

		// Helpers blank array
		$this->helpers = array();

		// Classes/actions loaded for the frontend and for ajax requests
		if ( ! is_admin() || defined('DOING_AJAX') ) {

			// Session class, handles session data for customers - can be overwritten if custom handler is needed
			$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
			$this->session = new $session_class();

			// Class instances
			$this->cart 			= new WC_Cart();				// Cart class, stores the cart contents
			$this->customer 		= new WC_Customer();			// Customer class, handles data such as customer location
			$this->query			= new WC_Query();				// Query class, handles front-end queries and loops
			$this->shortcodes		= new WC_Shortcodes();			// Shortcodes class, controls all frontend shortcodes

			// Load messages
			$this->load_messages();

			// Hooks
			add_action( 'get_header', array( $this, 'init_checkout' ) );
			add_filter( 'template_include', array( $this, 'template_loader' ) );
			add_filter( 'comments_template', array( $this, 'comments_template_loader' ) );
			add_filter( 'wp_redirect', array( $this, 'redirect' ), 1, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
			add_action( 'wp_print_scripts', array( $this, 'check_jquery' ), 25 );
			add_action( 'wp_head', array( $this, 'generator' ) );
			add_action( 'wp_head', array( $this, 'wp_head' ) );
			add_filter( 'body_class', array( $this->get_helper( 'body-class' ), 'output_body_class' ) );
			add_filter( 'post_class', array( $this->get_help( 'post-class' ), 'post_class' ), 20, 3 );
			add_action( 'wp_footer', array( $this->get_helper( 'inline-javascript' ), 'output_inline_js' ), 25 );

			// HTTPS urls with SSL on
			$filters = array( 'post_thumbnail_html', 'widget_text', 'wp_get_attachment_url', 'wp_get_attachment_image_attributes', 'wp_get_attachment_url', 'option_stylesheet_url', 'option_template_url', 'script_loader_src', 'style_loader_src', 'template_directory_uri', 'stylesheet_directory_uri', 'site_url' );

			foreach ( $filters as $filter )
				add_filter( $filter, array( $this, 'force_ssl' ) );
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
			load_textdomain( 'woocommerce', $this->plugin_path() . "/i18n/languages/woocommerce-admin-$locale.mo" );
		}

		// Frontend Locale
		load_textdomain( 'woocommerce', WP_LANG_DIR . "/woocommerce/woocommerce-$locale.mo" );

		if ( apply_filters( 'woocommerce_load_alt_locale', false ) )
			load_plugin_textdomain( 'woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . "/i18n/languages/alt" );
		else
			load_plugin_textdomain( 'woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . "/i18n/languages" );
	}


	/**
	 * Load a template.
	 *
	 * Handles template usage so that we can use our own templates instead of the themes.
	 *
	 * Templates are in the 'templates' folder. woocommerce looks for theme
	 * overrides in /theme/woocommerce/ by default
	 *
	 * For beginners, it also looks for a woocommerce.php template first. If the user adds
	 * this to the theme (containing a woocommerce() inside) this will be used for all
	 * woocommerce templates.
	 *
	 * @access public
	 * @param mixed $template
	 * @return string
	 */
	public function template_loader( $template ) {

		$find = array( 'woocommerce.php' );
		$file = '';

		if ( is_single() && get_post_type() == 'product' ) {

			$file 	= 'single-product.php';
			$find[] = $file;
			$find[] = $this->template_url . $file;

		} elseif ( is_tax( 'product_cat' ) || is_tax( 'product_tag' ) ) {

			$term = get_queried_object();

			$file 		= 'taxonomy-' . $term->taxonomy . '.php';
			$find[] 	= 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$find[] 	= $this->template_url . 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$find[] 	= $file;
			$find[] 	= $this->template_url . $file;

		} elseif ( is_post_type_archive( 'product' ) || is_page( woocommerce_get_page_id( 'shop' ) ) ) {

			$file 	= 'archive-product.php';
			$find[] = $file;
			$find[] = $this->template_url . $file;

		}

		if ( $file ) {
			$template = locate_template( $find );
			if ( ! $template ) $template = $this->plugin_path() . '/templates/' . $file;
		}

		return $template;
	}


	/**
	 * comments_template_loader function.
	 *
	 * @access public
	 * @param mixed $template
	 * @return string
	 */
	public function comments_template_loader( $template ) {
		if ( get_post_type() !== 'product' )
			return $template;

		if ( file_exists( STYLESHEETPATH . '/' . $this->template_url . 'single-product-reviews.php' ))
			return STYLESHEETPATH . '/' . $this->template_url . 'single-product-reviews.php';
		elseif ( file_exists( TEMPLATEPATH . '/' . $this->template_url . 'single-product-reviews.php' ))
			return TEMPLATEPATH . '/' . $this->template_url . 'single-product-reviews.php';
		else
			return $this->plugin_path() . '/templates/single-product-reviews.php';
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
	 * Add body classes.
	 *
	 * @access public
	 * @return void
	 */
	public function wp_head() {

		if ( is_woocommerce() ) {
			$this->get_helper( 'body-class' )->add_body_class( 'woocommerce' );
			$this->get_helper( 'body-class' )->add_body_class( 'woocommerce-page' );
			return;
		}

		if ( is_checkout() ) {
			$this->get_helper( 'body-class' )->add_body_class( 'woocommerce-checkout' );
			$this->get_helper( 'body-class' )->add_body_class( 'woocommerce-page' );
			return;
		}

		if ( is_cart() ) {
			$this->get_helper( 'body-class' )->add_body_class( 'woocommerce-cart' );
			$this->get_helper( 'body-class' )->add_body_class( 'woocommerce-page' );
			return;
		}

		if ( is_account_page() ) {
			$this->get_helper( 'body-class' )->add_body_class( 'woocommerce-account' );
			$this->get_helper( 'body-class' )->add_body_class( 'woocommerce-page' );
			return;
		}

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


	/**
	 * Register/queue frontend scripts.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() {
		global $post, $wp;

		$suffix               = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$lightbox_en          = get_option( 'woocommerce_enable_lightbox' ) == 'yes' ? true : false;
		$chosen_en            = get_option( 'woocommerce_enable_chosen' ) == 'yes' ? true : false;
		$ajax_cart_en         = get_option( 'woocommerce_enable_ajax_add_to_cart' ) == 'yes' ? true : false;
		$assets_path          = str_replace( array( 'http:', 'https:' ), '', $this->plugin_url() ) . '/assets/';
		$frontend_script_path = $assets_path . 'js/frontend/';

		// Register any scripts for later use, or used as dependencies
		wp_register_script( 'chosen', $assets_path . 'js/chosen/chosen.jquery' . $suffix . '.js', array( 'jquery' ), '0.9.14', true );
		wp_register_script( 'jquery-blockui', $assets_path . 'js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.60', true );
		wp_register_script( 'jquery-placeholder', $assets_path . 'js/jquery-placeholder/jquery.placeholder' . $suffix . '.js', array( 'jquery' ), $this->version, true );

		wp_register_script( 'wc-add-to-cart-variation', $frontend_script_path . 'add-to-cart-variation' . $suffix . '.js', array( 'jquery' ), $this->version, true );
		wp_register_script( 'wc-single-product', $frontend_script_path . 'single-product' . $suffix . '.js', array( 'jquery' ), $this->version, true );
		wp_register_script( 'jquery-cookie', $assets_path . 'js/jquery-cookie/jquery.cookie' . $suffix . '.js', array( 'jquery' ), '1.3.1', true );

		// Queue frontend scripts conditionally
		if ( $ajax_cart_en )
			wp_enqueue_script( 'wc-add-to-cart', $frontend_script_path . 'add-to-cart' . $suffix . '.js', array( 'jquery' ), $this->version, true );

		if ( is_cart() )
			wp_enqueue_script( 'wc-cart', $frontend_script_path . 'cart' . $suffix . '.js', array( 'jquery' ), $this->version, true );

		if ( is_checkout() ) {
			if ( $chosen_en ) {
				wp_enqueue_script( 'wc-chosen', $frontend_script_path . 'chosen-frontend' . $suffix . '.js', array( 'chosen' ), $this->version, true );
				wp_enqueue_style( 'woocommerce_chosen_styles', $assets_path . 'css/chosen.css' );
			}

			wp_enqueue_script( 'wc-checkout', $frontend_script_path . 'checkout' . $suffix . '.js', array( 'jquery', 'woocommerce' ), $this->version, true );
		}

		if ( $lightbox_en && ( is_product() || ( ! empty( $post->post_content ) && strstr( $post->post_content, '[product_page' ) ) ) ) {
			wp_enqueue_script( 'prettyPhoto', $assets_path . 'js/prettyPhoto/jquery.prettyPhoto' . $suffix . '.js', array( 'jquery' ), '3.1.5', true );
			wp_enqueue_script( 'prettyPhoto-init', $assets_path . 'js/prettyPhoto/jquery.prettyPhoto.init' . $suffix . '.js', array( 'jquery' ), $this->version, true );
			wp_enqueue_style( 'woocommerce_prettyPhoto_css', $assets_path . 'css/prettyPhoto.css' );
		}

		if ( is_product() )
			wp_enqueue_script( 'wc-single-product' );

		// Global frontend scripts
		wp_enqueue_script( 'woocommerce', $frontend_script_path . 'woocommerce' . $suffix . '.js', array( 'jquery', 'jquery-blockui' ), $this->version, true );
		wp_enqueue_script( 'wc-cart-fragments', $frontend_script_path . 'cart-fragments' . $suffix . '.js', array( 'jquery', 'jquery-cookie' ), $this->version, true );
		wp_enqueue_script( 'jquery-placeholder' );

		// Variables for JS scripts
		$woocommerce_params = array(
			'countries'                        => json_encode( $this->countries->get_allowed_country_states() ),
			'plugin_url'                       => $this->plugin_url(),
			'ajax_url'                         => $this->ajax_url(),
			'ajax_loader_url'                  => apply_filters( 'woocommerce_ajax_loader_url', $assets_path . 'images/ajax-loader@2x.gif' ),
			'i18n_select_state_text'           => esc_attr__( 'Select an option&hellip;', 'woocommerce' ),
			'i18n_required_rating_text'        => esc_attr__( 'Please select a rating', 'woocommerce' ),
			'i18n_no_matching_variations_text' => esc_attr__( 'Sorry, no products matched your selection. Please choose a different combination.', 'woocommerce' ),
			'i18n_required_text'               => esc_attr__( 'required', 'woocommerce' ),
			'i18n_view_cart'                   => esc_attr__( 'View Cart &rarr;', 'woocommerce' ),
			'review_rating_required'           => get_option( 'woocommerce_review_rating_required' ),
			'update_order_review_nonce'        => wp_create_nonce( "update-order-review" ),
			'apply_coupon_nonce'               => wp_create_nonce( "apply-coupon" ),
			'option_guest_checkout'            => get_option( 'woocommerce_enable_guest_checkout' ),
			'checkout_url'                     => add_query_arg( 'action', 'woocommerce-checkout', $this->ajax_url() ),
			'is_checkout'                      => is_page( woocommerce_get_page_id( 'checkout' ) ) && empty( $wp->query_vars['order-pay'] ) && ! isset( $wp->query_vars['order-received'] ) ? 1 : 0,
			'update_shipping_method_nonce'     => wp_create_nonce( "update-shipping-method" ),
			'cart_url'                         => get_permalink( woocommerce_get_page_id( 'cart' ) ),
			'cart_redirect_after_add'          => get_option( 'woocommerce_cart_redirect_after_add' )
		);

		if ( is_checkout() || is_cart() )
			$woocommerce_params['locale'] = json_encode( $this->countries->get_country_locale() );

		wp_localize_script( 'woocommerce', 'woocommerce_params', apply_filters( 'woocommerce_params', $woocommerce_params ) );

		// CSS Styles
		if ( ! defined( 'WOOCOMMERCE_USE_CSS' ) )
			define( 'WOOCOMMERCE_USE_CSS', true );

		if ( WOOCOMMERCE_USE_CSS ) {
			$css 				= file_exists( get_stylesheet_directory() . '/woocommerce/style.css' ) ? get_stylesheet_directory_uri() . '/woocommerce/style.css' : $assets_path . 'css/woocommerce.css';
			$css_layout 		= file_exists( get_stylesheet_directory() . '/woocommerce/style-layout.css' ) ? get_stylesheet_directory_uri() . '/woocommerce/style-layout.css' : $assets_path . 'css/woocommerce-layout.css';
			$css_smallscreen 	= file_exists( get_stylesheet_directory() . '/woocommerce/style-smallscreen.css' ) ? get_stylesheet_directory_uri() . '/woocommerce/style-smallscreen.css' : $assets_path . 'css/woocommerce-smallscreen.css';

			wp_enqueue_style( 'woocommerce_frontend_styles_layout', $css_layout );
			wp_enqueue_style( 'woocommerce_frontend_styles_smallscreen', $css_smallscreen, '','' ,'only screen and (max-width: ' . apply_filters( 'woocommerce_smallscreen_breakpoint', $breakpoint = '768px' ) . ' )' );
			wp_enqueue_style( 'woocommerce_frontend_styles', $css );
		}
	}

	/**
	 * WC requires jQuery 1.7 since it uses functions like .on() for events.
	 * If, by the time wp_print_scrips is called, jQuery is outdated (i.e not
	 * using the version in core) we need to deregister it and register the
	 * core version of the file.
	 *
	 * @access public
	 * @return void
	 */
	public function check_jquery() {
		global $wp_scripts;

		// Enforce minimum version of jQuery
		if ( ! empty( $wp_scripts->registered['jquery']->ver ) && ! empty( $wp_scripts->registered['jquery']->src ) && $wp_scripts->registered['jquery']->ver < '1.7' ) {
			wp_deregister_script( 'jquery' );
			wp_register_script( 'jquery', '/wp-includes/js/jquery/jquery.js', array(), '1.7' );
			wp_enqueue_script( 'jquery' );
		}
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
		do_action_ref_array( current_filter() . '_notification', func_get_args() );
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
		if ( isset( $this->plugin_url ) ) return $this->plugin_url;
		return $this->plugin_url = untrailingslashit( plugins_url( '/', __FILE__ ) );
	}


	/**
	 * Get the plugin path.
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_path() {
		if ( isset( $this->plugin_path ) ) return $this->plugin_path;

		return $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
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

		return esc_url_raw( trailingslashit( home_url( '/wc-api/' . $request, $scheme ) ) );
	}


	/**
	 * force_ssl function.
	 *
	 * @access public
	 * @param mixed $content
	 * @return void
	 */
	public function force_ssl( $content ) {
		if ( is_ssl() ) {
			if ( is_array($content) )
				$content = array_map( array( $this, 'force_ssl' ) , $content );
			else
				$content = str_replace( 'http:', 'https:', $content );
		}
		return $content;
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

	/** Messages ****************************************************************/

	/**
	 * Load Messages.
	 *
	 * @access public
	 * @return void
	 */
	public function load_messages() {
		$this->errors = $this->session->errors;
		$this->messages = $this->session->messages;
		unset( $this->session->errors, $this->session->messages );

		// Load errors from querystring
		if ( isset( $_GET['wc_error'] ) )
			$this->add_error( esc_attr( $_GET['wc_error'] ) );
	}


	/**
	 * Add an error.
	 *
	 * @access public
	 * @param string $error
	 * @return void
	 */
	public function add_error( $error ) {
		$this->errors[] = apply_filters( 'woocommerce_add_error', $error );
	}


	/**
	 * Add a message.
	 *
	 * @access public
	 * @param string $message
	 * @return void
	 */
	public function add_message( $message ) {
		$this->messages[] = apply_filters( 'woocommerce_add_message', $message );
	}


	/**
	 * Clear messages and errors from the session data.
	 *
	 * @access public
	 * @return void
	 */
	public function clear_messages() {
		$this->errors = $this->messages = array();
		unset( $this->session->errors, $this->session->messages );
	}


	/**
	 * error_count function.
	 *
	 * @access public
	 * @return int
	 */
	public function error_count() {
		return sizeof( $this->errors );
	}


	/**
	 * Get message count.
	 *
	 * @access public
	 * @return int
	 */
	public function message_count() {
		return sizeof( $this->messages );
	}


	/**
	 * Get errors.
	 *
	 * @access public
	 * @return array
	 */
	public function get_errors() {
		return (array) $this->errors;
	}


	/**
	 * Get messages.
	 *
	 * @access public
	 * @return array
	 */
	public function get_messages() {
		return (array) $this->messages;
	}


	/**
	 * Output the errors and messages.
	 *
	 * @access public
	 * @return void
	 */
	public function show_messages() {
		woocommerce_show_messages();
	}


	/**
	 * Set session data for messages.
	 *
	 * @access public
	 * @return void
	 */
	public function set_messages() {
		$this->session->errors = $this->errors;
		$this->session->messages = $this->messages;
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
		$this->set_messages();

		return apply_filters( 'woocommerce_redirect', $location );
	}

	/** Deprecated functions *********************************************************/

	/**
	 * Clear all transients cache for product data.
	 *
	 * @deprecated 2.1.0 Access via the helpers
	 * @access public
	 * @param int $post_id (default: 0)
	 * @return void
	 */
	public function clear_product_transients( $post_id = 0 ) {
		_deprecated_function( 'Woocommerce->clear_product_transients', '2.1', 'WC_Transient_Helper->clear_product_transients' );
		$this->get_helper( 'transient' )->clear_product_transients( $post_id );
	}

	/**
	 * Add some JavaScript inline to be output in the footer.
	 *
	 * @deprecated 2.1.0 Access via the helpers
	 * @access public
	 * @param string $code
	 * @return void
	 */
	public function add_inline_js( $code ) {
		_deprecated_function( 'Woocommerce->add_inline_js', '2.1', 'WC_Inline_Javascript_Helper->add_inline_js' );
		$this->get_helper( 'inline-javascript' )->add_inline_js( $code );
	}

	/**
	 * Output any queued inline JS.
	 *
	 * @deprecated 2.1.0 Access via the helpers
	 * @access public
	 * @return void
	 */
	public function output_inline_js() {
		_deprecated_function( 'Woocommerce->output_inline_js', '2.1', 'WC_Inline_Javascript_Helper->output_inline_js' );
		$this->get_helper( 'inline-javascript' )->output_inline_js();
	}

	/**
	 * Return a nonce field.
	 *
	 * @deprecated 2.1.0 Access via the helpers
	 * @access public
	 * @param mixed $action
	 * @param bool $referer (default: true)
	 * @param bool $echo (default: true)
	 * @return void
	 */
	public function nonce_field( $action, $referer = true , $echo = true ) {
		_deprecated_function( 'Woocommerce->nonce_field', '2.1', 'WC_Nonce_Helper->nonce_field' );
		return $this->get_helper( 'nonce' )->nonce_field( $action, $referer, $echo );
	}

	/**
	 * Return a url with a nonce appended.
	 *
	 * @deprecated 2.1.0 Access via the helpers
	 * @access public
	 * @param mixed $action
	 * @param string $url (default: '')
	 * @return string
	 */
	public function nonce_url( $action, $url = '' ) {
		_deprecated_function( 'Woocommerce->nonce_url', '2.1', 'WC_Nonce_Helper->nonce_url' );
		return $this->get_helper( 'nonce' )->nonce_url( $action, $url );
	}

	/**
	 * Check a nonce and sets woocommerce error in case it is invalid.
	 *
	 * To fail silently, set the error_message to an empty string
	 *
	 * @deprecated 2.1.0 Access via the helpers
	 * @access public
	 * @param string $name the nonce name
	 * @param string $action then nonce action
	 * @param string $method the http request method _POST, _GET or _REQUEST
	 * @param string $error_message custom error message, or false for default message, or an empty string to fail silently
	 * @return bool
	 */
	public function verify_nonce( $action, $method='_POST', $error_message = false ) {
		_deprecated_function( 'Woocommerce->verify_nonce', '2.1', 'WC_Nonce_Helper->verify_nonce' );
		return $this->get_helper( 'nonce' )->verify_nonce( $action, $method, $error_message );
	}

	/**
	 * Shortcode Wrapper
	 *
	 * @deprecated 2.1.0 Access via the helpers
	 * @access public
	 * @param mixed $function
	 * @param array $atts (default: array())
	 * @return string
	 */
	public function shortcode_wrapper(
		$function,
		$atts = array(),
		$wrapper = array(
			'class' => 'woocommerce',
			'before' => null,
			'after' => null
		)
	) {
		_deprecated_function( 'Woocommerce->shortcode_wrapper', '2.1', 'WC_Shortcode_Helper->shortcode_wrapper' );
		return $this->get_helper( 'shortcode' )->shortcode_wrapper( $function, $atts, $wrapper );
	}

	/**
	 * Get attribute taxonomies.
	 *
	 * @deprecated 2.1.0 Access via the helpers
	 * @access public
	 * @return object
	 */
	public function get_attribute_taxonomies() {
		_deprecated_function( 'Woocommerce->get_attribute_taxonomies', '2.1', 'WC_Attribute_Helper->get_attribute_taxonomies' );
		return $this->get_helper( 'attribute' )->get_attribute_taxonomies();
	}

	/**
	 * Get a product attributes name.
	 *
	 * @deprecated 2.1.0 Access via the helpers
	 * @access public
	 * @param mixed $name
	 * @return string
	 */
	public function attribute_taxonomy_name( $name ) {
		_deprecated_function( 'Woocommerce->attribute_taxonomy_name', '2.1', 'WC_Attribute_Helper->attribute_taxonomy_name' );
		return $this->get_helper( 'attribute' )->attribute_taxonomy_name( $name );
	}

	/**
	 * Get a product attributes label.
	 *
	 * @deprecated 2.1.0 Access via the helpers
	 * @access public
	 * @param mixed $name
	 * @return string
	 */
	public function attribute_label( $name ) {
		_deprecated_function( 'Woocommerce->attribute_label', '2.1', 'WC_Attribute_Helper->attribute_label' );
		return $this->get_helper( 'attribute' )->attribute_label( $name );
	}

	/**
	 * Get a product attributes orderby setting.
	 *
	 * @deprecated 2.1.0 Access via the helpers
	 * @access public
	 * @param mixed $name
	 * @return string
	 */
	public function attribute_orderby( $name ) {
		_deprecated_function( 'Woocommerce->attribute_orderby', '2.1', 'WC_Attribute_Helper->attribute_orderby' );
		return $this->get_helper( 'attribute' )->attribute_orderby( $name );
	}

	/**
	 * Get an array of product attribute taxonomies.
	 *
	 * @deprecated 2.1.0 Access via the helpers
	 * @access public
	 * @return array
	 */
	public function get_attribute_taxonomy_names() {
		_deprecated_function( 'Woocommerce->get_attribute_taxonomy_names', '2.1', 'WC_Attribute_Helper->get_attribute_taxonomy_names' );
		return $this->get_helper( 'attribute' )->get_attribute_taxonomy_names();
	}

	/**
	 * Get coupon types.
	 *
	 * @deprecated 2.1.0 Access via the helpers
	 * @access public
	 * @return array
	 */
	public function get_coupon_discount_types() {
		_deprecated_function( 'Woocommerce->get_coupon_discount_types', '2.1', 'WC_Coupon_Helper->get_coupon_discount_types' );
		return $this->get_helper( 'coupon' )->get_coupon_discount_types();
	}


	/**
	 * Get a coupon type's name.
	 *
	 * @deprecated 2.1.0 Access via the helpers
	 * @access public
	 * @param string $type (default: '')
	 * @return string
	 */
	public function get_coupon_discount_type( $type = '' ) {
		_deprecated_function( 'Woocommerce->get_coupon_discount_type', '2.1', 'WC_Coupon_Helper->get_coupon_discount_type' );
		return $this->get_helper( 'coupon' )->get_coupon_discount_type( $type );
	}

	/**
	 * Adds extra post classes for products
	 *
	 * @deprecated 2.1.0 Access via the helpers
	 * @since 2.0
	 * @access public
	 * @param array $classes
	 * @param string|array $class
	 * @param int $post_id
	 * @return array
	 */
	public function post_class( $classes, $class, $post_id ) {
		_deprecated_function( 'Woocommerce->post_class', '2.1', 'WC_Post_Class_Helper->post_class' );
		return $this->get_helper( 'post-class' )->post_class( $classes, $class, $post_id );
	}

	/**
	 * Add a class to the webpage body.
	 *
	 * @access public
	 * @param string $class
	 * @return void
	 */
	public function add_body_class( $class ) {
		_deprecated_function( 'Woocommerce->add_body_class', '2.1', 'WC_Body_Class_Helper->add_body_class' );
		$this->get_helper( 'body-class' )->add_body_class( $class );
	}

	/**
	 * Output classes on the body tag.
	 *
	 * @access public
	 * @param mixed $classes
	 * @return array
	 */
	public function output_body_class( $classes ) {
		_deprecated_function( 'Woocommerce->output_body_class', '2.1', 'WC_Body_Class_Helper->output_body_class' );
		return $this->get_helper( 'body-class' )->output_body_class( $classes );
	}
}

/**
 * Init woocommerce class
 */
$GLOBALS['woocommerce'] = new Woocommerce();

} // class_exists check
