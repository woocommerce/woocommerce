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
 * Setup the Admin menu in WordPress
 *
 * @access public
 * @return void
 */
function woocommerce_admin_menu() {
    global $menu, $woocommerce;

    if ( current_user_can( 'manage_woocommerce' ) )
    $menu[] = array( '', 'read', 'separator-woocommerce', '', 'wp-menu-separator woocommerce' );

    $main_page = add_menu_page(__('WooCommerce', 'woocommerce'), __('WooCommerce', 'woocommerce'), 'manage_woocommerce', 'woocommerce' , 'woocommerce_settings_page', null, '55.5' );

    $reports_page = add_submenu_page('woocommerce', __('Reports', 'woocommerce'),  __('Reports', 'woocommerce') , 'view_woocommerce_reports', 'woocommerce_reports', 'woocommerce_reports_page');

    add_submenu_page('edit.php?post_type=product', __('Attributes', 'woocommerce'), __('Attributes', 'woocommerce'), 'manage_woocommerce_products', 'woocommerce_attributes', 'woocommerce_attributes_page');

    add_action('load-' . $main_page, 'woocommerce_admin_help_tab');
    add_action('load-' . $reports_page, 'woocommerce_admin_help_tab');

    $print_css_on = apply_filters( 'woocommerce_screen_ids', array( 'toplevel_page_woocommerce', 'woocommerce_page_woocommerce_settings', 'woocommerce_page_woocommerce_reports', 'woocommerce_page_woocommerce_status', 'product_page_woocommerce_attributes', 'edit-tags.php', 'edit.php', 'index.php', 'post-new.php', 'post.php' ) );

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
	add_submenu_page( 'woocommerce', __('WooCommerce Settings', 'woocommerce'),  __('Settings', 'woocommerce') , 'manage_woocommerce', 'woocommerce_settings', 'woocommerce_settings_page');
	add_submenu_page( 'woocommerce', __('WooCommerce Status', 'woocommerce'),  __('System Status', 'woocommerce') , 'manage_woocommerce', 'woocommerce_status', 'woocommerce_status_page');
}

