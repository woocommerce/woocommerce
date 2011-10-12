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

	array( 'name' => __( 'General Options', 'woothemes' ), 'type' => 'title', 'desc' => '', 'id' => 'general_options' ),

	array(  
		'name' => __( 'Base Country/Region', 'woothemes' ),
		'desc' 		=> __( 'This is the base country for your business. Tax rates will be based on this country.', 'woothemes' ),
		'id' 		=> 'woocommerce_default_country',
		'css' 		=> 'min-width:175px;',
		'std' 		=> 'GB',
		'type' 		=> 'single_select_country'
	),
	
	array(  
		'name' => __( 'Allowed Countries', 'woothemes' ),
		'desc' 		=> __( 'These are countries that you are willing to ship to.', 'woothemes' ),
		'id' 		=> 'woocommerce_allowed_countries',
		'css' 		=> 'min-width:175px;',
		'std' 		=> 'all',
		'type' 		=> 'select',
		'options' => array(  
			'all'  => __( 'All Countries', 'woothemes' ),
			'specific' => __( 'Specific Countries', 'woothemes' )			
		)
	),
	
	array(  
		'name' => __( 'Specific Countries', 'woothemes' ),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_specific_allowed_countries',
		'css' 		=> '',
		'std' 		=> '',
		'type' 		=> 'multi_select_countries'
	),
	
	array(  
		'name' => __( 'Guest checkout', 'woothemes' ),
		'desc' 		=> __( 'Allow guest users to checkout without an account', 'woothemes' ),
		'id' 		=> 'woocommerce_enable_guest_checkout',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'	
	),
	
	array(  
		'name' => __( 'Force SSL', 'woothemes' ),
		'desc' 		=> __( 'Force SSL on the checkout for added security (SSL Certificate required).', 'woothemes' ),
		'id' 		=> 'woocommerce_force_ssl_checkout',
		'std' 		=> 'no',
		'type' 		=> 'checkbox'
	),
	
	array(  
		'name' => __( 'WooCommerce CSS', 'woothemes' ),
		'desc' 		=> __( 'Enable WooCommerce frontend CSS styles', 'woothemes' ),
		'id' 		=> 'woocommerce_frontend_css',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),
	
	array(  
		'name' => __( 'Lightbox', 'woothemes' ),
		'desc' 		=> __( 'Enable WooCommerce lightbox?', 'woothemes' ),
		'id' 		=> 'woocommerce_enable_lightbox',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),
	
	array(  
		'name' => __( 'Demo store', 'woothemes' ),
		'desc' 		=> __( 'Enable the "Demo Store" notice on your site', 'woothemes' ),
		'id' 		=> 'woocommerce_demo_store',
		'std' 		=> 'no',
		'type' 		=> 'checkbox'
	),
		
	array(  
		'name' => __( 'ShareThis Publisher ID', 'woothemes' ),
		'desc' 		=> sprintf( __( 'Enter your %1$sShareThis publisher ID%2$s to show ShareThis on product pages.', 'woothemes' ), '<a href="http://sharethis.com/account/">', '</a>' ),
		'id' 		=> 'woocommerce_sharethis',
		'type' 		=> 'text',
		'std' 		=> '',
        'css'       => ''
	),
	
	array(  
		'name' => __('Google Analytics ID', 'woothemes'),
		'desc' 		=> __('Log into your google analytics account to find your ID. e.g. <code>UA-XXXXX-X</code>', 'woothemes'),
		'id' 		=> 'woocommerce_ga_id',
		'type' 		=> 'text',
        'css'       => ''
	),
	
	array(  
		'name' => __('Google Analytics tracking', 'woothemes'),
		'desc' 		=> __('Adds standard tracking code to the footer. You don\'t need to enable this if using a 3rd party google analytics plugin.', 'woothemes'),
		'id' 		=> 'woocommerce_ga_standard_tracking_enabled',
		'type' 		=> 'checkbox',
	),
	
	array(  
		'name' => __('Google Analytics eCommerce tracking', 'woothemes'),
		'desc' 		=> __('Adds eCommerce tracking code to the thankyou page.', 'woothemes'),
		'id' 		=> 'woocommerce_ga_ecommerce_tracking_enabled',
		'type' 		=> 'checkbox',
	),
					
	array( 'type' => 'sectionend', 'id' => 'general_options'),

)); // End general settings

