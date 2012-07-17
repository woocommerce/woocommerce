<?php
/**
 * WooCommerce Core Functions
 * 
 * Functions available on both the front-end and admin. 
 *
 * @package		WooCommerce
 * @category	Core
 * @author		WooThemes
 */
 
/**
 * woocommerce_get_dimension function.
 * 
 * Normalise dimensions, unify to cm then convert to wanted unit value
 *
 * Usage: woocommerce_get_dimension(55, 'in');
 *
 * @access public
 * @param mixed $dim
 * @param mixed $unit 'in', 'm', 'cm', 'm'
 * @return void
 *                                                                      
 */
function woocommerce_get_dimension( $dim, $to_unit ) {
	
	$from_unit 	= strtolower( get_option( 'woocommerce_dimension_unit' ) );
	$to_unit	= strtolower( $to_unit );
	
	// Unify all units to cm first
	if ( $from_unit !== $to_unit ) {

		switch ( $from_unit ) {
			case 'in':
				$dim *= 2.54;
			break;
			case 'm':
				$dim *= 100;
			break;
			case 'mm':
				$dim *= 0.1;
			break;
			case 'yd':
				$dim *= 0.010936133;
			break;
		}

		// Output desired unit
		switch ( $to_unit ) {
			case 'in':
				$dim *= 0.3937;
			break;
			case 'm':
				$dim *= 0.01;
			break;
			case 'mm':
				$dim *= 10;
			break;
			case 'yd':
				$dim *= 91.44;
			break;
		}
	}
	return ( $dim < 0 ) ? 0 : $dim;
}

/**
 * woocommerce_get_weight function.
 * 
 * Normalise weights, unify to cm then convert to wanted unit value
 *
 * Usage: woocommerce_get_weight(55, 'kg');
 *
 * @access public
 * @param mixed $weight
 * @param mixed $unit 'g', 'kg', 'lbs'
 * @return void
 */
function woocommerce_get_weight( $weight, $to_unit ) {
	
	$from_unit 	= strtolower( get_option('woocommerce_weight_unit') );
	$to_unit	= strtolower( $to_unit );
		
	//Unify all units to kg first
	if ( $from_unit !== $to_unit ) {
		
		switch ( $from_unit ) {
			case 'g':
				$weight *= 0.001;
			break;
			case 'lbs':
				$weight *= 0.4536;
			break;
			case 'oz':
				$weight *= 0.0283;
			break;
		}

		// Output desired unit
		switch ( $to_unit ) {
			case 'g':
				$weight *= 1000;
			break;
			case 'lbs':
				$weight *= 2.2046;
			break;
			case 'oz':
				$weight *= 35.274;
			break;
		}
	}
	return ( $weight < 0 ) ? 0 : $weight;
}


/**
 * Get the placeholder for products etc
 **/
function woocommerce_placeholder_img_src() {
	global $woocommerce;
	
	return apply_filters('woocommerce_placeholder_img_src', $woocommerce->plugin_url().'/assets/images/placeholder.png');
}
 
/**
 * HTML emails from WooCommerce
 **/
function woocommerce_mail( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = "" ) {
	global $woocommerce;
	
	$mailer = $woocommerce->mailer();
		
	$mailer->send( $to, $subject, $message, $headers, $attachments );
}

/**
 * WooCommerce page IDs
 *
 * retrieve page ids - used for myaccount, edit_address, change_password, shop, cart, checkout, pay, view_order, thanks, terms, order_tracking
 *
 * returns -1 if no page is found
 **/
if ( ! function_exists( 'woocommerce_get_page_id' ) ) {
	function woocommerce_get_page_id( $page ) {
		$page = apply_filters('woocommerce_get_' . $page . '_page_id', get_option('woocommerce_' . $page . '_page_id'));
		return ($page) ? $page : -1;
	}
}

/**
 * WooCommerce clear cart
 *
 * Clears the cart session when called
 **/
if ( ! function_exists( 'woocommerce_empty_cart' ) ) {
	function woocommerce_empty_cart() {
		global $woocommerce;
		
		if (!isset($woocommerce->cart) || $woocommerce->cart == '' ) $woocommerce->cart = new WC_Cart();
		
		$woocommerce->cart->empty_cart( false );
	}
}

/**
 * WooCommerce disable admin bar
 *
 **/
if ( ! function_exists( 'woocommerce_disable_admin_bar' ) ) {
	function woocommerce_disable_admin_bar() {
		if ( get_option('woocommerce_lock_down_admin')=='yes' && !current_user_can('edit_posts') ) {
			add_filter( 'show_admin_bar', '__return_false' );
			wp_deregister_style( 'admin-bar' );
			remove_action('wp_head', '_admin_bar_bump_cb');
		}
	}
}

