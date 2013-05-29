<?php
/**
 * WooCommerce Admin
 *
 * Main admin file which loads all settings panels and sets up admin menus.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Functions for the product post type
 */
include_once( 'post-types/product.php' );

/**
 * Functions for the shop_coupon post type
 */
include_once( 'post-types/shop_coupon.php' );

/**
 * Functions for the shop_order post type
 */
include_once( 'post-types/shop_order.php' );

/**
 * Hooks in admin
 */
include_once( 'woocommerce-admin-hooks.php' );

/**
 * Functions in admin
 */
include_once( 'woocommerce-admin-functions.php' );

/**
 * Functions for handling taxonomies
 */
include_once( 'woocommerce-admin-taxonomies.php' );

/**
 * Welcome Page
 */
include_once( 'includes/welcome.php' );

/**
 * Setup the Admin menu in WordPress
 *
 * @access public
 * @return void
 */
function woocommerce_admin_menu() {
    global $menu, $woocommerce;

    if ( current_user_can( 'manage_woocommerce' ) )
    $menu[] = array( '', 'read', 'separator-woocommerce', '', 'wp-menu-separator woocommerce' );

    $main_page = add_menu_page( __( 'WooCommerce', 'woocommerce' ), __( 'WooCommerce', 'woocommerce' ), 'manage_woocommerce', 'woocommerce' , 'woocommerce_settings_page', null, '55.5' );

    $reports_page = add_submenu_page( 'woocommerce', __( 'Reports', 'woocommerce' ),  __( 'Reports', 'woocommerce' ) , 'view_woocommerce_reports', 'woocommerce_reports', 'woocommerce_reports_page' );

    add_submenu_page( 'edit.php?post_type=product', __( 'Attributes', 'woocommerce' ), __( 'Attributes', 'woocommerce' ), 'manage_product_terms', 'woocommerce_attributes', 'woocommerce_attributes_page');

    add_action( 'load-' . $main_page, 'woocommerce_admin_help_tab' );
    add_action( 'load-' . $reports_page, 'woocommerce_admin_help_tab' );

    $wc_screen_id = strtolower( __( 'WooCommerce', 'woocommerce' ) );

    $print_css_on = apply_filters( 'woocommerce_screen_ids', array( 'toplevel_page_' . $wc_screen_id, $wc_screen_id . '_page_woocommerce_settings', $wc_screen_id . '_page_woocommerce_reports', 'toplevel_page_woocommerce', 'woocommerce_page_woocommerce_settings', 'woocommerce_page_woocommerce_reports', 'woocommerce_page_woocommerce_status', 'product_page_woocommerce_attributes', 'edit-tags.php', 'edit.php', 'index.php', 'post-new.php', 'post.php' ) );

    foreach ( $print_css_on as $page )
    	add_action( 'admin_print_styles-'. $page, 'woocommerce_admin_css' );
}

add_action('admin_menu', 'woocommerce_admin_menu', 9);

/**
 * Setup the Admin menu in WordPress - later priority so they appear last
 *
 * @access public
 * @return void
 */
function woocommerce_admin_menu_after() {
	$settings_page = add_submenu_page( 'woocommerce', __( 'WooCommerce Settings', 'woocommerce' ),  __( 'Settings', 'woocommerce' ) , 'manage_woocommerce', 'woocommerce_settings', 'woocommerce_settings_page');
	$status_page = add_submenu_page( 'woocommerce', __( 'WooCommerce Status', 'woocommerce' ),  __( 'System Status', 'woocommerce' ) , 'manage_woocommerce', 'woocommerce_status', 'woocommerce_status_page');

	add_action( 'load-' . $settings_page, 'woocommerce_settings_page_init' );
}

add_action('admin_menu', 'woocommerce_admin_menu_after', 50);

/**
 * Loads gateways and shipping methods into memory for use within settings.
 *
 * @access public
 * @return void
 */
function woocommerce_settings_page_init() {
	$GLOBALS['woocommerce']->payment_gateways();
	$GLOBALS['woocommerce']->shipping();
}

/**
 * Highlights the correct top level admin menu item for post type add screens.
 *
 * @access public
 * @return void
 */