$shop_page_id = get_option('woocommerce_shop_page_id');
$base_slug = ($shop_page_id > 0 && get_page( $shop_page_id )) ? get_page_uri( $shop_page_id ) : 'shop';	
	
$woocommerce_settings['pages'] = apply_filters('woocommerce_page_settings', array(

	array( 'name' => __( 'Page Setup', 'woothemes' ), 'type' => 'title', 'desc' => '', 'id' => 'page_options' ),
	
	array(  
		'name' => __( 'Shop Base Page', 'woothemes' ),
		'desc' 		=> sprintf( __( 'This sets the base page of your shop. Leave blank to use page title.', 'woothemes' ), '<a target="_blank" href="options-permalink.php">', '</a>' ),
		'id' 		=> 'woocommerce_shop_page_id',
		'css' 		=> 'min-width:175px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __( 'Base Page Title', 'woothemes' ),
		'desc' 		=> __( 'This title to show on the shop base page.', 'woothemes' ),
		'id' 		=> 'woocommerce_shop_page_title',
		'type' 		=> 'text',
		'std' 		=> __('All Products', 'woothemes')
	),

	array(  
		'name' => __( 'Terms page ID', 'woothemes' ),
		'desc' 		=> __( 'If you define a "Terms" page the customer will be asked if they accept them when checking out.', 'woothemes' ),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_terms_page_id',
		'css' 		=> 'min-width:50px;',
		'std' 		=> '',
		'type' 		=> 'single_select_page',
		'args'		=> 'show_option_none=' . __('None', 'woothemes'),
	),
	
	array( 'type' => 'sectionend', 'id' => 'page_options' ),
	
	array( 'name' => __( 'Permalinks', 'woothemes' ), 'type' => 'title', 'desc' => '', 'id' => 'permalink_options' ),
	
	array(  
		'name' => __( 'Taxonomy base page', 'woothemes' ),
		'desc' 		=> sprintf(__( 'Prepend shop categories/tags with shop base page (<code>%s</code>)', 'woothemes' ), $base_slug),
		'id' 		=> 'woocommerce_prepend_shop_page_to_urls',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
	),
	
	array(  
		'name' => __( 'Product base page', 'woothemes' ),
		'desc' 		=> sprintf(__( 'Prepend product permalinks with shop base page (<code>%s</code>)', 'woothemes' ), $base_slug),
		'id' 		=> 'woocommerce_prepend_shop_page_to_products',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),
	
	array(  
		'name' => __( 'Product base category', 'woothemes' ),
		'desc' 		=> __( 'Prepend product permalinks with product category', 'woothemes' ),
		'id' 		=> 'woocommerce_prepend_category_to_products',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),
	
	array( 'type' => 'sectionend', 'id' => 'permalink_options' ),
	
	array( 'name' => __( 'Shop Pages', 'woothemes' ), 'type' => 'title', 'desc' => __( 'The following pages need selecting so that WooCommerce knows which are which. These pages should have been created upon installation of the plugin.', 'woothemes' ) ),
	
	array(  
		'name' => __( 'Cart Page', 'woothemes' ),
		'desc' 		=> __( 'Page contents: [woocommerce_cart]', 'woothemes' ),
		'id' 		=> 'woocommerce_cart_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __( 'Checkout Page', 'woothemes' ),
		'desc' 		=> __( 'Page contents: [woocommerce_checkout]', 'woothemes' ),
		'id' 		=> 'woocommerce_checkout_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __( 'Pay Page', 'woothemes' ),
		'desc' 		=> __( 'Page contents: [woocommerce_pay] Parent: "Checkout"', 'woothemes' ),
		'id' 		=> 'woocommerce_pay_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Thanks Page', 'woothemes'),
		'desc' 		=> __( 'Page contents: [woocommerce_thankyou] Parent: "Checkout"', 'woothemes' ),
		'id' 		=> 'woocommerce_thanks_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __( 'My Account Page', 'woothemes' ),
		'desc' 		=> __( 'Page contents: [woocommerce_my_account]', 'woothemes' ),
		'id' 		=> 'woocommerce_myaccount_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __( 'Edit Address Page', 'woothemes' ),
		'desc' 		=> __( 'Page contents: [woocommerce_edit_address] Parent: "My Account"', 'woothemes' ),
		'id' 		=> 'woocommerce_edit_address_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __( 'View Order Page', 'woothemes' ),
		'desc' 		=> __( 'Page contents: [woocommerce_view_order] Parent: "My Account"', 'woothemes' ),
		'id' 		=> 'woocommerce_view_order_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __( 'Change Password Page', 'woothemes' ),
		'desc' 		=> __( 'Page contents: [woocommerce_change_password] Parent: "My Account"', 'woothemes' ),
		'id' 		=> 'woocommerce_change_password_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),	
	
	array( 'type' => 'sectionend', 'id' => 'page_options'),

)); // End pages settings