/**
 * Load the cart upon login
 **/
function woocommerce_load_persistent_cart( $user_login, $user ) {
	global $woocommerce;
	
	$saved_cart = get_user_meta( $user->ID, '_woocommerce_persistent_cart', true );
	
	if ($saved_cart) {
		if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || sizeof($_SESSION['cart'])==0) {
			
			$_SESSION['cart'] = $saved_cart['cart'];
			
		}
	}
}

/**
 * WooCommerce conditionals
 *
 * is_woocommerce - Returns true if on a page which uses WooCommerce templates (cart and checkout are standard pages with shortcodes and thus are not included)
 **/
function is_woocommerce() {
	if (is_shop() || is_product_category() || is_product_tag() || is_product()) return true; else return false;
}
if ( ! function_exists( 'is_shop' ) ) {
	function is_shop() {
		if (is_post_type_archive( 'product' ) || is_page(woocommerce_get_page_id('shop'))) return true; else return false;
	}
}
if ( ! function_exists( 'is_product_category' ) ) {
	function is_product_category( $term = '' ) {
		return is_tax( 'product_cat', $term );
	}
}
if ( ! function_exists( 'is_product_tag' ) ) {
	function is_product_tag( $term = '' ) {
		return is_tax( 'product_tag', $term );
	}
}
if ( ! function_exists( 'is_product' ) ) {
	function is_product() {
		return is_singular( array('product') );
	}
}
if ( ! function_exists( 'is_cart' ) ) {
	function is_cart() {
		return is_page(woocommerce_get_page_id('cart'));
	}
}
if ( ! function_exists( 'is_checkout' ) ) {
	function is_checkout() {
		if (is_page(woocommerce_get_page_id('checkout')) || is_page(woocommerce_get_page_id('pay'))) return true; else return false;
	}
}
if ( ! function_exists( 'is_account_page' ) ) {
	function is_account_page() {
		if ( is_page(woocommerce_get_page_id('myaccount')) || is_page(woocommerce_get_page_id('edit_address')) || is_page(woocommerce_get_page_id('view_order')) || is_page(woocommerce_get_page_id('change_password')) ) return true; else return false;
	}
}
if ( ! function_exists( 'is_ajax' ) ) {
	function is_ajax() {
		if ( defined('DOING_AJAX') ) return true;
		if ( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) return true; else return false;
	}
}

/**
 * Get template part (for templates like the shop-loop)
 */
function woocommerce_get_template_part( $slug, $name = '' ) {
	global $woocommerce;
	$template = '';
	
	// Look in yourtheme/slug-name.php and yourtheme/woocommerce/slug-name.php
	if ( $name ) 
		$template = locate_template( array ( "{$slug}-{$name}.php", "{$woocommerce->template_url}{$slug}-{$name}.php" ) );
	
	// Get default slug-name.php
	if ( !$template && $name && file_exists( $woocommerce->plugin_path() . "/templates/{$slug}-{$name}.php" ) )
		$template = $woocommerce->plugin_path() . "/templates/{$slug}-{$name}.php";

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/woocommerce/slug.php
	if ( !$template ) 
		$template = locate_template( array ( "{$slug}.php", "{$woocommerce->template_url}{$slug}.php" ) );

	if ( $template ) 
		load_template( $template, false );
}

/**
 * Get other templates (e.g. product attributes) passing attributes and including the file
 */
function woocommerce_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	global $woocommerce;
	
	if ( $args && is_array($args) ) 
		extract( $args );
	
	$located = woocommerce_locate_template( $template_name, $template_path, $default_path );
	
	do_action( 'woocommerce_before_template_part', $template_name, $template_path, $located );
	
	include( $located );
	
	do_action( 'woocommerce_after_template_part', $template_name, $template_path, $located );  
}

/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 *		yourtheme		/	$template_path	/	$template_name
 *		yourtheme		/	$template_name
 *		$default_path	/	$template_name
 */
function woocommerce_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	global $woocommerce;
	
	if ( ! $template_path ) $template_path = $woocommerce->template_url;
	if ( ! $default_path ) $default_path = $woocommerce->plugin_path() . '/templates/';
	
	// Look within passed path within the theme - this is priority
	$template = locate_template( 
		array( 
			$template_path . $template_name,
			$template_name
		) 
	);

	// Get default template
	if ( ! $template )
		$template = $default_path . $template_name;

	// Return what we found
	return apply_filters('woocommerce_locate_template', $template, $template_name, $template_path);
}

/**
 * Get Currency
 **/