function woocommerce_admin_menu_highlight() {
	global $menu, $submenu, $parent_file, $submenu_file, $self, $post_type, $taxonomy;

	$to_highlight_types = array( 'shop_order', 'shop_coupon' );

	if ( isset( $post_type ) ) {
		if ( in_array( $post_type, $to_highlight_types ) ) {
			$submenu_file = 'edit.php?post_type=' . esc_attr( $post_type );
			$parent_file  = 'woocommerce';
		}

		if ( 'product' == $post_type ) {
			$screen = get_current_screen();

			if ( $screen->base == 'edit-tags' && 'pa_' == substr( $taxonomy, 0, 3 ) ) {
				$submenu_file = 'woocommerce_attributes';
				$parent_file  = 'edit.php?post_type=' . esc_attr( $post_type );
			}
		}
	}

	if ( isset( $submenu['woocommerce'] ) && isset( $submenu['woocommerce'][2] ) ) {
		$submenu['woocommerce'][0] = $submenu['woocommerce'][2];
		unset( $submenu['woocommerce'][2] );
	}

	// Sort out Orders menu when on the top level
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		foreach ( $menu as $key => $menu_item ) {
			if ( strpos( $menu_item[0], _x('Orders', 'Admin menu name', 'woocommerce') ) === 0 ) {

				$menu_name = _x('Orders', 'Admin menu name', 'woocommerce');
				$menu_name_count = '';
				if ( $order_count = woocommerce_processing_order_count() ) {
					$menu_name_count = " <span class='awaiting-mod update-plugins count-$order_count'><span class='processing-count'>" . number_format_i18n( $order_count ) . "</span></span>" ;
				}

				$menu[$key][0] = $menu_name . $menu_name_count;
				$submenu['edit.php?post_type=shop_order'][5][0] = $menu_name;
				break;
			}
		}
	}
}

add_action( 'admin_head', 'woocommerce_admin_menu_highlight' );


/**
 * woocommerce_admin_notices_styles function.
 *
 * @access public
 * @return void
 */
function woocommerce_admin_notices_styles() {

	if ( get_option( '_wc_needs_update' ) == 1 || get_option( '_wc_needs_pages' ) == 1 ) {
		wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', dirname( __FILE__ ) ) );
		add_action( 'admin_notices', 'woocommerce_admin_install_notices' );
	}

	$template = get_option( 'template' );

	if ( ! current_theme_supports( 'woocommerce' ) && ! in_array( $template, array( 'twentythirteen', 'twentyeleven', 'twentytwelve', 'twentyten' ) ) ) {

		if ( ! empty( $_GET['hide_woocommerce_theme_support_check'] ) ) {
			update_option( 'woocommerce_theme_support_check', $template );
			return;
		}

		if ( get_option( 'woocommerce_theme_support_check' ) !== $template ) {
			wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', dirname( __FILE__ ) ) );
			add_action( 'admin_notices', 'woocommerce_theme_check_notice' );
		}

	}

}

add_action( 'admin_print_styles', 'woocommerce_admin_notices_styles' );


/**
 * woocommerce_theme_check_notice function.
 *
 * @access public
 * @return void
 */
function woocommerce_theme_check_notice() {
	include( 'includes/notice-theme-support.php' );
}


/**
 * woocommerce_admin_install_notices function.
 *
 * @access public
 * @return void
 */
function woocommerce_admin_install_notices() {
	global $woocommerce;

	// If we need to update, include a message with the update button
	if ( get_option( '_wc_needs_update' ) == 1 ) {
		include( 'includes/notice-update.php' );
	}

	// If we have just installed, show a message with the install pages button
	elseif ( get_option( '_wc_needs_pages' ) == 1 ) {
		include( 'includes/notice-install.php' );
	}
}

/**
 * Include some admin files conditonally.
 *
 * @access public
 * @return void
 */