$woocommerce_settings['catalog'] = apply_filters('woocommerce_catalog_settings', array(

	array(	'name' => __( 'Catalog Options', 'woothemes' ), 'type' => 'title','desc' => '', 'id' => 'catalog_options' ),

	array(  
		'name' => __( 'Sub-categories', 'woothemes' ),
		'desc' 		=> __( 'Show sub-categories on category pages', 'woothemes' ),
		'id' 		=> 'woocommerce_show_subcategories',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),
	
	array(  
		'desc' 		=> __( 'Show sub-categories on the shop page', 'woothemes' ),
		'id' 		=> 'woocommerce_shop_show_subcategories',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),
	
	array(  
		'name' => __( 'Product fields', 'woothemes' ),
		'desc' 		=> __( 'Enable the SKU field for products', 'woothemes' ),
		'id' 		=> 'woocommerce_enable_sku',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),
	
	array(  
		'desc' 		=> __( 'Enable the weight field for products', 'woothemes' ),
		'id' 		=> 'woocommerce_enable_weight',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> ''
	),

	array(  
		'desc' 		=> __( 'Enable the dimension fields for products', 'woothemes' ),
		'id' 		=> 'woocommerce_enable_dimensions',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),
	
	array(  
		'name' => __( 'Weight Unit', 'woothemes' ),
		'desc' 		=> __( 'This controls what unit you will define weights in.', 'woothemes' ),
		'id' 		=> 'woocommerce_weight_unit',
		'css' 		=> 'min-width:175px;',
		'std' 		=> 'GBP',
		'type' 		=> 'select',
		'options' => array( 
			'kg' => __( 'kg', 'woothemes' ),
			'lbs' => __( 'lbs', 'woothemes' )
		)
	),

	array(  
		'name' => __( 'Dimensions Unit', 'woothemes' ),
		'desc' 		=> __( 'This controls what unit you will define lengths in.', 'woothemes' ),
		'id' 		=> 'woocommerce_dimension_unit',
		'css' 		=> 'min-width:175px;',
		'std' 		=> 'GBP',
		'type' 		=> 'select',
		'options' => array( 
			'cm' => __( 'cm', 'woothemes' ),
			'in' => __( 'in', 'woothemes' )
		)
	),
	
	array(  
		'name' => __( 'Cart redirect', 'woothemes' ),
		'desc' 		=> __( 'Redirect to cart after adding a product to the cart (on single product pages)', 'woothemes' ),
		'id' 		=> 'woocommerce_cart_redirect_after_add',
		'std' 		=> 'no',
		'type' 		=> 'checkbox'
	),
	
	array( 'type' => 'sectionend', 'id' => 'catalog_options' ),
	
	array(	'name' => __( 'Pricing Options', 'woothemes' ), 'type' => 'title','desc' => '', 'id' => 'pricing_options' ),
	
	array(  
		'name' => __( 'Currency', 'woothemes' ),
		'desc' 		=> sprintf( __("This controls what currency prices are listed at in the catalog, and which currency PayPal, and other gateways, will take payments in. See the list of supported <a target='_new' href='%s'>PayPal currencies</a>.", 'woothemes'), 'https://www.paypal.com/cgi-bin/webscr?cmd=p/sell/mc/mc_intro-outside' ),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_currency',
		'css' 		=> 'min-width:175px;',
		'std' 		=> 'GBP',
		'type' 		=> 'select',
		'options' => apply_filters('woocommerce_currencies', array( 
			'USD' => __( 'US Dollars (&#36;)', 'woothemes' ),
			'EUR' => __( 'Euros (&euro;)', 'woothemes' ),
			'GBP' => __( 'Pounds Sterling (&pound;)', 'woothemes' ),
			'AUD' => __( 'Australian Dollars (&#36;)', 'woothemes' ),
			'BRL' => __( 'Brazilian Real (&#36;)', 'woothemes' ),
			'CAD' => __( 'Canadian Dollars (&#36;)', 'woothemes' ),
			'CZK' => __( 'Czech Koruna', 'woothemes' ),
			'DKK' => __( 'Danish Krone', 'woothemes' ),
			'HKD' => __( 'Hong Kong Dollar (&#36;)', 'woothemes' ),
			'HUF' => __( 'Hungarian Forint', 'woothemes' ),
			'ILS' => __( 'Israeli Shekel', 'woothemes' ),
			'JPY' => __( 'Japanese Yen (&yen;)', 'woothemes' ),
			'MYR' => __( 'Malaysian Ringgits', 'woothemes' ),
			'MXN' => __( 'Mexican Peso (&#36;)', 'woothemes' ),
			'NZD' => __( 'New Zealand Dollar (&#36;)', 'woothemes' ),
			'NOK' => __( 'Norwegian Krone', 'woothemes' ),
			'PHP' => __( 'Philippine Pesos', 'woothemes' ),
			'PLN' => __( 'Polish Zloty', 'woothemes' ),
			'SGD' => __( 'Singapore Dollar (&#36;)', 'woothemes' ),
			'SEK' => __( 'Swedish Krona', 'woothemes' ),
			'CHF' => __( 'Swiss Franc', 'woothemes' ),
			'TWD' => __( 'Taiwan New Dollars', 'woothemes' ),
			'THB' => __( 'Thai Baht', 'woothemes' ), 
			'TRY' => __( 'Turkish Lira (TL)', 'woothemes' )
			)
		)
	),
	
	array(  
		'name' => __( 'Currency Position', 'woothemes' ),
		'desc' 		=> __( 'This controls the position of the currency symbol.', 'woothemes' ),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_currency_pos',
		'css' 		=> 'min-width:175px;',
		'std' 		=> 'left',
		'type' 		=> 'select',
		'options' => array( 
			'left' => __( 'Left', 'woothemes' ),
			'right' => __( 'Right', 'woothemes' ),
			'left_space' => __( 'Left (with space)', 'woothemes' ),
			'right_space' => __( 'Right (with space)', 'woothemes' )
		)
	),
	
	array(  
		'name' => __( 'Thousand separator', 'woothemes' ),
		'desc' 		=> __( 'This sets the thousand separator of displayed prices.', 'woothemes' ),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_price_thousand_sep',
		'css' 		=> 'width:30px;',
		'std' 		=> ',',
		'type' 		=> 'text',
	),
	
	array(  
		'name' => __( 'Decimal separator', 'woothemes' ),
		'desc' 		=> __( 'This sets the decimal separator of displayed prices.', 'woothemes' ),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_price_decimal_sep',
		'css' 		=> 'width:30px;',
		'std' 		=> '.',
		'type' 		=> 'text',
	),
	
	array(  
		'name' => __( 'Number of decimals', 'woothemes' ),
		'desc' 		=> __( 'This sets the number of decimal points shown in displayed prices.', 'woothemes' ),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_price_num_decimals',
		'css' 		=> 'width:30px;',
		'std' 		=> '2',
		'type' 		=> 'text',
	),
	
	array( 'type' => 'sectionend', 'id' => 'pricing_options' ),
	
	array(	'name' => __( 'Image Options', 'woothemes' ), 'type' => 'title','desc' => __('These settings affect the actual dimensions of images in your catalog - the display on the front-end will still be affected by CSS styles.', 'woothemes'), 'id' => 'image_options' ),
	
	array(  
		'name' => __( 'Catalog Images', 'woothemes' ),
		'desc' 		=> __('This size is usually used in product listings', 'woothemes'),
		'id' 		=> 'woocommerce_catalog_image',
		'css' 		=> '',
		'type' 		=> 'image_width',
		'std' 		=> '150'
	),

	array(  
		'name' => __( 'Single Product Image', 'woothemes' ),
		'desc' 		=> __('This is the size used by the main image on the product page.', 'woothemes'),
		'id' 		=> 'woocommerce_single_image',
		'css' 		=> '',
		'type' 		=> 'image_width',
		'std' 		=> '300'
	),
	
	array(  
		'name' => __( 'Product Thumbnails', 'woothemes' ),
		'desc' 		=> __('This size is usually used for the gallery of images on the product page.', 'woothemes'),
		'id' 		=> 'woocommerce_thumbnail_image',
		'css' 		=> '',
		'type' 		=> 'image_width',
		'std' 		=> '90'
	),
	
	array( 'type' => 'sectionend', 'id' => 'image_options' ),

)); // End catalog settings