function get_woocommerce_currency() {
	return apply_filters( 'woocommerce_currency', get_option('woocommerce_currency') );
}

/**
 * Currency
 **/
function get_woocommerce_currency_symbol( $currency = '' ) {
	if ( ! $currency ) $currency = get_woocommerce_currency();
	$currency_symbol = '';
	switch ($currency) :
		case 'BRL' : $currency_symbol = '&#82;&#36;'; break;
		case 'AUD' : $currency_symbol = '&#36;'; break;
		case 'CAD' : $currency_symbol = '&#36;'; break;
		case 'MXN' : $currency_symbol = '&#36;'; break;
		case 'NZD' : $currency_symbol = '&#36;'; break;
		case 'HKD' : $currency_symbol = '&#36;'; break;
		case 'SGD' : $currency_symbol = '&#36;'; break;
		case 'USD' : $currency_symbol = '&#36;'; break;
		case 'EUR' : $currency_symbol = '&euro;'; break;
		case 'CNY' : $currency_symbol = '&yen;'; break;
		case 'JPY' : $currency_symbol = '&yen;'; break;
		case 'TRY' : $currency_symbol = '&#84;&#76;'; break;
		case 'NOK' : $currency_symbol = '&#107;&#114;'; break;
		case 'ZAR' : $currency_symbol = '&#82;'; break;
		case 'CZK' : $currency_symbol = '&#75;&#269;'; break;
		case 'MYR' : $currency_symbol = '&#82;&#77;'; break;
		case 'DKK' : $currency_symbol = '&#107;&#114;'; break;
		case 'HUF' : $currency_symbol = '&#70;&#116;'; break;
		case 'ILS' : $currency_symbol = '&#8362;'; break;
		case 'PHP' : $currency_symbol = '&#8369;'; break;
		case 'PLN' : $currency_symbol = '&#122;&#322;'; break;
		case 'SEK' : $currency_symbol = '&#107;&#114;'; break;
		case 'CHF' : $currency_symbol = '&#67;&#72;&#70;'; break;
		case 'TWD' : $currency_symbol = '&#78;&#84;&#36;'; break;
		case 'THB' : $currency_symbol = '&#3647;'; break;
		case 'GBP' : $currency_symbol = '&pound;'; break;
		default    : $currency_symbol = '&pound;'; break;
	endswitch;
	return apply_filters( 'woocommerce_currency_symbol', $currency_symbol, $currency );
}

/**
 * Price Formatting Helper
 **/
function woocommerce_price( $price, $args = array() ) {
	global $woocommerce;
	
	extract(shortcode_atts(array(
		'ex_tax_label' 	=> '0'
	), $args));
	
	$return = '';
	$num_decimals = (int) get_option('woocommerce_price_num_decimals');
	$currency_pos = get_option('woocommerce_currency_pos');
	$currency_symbol = get_woocommerce_currency_symbol();
	
	$price = apply_filters( 'raw_woocommerce_price', (double) $price );
	
	$price = number_format( $price, $num_decimals, stripslashes(get_option('woocommerce_price_decimal_sep')), stripslashes(get_option('woocommerce_price_thousand_sep')) );
	
	if (get_option('woocommerce_price_trim_zeros')=='yes' && $num_decimals>0) 
		$price = woocommerce_trim_zeros($price);
	
	switch ($currency_pos) :
		case 'left' :
			$return = '<span class="amount">'. $currency_symbol . $price . '</span>';
		break;
		case 'right' :
			$return = '<span class="amount">'. $price . $currency_symbol . '</span>';
		break;
		case 'left_space' :
			$return = '<span class="amount">'. $currency_symbol . '&nbsp;' . $price . '</span>';
		break;
		case 'right_space' :
			$return = '<span class="amount">'. $price . '&nbsp;' . $currency_symbol . '</span>';
		break;
	endswitch;

	if ($ex_tax_label && get_option('woocommerce_calc_taxes')=='yes') $return .= ' <small>'.$woocommerce->countries->ex_tax_or_vat().'</small>';
	
	return $return;
}	
	
/**
 * Trim trailing zeros off prices
 **/
function woocommerce_trim_zeros( $price ) {
	return preg_replace('/'.preg_quote(get_option('woocommerce_price_decimal_sep'), '/').'0++$/', '', $price);
}

/**
 * Formal decimal numbers - format to 4 dp and remove trailing zeros
 **/
function woocommerce_format_decimal( $number ) {
	return rtrim( rtrim( number_format( $number, 4, '.', '' ), '0' ), '.' );
}

/**
 * Clean variables
 **/
function woocommerce_clean( $var ) {
	return trim(strip_tags(stripslashes($var)));
}