add_action('admin_menu', 'woocommerce_admin_menu_after', 50);


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
			$submenu_file = 'edit.php?post_type=' . $post_type;
			$parent_file  = 'woocommerce';
		}

		if ( 'product' == $post_type ) {
			$screen = get_current_screen();

			if ( $screen->base == 'edit-tags' && 'pa_' == substr( $taxonomy, 0, 3 ) ) {
				$submenu_file = 'woocommerce_attributes';
				$parent_file  = 'edit.php?post_type=' . $post_type;
			}
		}
	}

	if ( isset( $submenu['woocommerce'] ) ) {
		$submenu['woocommerce'][0] = $submenu['woocommerce'][2];
		unset( $submenu['woocommerce'][2] );
	}

	// Sort out Orders menu when on the top level
	if ( ! current_user_can( 'manage_woocommerce' ) && current_user_can( 'manage_woocommerce_orders' ) ) {
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
 * Show notices in admin.
 *
 * @access public
 * @return void
 */
function woocommerce_admin_install_notice() {
	?>
	<div id="message" class="updated woocommerce-message wc-connect">
		<div class="squeezer">
			<h4><?php _e( '<strong>Welcome to WooCommerce</strong> &#8211; You\'re almost ready to start selling :)', 'woocommerce' ); ?></h4>
			<p class="submit"><a href="<?php echo add_query_arg('install_woocommerce_pages', 'true', admin_url('admin.php?page=woocommerce_settings')); ?>" class="button-primary"><?php _e( 'Install WooCommerce Pages', 'woocommerce' ); ?></a> <a class="skip button-primary" href="<?php echo add_query_arg('skip_install_woocommerce_pages', 'true', admin_url('admin.php?page=woocommerce_settings')); ?>"><?php _e('Skip setup', 'woocommerce'); ?></a></p>
		</div>
	</div>
	<?php
}
function woocommerce_admin_installed_notice() {
	?>
	<div id="message" class="updated woocommerce-message wc-connect">
		<div class="squeezer">
			<h4><?php _e( '<strong>WooCommerce has been installed</strong> &#8211; You\'re ready to start selling :)', 'woocommerce' ); ?></h4>

			<p class="submit"><a href="<?php echo admin_url('admin.php?page=woocommerce_settings'); ?>" class="button-primary"><?php _e( 'Settings', 'woocommerce' ); ?></a> <a class="docs button-primary" href="http://www.woothemes.com/woocommerce-docs/"><?php _e('Documentation', 'woocommerce'); ?></a></p>

			<p><a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/woocommerce/" data-text="A open-source (free) #ecommerce plugin for #WordPress that helps you sell anything. Beautifully." data-via="WooThemes" data-size="large" data-hashtags="WooCommerce">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></p>
		</div>
	</div>
	<?php

	// Set installed option
	update_option('woocommerce_installed', 0);
}
function woocommerce_admin_notices_styles() {

	// Installed notices
	if ( get_option('woocommerce_installed')==1 ) {

		wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', dirname( __FILE__ ) ) );

		if (get_option('skip_install_woocommerce_pages')!=1 && woocommerce_get_page_id('shop')<1 && !isset($_GET['install_woocommerce_pages']) && !isset($_GET['skip_install_woocommerce_pages'])) {
			add_action( 'admin_notices', 'woocommerce_admin_install_notice' );
		} elseif ( !isset($_GET['page']) || $_GET['page']!='woocommerce' ) {
			add_action( 'admin_notices', 'woocommerce_admin_installed_notice' );
		}

	}
}

add_action( 'admin_print_styles', 'woocommerce_admin_notices_styles' );


/**
 * Include some admin files conditonally.
 *
 * @access public
 * @return void
 */
function woocommerce_admin_init() {
	global $pagenow, $typenow;

	ob_start();

	if ( $typenow=='post' && isset( $_GET['post'] ) && ! empty( $_GET['post'] ) ) {
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
 * On activation, include the installer and run it.
 *
 * @access public
 * @return void
 */
function activate_woocommerce() {
	include_once( 'woocommerce-admin-install.php' );
	update_option( 'skip_install_woocommerce_pages', 0 );
	update_option( 'woocommerce_installed', 1 );
	do_install_woocommerce();
}

/**
 * Include the installer and run it.
 *
 * @access public
 * @return void
 */
function install_woocommerce() {
	include_once( 'woocommerce-admin-install.php' );
	do_install_woocommerce();
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

	$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

	// Register scripts
	wp_register_script( 'woocommerce_admin', $woocommerce->plugin_url() . '/assets/js/admin/woocommerce_admin'.$suffix.'.js', array('jquery', 'jquery-ui-widget', 'jquery-ui-core'), $woocommerce->version );
	wp_register_script( 'jquery-ui-datepicker',  $woocommerce->plugin_url() . '/assets/js/admin/ui-datepicker.js', array('jquery','jquery-ui-core'), $woocommerce->version );
	wp_register_script( 'woocommerce_writepanel', $woocommerce->plugin_url() . '/assets/js/admin/write-panels'.$suffix.'.js', array('jquery', 'jquery-ui-datepicker'), $woocommerce->version );
	wp_register_script( 'ajax-chosen', $woocommerce->plugin_url() . '/assets/js/chosen/ajax-chosen.jquery'.$suffix.'.js', array('jquery', 'chosen'), $woocommerce->version );
	wp_register_script( 'chosen', $woocommerce->plugin_url() . '/assets/js/chosen/chosen.jquery'.$suffix.'.js', array('jquery'), $woocommerce->version );

	// Get admin screen id
    $screen = get_current_screen();

    // WooCommerce admin pages
    if (in_array( $screen->id, apply_filters( 'woocommerce_screen_ids', array( 'toplevel_page_woocommerce', 'woocommerce_page_woocommerce_settings', 'woocommerce_page_woocommerce_reports', 'edit-shop_order', 'edit-shop_coupon', 'shop_coupon', 'shop_order', 'edit-product', 'product' ) ) ) ) :

    	wp_enqueue_script( 'woocommerce_admin' );
    	wp_enqueue_script('farbtastic');
    	wp_enqueue_script( 'ajax-chosen' );
    	wp_enqueue_script( 'chosen' );
    	wp_enqueue_script( 'jquery-ui-sortable' );

    endif;

    // Edit product category pages
    if (in_array( $screen->id, array('edit-product_cat') )) :

		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );

	endif;

	// Product/Coupon/Orders
	if (in_array( $screen->id, array( 'shop_coupon', 'shop_order', 'product' ))) :

		wp_enqueue_script( 'woocommerce_writepanel' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_script( 'ajax-chosen' );
		wp_enqueue_script( 'chosen' );

		$woocommerce_witepanel_params = array(
			'remove_item_notice' 			=> __("Remove this item? If you have previously reduced this item's stock, or this order was submitted by a customer, will need to manually restore the item's stock.", 'woocommerce'),
			'remove_attribute'				=> __('Remove this attribute?', 'woocommerce'),
			'name_label'					=> __('Name', 'woocommerce'),
			'remove_label'					=> __('Remove', 'woocommerce'),
			'click_to_toggle'				=> __('Click to toggle', 'woocommerce'),
			'values_label'					=> __('Value(s)', 'woocommerce'),
			'text_attribute_tip'			=> __('Enter some text, or some attributes by pipe (|) separating values.', 'woocommerce'),
			'visible_label'					=> __('Visible on the product page', 'woocommerce'),
			'used_for_variations_label'		=> __('Used for variations', 'woocommerce'),
			'new_attribute_prompt'			=> __('Enter a name for the new attribute term:', 'woocommerce'),
			'calc_totals' 					=> __("Calculate totals based on order items, discount amount, and shipping? Note, you will need to (optionally) calculate tax rows and cart discounts manually.", 'woocommerce'),
			'calc_line_taxes' 				=> __("Calculate line taxes? This will calculate taxes based on the customers country. If no billing/shipping is set it will use the store base country.", 'woocommerce'),
			'copy_billing' 					=> __("Copy billing information to shipping information? This will remove any currently entered shipping information.", 'woocommerce'),
			'load_billing' 					=> __("Load the customer's billing information? This will remove any currently entered billing information.", 'woocommerce'),
			'load_shipping' 				=> __("Load the customer's shipping information? This will remove any currently entered shipping information.", 'woocommerce'),
			'featured_label'				=> __('Featured', 'woocommerce'),
			'tax_or_vat'					=> $woocommerce->countries->tax_or_vat(),
			'prices_include_tax' 			=> get_option('woocommerce_prices_include_tax'),
			'round_at_subtotal'				=> get_option( 'woocommerce_tax_round_at_subtotal' ),
			'meta_name'						=> __('Meta Name', 'woocommerce'),
			'meta_value'					=> __('Meta Value', 'woocommerce'),
			'no_customer_selected'			=> __('No customer selected', 'woocommerce'),
			'tax_label'						=> __('Tax Label:', 'woocommerce'),
			'compound_label'				=> __('Compound:', 'woocommerce'),
			'cart_tax_label'				=> __('Cart Tax:', 'woocommerce'),
			'shipping_tax_label'			=> __('Shipping Tax:', 'woocommerce'),
			'plugin_url' 					=> $woocommerce->plugin_url(),
			'ajax_url' 						=> admin_url('admin-ajax.php'),
			'add_order_item_nonce' 			=> wp_create_nonce("add-order-item"),
			'add_attribute_nonce' 			=> wp_create_nonce("add-attribute"),
			'calc_totals_nonce' 			=> wp_create_nonce("calc-totals"),
			'get_customer_details_nonce' 	=> wp_create_nonce("get-customer-details"),
			'search_products_nonce' 		=> wp_create_nonce("search-products"),
			'calendar_image'				=> $woocommerce->plugin_url().'/assets/images/calendar.png',
			'post_id'						=> $post->ID
		 );

		wp_localize_script( 'woocommerce_writepanel', 'woocommerce_writepanel_params', $woocommerce_witepanel_params );

	endif;

	// Term ordering - only when sorting by term_order
	if ( ( strstr( $screen->id, 'edit-pa_' ) || ( ! empty( $_GET['taxonomy'] ) && in_array( $_GET['taxonomy'], apply_filters( 'woocommerce_sortable_taxonomies', array( 'product_cat' ) ) ) ) ) && ! isset( $_GET['orderby'] ) ) :

		wp_register_script( 'woocommerce_term_ordering', $woocommerce->plugin_url() . '/assets/js/admin/term-ordering.js', array('jquery-ui-sortable'), $woocommerce->version );
		wp_enqueue_script( 'woocommerce_term_ordering' );

		$taxonomy = isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : '';

		$woocommerce_term_order_params = array(
			'taxonomy' 			=>  $taxonomy
		 );

		wp_localize_script( 'woocommerce_term_ordering', 'woocommerce_term_ordering_params', $woocommerce_term_order_params );

	endif;

	// Product sorting - only when sorting by menu order on the products page
	if ( current_user_can('edit_others_pages') && $screen->id == 'edit-product' && isset( $wp_query->query['orderby'] ) && $wp_query->query['orderby'] == 'menu_order title' ) {

		wp_enqueue_script( 'woocommerce_product_ordering', $woocommerce->plugin_url() . '/assets/js/admin/product-ordering.js', array('jquery-ui-sortable'), '1.0', true );

	}

	// Reports pages
    if ( $screen->id == apply_filters( 'woocommerce_reports_screen_id', 'woocommerce_page_woocommerce_reports' ) ) {

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
	global $woocommerce, $typenow, $post;

	if ($typenow=='post' && isset($_GET['post']) && !empty($_GET['post'])) :
		$typenow = $post->post_type;
	elseif (empty($typenow) && !empty($_GET['post'])) :
        $post = get_post($_GET['post']);
        $typenow = $post->post_type;
    endif;

	if ( $typenow == '' || $typenow=="product" || $typenow=="shop_order" || $typenow=="shop_coupon" ) :
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );
		wp_enqueue_style( 'jquery-ui-style', (is_ssl()) ? 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' : 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
	endif;

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
 * Add functionality to the image uploader on product pages to exclude an image
 *
 * @access public
 * @param mixed $fields
 * @param mixed $object
 * @return void
 */
function woocommerce_exclude_image_from_product_page_field( $fields, $object ) {

	if (!$object->post_parent) return $fields;

	$parent = get_post( $object->post_parent );

	if ($parent->post_type!=='product') return $fields;

	$exclude_image = (int) get_post_meta($object->ID, '_woocommerce_exclude_image', true);

	$label = __('Exclude image', 'woocommerce');

	$html = '<input type="checkbox" '.checked($exclude_image, 1, false).' name="attachments['.$object->ID.'][woocommerce_exclude_image]" id="attachments['.$object->ID.'][woocommerce_exclude_image" />';

	$fields['woocommerce_exclude_image'] = array(
			'label' => $label,
			'input' => 'html',
			'html' =>  $html,
			'value' => '',
			'helps' => __('Enabling this option will hide it from the product page image gallery.', 'woocommerce')
	);

	return $fields;
}

add_filter('attachment_fields_to_edit', 'woocommerce_exclude_image_from_product_page_field', 1, 2);
add_filter('attachment_fields_to_save', 'woocommerce_exclude_image_from_product_page_field_save', 1, 2);
add_action('add_attachment', 'woocommerce_exclude_image_from_product_page_field_add');


/**
 * Save the meta for exlcuding images from galleries.
 *
 * @access public
 * @param mixed $post
 * @param mixed $attachment
 * @return void
 */
function woocommerce_exclude_image_from_product_page_field_save( $post, $attachment ) {

	if (isset($_REQUEST['attachments'][$post['ID']]['woocommerce_exclude_image'])) :
		delete_post_meta( (int) $post['ID'], '_woocommerce_exclude_image' );
		update_post_meta( (int) $post['ID'], '_woocommerce_exclude_image', 1);
	else :
		delete_post_meta( (int) $post['ID'], '_woocommerce_exclude_image' );
		update_post_meta( (int) $post['ID'], '_woocommerce_exclude_image', 0);
	endif;

	return $post;

}

/**
 * Add the meta for exlcuding images from galleries.
 *
 * @access public
 * @param mixed $post_id
 * @return void
 */
function woocommerce_exclude_image_from_product_page_field_add( $post_id ) {
	add_post_meta( $post_id, '_woocommerce_exclude_image', 0, true );
}


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
		1 => sprintf( __('Product updated. <a href="%s">View Product</a>', 'woocommerce'), esc_url( get_permalink($post_ID) ) ),
		2 => __('Custom field updated.', 'woocommerce'),
		3 => __('Custom field deleted.', 'woocommerce'),
		4 => __('Product updated.', 'woocommerce'),
		5 => isset($_GET['revision']) ? sprintf( __('Product restored to revision from %s', 'woocommerce'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Product published. <a href="%s">View Product</a>', 'woocommerce'), esc_url( get_permalink($post_ID) ) ),
		7 => __('Product saved.', 'woocommerce'),
		8 => sprintf( __('Product submitted. <a target="_blank" href="%s">Preview Product</a>', 'woocommerce'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9 => sprintf( __('Product scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Product</a>', 'woocommerce'),
		  date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		10 => sprintf( __('Product draft updated. <a target="_blank" href="%s">Preview Product</a>', 'woocommerce'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	);

	$messages['shop_order'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => __('Order updated.', 'woocommerce'),
		2 => __('Custom field updated.', 'woocommerce'),
		3 => __('Custom field deleted.', 'woocommerce'),
		4 => __('Order updated.', 'woocommerce'),
		5 => isset($_GET['revision']) ? sprintf( __('Order restored to revision from %s', 'woocommerce'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __('Order updated.', 'woocommerce'),
		7 => __('Order saved.', 'woocommerce'),
		8 => __('Order submitted.', 'woocommerce'),
		9 => sprintf( __('Order scheduled for: <strong>%1$s</strong>.', 'woocommerce'),
		  date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $post->post_date ) ) ),
		10 => __('Order draft updated.', 'woocommerce')
	);

	$messages['shop_coupon'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => __('Coupon updated.', 'woocommerce'),
		2 => __('Custom field updated.', 'woocommerce'),
		3 => __('Custom field deleted.', 'woocommerce'),
		4 => __('Coupon updated.', 'woocommerce'),
		5 => isset($_GET['revision']) ? sprintf( __('Coupon restored to revision from %s', 'woocommerce'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __('Coupon updated.', 'woocommerce'),
		7 => __('Coupon saved.', 'woocommerce'),
		8 => __('Coupon submitted.', 'woocommerce'),
		9 => sprintf( __('Coupon scheduled for: <strong>%1$s</strong>.', 'woocommerce'),
		  date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $post->post_date ) ) ),
		10 => __('Coupon draft updated.', 'woocommerce')
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