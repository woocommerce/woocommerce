<?php
/**
 * Functions used for the showing help/links to WooCommerce resources in admin
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     1.6.4
 */


/**
 * Help Tab Content
 *
 * Shows some text about WooCommerce and links to docs.
 *
 * @access public
 * @return void
 */
function woocommerce_admin_help_tab_content() {
	$screen = get_current_screen();

	$screen->add_help_tab( array(
	    'id'	=> 'woocommerce_overview_tab',
	    'title'	=> __('Overview', 'woocommerce'),
	    'content'	=>

	    	'<p>' . sprintf(__( 'Thank you for using WooCommerce :) Should you need help using or extending WooCommerce please <a href="%s">read the documentation</a>. For further assistance you can use the <a href="%s">community forum</a> or if you have access, <a href="%s">the members forum</a>.', 'woocommerce' ), 'http://www.woothemes.com/woocommerce-docs/', 'http://www.woothemes.com/support-forum/?viewforum=150', 'http://www.woothemes.com/support-forum/') . '</p>' .

	    	'<p>' . __( 'If you are having problems, or to assist us with support, please check the status page to identify any problems with your configuration:', 'woocommerce' ) . '</p>' .

	    	'<p><a href="' . admin_url('admin.php?page=woocommerce_status') . '" class="button">' . __( 'System Status', 'woocommerce' ) . '</a></p>' .

	    	'<p>' . sprintf(__( 'If you come across a bug, or wish to contribute to the project you can also <a href="%s">get involved on GitHub</a>.', 'woocommerce' ), 'https://github.com/woothemes/woocommerce') . '</p>'

	) );

	$screen->add_help_tab( array(
	    'id'	=> 'woocommerce_settings_tab',
	    'title'	=> __('Settings', 'woocommerce'),
	    'content'	=>
	    	'<p>' . __( 'Here you can set up your store and customise it to fit your needs. The sections available from the settings page include:', 'woocommerce' ) . '</p>' .
	    	'<p><strong>' . __( 'General', 'woocommerce' ) . '</strong> - ' . __( 'General settings such as your shop base, currency, and script/styling options which affect features used in your store.', 'woocommerce' ) . '</p>' .
	    	'<p><strong>' . __( 'Pages', 'woocommerce' ) . '</strong> - ' . __( 'This is where important store page are defined. You can also set up other pages (such as a Terms page) here.', 'woocommerce' ) . '</p>' .
	    	'<p><strong>' . __( 'Catalog', 'woocommerce' ) . '</strong> - ' . __( 'Options for how things like price, images and weights appear in your product catalog.', 'woocommerce' ) . '</p>' .
	    	'<p><strong>' . __( 'Inventory', 'woocommerce' ) . '</strong> - ' . __( 'Options concerning stock and stock notices.', 'woocommerce' ) . '</p>' .
	    	'<p><strong>' . __( 'Tax', 'woocommerce' ) . '</strong> - ' . __( 'Options concerning tax, including international and local tax rates.', 'woocommerce' ) . '</p>' .
	    	'<p><strong>' . __( 'Shipping', 'woocommerce' ) . '</strong> - ' . __( 'This is where shipping options are defined, and shipping methods are set up.', 'woocommerce' ) . '</p>' .
	    	'<p><strong>' . __( 'Payment Methods', 'woocommerce' ) . '</strong> - ' . __( 'This is where payment gateway options are defined, and individual payment gateways are set up.', 'woocommerce' ) . '</p>' .
	    	'<p><strong>' . __( 'Emails', 'woocommerce' ) . '</strong> - ' . __( 'Here you can customise the way WooCommerce emails appear.', 'woocommerce' ) . '</p>' .
	    	'<p><strong>' . __( 'Integration', 'woocommerce' ) . '</strong> - ' . __( 'The integration section contains options for third party services which integrate with WooCommerce.', 'woocommerce' ) . '</p>'
	) );

	$screen->add_help_tab( array(
	    'id'	=> 'woocommerce_overview_tab_2',
	    'title'	=> __('Reports', 'woocommerce'),
	    'content'	=>
				'<p>' . __( 'The reports section can be accessed from the left-hand navigation menu. Here you can generate reports for sales and customers.', 'woocommerce' ) . '</p>' .
				'<p><strong>' . __( 'Sales', 'woocommerce' ) . '</strong> - ' . __( 'Reports for sales based on date, top sellers and top earners.', 'woocommerce' ) . '</p>' .
				'<p><strong>' . __( 'Customers', 'woocommerce' ) . '</strong> - ' . __( 'Customer reports, such as signups per day.', 'woocommerce' ) . '</p>' .
				'<p><strong>' . __( 'Stock', 'woocommerce' ) . '</strong> - ' . __( 'Stock reports for low stock and out of stock items.', 'woocommerce' ) . '</p>'
	) );

	$screen->add_help_tab( array(
	     'id'	=> 'woocommerce_overview_tab_3',
	     'title'	=> __('Orders', 'woocommerce'),
	     'content'	=>
				'<p>' . __( 'The orders section can be accessed from the left-hand navigation menu. Here you can view and manage customer orders.', 'woocommerce' ) . '</p>' .
				'<p>' . __( 'Orders can also be added from this section if you want to set them up for a customer manually.', 'woocommerce' ) . '</p>'
	) );

	$screen->add_help_tab( array(
	     'id'	=> 'woocommerce_overview_tab_4',
	     'title'	=> __('Coupons', 'woocommerce'),
	     'content'	=>
				'<p>' . __( 'Coupons can be managed from this section. Once added, customers will be able to enter coupon codes on the cart/checkout page. If a customer uses a coupon code they will be viewable when viewing orders.', 'woocommerce' ) . '</p>'
	) );

	$screen->set_help_sidebar(
		'<p><strong>' . __('For more information:', 'woocommerce') . '</strong></p>' .
		'<p>' . __('<a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a>', 'woocommerce') . '</p>' .
		'<p>' . __('<a href="http://wordpress.org/extend/plugins/woocommerce/" target="_blank">Project on WordPress.org</a>', 'woocommerce') . '</p>' .
		'<p>' . __('<a href="https://github.com/woothemes/woocommerce" target="_blank">Project on Github</a>', 'woocommerce') . '</p>' .
		'<p>' . __('<a href="http://www.woothemes.com/woocommerce-docs/" target="_blank">WooCommerce Docs</a>', 'woocommerce') . '</p>' .
		'<p>' . __('<a href="http://www.woothemes.com/extensions/woocommerce-extensions/" target="_blank">Official Extensions</a>', 'woocommerce') . '</p>' .
		'<p>' . __('<a href="http://www.woothemes.com/themes/woocommerce-themes/" target="_blank">Official Themes</a>', 'woocommerce') . '</p>'
	);
}