/**
 * Merge two arrays
 **/
function woocommerce_array_overlay($a1,$a2) {
    foreach($a1 as $k => $v) {
        if(!array_key_exists($k,$a2)) continue;
        if(is_array($v) && is_array($a2[$k])){
            $a1[$k] = woocommerce_array_overlay($v,$a2[$k]);
        }else{
            $a1[$k] = $a2[$k];
        }
    }
    return $a1;
}

/**
 * Get top term
 * http://wordpress.stackexchange.com/questions/24794/get-the-the-top-level-parent-of-a-custom-taxonomy-term
 **/
function woocommerce_get_term_top_most_parent($term_id, $taxonomy) {
    // start from the current term
    $parent  = get_term_by( 'id', $term_id, $taxonomy);
    // climb up the hierarchy until we reach a term with parent = '0'
    while ($parent->parent != '0'){
        $term_id = $parent->parent;
        $parent  = get_term_by( 'id', $term_id, $taxonomy);
    }
    return $parent;
}

/**
 * Variation Formatting
 *
 * Gets a formatted version of variation data or item meta
 **/
function woocommerce_get_formatted_variation( $variation = '', $flat = false ) {
	global $woocommerce;

	if (is_array($variation)) :

		if (!$flat) $return = '<dl class="variation">'; else $return = '';

		$variation_list = array();

		foreach ($variation as $name => $value) :

			if (!$value) continue;

			// If this is a term slug, get the term's nice name
            if (taxonomy_exists(esc_attr(str_replace('attribute_', '', $name)))) :
            	$term = get_term_by('slug', $value, esc_attr(str_replace('attribute_', '', $name)));
            	if (!is_wp_error($term) && $term->name) :
            		$value = $term->name;
            	endif;
            else :
            	$value = ucfirst($value);
            endif;

			if ($flat) :
				$variation_list[] = $woocommerce->attribute_label(str_replace('attribute_', '', $name)).': '.$value;
			else :
				$variation_list[] = '<dt>'.$woocommerce->attribute_label(str_replace('attribute_', '', $name)).':</dt><dd>'.$value.'</dd>';
			endif;

		endforeach;

		if ($flat) :
			$return .= implode(', ', $variation_list);
		else :
			$return .= implode('', $variation_list);
		endif;

		if (!$flat) $return .= '</dl>';

		return $return;

	endif;
}

/**
 * Hex darker/lighter/contrast functions for colours
 **/
if ( ! function_exists( 'woocommerce_rgb_from_hex' ) ) {
	function woocommerce_rgb_from_hex( $color ) {
		$color = str_replace( '#', '', $color );
		// Convert shorthand colors to full format, e.g. "FFF" -> "FFFFFF"
		$color = preg_replace( '~^(.)(.)(.)$~', '$1$1$2$2$3$3', $color );
	
		$rgb['R'] = hexdec( $color{0}.$color{1} );
		$rgb['G'] = hexdec( $color{2}.$color{3} );
		$rgb['B'] = hexdec( $color{4}.$color{5} );
		return $rgb;
	}
}

if ( ! function_exists( 'woocommerce_hex_darker' ) ) {
	function woocommerce_hex_darker( $color, $factor = 30 ) {
		$base = woocommerce_rgb_from_hex( $color );
		$color = '#';

		foreach ($base as $k => $v) :
	        $amount = $v / 100;
	        $amount = round($amount * $factor);
	        $new_decimal = $v - $amount;
	
	        $new_hex_component = dechex($new_decimal);
	        if(strlen($new_hex_component) < 2) :
	        	$new_hex_component = "0".$new_hex_component;
	        endif;
	        $color .= $new_hex_component;
		endforeach;
		        
		return $color;        
	}
}
if ( ! function_exists( 'woocommerce_hex_lighter' ) ) {
	function woocommerce_hex_lighter( $color, $factor = 30 ) {
		$base = woocommerce_rgb_from_hex( $color );
		$color = '#';

	    foreach ($base as $k => $v) :
	        $amount = 255 - $v; 
	        $amount = $amount / 100; 
	        $amount = round($amount * $factor); 
	        $new_decimal = $v + $amount; 
	     
	        $new_hex_component = dechex($new_decimal); 
	        if(strlen($new_hex_component) < 2) :
	        	$new_hex_component = "0".$new_hex_component;
	        endif;
	        $color .= $new_hex_component; 
	   	endforeach;
	         
	   	return $color;          
	}
}

/**
 * Detect if we should use a light or dark colour on a background colour
 **/
