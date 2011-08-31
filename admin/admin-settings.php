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

	array( 'name' => 'General Options', 'type' => 'title', 'desc' 		=> '' ),

	array(  
		'name' => __('Base Country/Region', 'woothemes'),
		'desc' 		=> __('This is the base country for your business. Tax rates will be based on this country.', 'woothemes'),
		'id' 		=> 'woocommerce_default_country',
		'css' 		=> 'min-width:175px;',
		'std' 		=> 'GB',
		'type' 		=> 'single_select_country'
	),
	
	array(  
		'name' => __('Allowed Countries', 'woothemes'),
		'desc' 		=> __('These are countries that you are willing to ship to.', 'woothemes'),
		'id' 		=> 'woocommerce_allowed_countries',
		'css' 		=> 'min-width:175px;',
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
		'id' 		=> 'woocommerce_specific_allowed_countries',
		'css' 		=> '',
		'std' 		=> '',
		'type' 		=> 'multi_select_countries'
	),
	
	array(  
		'name' => __('Guest checkout', 'woothemes'),
		'desc' 		=> __('Allow guest users to checkout without an account', 'woothemes'),
		'id' 		=> 'woocommerce_enable_guest_checkout',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'	
	),
	
	array(  
		'name' => __('Force SSL', 'woothemes'),
		'desc' 		=> __('Force SSL on the checkout for added security (SSL Certificate required).', 'woothemes'),
		'id' 		=> 'woocommerce_force_ssl_checkout',
		'std' 		=> 'no',
		'type' 		=> 'checkbox'
	),
	
	array(  
		'name' => __('ShareThis Publisher ID', 'woothemes'),
		'desc' 		=> __("Enter your <a href='http://sharethis.com/account/'>ShareThis publisher ID</a> to show ShareThis on product pages.", 'woothemes'),
		'id' 		=> 'woocommerce_sharethis',
		'css' 		=> 'width:300px;',
		'type' 		=> 'text',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('WooCommerce CSS', 'woothemes'),
		'desc' 		=> __('Enable WooCommerce frontend CSS styles', 'woothemes'),
		'id' 		=> 'woocommerce_frontend_css',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),
	
	array(  
		'name' => __('Demo store', 'woothemes'),
		'desc' 		=> __('Enable the "Demo Store" notice on your site', 'woothemes'),
		'id' 		=> 'woocommerce_demo_store',
		'std' 		=> 'no',
		'type' 		=> 'checkbox'
	),
	
	array( 'type' => 'sectionend'),
	
	array( 'type' => 'tabend'),
	
	array( 'type' => 'tab', 'tabname' => __('Pages', 'woothemes') ),
	
	array( 'name' => 'Page setup', 'type' => 'title', 'desc' 		=> '' ),
	
	array(  
		'name' => __('Shop Base Page', 'woothemes'),
		'desc' 		=> sprintf( __("This sets the base page of your shop. IMPORTANT: You must <a target='_blank' href='%s'>re-save your permalinks</a> for this change to take effect.", 'woothemes'), 'options-permalink.php' ),
		'id' 		=> 'woocommerce_shop_page_id',
		'css' 		=> 'min-width:175px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Prepend base page', 'woothemes'),
		'desc' 		=> __('Prepend shop categories/tags with shop base page', 'woothemes'),
		'id' 		=> 'woocommerce_prepend_shop_page_to_urls',
		'std' 		=> 'no',
		'type' 		=> 'checkbox',
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
	
	array( 'type' => 'sectionend'),
	
	array( 'name' => 'Shop pages', 'type' => 'title', 'desc' 		=> 'The following pages need selecting so that WooCommerce knows which are which. These pages should have been created upon installation of the plugin.' ),
	
	array(  
		'name' => __('Cart Page', 'woothemes'),
		'desc' 		=> __('Page contents: [woocommerce_cart]', 'woothemes'),
		'id' 		=> 'woocommerce_cart_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Checkout Page', 'woothemes'),
		'desc' 		=> __('Page contents: [woocommerce_checkout]', 'woothemes'),
		'id' 		=> 'woocommerce_checkout_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Pay Page', 'woothemes'),
		'desc' 		=> __('Page contents: [woocommerce_pay] Parent: "Checkout"', 'woothemes'),
		'id' 		=> 'woocommerce_pay_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Thanks Page', 'woothemes'),
		'desc' 		=> __('Page contents: [woocommerce_thankyou] Parent: "Checkout"', 'woothemes'),
		'id' 		=> 'woocommerce_thanks_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('My Account Page', 'woothemes'),
		'desc' 		=> __('Page contents: [woocommerce_my_account]', 'woothemes'),
		'id' 		=> 'woocommerce_myaccount_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Edit Address Page', 'woothemes'),
		'desc' 		=> __('Page contents: [woocommerce_edit_address] Parent: "My Account"', 'woothemes'),
		'id' 		=> 'woocommerce_edit_address_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('View Order Page', 'woothemes'),
		'desc' 		=> __('Page contents: [woocommerce_view_order] Parent: "My Account"', 'woothemes'),
		'id' 		=> 'woocommerce_view_order_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),
	
	array(  
		'name' => __('Change Password Page', 'woothemes'),
		'desc' 		=> __('Page contents: [woocommerce_change_password] Parent: "My Account"', 'woothemes'),
		'id' 		=> 'woocommerce_change_password_page_id',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'single_select_page',
		'std' 		=> ''
	),	
	
	array( 'type' => 'sectionend'),
	
	array( 'type' => 'tabend'),
	
	array( 'type' 		=> 'tab', 'tabname' => __('Catalog', 'woothemes') ),
	
	array(	'name' => __('Catalog Options', 'woothemes'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),

	array(  
		'name' => __('Product fields', 'woothemes'),
		'desc' 		=> __('Enable the SKU field for products', 'woothemes'),
		'id' 		=> 'woocommerce_enable_sku',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'start'
	),
	
	array(  
		'desc' 		=> __('Enable the weight field for products', 'woothemes'),
		'id' 		=> 'woocommerce_enable_weight',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup'		=> 'end'
	),
	
	array(  
		'name' => __('Weight Unit', 'woothemes'),
		'desc' 		=> __("This controls what unit you will define weights in.", 'woothemes'),
		'id' 		=> 'woocommerce_weight_unit',
		'css' 		=> 'min-width:175px;',
		'std' 		=> 'GBP',
		'type' 		=> 'select',
		'options' => array( 
			'kg' => __('kg', 'woothemes'),
			'lbs' => __('lbs', 'woothemes')
		)
	),
	
	array(  
		'name' => __('Cart redirect', 'woothemes'),
		'desc' 		=> __('Redirect to cart after adding a product to the cart (on single product pages)', 'woothemes'),
		'id' 		=> 'woocommerce_cart_redirect_after_add',
		'std' 		=> 'no',
		'type' 		=> 'checkbox'
	),
	
	array( 'type' => 'sectionend'),
	
	array(	'name' => __('Image Options', 'woothemes'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),
	
	array(  
		'name' => __('Catalog images', 'woothemes'),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_catalog_image',
		'css' 		=> '',
		'type' 		=> 'image_width',
		'std' 		=> '150'
	),

	array(  
		'name' => __('Single product images', 'woothemes'),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_single_image',
		'css' 		=> '',
		'type' 		=> 'image_width',
		'std' 		=> '300'
	),
	
	array(  
		'name' => __('Thumbnail images', 'woothemes'),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_thumbnail_image',
		'css' 		=> '',
		'type' 		=> 'image_width',
		'std' 		=> '90'
	),
	
	array( 'type' => 'sectionend'),
	
	array(	'name' => __('Pricing Options', 'woothemes'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),
	
	array(  
		'name' => __('Currency', 'woothemes'),
		'desc' 		=> sprintf( __("This controls what currency prices are listed at in the catalog, and which currency PayPal, and other gateways, will take payments in. See the list of supported <a target='_new' href='%s'>PayPal currencies</a>.", 'woothemes'), 'https://www.paypal.com/cgi-bin/webscr?cmd=p/sell/mc/mc_intro-outside' ),
		'tip' 		=> '',
		'id' 		=> 'woocommerce_currency',
		'css' 		=> 'min-width:175px;',
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
		'css' 		=> 'min-width:175px;',
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
	
	array( 'type' => 'sectionend'),

	array(	'name' => __('Inventory Options', 'woothemes'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),
	
	array(  
		'name' => __('Manage stock', 'woothemes'),
		'desc' 		=> __('Enable stock management', 'woothemes'),
		'id' 		=> 'woocommerce_manage_stock',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),
	
	array(  
		'name' => __('Notifications', 'woothemes'),
		'desc' 		=> __('Enable low stock notifications', 'woothemes'),
		'id' 		=> 'woocommerce_notify_low_stock',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup' => 'start'
	),
	
	array(  
		'desc' 		=> __('Enable out of stock notifications', 'woothemes'),
		'id' 		=> 'woocommerce_notify_no_stock',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox',
		'checkboxgroup' => 'end'
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
		'name' => __('Out of stock threshold', 'woothemes'),
		'desc' 		=> '',
		'tip' 		=> '',
		'id' 		=> 'woocommerce_notify_no_stock_amount',
		'css' 		=> 'min-width:50px;',
		'type' 		=> 'text',
		'std' 		=> '0'
	),
	
	array(  
		'name' => __('Out of stock visibility', 'woothemes'),
		'desc' 		=> __('Hide out of stock items from the catalog', 'woothemes'),
		'id' 		=> 'woocommerce_hide_out_of_stock_items',
		'std' 		=> 'no',
		'type' 		=> 'checkbox'
	),
	
	array( 'type' => 'sectionend'),
	
	array( 'type' => 'tabend'),
	
	array( 'type' 		=> 'tab', 'tabname' => __('Shipping', 'woothemes') ),
	
	array(	'name' => __('Shipping Options', 'woothemes'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),
	
	array(  
		'name' 		=> __('Calculate shipping', 'woothemes'),
		'desc' 		=> __('Enable shipping/shipping calculations', 'woothemes'),
		'id' 		=> 'woocommerce_calc_shipping',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),
	
	array(  
		'name' 		=> __('Shipping calculator', 'woothemes'),
		'desc' 		=> __('Enable the shipping calculator on the cart page', 'woothemes'),
		'id' 		=> 'woocommerce_enable_shipping_calc',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),
	
	array(  
		'name' 		=> __('Ship to billing', 'woothemes'),
		'desc' 		=> __('Only ship to the users billing address', 'woothemes'),
		'id' 		=> 'woocommerce_ship_to_billing_address_only',
		'std' 		=> 'no',
		'type' 		=> 'checkbox'
	),
	
	array( 'type' => 'sectionend'),
	
	array( 'type' => 'tabend'),
	
	array( 'type' 		=> 'tab', 'tabname' => __('Shipping Methods', 'woothemes') ),
	
	array( 'type' => 'shipping_options'),
	
	array( 'type' => 'sectionend'),

	array( 'type' => 'tabend'),
	
	array( 'type' 		=> 'tab', 'tabname' => __('Tax', 'woothemes') ),
	
	array(	'name' => __('Tax Options', 'woothemes'), 'type' 		=> 'title','desc' 		=> '', 'id' 		=> '' ),
	
	array(  
		'name' => __('Calculate Taxes', 'woothemes'),
		'desc' 		=> __('Enable taxes and tax calculations', 'woothemes'),
		'id' 		=> 'woocommerce_calc_taxes',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
	),
	
	array(  
		'name' => __('Prices inclusive of tax', 'woothemes'),
		'desc' 		=> __('Catalog Prices include tax', 'woothemes'),
		'id' 		=> 'woocommerce_prices_include_tax',
		'std' 		=> 'yes',
		'type' 		=> 'checkbox'
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
	
	array( 'type' => 'sectionend'),

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
    
    	$nonce = $_REQUEST['_wpnonce'];
		if (!wp_verify_nonce($nonce, 'woocommerce-settings') ) die( __('Action failed. Please refresh the page and retry.', 'woothemes') ); 
    
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
						
						$rate = number_format(woocommerce_clean($tax_rate[$i]), 4);
						$class = woocommerce_clean($tax_classes[$i]);
						
						if (isset($tax_shipping[$i]) && $tax_shipping[$i]) $shipping = 'yes'; else $shipping = 'no';
						
						// Handle countries
						$counties_array = array();
						$countries = $tax_countries[$i];
						if ($countries) foreach ($countries as $country) :
							
							$country = woocommerce_clean($country);
							$state = '*';
							
							if (strstr($country, ':')) :
								$cr = explode(':', $country);
								$country = current($cr);
								$state = end($cr);
							endif;
						
							$counties_array[$country][] = $state;
							
						endforeach;
						
						$tax_rates[] = array(
							'countries' => $counties_array,
							'rate' => $rate,
							'shipping' => $shipping,
							'class' => $class
						); 
						
					endif;

				endfor;
				
				update_option($value['id'], $tax_rates);
			
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
	            
	        elseif (isset($value['type']) && $value['type']=='checkbox') :
	            
	            if(isset($value['id']) && isset($_POST[$value['id']])) {
	            	update_option($value['id'], 'yes');
	            } else {
	                update_option($value['id'], 'no');
	            }
	            
	        elseif (isset($value['type']) && $value['type']=='image_width') :
	            	
	            if(isset($value['id'])) {
	            	update_option($value['id'].'_width', (int) woocommerce_clean($_POST[$value['id'].'_width']));
	            	update_option($value['id'].'_height', (int) woocommerce_clean($_POST[$value['id'].'_height']));
	            } else {
	                update_option($value['id'].'_width', $value['std']);
	            	update_option($value['id'].'_height', $value['std']);
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
        
        wp_redirect( add_query_arg('saved', 'true', admin_url('admin.php?page=woocommerce') ));
    }
}

/**
 * Admin fields
 * 
 * Loops though the woocommerce options array and outputs each field.
 */
function woocommerce_admin_fields($options) {
	?>
	<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br></div><h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
		<?php
		$counter = 1;
	    foreach ($options as $value) {
			if ( 'tab' == $value['type'] ) :
	            echo '<a href="#'.$value['type'].$counter.'" class="nav-tab">'.$value['tabname'].'</a>';
	            $counter++;
			endif;
	    }
		?>
	</h2>
	<?php
	    $counter = 1;
	    foreach ($options as $value) :
	        switch($value['type']) :
	            case 'tab':
	                echo '<div id="'.$value['type'].$counter.'" class="panel">';
	            break;
	            case 'title':
	            	if (isset($value['name']) && $value['name']) echo '<h3>'.$value['name'].'</h3>'; 
	            	if (isset($value['desc']) && $value['desc']) echo wpautop(wptexturize($value['desc']));
	            	echo '<table class="form-table">'. "\n\n";
	            break;
	            case 'sectionend':
	            	echo '</table>';
	            break;
	            case 'text':
	            	?><tr valign="top">
						<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
	                    <td class="forminp"><input name="<?php echo $value['id'] ?>" id="<?php echo $value['id'] ?>" type="<?php echo $value['type'] ?>" style="<?php echo $value['css'] ?>" value="<?php if ( get_option( $value['id']) !== false && get_option( $value['id']) !== null ) echo  stripslashes(get_option($value['id'])); else echo $value['std'] ?>" /> <span class="description"><?php echo $value['desc'] ?></span></td>
	                </tr><?php
	            break;
	            case 'image_width' :
	            	?><tr valign="top">
						<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
	                    <td class="forminp"><?php _e('Width'); ?> <input name="<?php echo $value['id'] ?>_width" id="<?php echo $value['id'] ?>_width" type="text" size="3" value="<?php if ( get_option( $value['id'].'_width') ) echo stripslashes(get_option($value['id'].'_width')); else echo $value['std'] ?>" /> <?php _e('Height'); ?> <input name="<?php echo $value['id'] ?>_height" id="<?php echo $value['id'] ?>_height" type="text" size="3"" value="<?php if ( get_option( $value['id'].'_height') ) echo stripslashes(get_option($value['id'].'_height')); else echo $value['std'] ?>" /> <span class="description"><?php echo $value['desc'] ?></span></td>
	                </tr><?php
	            break;
	            case 'select':
	            	?><tr valign="top">
						<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
	                    <td class="forminp"><select name="<?php echo $value['id'] ?>" id="<?php echo $value['id'] ?>" style="<?php echo $value['css'] ?>">
	                        <?php
	                        foreach ($value['options'] as $key => $val) {
	                        ?>
	                            <option value="<?php echo $key ?>" <?php if (get_option($value['id']) == $key) { ?> selected="selected" <?php } ?>><?php echo ucfirst($val) ?></option>
	                        <?php
	                        }
	                        ?>
	                       </select> <span class="description"><?php echo $value['desc'] ?></span>
	                    </td>
	                </tr><?php
	            break;
	            case 'checkbox' :
	            
	            	if (!isset($value['checkboxgroup']) || (isset($value['checkboxgroup']) && $value['checkboxgroup']=='start')) :
	            		?>
	            		<tr valign="top">
						<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
						<td class="forminp">
						<?php
	            	endif;
	            	
	            	?>
		            <fieldset><legend class="screen-reader-text"><span><?php echo $value['name'] ?></span></legend>
						<label for="<?php echo $value['id'] ?>">
						<input name="<?php echo $value['id'] ?>" id="<?php echo $value['id'] ?>" type="checkbox" value="1" <?php checked(get_option($value['id']), 'yes'); ?> />
						<?php echo $value['desc'] ?></label><br>
					</fieldset>
					<?php
					
					if (!isset($value['checkboxgroup']) || (isset($value['checkboxgroup']) && $value['checkboxgroup']=='end')) :
						?>
							</td>
						</tr>
						<?php
					endif;
					
	            break;
	            case 'textarea':
	            	?><tr valign="top">
						<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
	                    <td class="forminp">
	                        <textarea <?php if ( isset($value['args']) ) echo $value['args'] . ' '; ?>name="<?php echo $value['id'] ?>" id="<?php echo $value['id'] ?>" style="<?php echo $value['css'] ?>"><?php if (get_option($value['id'])) echo stripslashes(get_option($value['id'])); else echo $value['std']; ?></textarea> <span class="description"><?php echo $value['desc'] ?></span>
	                    </td>
	                </tr><?php
	            break;
	            case 'tabend':
					echo '</div>';
	                $counter++;
	            break;
	            case 'single_select_page' :
	            	$page_setting = (int) get_option($value['id']);
	            	
	            	$args = array( 'name'	=> $value['id'],
	            				   'id'		=> $value['id']. '" style="width: 200px;',
	            				   'sort_column' 	=> 'menu_order',
	            				   'sort_order'		=> 'ASC',
	            				   'selected'		=> $page_setting);
	            	
	            	if( isset($value['args']) ) $args = wp_parse_args($value['args'], $args);
	            	
	            	?><tr valign="top" class="single_select_page">
	                    <th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
	                    <td class="forminp">
				        	<?php wp_dropdown_pages($args); ?> <span class="description"><?php echo $value['desc'] ?></span>        
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
	            	?><tr valign="top" class="multi_select_countries">
	                    <th scope="rpw" class="titledesc"><?php echo $value['name'] ?></th>
	                    <td class="forminp"><select name="<?php echo $value['id'] ?>" title="Country" style="width: 175px;">	
				        	<?php echo woocommerce_countries::country_dropdown_options($country, $state); ?>          
				        </select> <span class="description"><?php echo $value['desc'] ?></span>
	               		</td>
	               	</tr><?php	
	            break;
	            case 'multi_select_countries' :
	            	$countries = woocommerce_countries::$countries;
	            	asort($countries);
	            	$selections = (array) get_option($value['id']);
	            	?><tr valign="top" class="multi_select_countries">
						<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
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
	            case 'tax_rates' :
	            	$_tax = new woocommerce_tax();
	            	$tax_classes = $_tax->get_tax_classes();
	            	$tax_rates = get_option('woocommerce_tax_rates');
	            	
	            	?><tr valign="top">
						<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
	                    <td class="forminp" id="tax_rates">
	                    	<div class="taxrows">
								
								<?php $i = -1; if ($tax_rates && is_array($tax_rates) && sizeof($tax_rates)>0) foreach( $tax_rates as $rate ) : $i++; ?>
								<div class="taxrow">
		               				<select name="tax_country[<?php echo $i; ?>][]" title="Country" class="country_multiselect" size="10" multiple="multiple">
					                   <?php echo woocommerce_countries::country_multiselect_options( $rate['countries'] ); ?>
					                </select>
					                <select name="tax_class[<?php echo $i; ?>]" title="Tax Class">
						                <option value=""><?php _e('Standard Rate', 'woothemes'); ?></option>
						                <?php
				                    		if ($tax_classes) foreach ($tax_classes as $class) :
						                        echo '<option value="'.sanitize_title($class).'"';
						                        selected($rate['class'], sanitize_title($class));
						                        echo '>'.$class.'</option>';
						                    endforeach;
					                    ?>
				                    </select>
				                    <input type="text" class="text" value="<?php echo $rate['rate']; ?>" name="tax_rate[<?php echo $i; ?>]" title="<?php _e('Rate', 'woothemes'); ?>" placeholder="<?php _e('Rate', 'woothemes'); ?>" maxlength="8" />% 
				                    <label class="checkbox"><input type="checkbox" name="tax_shipping[<?php echo $i; ?>]" <?php  if (isset($rate['shipping'])) checked($rate['shipping'], 'yes'); ?> /> <?php _e('Apply to shipping', 'woothemes'); ?></label><a href="#" class="remove button">&times;</a>
	               				</div>
	               				<?php endforeach; ?>
	               				
	                        </div>
	                        <p><a href="#" class="add button"><?php _e('+ Add Tax Rule', 'woothemes'); ?></a></p>
	                    </td>
	                </tr>
	                <script type="text/javascript">
					/* <![CDATA[ */
						jQuery(function() {
						
							jQuery(".country_multiselect").multiselect({
								noneSelectedText: '<?php _e('Select countries/states', 'woothemes'); ?>',
								selectedList: 4
							});
						
						
							jQuery('#tax_rates a.add').live('click', function(){
								var size = jQuery('.taxrows .taxrow').size();
								
								// Add the row
								jQuery('<div class="taxrow">\
		               				<select name="tax_country[' + size + '][]" title="Country" class="country_multiselect" size="10" multiple="multiple"><?php echo woocommerce_countries::country_multiselect_options('',true); ?></select>\
					                <select name="tax_class[' + size + ']" title="Tax Class"><option value=""><?php _e('Standard Rate', 'woothemes'); ?></option><?php
				                    		if ($tax_classes) foreach ($tax_classes as $class) :
						                        echo '<option value="'.sanitize_title($class).'">'.$class.'</option>';
						                    endforeach;
					                ?></select>\
				                    <input type="text" class="text" name="tax_rate[' + size + ']" title="<?php _e('Rate', 'woothemes'); ?>" placeholder="<?php _e('Rate', 'woothemes'); ?>" maxlength="8" />% \
				                    <label class="checkbox"><input type="checkbox" name="tax_shipping[' + size + ']" checked="checked" /> <?php _e('Apply to shipping', 'woothemes'); ?></label><a href="#" class="remove button">&times;</a>\
	               				</div>').appendTo('#tax_rates div.taxrows');
	               				
	               				// Multiselect
	               				jQuery(".country_multiselect").multiselect({
									noneSelectedText: '<?php _e('Select countries/states', 'woothemes'); ?>',
									selectedList: 4
								});
	               				
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
	            
	            	$links = array();
	            	
	            	foreach (woocommerce_shipping::$shipping_methods as $method) :
	            		
	            		$title = ($method->title) ? ucwords($method->title) : ucwords($method->id);
	            		
	            		$links[] = '<a href="#shipping-'.$method->id.'">'.$title.'</a>';
					
					endforeach;
					
					echo '<div class="subsubsub_section"><ul class="subsubsub"><li>' . implode(' | </li><li>', $links) . '</li></ul><br class="clear" />';
	            
	            	foreach (woocommerce_shipping::$shipping_methods as $method) :
	            		
	            		echo '<div class="section" id="shipping-'.$method->id.'">';
	            		$method->admin_options();
	            		echo '</div>';
	            		
	            	endforeach; 
	            	
	            	echo '</div>';
	            	          
	            break;
	            case "gateway_options" :
	            	
	            	$links = array();
	            	
	            	foreach (woocommerce_payment_gateways::payment_gateways() as $gateway) :
	            		
	            		$title = ($gateway->title) ? ucwords($gateway->title) : ucwords($gateway->id);
	            		
	            		$links[] = '<a href="#gateway-'.$gateway->id.'">'.$title.'</a>';
					
					endforeach;
					
					echo '<div class="subsubsub_section"><ul class="subsubsub"><li>' . implode(' | </li><li>', $links) . '</li></ul><br class="clear" />';
	            
	            	foreach (woocommerce_payment_gateways::payment_gateways() as $gateway) :
	            		
	            		echo '<div class="section" id="gateway-'.$gateway->id.'">';
	            		$gateway->admin_options();
	            		echo '</div>';
	            		
	            	endforeach; 
	            	
	            	echo '</div>';
	            	           
	            break;
	        endswitch;
	    endforeach;
	?>
	<p class="submit"><input name="save" class="button-primary" type="submit" value="<?php _e('Save changes', 'woothemes') ?>" /></p>
	<script type="text/javascript">
	jQuery(function() {
	
	    // Tabs
		jQuery('.woo-nav-tab-wrapper a:first').addClass('nav-tab-active');
		jQuery('div.panel:not(div.panel:first)').hide();
		jQuery('.woo-nav-tab-wrapper a').click(function(){
			jQuery('.woo-nav-tab-wrapper a').removeClass('nav-tab-active');
			jQuery(this).addClass('nav-tab-active');
			jQuery('div.panel').hide();
			jQuery( jQuery(this).attr('href') ).show();
			
			jQuery.cookie('woocommerce_settings_tab_index', jQuery(this).index('.woo-nav-tab-wrapper a'))

			return false;
		});
		
		<?php if (isset($_COOKIE['woocommerce_settings_tab_index']) && $_COOKIE['woocommerce_settings_tab_index'] > 0) : ?>
			jQuery('.woo-nav-tab-wrapper a:eq(<?php echo $_COOKIE['woocommerce_settings_tab_index']; ?>)').click();
		<?php endif; ?>
		
		// Subsubsub tabs
		jQuery('ul.subsubsub li a').click(function(){
			jQuery('a', jQuery(this).closest('ul.subsubsub')).removeClass('current');
			jQuery(this).addClass('current');
			jQuery('.section', jQuery(this).closest('.subsubsub_section')).hide();
			jQuery( jQuery(this).attr('href') ).show();
			return false;
		});
		jQuery('ul.subsubsub').each(function(){
			jQuery('li a:eq(0)', jQuery(this)).click();
		});
		
		// Options
		jQuery('tr.hidden_unless_enabled').hide();
		jQuery('tr.option_enabled input').change(function(){
			if (jQuery(this).is(':checked')) {
				jQuery('tr.hidden_unless_enabled', jQuery(this).closest( 'table' )).show();
			} else {
				jQuery('tr.hidden_unless_enabled', jQuery(this).closest( 'table' )).hide();
			}
		});
		jQuery('tr.option_enabled input').change();
		
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
    woocommerce_update_options( $options_settings );
    if (isset($_GET['saved']) && $_GET['saved']) echo '<div id="message" class="updated fade"><p><strong>'.__('Your settings have been saved.', 'woothemes').'</strong></p></div>';
    ?>
	<div class="wrap woocommerce">
		<form method="post" id="mainform" action="">
			<?php wp_nonce_field('woocommerce-settings', '_wpnonce', true, true); ?>
	        <?php woocommerce_admin_fields($options_settings); ?>
	        <input name="submitted" type="hidden" value="yes" />
		</form>
	</div>
	<?php
}