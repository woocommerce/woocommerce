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
 * options_settings
 * 
 * This variable contains all the options used on the jigpshop settings page
 */
global $options_settings;

$options_settings = apply_filters('woocommerce_options_settings', array(

	array( 'type' => 'tab', 'tabname' => __('General', 'woothemes') ),

	array( 'name' => __('General Options', 'woothemes'), 'type' => 'title', 'desc' 		=> '' ),
	
	array(  
		'name' => __('Demo store', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> __('Enable this option to show a banner at the top of the page stating its a demo store.', 'woothemes'),
		'id' 		=> 'woocommerce_demo_store',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'no',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'woothemes'),
			'no'  => __('No', 'woothemes')
		)
	),

	array(  
		'name' => __('Enable SKU field', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> __('Turning off the SKU field will give products an SKU of their post id.', 'woothemes'),
		'id' 		=> 'woocommerce_enable_sku',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'woothemes'),
			'no'  => __('No', 'woothemes')
		)
	),
	
	array(  
		'name' => __('Enable weight field', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'woocommerce_enable_weight',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'woothemes'),
			'no'  => __('No', 'woothemes')
		)
	),
	
	array(  
		'name' => __('Weight Unit', 'woothemes'),
		'desc' 		=> __("This controls what unit you will define weights in.", 'woothemes'),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_weight_unit',
		'css' 		=> 'min-width:200px;',
		'std' 		=> 'GBP',
		'type' 		=> 'select',
		'options' => array( 
			'kg' => __('kg', 'woothemes'),
			'lbs' => __('lbs', 'woothemes')
		)
	),
	
	array(  
		'name' => __('Base Country/Region', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> __('This is the base country for your business. Tax rates will be based on this country.', 'woothemes'),
		'id' 		=> 'woocommerce_default_country',
		'css' 		=> '',
		'std' 		=> 'GB',
		'type' 		=> 'single_select_country'
	),
	
	array(  
		'name' => __('Allowed Countries', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> __('These are countries that you are willing to ship to.', 'woothemes'),
		'id' 		=> 'woocommerce_allowed_countries',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'all',
		'type' 		=> 'select',
		'options' => array(  
			'all'  => __('All Countries', 'woothemes'),
			'specific' => __('Specific Countries', 'woothemes')			
		)
	),
	
	array(  
		'name' => __('Specific Countries', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'woocommerce_specific_allowed_countries',
		'css' 		=> '',
		'std' 		=> '',
		'type' 		=> 'multi_select_countries'
	),
	
	array(  
		'name' => __('Enable guest checkout?', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> __('Without guest checkout, all users will require an account in order to checkout.', 'woothemes'),
		'id' 		=> 'woocommerce_enable_guest_checkout',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'woothemes'),
			'no'  => __('No', 'woothemes')
		)
	),
	
	array(  
		'name' => __('Force SSL on checkout?', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> __('Forcing SSL is recommended', 'woothemes'),
		'id' 		=> 'woocommerce_force_ssl_checkout',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'no',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'woothemes'),
			'no'  => __('No', 'woothemes')
		)
	),
	
	array(  
		'name' => __('ShareThis Publisher ID', 'woothemes'),
		'desc' 		=> __("Enter your <a href='http://sharethis.com/account/'>ShareThis publisher ID</a> to show ShareThis on product pages.", 'woothemes'),
		'tip' 		=> __('ShareThis is a small social sharing widget for posting links on popular sites such as Twitter and Facebook.', 'woothemes'),
		'id' 		=> 'woocommerce_sharethis',
		'css' 		=> 'width:300px;',
		'type' 		=> 'text',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Disable WooCommerce CSS', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> __('Useful if you want to disable WooCommerce styles and theme it yourself via your theme.', 'woothemes'),
		'id' 		=> 'woocommerce_disable_css',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'no',
		'type' 		=> 'select',
		'options' => array(  
			'no'  => __('No', 'woothemes'),
			'yes' => __('Yes', 'woothemes')
		)
	),
	
	array( 'type' => 'tabend'),
	
	array( 'type' => 'tab', 'tabname' => __('Pages', 'woothemes') ),

	array( 'name' => __('Shop page configuration', 'woothemes'), 'type' => 'title', 'desc' 		=> '' ),
	
	array(  
		'name' => __('Cart Page', 'woothemes'),
		'desc' 		=> __('Your page should contain [woocommerce_cart]', 'woothemes'),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_cart_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Checkout Page', 'woothemes'),
		'desc' 		=> __('Your page should contain [woocommerce_checkout]', 'woothemes'),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_checkout_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Pay Page', 'woothemes'),
		'desc' 		=> __('Your page should contain [woocommerce_pay] and usually have "Checkout" as the parent.', 'woothemes'),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_pay_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Thanks Page', 'woothemes'),
		'desc' 		=> __('Your page should contain [woocommerce_thankyou] and usually have "Checkout" as the parent.', 'woothemes'),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_thanks_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('My Account Page', 'woothemes'),
		'desc' 		=> __('Your page should contain [woocommerce_my_account]', 'woothemes'),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_myaccount_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Edit Address Page', 'woothemes'),
		'desc' 		=> __('Your page should contain [woocommerce_edit_address] and usually have "My Account" as the parent.', 'woothemes'),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_edit_address_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('View Order Page', 'woothemes'),
		'desc' 		=> __('Your page should contain [woocommerce_view_order] and usually have "My Account" as the parent.', 'woothemes'),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_view_order_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Change Password Page', 'woothemes'),
		'desc' 		=> __('Your page should contain [woocommerce_change_password] and usually have "My Account" as the parent.', 'woothemes'),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_change_password_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),	
	
	array( 'type' => 'tabend'),
	
	array( 'type' 		=> 'tab', 'tabname' => __('Catalog', 'woothemes') ),
	
	array(	'name' => __('Catalog Options', 'woothemes'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),

	
	array(  
		'name' => __('Products Base Page', 'woothemes'),
		'desc' 		=> sprintf( __("IMPORTANT: You must <a target='_blank' href='%s'>re-save your permalinks</a> for this change to take effect.", 'woothemes'), 'options-permalink.php' ),
		'tip' 		=> __('This sets the base page of your shop. You should not change this value once you have launched your site otherwise you risk breaking urls of other sites pointing to yours, etc.', 'woothemes'),
		'id' 		=> 'woocommerce_shop_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Prepend shop categories/tags with base page?', 'woothemes'),
		'desc' 		=> sprintf( __("IMPORTANT: You must <a target='_blank' href='%s'>re-save your permalinks</a> for this change to take effect.", 'woothemes'), 'options-permalink.php' ),
		'tip' 		=> __('If set to yes, categories will show up as your_base_page/shop_category instead of just shop_category.', 'woothemes'),
		'id' 		=> 'woocommerce_prepend_shop_page_to_urls',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'no',
		'type' 		=> 'select',
		'options' => array(  
			'no'  => __('No', 'woothemes'),
			'yes' => __('Yes', 'woothemes')
		)
	),

	array(  
		'name' => __('Terms page ID', 'woothemes'),
		'desc' 		=> __('If you define a "Terms" page the customer will be asked if they accept them when checking out.', 'woothemes'),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_terms_page_id',
		'css' 		=> 'min-width:50px;',
		'std' 		=> '',
		'type' 		=> 'single_select_page',
		'args'		=> 'show_option_none=' . __('None', 'woothemes'),
	),
	
	array(	'name' => __('Pricing Options', 'woothemes'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),

	array(  
		'name' => __('Currency', 'woothemes'),
		'desc' 		=> sprintf( __("This controls what currency prices are listed at in the catalog, and which currency PayPal, and other gateways, will take payments in. See the list of supported <a target='_new' href='%s'>PayPal currencies</a>.", 'woothemes'), 'https://www.paypal.com/cgi-bin/webscr?cmd=p/sell/mc/mc_intro-outside' ),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_currency',
		'css' 		=> 'min-width:200px;',
		'std' 		=> 'GBP',
		'type' 		=> 'select',
		'options' => apply_filters('woocommerce_currencies', array( 
			'USD' => __('US Dollars (&#36;)', 'woothemes'),
			'EUR' => __('Euros (&euro;)', 'woothemes'),
			'GBP' => __('Pounds Sterling (&pound;)', 'woothemes'),
			'AUD' => __('Australian Dollars (&#36;)', 'woothemes'),
			'BRL' => __('Brazilian Real (&#36;)', 'woothemes'),
			'CAD' => __('Canadian Dollars (&#36;)', 'woothemes'),
			'CZK' => __('Czech Koruna', 'woothemes'),
			'DKK' => __('Danish Krone', 'woothemes'),
			'HKD' => __('Hong Kong Dollar (&#36;)', 'woothemes'),
			'HUF' => __('Hungarian Forint', 'woothemes'),
			'ILS' => __('Israeli Shekel', 'woothemes'),
			'JPY' => __('Japanese Yen (&yen;)', 'woothemes'),
			'MYR' => __('Malaysian Ringgits', 'woothemes'),
			'MXN' => __('Mexican Peso (&#36;)', 'woothemes'),
			'NZD' => __('New Zealand Dollar (&#36;)', 'woothemes'),
			'NOK' => __('Norwegian Krone', 'woothemes'),
			'PHP' => __('Philippine Pesos', 'woothemes'),
			'PLN' => __('Polish Zloty', 'woothemes'),
			'SGD' => __('Singapore Dollar (&#36;)', 'woothemes'),
			'SEK' => __('Swedish Krona', 'woothemes'),
			'CHF' => __('Swiss Franc', 'woothemes'),
			'TWD' => __('Taiwan New Dollars', 'woothemes'),
			'THB' => __('Thai Baht', 'woothemes') 
			)
		)
	),
	
	array(  
		'name' => __('Currency Position', 'woothemes'),
		'desc' 		=> __("This controls the position of the currency symbol.", 'woothemes'),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_currency_pos',
		'css' 		=> 'min-width:200px;',
		'std' 		=> 'left',
		'type' 		=> 'select',
		'options' => array( 
			'left' => __('Left', 'woothemes'),
			'right' => __('Right', 'woothemes'),
			'left_space' => __('Left (with space)', 'woothemes'),
			'right_space' => __('Right (with space)', 'woothemes')
		)
	),
	
	array(  
		'name' => __('Thousand separator', 'woothemes'),
		'desc' 		=> __('This sets the thousand separator of displayed prices.', 'woothemes'),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_price_thousand_sep',
		'css' 		=> 'width:30px;',
		'std' 		=> ',',
		'type' 		=> 'text',
	),
	
	array(  
		'name' => __('Decimal separator', 'woothemes'),
		'desc' 		=> __('This sets the decimal separator of displayed prices.', 'woothemes'),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_price_decimal_sep',
		'css' 		=> 'width:30px;',
		'std' 		=> '.',
		'type' 		=> 'text',
	),
	
	array(  
		'name' => __('Number of decimals', 'woothemes'),
		'desc' 		=> __('This sets the number of decimal points shown in displayed prices.', 'woothemes'),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_price_num_decimals',
		'css' 		=> 'width:30px;',
		'std' 		=> '2',
		'type' 		=> 'text',
	),
	
	array( 'type' => 'tabend'),
	
	array( 'type' => 'tab', 'tabname' => __('Coupons', 'woothemes') ),

	array( 'name' => __('Coupon Codes', 'woothemes'), 'type' => 'title', 'desc' 		=> '' ),
	
	array(  
		'name' => __('Coupons', 'woothemes'),
		'desc' 		=> 'All fields are required.',
		'tip' 		=> 'Coupons allow you to give customers special offers and discounts. Leave product IDs blank to apply to all products/items in the cart.',
		'id' 		=> 'woocommerce_coupons',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'coupons',
		'std' 		=> ''
	),
	
	array( 'type' => 'tabend'),
	
	array( 'type' 		=> 'tab', 'tabname' => __('Inventory', 'woothemes') ),
	
	array(	'name' => __('Inventory Options', 'woothemes'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),
	
	array(  
		'name' => __('Manage stock?', 'woothemes'),
		'desc' 		=> __('If you are not managing stock, turn it off here to disable it in admin and on the front-end.', 'woothemes'),
		'tip' 		=> __('You can manage stock on a per-item basis if you leave this option on.', 'woothemes'),
		'id' 		=> 'woocommerce_manage_stock',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'woothemes'),
			'no'  => __('No', 'woothemes')
		)
	),
	
	array(  
		'name' => __('Low stock notification', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> __('Set the minimum threshold for this below.', 'woothemes'),
		'id' 		=> 'woocommerce_notify_low_stock',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'woothemes'),
			'no'  => __('No', 'woothemes')
		)
	),
	
	array(  
		'name' => __('Low stock threshold', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'woocommerce_notify_low_stock_amount',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'text',
		'std' 		=> '2'
	),
	
	array(  
		'name' => __('Out-of-stock notification', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> __('Set the minimum threshold for this below.', 'woothemes'),
		'id' 		=> 'woocommerce_notify_no_stock',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'woothemes'),
			'no'  => __('No', 'woothemes')
		)
	),
	
	array(  
		'name' => __('Out of stock threshold', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'woocommerce_notify_no_stock_amount',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'text',
		'std' 		=> '0'
	),
	
	array( 'type' => 'tabend'),
	
	array( 'type' 		=> 'tab', 'tabname' => __('Shipping', 'woothemes') ),
	
	array(	'name' => __('Shipping Options', 'woothemes'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),
	
	array(  
		'name' => __('Calculate Shipping', 'woothemes'),
		'desc' 		=> __('Only set this to no if you are not shipping items, or items have shipping costs included.', 'woothemes'),
		'tip' 		=> __('If you are not calculating shipping then you can ignore all other tax options.', 'woothemes'),
		'id' 		=> 'woocommerce_calc_shipping',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'woothemes'),
			'no'  => __('No', 'woothemes')
		)
	),
	
	array(  
		'name' => __('Enable shipping calculator on cart', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'woocommerce_enable_shipping_calc',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'woothemes'),
			'no'  => __('No', 'woothemes')
		)
	),
	
	array(  
		'name' => __('Only ship to billing address?', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'woocommerce_ship_to_billing_address_only',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'no',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'woothemes'),
			'no'  => __('No', 'woothemes')
		)
	),
	
	array( 'type' => 'shipping_options'),
	
	array( 'type' => 'tabend'),
	
	array( 'type' 		=> 'tab', 'tabname' => __('Tax', 'woothemes') ),
	
	array(	'name' => __('Tax Options', 'woothemes'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),
	
	array(  
		'name' => __('Calculate Taxes', 'woothemes'),
		'desc' 		=> __('Only set this to no if you are exclusively selling non-taxable items.', 'woothemes'),
		'tip' 		=> __('If you are not calculating taxes then you can ignore all other tax options.', 'woothemes'),
		'id' 		=> 'woocommerce_calc_taxes',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'woothemes'),
			'no'  => __('No', 'woothemes')
		)
	),
	
	array(  
		'name' => __('Catalog Prices include tax?', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> __('If prices include tax then tax calculations will work backwards.', 'woothemes'),
		'id' 		=> 'woocommerce_prices_include_tax',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'woothemes'),
			'no'  => __('No', 'woothemes')
		)
	),
	
	array(  
		'name' => __('Cart totals display...', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> __('Should the subtotal be shown including or excluding tax on the frontend?', 'woothemes'),
		'id' 		=> 'woocommerce_display_totals_tax',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'excluding',
		'type' 		=> 'select',
		'options' => array(  
			'including' => __('price including tax', 'woothemes'),
			'excluding'  => __('price excluding tax', 'woothemes')
		)
	),

	array(  
		'name' => __('Additional Tax classes', 'woothemes'),
		'desc' 		=> __('List 1 per line. This is in addition to the default <em>Standard Rate</em>.', 'woothemes'),
		'tip' 		=> __('List product and shipping tax classes here, e.g. Zero Tax, Reduced Rate.', 'woothemes'),
		'id' 		=> 'woocommerce_tax_classes',
		'css' 		=> 'width:100%; height: 75px;',
		'type' 		=> 'textarea',
		'std' 		=> "Reduced Rate\nZero Rate"
	),
	
	array(  
		'name' => __('Tax rates', 'woothemes'),
		'desc' 		=> 'All fields are required.',
		'tip' 		=> 'To avoid rounding errors, insert tax rates with 4 decimal places.',
		'id' 		=> 'woocommerce_tax_rates',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'tax_rates',
		'std' 		=> ''
	),
	
	array( 'type' => 'tabend'),
	
	array( 'type' 		=> 'tab', 'tabname' => __('Payment Gateways', 'woothemes') ),
	
	array( 'type' => 'gateway_options'),
	
	array( 'type' => 'tabend')

) );