if ( ! function_exists( 'woocommerce_light_or_dark' ) ) {
	function woocommerce_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {
	    //return ( hexdec( $color ) > 0xffffff / 2 ) ? $dark : $light;
	    $hex = str_replace( '#', '', $color );

		$c_r = hexdec( substr( $hex, 0, 2 ) );
		$c_g = hexdec( substr( $hex, 2, 2 ) );
		$c_b = hexdec( substr( $hex, 4, 2 ) );
		$brightness = ( ( $c_r * 299 ) + ( $c_g * 587 ) + ( $c_b * 114 ) ) / 1000;
		
		return $brightness > 155 ? $dark : $light;
	}
}

/**
 * Format string as hex
 **/
if ( ! function_exists( 'woocommerce_format_hex' ) ) {
	function woocommerce_format_hex( $hex ) {
	    
	    $hex = trim( str_replace( '#', '', $hex ) );
	    
	    if ( strlen( $hex ) == 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	    }
	    
	    if ( $hex ) return '#' . $hex;
	}
}

/**
 * Exclude order comments from queries and RSS
 *
 * This code should exclude shop_order comments from queries. Some queries (like the recent comments widget on the dashboard) are hardcoded
 * and are not filtered, however, the code current_user_can( 'read_post', $comment->comment_post_ID ) should keep them safe since only admin and
 * shop managers can view orders anyway.
 *
 * The frontend view order pages get around this filter by using remove_filter('comments_clauses', 'woocommerce_exclude_order_comments');
 **/
add_filter( 'comments_clauses', 'woocommerce_exclude_order_comments', 10, 1);
add_action( 'comment_feed_join', 'woocommerce_exclude_order_comments_from_feed_join' );
add_action( 'comment_feed_where', 'woocommerce_exclude_order_comments_from_feed_where' );

function woocommerce_exclude_order_comments( $clauses ) {
	global $wpdb, $typenow, $pagenow;
	
	if ( is_admin() && ( $typenow == 'shop_order' || $pagenow == 'edit-comments.php' ) && current_user_can( 'manage_woocommerce' ) ) 
		return $clauses; // Don't hide when viewing orders in admin
	
	if ( ! $clauses['join'] )
		$clauses['join'] = '';
	
	if ( ! strstr( $clauses['join'], "JOIN $wpdb->posts" ) )
		$clauses['join'] .= " LEFT JOIN $wpdb->posts ON $wpdb->comments.comment_post_ID = $wpdb->posts.ID ";
	
	if ( $clauses['where'] ) 
		$clauses['where'] .= ' AND ';
	
	$clauses['where'] .= " $wpdb->posts.post_type NOT IN ('shop_order') ";
	
	return $clauses;	
}
function woocommerce_exclude_order_comments_from_feed_join( $join ) {
	global $wpdb;
	
    if ( ! $join ) 
    	$join = " LEFT JOIN $wpdb->posts ON $wpdb->comments.comment_post_ID = $wpdb->posts.ID ";

    return $join;
}
function woocommerce_exclude_order_comments_from_feed_where( $where ) {
	global $wpdb;

    if ( $where ) 
    	$where .= ' AND ';
	
	$where .= " $wpdb->posts.post_type NOT IN ('shop_order') ";
    
    return $where;
}

/**
 * Order Status completed - GIVE DOWNLOADABLE PRODUCT ACCESS TO CUSTOMER
 **/
add_action('woocommerce_order_status_completed', 'woocommerce_downloadable_product_permissions');

if (get_option('woocommerce_downloads_grant_access_after_payment')=='yes') add_action('woocommerce_order_status_processing', 'woocommerce_downloadable_product_permissions');

function woocommerce_downloadable_product_permissions( $order_id ) {
	global $wpdb;
	
	if (get_post_meta( $order_id, __('Download Permissions Granted', 'woocommerce'), true)==1) return; // Only do this once
	
	$order = new WC_Order( $order_id );
	
	if (sizeof($order->get_items())>0) foreach ($order->get_items() as $item) :
	
		if ($item['id']>0) :
			$_product = $order->get_product_from_item( $item );
			
			if ( $_product->exists() && $_product->is_downloadable() ) :
			
				$download_id = ($item['variation_id']>0) ? $item['variation_id'] : $item['id'];
				
				$user_email = $order->billing_email;
				
				$limit = trim(get_post_meta($download_id, '_download_limit', true));
				$expiry = trim(get_post_meta($download_id, '_download_expiry', true));
				
                $limit = (empty($limit)) ? '' : (int) $limit;

                // Default value is NULL in the table schema
				$expiry = (empty($expiry)) ? null : (int) $expiry;
				
				if ($expiry) $expiry = date("Y-m-d", strtotime('NOW + ' . $expiry . ' DAY'));

                $data = array(
					'product_id' 			=> $download_id,
					'user_id' 				=> $order->user_id,
					'user_email' 			=> $user_email,
					'order_id' 				=> $order->id,
					'order_key' 			=> $order->order_key,
					'downloads_remaining' 	=> $limit,
					'access_granted'		=> current_time('mysql'),
					'download_count'		=> 0
                );

                $format = array(
					'%s', 
					'%s', 
					'%s', 
					'%s', 
					'%s',
					'%s',
					'%s',
					'%d'
                );

                if ( ! is_null($expiry)) {
                    $data['access_expires'] = $expiry;
                    $format[] = '%s';
                }

				// Downloadable product - give access to the customer
                $wpdb->insert( $wpdb->prefix . 'woocommerce_downloadable_product_permissions', 
                    $data,
                    $format
                );	
				
			endif;
			
		endif;
	
	endforeach;
	
	update_post_meta( $order_id,  __('Download Permissions Granted', 'woocommerce'), 1);
}