function woocommerce_admin_init() {
	global $pagenow, $typenow;

	ob_start();

	// Install - Add pages button
	if ( ! empty( $_GET['install_woocommerce_pages'] ) ) {

		require_once( 'woocommerce-admin-install.php' );
		woocommerce_create_pages();

		// We no longer need to install pages
		delete_option( '_wc_needs_pages' );
		delete_transient( '_wc_activation_redirect' );

		// What's new redirect
		wp_safe_redirect( admin_url( 'index.php?page=wc-about&wc-installed=true' ) );
		exit;

	// Skip button
	} elseif ( ! empty( $_GET['skip_install_woocommerce_pages'] ) ) {

		// We no longer need to install pages
		delete_option( '_wc_needs_pages' );
		delete_transient( '_wc_activation_redirect' );

		// Flush rules after install
		flush_rewrite_rules();

		// What's new redirect
		wp_safe_redirect( admin_url( 'index.php?page=wc-about' ) );
		exit;

	// Update button
	} elseif ( ! empty( $_GET['do_update_woocommerce'] ) ) {

		include_once( 'woocommerce-admin-update.php' );
		do_update_woocommerce();

		// Update complete
		delete_option( '_wc_needs_pages' );
		delete_option( '_wc_needs_update' );
		delete_transient( '_wc_activation_redirect' );

		// What's new redirect
		wp_safe_redirect( admin_url( 'index.php?page=wc-about&wc-updated=true' ) );
		exit;
	}

	// Includes
	if ( $typenow == 'post' && isset( $_GET['post'] ) && ! empty( $_GET['post'] ) ) {
		$typenow = $post->post_type;
	} elseif ( empty( $typenow ) && ! empty( $_GET['post'] ) ) {
	    $post = get_post( $_GET['post'] );
	    $typenow = $post->post_type;
	}

	if ( $pagenow == 'index.php' ) {

		include_once( 'woocommerce-admin-dashboard.php' );

	} elseif ( $pagenow == 'admin.php' && isset( $_GET['import'] ) ) {

		include_once( 'woocommerce-admin-import.php' );

	} elseif ( $pagenow == 'post-new.php' || $pagenow == 'post.php' || $pagenow == 'edit.php' ) {

		include_once( 'post-types/writepanels/writepanels-init.php' );

		if ( in_array( $typenow, array( 'product', 'shop_coupon', 'shop_order' ) ) )
			add_action('admin_print_styles', 'woocommerce_admin_help_tab');

	} elseif ( $pagenow == 'users.php' || $pagenow == 'user-edit.php' || $pagenow == 'profile.php' ) {

		include_once( 'woocommerce-admin-users.php' );

	}

	// Register importers
	if ( defined( 'WP_LOAD_IMPORTERS' ) ) {
		include_once( 'importers/importers-init.php' );
	}
}

add_action('admin_init', 'woocommerce_admin_init');


/**
 * Include and display the settings page.
 *
 * @access public
 * @return void
 */
function woocommerce_settings_page() {
	include_once( 'woocommerce-admin-settings.php' );
	woocommerce_settings();
}

/**
 * Include and display the reports page.
 *
 * @access public
 * @return void
 */
function woocommerce_reports_page() {
	include_once( 'woocommerce-admin-reports.php' );
	woocommerce_reports();
}

/**
 * Include and display the attibutes page.
 *
 * @access public
 * @return void
 */
function woocommerce_attributes_page() {
	include_once( 'woocommerce-admin-attributes.php' );
	woocommerce_attributes();
}

/**
 * Include and display the status page.
 *
 * @access public
 * @return void
 */
function woocommerce_status_page() {
	include_once( 'woocommerce-admin-status.php' );
	woocommerce_status();
}


/**
 * Include and add help tabs to WordPress admin.
 *
 * @access public
 * @return void
 */
function woocommerce_admin_help_tab() {
	include_once( 'woocommerce-admin-content.php' );
	woocommerce_admin_help_tab_content();
}


/**
 * Include admin scripts and styles.
 *
 * @access public
 * @return void
 */