/**
 * Update options
 * 
 * Updates the options on the woocommerce settings page.
 */
function woocommerce_update_options($options) {
    if(isset($_POST['submitted']) && $_POST['submitted'] == 'yes') {
        foreach ($options as $value) {
        	if (isset($value['id']) && $value['id']=='woocommerce_tax_rates') :
        	
        		$tax_classes = array();
        		$tax_countries = array();
        		$tax_rate = array();
        		$tax_rates = array();
        		$tax_shipping = array();

				if (isset($_POST['tax_class'])) $tax_classes = $_POST['tax_class'];
				if (isset($_POST['tax_country'])) $tax_countries = $_POST['tax_country'];
				if (isset($_POST['tax_rate'])) $tax_rate = $_POST['tax_rate'];
				if (isset($_POST['tax_shipping'])) $tax_shipping = $_POST['tax_shipping'];
				
				for ($i=0; $i<sizeof($tax_classes); $i++) :
				
					if (isset($tax_classes[$i]) && isset($tax_countries[$i]) && isset($tax_rate[$i]) && $tax_rate[$i] && is_numeric($tax_rate[$i])) :
						
						$country = woocommerce_clean($tax_countries[$i]);
						$state = '*';
						$rate = number_format(woocommerce_clean($tax_rate[$i]), 4);
						$class = woocommerce_clean($tax_classes[$i]);
						
						if (isset($tax_shipping[$i]) && $tax_shipping[$i]) $shipping = 'yes'; else $shipping = 'no';
						
						// Get state from country input if defined
						if (strstr($country, ':')) :
							$cr = explode(':', $country);
							$country = current($cr);
							$state = end($cr);
						endif;
						
						$tax_rates[] = array(
							'country' => $country,
							'state' => $state,
							'rate' => $rate,
							'shipping' => $shipping,
							'class' => $class
						); 
						
					endif;

				endfor;
				
				update_option($value['id'], $tax_rates);
				
			elseif (isset($value['id']) && $value['id']=='woocommerce_coupons') :
				
				$coupon_code = array();
        		$coupon_type = array();
        		$coupon_amount = array();
        		$product_ids = array();
        		$coupons = array();
				$individual = array();
				
				if (isset($_POST['coupon_code'])) $coupon_code = $_POST['coupon_code'];
				if (isset($_POST['coupon_type'])) $coupon_type = $_POST['coupon_type'];
				if (isset($_POST['coupon_amount'])) $coupon_amount = $_POST['coupon_amount'];
				if (isset($_POST['product_ids'])) $product_ids = $_POST['product_ids'];
				if (isset($_POST['individual'])) $individual = $_POST['individual'];
				
				for ($i=0; $i<sizeof($coupon_code); $i++) :
					
					if ( isset($coupon_code[$i]) && isset($coupon_type[$i]) && isset($coupon_amount[$i]) ) :
						
						$code = woocommerce_clean($coupon_code[$i]);
						$type = woocommerce_clean($coupon_type[$i]);
						$amount = woocommerce_clean($coupon_amount[$i]);
						
						if (isset($product_ids[$i]) && $product_ids[$i]) $products = array_map('trim', explode(',', $product_ids[$i])); else $products = array();
						
						if (isset($individual[$i]) && $individual[$i]) $individual_use = 'yes'; else $individual_use = 'no';
						
						if ($code && $type && $amount) :
							$coupons[$code] = array( 
								'code' => $code,
								'amount' => $amount,
								'type' => $type,
								'products' => $products,
								'individual_use' => $individual_use
							);
						endif;
						
					endif;

				endfor;
				
				update_option($value['id'], $coupons);
			
			elseif (isset($value['type']) && $value['type']=='multi_select_countries') :
			
				// Get countries array
				if (isset($_POST[$value['id']])) $selected_countries = $_POST[$value['id']]; else $selected_countries = array();
				update_option($value['id'], $selected_countries);
			
			/* price separators get a special treatment as they should allow a spaces (don't trim) */
			elseif ( isset($value['id']) && ( $value['id'] == 'woocommerce_price_thousand_sep' || $value['id'] == 'woocommerce_price_decimal_sep' ) ):
				
				if( isset( $_POST[ $value['id'] ] )  ) {
					update_option($value['id'], $_POST[$value['id']] );
				} else {
	                @delete_option($value['id']);
	            }
	            
        	else :
			    
        		if(isset($value['id']) && isset($_POST[$value['id']])) {
	            	update_option($value['id'], woocommerce_clean($_POST[$value['id']]));
	            } else {
	                @delete_option($value['id']);
	            }
            
	        endif;
	        
        }
        
        do_action('woocommerce_update_options');
        
        echo '<div id="message" class="updated fade"><p><strong>'.__('Your settings have been saved.', 'woothemes').'</strong></p></div>';
    }
}

