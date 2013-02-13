<?php
/**
 * Defines the array of settings which are displayed in admin.
 *
 * Settings are defined here and displayed via functions.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Settings
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;

$localisation_setting = defined( 'WPLANG' ) && file_exists( $woocommerce->plugin_path() . '/i18n/languages/informal/woocommerce-' . WPLANG . '.mo' ) ? array(
	'title' => __( 'Localisation', 'woocommerce' ),
	'desc' 		=> sprintf( __( 'Use informal localisation for %s', 'woocommerce' ), WPLANG ),
	'id' 		=> 'woocommerce_informal_localisation_type',
	'type' 		=> 'checkbox',
	'default'	=> 'no',
) : array();

$currency_code_options = get_woocommerce_currencies();

foreach ( $currency_code_options as $code => $name ) {
	$currency_code_options[ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
}

$woocommerce_settings['general'] = apply_filters('woocommerce_general_settings', array(

	array( 'title' => __( 'General Options', 'woocommerce' ), 'type' => 'title', 'desc' => '', 'id' => 'general_options' ),

	array(
		'title' 	=> __( 'Base Location', 'woocommerce' ),
		'desc' 		=> __( 'This is the base location for your business. Tax rates will be based on this country.', 'woocommerce' ),
		'id' 		=> 'woocommerce_default_country',
		'css' 		=> 'min-width:350px;',
		'default'	=> 'GB',
		'type' 		=> 'single_select_country',
		'desc_tip'	=>  true,
	),

	array(
		'title' 	=> __( 'Currency', 'woocommerce' ),
		'desc' 		=> __( "This controls what currency prices are listed at in the catalog and which currency gateways will take payments in.", 'woocommerce' ),
		'id' 		=> 'woocommerce_currency',
		'css' 		=> 'min-width:350px;',
		'default'	=> 'GBP',
		'type' 		=> 'select',
		'class'		=> 'chosen_select',
		'desc_tip'	=>  true,
		'options'   => $currency_code_options
	),

	array(
		'title' => __( 'Allowed Countries', 'woocommerce' ),
		'desc' 		=> __( 'These are countries that you are willing to ship to.', 'woocommerce' ),
		'id' 		=> 'woocommerce_allowed_countries',
		'default'	=> 'all',
		'type' 		=> 'select',
		'class'		=> 'chosen_select',
		'css' 		=> 'min-width:350px;',
		'desc_tip'	=>  true,
		'options' => array(
			'all'  => __( 'All Countries', 'woocommerce' ),
			'specific' => __( 'Specific Countries', 'woocommerce' )
		)
	),

	array(
		'title' => __( 'Specific Countries', 'woocommerce' ),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_specific_allowed_countries',
		'css' 		=> '',
		'default'	=> '',
		'type' 		=> 'multi_select_countries'
	),

	$localisation_setting,

	array(
		'title' => __( 'Store Notice', 'woocommerce' ),
		'desc' 		=> __( 'Enable site-wide store notice text', 'woocommerce' ),
		'id' 		=> 'woocommerce_demo_store',
		'default'	=> 'no',
		'type' 		=> 'checkbox'
	),

	array(
		'title' => __( 'Store Notice Text', 'woocommerce' ),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_demo_store_notice',
		'default'	=> __( 'This is a demo store for testing purposes &mdash; no orders shall be fulfilled.', 'woocommerce' ),
		'type' 		=> 'text',
		'css' 		=> 'min-width:300px;',
	),

	array( 'type' => 'sectionend', 'id' => 'general_options'),

	array(	'title' => __( 'Cart, Checkout and Accounts', 'woocommerce' ), 'type' => 'title', 'id' => 'checkout_account_options' ),

	array(
		'title' => __( 'Coupons', 'woocommerce' ),
		'desc'          => __( 'Enable the use of coupons', 'woocommerce' ),
		'id'            => 'woocommerce_enable_coupons',
		'default'       => 'yes',
		'type'          => 'checkbox',
		'desc_tip'		=>  __( 'Coupons can be applied from the cart and checkout pages.', 'woocommerce' ),
	),

	array(
		'title' => __( 'Checkout', 'woocommerce' ),
		'desc' 		=> __( 'Enable guest checkout (no account required)', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_guest_checkout',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'	=> 'start'
	),

	array(
		'desc' 		=> __( 'Enable customer note field on checkout', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_order_comments',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc' 		=> __( 'Force secure checkout', 'woocommerce' ),
		'id' 		=> 'woocommerce_force_ssl_checkout',
		'default'	=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> '',
		'show_if_checked' => 'option',
		'desc_tip'	=>  __( 'Force SSL (HTTPS) on the checkout pages (an SSL Certificate is required).', 'woocommerce' ),
	),

	array(
		'desc' 		=> __( 'Un-force HTTPS when leaving the checkout', 'woocommerce' ),
		'id' 		=> 'woocommerce_unforce_ssl_checkout',
		'default'	=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end',
		'show_if_checked' => 'yes',
	),

	array(
		'title' => __( 'Registration', 'woocommerce' ),
		'desc' 		=> __( 'Allow registration on the checkout page', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_signup_and_login_from_checkout',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Allow registration on the "My Account" page', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_myaccount_registration',
		'default'	=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc' 		=> __( 'Register using the email address for the username', 'woocommerce' ),
		'id' 		=> 'woocommerce_registration_email_for_username',
		'default'	=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array(
		'title' => __( 'Customer Accounts', 'woocommerce' ),
		'desc' 		=> __( 'Prevent customers from accessing WordPress admin', 'woocommerce' ),
		'id' 		=> 'woocommerce_lock_down_admin',
		'default'	=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Clear cart when logging out', 'woocommerce' ),
		'id' 		=> 'woocommerce_clear_cart_on_logout',
		'default'	=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc' 		=> __( 'Allow customers to repurchase orders from their account page', 'woocommerce' ),
		'id' 		=> 'woocommerce_allow_customers_to_reorder',
		'default'	=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array( 'type' => 'sectionend', 'id' => 'checkout_account_options'),

	array(	'title' => __( 'Styles and Scripts', 'woocommerce' ), 'type' => 'title', 'id' => 'script_styling_options' ),

	array(
		'title' => __( 'Styling', 'woocommerce' ),
		'desc' 		=> __( 'Enable WooCommerce CSS', 'woocommerce' ),
		'id' 		=> 'woocommerce_frontend_css',
		'default'	=> 'yes',
		'type' 		=> 'checkbox'
	),

	array(
		'type' 		=> 'frontend_styles'
	),

	array(
		'title' => __( 'Scripts', 'woocommerce' ),
		'desc' 	=> __( 'Enable Lightbox', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_lightbox',
		'default'	=> 'yes',
		'desc_tip'	=> __( 'Include WooCommerce\'s lightbox. Product gallery images and the add review form will open in a lightbox.', 'woocommerce' ),
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Enable enhanced country select boxes', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_chosen',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end',
		'desc_tip'	=> __( 'This will enable a script allowing the country fields to be searchable.', 'woocommerce' ),
	),

	array( 'type' => 'sectionend', 'id' => 'script_styling_options'),

	array(	'title' => __( 'Downloadable Products', 'woocommerce' ), 'type' => 'title', 'id' => 'digital_download_options' ),

	array(
		'title' => __( 'File Download Method', 'woocommerce' ),
		'desc' 		=> __( 'Forcing downloads will keep URLs hidden, but some servers may serve large files unreliably. If supported, <code>X-Accel-Redirect</code>/ <code>X-Sendfile</code> can be used to serve downloads instead (server requires <code>mod_xsendfile</code>).', 'woocommerce' ),
		'id' 		=> 'woocommerce_file_download_method',
		'type' 		=> 'select',
		'class'		=> 'chosen_select',
		'css' 		=> 'min-width:300px;',
		'default'	=> 'force',
		'desc_tip'	=>  true,
		'options' => array(
			'force'  	=> __( 'Force Downloads', 'woocommerce' ),
			'xsendfile' => __( 'X-Accel-Redirect/X-Sendfile', 'woocommerce' ),
			'redirect'  => __( 'Redirect only', 'woocommerce' ),
		)
	),

	array(
		'title' => __( 'Access Restriction', 'woocommerce' ),
		'desc' 		=> __( 'Downloads require login', 'woocommerce' ),
		'id' 		=> 'woocommerce_downloads_require_login',
		'type' 		=> 'checkbox',
		'default'	=> 'no',
		'desc_tip'	=> __( 'This setting does not apply to guest purchases.', 'woocommerce' ),
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Grant access to downloadable products after payment', 'woocommerce' ),
		'id' 		=> 'woocommerce_downloads_grant_access_after_payment',
		'type' 		=> 'checkbox',
		'default'	=> 'yes',
		'desc_tip'	=> __( 'Enable this option to grant access to downloads when orders are "processing", rather than "completed".', 'woocommerce' ),
		'checkboxgroup'		=> 'end'
	),

	array( 'type' => 'sectionend', 'id' => 'digital_download_options' ),

)); // End general settings

// Get shop page
$shop_page_id = woocommerce_get_page_id('shop');

$base_slug = ($shop_page_id > 0 && get_page( $shop_page_id )) ? get_page_uri( $shop_page_id ) : 'shop';

$woocommerce_prepend_shop_page_to_products_warning = '';

if ( $shop_page_id > 0 && sizeof(get_pages("child_of=$shop_page_id")) > 0 )
	$woocommerce_prepend_shop_page_to_products_warning = ' <mark class="notice">' . __( 'Note: The shop page has children - child pages will not work if you enable this option.', 'woocommerce' ) . '</mark>';

$woocommerce_settings['pages'] = apply_filters('woocommerce_page_settings', array(

	array(
		'title' => __( 'Page Setup', 'woocommerce' ),
		'type' => 'title',
		'desc' => sprintf( __( 'Set up core WooCommerce pages here, for example the base page. The base page can also be used in your %sproduct permalinks%s.', 'woocommerce' ), '<a target="_blank" href="' . admin_url( 'options-permalink.php' ) . '">', '</a>' ),
		'id' => 'page_options'
	),

	array(
		'title' => __( 'Shop Base Page', 'woocommerce' ),
		'desc' 		=> __( 'This sets the base page of your shop - this is where your product archive will be.', 'woocommerce' ),
		'id' 		=> 'woocommerce_shop_page_id',
		'type' 		=> 'single_select_page',
		'default'	=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true
	),

	array(
		'title' => __( 'Base Page Title', 'woocommerce' ),
		'desc' 		=> __( 'This title to show on the shop base page. Leave blank to use the page title.', 'woocommerce' ),
		'id' 		=> 'woocommerce_shop_page_title',
		'type' 		=> 'text',
		'css' 		=> 'min-width:300px;',
		'default'	=> 'All Products', // Default value for the page title - changed in settings
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Terms Page ID', 'woocommerce' ),
		'desc' 		=> __( 'If you define a "Terms" page the customer will be asked if they accept them when checking out.', 'woocommerce' ),
		'id' 		=> 'woocommerce_terms_page_id',
		'default'	=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'type' 		=> 'single_select_page',
		'desc_tip'	=>  true,
	),

	array( 'type' => 'sectionend', 'id' => 'page_options' ),

	array( 'title' => __( 'Shop Pages', 'woocommerce' ), 'type' => 'title', 'desc' => __( 'The following pages need selecting so that WooCommerce knows where they are. These pages should have been created upon installation of the plugin, if not you will need to create them.', 'woocommerce' ) ),

	array(
		'title' => __( 'Cart Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_cart]', 'woocommerce' ),
		'id' 		=> 'woocommerce_cart_page_id',
		'type' 		=> 'single_select_page',
		'default'	=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Checkout Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_checkout]', 'woocommerce' ),
		'id' 		=> 'woocommerce_checkout_page_id',
		'type' 		=> 'single_select_page',
		'default'	=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Pay Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_pay] Parent: "Checkout"', 'woocommerce' ),
		'id' 		=> 'woocommerce_pay_page_id',
		'type' 		=> 'single_select_page',
		'default'	=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Thanks Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_thankyou] Parent: "Checkout"', 'woocommerce' ),
		'id' 		=> 'woocommerce_thanks_page_id',
		'type' 		=> 'single_select_page',
		'default'	=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'My Account Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_my_account]', 'woocommerce' ),
		'id' 		=> 'woocommerce_myaccount_page_id',
		'type' 		=> 'single_select_page',
		'default'	=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Edit Address Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_edit_address] Parent: "My Account"', 'woocommerce' ),
		'id' 		=> 'woocommerce_edit_address_page_id',
		'type' 		=> 'single_select_page',
		'default'	=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'View Order Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_view_order] Parent: "My Account"', 'woocommerce' ),
		'id' 		=> 'woocommerce_view_order_page_id',
		'type' 		=> 'single_select_page',
		'default'	=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Change Password Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_change_password] Parent: "My Account"', 'woocommerce' ),
		'id' 		=> 'woocommerce_change_password_page_id',
		'type' 		=> 'single_select_page',
		'default'	=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Logout Page', 'woocommerce' ),
		'desc' 		=> __( 'Parent: "My Account"', 'woocommerce' ),
		'id' 		=> 'woocommerce_logout_page_id',
		'type' 		=> 'single_select_page',
		'default'	=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Lost Password Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_lost_password] Parent: "My Account"', 'woocommerce' ),
		'id' 		=> 'woocommerce_lost_password_page_id',
		'type' 		=> 'single_select_page',
		'default'	=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array( 'type' => 'sectionend', 'id' => 'page_options')

)); // End pages settings


$woocommerce_settings['catalog'] = apply_filters('woocommerce_catalog_settings', array(

	array(	'title' => __( 'Catalog Options', 'woocommerce' ), 'type' => 'title','desc' => '', 'id' => 'catalog_options' ),

	array(
		'title' => __( 'Default Product Sorting', 'woocommerce' ),
		'desc' 		=> __( 'This controls the default sort order of the catalog.', 'woocommerce' ),
		'id' 		=> 'woocommerce_default_catalog_orderby',
		'css' 		=> 'min-width:150px;',
		'default'	=> 'title',
		'type' 		=> 'select',
		'options' => apply_filters('woocommerce_default_catalog_orderby_options', array(
			'menu_order' => __( 'Default sorting (custom ordering + name)', 'woocommerce' ),
			'popularity' => __( 'Popularity (sales)', 'woocommerce' ),
			'rating'     => __( 'Average Rating', 'woocommerce' ),
			'date'       => __( 'Sort by most recent', 'woocommerce' ),
			'price'      => __( 'Sort by price (asc)', 'woocommerce' ),
			'price-desc' => __( 'Sort by price (desc)', 'woocommerce' ),
		)),
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Shop Page Display', 'woocommerce' ),
		'desc' 		=> __( 'This controls what is shown on the product archive.', 'woocommerce' ),
		'id' 		=> 'woocommerce_shop_page_display',
		'css' 		=> 'min-width:150px;',
		'default'	=> '',
		'type' 		=> 'select',
		'options' => array(
			''  			=> __( 'Show products', 'woocommerce' ),
			'subcategories' => __( 'Show subcategories', 'woocommerce' ),
			'both'   		=> __( 'Show both', 'woocommerce' ),
		),
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Default Category Display', 'woocommerce' ),
		'desc' 		=> __( 'This controls what is shown on category archives.', 'woocommerce' ),
		'id' 		=> 'woocommerce_category_archive_display',
		'css' 		=> 'min-width:150px;',
		'default'	=> '',
		'type' 		=> 'select',
		'options' => array(
			''  			=> __( 'Show products', 'woocommerce' ),
			'subcategories' => __( 'Show subcategories', 'woocommerce' ),
			'both'   		=> __( 'Show both', 'woocommerce' ),
		),
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Add to cart', 'woocommerce' ),
		'desc' 		=> __( 'Redirect to the cart page after successful addition', 'woocommerce' ),
		'id' 		=> 'woocommerce_cart_redirect_after_add',
		'default'	=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Enable AJAX add to cart buttons on archives', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_ajax_add_to_cart',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array( 'type' => 'sectionend', 'id' => 'catalog_options' ),

	array(	'title' => __( 'Product Data', 'woocommerce' ), 'type' => 'title', 'desc' => __( 'The following options affect the fields available on the edit product page.', 'woocommerce' ), 'id' => 'product_data_options' ),

	array(
		'title' => __( 'Product Fields', 'woocommerce' ),
		'desc' 		=> __( 'Enable the <strong>SKU</strong> field for products', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_sku',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Enable the <strong>weight</strong> field for products (some shipping methods may require this)', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_weight',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc' 		=> __( 'Enable the <strong>dimension</strong> fields for products (some shipping methods may require this)', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_dimensions',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc' 		=> __( 'Show <strong>weight and dimension</strong> values on the <strong>Additional Information</strong> tab', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_dimension_product_attributes',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array(
		'title' => __( 'Weight Unit', 'woocommerce' ),
		'desc' 		=> __( 'This controls what unit you will define weights in.', 'woocommerce' ),
		'id' 		=> 'woocommerce_weight_unit',
		'css' 		=> 'min-width:150px;',
		'default'	=> 'kg',
		'type' 		=> 'select',
		'options' => array(
			'kg'  => __( 'kg', 'woocommerce' ),
			'g'   => __( 'g', 'woocommerce' ),
			'lbs' => __( 'lbs', 'woocommerce' ),
			'oz' => __( 'oz', 'woocommerce' ),
		),
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Dimensions Unit', 'woocommerce' ),
		'desc' 		=> __( 'This controls what unit you will define lengths in.', 'woocommerce' ),
		'id' 		=> 'woocommerce_dimension_unit',
		'css' 		=> 'min-width:150px;',
		'default'	=> 'cm',
		'type' 		=> 'select',
		'options' => array(
			'm'  => __( 'm', 'woocommerce' ),
			'cm' => __( 'cm', 'woocommerce' ),
			'mm' => __( 'mm', 'woocommerce' ),
			'in' => __( 'in', 'woocommerce' ),
			'yd' => __( 'yd', 'woocommerce' ),
		),
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Product Ratings', 'woocommerce' ),
		'desc' 		=> __( 'Enable ratings on reviews', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_review_rating',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start',
		'show_if_checked' => 'option',
	),

	array(
		'desc' 		=> __( 'Ratings are required to leave a review', 'woocommerce' ),
		'id' 		=> 'woocommerce_review_rating_required',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> '',
		'show_if_checked' => 'yes',
	),

	array(
		'desc' 		=> __( 'Show "verified owner" label for customer reviews', 'woocommerce' ),
		'id' 		=> 'woocommerce_review_rating_verification_label',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end',
		'show_if_checked' => 'yes',
	),

	array( 'type' => 'sectionend', 'id' => 'product_review_options' ),

	array(	'title' => __( 'Pricing Options', 'woocommerce' ), 'type' => 'title', 'desc' => __( 'The following options affect how prices are displayed on the frontend.', 'woocommerce' ), 'id' => 'pricing_options' ),

	array(
		'title' => __( 'Currency Position', 'woocommerce' ),
		'desc' 		=> __( 'This controls the position of the currency symbol.', 'woocommerce' ),
		'id' 		=> 'woocommerce_currency_pos',
		'css' 		=> 'min-width:150px;',
		'default'	=> 'left',
		'type' 		=> 'select',
		'options' => array(
			'left' => __( 'Left', 'woocommerce' ),
			'right' => __( 'Right', 'woocommerce' ),
			'left_space' => __( 'Left (with space)', 'woocommerce' ),
			'right_space' => __( 'Right (with space)', 'woocommerce' )
		),
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Thousand Separator', 'woocommerce' ),
		'desc' 		=> __( 'This sets the thousand separator of displayed prices.', 'woocommerce' ),
		'id' 		=> 'woocommerce_price_thousand_sep',
		'css' 		=> 'width:50px;',
		'default'	=> ',',
		'type' 		=> 'text',
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Decimal Separator', 'woocommerce' ),
		'desc' 		=> __( 'This sets the decimal separator of displayed prices.', 'woocommerce' ),
		'id' 		=> 'woocommerce_price_decimal_sep',
		'css' 		=> 'width:50px;',
		'default'	=> '.',
		'type' 		=> 'text',
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Number of Decimals', 'woocommerce' ),
		'desc' 		=> __( 'This sets the number of decimal points shown in displayed prices.', 'woocommerce' ),
		'id' 		=> 'woocommerce_price_num_decimals',
		'css' 		=> 'width:50px;',
		'default'	=> '2',
		'desc_tip'	=>  true,
		'type' 		=> 'number',
		'custom_attributes' => array(
			'min' 	=> 0,
			'step' 	=> 1
		)
	),

	array(
		'title'		=> __( 'Trailing Zeros', 'woocommerce' ),
		'desc' 		=> __( 'Remove zeros after the decimal point. e.g. <code>$10.00</code> becomes <code>$10</code>', 'woocommerce' ),
		'id' 		=> 'woocommerce_price_trim_zeros',
		'default'	=> 'yes',
		'type' 		=> 'checkbox'
	),

	array( 'type' => 'sectionend', 'id' => 'pricing_options' ),

	array(	'title' => __( 'Image Options', 'woocommerce' ), 'type' => 'title','desc' => sprintf(__( 'These settings affect the actual dimensions of images in your catalog - the display on the front-end will still be affected by CSS styles. After changing these settings you may need to <a href="%s">regenerate your thumbnails</a>.', 'woocommerce' ), 'http://wordpress.org/extend/plugins/regenerate-thumbnails/'), 'id' => 'image_options' ),

	array(
		'title' => __( 'Catalog Images', 'woocommerce' ),
		'desc' 		=> __( 'This size is usually used in product listings', 'woocommerce' ),
		'id' 		=> 'shop_catalog_image_size',
		'css' 		=> '',
		'type' 		=> 'image_width',
		'default'	=> array(
			'width' 	=> '150',
			'height'	=> '150',
			'crop'		=> true
		),
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Single Product Image', 'woocommerce' ),
		'desc' 		=> __( 'This is the size used by the main image on the product page.', 'woocommerce' ),
		'id' 		=> 'shop_single_image_size',
		'css' 		=> '',
		'type' 		=> 'image_width',
		'default'	=> array(
			'width' 	=> '300',
			'height'	=> '300',
			'crop'		=> 1
		),
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Product Thumbnails', 'woocommerce' ),
		'desc' 		=> __( 'This size is usually used for the gallery of images on the product page.', 'woocommerce' ),
		'id' 		=> 'shop_thumbnail_image_size',
		'css' 		=> '',
		'type' 		=> 'image_width',
		'default'	=> array(
			'width' 	=> '90',
			'height'	=> '90',
			'crop'		=> 1
		),
		'desc_tip'	=>  true,
	),

	array( 'type' => 'sectionend', 'id' => 'image_options' ),

)); // End catalog settings


$woocommerce_settings['inventory'] = apply_filters('woocommerce_inventory_settings', array(

	array(	'title' => __( 'Inventory Options', 'woocommerce' ), 'type' => 'title','desc' => '', 'id' => 'inventory_options' ),

	array(
		'title' => __( 'Manage Stock', 'woocommerce' ),
		'desc' 		=> __( 'Enable stock management', 'woocommerce' ),
		'id' 		=> 'woocommerce_manage_stock',
		'default'	=> 'yes',
		'type' 		=> 'checkbox'
	),

	array(
		'title' => __( 'Hold Stock (minutes)', 'woocommerce' ),
		'desc' 		=> __( 'Hold stock (for unpaid orders) for x minutes. When this limit is reached, the pending order will be cancelled. Leave blank to disable.', 'woocommerce' ),
		'id' 		=> 'woocommerce_hold_stock_minutes',
		'type' 		=> 'number',
		'custom_attributes' => array(
			'min' 	=> 0,
			'step' 	=> 1
		),
		'css' 		=> 'width:50px;',
		'default'	=> '60'
	),

	array(
		'title' => __( 'Notifications', 'woocommerce' ),
		'desc' 		=> __( 'Enable low stock notifications', 'woocommerce' ),
		'id' 		=> 'woocommerce_notify_low_stock',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup' => 'start'
	),

	array(
		'desc' 		=> __( 'Enable out of stock notifications', 'woocommerce' ),
		'id' 		=> 'woocommerce_notify_no_stock',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup' => 'end'
	),

	array(
		'title' => __( 'Notification Recipient', 'woocommerce' ),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_stock_email_recipient',
		'type' 		=> 'email',
		'default'	=> get_option( 'admin_email' )
	),

	array(
		'title' => __( 'Low Stock Threshold', 'woocommerce' ),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_notify_low_stock_amount',
		'css' 		=> 'width:50px;',
		'type' 		=> 'number',
		'custom_attributes' => array(
			'min' 	=> 0,
			'step' 	=> 1
		),
		'default'	=> '2'
	),

	array(
		'title' => __( 'Out Of Stock Threshold', 'woocommerce' ),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_notify_no_stock_amount',
		'css' 		=> 'width:50px;',
		'type' 		=> 'number',
		'custom_attributes' => array(
			'min' 	=> 0,
			'step' 	=> 1
		),
		'default'	=> '0'
	),

	array(
		'title' => __( 'Out Of Stock Visibility', 'woocommerce' ),
		'desc' 		=> __( 'Hide out of stock items from the catalog', 'woocommerce' ),
		'id' 		=> 'woocommerce_hide_out_of_stock_items',
		'default'	=> 'no',
		'type' 		=> 'checkbox'
	),

	array(
		'title' => __( 'Stock Display Format', 'woocommerce' ),
		'desc' 		=> __( 'This controls how stock is displayed on the frontend.', 'woocommerce' ),
		'id' 		=> 'woocommerce_stock_format',
		'css' 		=> 'min-width:150px;',
		'default'	=> '',
		'type' 		=> 'select',
		'options' => array(
			''  			=> __( 'Always show stock e.g. "12 in stock"', 'woocommerce' ),
			'low_amount'	=> __( 'Only show stock when low e.g. "Only 2 left in stock" vs. "In Stock"', 'woocommerce' ),
			'no_amount' 	=> __( 'Never show stock amount', 'woocommerce' ),
		),
		'desc_tip'	=>  true,
	),

	array( 'type' => 'sectionend', 'id' => 'inventory_options'),

)); // End inventory settings


$woocommerce_settings['shipping'] = apply_filters('woocommerce_shipping_settings', array(

	array( 'title' => __( 'Shipping Options', 'woocommerce' ), 'type' => 'title', 'id' => 'shipping_options' ),

	array(
		'title' 		=> __( 'Shipping Calculations', 'woocommerce' ),
		'desc' 		=> __( 'Enable shipping', 'woocommerce' ),
		'id' 		=> 'woocommerce_calc_shipping',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Enable the shipping calculator on the cart page', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_shipping_calc',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc' 		=> __( 'Hide shipping costs until an address is entered', 'woocommerce' ),
		'id' 		=> 'woocommerce_shipping_cost_requires_address',
		'default'	=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array(
		'title' 	=> __( 'Shipping Method Display', 'woocommerce' ),
		'desc' 		=> __( 'This controls how multiple shipping methods are displayed on the frontend.', 'woocommerce' ),
		'id' 		=> 'woocommerce_shipping_method_format',
		'css' 		=> 'min-width:150px;',
		'default'	=> '',
		'type' 		=> 'select',
		'options' => array(
			''  			=> __( 'Radio buttons', 'woocommerce' ),
			'select'		=> __( 'Select box', 'woocommerce' ),
		),
		'desc_tip'	=>  true,
	),

	array(
		'title' 	=> __( 'Shipping Destination', 'woocommerce' ),
		'desc' 		=> __( 'Only ship to the users billing address', 'woocommerce' ),
		'id' 		=> 'woocommerce_ship_to_billing_address_only',
		'default'	=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Ship to billing address by default', 'woocommerce' ),
		'id' 		=> 'woocommerce_ship_to_same_address',
		'default'	=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc' 		=> __( 'Collect shipping address even when not required', 'woocommerce' ),
		'id' 		=> 'woocommerce_require_shipping_address',
		'default'	=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array(
		'type' 		=> 'shipping_methods',
	),

	array( 'type' => 'sectionend', 'id' => 'shipping_options' ),

)); // End shipping settings


$woocommerce_settings['payment_gateways'] = apply_filters('woocommerce_payment_gateways_settings', array(

	array( 'title' => __( 'Payment Gateways', 'woocommerce' ), 'desc' => __( 'Installed payment gateways are displayed below. Drag and drop payment gateways to control their display order on the checkout.', 'woocommerce' ), 'type' => 'title', 'id' => 'payment_gateways_options' ),

	array(
		'type' 		=> 'payment_gateways',
	),

	array( 'type' => 'sectionend', 'id' => 'payment_gateways_options' ),

)); // End payment_gateway settings

$tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option( 'woocommerce_tax_classes' ) ) ) );
$classes_options = array();
if ( $tax_classes )
	foreach ( $tax_classes as $class )
		$classes_options[ sanitize_title( $class ) ] = esc_html( $class );

$woocommerce_settings['tax'] = apply_filters('woocommerce_tax_settings', array(

	array(	'title' => __( 'Tax Options', 'woocommerce' ), 'type' => 'title','desc' => '', 'id' => 'tax_options' ),

	array(
		'title' => __( 'Enable Taxes', 'woocommerce' ),
		'desc' 		=> __( 'Enable taxes and tax calculations', 'woocommerce' ),
		'id' 		=> 'woocommerce_calc_taxes',
		'default'	=> 'no',
		'type' 		=> 'checkbox'
	),

	array(
		'title' => __( 'Prices Entered With Tax', 'woocommerce' ),
		'id' 		=> 'woocommerce_prices_include_tax',
		'default'	=> 'no',
		'type' 		=> 'radio',
		'desc_tip'	=>  __( 'This option is important as it will affect how you input prices. Changing it will not update existing products.', 'woocommerce' ),
		'options'	=> array(
			'yes' => __( 'Yes, I will enter prices inclusive of tax', 'woocommerce' ),
			'no' => __( 'No, I will enter prices exclusive of tax', 'woocommerce' )
		),
	),

	array(
		'title'     => __( 'Calculate Tax Based On:', 'woocommerce' ),
		'id'        => 'woocommerce_tax_based_on',
		'desc_tip'	=>  __( 'This option determines which address is used to calculate tax.', 'woocommerce' ),
		'default'   => 'shipping',
		'type'      => 'select',
		'options'   => array(
			'shipping' => __( 'Customer shipping address', 'woocommerce' ),
			'billing'  => __( 'Customer billing address', 'woocommerce' ),
			'base'     => __( 'Shop base address', 'woocommerce' )
		),
	),

	array(
		'title'     => __( 'Default Customer Address:', 'woocommerce' ),
		'id'        => 'woocommerce_default_customer_address',
		'desc_tip'	=>  __( 'This option determines the customers default address (before they input their own).', 'woocommerce' ),
		'default'   => 'base',
		'type'      => 'select',
		'options'   => array(
			''     => __( 'No address', 'woocommerce' ),
			'base' => __( 'Shop base address', 'woocommerce' ),
		),
	),

	array(
		'title' 		=> __( 'Shipping Tax Class:', 'woocommerce' ),
		'desc' 		=> __( 'Optionally control which tax class shipping gets, or leave it so shipping tax is based on the cart items themselves.', 'woocommerce' ),
		'id' 		=> 'woocommerce_shipping_tax_class',
		'css' 		=> 'min-width:150px;',
		'default'	=> 'title',
		'type' 		=> 'select',
		'options' 	=> array( '' => __( 'Shipping tax class based on cart items', 'woocommerce' ), 'standard' => __( 'Standard', 'woocommerce' ) ) + $classes_options,
		'desc_tip'	=>  true,
	),

	array(
		'title' => __( 'Rounding', 'woocommerce' ),
		'desc' 		=> __( 'Round tax at subtotal level, instead of rounding per line', 'woocommerce' ),
		'id' 		=> 'woocommerce_tax_round_at_subtotal',
		'default'	=> 'no',
		'type' 		=> 'checkbox',
	),

	array(
		'title' 		=> __( 'Additional Tax Classes', 'woocommerce' ),
		'desc' 		=> __( 'List additonal tax classes below (1 per line). This is in addition to the default <code>Standard Rate</code>. Tax classes can be assigned to products.', 'woocommerce' ),
		'id' 		=> 'woocommerce_tax_classes',
		'css' 		=> 'width:100%; height: 65px;',
		'type' 		=> 'textarea',
		'default'	=> sprintf( __( 'Reduced Rate%sZero Rate', 'woocommerce' ), PHP_EOL )
	),

	array(
		'title'   => __( 'Display prices during cart/checkout:', 'woocommerce' ),
		'id'      => 'woocommerce_tax_display_cart',
		'default' => 'excl',
		'type'    => 'select',
		'options' => array(
			'incl'   => __( 'Including tax', 'woocommerce' ),
			'excl'   => __( 'Excluding tax', 'woocommerce' ),
		),
	),

	array( 'type' => 'sectionend', 'id' => 'tax_options' ),

)); // End tax settings

$woocommerce_settings['email'] = apply_filters('woocommerce_email_settings', array(

	array( 'type' => 'sectionend', 'id' => 'email_recipient_options' ),

	array(	'title' => __( 'Email Sender Options', 'woocommerce' ), 'type' => 'title', 'desc' => __( 'The following options affect the sender (email address and name) used in WooCommerce emails.', 'woocommerce' ), 'id' => 'email_options' ),

	array(
		'title' => __( '"From" Name', 'woocommerce' ),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_email_from_name',
		'type' 		=> 'text',
		'css' 		=> 'min-width:300px;',
		'default'	=> esc_attr(get_bloginfo('title'))
	),

	array(
		'title' => __( '"From" Email Address', 'woocommerce' ),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_email_from_address',
		'type' 		=> 'email',
		'custom_attributes' => array(
			'multiple' 	=> 'multiple'
		),
		'css' 		=> 'min-width:300px;',
		'default'	=> get_option('admin_email')
	),

	array( 'type' => 'sectionend', 'id' => 'email_options' ),

	array(	'title' => __( 'Email Template', 'woocommerce' ), 'type' => 'title', 'desc' => sprintf(__( 'This section lets you customise the WooCommerce emails. <a href="%s" target="_blank">Click here to preview your email template</a>. For more advanced control copy <code>woocommerce/templates/emails/</code> to <code>yourtheme/woocommerce/emails/</code>.', 'woocommerce' ), wp_nonce_url(admin_url('?preview_woocommerce_mail=true'), 'preview-mail')), 'id' => 'email_template_options' ),

	array(
		'title' => __( 'Header Image', 'woocommerce' ),
		'desc' 		=> sprintf(__( 'Enter a URL to an image you want to show in the email\'s header. Upload your image using the <a href="%s">media uploader</a>.', 'woocommerce' ), admin_url('media-new.php')),
		'id' 		=> 'woocommerce_email_header_image',
		'type' 		=> 'text',
		'css' 		=> 'min-width:300px;',
		'default'	=> ''
	),

	array(
		'title' => __( 'Email Footer Text', 'woocommerce' ),
		'desc' 		=> __( 'The text to appear in the footer of WooCommerce emails.', 'woocommerce' ),
		'id' 		=> 'woocommerce_email_footer_text',
		'css' 		=> 'width:100%; height: 75px;',
		'type' 		=> 'textarea',
		'default'	=> get_bloginfo('title') . ' - ' . __( 'Powered by WooCommerce', 'woocommerce' )
	),

	array(
		'title' => __( 'Base Colour', 'woocommerce' ),
		'desc' 		=> __( 'The base colour for WooCommerce email templates. Default <code>#557da1</code>.', 'woocommerce' ),
		'id' 		=> 'woocommerce_email_base_color',
		'type' 		=> 'color',
		'css' 		=> 'width:6em;',
		'default'	=> '#557da1'
	),

	array(
		'title' => __( 'Background Colour', 'woocommerce' ),
		'desc' 		=> __( 'The background colour for WooCommerce email templates. Default <code>#f5f5f5</code>.', 'woocommerce' ),
		'id' 		=> 'woocommerce_email_background_color',
		'type' 		=> 'color',
		'css' 		=> 'width:6em;',
		'default'	=> '#f5f5f5'
	),

	array(
		'title' => __( 'Email Body Background Colour', 'woocommerce' ),
		'desc' 		=> __( 'The main body background colour. Default <code>#fdfdfd</code>.', 'woocommerce' ),
		'id' 		=> 'woocommerce_email_body_background_color',
		'type' 		=> 'color',
		'css' 		=> 'width:6em;',
		'default'	=> '#fdfdfd'
	),

	array(
		'title' => __( 'Email Body Text Colour', 'woocommerce' ),
		'desc' 		=> __( 'The main body text colour. Default <code>#505050</code>.', 'woocommerce' ),
		'id' 		=> 'woocommerce_email_text_color',
		'type' 		=> 'color',
		'css' 		=> 'width:6em;',
		'default'	=> '#505050'
	),

	array( 'type' => 'sectionend', 'id' => 'email_template_options' ),

)); // End email settings