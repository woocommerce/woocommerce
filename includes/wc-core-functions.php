<?php
/**
 * WooCommerce Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include core functions (available in both admin and frontend)
include( 'wc-conditional-functions.php' );
include( 'wc-coupon-functions.php' );
include( 'wc-user-functions.php' );
include( 'wc-deprecated-functions.php' );
include( 'wc-formatting-functions.php' );
include( 'wc-order-functions.php' );
include( 'wc-page-functions.php' );
include( 'wc-product-functions.php' );
include( 'wc-term-functions.php' );
include( 'wc-attribute-functions.php' );

/**
 * Filters on data used in admin and frontend
 */
add_filter( 'woocommerce_coupon_code', 'html_entity_decode' );
add_filter( 'woocommerce_coupon_code', 'sanitize_text_field' );
add_filter( 'woocommerce_coupon_code', 'strtolower' ); // Coupons case-insensitive by default
add_filter( 'woocommerce_stock_amount', 'intval' ); // Stock amounts are integers by default

/**
 * Short Description (excerpt)
 */
add_filter( 'woocommerce_short_description', 'wptexturize' );
add_filter( 'woocommerce_short_description', 'convert_smilies' );
add_filter( 'woocommerce_short_description', 'convert_chars' );
add_filter( 'woocommerce_short_description', 'wpautop' );
add_filter( 'woocommerce_short_description', 'shortcode_unautop' );
add_filter( 'woocommerce_short_description', 'prepend_attachment' );
add_filter( 'woocommerce_short_description', 'do_shortcode', 11 ); // AFTER wpautop()

/**
 * Create a new order programmatically
 *
 * Returns a new order object on success which can then be used to add additional data.
 *
 * @return WC_Order on success, WP_Error on failure
 */
function wc_create_order( $args = array() ) {
	$default_args = array(
		'status'        => '',
		'customer_id'   => null,
		'customer_note' => null,
		'order_id'      => 0
	);

	$args       = wp_parse_args( $args, $default_args );
	$order_data = array();

	if ( $args['order_id'] > 0 ) {
		$updating         = true;
		$order_data['ID'] = $args['order_id'];
	} else {
		$updating                    = false;
		$order_data['post_type']     = 'shop_order';
		$order_data['post_status']   = 'wc-' . apply_filters( 'woocommerce_default_order_status', 'pending' );
		$order_data['ping_status']   = 'closed';
		$order_data['post_author']   = 1;
		$order_data['post_password'] = uniqid( 'order_' );
		$order_data['post_title']    = sprintf( __( 'Order &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ) ) );
	}

	if ( $args['status'] ) {
		if ( ! in_array( 'wc-' . $args['status'], array_keys( wc_get_order_statuses() ) ) ) {
			return new WP_Error( 'woocommerce_invalid_order_status', __( 'Invalid order status', 'woocommerce' ) );
		}
		$order_data['post_status']  = 'wc-' . $args['status'];
	}

	if ( ! is_null( $args['customer_note'] ) ) {
		$order_data['post_excerpt'] = $args['customer_note'];
	}

	if ( $updating ) {
		$order_id = wp_update_post( $order_data );
	} else {
		$order_id = wp_insert_post( apply_filters( 'woocommerce_new_order_data', $order_data ), true );
	}

	if ( is_wp_error( $order_id ) ) {
		return $order_id;
	}

	// Default order meta data.
	if ( ! $updating ) {
		update_post_meta( $order_id, '_order_key', 'wc_' . apply_filters( 'woocommerce_generate_order_key', uniqid( 'order_' ) ) );
		update_post_meta( $order_id, '_order_currency', get_woocommerce_currency() );
		update_post_meta( $order_id, '_prices_include_tax', get_option( 'woocommerce_prices_include_tax' ) );
		update_post_meta( $order_id, '_customer_ip_address', isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] );
		update_post_meta( $order_id, '_customer_user_agent', isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' );
		update_post_meta( $order_id, '_customer_user', 0 );
	}

	if ( is_numeric( $args['customer_id'] ) ) {
		update_post_meta( $order_id, '_customer_user', $args['customer_id'] );
	}

	return new WC_Order( $order_id );
}

/**
 * Update an order. Uses wc_create_order.
 * @param  array $args
 * @return string | WC_Order
 */
function wc_update_order( $args ) {
	if ( ! $args['order_id'] ) {
		return new WP_Error( __( 'Invalid order ID', 'woocommerce' ) );
	}
	return wc_create_order( $args );
}

/**
 * Get template part (for templates like the shop-loop).
 *
 * @access public
 * @param mixed $slug
 * @param string $name (default: '')
 * @return void
 */
function wc_get_template_part( $slug, $name = '' ) {
	$template = '';

	// Look in yourtheme/slug-name.php and yourtheme/woocommerce/slug-name.php
	if ( $name && ! WC_TEMPLATE_DEBUG_MODE ) {
		$template = locate_template( array( "{$slug}-{$name}.php", WC()->template_path() . "{$slug}-{$name}.php" ) );
	}

	// Get default slug-name.php
	if ( ! $template && $name && file_exists( WC()->plugin_path() . "/templates/{$slug}-{$name}.php" ) ) {
		$template = WC()->plugin_path() . "/templates/{$slug}-{$name}.php";
	}

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/woocommerce/slug.php
	if ( ! $template && ! WC_TEMPLATE_DEBUG_MODE ) {
		$template = locate_template( array( "{$slug}.php", WC()->template_path() . "{$slug}.php" ) );
	}

	// Allow 3rd party plugin filter template file from their plugin
	if ( ( ! $template && WC_TEMPLATE_DEBUG_MODE ) || $template ) {
		$template = apply_filters( 'wc_get_template_part', $template, $slug, $name );
	}

	if ( $template ) {
		load_template( $template, false );
	}
}

/**
 * Get other templates (e.g. product attributes) passing attributes and including the file.
 *
 * @access public
 * @param string $template_name
 * @param array $args (default: array())
 * @param string $template_path (default: '')
 * @param string $default_path (default: '')
 * @return void
 */
function wc_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	if ( $args && is_array( $args ) ) {
		extract( $args );
	}

	$located = wc_locate_template( $template_name, $template_path, $default_path );

	if ( ! file_exists( $located ) ) {
		_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );
		return;
	}

	// Allow 3rd party plugin filter template file from their plugin
	$located = apply_filters( 'wc_get_template', $located, $template_name, $args, $template_path, $default_path );

	do_action( 'woocommerce_before_template_part', $template_name, $template_path, $located, $args );

	include( $located );

	do_action( 'woocommerce_after_template_part', $template_name, $template_path, $located, $args );
}