$woocommerce_settings['inventory'] = apply_filters('woocommerce_inventory_settings', array(

	array(	'name' => __( 'Inventory Options', 'woothemes' ), 'type' => 'title','desc' => '', 'id' => 'inventory_options' ),
	
	array(  
		'name' => __( 'Manage stock', 'woothemes' ),
		'desc' 		=> __( 'Enable stock management', 'woothemes' ),
		'id' 		=> 'woocommerce_manage_stock',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),
	
	array(  
		'name' => __( 'Notifications', 'woothemes' ),
		'desc' 		=> __( 'Enable low stock notifications', 'woothemes' ),
		'id' 		=> 'woocommerce_notify_low_stock',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup' => 'start'
	),
	
	array(  
		'desc' 		=> __( 'Enable out of stock notifications', 'woothemes' ),
		'id' 		=> 'woocommerce_notify_no_stock',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup' => 'end'
	),
	
	array(  
		'name' => __( 'Low stock threshold', 'woothemes' ),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'woocommerce_notify_low_stock_amount',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'text',
		'std' 		=> '2'
	),
	
	array(  
		'name' => __( 'Out of stock threshold', 'woothemes' ),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'woocommerce_notify_no_stock_amount',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'text',
		'std' 		=> '0'
	),
	
	array(  
		'name' => __( 'Out of stock visibility', 'woothemes' ),
		'desc' 		=> __('Hide out of stock items from the catalog', 'woothemes'),
		'id' 		=> 'woocommerce_hide_out_of_stock_items',
		'std' 		=> 'no',
		'type' 		=> 'checkbox'
	),
	
	array( 'type' => 'sectionend', 'id' => 'inventory_options'),

)); // End inventory settings


