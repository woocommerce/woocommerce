<?php
/**
 * Functions for the settings page in admin.
 * 
 * The settings page contains options for the WooCommerce plugin - this file contains functions to display
 * and save the list of options.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */

/**
 * Define settings for the WooCommerce settings pages
 */
global $woocommerce_settings;

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
		'tip' 		=> '',
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
			'MYR' => __( 'Malaysian Ringgits', 'woocommerce' ),
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
	
	array(  
		'name' => __('Localisation', 'woocommerce'),
		'desc' 		=> __('Use informal localisation file if it exists', 'woocommerce'),
		'id' 		=> 'woocommerce_informal_localisation_type',
		'type' 		=> 'checkbox',
		'std' 		=> 'no',
	),
	
	array( 'type' => 'sectionend', 'id' => 'general_options'),
	
	array(	'name' => __( 'Checkout and Accounts', 'woocommerce' ), 'type' => 'title','desc' => __('The following options control the behaviour of the checkout process and customer accounts.', 'woocommerce'), 'id' => 'checkout_account_options' ),

	array(  
		'name' => __( 'Security', 'woocommerce' ),
		'desc' 		=> __( 'Force <abbr title="Secure Sockets Layer, a computing protocol that ensures the security of data sent via the Internet by using encryption">SSL</abbr>/HTTPS (an SSL Certificate is required)', 'woocommerce' ),
		'id' 		=> 'woocommerce_force_ssl_checkout',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start',
		'show_if_checked' => 'option',
	),
	
	array(  
		'desc' 		=> __( 'Un-force <abbr title="Secure Sockets Layer, a computing protocol that ensures the security of data sent via the Internet by using encryption">SSL</abbr>/HTTPS when leaving the checkout', 'woocommerce' ),
		'id' 		=> 'woocommerce_unforce_ssl_checkout',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end',
		'show_if_checked' => 'yes',
	),

	array(
		'name' => __( 'Coupons', 'woocommerce' ),
		'desc'          => __( 'Enable the use of coupons', 'woocommerce' ),
		'id'            => 'woocommerce_enable_coupons',
		'std'           => 'yes',
		'type'          => 'checkbox',
		'checkboxgroup' => 'start',
		'show_if_checked' => 'option'
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
		'name' => __( 'Checkout', 'woocommerce' ),
		'desc' 		=> __( 'Enable Guest Checkout (no account required)', 'woocommerce' ),
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
		'name' => __( 'Customer Accounts', 'woocommerce' ),
		'desc' 		=> __( 'Allow unregistered users to register from the Checkout', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_signup_and_login_from_checkout',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),

	array(  
		'desc' 		=> __( 'Allow unregistered users to register from "My Account"', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_myaccount_registration',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),
	
	array(  
		'desc' 		=> __( 'Clear cart when logging out', 'woocommerce' ),
		'id' 		=> 'woocommerce_clear_cart_on_logout',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),
	
	array(  
		'desc' 		=> __( 'Prevent customers from accessing WordPress admin', 'woocommerce' ),
		'id' 		=> 'woocommerce_lock_down_admin',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),
	
	array(  
		'desc' 		=> __( 'Allow customers to reorder items from past orders', 'woocommerce' ),
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
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),
	
	array(  
		'desc' 		=> __( 'Enable the "Demo Store" notice on your site', 'woocommerce' ),
		'id' 		=> 'woocommerce_demo_store',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'	=> 'end'
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
		'desc' 		=> __( 'Enable "chosen" (enhanced select input) for country selection inputs', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_chosen',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),
	
	array(  
		'desc' 		=> __( 'Enable jQuery UI (used by the price slider widget)', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_jquery_ui',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),
	
	array(  
	    'desc'     => __( 'Output WooCommerce JavaScript in the footer', 'woocommerce' ),
	    'id'     => 'woocommerce_scripts_position',
	    'std'     => 'yes',
	    'type'     => 'checkbox',
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
		'name' => __('Require login to download', 'woocommerce'),
		'desc' 		=> __('Do not allow downloads if a user is not logged in. This setting does not apply to guest downloads.', 'woocommerce'),
		'id' 		=> 'woocommerce_downloads_require_login',
		'type' 		=> 'checkbox',
		'std' 		=> 'no',
	),
	
	array(  
		'name' => __('Limit quantity', 'woocommerce'),
		'desc' 		=> __( 'Limit the purchasable quantity of downloadable-virtual items to 1.', 'woocommerce' ),
		'id' 		=> 'woocommerce_limit_downloadable_product_qty',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),
	
	array(  
		'name' => __('Mixed cart handling', 'woocommerce'),
		'desc' 		=> __('Grant access to downloadable products after payment. Turn this option off to only grant access when an order is "complete".', 'woocommerce'),
		'id' 		=> 'woocommerce_downloads_grant_access_after_payment',
		'type' 		=> 'checkbox',
		'std' 		=> 'yes',
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
			'title'  => __( 'Sort by title', 'woocommerce' ),
			'date'   => __( 'Sort by date', 'woocommerce' ),
			'price' => __( 'Sort by price', 'woocommerce' ),
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
		),
		'desc_tip'	=>  true,
	),
	
	array( 'type' => 'sectionend', 'id' => 'product_data_options' ),

	array(	'name' => __( 'Product Reviews', 'woocommerce' ), 'type' => 'title', 'desc' => __('The following options affect product reviews (comments).', 'woocommerce'), 'id' => 'product_review_options' ),
	
	array(  
		'name' => __( 'Ratings', 'woocommerce' ),
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
	
	array( 'type' => 'sectionend', 'id' => 'inventory_options'),

)); // End inventory settings


$woocommerce_settings['shipping'] = apply_filters('woocommerce_shipping_settings', array(

	array( 'name' => __( 'Shipping Options', 'woocommerce' ), 'type' => 'title', 'desc' => __('Shipping can be enabled and disabled from this section.', 'woocommerce'), 'id' => 'shipping_options' ),
	
	array(  
		'name' 		=> __( 'Shipping calculations', 'woocommerce' ),
		'desc' 		=> __( 'Enable shipping', 'woocommerce' ),
		'id' 		=> 'woocommerce_calc_shipping',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),
	
	array(  
		'name' 		=> __( 'Shipping calculations', 'woocommerce' ),
		'desc' 		=> __( 'Enable the shipping calculator on the cart page', 'woocommerce' ),
		'id' 		=> 'woocommerce_enable_shipping_calc',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),
	
	array(  
		'name' 		=> __( 'Shipping destination', 'woocommerce' ),
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
		'checkboxgroup'		=> 'end'
	),
	
	array( 'type' => 'sectionend', 'id' => 'shipping_options' ),

)); // End shipping settings


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

$woocommerce_settings['integration'] = apply_filters('woocommerce_intregation_settings', array(
	
	array( 'name' => __( 'ShareThis', 'woocommerce' ), 'type' => 'title', 'desc' => __('ShareThis offers a sharing widget which will allow customers to share links to products with their friends.', 'woocommerce'), 'id' => 'share_this' ),

	array(  
		'name' => __( 'ShareThis Publisher ID', 'woocommerce' ),
		'desc' 		=> sprintf( __( 'Enter your %1$sShareThis publisher ID%2$s to show social sharing buttons on product pages.', 'woocommerce' ), '<a href="http://sharethis.com/account/">', '</a>' ),
		'id' 		=> 'woocommerce_sharethis',
		'type' 		=> 'text',
		'std' 		=> '',
        'css' 		=> 'min-width:300px;',
	),
	
	array( 'type' => 'sectionend', 'id' => 'share_this'),

	array( 'name' => __( 'ShareDaddy', 'woocommerce' ), 'type' => 'title', 'desc' => __('ShareDaddy is a sharing plugin bundled with JetPack.', 'woocommerce'), 'id' => 'share_this' ),

	array(  
		'name' => __( 'Output ShareDaddy button?', 'woocommerce' ),
		'desc' 		=> __( 'Enable this option to show the ShareDaddy button (if installed) on the product page.', 'woocommerce' ),
		'id' 		=> 'woocommerce_sharedaddy',
		'type' 		=> 'checkbox',
		'std' 		=> 'no',
	),
	
	array( 'type' => 'sectionend', 'id' => 'share_this'),
	
	array( 'name' => __( 'Google Analytics', 'woocommerce' ), 'type' => 'title', 'desc' => __('Google Analytics is a free service offered by Google that generates detailed statistics about the visitors to a website.', 'woocommerce'), 'id' => 'google_analytics' ),
	
	array(  
		'name' => __('Google Analytics ID', 'woocommerce'),
		'desc' 		=> __('Log into your google analytics account to find your ID. e.g. <code>UA-XXXXX-X</code>', 'woocommerce'),
		'id' 		=> 'woocommerce_ga_id',
		'type' 		=> 'text',
        'css' 		=> 'min-width:300px;',
	),
	
	array(  
		'name' => __('Tracking code', 'woocommerce'),
		'desc' 		=> __('Add tracking code to your site\'s footer. You don\'t need to enable this if using a 3rd party analytics plugin.', 'woocommerce'),
		'id' 		=> 'woocommerce_ga_standard_tracking_enabled',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),
	
	array(  
		'name' => __('Tracking code', 'woocommerce'),
		'desc' 		=> __('Add eCommerce tracking code to the thankyou page', 'woocommerce'),
		'id' 		=> 'woocommerce_ga_ecommerce_tracking_enabled',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),
					
	array( 'type' => 'sectionend', 'id' => 'google_analytics'),

)); // End integration settings

/**
 * Settings page
 * 
 * Handles the display of the main woocommerce settings page in admin.
 */
if (!function_exists('woocommerce_settings')) {
function woocommerce_settings() {
    global $woocommerce, $woocommerce_settings;
    
    $current_tab = (isset($_GET['tab'])) ? $_GET['tab'] : 'general';
    
    // Save settings
    if( isset( $_POST ) && $_POST ) {
    	if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) ) 
    		die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) ); 
    	
    	switch ( $current_tab ) {
			case "general" :
			case "pages" :
			case "catalog" :
			case "inventory" :
			case "shipping" :
			case "tax" :
			case "email" :
			case "integration" :
				woocommerce_update_options( $woocommerce_settings[$current_tab] );
			break;
		}
		
		do_action( 'woocommerce_update_options' );
		do_action( 'woocommerce_update_options_' . $current_tab );
		
		if ($current_tab=='shipping') do_action( 'woocommerce_update_options_shipping_methods' ); // Shipping Methods
		
		flush_rewrite_rules( false );
		
		unset($_SESSION['orderby']);
		
		wp_redirect( add_query_arg( 'subtab', esc_attr(str_replace('#', '', $_POST['subtab'])), add_query_arg( 'saved', 'true', admin_url( 'admin.php?page=woocommerce&tab=' . $current_tab ) )) );
		
		exit;
		
	}
    
    // Settings saved message
    if (isset($_GET['saved']) && $_GET['saved']) {
    	echo '<div id="message" class="updated fade"><p><strong>' . __( 'Your settings have been saved.', 'woocommerce' ) . '</strong></p></div>';
        
        flush_rewrite_rules( false );
        
        do_action('woocommerce_settings_saved');
    }
    
    // Hide WC Link
    if (isset($_GET['hide-wc-extensions-message'])) 
    	update_option('hide-wc-extensions-message', 1);
    
    // Install/page installer
    $install_complete = false;
    
    // Add pages button
    if (isset($_GET['install_woocommerce_pages']) && $_GET['install_woocommerce_pages']) {
		
		require_once( 'woocommerce-admin-install.php' );
    	woocommerce_create_pages();
    	update_option('skip_install_woocommerce_pages', 1);
    	$install_complete = true;
	
	// Skip button
    } elseif (isset($_GET['skip_install_woocommerce_pages']) && $_GET['skip_install_woocommerce_pages']) {
    	
    	update_option('skip_install_woocommerce_pages', 1);
    	$install_complete = true;
    	
    }
	
	if ($install_complete) {
		?>
    	<div id="message" class="updated woocommerce-message wc-connect">
			<div class="squeezer">
				<h4><?php _e( '<strong>Congratulations!</strong> &#8211; WooCommerce has been installed and setup. Enjoy :)', 'woocommerce' ); ?></h4>
				<p><a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/woocommerce/" data-text="A open-source (free) #ecommerce plugin for #WordPress that helps you sell anything. Beautifully." data-via="WooThemes" data-size="large" data-hashtags="WooCommerce">Tweet</a>
	<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></p>
			</div>
		</div>
		<?php
    	
		// Flush rules after install
		flush_rewrite_rules( false );
		
		// Set installed option
		update_option('woocommerce_installed', 0);
	}
    ?>
	<div class="wrap woocommerce">
		<form method="post" id="mainform" action="">
			<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br></div><h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
				<?php
					$tabs = array(
						'general' => __( 'General', 'woocommerce' ),
						'catalog' => __( 'Catalog', 'woocommerce' ),
						'pages' => __( 'Pages', 'woocommerce' ),
						'inventory' => __( 'Inventory', 'woocommerce' ),
						'tax' => __( 'Tax', 'woocommerce'),
						'shipping' => __( 'Shipping', 'woocommerce' ),
						'payment_gateways' => __( 'Payment Gateways', 'woocommerce' ),
						'email' => __( 'Emails', 'woocommerce' ),
						'integration' => __( 'Integration', 'woocommerce' )
					);
					
					$tabs = apply_filters('woocommerce_settings_tabs_array', $tabs);
					
					foreach ($tabs as $name => $label) :
						echo '<a href="' . admin_url( 'admin.php?page=woocommerce&tab=' . $name ) . '" class="nav-tab ';
						if( $current_tab==$name ) echo 'nav-tab-active';
						echo '">' . $label . '</a>';
					endforeach;
					
					do_action( 'woocommerce_settings_tabs' ); 
				?>
			</h2>
			<?php wp_nonce_field( 'woocommerce-settings', '_wpnonce', true, true ); ?>
			
			<?php if (!get_option('hide-wc-extensions-message')) : ?>
			<div id="woocommerce_extensions"><a href="<?php echo add_query_arg('hide-wc-extensions-message', 'true') ?>" class="hide">&times;</a><?php echo sprintf(__('More functionality and gateway options available via <a href="%s" target="_blank">WC official extensions</a>.', 'woocommerce'), 'http://www.woothemes.com/extensions/woocommerce-extensions/'); ?></div>
			<?php endif; ?>

			<?php
				switch ($current_tab) :
					case "general" :
					case "pages" :
					case "catalog" :
					case "inventory" :
					case "tax" :
					case "email" :
					case "integration" :
						woocommerce_admin_fields( $woocommerce_settings[$current_tab] );
					break;
					case "shipping" :
						
						$links = array( '<a href="#shipping-options">'.__('Shipping Options', 'woocommerce').'</a>' );
						
						foreach ( $woocommerce->shipping->shipping_methods as $method ) :
							$title = ( isset( $method->method_title ) && $method->method_title) ? ucwords($method->method_title) : ucwords($method->id);
							$links[] = '<a href="#shipping-'.$method->id.'">'.$title.'</a>';
						endforeach;
						
						echo '<div class="subsubsub_section"><ul class="subsubsub"><li>' . implode(' | </li><li>', $links) . '</li></ul><br class="clear" />';
						
						// Gateway ordering
						echo '<div class="section" id="shipping-options">';
						
						woocommerce_admin_fields( $woocommerce_settings[$current_tab] );
						
						?>
						<h3><?php _e('Shipping Methods', 'woocommerce'); ?></h3>
						<p><?php _e('Your activated shipping methods are listed below. Drag and drop rows to re-order them for display on the frontend.', 'woocommerce'); ?></p>
						<table class="wc_shipping widefat" cellspacing="0">
							<thead>
								<tr>
									<th><?php _e('Default', 'woocommerce'); ?></th>
									<th><?php _e('Shipping Method', 'woocommerce'); ?></th>
									<th><?php _e('Status', 'woocommerce'); ?></th>
								</tr>
							</thead>
							<tbody>
						    	<?php
						    	foreach ( $woocommerce->shipping->shipping_methods as $method ) :
						    	
						    	$default_shipping_method = get_option('woocommerce_default_shipping_method');
						    	
						    	echo '<tr>
						    		<td width="1%" class="radio">
						    			<input type="radio" name="default_shipping_method" value="'.$method->id.'" '.checked($default_shipping_method, $method->id, false).' />
						    			<input type="hidden" name="method_order[]" value="'.$method->id.'" />
						    			<td>
						    				<p><strong>'.$method->title.'</strong><br/>
						    				<small>'.__('Method ID', 'woocommerce').': '.$method->id.'</small></p>
						    			</td>
						    			<td>';
						    		
						    		if ($method->enabled == 'yes') 
						    			echo '<img src="'.$woocommerce->plugin_url().'/assets/images/success.png" alt="yes" />';
									else 
										echo '<img src="'.$woocommerce->plugin_url().'/assets/images/success-off.png" alt="no" />';	
						    			
						    		echo '</td>
						    		</tr>';
						    		
						    	endforeach; 
						    	?>
							</tbody>
						</table>
						<?php
						
						echo '</div>';
						
						// Specific method options
		            	foreach ($woocommerce->shipping->shipping_methods as $method) :
		            		echo '<div class="section" id="shipping-'.$method->id.'">';
		            		$method->admin_options();
		            		echo '</div>';
		            	endforeach; 
		            	
		            	echo '</div>';
            	
					break;
					case "payment_gateways" : 	
					
						$links = array( '<a href="#gateway-order">'.__('Payment Gateways', 'woocommerce').'</a>' );
            			
		            	foreach ($woocommerce->payment_gateways->payment_gateways() as $gateway) :
		            		$title = ( isset( $gateway->method_title ) && $gateway->method_title) ? ucwords($gateway->method_title) : ucwords($gateway->id);
		            		$links[] = '<a href="#gateway-'.$gateway->id.'">'.$title.'</a>';
						endforeach;
						
						echo '<div class="subsubsub_section"><ul class="subsubsub"><li>' . implode(' | </li><li>', $links) . '</li></ul><br class="clear" />';
		            	
		            	// Gateway ordering
		            	echo '<div class="section" id="gateway-order">';
		            	
		            	?>
		            	<h3><?php _e('Payment Gateways', 'woocommerce'); ?></h3>
		            	<p><?php _e('Your activated payment gateways are listed below. Drag and drop rows to re-order them for display on the checkout.', 'woocommerce'); ?></p>
		            	<table class="wc_gateways widefat" cellspacing="0">
		            		<thead>
		            			<tr>
		            				<th width="1%"><?php _e('Default', 'woocommerce'); ?></th>
		            				<th><?php _e('Gateway', 'woocommerce'); ?></th>
		            				<th><?php _e('Status', 'woocommerce'); ?></th>
		            			</tr>
		            		</thead>
		            		<tbody>
				            	<?php
				            	foreach ( $woocommerce->payment_gateways->payment_gateways() as $gateway ) :
				            		
				            		$default_gateway = get_option('woocommerce_default_gateway');
				            		
				            		echo '<tr>
				            			<td width="1%" class="radio">
				            				<input type="radio" name="default_gateway" value="'.$gateway->id.'" '.checked($default_gateway, $gateway->id, false).' />
				            				<input type="hidden" name="gateway_order[]" value="'.$gateway->id.'" />
				            			</td>
				            			<td>
				            				<p><strong>'.$gateway->title.'</strong><br/>
				            				<small>'.__('Gateway ID', 'woocommerce').': '.$gateway->id.'</small></p>
				            			</td>
				            			<td>';
				            		
				            		if ($gateway->enabled == 'yes') 
				            			echo '<img src="'.$woocommerce->plugin_url().'/assets/images/success.png" alt="yes" />';
									else 
										echo '<img src="'.$woocommerce->plugin_url().'/assets/images/success-off.png" alt="no" />';	
				            			
				            		echo '</td>
				            		</tr>';
				            		
				            	endforeach; 
				            	?>
		            		</tbody>
		            	</table>
		            	<?php
		            	
		            	echo '</div>';
		            	
		            	// Specific gateway options
		            	foreach ( $woocommerce->payment_gateways->payment_gateways() as $gateway ) :
		            		echo '<div class="section" id="gateway-'.$gateway->id.'">';
		            		$gateway->admin_options();
		            		echo '</div>';
		            	endforeach; 
		            	
		            	echo '</div>';
            	
					break;
					default :
						do_action( 'woocommerce_settings_tabs_' . $current_tab );
					break;
				endswitch;
			?>
	        <p class="submit">
	        	<input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'woocommerce' ); ?>" />
	        	<input type="hidden" name="subtab" id="last_tab" />
	        </p>
		</form>
		
		<script type="text/javascript">
			jQuery(window).load(function(){
			
				// Subsubsub tabs
				jQuery('ul.subsubsub li a:eq(0)').addClass('current');
				jQuery('.subsubsub_section .section:gt(0)').hide();
				
				jQuery('ul.subsubsub li a').click(function(){
					jQuery('a', jQuery(this).closest('ul.subsubsub')).removeClass('current');
					jQuery(this).addClass('current');
					jQuery('.section', jQuery(this).closest('.subsubsub_section')).hide();
					jQuery( jQuery(this).attr('href') ).show();
					jQuery('#last_tab').val( jQuery(this).attr('href') );
					return false;
				});
				
				<?php if (isset($_GET['subtab']) && $_GET['subtab']) echo 'jQuery("ul.subsubsub li a[href=#'.$_GET['subtab'].']").click();'; ?>
				
				// Countries
				jQuery('select#woocommerce_allowed_countries').change(function(){
					if (jQuery(this).val()=="specific") {
						jQuery(this).parent().parent().next('tr').show();
					} else {
						jQuery(this).parent().parent().next('tr').hide();
					}
				}).change();
				
				// Color picker
				jQuery('.colorpick').each(function(){
					jQuery('.colorpickdiv', jQuery(this).parent()).farbtastic(this);
					jQuery(this).click(function() {
						if ( jQuery(this).val() == "" ) jQuery(this).val('#');
						jQuery('.colorpickdiv', jQuery(this).parent() ).show();
					});	
				});
				jQuery(document).mousedown(function(){
					jQuery('.colorpickdiv').hide();
				});
				
				// Edit prompt
				jQuery(function(){
					var changed = false;
					
					jQuery('input, textarea, select, checkbox').change(function(){
						changed = true;
					});
					
					jQuery('.woo-nav-tab-wrapper a').click(function(){
						if (changed) {
							window.onbeforeunload = function() {
							    return '<?php echo __( 'The changes you made will be lost if you navigate away from this page.', 'woocommerce' ); ?>';
							}
						} else {
							window.onbeforeunload = '';
						}
					});
					
					jQuery('.submit input').click(function(){
						window.onbeforeunload = '';
					});
				});
				
				// Sorting
				jQuery('table.wc_gateways tbody, table.wc_shipping tbody').sortable({
					items:'tr',
					cursor:'move',
					axis:'y',
					handle: 'td',
					scrollSensitivity:40,
					helper:function(e,ui){
						ui.children().each(function(){
							jQuery(this).width(jQuery(this).width());
						});
						ui.css('left', '0');
						return ui;
					},
					start:function(event,ui){
						ui.item.css('background-color','#f6f6f6');
					},
					stop:function(event,ui){
						ui.item.removeAttr('style');
					}
				});
				
				// Chosen selects
				jQuery("select.chosen_select").chosen();
				
				jQuery("select.chosen_select_nostd").chosen({
					allow_single_deselect: 'true'
				});
				
			});
		</script>
	</div>
	<?php
}
}