/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 *		yourtheme		/	$template_path	/	$template_name
 *		yourtheme		/	$template_name
 *		$default_path	/	$template_name
 *
 * @access public
 * @param string $template_name
 * @param string $template_path (default: '')
 * @param string $default_path (default: '')
 * @return string
 */
function wc_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = WC()->template_path();
	}

	if ( ! $default_path ) {
		$default_path = WC()->plugin_path() . '/templates/';
	}

	// Look within passed path within the theme - this is priority
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name
		)
	);

	// Get default template
	if ( ! $template || WC_TEMPLATE_DEBUG_MODE ) {
		$template = $default_path . $template_name;
	}

	// Return what we found
	return apply_filters( 'woocommerce_locate_template', $template, $template_name, $template_path );
}

/**
 * Get Base Currency Code.
 * @return string
 */
function get_woocommerce_currency() {
	return apply_filters( 'woocommerce_currency', get_option('woocommerce_currency') );
}

/**
 * Get full list of currency codes.
 * @return array
 */
function get_woocommerce_currencies() {
	return array_unique(
		apply_filters( 'woocommerce_currencies',
			array(
				'AED' => __( 'United Arab Emirates Dirham', 'woocommerce' ),
				'AUD' => __( 'Australian Dollars', 'woocommerce' ),
				'BDT' => __( 'Bangladeshi Taka', 'woocommerce' ),
				'BRL' => __( 'Brazilian Real', 'woocommerce' ),
				'BGN' => __( 'Bulgarian Lev', 'woocommerce' ),
				'CAD' => __( 'Canadian Dollars', 'woocommerce' ),
				'CLP' => __( 'Chilean Peso', 'woocommerce' ),
				'CNY' => __( 'Chinese Yuan', 'woocommerce' ),
				'COP' => __( 'Colombian Peso', 'woocommerce' ),
				'CZK' => __( 'Czech Koruna', 'woocommerce' ),
				'DKK' => __( 'Danish Krone', 'woocommerce' ),
				'DOP' => __( 'Dominican Peso', 'woocommerce' ),
				'EUR' => __( 'Euros', 'woocommerce' ),
				'HKD' => __( 'Hong Kong Dollar', 'woocommerce' ),
				'HRK' => __( 'Croatia kuna', 'woocommerce' ),
				'HUF' => __( 'Hungarian Forint', 'woocommerce' ),
				'ISK' => __( 'Icelandic krona', 'woocommerce' ),
				'IDR' => __( 'Indonesia Rupiah', 'woocommerce' ),
				'INR' => __( 'Indian Rupee', 'woocommerce' ),
				'NPR' => __( 'Nepali Rupee', 'woocommerce' ),
				'ILS' => __( 'Israeli Shekel', 'woocommerce' ),
				'JPY' => __( 'Japanese Yen', 'woocommerce' ),
				'KIP' => __( 'Lao Kip', 'woocommerce' ),
				'KRW' => __( 'South Korean Won', 'woocommerce' ),
				'MYR' => __( 'Malaysian Ringgits', 'woocommerce' ),
				'MXN' => __( 'Mexican Peso', 'woocommerce' ),
				'NGN' => __( 'Nigerian Naira', 'woocommerce' ),
				'NOK' => __( 'Norwegian Krone', 'woocommerce' ),
				'NZD' => __( 'New Zealand Dollar', 'woocommerce' ),
				'PYG' => __( 'Paraguayan Guaraní', 'woocommerce' ),
				'PHP' => __( 'Philippine Pesos', 'woocommerce' ),
				'PLN' => __( 'Polish Zloty', 'woocommerce' ),
				'GBP' => __( 'Pounds Sterling', 'woocommerce' ),
				'RON' => __( 'Romanian Leu', 'woocommerce' ),
				'RUB' => __( 'Russian Ruble', 'woocommerce' ),
				'SGD' => __( 'Singapore Dollar', 'woocommerce' ),
				'ZAR' => __( 'South African rand', 'woocommerce' ),
				'SEK' => __( 'Swedish Krona', 'woocommerce' ),
				'CHF' => __( 'Swiss Franc', 'woocommerce' ),
				'TWD' => __( 'Taiwan New Dollars', 'woocommerce' ),
				'THB' => __( 'Thai Baht', 'woocommerce' ),
				'TRY' => __( 'Turkish Lira', 'woocommerce' ),
				'UAH' => __( 'Ukrainian Hryvnia', 'woocommerce' ),
				'USD' => __( 'US Dollars', 'woocommerce' ),
				'VND' => __( 'Vietnamese Dong', 'woocommerce' ),
				'EGP' => __( 'Egyptian Pound', 'woocommerce' ),
			)
		)
	);
}