$woocommerce_settings['shipping'] = apply_filters('woocommerce_shipping_settings', array(

	array(	'name' => __( 'Shipping Options', 'woothemes' ), 'type' => 'title','desc' => '', 'id' => 'shipping_options' ),
	
	array(  
		'name' 		=> __( 'Calculate shipping', 'woothemes' ),
		'desc' 		=> __( 'Enable shipping/shipping calculations', 'woothemes' ),
		'id' 		=> 'woocommerce_calc_shipping',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),
	
	array(  
		'name' 		=> __( 'Shipping calculator', 'woothemes' ),
		'desc' 		=> __( 'Enable the shipping calculator on the cart page', 'woothemes' ),
		'id' 		=> 'woocommerce_enable_shipping_calc',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),
	
	array(  
		'name' 		=> __( 'Ship to billing', 'woothemes' ),
		'desc' 		=> __( 'Only ship to the users billing address', 'woothemes' ),
		'id' 		=> 'woocommerce_ship_to_billing_address_only',
		'std' 		=> 'no',
		'type' 		=> 'checkbox'
	),
	
	array( 'type' => 'sectionend', 'id' => 'shipping_options' ),

)); // End shipping settings


$woocommerce_settings['tax'] = apply_filters('woocommerce_tax_settings', array(

	array(	'name' => __( 'Tax Options', 'woothemes' ), 'type' => 'title','desc' => '', 'id' => 'tax_options' ),
	
	array(  
		'name' => __( 'Calculate Taxes', 'woothemes' ),
		'desc' 		=> __( 'Enable taxes and tax calculations', 'woothemes' ),
		'id' 		=> 'woocommerce_calc_taxes',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),
	
	array(  
		'name' => __( 'Prices inclusive of tax', 'woothemes' ),
		'desc' 		=> __( 'Catalog Prices include tax', 'woothemes' ),
		'id' 		=> 'woocommerce_prices_include_tax',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),
	
	array(  
		'name' => __( 'Cart totals display...', 'woothemes' ),
		'desc' 		=> '',
		'tip' 		=> __( 'Should the subtotal be shown including or excluding tax on the frontend?', 'woothemes' ),
		'id' 		=> 'woocommerce_display_totals_tax',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'excluding',
		'type' 		=> 'select',
		'options' => array(  
			'including' => __( 'price including tax', 'woothemes' ),
			'excluding'  => __( 'price excluding tax', 'woothemes' )
		)
	),

	array(  
		'name' => __( 'Additional Tax classes', 'woothemes' ),
		'desc' 		=> __( 'List 1 per line. This is in addition to the default <em>Standard Rate</em>.', 'woothemes' ),
		'tip' 		=> __( 'List product and shipping tax classes here, e.g. Zero Tax, Reduced Rate.', 'woothemes' ),
		'id' 		=> 'woocommerce_tax_classes',
		'css' 		=> 'width:100%; height: 75px;',
		'type' 		=> 'textarea',
		'std' 		=> "Reduced Rate\nZero Rate"
	),
	
	array(  
		'name' => __( 'Tax rates', 'woothemes' ),
		'desc' 		=> __( 'All fields are required.', 'woothemes' ),
		'tip' 		=> __( 'To avoid rounding errors, insert tax rates with 4 decimal places.', 'woothemes' ),
		'id' 		=> 'woocommerce_tax_rates',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'tax_rates',
		'std' 		=> ''
	),
	
	array( 'type' => 'sectionend', 'id' => 'tax_options' ),

)); // End tax settings

