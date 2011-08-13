<?php
/*
Plugin Name: WooCommerce
Plugin URI: http://woocommerce.com
Description: An eCommerce plugin for wordpress.
Version: 1.0
Author: WooThemes
Author URI: http://woothemes.com
Requires at least: 3.1
Tested up to: 3.2
*/

@session_start();

/**
 * Localisation
 **/
load_plugin_textdomain('woothemes', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');


/**
 * Constants
 **/
if (!defined('WOOCOMMERCE_TEMPLATE_URL')) define('WOOCOMMERCE_TEMPLATE_URL', 'woocommerce/');
if (!defined("WOOCOMMERCE_VERSION")) define("WOOCOMMERCE_VERSION", "1.0");	
if (!defined("PHP_EOL")) define("PHP_EOL", "\r\n");


/**
 * Include core files
 **/
include_once( 'woocommerce_taxonomy.php' );
include_once( 'classes/woocommerce.class.php' );
include_once( 'widgets/widgets-init.php' );
include_once( 'shortcodes/shortcodes-init.php' );
include_once( 'woocommerce_actions.php' );
include_once( 'woocommerce_emails.php' );
include_once( 'woocommerce_query.php' );
include_once( 'woocommerce_template_actions.php' );
include_once( 'woocommerce_templates.php' );
include_once( 'classes/gateways/gateways.class.php' );
include_once( 'classes/gateways/gateway.class.php' );
include_once( 'classes/shipping/shipping.class.php' );
include_once( 'classes/shipping/shipping_method.class.php' );
	
	
/**
 * Include admin area
 **/
if (is_admin()) :
	require_once( 'admin/admin-init.php' );

	/**
	 * Installs and upgrades
	 **/
	register_activation_hook( __FILE__, 'install_woocommerce' );
	
	function woocommerce_update_check() {
	    if (get_site_option('woocommerce_db_version') != WOOCOMMERCE_VERSION) install_woocommerce();
	}
	add_action('init', 'woocommerce_update_check');
	
endif;


/**
 * Include classes
 */
include_once( 'classes/cart.class.php' );
include_once( 'classes/checkout.class.php' );
include_once( 'classes/countries.class.php' );
include_once( 'classes/coupons.class.php' );
include_once( 'classes/customer.class.php' ); 
include_once( 'classes/order.class.php' );
include_once( 'classes/orders.class.php' );
include_once( 'classes/product.class.php' );
include_once( 'classes/product_variation.class.php' );
include_once( 'classes/tax.class.php' );
include_once( 'classes/validation.class.php' ); 


/**
 * Include shipping modules
 */
include_once( 'classes/shipping/shipping-flat_rate.php' );
include_once( 'classes/shipping/shipping-free_shipping.php' );


/**
 * Include payment gateways
 */
include_once( 'classes/gateways/gateway-banktransfer.php' );
include_once( 'classes/gateways/gateway-cheque.php' );
include_once( 'classes/gateways/gateway-moneybookers.php' );
include_once( 'classes/gateways/gateway-paypal.php' );


/**
 * Init class singletons
 */		
$woocommerce 					= woocommerce::get();
$woocommerce_customer 			= woocommerce_customer::get();				// Customer class, sorts out session data such as location
$woocommerce_shipping 			= woocommerce_shipping::get();				// Shipping class. loads and stores shipping methods
$woocommerce_payment_gateways 	= woocommerce_payment_gateways::get();		// Payment gateways class. loads and stores payment methods
$woocommerce_cart 				= woocommerce_cart::get();					// Cart class, stores the cart contents
	
	
/**
 * Add post thumbnail support to wordpress
 **/
if ( !current_theme_supports( 'post-thumbnails' ) ) :
   add_theme_support( 'post-thumbnails' );
   add_action( 'init', 'woocommerce_remove_post_type_thumbnail_support' );
endif;

function woocommerce_remove_post_type_thumbnail_support() {
   remove_post_type_support( 'post', 'thumbnail' );
   remove_post_type_support( 'page', 'thumbnail' );
}
	
	
/**
 * Filters and hooks
 **/
add_action('init', 'woocommerce_init', 0);
add_action('plugins_loaded', 'woocommerce_shipping::init', 1); 		// Load shipping methods - some may be added by plugins
add_action('plugins_loaded', 'woocommerce_payment_gateways::init', 1); // Load payment methods - some may be added by plugins

if (get_option('woocommerce_force_ssl_checkout')=='yes') add_action( 'wp_head', 'woocommerce_force_ssl');

add_action( 'wp_footer', 'woocommerce_demo_store' );
add_action( 'wp_footer', 'woocommerce_sharethis' );


/**
 * IIS compat fix/fallback
 **/
if (!isset($_SERVER['REQUEST_URI'])) {
	$_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'],1 );
	if (isset($_SERVER['QUERY_STRING'])) { $_SERVER['REQUEST_URI'].='?'.$_SERVER['QUERY_STRING']; }
}


/**
 * Init WooCommerce
 **/
function woocommerce_init() {
	
	woocommerce_post_type();
	
	// Constants	
	if (!defined('WOOCOMMERCE_USE_CSS')) :
		if (get_option('woocommerce_frontend_css')=='no') define('WOOCOMMERCE_USE_CSS', false);
		else define('WOOCOMMERCE_USE_CSS', true);
	endif;
	
	// Image sizes
	add_image_size( 'shop_tiny', woocommerce::get_var('shop_tiny_w'), woocommerce::get_var('shop_tiny_h'), 'true' );
	add_image_size( 'shop_thumbnail', woocommerce::get_var('shop_thumbnail_w'), woocommerce::get_var('shop_thumbnail_h'), 'true' );
	add_image_size( 'shop_small', woocommerce::get_var('shop_small_w'), woocommerce::get_var('shop_small_h'), 'true' );
	add_image_size( 'shop_large', woocommerce::get_var('shop_large_w'), woocommerce::get_var('shop_large_h'), 'true' );

	// Include template functions here so they are pluggable by themes
	include_once( 'woocommerce_template_functions.php' );
	
	@ob_start();

	$css = file_exists(get_stylesheet_directory() . '/woocommerce/style.css') ? get_stylesheet_directory_uri() . '/woocommerce/style.css' : woocommerce::plugin_url() . '/assets/css/woocommerce.css';
    if (WOOCOMMERCE_USE_CSS) wp_register_style('woocommerce_frontend_styles', $css );

    if (is_admin()) :
    	wp_register_style('woocommerce_admin_styles', woocommerce::plugin_url() . '/assets/css/admin.css');
    	wp_enqueue_style('woocommerce_admin_styles');
    else :
    	wp_register_style( 'woocommerce_fancybox_styles', woocommerce::plugin_url() . '/assets/css/fancybox.css' ); 
    	wp_register_style( 'jqueryui_styles', woocommerce::plugin_url() . '/assets/css/ui.css' );
    	
    	wp_enqueue_style('woocommerce_frontend_styles');
    	wp_enqueue_style('woocommerce_fancybox_styles');
    	wp_enqueue_style('jqueryui_styles');
    endif;
}

function woocommerce_admin_scripts() {
	
	wp_register_script( 'woocommerce_admin', woocommerce::plugin_url() . '/assets/js/woocommerce_admin.js', 'jquery', '1.0' );
    wp_enqueue_script('woocommerce_admin');
    	
}
add_action('admin_print_scripts', 'woocommerce_admin_scripts');

function woocommerce_frontend_scripts() {
	
	wp_register_script( 'woocommerce', woocommerce::plugin_url() . '/assets/js/woocommerce.js', 'jquery', '1.0' );
	wp_register_script( 'woocommerce_plugins', woocommerce::plugin_url() . '/assets/js/woocommerce_plugins.js', 'jquery', '1.0' );
	wp_register_script( 'fancybox', woocommerce::plugin_url() . '/assets/js/fancybox.js', 'jquery', '1.0' );
	
	wp_enqueue_script('jquery');
	wp_enqueue_script('woocommerce_plugins');
	wp_enqueue_script('woocommerce');
	wp_enqueue_script('fancybox');
    	
	/* Script variables */
	$params = array(
		'currency_symbol' 				=> get_woocommerce_currency_symbol(),
		'countries' 					=> json_encode(woocommerce_countries::$states),
		'select_state_text' 			=> __('Select a state&hellip;', 'woothemes'),
		'state_text' 					=> __('state', 'woothemes'),
		'variation_not_available_text' 	=> __('This variation is not available.', 'woothemes'),
		'plugin_url' 					=> woocommerce::plugin_url(),
		'ajax_url' 						=> admin_url('admin-ajax.php'),
		'get_variation_nonce' 			=> wp_create_nonce("get-variation"),
		'review_order_url'				=> woocommerce_get_template_file_url('checkout/review_order.php', true),
		'option_guest_checkout'			=> get_option('woocommerce_enable_guest_checkout'),
		'checkout_url'					=> admin_url('admin-ajax.php?action=woocommerce-checkout')
	);
	
	if (isset($_SESSION['min_price'])) :
		$params['min_price'] = $_SESSION['min_price'];
	endif;
	if (isset($_SESSION['max_price'])) :
		$params['max_price'] = $_SESSION['max_price'];
	endif;
		
	if ( is_page(get_option('woocommerce_checkout_page_id')) || is_page(get_option('woocommerce_pay_page_id')) ) :
		$params['is_checkout'] = 1;
	else :
		$params['is_checkout'] = 0;
	endif;
	
	wp_localize_script( 'woocommerce', 'params', $params );
	
}
add_action('template_redirect', 'woocommerce_frontend_scripts');


/**
 * Demo Banner
 *
 * Adds a demo store banner to the site if enabled
 **/
function woocommerce_demo_store() {
	
	if (get_option('woocommerce_demo_store')=='yes') :
		
		echo '<p class="demo_store">'.__('This is a demo store for testing purposes &mdash; no orders shall be fulfilled.', 'woothemes').'</p>';
		
	endif;
}


/**
 * Sharethis
 *
 * Adds social sharing code
 **/
function woocommerce_sharethis() {
	if (is_single() && get_option('woocommerce_sharethis')) :
		
		if (is_ssl()) :
			$sharethis = 'https://ws.sharethis.com/button/buttons.js';
		else :
			$sharethis = 'http://w.sharethis.com/button/buttons.js';
		endif;
		
		echo '<script type="text/javascript">var switchTo5x=true;</script><script type="text/javascript" src="'.$sharethis.'"></script><script type="text/javascript">stLight.options({publisher:"'.get_option('woocommerce_sharethis').'"});</script>';
		
	endif;
}


/**
 * WooCommerce conditionals
 **/
function is_cart() {
	if (is_page(get_option('woocommerce_cart_page_id'))) return true;
	return false;
}
function is_checkout() {
	if (
		is_page(get_option('woocommerce_checkout_page_id'))
	) return true;
	return false;
}
if (!function_exists('is_ajax')) {
	function is_ajax() {
		if ( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) return true;
		return false;
	}
}


/**
 * Force SSL (if enabled)
 **/
function woocommerce_force_ssl() {
	if (is_checkout() && !is_ssl()) :
		wp_redirect( str_replace('http:', 'https:', get_permalink(get_option('woocommerce_checkout_page_id'))), 301 );
		exit;
	endif;
}
function woocommerce_force_ssl_images( $content ) {
	if (is_ssl()) :
		if (is_array($content)) :
			$content = array_map('woocommerce_force_ssl_images', $content);
		else :
			$content = str_replace('http:', 'https:', $content);
		endif;
	endif;
	return $content;
}
add_filter('post_thumbnail_html', 'woocommerce_force_ssl_images');
add_filter('widget_text', 'woocommerce_force_ssl_images');
add_filter('wp_get_attachment_url', 'woocommerce_force_ssl_images');
add_filter('wp_get_attachment_image_attributes', 'woocommerce_force_ssl_images');
add_filter('wp_get_attachment_url', 'woocommerce_force_ssl_images');


/**
 * Currency
 **/
function get_woocommerce_currency_symbol() {
	$currency = get_option('woocommerce_currency');
	$currency_symbol = '';
	switch ($currency) :
		case 'AUD' :
		case 'BRL' :
		case 'CAD' :
		case 'MXN' :
		case 'NZD' :
		case 'HKD' :
		case 'SGD' :
		case 'USD' : $currency_symbol = '&#36;'; break;
		case 'EUR' : $currency_symbol = '&euro;'; break;
		case 'JPY' : $currency_symbol = '&yen;'; break;
		
		case 'CZK' :
		case 'DKK' :
		case 'HUF' :
		case 'ILS' :
		case 'MYR' :
		case 'NOK' :
		case 'PHP' :
		case 'PLN' :
		case 'SEK' :
		case 'CHF' :
		case 'TWD' :
		case 'THB' : $currency_symbol = $currency; break;
		
		case 'GBP' : 
		default    : $currency_symbol = '&pound;'; break;
	endswitch;
	return apply_filters('woocommerce_currency_symbol', $currency_symbol, $currency);
}


/**
 * Price Formatting
 **/
function woocommerce_price( $price, $args = array() ) {
	
	extract(shortcode_atts(array(
		'ex_tax_label' 	=> '0'
	), $args));
	
	$return = '';
	$num_decimals = (int) get_option('woocommerce_price_num_decimals');
	$currency_pos = get_option('woocommerce_currency_pos');
	$currency_symbol = get_woocommerce_currency_symbol();
	$price = number_format( (double) $price, $num_decimals, get_option('woocommerce_price_decimal_sep'), get_option('woocommerce_price_thousand_sep') );
	
	switch ($currency_pos) :
		case 'left' :
			$return = $currency_symbol . $price;
		break;
		case 'right' :
			$return = $price . $currency_symbol;
		break;
		case 'left_space' :
			$return = $currency_symbol . ' ' . $price;
		break;
		case 'right_space' :
			$return = $price . ' ' . $currency_symbol;
		break;
	endswitch;
	
	if ($ex_tax_label && get_option('woocommerce_calc_taxes')=='yes') $return .= __(' <small>(ex. tax)</small>', 'woothemes');
	
	return $return;
}


/**
 * Variation Formatting
 **/
function woocommerce_get_formatted_variation( $variation = '', $flat = false ) {
	if ($variation && is_array($variation)) :
		
		$return = '';
		
		if (!$flat) :
			$return = '<dl class="variation">';
		endif;
		
		$varation_list = array();
		
		foreach ($variation as $name => $value) :
			
			if ($flat) :
				$varation_list[] = ucfirst(str_replace('tax_', '', $name)).': '.ucfirst($value);
			else :
				$varation_list[] = '<dt>'.ucfirst(str_replace('tax_', '', $name)).':</dt><dd>'.ucfirst($value).'</dd>';
			endif;
			
		endforeach;
		
		if ($flat) :
			$return .= implode(', ', $varation_list);
		else :
			$return .= implode('', $varation_list);
		endif;
		
		if (!$flat) :
			$return .= '</dl>';
		endif;
		
		return $return;
		
	endif;
}


/**
 * Letter to number
 **/
function woocommerce_let_to_num($v) {
    $l = substr($v, -1);
    $ret = substr($v, 0, -1);
    switch(strtoupper($l)){
    case 'P':
        $ret *= 1024;
    case 'T':
        $ret *= 1024;
    case 'G':
        $ret *= 1024;
    case 'M':
        $ret *= 1024;
    case 'K':
        $ret *= 1024;
        break;
    }
    return $ret;
}


/**
 * Clean variables
 **/
function woocommerce_clean( $var ) {
	return strip_tags(stripslashes(trim($var)));
}


/**
 * Rating field for comments
 **/
function woocommerce_add_comment_rating($comment_id) {
	if ( isset($_POST['rating']) ) :
		if (!$_POST['rating'] || $_POST['rating'] > 5 || $_POST['rating'] < 0) $_POST['rating'] = 5; 
		add_comment_meta( $comment_id, 'rating', $_POST['rating'], true );
	endif;
}
add_action( 'comment_post', 'woocommerce_add_comment_rating', 1 );

function woocommerce_check_comment_rating($comment_data) {
	// If posting a comment (not trackback etc) and not logged in
	if ( isset($_POST['rating']) && !woocommerce::verify_nonce('comment_rating') )
		wp_die( __('You have taken too long. Please go back and refresh the page.', 'woothemes') );
		
	elseif ( isset($_POST['rating']) && empty($_POST['rating']) && $comment_data['comment_type']== '' ) {
		wp_die( __('Please rate the product.',"woocommerce") );
		exit;
	}
	return $comment_data;
}
add_filter('preprocess_comment', 'woocommerce_check_comment_rating', 0);	


/**
 * Review comments template
 **/
function woocommerce_comments($comment, $args, $depth) {
	$GLOBALS['comment'] = $comment; global $post; ?>
	
	<li <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>">
		<div id="comment-<?php comment_ID(); ?>" class="comment_container">

  			<?php echo get_avatar( $comment, $size='60' ); ?>
			
			<div class="comment-text">
				<div class="star-rating" title="<?php echo get_comment_meta( $comment->comment_ID, 'rating', true ); ?>">
					<span style="width:<?php echo get_comment_meta( $comment->comment_ID, 'rating', true )*16; ?>px"><?php echo get_comment_meta( $comment->comment_ID, 'rating', true ); ?> <?php _e('out of 5', 'woothemes'); ?></span>
				</div>
				<?php if ($comment->comment_approved == '0') : ?>
					<p class="meta"><em><?php _e('Your comment is awaiting approval', 'woothemes'); ?></em></p>
				<?php else : ?>
					<p class="meta">
						<?php _e('Rating by', 'woothemes'); ?> <strong class="reviewer vcard"><span class="fn"><?php comment_author(); ?></span></strong> <?php _e('on', 'woothemes'); ?> <?php echo get_comment_date('M jS Y'); ?>:
					</p>
				<?php endif; ?>
  				<div class="description"><?php comment_text(); ?></div>
  				<div class="clear"></div>
  			</div>
			<div class="clear"></div>			
		</div>
	<?php
}


/**
 * Exclude order comments from front end
 **/
function woocommerce_exclude_order_comments( $clauses ) {
	
	global $wpdb;
	
	$clauses['join'] = "
		LEFT JOIN $wpdb->posts ON $wpdb->comments.comment_post_ID = $wpdb->posts.ID
	";
	
	if ($clauses['where']) $clauses['where'] .= ' AND ';
	
	$clauses['where'] .= "
		$wpdb->posts.post_type NOT IN ('shop_order')
	";
	
	return $clauses;	

}
if (!is_admin()) add_filter('comments_clauses', 'woocommerce_exclude_order_comments');


/**
 * Cron Job - Update price if on sale
 **/
function woocommerce_update_sale_prices() {
	
	global $wpdb;
	
	// On Sale Products
	$on_sale = $wpdb->get_results("
		SELECT post_id FROM $wpdb->postmeta
		WHERE meta_key = 'sale_price_dates_from'
		AND meta_value < ".strtotime('NOW')."
	");
	if ($on_sale) foreach ($on_sale as $product) :
		
		$data = unserialize( get_post_meta($product, 'product_data', true) );
		$price = get_post_meta($product, 'price', true); 
	
		if ($data['sale_price'] && $price!==$data['sale_price']) update_post_meta($product, 'price', $data['sale_price']);
		
	endforeach;
	
	// Expired Sales
	$sale_expired = $wpdb->get_results("
		SELECT post_id FROM $wpdb->postmeta
		WHERE meta_key = 'sale_price_dates_to'
		AND meta_value < ".strtotime('NOW')."
	");
	if ($sale_expired) foreach ($sale_expired as $product) :
	
		$data = unserialize( get_post_meta($product, 'product_data', true) );
		$price = get_post_meta($product, 'price', true); 
	
		if ($data['regular_price'] && $price!==$data['regular_price']) update_post_meta($product, 'price', $data['regular_price']);
		
		// Sale has expired - clear the schedule boxes
		update_post_meta($product, 'sale_price_dates_from', '');
		update_post_meta($product, 'sale_price_dates_to', '');
		
	endforeach;
	
}
function woocommerce_update_sale_prices_schedule_check(){
	wp_schedule_event(time(), 'daily', 'woocommerce_update_sale_prices_schedule_check');
	update_option('woocommerce_update_sale_prices', 'yes');
}
if (get_option('woocommerce_update_sale_prices')!='yes') woocommerce_update_sale_prices_schedule_check();

add_action('woocommerce_update_sale_prices_schedule_check', 'woocommerce_update_sale_prices');


/**
 * Set up Roles & Capabilities
 **/
function woocommerce_init_roles() {
	global $wp_roles;

	if (class_exists('WP_Roles')) if ( ! isset( $wp_roles ) ) $wp_roles = new WP_Roles();	
	
	if (is_object($wp_roles)) :
		
		// Customer role
		add_role('customer', __('Customer', 'woothemes'), array(
		    'read' => true,
		    'edit_posts' => false,
		    'delete_posts' => false
		));
	
		// Shop manager role
		add_role('shop_manager', __('Shop Manager', 'woothemes'), array(
		    'read' 			=> true,
		    'edit_posts' 	=> true,
		    'delete_posts' 	=> true,
		));

		// Main Shop capabilities
		$wp_roles->add_cap( 'administrator', 'manage_woocommerce' );
		$wp_roles->add_cap( 'shop_manager', 'manage_woocommerce' );
		
	endif;
}

add_action('init', 'woocommerce_init_roles');