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

$localisation_setting = ( defined('WPLANG') ) ? array(
	'name' => __('Localisation', 'woocommerce'),
	'desc' 		=> __('Use informal localisation file if it exists', 'woocommerce'),
	'id' 		=> 'woocommerce_informal_localisation_type',
	'type' 		=> 'checkbox',
	'std' 		=> 'no',
) : array();

$woocommerce_settings['general'] = apply_filters('woocommerce_general_settings', array(

	array( 'name' => __( 'General Options', 'woocommerce' ), 'type' => 'title', 'desc' => '', 'id' => 'general_options' ),

	array(
		'name' => __( 'Base Country/Region', 'woocommerce' ),
		'desc' 		=> __( 'This is the base country for your business. Tax rates will be based on this country.', 'woocommerce' ),
		'id' 		=> 'woocommerce_default_country',
		'css' 		=> 'min-width:300px;',
		'std' 		=> 'GB',
		'type' 		=> 'single_select_country',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'Currency', 'woocommerce' ),
		'desc' 		=> __("This controls what currency prices are listed at in the catalog and which currency gateways will take payments in.", 'woocommerce' ),
		'id' 		=> 'woocommerce_currency',
		'css' 		=> 'min-width:300px;',
		'std' 		=> 'GBP',
		'type' 		=> 'select',
		'class'		=> 'chosen_select',
		'desc_tip'	=>  true,
		'options' => array_unique(apply_filters('woocommerce_currencies', array(
			'USD' => __( 'US Dollars (&#36;)', 'woocommerce' ),
			'EUR' => __( 'Euros (&euro;)', 'woocommerce' ),
			'GBP' => __( 'Pounds Sterling (&pound;)', 'woocommerce' ),
			'AUD' => __( 'Australian Dollars (&#36;)', 'woocommerce' ),
			'BRL' => __( 'Brazilian Real (&#36;)', 'woocommerce' ),
			'CAD' => __( 'Canadian Dollars (&#36;)', 'woocommerce' ),
			'CZK' => __( 'Czech Koruna (&#75;&#269;)', 'woocommerce' ),
			'DKK' => __( 'Danish Krone', 'woocommerce' ),
			'HKD' => __( 'Hong Kong Dollar (&#36;)', 'woocommerce' ),
			'HUF' => __( 'Hungarian Forint', 'woocommerce' ),
			'ILS' => __( 'Israeli Shekel', 'woocommerce' ),
			'RMB' => __( 'Chinese Yuan (&yen;)', 'woocommerce' ),
			'JPY' => __( 'Japanese Yen (&yen;)', 'woocommerce' ),
			'MYR' => __( 'Malaysian Ringgits (RM)', 'woocommerce' ),
			'MXN' => __( 'Mexican Peso (&#36;)', 'woocommerce' ),
			'NZD' => __( 'New Zealand Dollar (&#36;)', 'woocommerce' ),
			'NOK' => __( 'Norwegian Krone', 'woocommerce' ),
			'PHP' => __( 'Philippine Pesos', 'woocommerce' ),
			'PLN' => __( 'Polish Zloty', 'woocommerce' ),
			'SGD' => __( 'Singapore Dollar (&#36;)', 'woocommerce' ),
			'SEK' => __( 'Swedish Krona', 'woocommerce' ),
			'CHF' => __( 'Swiss Franc', 'woocommerce' ),
			'TWD' => __( 'Taiwan New Dollars', 'woocommerce' ),
			'THB' => __( 'Thai Baht', 'woocommerce' ),
			'TRY' => __( 'Turkish Lira (TL)', 'woocommerce' ),
			'ZAR' => __( 'South African rand (R)', 'woocommerce' ),
			'RON' => __( 'Romanian Leu (RON)', 'woocommerce' ),
			))
		)
	),

	array(
		'name' => __( 'Allowed Countries', 'woocommerce' ),
		'desc' 		=> __( 'These are countries that you are willing to ship to.', 'woocommerce' ),
		'id' 		=> 'woocommerce_allowed_countries',
		'std' 		=> 'all',
		'type' 		=> 'select',
		'class'		=> 'chosen_select',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
		'options' => array(
			'all'  => __( 'All Countries', 'woocommerce' ),
			'specific' => __( 'Specific Countries', 'woocommerce' )
		)
	),

	array(
		'name' => __( 'Specific Countries', 'woocommerce' ),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_specific_allowed_countries',
		'css' 		=> '',
		'std' 		=> '',
		'type' 		=> 'multi_select_countries'
	),

	$localisation_setting,

	array( 'type' => 'sectionend', 'id' => 'general_options'),

	array(	'name' => __( 'Checkout and Accounts', 'woocommerce' ), 'type' => 'title','desc' => __('The following options control the behaviour of the checkout process and customer accounts.', 'woocommerce'), 'id' => 'checkout_account_options' ),

	array(
		'name' => __( 'Checkout', 'woocommerce' ),
		'desc' 		=> __( 'Enable guest checkout (no account required)', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_guest_checkout',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'	=> 'start'
	),

	array(
		'desc' 		=> __( 'Show order comments section', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_order_comments',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array(
		'name' => __( 'Security', 'woocommerce' ),
		'desc' 		=> __( 'Force secure checkout', 'woocommerce' ),
		'id' 		=> 'woocommerce_force_ssl_checkout',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start',
		'show_if_checked' => 'option',
		'desc_tip'	=>  __('Force SSL (HTTPS) on the checkout pages (an SSL Certificate is required)', 'woocommerce'),
	),

	array(
		'desc' 		=> __( 'Un-force HTTPS when leaving the checkout', 'woocommerce' ),
		'id' 		=> 'woocommerce_unforce_ssl_checkout',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end',
		'show_if_checked' => 'yes',
	),

	array(
		'name' => __( 'Coupons', 'woocommerce' ),
		'desc'          => __( 'Enable coupons', 'woocommerce' ),
		'id'            => 'woocommerce_enable_coupons',
		'std'           => 'yes',
		'type'          => 'checkbox',
		'checkboxgroup' => 'start',
		'show_if_checked' => 'option'
	),

	array(
		'desc' 		=> __( 'Enable coupon form on cart', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_coupon_form_on_cart',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'	=> '',
		'show_if_checked' => 'yes'
	),

	array(
		'desc' 		=> __( 'Enable coupon form on checkout', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_coupon_form_on_checkout',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'	=> 'end',
		'show_if_checked' => 'yes'
	),

	array(
		'name' => __( 'Registration', 'woocommerce' ),
		'desc' 		=> __( 'Allow registration on the checkout page', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_signup_and_login_from_checkout',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Allow registration on the "My Account" page', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_myaccount_registration',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc' 		=> __( 'Register using the email address for the username', 'woocommerce' ),
		'id' 		=> 'woocommerce_registration_email_for_username',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array(
		'name' => __( 'Customer Accounts', 'woocommerce' ),
		'desc' 		=> __( 'Prevent customers from accessing WordPress admin', 'woocommerce' ),
		'id' 		=> 'woocommerce_lock_down_admin',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Clear cart when logging out', 'woocommerce' ),
		'id' 		=> 'woocommerce_clear_cart_on_logout',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc' 		=> __( 'Allow customers to repurchase past orders', 'woocommerce' ),
		'id' 		=> 'woocommerce_allow_customers_to_reorder',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array( 'type' => 'sectionend', 'id' => 'checkout_account_options'),

	array(	'name' => __( 'Styles and Scripts', 'woocommerce' ), 'type' => 'title', 'desc' => __('The following options affect the styling of your store, as well as how certain features behave.', 'woocommerce'), 'id' => 'script_styling_options' ),

	array(
		'name' => __( 'Styling', 'woocommerce' ),
		'desc' 		=> __( 'Enable WooCommerce CSS styles', 'woocommerce' ),
		'id' 		=> 'woocommerce_frontend_css',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),

	array(
		'type' 		=> 'frontend_styles'
	),

	array(
		'name' => __( 'Store Notice', 'woocommerce' ),
		'desc' 		=> __( 'Enable the "Demo Store" notice on your site', 'woocommerce' ),
		'id' 		=> 'woocommerce_demo_store',
		'std' 		=> 'no',
		'type' 		=> 'checkbox'
	),

	array(
		'name' => __( 'Store Notice Text', 'woocommerce' ),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_demo_store_notice',
		'std' 		=> __( 'This is a demo store for testing purposes &mdash; no orders shall be fulfilled.', 'woocommerce' ),
		'type' 		=> 'text',
		'css' 		=> 'min-width:300px;',
	),

	array(
		'name' => __( 'Scripts', 'woocommerce' ),
		'desc' 		=> __( 'Enable AJAX add to cart buttons on product archives', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_ajax_add_to_cart',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Enable WooCommerce lightbox on the product page', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_lightbox',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc' 		=> __( 'Enable enhanced country select boxes', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_chosen',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array( 'type' => 'sectionend', 'id' => 'script_styling_options'),

	array(	'name' => __( 'Digital Downloads', 'woocommerce' ), 'type' => 'title','desc' => __('The following options are specific to downloadable products.', 'woocommerce'), 'id' => 'digital_download_options' ),

	array(
		'name' => __('File download method', 'woocommerce'),
		'desc' 		=> __('Forcing downloads will keep URLs hidden, but some servers may serve large files unreliably. If supported, <code>X-Accel-Redirect</code>/ <code>X-Sendfile</code> can be used to serve downloads instead (server requires <code>mod_xsendfile</code>).', 'woocommerce'),
		'id' 		=> 'woocommerce_file_download_method',
		'type' 		=> 'select',
		'class'		=> 'chosen_select',
		'css' 		=> 'min-width:300px;',
		'std'		=> 'force',
		'desc_tip'	=>  true,
		'options' => array(
			'force'  	=> __( 'Force Downloads', 'woocommerce' ),
			'xsendfile' => __( 'X-Accel-Redirect/X-Sendfile', 'woocommerce' ),
			'redirect'  => __( 'Redirect only', 'woocommerce' ),
		)
	),

	array(
		'name' => __('Access Restrictions', 'woocommerce'),
		'desc' 		=> __('Must be logged in to download files', 'woocommerce'),
		'id' 		=> 'woocommerce_downloads_require_login',
		'type' 		=> 'checkbox',
		'std' 		=> 'no',
		'desc_tip'	=> __('This setting does not apply to guest downloads.', 'woocommerce'),
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __('Grant access to downloadable products after payment', 'woocommerce'),
		'id' 		=> 'woocommerce_downloads_grant_access_after_payment',
		'type' 		=> 'checkbox',
		'std' 		=> 'yes',
		'desc_tip'	=> __('Turn this option off to only grant access when an order is "complete", rather than "processing"', 'woocommerce'),
		'checkboxgroup'		=> 'end'
	),

	array(
		'name' => __('Limit quantity', 'woocommerce'),
		'desc' 		=> __( 'Limit the purchasable quantity of downloadable-virtual items to 1', 'woocommerce' ),
		'id' 		=> 'woocommerce_limit_downloadable_product_qty',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),

	array( 'type' => 'sectionend', 'id' => 'digital_download_options' ),

)); // End general settings

// Get shop page
$shop_page_id = woocommerce_get_page_id('shop');

$base_slug = ($shop_page_id > 0 && get_page( $shop_page_id )) ? get_page_uri( $shop_page_id ) : 'shop';

$woocommerce_prepend_shop_page_to_products_warning = '';

if ( $shop_page_id > 0 && sizeof(get_pages("child_of=$shop_page_id")) > 0 )
	$woocommerce_prepend_shop_page_to_products_warning = ' <mark class="notice">' . __('Note: The shop page has children - child pages will not work if you enable this option.', 'woocommerce') . '</mark>';

$woocommerce_settings['pages'] = apply_filters('woocommerce_page_settings', array(

	array( 'name' => __( 'Page Setup', 'woocommerce' ), 'type' => 'title', 'desc' => '', 'id' => 'page_options' ),

	array(
		'name' => __( 'Shop Base Page', 'woocommerce' ),
		'desc' 		=> sprintf( __( 'This sets the base page of your shop - this is where your product archive will be.', 'woocommerce' ), '<a target="_blank" href="options-permalink.php">', '</a>' ),
		'id' 		=> 'woocommerce_shop_page_id',
		'type' 		=> 'single_select_page',
		'std' 		=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'Base Page Title', 'woocommerce' ),
		'desc' 		=> __( 'This title to show on the shop base page. Leave blank to use the page title.', 'woocommerce' ),
		'id' 		=> 'woocommerce_shop_page_title',
		'type' 		=> 'text',
		'css' 		=> 'min-width:300px;',
		'std' 		=> 'All Products', // Default value for the page title - changed in settings
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'Terms page ID', 'woocommerce' ),
		'desc' 		=> __( 'If you define a "Terms" page the customer will be asked if they accept them when checking out.', 'woocommerce' ),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_terms_page_id',
		'std' 		=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'type' 		=> 'single_select_page',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'Logout link', 'woocommerce' ),
		'desc' 		=> sprintf(__( 'Append a logout link to menus containing "My Account"', 'woocommerce' ), $base_slug),
		'id' 		=> 'woocommerce_menu_logout_link',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
	),

	array( 'type' => 'sectionend', 'id' => 'page_options' ),

	array( 'name' => __( 'Permalinks', 'woocommerce' ), 'type' => 'title', 'desc' => '', 'id' => 'permalink_options' ),

	array(
		'name' => __( 'Taxonomy base page', 'woocommerce' ),
		'desc' 		=> sprintf(__( 'Prepend shop categories/tags with shop base page (<code>%s</code>)', 'woocommerce' ), $base_slug),
		'id' 		=> 'woocommerce_prepend_shop_page_to_urls',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
	),

	array(
		'name' => __( 'Product category slug', 'woocommerce' ),
		'desc' 		=> __( 'Shows in the product category URLs. Leave blank to use the default slug.', 'woocommerce' ),
		'id' 		=> 'woocommerce_product_category_slug',
		'type' 		=> 'text',
		'css' 		=> 'min-width:300px;',
		'std' 		=> '',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'Product tag slug', 'woocommerce' ),
		'desc' 		=> __( 'Shows in the product tag URLs. Leave blank to use the default slug.', 'woocommerce' ),
		'id' 		=> 'woocommerce_product_tag_slug',
		'type' 		=> 'text',
		'css' 		=> 'min-width:300px;',
		'std' 		=> '',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'Product base page', 'woocommerce' ),
		'desc' 		=> sprintf(__( 'Prepend product permalinks with shop base page (<code>%s</code>)', 'woocommerce' ), $base_slug) . $woocommerce_prepend_shop_page_to_products_warning,
		'id' 		=> 'woocommerce_prepend_shop_page_to_products',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'name' => __( 'Product base category', 'woocommerce' ),
		'desc' 		=> __( 'Prepend product permalinks with product category', 'woocommerce' ),
		'id' 		=> 'woocommerce_prepend_category_to_products',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array( 'type' => 'sectionend', 'id' => 'permalink_options' ),

	array( 'name' => __( 'Shop Pages', 'woocommerce' ), 'type' => 'title', 'desc' => __( 'The following pages need selecting so that WooCommerce knows where they are. These pages should have been created upon installation of the plugin, if not you will need to create them.', 'woocommerce' ) ),

	array(
		'name' => __( 'Cart Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_cart]', 'woocommerce' ),
		'id' 		=> 'woocommerce_cart_page_id',
		'type' 		=> 'single_select_page',
		'std' 		=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'Checkout Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_checkout]', 'woocommerce' ),
		'id' 		=> 'woocommerce_checkout_page_id',
		'type' 		=> 'single_select_page',
		'std' 		=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'Pay Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_pay] Parent: "Checkout"', 'woocommerce' ),
		'id' 		=> 'woocommerce_pay_page_id',
		'type' 		=> 'single_select_page',
		'std' 		=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __('Thanks Page', 'woocommerce'),
		'desc' 		=> __( 'Page contents: [woocommerce_thankyou] Parent: "Checkout"', 'woocommerce' ),
		'id' 		=> 'woocommerce_thanks_page_id',
		'type' 		=> 'single_select_page',
		'std' 		=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'My Account Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_my_account]', 'woocommerce' ),
		'id' 		=> 'woocommerce_myaccount_page_id',
		'type' 		=> 'single_select_page',
		'std' 		=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'Edit Address Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_edit_address] Parent: "My Account"', 'woocommerce' ),
		'id' 		=> 'woocommerce_edit_address_page_id',
		'type' 		=> 'single_select_page',
		'std' 		=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'View Order Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_view_order] Parent: "My Account"', 'woocommerce' ),
		'id' 		=> 'woocommerce_view_order_page_id',
		'type' 		=> 'single_select_page',
		'std' 		=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'Change Password Page', 'woocommerce' ),
		'desc' 		=> __( 'Page contents: [woocommerce_change_password] Parent: "My Account"', 'woocommerce' ),
		'id' 		=> 'woocommerce_change_password_page_id',
		'type' 		=> 'single_select_page',
		'std' 		=> '',
		'class'		=> 'chosen_select_nostd',
		'css' 		=> 'min-width:300px;',
		'desc_tip'	=>  true,
	),

	array( 'type' => 'sectionend', 'id' => 'page_options'),

)); // End pages settings