/**
 * Order Status completed - This is a paying customer
 **/
add_action('woocommerce_order_status_completed', 'woocommerce_paying_customer');

function woocommerce_paying_customer( $order_id ) {
	
	$order = new WC_Order( $order_id );
	
	if ( $order->user_id > 0 ) update_user_meta( $order->user_id, 'paying_customer', 1 );
}

/**
 * Filter to allow product_cat in the permalinks for products.
 *
 * @param string $permalink The existing permalink URL.
 */
add_filter( 'post_type_link', 'woocommerce_product_cat_filter_post_link', 10, 4 );

function woocommerce_product_cat_filter_post_link( $permalink, $post, $leavename, $sample ) {
    // Abort if post is not a product
    if ($post->post_type!=='product') return $permalink;
    
    // Abort early if the placeholder rewrite tag isn't in the generated URL
    if ( false === strpos( $permalink, '%product_cat%' ) ) return $permalink;

    // Get the custom taxonomy terms in use by this post
    $terms = get_the_terms( $post->ID, 'product_cat' );

    if ( empty( $terms ) ) :
    	// If no terms are assigned to this post, use a string instead (can't leave the placeholder there)
        $permalink = str_replace( '%product_cat%', _x('product', 'slug', 'woocommerce'), $permalink );
    else :
    	// Replace the placeholder rewrite tag with the first term's slug
        $first_term = array_shift( $terms );
        $permalink = str_replace( '%product_cat%', $first_term->slug, $permalink );
    endif;

    return $permalink;
}

/**
 * Add term ordering to get_terms
 * 
 * It enables the support a 'menu_order' parameter to get_terms for the product_cat taxonomy.
 * By default it is 'ASC'. It accepts 'DESC' too
 * 
 * To disable it, set it ot false (or 0)
 * 
 */
add_filter( 'terms_clauses', 'woocommerce_terms_clauses', 10, 3);

function woocommerce_terms_clauses($clauses, $taxonomies, $args ) {
	global $wpdb, $woocommerce;

	// No sorting when menu_order is false
	if ( isset($args['menu_order']) && $args['menu_order'] == false ) return $clauses;
	
	// No sorting when orderby is non default
	if ( isset($args['orderby']) && $args['orderby'] != 'name' ) return $clauses;
	
	// No sorting in admin when sorting by a column
	if ( isset($_GET['orderby']) ) return $clauses;

	// wordpress should give us the taxonomies asked when calling the get_terms function. Only apply to categories and pa_ attributes
	$found = false;
	foreach ((array) $taxonomies as $taxonomy) :
		if ( strstr($taxonomy, 'pa_') || in_array( $taxonomy, apply_filters( 'woocommerce_sortable_taxonomies', array( 'product_cat' ) ) ) ) :
			$found = true;
			break;
		endif;
	endforeach;
	if (!$found) return $clauses;
	
	// Meta name
	if ( ! empty( $taxonomies[0] ) && strstr($taxonomies[0], 'pa_') ) {
		$meta_name =  'order_' . esc_attr($taxonomies[0]);
	} else {
		$meta_name = 'order';
	}

	// query fields
	if( strpos('COUNT(*)', $clauses['fields']) === false ) $clauses['fields']  .= ', tm.* ';

	//query join
	$clauses['join'] .= " LEFT JOIN {$wpdb->woocommerce_termmeta} AS tm ON (t.term_id = tm.woocommerce_term_id AND tm.meta_key = '". $meta_name ."') ";
	
	// default to ASC
	if( ! isset($args['menu_order']) || ! in_array( strtoupper($args['menu_order']), array('ASC', 'DESC')) ) $args['menu_order'] = 'ASC';

	$order = "ORDER BY CAST(tm.meta_value AS SIGNED) " . $args['menu_order'];
	
	if ( $clauses['orderby'] ):
		$clauses['orderby'] = str_replace('ORDER BY', $order . ',', $clauses['orderby'] );
	else:
		$clauses['orderby'] = $order;
	endif;
	
	return $clauses;
}