/**
 * Get Currency symbol.
 * @param string $currency (default: '')
 * @return string
 */
function get_woocommerce_currency_symbol( $currency = '' ) {
	if ( ! $currency ) {
		$currency = get_woocommerce_currency();
	}

	switch ( $currency ) {
		case 'AED' :
			$currency_symbol = 'د.إ';
			break;
		case 'AUD' :
		case 'CAD' :
		case 'CLP' :
		case 'COP' :
		case 'HKD' :
		case 'MXN' :
		case 'NZD' :
		case 'SGD' :
		case 'USD' :
			$currency_symbol = '&#36;';
			break;
		case 'BDT':
			$currency_symbol = '&#2547;&nbsp;';
			break;
		case 'BGN' :
			$currency_symbol = '&#1083;&#1074;.';
			break;
		case 'BRL' :
			$currency_symbol = '&#82;&#36;';
			break;
		case 'CHF' :
			$currency_symbol = '&#67;&#72;&#70;';
			break;
		case 'CNY' :
		case 'JPY' :
		case 'RMB' :
			$currency_symbol = '&yen;';
			break;
		case 'CZK' :
			$currency_symbol = '&#75;&#269;';
			break;
		case 'DKK' :
			$currency_symbol = 'kr.';
			break;
		case 'DOP' :
			$currency_symbol = 'RD&#36;';
			break;
		case 'EGP' :
			$currency_symbol = 'EGP';
			break;
		case 'EUR' :
			$currency_symbol = '&euro;';
			break;
		case 'GBP' :
			$currency_symbol = '&pound;';
			break;
		case 'HRK' :
			$currency_symbol = 'Kn';
			break;
		case 'HUF' :
			$currency_symbol = '&#70;&#116;';
			break;
		case 'IDR' :
			$currency_symbol = 'Rp';
			break;
		case 'ILS' :
			$currency_symbol = '&#8362;';
			break;
		case 'INR' :
			$currency_symbol = 'Rs.';
			break;
		case 'ISK' :
			$currency_symbol = 'Kr.';
			break;
		case 'KIP' :
			$currency_symbol = '&#8365;';
			break;
		case 'KRW' :
			$currency_symbol = '&#8361;';
			break;
		case 'MYR' :
			$currency_symbol = '&#82;&#77;';
			break;
		case 'NGN' :
			$currency_symbol = '&#8358;';
			break;
		case 'NOK' :
			$currency_symbol = '&#107;&#114;';
			break;
		case 'NPR' :
			$currency_symbol = 'Rs.';
			break;
		case 'PHP' :
			$currency_symbol = '&#8369;';
			break;
		case 'PLN' :
			$currency_symbol = '&#122;&#322;';
			break;
		case 'PYG' :
			$currency_symbol = '&#8370;';
			break;
		case 'RON' :
			$currency_symbol = 'lei';
			break;
		case 'RUB' :
			$currency_symbol = '&#1088;&#1091;&#1073;.';
			break;
		case 'SEK' :
			$currency_symbol = '&#107;&#114;';
			break;
		case 'THB' :
			$currency_symbol = '&#3647;';
			break;
		case 'TRY' :
			$currency_symbol = '&#8378;';
			break;
		case 'TWD' :
			$currency_symbol = '&#78;&#84;&#36;';
			break;
		case 'UAH' :
			$currency_symbol = '&#8372;';
			break;
		case 'VND' :
			$currency_symbol = '&#8363;';
			break;
		case 'ZAR' :
			$currency_symbol = '&#82;';
			break;
		default :
			$currency_symbol = '';
			break;
	}

	return apply_filters( 'woocommerce_currency_symbol', $currency_symbol, $currency );
}