/**
 * Settings page
 * 
 * Handles the display of the main woocommerce settings page in admin.
 */
if (!function_exists('woocommerce_settings')) {
function woocommerce_settings() {
    global $woocommerce, $woocommerce_settings;
    
    $current_tab = (isset($_GET['tab'])) ? $_GET['tab'] : 'general';
    
    if( isset( $_POST ) && $_POST ) :
    	if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) ) die( __( 'Action failed. Please refresh the page and retry.', 'woothemes' ) ); 
    	
    	switch ( $current_tab ) :
			case "general" :
			case "pages" :
			case "catalog" :
			case "inventory" :
			case "shipping" :
			case "tax" :
				woocommerce_update_options( $woocommerce_settings[$current_tab] );
			break;
		endswitch;
		
		do_action( 'woocommerce_update_options' );
		do_action( 'woocommerce_update_options_' . $current_tab );
		flush_rewrite_rules( false );
		wp_redirect( add_query_arg( 'saved', 'true', admin_url( 'admin.php?page=woocommerce&tab=' . $current_tab ) ) );
    endif;
    
    if (isset($_GET['saved']) && $_GET['saved']) :
    	echo '<div id="message" class="updated fade"><p><strong>' . __( 'Your settings have been saved.', 'woothemes' ) . '</strong></p></div>';
        flush_rewrite_rules( false );
    endif;
    
    if (isset($_GET['installed']) && $_GET['installed']) :
    	echo '<div id="message" class="updated fade"><p style="float:right;">' . __( 'Like WooCommerce? <a href="http://wordpress.org/extend/plugins/woocommerce/">Support us by leaving a rating!</a>', 'woothemes' ) . '</p><p><strong>' . __( 'WooCommerce has been installed. Enjoy :)', 'woothemes' ) . '</strong></p></div>';
    	flush_rewrite_rules( false );
    endif;
    ?>
	<div class="wrap woocommerce">
		<form method="post" id="mainform" action="">
			<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br></div><h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
				<?php
					$tabs = array(
						'general' => __( 'General', 'woothemes' ),
						'pages' => __( 'Pages', 'woothemes' ),
						'catalog' => __( 'Catalog', 'woothemes' ),
						'inventory' => __( 'Inventory', 'woothemes' ),
						'shipping' => __( 'Shipping', 'woothemes' ),
						'tax' => __( 'Tax', 'woothemes'),
						'shipping_methods' => __( 'Shipping Methods', 'woothemes' ),
						'payment_gateways' => __( 'Payment Gateways', 'woothemes' )
					);
					foreach ($tabs as $name => $label) :
						echo '<a href="' . admin_url( 'admin.php?page=woocommerce&tab=' . $name ) . '" class="nav-tab ';
						if( $current_tab==$name ) echo 'nav-tab-active';
						echo '">' . $label . '</a>';
					endforeach;
					
					do_action( 'woocommerce_settings_tabs' ); 
				?>
			</h2>
			<?php wp_nonce_field( 'woocommerce-settings', '_wpnonce', true, true ); ?>
			<?php
				switch ($current_tab) :
					case "general" :
					case "pages" :
					case "catalog" :
					case "inventory" :
					case "shipping" :
					case "tax" :
						woocommerce_admin_fields( $woocommerce_settings[$current_tab] );
					break;
					case "shipping_methods" : 	
						
						$links = array();
		            	
		            	foreach ( $woocommerce->shipping->shipping_methods as $method ) :
		            		$title = ($method->method_title) ? ucwords($method->method_title) : ucwords($method->id);
		            		$links[] = '<a href="#shipping-'.$method->id.'">'.$title.'</a>';
						endforeach;
						
						echo '<div class="subsubsub_section"><ul class="subsubsub"><li>' . implode(' | </li><li>', $links) . '</li></ul><br class="clear" />';
		            
		            	foreach ($woocommerce->shipping->shipping_methods as $method) :
		            		echo '<div class="section" id="shipping-'.$method->id.'">';
		            		$method->admin_options();
		            		echo '</div>';
		            	endforeach; 
		            	
		            	echo '</div>';
            	
					break;
					case "payment_gateways" : 	
					
						$links = array();
            	
		            	foreach ($woocommerce->payment_gateways->payment_gateways() as $gateway) :
		            		$title = ( isset( $gateway->method_title ) && $gateway->method_title) ? ucwords($gateway->method_title) : ucwords($gateway->id);
		            		$links[] = '<a href="#gateway-'.$gateway->id.'">'.$title.'</a>';
						endforeach;
						
						echo '<div class="subsubsub_section"><ul class="subsubsub"><li>' . implode(' | </li><li>', $links) . '</li></ul><br class="clear" />';
		            
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
	        <p class="submit"><input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'woothemes' ); ?>" /></p>
		</form>
		
		<script type="text/javascript">

			// Subsubsub tabs
			jQuery('ul.subsubsub li a:eq(0)').addClass('current');
			jQuery('.subsubsub_section .section:gt(0)').hide();
			
			jQuery('ul.subsubsub li a').click(function(){
				jQuery('a', jQuery(this).closest('ul.subsubsub')).removeClass('current');
				jQuery(this).addClass('current');
				jQuery('.section', jQuery(this).closest('.subsubsub_section')).hide();
				jQuery( jQuery(this).attr('href') ).show();
				return false;
			});
			
			// Countries
			jQuery('select#woocommerce_allowed_countries').change(function(){
				if (jQuery(this).val()=="specific") {
					jQuery(this).parent().parent().next('tr.multi_select_countries').show();
				} else {
					jQuery(this).parent().parent().next('tr.multi_select_countries').hide();
				}
			}).change();
			
			// Country Multiselect boxes
			jQuery(".country_multiselect").multiselect({
				noneSelectedText: '<?php _e( 'Select countries/states', 'woothemes' ); ?>',
				selectedList: 4
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
						    return '<?php echo __( 'The changes you made will be lost if you navigate away from this page.', 'woothemes' ); ?>';
						}
					} else {
						window.onbeforeunload = '';
					}
				});
				
				jQuery('.submit input').click(function(){
					window.onbeforeunload = '';
				});
			});
		</script>
	</div>
	<?php
}
}