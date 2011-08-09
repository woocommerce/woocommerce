<?php
/**
 * options_settings
 * 
 * This variable contains all the options used on the jigpshop settings page
 *
 * @since 		1.0
 * @category 	Admin
 * @usedby 		jigoshop_settings(), jigoshop_default_options()
 */
global $options_settings;

$options_settings = apply_filters('jigoshop_options_settings', array(

	array( 'type' => 'tab', 'tabname' => __('General', 'jigoshop') ),

	array( 'name' => __('General Options', 'jigoshop'), 'type' => 'title', 'desc' 		=> '' ),
	
	array(  
		'name' => __('Demo store','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> __('Enable this option to show a banner at the top of the page stating its a demo store.','jigoshop'),
		'id' 		=> 'jigoshop_demo_store',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'no',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'jigoshop'),
			'no'  => __('No', 'jigoshop')
		)
	),

	array(  
		'name' => __('Enable SKU field','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> __('Turning off the SKU field will give products an SKU of their post id.','jigoshop'),
		'id' 		=> 'jigoshop_enable_sku',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'jigoshop'),
			'no'  => __('No', 'jigoshop')
		)
	),
	
	array(  
		'name' => __('Enable weight field','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'jigoshop_enable_weight',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'jigoshop'),
			'no'  => __('No', 'jigoshop')
		)
	),
	
	array(  
		'name' => __('Weight Unit', 'jigoshop'),
		'desc' 		=> __("This controls what unit you will define weights in.", 'jigoshop'),
		'tip' 		=> '',
		'id' 		=> 'jigoshop_weight_unit',
		'css' 		=> 'min-width:200px;',
		'std' 		=> 'GBP',
		'type' 		=> 'select',
		'options' => array( 
			'kg' => __('kg', 'jigoshop'),
			'lbs' => __('lbs', 'jigoshop')
		)
	),
	
	array(  
		'name' => __('Base Country/Region','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> __('This is the base country for your business. Tax rates will be based on this country.','jigoshop'),
		'id' 		=> 'jigoshop_default_country',
		'css' 		=> '',
		'std' 		=> 'GB',
		'type' 		=> 'single_select_country'
	),
	
	array(  
		'name' => __('Allowed Countries','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> __('These are countries that you are willing to ship to.','jigoshop'),
		'id' 		=> 'jigoshop_allowed_countries',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'all',
		'type' 		=> 'select',
		'options' => array(  
			'all'  => __('All Countries', 'jigoshop'),
			'specific' => __('Specific Countries', 'jigoshop')			
		)
	),
	
	array(  
		'name' => __('Specific Countries','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'jigoshop_specific_allowed_countries',
		'css' 		=> '',
		'std' 		=> '',
		'type' 		=> 'multi_select_countries'
	),
	
	array(  
		'name' => __('Enable guest checkout?','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> __('Without guest checkout, all users will require an account in order to checkout.','jigoshop'),
		'id' 		=> 'jigoshop_enable_guest_checkout',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'jigoshop'),
			'no'  => __('No', 'jigoshop')
		)
	),
	
	array(  
		'name' => __('Force SSL on checkout?','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> __('Forcing SSL is recommended','jigoshop'),
		'id' 		=> 'jigoshop_force_ssl_checkout',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'no',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'jigoshop'),
			'no'  => __('No', 'jigoshop')
		)
	),
	
	array(  
		'name' => __('ShareThis Publisher ID','jigoshop'),
		'desc' 		=> __("Enter your <a href='http://sharethis.com/account/'>ShareThis publisher ID</a> to show ShareThis on product pages.",'jigoshop'),
		'tip' 		=> __('ShareThis is a small social sharing widget for posting links on popular sites such as Twitter and Facebook.','jigoshop'),
		'id' 		=> 'jigoshop_sharethis',
		'css' 		=> 'width:300px;',
		'type' 		=> 'text',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Disable Jigoshop frontend.css','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> __('Useful if you want to disable Jigoshop styles and theme it yourself via your theme.','jigoshop'),
		'id' 		=> 'jigoshop_disable_css',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'no',
		'type' 		=> 'select',
		'options' => array(  
			'no'  => __('No', 'jigoshop'),
			'yes' => __('Yes', 'jigoshop')
		)
	),
	
	array( 'type' => 'tabend'),
	
	array( 'type' => 'tab', 'tabname' => __('Pages', 'jigoshop') ),

	array( 'name' => __('Shop page configuration', 'jigoshop'), 'type' => 'title', 'desc' 		=> '' ),
	
	array(  
		'name' => __('Cart Page','jigoshop'),
		'desc' 		=> __('Your page should contain [jigoshop_cart]','jigoshop'),
		'tip' 		=> '',
		'id' 		=> 'jigoshop_cart_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Checkout Page','jigoshop'),
		'desc' 		=> __('Your page should contain [jigoshop_checkout]','jigoshop'),
		'tip' 		=> '',
		'id' 		=> 'jigoshop_checkout_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Pay Page','jigoshop'),
		'desc' 		=> __('Your page should contain [jigoshop_pay] and usually have "Checkout" as the parent.','jigoshop'),
		'tip' 		=> '',
		'id' 		=> 'jigoshop_pay_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Thanks Page','jigoshop'),
		'desc' 		=> __('Your page should contain [jigoshop_thankyou] and usually have "Checkout" as the parent.','jigoshop'),
		'tip' 		=> '',
		'id' 		=> 'jigoshop_thanks_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('My Account Page','jigoshop'),
		'desc' 		=> __('Your page should contain [jigoshop_my_account]','jigoshop'),
		'tip' 		=> '',
		'id' 		=> 'jigoshop_myaccount_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Edit Address Page','jigoshop'),
		'desc' 		=> __('Your page should contain [jigoshop_edit_address] and usually have "My Account" as the parent.','jigoshop'),
		'tip' 		=> '',
		'id' 		=> 'jigoshop_edit_address_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('View Order Page','jigoshop'),
		'desc' 		=> __('Your page should contain [jigoshop_view_order] and usually have "My Account" as the parent.','jigoshop'),
		'tip' 		=> '',
		'id' 		=> 'jigoshop_view_order_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Change Password Page','jigoshop'),
		'desc' 		=> __('Your page should contain [jigoshop_change_password] and usually have "My Account" as the parent.','jigoshop'),
		'tip' 		=> '',
		'id' 		=> 'jigoshop_change_password_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),	
	
	array( 'type' => 'tabend'),
	
	array( 'type' 		=> 'tab', 'tabname' => __('Catalog', 'jigoshop') ),
	
	array(	'name' => __('Catalog Options', 'jigoshop'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),

	
	array(  
		'name' => __('Products Base Page','jigoshop'),
		'desc' 		=> sprintf( __("IMPORTANT: You must <a target='_blank' href='%s'>re-save your permalinks</a> for this change to take effect.",'jigoshop'), 'options-permalink.php' ),
		'tip' 		=> __('This sets the base page of your shop. You should not change this value once you have launched your site otherwise you risk breaking urls of other sites pointing to yours, etc.','jigoshop'),
		'id' 		=> 'jigoshop_shop_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Prepend shop categories/tags with base page?','jigoshop'),
		'desc' 		=> sprintf( __("IMPORTANT: You must <a target='_blank' href='%s'>re-save your permalinks</a> for this change to take effect.",'jigoshop'), 'options-permalink.php' ),
		'tip' 		=> __('If set to yes, categories will show up as your_base_page/shop_category instead of just shop_category.', 'jigoshop'),
		'id' 		=> 'jigoshop_prepend_shop_page_to_urls',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'no',
		'type' 		=> 'select',
		'options' => array(  
			'no'  => __('No', 'jigoshop'),
			'yes' => __('Yes', 'jigoshop')
		)
	),

	array(  
		'name' => __('Terms page ID', 'jigoshop'),
		'desc' 		=> __('If you define a "Terms" page the customer will be asked if they accept them when checking out.', 'jigoshop'),
		'tip' 		=> '',
		'id' 		=> 'jigoshop_terms_page_id',
		'css' 		=> 'min-width:50px;',
		'std' 		=> '',
		'type' 		=> 'single_select_page',
		'args'		=> 'show_option_none=' . __('None', 'jigoshop'),
	),
	
	array(	'name' => __('Pricing Options', 'jigoshop'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),

	array(  
		'name' => __('Currency', 'jigoshop'),
		'desc' 		=> sprintf( __("This controls what currency prices are listed at in the catalog, and which currency PayPal, and other gateways, will take payments in. See the list of supported <a target='_new' href='%s'>PayPal currencies</a>.", 'jigoshop'), 'https://www.paypal.com/cgi-bin/webscr?cmd=p/sell/mc/mc_intro-outside' ),
		'tip' 		=> '',
		'id' 		=> 'jigoshop_currency',
		'css' 		=> 'min-width:200px;',
		'std' 		=> 'GBP',
		'type' 		=> 'select',
		'options' => apply_filters('jigoshop_currencies', array( 
			'USD' => __('US Dollars (&#36;)', 'jigoshop'),
			'EUR' => __('Euros (&euro;)', 'jigoshop'),
			'GBP' => __('Pounds Sterling (&pound;)', 'jigoshop'),
			'AUD' => __('Australian Dollars (&#36;)', 'jigoshop'),
			'BRL' => __('Brazilian Real (&#36;)', 'jigoshop'),
			'CAD' => __('Canadian Dollars (&#36;)', 'jigoshop'),
			'CZK' => __('Czech Koruna', 'jigoshop'),
			'DKK' => __('Danish Krone', 'jigoshop'),
			'HKD' => __('Hong Kong Dollar (&#36;)', 'jigoshop'),
			'HUF' => __('Hungarian Forint', 'jigoshop'),
			'ILS' => __('Israeli Shekel', 'jigoshop'),
			'JPY' => __('Japanese Yen (&yen;)', 'jigoshop'),
			'MYR' => __('Malaysian Ringgits', 'jigoshop'),
			'MXN' => __('Mexican Peso (&#36;)', 'jigoshop'),
			'NZD' => __('New Zealand Dollar (&#36;)', 'jigoshop'),
			'NOK' => __('Norwegian Krone', 'jigoshop'),
			'PHP' => __('Philippine Pesos', 'jigoshop'),
			'PLN' => __('Polish Zloty', 'jigoshop'),
			'SGD' => __('Singapore Dollar (&#36;)', 'jigoshop'),
			'SEK' => __('Swedish Krona', 'jigoshop'),
			'CHF' => __('Swiss Franc', 'jigoshop'),
			'TWD' => __('Taiwan New Dollars', 'jigoshop'),
			'THB' => __('Thai Baht', 'jigoshop') 
			)
		)
	),
	
	array(  
		'name' => __('Currency Position', 'jigoshop'),
		'desc' 		=> __("This controls the position of the currency symbol.", 'jigoshop'),
		'tip' 		=> '',
		'id' 		=> 'jigoshop_currency_pos',
		'css' 		=> 'min-width:200px;',
		'std' 		=> 'left',
		'type' 		=> 'select',
		'options' => array( 
			'left' => __('Left', 'jigoshop'),
			'right' => __('Right', 'jigoshop'),
			'left_space' => __('Left (with space)', 'jigoshop'),
			'right_space' => __('Right (with space)', 'jigoshop')
		)
	),
	
	array(  
		'name' => __('Thousand separator', 'jigoshop'),
		'desc' 		=> __('This sets the thousand separator of displayed prices.', 'jigoshop'),
		'tip' 		=> '',
		'id' 		=> 'jigoshop_price_thousand_sep',
		'css' 		=> 'width:30px;',
		'std' 		=> ',',
		'type' 		=> 'text',
	),
	
	array(  
		'name' => __('Decimal separator', 'jigoshop'),
		'desc' 		=> __('This sets the decimal separator of displayed prices.', 'jigoshop'),
		'tip' 		=> '',
		'id' 		=> 'jigoshop_price_decimal_sep',
		'css' 		=> 'width:30px;',
		'std' 		=> '.',
		'type' 		=> 'text',
	),
	
	array(  
		'name' => __('Number of decimals', 'jigoshop'),
		'desc' 		=> __('This sets the number of decimal points shown in displayed prices.', 'jigoshop'),
		'tip' 		=> '',
		'id' 		=> 'jigoshop_price_num_decimals',
		'css' 		=> 'width:30px;',
		'std' 		=> '2',
		'type' 		=> 'text',
	),
	
	array( 'type' => 'tabend'),
	
	array( 'type' => 'tab', 'tabname' => __('Coupons', 'jigoshop') ),

	array( 'name' => __('Coupon Codes', 'jigoshop'), 'type' => 'title', 'desc' 		=> '' ),
	
	array(  
		'name' => __('Coupons','jigoshop'),
		'desc' 		=> 'All fields are required.',
		'tip' 		=> 'Coupons allow you to give customers special offers and discounts. Leave product IDs blank to apply to all products/items in the cart.',
		'id' 		=> 'jigoshop_coupons',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'coupons',
		'std' 		=> ''
	),
	
	array( 'type' => 'tabend'),
	
	array( 'type' 		=> 'tab', 'tabname' => __('Inventory', 'jigoshop') ),
	
	array(	'name' => __('Inventory Options', 'jigoshop'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),
	
	array(  
		'name' => __('Manage stock?','jigoshop'),
		'desc' 		=> __('If you are not managing stock, turn it off here to disable it in admin and on the front-end.','jigoshop'),
		'tip' 		=> __('You can manage stock on a per-item basis if you leave this option on.', 'jigoshop'),
		'id' 		=> 'jigoshop_manage_stock',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'jigoshop'),
			'no'  => __('No', 'jigoshop')
		)
	),
	
	array(  
		'name' => __('Low stock notification','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> __('Set the minimum threshold for this below.', 'jigoshop'),
		'id' 		=> 'jigoshop_notify_low_stock',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'jigoshop'),
			'no'  => __('No', 'jigoshop')
		)
	),
	
	array(  
		'name' => __('Low stock threshold','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'jigoshop_notify_low_stock_amount',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'text',
		'std' 		=> '2'
	),
	
	array(  
		'name' => __('Out-of-stock notification','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> __('Set the minimum threshold for this below.', 'jigoshop'),
		'id' 		=> 'jigoshop_notify_no_stock',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'jigoshop'),
			'no'  => __('No', 'jigoshop')
		)
	),
	
	array(  
		'name' => __('Out of stock threshold','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'jigoshop_notify_no_stock_amount',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'text',
		'std' 		=> '0'
	),
	
	array( 'type' => 'tabend'),
	
	array( 'type' 		=> 'tab', 'tabname' => __('Shipping', 'jigoshop') ),
	
	array(	'name' => __('Shipping Options', 'jigoshop'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),
	
	array(  
		'name' => __('Calculate Shipping','jigoshop'),
		'desc' 		=> __('Only set this to no if you are not shipping items, or items have shipping costs included.','jigoshop'),
		'tip' 		=> __('If you are not calculating shipping then you can ignore all other tax options.', 'jigoshop'),
		'id' 		=> 'jigoshop_calc_shipping',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'jigoshop'),
			'no'  => __('No', 'jigoshop')
		)
	),
	
	array(  
		'name' => __('Enable shipping calculator on cart','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'jigoshop_enable_shipping_calc',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'jigoshop'),
			'no'  => __('No', 'jigoshop')
		)
	),
	
	array(  
		'name' => __('Only ship to billing address?','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'jigoshop_ship_to_billing_address_only',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'no',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'jigoshop'),
			'no'  => __('No', 'jigoshop')
		)
	),
	
	array( 'type' => 'shipping_options'),
	
	array( 'type' => 'tabend'),
	
	array( 'type' 		=> 'tab', 'tabname' => __('Tax', 'jigoshop') ),
	
	array(	'name' => __('Tax Options', 'jigoshop'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),
	
	array(  
		'name' => __('Calculate Taxes','jigoshop'),
		'desc' 		=> __('Only set this to no if you are exclusively selling non-taxable items.','jigoshop'),
		'tip' 		=> __('If you are not calculating taxes then you can ignore all other tax options.', 'jigoshop'),
		'id' 		=> 'jigoshop_calc_taxes',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'jigoshop'),
			'no'  => __('No', 'jigoshop')
		)
	),
	
	array(  
		'name' => __('Catalog Prices include tax?','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> __('If prices include tax then tax calculations will work backwards.','jigoshop'),
		'id' 		=> 'jigoshop_prices_include_tax',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'yes',
		'type' 		=> 'select',
		'options' => array(  
			'yes' => __('Yes', 'jigoshop'),
			'no'  => __('No', 'jigoshop')
		)
	),
	
	array(  
		'name' => __('Cart totals display...','jigoshop'),
		'desc' 		=> '',
		'tip' 		=> __('Should the subtotal be shown including or excluding tax on the frontend?','jigoshop'),
		'id' 		=> 'jigoshop_display_totals_tax',
		'css' 		=> 'min-width:100px;',
		'std' 		=> 'excluding',
		'type' 		=> 'select',
		'options' => array(  
			'including' => __('price including tax', 'jigoshop'),
			'excluding'  => __('price excluding tax', 'jigoshop')
		)
	),

	array(  
		'name' => __('Additional Tax classes','jigoshop'),
		'desc' 		=> __('List 1 per line. This is in addition to the default <em>Standard Rate</em>.','jigoshop'),
		'tip' 		=> __('List product and shipping tax classes here, e.g. Zero Tax, Reduced Rate.','jigoshop'),
		'id' 		=> 'jigoshop_tax_classes',
		'css' 		=> 'width:100%; height: 75px;',
		'type' 		=> 'textarea',
		'std' 		=> "Reduced Rate\nZero Rate"
	),
	
	array(  
		'name' => __('Tax rates','jigoshop'),
		'desc' 		=> 'All fields are required.',
		'tip' 		=> 'To avoid rounding errors, insert tax rates with 4 decimal places.',
		'id' 		=> 'jigoshop_tax_rates',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'tax_rates',
		'std' 		=> ''
	),
	
	array( 'type' => 'tabend'),
	
	array( 'type' 		=> 'tab', 'tabname' => __('Payment Gateways', 'jigoshop') ),
	
	array( 'type' => 'gateway_options'),
	
	array( 'type' => 'tabend')

) );