/**
 * Send HTML emails from WooCommerce
 *
 * @param mixed $to
 * @param mixed $subject
 * @param mixed $message
 * @param string $headers (default: "Content-Type: text/html\r\n")
 * @param string $attachments (default: "")
 */
function wc_mail( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = "" ) {
	$mailer = WC()->mailer();

	$mailer->send( $to, $subject, $message, $headers, $attachments );
}

/**
 * Get an image size.
 *
 * Variable is filtered by woocommerce_get_image_size_{image_size}
 *
 * @param mixed $image_size
 * @return array
 */
function wc_get_image_size( $image_size ) {
	if ( is_array( $image_size ) ) {
		$width  = isset( $image_size[0] ) ? $image_size[0] : '300';
		$height = isset( $image_size[1] ) ? $image_size[1] : '300';
		$crop   = isset( $image_size[2] ) ? $image_size[2] : 1;

		$size = array(
			'width'  => $width,
			'height' => $height,
			'crop'   => $crop
		);

		$image_size = $width . '_' . $height;

	} elseif ( in_array( $image_size, array( 'shop_thumbnail', 'shop_catalog', 'shop_single' ) ) ) {
		$size           = get_option( $image_size . '_image_size', array() );
		$size['width']  = isset( $size['width'] ) ? $size['width'] : '300';
		$size['height'] = isset( $size['height'] ) ? $size['height'] : '300';
		$size['crop']   = isset( $size['crop'] ) ? $size['crop'] : 0;

	} else {
		$size = array(
			'width'  => '300',
			'height' => '300',
			'crop'   => 1
		);
	}

	return apply_filters( 'woocommerce_get_image_size_' . $image_size, $size );
}

/**
 * Queue some JavaScript code to be output in the footer.
 *
 * @param string $code
 */
function wc_enqueue_js( $code ) {
	global $wc_queued_js;

	if ( empty( $wc_queued_js ) ) {
		$wc_queued_js = '';
	}

	$wc_queued_js .= "\n" . $code . "\n";
}

/**
 * Output any queued javascript code in the footer.
 */
function wc_print_js() {
	global $wc_queued_js;

	if ( ! empty( $wc_queued_js ) ) {

		echo "<!-- WooCommerce JavaScript -->\n<script type=\"text/javascript\">\njQuery(function($) {";

		// Sanitize
		$wc_queued_js = wp_check_invalid_utf8( $wc_queued_js );
		$wc_queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $wc_queued_js );
		$wc_queued_js = str_replace( "\r", '', $wc_queued_js );

		echo $wc_queued_js . "});\n</script>\n";

		unset( $wc_queued_js );
	}
}