/**
 * woocommerce_get_product_terms function.
 *
 * Gets product terms in the order they are defined in the backend.
 * 
 * @access public
 * @param mixed $object_id
 * @param mixed $taxonomy
 * @param mixed $fields ids, names, slugs, all
 * @return array
 */
function woocommerce_get_product_terms( $object_id, $taxonomy, $fields = 'all' ) {
	
	if ( ! taxonomy_exists( $taxonomy ) ) 
		return array();
	
	$terms 			= array();
	$object_terms 	= wp_get_object_terms( $object_id, $taxonomy );
	$all_terms 		= array_flip( get_terms( $taxonomy, array( 'menu_order' => 'ASC', 'fields' => 'ids' ) ) );
	
	switch ( $fields ) {
		case 'names' :
			foreach ( $object_terms as $term )
				$terms[ $all_terms[ $term->term_id ] ] = $term->name;
			break;
		case 'ids' :
			foreach ( $object_terms as $term )
				$terms[ $all_terms[ $term->term_id ] ] = $term->term_id;
			break;
		case 'slugs' :
			foreach ( $object_terms as $term )
				$terms[ $all_terms[ $term->term_id ] ] = $term->slug;
			break;
		case 'all' :
			foreach ( $object_terms as $term )
				$terms[ $all_terms[ $term->term_id ] ] = $term;
			break;
	}
	
	ksort( $terms );
	
	return $terms;
}

/**
 * WooCommerce Dropdown categories
 * 
 * Stuck with this until a fix for http://core.trac.wordpress.org/ticket/13258
 * We use a custom walker, just like WordPress does
 */
function woocommerce_product_dropdown_categories( $show_counts = 1, $hierarchal = 1, $show_uncategorized = 1 ) {
	global $wp_query, $woocommerce;
	
	include_once( $woocommerce->plugin_path() . '/classes/walkers/class-product-cat-dropdown-walker.php' );
	
	$r = array();
	$r['pad_counts'] 	= 1;
	$r['hierarchal'] 	= $hierarchal;
	$r['hide_empty'] 	= 1;
	$r['show_count'] 	= 1;
	$r['selected'] 		= ( isset( $wp_query->query['product_cat'] ) ) ? $wp_query->query['product_cat'] : '';
	
	$terms = get_terms( 'product_cat', $r );
	
	if (!$terms) return;
	
	$output  = "<select name='product_cat' id='dropdown_product_cat'>";
	$output .= '<option value="" ' .  selected( isset( $_GET['product_cat'] ) ? $_GET['product_cat'] : '', '', false ) . '>'.__('Select a category', 'woocommerce').'</option>';
	$output .= woocommerce_walk_category_dropdown_tree( $terms, 0, $r );
	
	if ( $show_uncategorized )
		$output .= '<option value="0" ' . selected( isset( $_GET['product_cat'] ) ? $_GET['product_cat'] : '', '0', false ) . '>' . __('Uncategorized', 'woocommerce') . '</option>';
	
	$output .="</select>";
	
	echo $output;
}

/**
 * Walk the Product Categories.
 */
function woocommerce_walk_category_dropdown_tree() {
	$args = func_get_args();
	
	// the user's options are the third parameter
	if ( empty($args[2]['walker']) || !is_a($args[2]['walker'], 'Walker') )
		$walker = new WC_Product_Cat_Dropdown_Walker;
	else
		$walker = $args[2]['walker'];

	return call_user_func_array(array( &$walker, 'walk' ), $args );
}

/**
 * WooCommerce Term Meta API
 * 
 * API for working with term meta data. Adapted from 'Term meta API' by Nikolay Karev
 * 
 */
add_action( 'init', 'woocommerce_taxonomy_metadata_wpdbfix', 0 );
add_action( 'switch_blog', 'woocommerce_taxonomy_metadata_wpdbfix', 0 );

function woocommerce_taxonomy_metadata_wpdbfix() {
	global $wpdb;
	$variable_name = 'woocommerce_termmeta';
	$wpdb->$variable_name = $wpdb->prefix . $variable_name;	
	$wpdb->tables[] = $variable_name;
} 

function update_woocommerce_term_meta($term_id, $meta_key, $meta_value, $prev_value = ''){
	return update_metadata('woocommerce_term', $term_id, $meta_key, $meta_value, $prev_value);
}