function woocommerce_admin_scripts() {
	global $woocommerce, $pagenow, $post, $wp_query;

	$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	// Register scripts
	wp_register_script( 'woocommerce_admin', $woocommerce->plugin_url() . '/assets/js/admin/woocommerce_admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-placeholder', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), $woocommerce->version );

	wp_register_script( 'jquery-blockui', $woocommerce->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.60', true );

	wp_register_script( 'jquery-placeholder', $woocommerce->plugin_url() . '/assets/js/jquery-placeholder/jquery.placeholder' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );

	wp_register_script( 'jquery-tiptip', $woocommerce->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );

	wp_register_script( 'woocommerce_writepanel', $woocommerce->plugin_url() . '/assets/js/admin/write-panels'.$suffix.'.js', array('jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable'), $woocommerce->version );

	wp_register_script( 'ajax-chosen', $woocommerce->plugin_url() . '/assets/js/chosen/ajax-chosen.jquery'.$suffix.'.js', array('jquery', 'chosen'), $woocommerce->version );

	wp_register_script( 'chosen', $woocommerce->plugin_url() . '/assets/js/chosen/chosen.jquery'.$suffix.'.js', array('jquery'), $woocommerce->version );

	// Get admin screen id
    $screen       = get_current_screen();
    $wc_screen_id = strtolower( __( 'WooCommerce', 'woocommerce' ) );

    // WooCommerce admin pages
    if ( in_array( $screen->id, apply_filters( 'woocommerce_screen_ids', array( 'toplevel_page_' . $wc_screen_id, $wc_screen_id . '_page_woocommerce_settings', $wc_screen_id . '_page_woocommerce_reports', 'toplevel_page_woocommerce', 'woocommerce_page_woocommerce_settings', 'woocommerce_page_woocommerce_reports', 'edit-shop_order', 'edit-shop_coupon', 'shop_coupon', 'shop_order', 'edit-product', 'product' ) ) ) ) {

    	wp_enqueue_script( 'woocommerce_admin' );
    	wp_enqueue_script( 'farbtastic' );
    	wp_enqueue_script( 'ajax-chosen' );
    	wp_enqueue_script( 'chosen' );
    	wp_enqueue_script( 'jquery-ui-sortable' );
    	wp_enqueue_script( 'jquery-ui-autocomplete' );

    }

    // Edit product category pages
    if ( in_array( $screen->id, array('edit-product_cat') ) )
		wp_enqueue_media();

	// Product/Coupon/Orders
	if ( in_array( $screen->id, array( 'shop_coupon', 'shop_order', 'product' ) ) ) {

		wp_enqueue_script( 'woocommerce_writepanel' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_media();
		wp_enqueue_script( 'ajax-chosen' );
		wp_enqueue_script( 'chosen' );
		wp_enqueue_script( 'plupload-all' );

		$woocommerce_witepanel_params = array(
			'remove_item_notice' 			=> __( 'Are you sure you want to remove the selected items? If you have previously reduced this item\'s stock, or this order was submitted by a customer, you will need to manually restore the item\'s stock.', 'woocommerce' ),
			'i18n_select_items'				=> __( 'Please select some items.', 'woocommerce' ),
			'remove_item_meta'				=> __( 'Remove this item meta?', 'woocommerce' ),
			'remove_attribute'				=> __( 'Remove this attribute?', 'woocommerce' ),
			'name_label'					=> __( 'Name', 'woocommerce' ),
			'remove_label'					=> __( 'Remove', 'woocommerce' ),
			'click_to_toggle'				=> __( 'Click to toggle', 'woocommerce' ),
			'values_label'					=> __( 'Value(s)', 'woocommerce' ),
			'text_attribute_tip'			=> __( 'Enter some text, or some attributes by pipe (|) separating values.', 'woocommerce' ),
			'visible_label'					=> __( 'Visible on the product page', 'woocommerce' ),
			'used_for_variations_label'		=> __( 'Used for variations', 'woocommerce' ),
			'new_attribute_prompt'			=> __( 'Enter a name for the new attribute term:', 'woocommerce' ),
			'calc_totals' 					=> __( 'Calculate totals based on order items, discounts, and shipping?', 'woocommerce' ),
			'calc_line_taxes' 				=> __( 'Calculate line taxes? This will calculate taxes based on the customers country. If no billing/shipping is set it will use the store base country.', 'woocommerce' ),
			'copy_billing' 					=> __( 'Copy billing information to shipping information? This will remove any currently entered shipping information.', 'woocommerce' ),
			'load_billing' 					=> __( 'Load the customer\'s billing information? This will remove any currently entered billing information.', 'woocommerce' ),
			'load_shipping' 				=> __( 'Load the customer\'s shipping information? This will remove any currently entered shipping information.', 'woocommerce' ),
			'featured_label'				=> __( 'Featured', 'woocommerce' ),
			'prices_include_tax' 			=> esc_attr( get_option('woocommerce_prices_include_tax') ),
			'round_at_subtotal'				=> esc_attr( get_option( 'woocommerce_tax_round_at_subtotal' ) ),
			'no_customer_selected'			=> __( 'No customer selected', 'woocommerce' ),
			'plugin_url' 					=> $woocommerce->plugin_url(),
			'ajax_url' 						=> admin_url('admin-ajax.php'),
			'order_item_nonce' 				=> wp_create_nonce("order-item"),
			'add_attribute_nonce' 			=> wp_create_nonce("add-attribute"),
			'save_attributes_nonce' 		=> wp_create_nonce("save-attributes"),
			'calc_totals_nonce' 			=> wp_create_nonce("calc-totals"),
			'get_customer_details_nonce' 	=> wp_create_nonce("get-customer-details"),
			'search_products_nonce' 		=> wp_create_nonce("search-products"),
			'calendar_image'				=> $woocommerce->plugin_url().'/assets/images/calendar.png',
			'post_id'						=> $post->ID,
			'base_country'					=> $woocommerce->countries->get_base_country(),
			'currency_format_num_decimals'	=> absint( get_option( 'woocommerce_price_num_decimals' ) ),
			'currency_format_symbol'		=> get_woocommerce_currency_symbol(),
			'currency_format_decimal_sep'	=> esc_attr( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ) ),
			'currency_format_thousand_sep'	=> esc_attr( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ) ),
			'currency_format'				=> esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) ), // For accounting JS
			'product_types'					=> array_map( 'sanitize_title', get_terms( 'product_type', array( 'hide_empty' => false, 'fields' => 'names' ) ) ),
			'default_attribute_visibility'  => apply_filters( 'default_attribute_visibility', false ),
			'default_attribute_variation'   => apply_filters( 'default_attribute_variation', false )
		 );

		wp_localize_script( 'woocommerce_writepanel', 'woocommerce_writepanel_params', $woocommerce_witepanel_params );
	}

	// Term ordering - only when sorting by term_order
	if ( ( strstr( $screen->id, 'edit-pa_' ) || ( ! empty( $_GET['taxonomy'] ) && in_array( $_GET['taxonomy'], apply_filters( 'woocommerce_sortable_taxonomies', array( 'product_cat' ) ) ) ) ) && ! isset( $_GET['orderby'] ) ) {

		wp_register_script( 'woocommerce_term_ordering', $woocommerce->plugin_url() . '/assets/js/admin/term-ordering.js', array('jquery-ui-sortable'), $woocommerce->version );
		wp_enqueue_script( 'woocommerce_term_ordering' );

		$taxonomy = isset( $_GET['taxonomy'] ) ? woocommerce_clean( $_GET['taxonomy'] ) : '';

		$woocommerce_term_order_params = array(
			'taxonomy' 			=>  $taxonomy
		 );

		wp_localize_script( 'woocommerce_term_ordering', 'woocommerce_term_ordering_params', $woocommerce_term_order_params );

	}

	// Product sorting - only when sorting by menu order on the products page
	if ( current_user_can('edit_others_pages') && $screen->id == 'edit-product' && isset( $wp_query->query['orderby'] ) && $wp_query->query['orderby'] == 'menu_order title' ) {

		wp_enqueue_script( 'woocommerce_product_ordering', $woocommerce->plugin_url() . '/assets/js/admin/product-ordering.js', array('jquery-ui-sortable'), '1.0', true );

	}

	// Reports pages
    if ( in_array( $screen->id, apply_filters( 'woocommerce_reports_screen_ids', array( $wc_screen_id . '_page_woocommerce_reports', apply_filters( 'woocommerce_reports_screen_id', 'woocommerce_page_woocommerce_reports' ) ) ) ) ) {

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'flot', $woocommerce->plugin_url() . '/assets/js/admin/jquery.flot'.$suffix.'.js', 'jquery', '1.0' );
		wp_enqueue_script( 'flot-resize', $woocommerce->plugin_url() . '/assets/js/admin/jquery.flot.resize'.$suffix.'.js', array('jquery', 'flot'), '1.0' );

	}
}