/**
 * Set a cookie - wrapper for setcookie using WP constants
 *
 * @param  string  $name   Name of the cookie being set
 * @param  string  $value  Value of the cookie
 * @param  integer $expire Expiry of the cookie
 * @param  string  $secure Whether the cookie should be served only over https
 */
function wc_setcookie( $name, $value, $expire = 0, $secure = false ) {
	if ( ! headers_sent() ) {
		setcookie( $name, $value, $expire, COOKIEPATH, COOKIE_DOMAIN, $secure );
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		headers_sent( $file, $line );
		trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE );
	}
}

/**
 * Get the URL to the WooCommerce REST API
 *
 * @since 2.1
 * @param string $path an endpoint to include in the URL
 * @return string the URL
 */
function get_woocommerce_api_url( $path ) {

	$version = defined( 'WC_API_REQUEST_VERSION' ) ? WC_API_REQUEST_VERSION : WC_API::VERSION;

	$url = get_home_url( null, "wc-api/v{$version}/", is_ssl() ? 'https' : 'http' );

	if ( ! empty( $path ) && is_string( $path ) ) {
		$url .= ltrim( $path, '/' );
	}

	return $url;
}

/**
 * Get a log file path
 *
 * @since 2.2
 * @param string $handle name
 * @return string the log file path
 */
function wc_get_log_file_path( $handle ) {
	return trailingslashit( WC_LOG_DIR ) . $handle . '-' . sanitize_file_name( wp_hash( $handle ) ) . '.log';
}

/**
 * Init for our rewrite rule fixes
 */
function wc_fix_rewrite_rules_init() {
	$permalinks = get_option( 'woocommerce_permalinks' );

	if ( ! empty( $permalinks['use_verbose_page_rules'] ) ) {
		$GLOBALS['wp_rewrite']->use_verbose_page_rules = true;
	}
}
add_action( 'init', 'wc_fix_rewrite_rules_init' );

/**
 * Various rewrite rule fixes
 *
 * @since 2.2
 * @param array $rules
 * @return array
 */
function wc_fix_rewrite_rules( $rules ) {
	global $wp_rewrite;

	$permalinks        = get_option( 'woocommerce_permalinks' );
	$product_permalink = empty( $permalinks['product_base'] ) ? _x( 'product', 'slug', 'woocommerce' ) : $permalinks['product_base'];

	// Fix the rewrite rules when the product permalink have %product_cat% flag
	if ( preg_match( '`/(.+)(/%product_cat%)`' , $product_permalink, $matches ) ) {
		foreach ( $rules as $rule => $rewrite ) {

			if ( preg_match( '`^' . preg_quote( $matches[1], '`' ) . '/\(`', $rule ) && preg_match( '/^(index\.php\?product_cat)(?!(.*product))/', $rewrite ) ) {
				unset( $rules[ $rule ] );
			}
		}
	}

	// If the shop page is used as the base, we need to enable verbose rewrite rules or sub pages will 404
	if ( ! empty( $permalinks['use_verbose_page_rules'] ) ) {
		$page_rewrite_rules = $wp_rewrite->page_rewrite_rules();
		$rules              = array_merge( $page_rewrite_rules, $rules );
	}

	return $rules;
}
add_filter( 'rewrite_rules_array', 'wc_fix_rewrite_rules' );

/**
 * Prevent product attachment links from breaking when using complex rewrite structures.
 *
 * @param  string $link
 * @param  id $post_id
 * @return string
 */
function wc_fix_product_attachment_link( $link, $post_id ) {
	global $wp_rewrite;

	$post = get_post( $post_id );
	if ( 'product' === get_post_type( $post->post_parent ) ) {
		$permalinks        = get_option( 'woocommerce_permalinks' );
		$product_permalink = empty( $permalinks['product_base'] ) ? _x( 'product', 'slug', 'woocommerce' ) : $permalinks['product_base'];
		if ( preg_match( '/\/(.+)(\/%product_cat%)$/' , $product_permalink, $matches ) ) {
			$link = home_url( '/?attachment_id=' . $post->ID );
		}
	}
	return $link;
}
add_filter( 'attachment_link', 'wc_fix_product_attachment_link', 10, 2 );

/**
 * Protect downloads from ms-files.php in multisite
 *
 * @param mixed $rewrite
 * @return string
 */