/**
 * Admin fields
 * 
 * Loops though the woocommerce options array and outputs each field.
 */
function woocommerce_admin_fields($options) {
	?>
	<div id="tabs-wrap">
		<p class="submit"><input name="save" type="submit" value="<?php _e('Save changes', 'woothemes') ?>" /></p>
		<?php
		    $counter = 1;
		    echo '<ul class="tabs">';
		    foreach ($options as $value) {
				if ( 'tab' == $value['type'] ) :
		            echo '<li><a href="#'.$value['type'].$counter.'">'.$value['tabname'].'</a></li>'. "\n";
		            $counter = $counter + 1;
				endif;
		    }
		    echo '</ul>';
		    $counter = 1;
		    foreach ($options as $value) :
		        switch($value['type']) :
					case 'string':
						?>
						<tr>
							<td class="titledesc"><?php echo $value['name']; ?></td>
							<td class="forminp"><?php echo $value['desc']; ?></td>
						</tr>
						<?php
					break;
		            case 'tab':
		                echo '<div id="'.$value['type'].$counter.'" class="panel">';
		                echo '<table class="widefat fixed" style="width:850px; margin-bottom:20px;">'. "\n\n";
		            break;
		            case 'title':
		            	?><thead><tr><th scope="col" width="200px"><?php echo $value['name'] ?></th><th scope="col" class="desc"><?php if (isset($value['desc'])) echo $value['desc'] ?>&nbsp;</th></tr></thead><?php
		            break;
		            case 'text':
		            	?><tr>
		                    <td class="titledesc"><?php if ($value['tip']) { ?><a href="#" tip="<?php echo $value['tip'] ?>" class="tips" tabindex="99"></a><?php } ?><?php echo $value['name'] ?>:</td>
		                    <td class="forminp"><input name="<?php echo $value['id'] ?>" id="<?php echo $value['id'] ?>" type="<?php echo $value['type'] ?>" style="<?php echo $value['css'] ?>" value="<?php if ( get_option( $value['id']) !== false && get_option( $value['id']) !== null ) echo  stripslashes(get_option($value['id'])); else echo $value['std'] ?>" /><br /><small><?php echo $value['desc'] ?></small></td>
		                </tr><?php
		            break;
		            case 'select':
		            	?><tr>
		                    <td class="titledesc"><?php if ($value['tip']) { ?><a href="#" tip="<?php echo $value['tip'] ?>" class="tips" tabindex="99"></a><?php } ?><?php echo $value['name'] ?>:</td>
		                    <td class="forminp"><select name="<?php echo $value['id'] ?>" id="<?php echo $value['id'] ?>" style="<?php echo $value['css'] ?>">
		                        <?php
		                        foreach ($value['options'] as $key => $val) {
		                        ?>
		                            <option value="<?php echo $key ?>" <?php if (get_option($value['id']) == $key) { ?> selected="selected" <?php } ?>><?php echo ucfirst($val) ?></option>
		                        <?php
		                        }
		                        ?>
		                       </select><br /><small><?php echo $value['desc'] ?></small>
		                    </td>
		                </tr><?php
		            break;
		            case 'textarea':
		            	?><tr>
		                    <td class="titledesc"><?php if ($value['tip']) { ?><a href="#" tip="<?php echo $value['tip'] ?>" class="tips" tabindex="99"></a><?php } ?><?php echo $value['name'] ?>:</td>
		                    <td class="forminp">
		                        <textarea <?php if ( isset($value['args']) ) echo $value['args'] . ' '; ?>name="<?php echo $value['id'] ?>" id="<?php echo $value['id'] ?>" style="<?php echo $value['css'] ?>"><?php if (get_option($value['id'])) echo stripslashes(get_option($value['id'])); else echo $value['std']; ?></textarea>
		                        <br /><small><?php echo $value['desc'] ?></small>
		                    </td>
		                </tr><?php
		            break;
		            case 'tabend':
						echo '</table></div>';
		                $counter = $counter + 1;
		            break;
		            case 'single_select_page' :
		            	$page_setting = (int) get_option($value['id']);
		            	
		            	$args = array( 'name'	=> $value['id'],
		            				   'id'		=> $value['id']. '" style="width: 200px;',
		            				   'sort_column' 	=> 'menu_order',
		            				   'sort_order'		=> 'ASC',
		            				   'selected'		=> $page_setting);
		            	
		            	if( isset($value['args']) ) $args = wp_parse_args($value['args'], $args);
		            	
		            	?><tr class="single_select_page">
		                    <td class="titledesc"><?php if ($value['tip']) { ?><a href="#" tip="<?php echo $value['tip'] ?>" class="tips" tabindex="99"></a><?php } ?><?php echo $value['name'] ?>:</td>
		                    <td class="forminp">
					        	<?php wp_dropdown_pages($args); ?>  
					        	<br /><small><?php echo $value['desc'] ?></small>        
					        </td>
		               	</tr><?php	
		            break;
		            case 'single_select_country' :
		            	$countries = woocommerce_countries::$countries;
		            	$country_setting = (string) get_option($value['id']);
		            	if (strstr($country_setting, ':')) :
		            		$country = current(explode(':', $country_setting));
		            		$state = end(explode(':', $country_setting));
		            	else :
		            		$country = $country_setting;
		            		$state = '*';
		            	endif;
		            	?><tr class="multi_select_countries">
		                    <td class="titledesc"><?php if ($value['tip']) { ?><a href="#" tip="<?php echo $value['tip'] ?>" class="tips" tabindex="99"></a><?php } ?><?php echo $value['name'] ?>:</td>
		                    <td class="forminp"><select name="<?php echo $value['id'] ?>" title="Country" style="width: 150px;">	
					        	<?php echo woocommerce_countries::country_dropdown_options($country, $state); ?>          
					        </select>
		               		</td>
		               	</tr><?php	
		            break;
		            case 'multi_select_countries' :
		            	$countries = woocommerce_countries::$countries;
		            	asort($countries);
		            	$selections = (array) get_option($value['id']);
		            	?><tr class="multi_select_countries">
		                    <td class="titledesc"><?php if ($value['tip']) { ?><a href="#" tip="<?php echo $value['tip'] ?>" class="tips" tabindex="99"></a><?php } ?><?php echo $value['name'] ?>:</td>
		                    <td class="forminp">
		                    	<div class="multi_select_countries"><ul><?php
			            			if ($countries) foreach ($countries as $key=>$val) :
		                    			                    			
			            				echo '<li><label><input type="checkbox" name="'. $value['id'] .'[]" value="'. $key .'" ';
			            				if (in_array($key, $selections)) echo 'checked="checked"';
			            				echo ' />'. $val .'</label></li>';
		 
		                    		endforeach;
		               			?></ul></div>
		               		</td>
		               	</tr><?php		            	
		            break;
		            case 'coupons' :
		            	$coupons = new woocommerce_coupons();
		            	$coupon_codes = $coupons->get_coupons();
		            	?><tr>
		                    <td class="titledesc"><?php if ($value['tip']) { ?><a href="#" tip="<?php echo $value['tip'] ?>" class="tips" tabindex="99"></a><?php } ?><?php echo $value['name'] ?>:</td>
		                    <td class="forminp" id="coupon_codes">
		                    	<table class="coupon_rows" cellspacing="0">
			                    	<thead>
			                    		<tr>
			                    			<th><?php _e('Coupon Code', 'woothemes'); ?></th>
			                    			<th><?php _e('Coupon Type', 'woothemes'); ?></th>
			                    			<th><?php _e('Coupon Amount', 'woothemes'); ?></th>
			                    			<th><?php _e('Product ids', 'woothemes'); ?></th>
			                    			<th><?php _e('Individual use', 'woothemes'); ?></th>
			                    			<th><?php _e('Delete', 'woothemes'); ?></th>
			                    		</tr>
			                    	</thead>
			                    	<tbody>
			                    	<?php
			                    	$i = -1;
			                    	if ($coupon_codes && is_array($coupon_codes) && sizeof($coupon_codes)>0) foreach( $coupon_codes as $coupon ) : $i++;
			                    		echo '<tr class="coupon_row"><td><input type="text" value="'.$coupon['code'].'" name="coupon_code['.$i.']" title="'.__('Coupon Code', 'woothemes').'" placeholder="'.__('Coupon Code', 'woothemes').'" class="text" /></td><td><select name="coupon_type['.$i.']" title="Coupon Type">';
			                    		
			                    		$discount_types = array(
			                    			'fixed_cart' 	=> __('Cart Discount', 'woothemes'),
			                    			'percent' 		=> __('Cart % Discount', 'woothemes'),
			                    			'fixed_product'	=> __('Product Discount', 'woothemes')
			                    		);
			                    		
			                    		foreach ($discount_types as $type => $label) :
			                    			$selected = ($coupon['type']==$type) ? 'selected="selected"' : '';
			                    			echo '<option value="'.$type.'" '.$selected.'>'.$label.'</option>';
			                    		endforeach;
			                    		
			                    		echo '</select></td><td><input type="text" value="'.$coupon['amount'].'" name="coupon_amount['.$i.']" title="'.__('Coupon Amount', 'woothemes').'" placeholder="'.__('Coupon Amount', 'woothemes').'" class="text" /></td><td><input type="text" value="'.implode(', ', $coupon['products']).'" name="product_ids['.$i.']" placeholder="'.__('1, 2, 3', 'woothemes').'" class="text" /></td><td><label><input type="checkbox" name="individual['.$i.']" ';
					                    
					                    if (isset($coupon['individual_use']) && $coupon['individual_use']=='yes') echo 'checked="checked"';
					                    
					                    echo ' /> '.__('Individual use only', 'woothemes').'</label></td><td><a href="#" class="remove button">&times;</a></td></tr>';
			                    	endforeach;
			                    	?>
			                    	</tbody>
		                        </table>
		                        <p><a href="#" class="add button"><?php _e('+ Add Coupon', 'woothemes'); ?></a></p>
		                    </td>
		                </tr>
		                <script type="text/javascript">
						/* <![CDATA[ */
							jQuery(function() {
								jQuery('#coupon_codes a.add').live('click', function(){
									var size = jQuery('#coupon_codes table.coupon_rows tbody .coupon_row').size();
									
									// Make sure tbody exists
									var tbody_size = jQuery('#coupon_codes table.coupon_rows tbody').size();
									if (tbody_size==0) jQuery('#coupon_codes table.coupon_rows').append('<tbody></tbody>');
									
									// Add the row
									jQuery('<tr class="coupon_row">\
										<td><input type="text" value="" name="coupon_code[' + size + ']" title="<?php _e('Coupon Code', 'woothemes'); ?>" placeholder="<?php _e('Coupon Code', 'woothemes'); ?>" class="text" /></td>\
										<td><select name="coupon_type[' + size + ']" title="Coupon Type">\
			                    			<option value="percent"><?php _e('% Discount', 'woothemes'); ?></option>\
			                    			<option value="fixed_product"><?php _e('Product Discount', 'woothemes');?></option>\
			                    			<option value="fixed_cart"><?php _e('Cart Discount', 'woothemes'); ?></option>\
			                    		</select></td>\
			                    		<td><input type="text" value="" name="coupon_amount[' + size + ']" title="<?php _e('Coupon Amount', 'woothemes'); ?>" placeholder="<?php _e('Coupon Amount', 'woothemes'); ?>" class="text" /></td>\
			                    		<td><input type="text" value="" name="product_ids[' + size + ']" placeholder="<?php _e('1, 2, 3', 'woothemes'); ?>" class="text" /></td>\
			                    		<td><label><input type="checkbox" name="individual[' + size + ']" /> <?php _e('Individual use only', 'woothemes'); ?></label></td>\
			                    		<td><a href="#" class="remove button">&times;</a></td></tr>').appendTo('#coupon_codes table.coupon_rows tbody');
									return false;
								});
								jQuery('#coupon_codes a.remove').live('click', function(){
									var answer = confirm("<?php _e('Delete this coupon?', 'woothemes'); ?>")
									if (answer) {
										jQuery('input', jQuery(this).parent().parent()).val('');
										jQuery(this).parent().parent().hide();
									}
									return false;
								});
							});						
						/* ]]> */
						</script>
		                <?php
		            break;
		            case 'tax_rates' :
		            	$_tax = new woocommerce_tax();
		            	$tax_classes = $_tax->get_tax_classes();
		            	$tax_rates = get_option('woocommerce_tax_rates');
		            	?><tr>
		                    <td class="titledesc"><?php if ($value['tip']) { ?><a href="#" tip="<?php echo $value['tip'] ?>" class="tips" tabindex="99"></a><?php } ?><?php echo $value['name'] ?>:</td>
		                    <td class="forminp" id="tax_rates">
		                    	<div class="taxrows">
			                    	<?php
			                    	$i = -1;
			                    	if ($tax_rates && is_array($tax_rates) && sizeof($tax_rates)>0) foreach( $tax_rates as $rate ) : $i++;
			                    		echo '<p class="taxrow"><select name="tax_class['.$i.']" title="Tax Class"><option value="">'.__('Standard Rate', 'woothemes').'</option>';
			                    		
			                    		if ($tax_classes) foreach ($tax_classes as $class) :
					                        echo '<option value="'.sanitize_title($class).'"';
					                        
					                        if ($rate['class']==sanitize_title($class)) echo 'selected="selected"';
					                        
					                        echo '>'.$class.'</option>';
					                    endforeach;
					                    
					                    echo '</select><select name="tax_country['.$i.']" title="Country">';	
					                        		
					                    woocommerce_countries::country_dropdown_options($rate['country'], $rate['state']);
					                    
					                    echo '</select><input type="text" class="text" value="'.$rate['rate'].'" name="tax_rate['.$i.']" title="'.__('Rate', 'woothemes').'" placeholder="'.__('Rate', 'woothemes').'" maxlength="8" />% <label><input type="checkbox" name="tax_shipping['.$i.']" ';
					                    
					                    if (isset($rate['shipping']) && $rate['shipping']=='yes') echo 'checked="checked"';
					                    
					                    echo ' /> '.__('Apply to shipping', 'woothemes').'</label><a href="#" class="remove button">&times;</a></p>';
			                    	endforeach;
			                    	?>
		                        </div>
		                        <p><a href="#" class="add button"><?php _e('+ Add Tax Rule', 'woothemes'); ?></a></p>
		                    </td>
		                </tr>
		                <script type="text/javascript">
						/* <![CDATA[ */
							jQuery(function() {
								jQuery('#tax_rates a.add').live('click', function(){
									var size = jQuery('.taxrows .taxrow').size();
									
									// Add the row
									jQuery('<p class="taxrow"> \
				                        <select name="tax_class[' + size + ']" title="Tax Class"> \
				                        	<option value=""><?php _e('Standard Rate', 'woothemes'); ?></option><?php
				                        		$tax_classes = $_tax->get_tax_classes();
				                        		if ($tax_classes) foreach ($tax_classes as $class) :
				                        			echo '<option value="'.sanitize_title($class).'">'.$class.'</option>';
				                        		endforeach;
				                        	?></select><select name="tax_country[' + size + ']" title="Country"><?php
				                        		woocommerce_countries::country_dropdown_options('','',true);
				                        	?></select><input type="text" class="text" name="tax_rate[' + size + ']" title="<?php _e('Rate', 'woothemes'); ?>" placeholder="<?php _e('Rate', 'woothemes'); ?>" maxlength="8" />%\
				                        	<label><input type="checkbox" name="tax_shipping[' + size + ']" /> <?php _e('Apply to shipping', 'woothemes'); ?></label>\
				                        	<a href="#" class="remove button">&times;</a>\
			                        </p>').appendTo('#tax_rates div.taxrows');
									return false;
								});
								jQuery('#tax_rates a.remove').live('click', function(){
									var answer = confirm("<?php _e('Delete this rule?', 'woothemes'); ?>");
									if (answer) {
										jQuery('input', jQuery(this).parent()).val('');
										jQuery(this).parent().hide();
									}
									return false;
								});
							});						
						/* ]]> */
						</script>
		                <?php
		            break;
		            case "shipping_options" :
		            
		            	foreach (woocommerce_shipping::$shipping_methods as $method) :
		            		
		            		$method->admin_options();
		            		
		            	endforeach;  
		            	          
		            break;
		            case "gateway_options" :
		            
		            	foreach (woocommerce_payment_gateways::payment_gateways() as $gateway) :
		            		
		            		$gateway->admin_options();
		            		
		            	endforeach; 
		            	           
		            break;
		        endswitch;
		    endforeach;
		?>
		<p class="submit"><input name="save" type="submit" value="<?php _e('Save changes', 'woothemes') ?>" /></p>
	</div>
	<script type="text/javascript">
	jQuery(function() {
	    // Tabs
		jQuery('ul.tabs').show();
		jQuery('ul.tabs li:first').addClass('active');
		jQuery('div.panel:not(div.panel:first)').hide();
		jQuery('ul.tabs a').click(function(){
			jQuery('ul.tabs li').removeClass('active');
			jQuery(this).parent().addClass('active');
			jQuery('div.panel').hide();
			jQuery( jQuery(this).attr('href') ).show();
			
			jQuery.cookie('woocommerce_settings_tab_index', jQuery(this).parent().index('ul.tabs li'))

			return false;
		});
		
		<?php if (isset($_COOKIE['woocommerce_settings_tab_index']) && $_COOKIE['woocommerce_settings_tab_index'] > 0) : ?>
			
			jQuery('ul.tabs li:eq(<?php echo $_COOKIE['woocommerce_settings_tab_index']; ?>) a').click();
			
		<?php endif; ?>
		
		// Countries
		jQuery('select#woocommerce_allowed_countries').change(function(){
			if (jQuery(this).val()=="specific") {
				jQuery(this).parent().parent().next('tr.multi_select_countries').show();
			} else {
				jQuery(this).parent().parent().next('tr.multi_select_countries').hide();
			}
		}).change();
		
	});
	</script>
	<?php
}


/**
 * Settings page
 * 
 * Handles the display of the settings page in admin.
 */
function woocommerce_settings() {
    global $options_settings;
    woocommerce_update_options($options_settings);
	?>
	<script type="text/javascript" src="<?php echo woocommerce::plugin_url(); ?>/assets/js/easyTooltip.js"></script>
	<div class="wrap woocommerce">
        <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br/></div>
		<h2><?php _e('General Settings', 'woothemes'); ?></h2>
		<form method="post" id="mainform" action="">
	        <?php woocommerce_admin_fields($options_settings); ?>
	        <input name="submitted" type="hidden" value="yes" />
		</form>
	</div>
	<?php
}