add_action( 'admin_enqueue_scripts', 'woocommerce_admin_scripts' );


/**
 * Queue WooCommerce CSS.
 *
 * @access public
 * @return void
 */
function woocommerce_admin_css() {
	global $woocommerce, $typenow, $post, $wp_scripts;

	if ( $typenow == 'post' && ! empty( $_GET['post'] ) ) {
		$typenow = $post->post_type;
	} elseif ( empty( $typenow ) && ! empty( $_GET['post'] ) ) {
        $post = get_post( $_GET['post'] );
        $typenow = $post->post_type;
    }

	if ( $typenow == '' || $typenow == "product" || $typenow == "shop_order" || $typenow == "shop_coupon" ) {
		wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );

		$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';

		wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css' );
	}

	wp_enqueue_style('farbtastic');

	do_action('woocommerce_admin_css');
}


/**
 * Queue admin menu icons CSS.
 *
 * @access public
 * @return void
 */
function woocommerce_admin_menu_styles() {
	global $woocommerce;
	wp_enqueue_style( 'woocommerce_admin_menu_styles', $woocommerce->plugin_url() . '/assets/css/menu.css' );
}

add_action( 'admin_print_styles', 'woocommerce_admin_menu_styles' );


/**
 * Reorder the WC menu items in admin.
 *
 * @access public
 * @param mixed $menu_order
 * @return void
 */
function woocommerce_admin_menu_order( $menu_order ) {

	// Initialize our custom order array
	$woocommerce_menu_order = array();

	// Get the index of our custom separator
	$woocommerce_separator = array_search( 'separator-woocommerce', $menu_order );

	// Get index of product menu
	$woocommerce_product = array_search( 'edit.php?post_type=product', $menu_order );

	// Loop through menu order and do some rearranging
	foreach ( $menu_order as $index => $item ) :

		if ( ( ( 'woocommerce' ) == $item ) ) :
			$woocommerce_menu_order[] = 'separator-woocommerce';
			$woocommerce_menu_order[] = $item;
			$woocommerce_menu_order[] = 'edit.php?post_type=product';
			unset( $menu_order[$woocommerce_separator] );
			unset( $menu_order[$woocommerce_product] );
		elseif ( !in_array( $item, array( 'separator-woocommerce' ) ) ) :
			$woocommerce_menu_order[] = $item;
		endif;

	endforeach;

	// Return order
	return $woocommerce_menu_order;
}