function add_woocommerce_term_meta($term_id, $meta_key, $meta_value, $unique = false){
	return add_metadata('woocommerce_term', $term_id, $meta_key, $meta_value, $unique);
}

function delete_woocommerce_term_meta($term_id, $meta_key, $meta_value = '', $delete_all = false){
	return delete_metadata('woocommerce_term', $term_id, $meta_key, $meta_value, $delete_all);
}

function get_woocommerce_term_meta($term_id, $key, $single = true){
	return get_metadata('woocommerce_term', $term_id, $key, $single);
}

/**
 * Move a term before the a	given element of its hierarchy level
 *
 * @param object $the_term
 * @param int $next_id the id of the next slibling element in save hierachy level
 * @param int $index
 * @param int $terms
 */
function woocommerce_order_terms( $the_term, $next_id, $taxonomy, $index=0, $terms=null ) {
	
	if( ! $terms ) $terms = get_terms($taxonomy, 'menu_order=ASC&hide_empty=0&parent=0');
	if( empty( $terms ) ) return $index;
	
	$id	= $the_term->term_id;
	
	$term_in_level = false; // flag: is our term to order in this level of terms
	
	foreach ($terms as $term) {
		
		if( $term->term_id == $id ) { // our term to order, we skip
			$term_in_level = true;
			continue; // our term to order, we skip
		}
		// the nextid of our term to order, lets move our term here
		if(null !== $next_id && $term->term_id == $next_id) { 
			$index++;
			$index = woocommerce_set_term_order($id, $index, $taxonomy, true);
		}		
		
		// set order
		$index++;
		$index = woocommerce_set_term_order($term->term_id, $index, $taxonomy);
		
		// if that term has children we walk through them
		$children = get_terms($taxonomy, "parent={$term->term_id}&menu_order=ASC&hide_empty=0");
		if( !empty($children) ) {
			$index = woocommerce_order_terms( $the_term, $next_id, $taxonomy, $index, $children );	
		}
	}

	// no nextid meaning our term is in last position
	if( $term_in_level && null === $next_id )
		$index = woocommerce_set_term_order($id, $index+1, $taxonomy, true);

	wp_cache_flush();
	
	return $index;
}

/**
 * Set the sort order of a term
 * 
 * @param int $term_id
 * @param int $index
 * @param bool $recursive
 */
function woocommerce_set_term_order($term_id, $index, $taxonomy, $recursive=false) {
	global $wpdb;
	
	$term_id 	= (int) $term_id;
	$index 		= (int) $index;
	
	// Meta name
	if (strstr($taxonomy, 'pa_')) :
		$meta_name =  'order_' . esc_attr($taxonomy);
	else :
		$meta_name = 'order';
	endif;
	
	update_woocommerce_term_meta( $term_id, $meta_name, $index );
	
	if( ! $recursive ) return $index;
	
	$children = get_terms($taxonomy, "parent=$term_id&menu_order=ASC&hide_empty=0");

	foreach ( $children as $term ) {
		$index ++;
		$index = woocommerce_set_term_order($term->term_id, $index, $taxonomy, true);		
	}
	
	return $index;
}


/**
 * let_to_num function.
 * 
 * This function transforms the php.ini notation for numbers (like '2M') to an integer
 *
 * @access public
 * @param $size
 * @return int
 */
function woocommerce_let_to_num( $size ) {
    $l 		= substr( $size, -1 );
    $ret 	= substr( $size, 0, -1 );
    switch( strtoupper( $l ) ) {
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
    }
    return $ret;
}

/**
 * woocommerce_customer_bought_product
 *
 * Checks if a user (by email) has bought an item
 **/
function woocommerce_customer_bought_product( $customer_email, $user_id, $product_id ) {
	global $wpdb;
	
	$emails = array();
	
	if ( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		$emails[] = $user->user_email;
	}
	
	if ( is_email( $customer_email ) ) 
		$emails[] = $customer_email;
	
	if ( sizeof( $emails ) == 0 )
		return false;
	
	$orders = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE ( meta_key = '_billing_email' AND meta_value IN ( '" . implode( "','", array_unique( $emails ) ) . "' ) ) OR ( meta_key = '_customer_user' AND meta_value = %s AND meta_value > 0 )", $user_id ) );
	
	foreach ( $orders as $order_id ) {
		
		$items = maybe_unserialize( get_post_meta( $order_id, '_order_items', true ) );
		
		if ( $items )
			foreach ( $items as $item )
				if ( $item['id'] == $product_id || $item['variation_id'] == $product_id )
					return true;
			
	}
}