$woocommerce_settings['catalog'] = apply_filters('woocommerce_catalog_settings', array(

	array(	'name' => __( 'Catalog Options', 'woocommerce' ), 'type' => 'title','desc' => '', 'id' => 'catalog_options' ),

	array(
		'name' => __( 'Default product sorting', 'woocommerce' ),
		'desc' 		=> __( 'This controls the default sort order of the catalog.', 'woocommerce' ),
		'id' 		=> 'woocommerce_default_catalog_orderby',
		'css' 		=> 'min-width:150px;',
		'std' 		=> 'title',
		'type' 		=> 'select',
		'options' => apply_filters('woocommerce_default_catalog_orderby_options', array(
			'menu_order'  	=> __( 'Default sorting', 'woocommerce' ),
			'title'  		=> __( 'Sort alphabetically', 'woocommerce' ),
			'date'   		=> __( 'Sort by most recent', 'woocommerce' ),
			'price' 		=> __( 'Sort by price', 'woocommerce' ),
		)),
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'Show subcategories', 'woocommerce' ),
		'desc' 		=> __( 'Show subcategories on category pages', 'woocommerce' ),
		'id' 		=> 'woocommerce_show_subcategories',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Show subcategories on the shop page', 'woocommerce' ),
		'id' 		=> 'woocommerce_shop_show_subcategories',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc' 		=> __( 'When showing subcategories, hide products', 'woocommerce' ),
		'id' 		=> 'woocommerce_hide_products_when_showing_subcategories',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array(
		'name' => __( 'Redirects', 'woocommerce' ),
		'desc' 		=> __( 'Redirect to cart after adding a product to the cart (on single product pages)', 'woocommerce' ),
		'id' 		=> 'woocommerce_cart_redirect_after_add',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Redirect to the product page on a single matching search result', 'woocommerce' ),
		'id' 		=> 'woocommerce_redirect_on_single_search_result',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array( 'type' => 'sectionend', 'id' => 'catalog_options' ),

	array(	'name' => __( 'Product Data', 'woocommerce' ), 'type' => 'title', 'desc' => __('The following options affect the fields available on the edit product page.', 'woocommerce'), 'id' => 'product_data_options' ),

	array(
		'name' => __( 'Product fields', 'woocommerce' ),
		'desc' 		=> __( 'Enable the SKU field for products', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_sku',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Enable the weight field for products', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_weight',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc' 		=> __( 'Enable the dimension fields for products', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_dimensions',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc' 		=> __( 'Show weight and dimension fields in product attributes tab', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_dimension_product_attributes',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array(
		'name' => __( 'Weight Unit', 'woocommerce' ),
		'desc' 		=> __( 'This controls what unit you will define weights in.', 'woocommerce' ),
		'id' 		=> 'woocommerce_weight_unit',
		'css' 		=> 'min-width:150px;',
		'std' 		=> 'kg',
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
		'name' => __( 'Dimensions Unit', 'woocommerce' ),
		'desc' 		=> __( 'This controls what unit you will define lengths in.', 'woocommerce' ),
		'id' 		=> 'woocommerce_dimension_unit',
		'css' 		=> 'min-width:150px;',
		'std' 		=> 'cm',
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
		'name' => __( 'Product Ratings', 'woocommerce' ),
		'desc' 		=> __( 'Enable the star rating field on the review form', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_review_rating',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start',
		'show_if_checked' => 'option',
	),

	array(
		'desc' 		=> __( 'Ratings are required to leave a review', 'woocommerce' ),
		'id' 		=> 'woocommerce_review_rating_required',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> '',
		'show_if_checked' => 'yes',
	),

	array(
		'desc' 		=> __( 'Show "verified owner" label for customer reviews', 'woocommerce' ),
		'id' 		=> 'woocommerce_review_rating_verification_label',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end',
		'show_if_checked' => 'yes',
	),

	array( 'type' => 'sectionend', 'id' => 'product_review_options' ),

	array(	'name' => __( 'Pricing Options', 'woocommerce' ), 'type' => 'title', 'desc' => __('The following options affect how prices are displayed on the frontend.', 'woocommerce'), 'id' => 'pricing_options' ),

	array(
		'name' => __( 'Currency Position', 'woocommerce' ),
		'desc' 		=> __( 'This controls the position of the currency symbol.', 'woocommerce' ),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_currency_pos',
		'css' 		=> 'min-width:150px;',
		'std' 		=> 'left',
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
		'name' => __( 'Thousand separator', 'woocommerce' ),
		'desc' 		=> __( 'This sets the thousand separator of displayed prices.', 'woocommerce' ),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_price_thousand_sep',
		'css' 		=> 'width:30px;',
		'std' 		=> ',',
		'type' 		=> 'text',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'Decimal separator', 'woocommerce' ),
		'desc' 		=> __( 'This sets the decimal separator of displayed prices.', 'woocommerce' ),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_price_decimal_sep',
		'css' 		=> 'width:30px;',
		'std' 		=> '.',
		'type' 		=> 'text',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'Number of decimals', 'woocommerce' ),
		'desc' 		=> __( 'This sets the number of decimal points shown in displayed prices.', 'woocommerce' ),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_price_num_decimals',
		'css' 		=> 'width:30px;',
		'std' 		=> '2',
		'type' 		=> 'text',
		'desc_tip'	=>  true,
	),

	array(
		'name'		=> __( 'Trailing zeros', 'woocommerce' ),
		'desc' 		=> __( 'Remove zeros after the decimal point. e.g. <code>$10.00</code> becomes <code>$10</code>', 'woocommerce' ),
		'id' 		=> 'woocommerce_price_trim_zeros',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),

	array( 'type' => 'sectionend', 'id' => 'pricing_options' ),

	array(	'name' => __( 'Image Options', 'woocommerce' ), 'type' => 'title','desc' => sprintf(__('These settings affect the actual dimensions of images in your catalog - the display on the front-end will still be affected by CSS styles. After changing these settings you may need to <a href="%s">regenerate your thumbnails</a>.', 'woocommerce'), 'http://wordpress.org/extend/plugins/regenerate-thumbnails/'), 'id' => 'image_options' ),

	array(
		'name' => __( 'Catalog Images', 'woocommerce' ),
		'desc' 		=> __('This size is usually used in product listings', 'woocommerce'),
		'id' 		=> 'woocommerce_catalog_image',
		'css' 		=> '',
		'type' 		=> 'image_width',
		'std' 		=> '150',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'Single Product Image', 'woocommerce' ),
		'desc' 		=> __('This is the size used by the main image on the product page.', 'woocommerce'),
		'id' 		=> 'woocommerce_single_image',
		'css' 		=> '',
		'type' 		=> 'image_width',
		'std' 		=> '300',
		'desc_tip'	=>  true,
	),

	array(
		'name' => __( 'Product Thumbnails', 'woocommerce' ),
		'desc' 		=> __('This size is usually used for the gallery of images on the product page.', 'woocommerce'),
		'id' 		=> 'woocommerce_thumbnail_image',
		'css' 		=> '',
		'type' 		=> 'image_width',
		'std' 		=> '90',
		'desc_tip'	=>  true,
	),

	array( 'type' => 'sectionend', 'id' => 'image_options' ),

)); // End catalog settings


$woocommerce_settings['inventory'] = apply_filters('woocommerce_inventory_settings', array(

	array(	'name' => __( 'Inventory Options', 'woocommerce' ), 'type' => 'title','desc' => '', 'id' => 'inventory_options' ),

	array(
		'name' => __( 'Manage stock', 'woocommerce' ),
		'desc' 		=> __( 'Enable stock management', 'woocommerce' ),
		'id' 		=> 'woocommerce_manage_stock',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),

	array(
		'name' => __( 'Notifications', 'woocommerce' ),
		'desc' 		=> __( 'Enable low stock notifications', 'woocommerce' ),
		'id' 		=> 'woocommerce_notify_low_stock',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup' => 'start'
	),

	array(
		'desc' 		=> __( 'Enable out of stock notifications', 'woocommerce' ),
		'id' 		=> 'woocommerce_notify_no_stock',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup' => 'end'
	),

	array(
		'name' => __( 'Low stock threshold', 'woocommerce' ),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'woocommerce_notify_low_stock_amount',
		'css' 		=> 'width:30px;',
		'type' 		=> 'text',
		'std' 		=> '2'
	),

	array(
		'name' => __( 'Out of stock threshold', 'woocommerce' ),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'woocommerce_notify_no_stock_amount',
		'css' 		=> 'width:30px;',
		'type' 		=> 'text',
		'std' 		=> '0'
	),

	array(
		'name' => __( 'Out of stock visibility', 'woocommerce' ),
		'desc' 		=> __('Hide out of stock items from the catalog', 'woocommerce'),
		'id' 		=> 'woocommerce_hide_out_of_stock_items',
		'std' 		=> 'no',
		'type' 		=> 'checkbox'
	),

	array(
		'name' => __( 'Stock display format', 'woocommerce' ),
		'desc' 		=> __( 'This controls how stock is displayed on the frontend.', 'woocommerce' ),
		'id' 		=> 'woocommerce_stock_format',
		'css' 		=> 'min-width:150px;',
		'std' 		=> '',
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

	array( 'name' => __( 'Shipping Options', 'woocommerce' ), 'type' => 'title', 'id' => 'shipping_options' ),

	array(
		'name' 		=> __( 'Shipping calculations', 'woocommerce' ),
		'desc' 		=> __( 'Enable shipping', 'woocommerce' ),
		'id' 		=> 'woocommerce_calc_shipping',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Enable the shipping calculator on the cart page', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_shipping_calc',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc' 		=> __( 'Hide shipping costs until an address is entered', 'woocommerce' ),
		'id' 		=> 'woocommerce_shipping_cost_requires_address',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array(
		'name' => __( 'Shipping method display', 'woocommerce' ),
		'desc' 		=> __( 'This controls how multiple shipping methods are displayed on the frontend.', 'woocommerce' ),
		'id' 		=> 'woocommerce_shipping_method_format',
		'css' 		=> 'min-width:150px;',
		'std' 		=> '',
		'type' 		=> 'select',
		'options' => array(
			''  			=> __( 'Radio buttons', 'woocommerce' ),
			'select'		=> __( 'Select box', 'woocommerce' ),
		),
		'desc_tip'	=>  true,
	),

	array(
		'name' 		=> __( 'Shipping Destination', 'woocommerce' ),
		'desc' 		=> __( 'Only ship to the users billing address', 'woocommerce' ),
		'id' 		=> 'woocommerce_ship_to_billing_address_only',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Ship to billing address by default', 'woocommerce' ),
		'id' 		=> 'woocommerce_ship_to_same_address',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc' 		=> __( 'Collect shipping address even when not required', 'woocommerce' ),
		'id' 		=> 'woocommerce_require_shipping_address',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array(
		'type' 		=> 'shipping_methods',
	),

	array( 'type' => 'sectionend', 'id' => 'shipping_options' ),

)); // End shipping settings


$woocommerce_settings['payment_gateways'] = apply_filters('woocommerce_payment_gateways_settings', array(

	array( 'name' => __( 'Payment Gateways', 'woocommerce' ), 'desc' => __('Installed payment gateways are displayed below. Drag and drop payment gateways to control their display order on the checkout.', 'woocommerce'), 'type' => 'title', 'id' => 'payment_gateways_options' ),

	array(
		'type' 		=> 'payment_gateways',
	),

	array( 'type' => 'sectionend', 'id' => 'payment_gateways_options' ),

)); // End payment_gateway settings


$woocommerce_settings['tax'] = apply_filters('woocommerce_tax_settings', array(

	array(	'name' => __( 'Tax Options', 'woocommerce' ), 'type' => 'title','desc' => '', 'id' => 'tax_options' ),

	array(
		'name' => __( 'Tax calculations', 'woocommerce' ),
		'desc' 		=> __( 'Enable taxes and tax calculations', 'woocommerce' ),
		'id' 		=> 'woocommerce_calc_taxes',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(
		'desc' 		=> __( 'Display taxes on cart page', 'woocommerce' ),
		'id' 		=> 'woocommerce_display_cart_taxes',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(
		'desc'          => __( 'Display taxes even when the amount is zero', 'woocommerce' ),
		'id'            => 'woocommerce_display_cart_taxes_if_zero',
		'std'           => 'no',
		'type'          => 'checkbox',
		'checkboxgroup' => '',
	),

	array(
		'desc' 		=> __( 'Round tax at subtotal level, instead of per line', 'woocommerce' ),
		'id' 		=> 'woocommerce_tax_round_at_subtotal',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),

	array(
		'name' => __( 'Catalog Prices', 'woocommerce' ),
		'desc' 		=> __( 'Catalog prices defined including tax', 'woocommerce' ),
		'id' 		=> 'woocommerce_prices_include_tax',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start',
		'show_if_checked' => 'option',
	),

	array(
		'desc' 		=> __( 'Display cart contents excluding tax', 'woocommerce' ),
		'id' 		=> 'woocommerce_display_cart_prices_excluding_tax',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> '',
		'show_if_checked' => 'yes',
	),

	array(
		'desc' 		=> __( 'Display cart totals excluding tax', 'woocommerce' ),
		'id' 		=> 'woocommerce_display_totals_excluding_tax',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end',
		'show_if_checked' => 'yes',
	),

	array(
		'name' => __( 'Additional Tax classes', 'woocommerce' ),
		'desc' 		=> __( 'List 1 per line. This is in addition to the default <em>Standard Rate</em>.', 'woocommerce' ),
		'tip' 		=> __( 'List product and shipping tax classes here, e.g. Zero Tax, Reduced Rate.', 'woocommerce' ),
		'id' 		=> 'woocommerce_tax_classes',
		'css' 		=> 'width:100%; height: 75px;',
		'type' 		=> 'textarea',
		'std' 		=> sprintf( __( 'Reduced Rate%sZero Rate', 'woocommerce' ), PHP_EOL )
	),

	array(
		'name' => __( 'Tax rates', 'woocommerce' ),
		'desc' 		=> __( 'All fields are required.', 'woocommerce' ),
		'tip' 		=> __( 'To avoid rounding errors, insert tax rates with 4 decimal places.', 'woocommerce' ),
		'id' 		=> 'woocommerce_tax_rates',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'tax_rates',
		'std' 		=> ''
	),

	array( 'type' => 'sectionend', 'id' => 'tax_options' ),

)); // End tax settings

$woocommerce_settings['email'] = apply_filters('woocommerce_email_settings', array(

	array(	'name' => __( 'Email Recipient Options', 'woocommerce' ), 'type' => 'title', '', 'id' => 'email_recipient_options' ),

	array(
		'name' => __( 'New order notifications', 'woocommerce' ),
		'desc' 		=> __( 'The recipient of new order emails. Defaults to the admin email.', 'woocommerce' ),
		'id' 		=> 'woocommerce_new_order_email_recipient',
		'type' 		=> 'text',
		'css' 		=> 'min-width:300px;',
		'std' 		=> esc_attr(get_option('admin_email'))
	),

	array(
		'name' => __( 'Inventory notifications', 'woocommerce' ),
		'desc' 		=> __( 'The recipient of stock emails. Defaults to the admin email.', 'woocommerce' ),
		'id' 		=> 'woocommerce_stock_email_recipient',
		'type' 		=> 'text',
		'css' 		=> 'min-width:300px;',
		'std' 		=> esc_attr(get_option('admin_email'))
	),

	array( 'type' => 'sectionend', 'id' => 'email_recipient_options' ),

	array(	'name' => __( 'Email Sender Options', 'woocommerce' ), 'type' => 'title', 'desc' => __('The following options affect the sender (email address and name) used in WooCommerce emails.', 'woocommerce'), 'id' => 'email_options' ),

	array(
		'name' => __( '"From" name', 'woocommerce' ),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_email_from_name',
		'type' 		=> 'text',
		'css' 		=> 'min-width:300px;',
		'std' 		=> esc_attr(get_bloginfo('name'))
	),

	array(
		'name' => __( '"From" email address', 'woocommerce' ),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_email_from_address',
		'type' 		=> 'text',
		'css' 		=> 'min-width:300px;',
		'std' 		=> get_option('admin_email')
	),

	array( 'type' => 'sectionend', 'id' => 'email_options' ),

	array(	'name' => __( 'Email template', 'woocommerce' ), 'type' => 'title', 'desc' => sprintf(__('This section lets you customise the WooCommerce emails. <a href="%s" target="_blank">Click here to preview your email template</a>. For more advanced control copy <code>woocommerce/templates/emails/</code> to <code>yourtheme/woocommerce/emails/</code>.', 'woocommerce'), wp_nonce_url(admin_url('?preview_woocommerce_mail=true'), 'preview-mail')), 'id' => 'email_template_options' ),

	array(
		'name' => __( 'Header image', 'woocommerce' ),
		'desc' 		=> sprintf(__( 'Enter a URL to an image you want to show in the email\'s header. Upload your image using the <a href="%s">media uploader</a>.', 'woocommerce' ), admin_url('media-new.php')),
		'id' 		=> 'woocommerce_email_header_image',
		'type' 		=> 'text',
		'css' 		=> 'min-width:300px;',
		'std' 		=> ''
	),

	array(
		'name' => __( 'Email footer text', 'woocommerce' ),
		'desc' 		=> __( 'The text to appear in the footer of WooCommerce emails.', 'woocommerce' ),
		'id' 		=> 'woocommerce_email_footer_text',
		'css' 		=> 'width:100%; height: 75px;',
		'type' 		=> 'textarea',
		'std' 		=> get_bloginfo('name') . ' - ' . __('Powered by WooCommerce', 'woocommerce')
	),

	array(
		'name' => __( 'Base colour', 'woocommerce' ),
		'desc' 		=> __( 'The base colour for WooCommerce email templates. Default <code>#557da1</code>.', 'woocommerce' ),
		'id' 		=> 'woocommerce_email_base_color',
		'type' 		=> 'color',
		'css' 		=> 'width:6em;',
		'std' 		=> '#557da1'
	),

	array(
		'name' => __( 'Background colour', 'woocommerce' ),
		'desc' 		=> __( 'The background colour for WooCommerce email templates. Default <code>#eeeeee</code>.', 'woocommerce' ),
		'id' 		=> 'woocommerce_email_background_color',
		'type' 		=> 'color',
		'css' 		=> 'width:6em;',
		'std' 		=> '#eeeeee'
	),

	array(
		'name' => __( 'Email body background colour', 'woocommerce' ),
		'desc' 		=> __( 'The main body background colour. Default <code>#fdfdfd</code>.', 'woocommerce' ),
		'id' 		=> 'woocommerce_email_body_background_color',
		'type' 		=> 'color',
		'css' 		=> 'width:6em;',
		'std' 		=> '#fdfdfd'
	),

	array(
		'name' => __( 'Email body text colour', 'woocommerce' ),
		'desc' 		=> __( 'The main body text colour. Default <code>#505050</code>.', 'woocommerce' ),
		'id' 		=> 'woocommerce_email_text_color',
		'type' 		=> 'color',
		'css' 		=> 'width:6em;',
		'std' 		=> '#505050'
	),

	array( 'type' => 'sectionend', 'id' => 'email_template_options' ),

)); // End email settings