add_action('menu_order', 'woocommerce_admin_menu_order');


/**
 * woocommerce_admin_custom_menu_order function.
 *
 * @access public
 * @return void
 */
function woocommerce_admin_custom_menu_order() {
	if ( ! current_user_can( 'manage_woocommerce' ) )
		return false;
	return true;
}

add_action( 'custom_menu_order', 'woocommerce_admin_custom_menu_order' );


/**
 * Admin Head
 *
 * Outputs some styles in the admin <head> to show icons on the woocommerce admin pages
 *
 * @access public
 * @return void
 */
function woocommerce_admin_head() {
	global $woocommerce;

	if ( ! current_user_can( 'manage_woocommerce' ) ) return false;
	?>
	<style type="text/css">
		<?php if ( isset($_GET['taxonomy']) && $_GET['taxonomy']=='product_cat' ) : ?>
			.icon32-posts-product { background-position: -243px -5px !important; }
		<?php elseif ( isset($_GET['taxonomy']) && $_GET['taxonomy']=='product_tag' ) : ?>
			.icon32-posts-product { background-position: -301px -5px !important; }
		<?php endif; ?>
	</style>
	<?php
}

add_action('admin_head', 'woocommerce_admin_head');


/**
 * Duplicate a product action
 *
 * @access public
 * @return void
 */
function woocommerce_duplicate_product_action() {
	include_once('includes/duplicate_product.php');
	woocommerce_duplicate_product();
}

add_action('admin_action_duplicate_product', 'woocommerce_duplicate_product_action');


/**
 * Post updated messages
 *
 * @access public
 * @param mixed $messages
 * @return void
 */