function wc_ms_protect_download_rewite_rules( $rewrite ) {
	if ( ! is_multisite() || 'redirect' == get_option( 'woocommerce_file_download_method' ) ) {
		return $rewrite;
	}

	$rule  = "\n# WooCommerce Rules - Protect Files from ms-files.php\n\n";
	$rule .= "<IfModule mod_rewrite.c>\n";
	$rule .= "RewriteEngine On\n";
	$rule .= "RewriteCond %{QUERY_STRING} file=woocommerce_uploads/ [NC]\n";
	$rule .= "RewriteRule /ms-files.php$ - [F]\n";
	$rule .= "</IfModule>\n\n";

	return $rule . $rewrite;
}
add_filter( 'mod_rewrite_rules', 'wc_ms_protect_download_rewite_rules' );

/**
 * WooCommerce Core Supported Themes
 *
 * @since 2.2
 * @return array
 */
function wc_get_core_supported_themes() {
	return array( 'twentyfifteen', 'twentyfourteen', 'twentythirteen', 'twentyeleven', 'twentytwelve', 'twentyten' );
}

/**
 * Wrapper function to execute the `woocommerce_deliver_webhook_async` cron
 * hook, see WC_Webhook::process()
 *
 * @since 2.2
 * @param int $webhook_id webhook ID to deliver
 * @param mixed $arg hook argument
 */
function wc_deliver_webhook_async( $webhook_id, $arg ) {

	$webhook = new WC_Webhook( $webhook_id );

	$webhook->deliver( $arg );
}
add_action( 'woocommerce_deliver_webhook_async', 'wc_deliver_webhook_async', 10, 2 );

/**
 * Enables template debug mode
 */
function wc_template_debug_mode() {
	if ( ! defined( 'WC_TEMPLATE_DEBUG_MODE' ) ) {
		$status_options = get_option( 'woocommerce_status_options', array() );
		if ( ! empty( $status_options['template_debug_mode'] ) && current_user_can( 'manage_options' ) ) {
			define( 'WC_TEMPLATE_DEBUG_MODE', true );
		} else {
			define( 'WC_TEMPLATE_DEBUG_MODE', false );
		}
	}
}
add_action( 'after_setup_theme', 'wc_template_debug_mode', 20 );

/**
 * Formats a string in the format COUNTRY:STATE into an array.
 * @since 2.3.0
 * @param  string $country_string
 * @return array
 */
function wc_format_country_state_string( $country_string ) {
	if ( strstr( $country_string, ':' ) ) {
		list( $country, $state ) = explode( ':', $country_string );
	} else {
		$country = $country_string;
		$state   = '';
	}
	return array(
		'country' => $country,
		'state'   => $state
	);
}

/**
 * Get the store's base location.
 * @todo should the woocommerce_default_country option be renamed to contain 'base'?
 * @since 2.3.0
 * @return array
 */
function wc_get_base_location() {
	$default = apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) );

	return wc_format_country_state_string( $default );
}

/**
 * Get the customer's default location. Filtered, and set to base location or left blank.
 * @todo should the woocommerce_default_country option be renamed to contain 'base'?
 * @since 2.3.0
 * @return array
 */
function wc_get_customer_default_location() {
	switch ( get_option( 'woocommerce_default_customer_address' ) ) {
		case 'geolocation' :
			$location = WC_Geolocation::geolocate_ip();

			// Base fallback
			if ( empty( $location['country'] ) ) {
				$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) ) );
			}
		break;
		case 'base' :
			$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) ) );
		break;
		default :
			$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', '' ) );
		break;
	}

	return $location;
}

// This function can be removed when WP 3.9.2 or greater is required
if ( ! function_exists( 'hash_equals' ) ) :
	/**
	 * Compare two strings in constant time.
	 *
	 * This function was added in PHP 5.6.
	 * It can leak the length of a string.
	 *
	 * @since 3.9.2
	 *
	 * @param string $a Expected string.
	 * @param string $b Actual string.
	 * @return bool Whether strings are equal.
	 */
	function hash_equals( $a, $b ) {
		$a_length = strlen( $a );
		if ( $a_length !== strlen( $b ) ) {
			return false;
		}
		$result = 0;

		// Do not attempt to "optimize" this.
		for ( $i = 0; $i < $a_length; $i++ ) {
			$result |= ord( $a[ $i ] ) ^ ord( $b[ $i ] );
		}

		return $result === 0;
	}
endif;