function woocommerce_product_updated_messages( $messages ) {
	global $post, $post_ID;

	$messages['product'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __( 'Product updated. <a href="%s">View Product</a>', 'woocommerce' ), esc_url( get_permalink($post_ID) ) ),
		2 => __( 'Custom field updated.', 'woocommerce' ),
		3 => __( 'Custom field deleted.', 'woocommerce' ),
		4 => __( 'Product updated.', 'woocommerce' ),
		5 => isset($_GET['revision']) ? sprintf( __( 'Product restored to revision from %s', 'woocommerce' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __( 'Product published. <a href="%s">View Product</a>', 'woocommerce' ), esc_url( get_permalink($post_ID) ) ),
		7 => __( 'Product saved.', 'woocommerce' ),
		8 => sprintf( __( 'Product submitted. <a target="_blank" href="%s">Preview Product</a>', 'woocommerce' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9 => sprintf( __( 'Product scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Product</a>', 'woocommerce' ),
		  date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		10 => sprintf( __( 'Product draft updated. <a target="_blank" href="%s">Preview Product</a>', 'woocommerce' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	);

	$messages['shop_order'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => __( 'Order updated.', 'woocommerce' ),
		2 => __( 'Custom field updated.', 'woocommerce' ),
		3 => __( 'Custom field deleted.', 'woocommerce' ),
		4 => __( 'Order updated.', 'woocommerce' ),
		5 => isset($_GET['revision']) ? sprintf( __( 'Order restored to revision from %s', 'woocommerce' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __( 'Order updated.', 'woocommerce' ),
		7 => __( 'Order saved.', 'woocommerce' ),
		8 => __( 'Order submitted.', 'woocommerce' ),
		9 => sprintf( __( 'Order scheduled for: <strong>%1$s</strong>.', 'woocommerce' ),
		  date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $post->post_date ) ) ),
		10 => __( 'Order draft updated.', 'woocommerce' )
	);

	$messages['shop_coupon'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => __( 'Coupon updated.', 'woocommerce' ),
		2 => __( 'Custom field updated.', 'woocommerce' ),
		3 => __( 'Custom field deleted.', 'woocommerce' ),
		4 => __( 'Coupon updated.', 'woocommerce' ),
		5 => isset($_GET['revision']) ? sprintf( __( 'Coupon restored to revision from %s', 'woocommerce' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __( 'Coupon updated.', 'woocommerce' ),
		7 => __( 'Coupon saved.', 'woocommerce' ),
		8 => __( 'Coupon submitted.', 'woocommerce' ),
		9 => sprintf( __( 'Coupon scheduled for: <strong>%1$s</strong>.', 'woocommerce' ),
		  date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $post->post_date ) ) ),
		10 => __( 'Coupon draft updated.', 'woocommerce' )
	);

	return $messages;
}

add_filter('post_updated_messages', 'woocommerce_product_updated_messages');


/**
 * Post updated messages
 *
 * @access public
 * @param mixed $types
 * @return void
 */
function woocommerce_admin_comment_types_dropdown( $types ) {
	$types['order_note'] = __( 'Order notes', 'woocommerce' );
	return $types;
}

add_filter( 'admin_comment_types_dropdown', 'woocommerce_admin_comment_types_dropdown' );


/**
 * woocommerce_permalink_settings function.
 *
 * @access public
 * @return void
 */
function woocommerce_permalink_settings() {

	echo wpautop( __( 'These settings control the permalinks used for products. These settings only apply when <strong>not using "default" permalinks above</strong>.', 'woocommerce' ) );

	$permalinks = get_option( 'woocommerce_permalinks' );
	$product_permalink = $permalinks['product_base'];

	// Get shop page
	$shop_page_id 	= woocommerce_get_page_id( 'shop' );
	$base_slug 		= ( $shop_page_id > 0 && get_page( $shop_page_id ) ) ? get_page_uri( $shop_page_id ) : _x( 'shop', 'default-slug', 'woocommerce' );
	$product_base 	= _x( 'product', 'default-slug', 'woocommerce' );

	$structures = array(
		0 => '',
		1 => '/' . trailingslashit( $product_base ),
		2 => '/' . trailingslashit( $base_slug ),
		3 => '/' . trailingslashit( $base_slug ) . trailingslashit( '%product_cat%' )
	);
	?>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label><input name="product_permalink" type="radio" value="<?php echo $structures[0]; ?>" class="wctog" <?php checked( $structures[0], $product_permalink ); ?> /> <?php _e( 'Default', 'woocommerce' ); ?></label></th>
				<td><code><?php echo home_url(); ?>/?product=sample-product</code></td>
			</tr>
			<tr>
				<th><label><input name="product_permalink" type="radio" value="<?php echo $structures[1]; ?>" class="wctog" <?php checked( $structures[1], $product_permalink ); ?> /> <?php _e( 'Product', 'woocommerce' ); ?></label></th>
				<td><code><?php echo home_url(); ?>/<?php echo $product_base; ?>/sample-product/</code></td>
			</tr>
			<?php if ( $shop_page_id ) : ?>
				<tr>
					<th><label><input name="product_permalink" type="radio" value="<?php echo $structures[2]; ?>" class="wctog" <?php checked( $structures[2], $product_permalink ); ?> /> <?php _e( 'Shop base', 'woocommerce' ); ?></label></th>
					<td><code><?php echo home_url(); ?>/<?php echo $base_slug; ?>/sample-product/</code></td>
				</tr>
				<tr>
					<th><label><input name="product_permalink" type="radio" value="<?php echo $structures[3]; ?>" class="wctog" <?php checked( $structures[3], $product_permalink ); ?> /> <?php _e( 'Shop base with category', 'woocommerce' ); ?></label></th>
					<td><code><?php echo home_url(); ?>/<?php echo $base_slug; ?>/product-category/sample-product/</code></td>
				</tr>
			<?php endif; ?>
			<tr>
				<th><label><input name="product_permalink" id="woocommerce_custom_selection" type="radio" value="custom" class="tog" <?php checked( in_array( $product_permalink, $structures ), false ); ?> />
					<?php _e( 'Custom Base', 'woocommerce' ); ?></label></th>
				<td>
					<input name="product_permalink_structure" id="woocommerce_permalink_structure" type="text" value="<?php echo esc_attr( $product_permalink ); ?>" class="regular-text code"> <span class="description"><?php _e( 'Enter a custom base to use. A base <strong>must</strong> be set or WordPress will use default instead.', 'woocommerce' ); ?></span>
				</td>
			</tr>
		</tbody>
	</table>
	<script type="text/javascript">
		jQuery(function(){
			jQuery('input.wctog').change(function() {
				jQuery('#woocommerce_permalink_structure').val( jQuery(this).val() );
			});

			jQuery('#woocommerce_permalink_structure').focus(function(){
				jQuery('#woocommerce_custom_selection').click();
			});
		});
	</script>
	<?php
}

/**
 * woocommerce_permalink_settings_init function.
 *
 * @access public
 * @return void
 */
function woocommerce_permalink_settings_init() {

	// Add a section to the permalinks page
	add_settings_section( 'woocommerce-permalink', __( 'Product permalink base', 'woocommerce' ), 'woocommerce_permalink_settings', 'permalink' );

	// Add our settings
	add_settings_field(
		'woocommerce_product_category_slug',      	// id
		__( 'Product category base', 'woocommerce' ), 	// setting title
		'woocommerce_product_category_slug_input',  // display callback
		'permalink',                 				// settings page
		'optional'                  				// settings section
	);
	add_settings_field(
		'woocommerce_product_tag_slug',      		// id
		__( 'Product tag base', 'woocommerce' ), 	// setting title
		'woocommerce_product_tag_slug_input',  		// display callback
		'permalink',                 				// settings page
		'optional'                  				// settings section
	);
	add_settings_field(
		'woocommerce_product_attribute_slug',      	// id
		__( 'Product attribute base', 'woocommerce' ), 	// setting title
		'woocommerce_product_attribute_slug_input',  		// display callback
		'permalink',                 				// settings page
		'optional'                  				// settings section
	);
}

add_action( 'admin_init', 'woocommerce_permalink_settings_init' );

/**
 * woocommerce_permalink_settings_save function.
 *
 * @access public
 * @return void
 */
function woocommerce_permalink_settings_save() {
	if ( ! is_admin() )
		return;

	// We need to save the options ourselves; settings api does not trigger save for the permalinks page
	if ( isset( $_POST['permalink_structure'] ) || isset( $_POST['category_base'] ) && isset( $_POST['product_permalink'] ) ) {
		// Cat and tag bases
		$woocommerce_product_category_slug = woocommerce_clean( $_POST['woocommerce_product_category_slug'] );
		$woocommerce_product_tag_slug = woocommerce_clean( $_POST['woocommerce_product_tag_slug'] );
		$woocommerce_product_attribute_slug = woocommerce_clean( $_POST['woocommerce_product_attribute_slug'] );

		$permalinks = get_option( 'woocommerce_permalinks' );
		if ( ! $permalinks )
			$permalinks = array();

		$permalinks['category_base'] 	= untrailingslashit( $woocommerce_product_category_slug );
		$permalinks['tag_base'] 		= untrailingslashit( $woocommerce_product_tag_slug );
		$permalinks['attribute_base'] 	= untrailingslashit( $woocommerce_product_attribute_slug );

		// Product base
		$product_permalink = woocommerce_clean( $_POST['product_permalink'] );

		if ( $product_permalink == 'custom' ) {
			$product_permalink = woocommerce_clean( $_POST['product_permalink_structure'] );
		} elseif ( empty( $product_permalink ) ) {
			$product_permalink = false;
		}

		$permalinks['product_base'] = untrailingslashit( $product_permalink );

		update_option( 'woocommerce_permalinks', $permalinks );
	}
}

add_action( 'before_woocommerce_init', 'woocommerce_permalink_settings_save' );

/**
 * woocommerce_product_category_slug_input function.
 *
 * @access public
 * @return void
 */
function woocommerce_product_category_slug_input() {
	$permalinks = get_option( 'woocommerce_permalinks' );
	?>
	<input name="woocommerce_product_category_slug" type="text" class="regular-text code" value="<?php if ( isset( $permalinks['category_base'] ) ) echo esc_attr( $permalinks['category_base'] ); ?>" placeholder="<?php echo _x('product-category', 'slug', 'woocommerce') ?>" />
	<?php
}

/**
 * woocommerce_product_tag_slug_input function.
 *
 * @access public
 * @return void
 */
function woocommerce_product_tag_slug_input() {
	$permalinks = get_option( 'woocommerce_permalinks' );
	?>
	<input name="woocommerce_product_tag_slug" type="text" class="regular-text code" value="<?php if ( isset( $permalinks['tag_base'] ) ) echo esc_attr( $permalinks['tag_base'] ); ?>" placeholder="<?php echo _x('product-tag', 'slug', 'woocommerce') ?>" />
	<?php
}

/**
 * woocommerce_product_attribute_slug_input function.
 *
 * @access public
 * @return void
 */
function woocommerce_product_attribute_slug_input() {
	$permalinks = get_option( 'woocommerce_permalinks' );
	?>
	<input name="woocommerce_product_attribute_slug" type="text" class="regular-text code" value="<?php if ( isset( $permalinks['attribute_base'] ) ) echo esc_attr( $permalinks['attribute_base'] ); ?>" /><code>/attribute-name/attribute/</code>
	<?php
}