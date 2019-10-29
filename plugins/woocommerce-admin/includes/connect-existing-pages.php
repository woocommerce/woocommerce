<?php
/**
 * Connect existing WooCommerce pages to WooCommerce Admin.
 *
 * @package Woocommerce Admin
 */

$admin_page_base    = 'admin.php';
$posttype_list_base = 'edit.php';

// WooCommerce > Settings > General (default tab).
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings',
		'screen_id' => 'woocommerce_page_wc-settings-general',
		'title'     => array(
			__( 'Settings', 'woocommerce-admin' ),
			__( 'General', 'woocommerce-admin' ),
		),
		'path'      => add_query_arg( 'page', 'wc-settings', $admin_page_base ),
	)
);

// WooCommerce > Settings > Products > General (default tab).
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-products',
		'parent'    => 'woocommerce-settings',
		'screen_id' => 'woocommerce_page_wc-settings-products',
		'title'     => array(
			__( 'Products', 'woocommerce-admin' ),
			__( 'General', 'woocommerce-admin' ),
		),
		'path'      => add_query_arg(
			array(
				'page' => 'wc-settings',
				'tab'  => 'products',
			),
			$admin_page_base
		),
	)
);

// WooCommerce > Settings > Products > Inventory.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-products-inventory',
		'parent'    => 'woocommerce-settings-products',
		'screen_id' => 'woocommerce_page_wc-settings-products-inventory',
		'title'     => __( 'Inventory', 'woocommerce-admin' ),
	)
);

// WooCommerce > Settings > Products > Downloadable products.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-products-downloadable',
		'parent'    => 'woocommerce-settings-products',
		'screen_id' => 'woocommerce_page_wc-settings-products-downloadable',
		'title'     => __( 'Downloadable products', 'woocommerce-admin' ),
	)
);

// WooCommerce > Settings > Tax
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-tax',
		'parent'    => 'woocommerce-settings',
		'screen_id' => 'woocommerce_page_wc-settings-tax',
		'title'     => array(
			__( 'Tax', 'woocommerce-admin' ),
		),
		'path'      => add_query_arg(
			array(
				'page' => 'wc-settings',
				'tab'  => 'tax',
			),
			$admin_page_base
		),
	)
);

// WooCommerce > Settings > Shipping > Shipping zones (default tab).
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-shipping',
		'parent'    => 'woocommerce-settings',
		'screen_id' => 'woocommerce_page_wc-settings-shipping',
		'title'     => array(
			__( 'Shipping', 'woocommerce-admin' ),
			__( 'Shipping zones', 'woocommerce-admin' ),
		),
		'path'      => add_query_arg(
			array(
				'page' => 'wc-settings',
				'tab'  => 'shipping',
			),
			$admin_page_base
		),
	)
);

// WooCommerce > Settings > Shipping > Shipping zones > Edit zone.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-edit-shipping-zone',
		'parent'    => 'woocommerce-settings',
		'screen_id' => 'woocommerce_page_wc-settings-shipping-edit_zone',
		'title'     => array(
			__( 'Shipping zones', 'woocommerce-admin' ),
			__( 'Edit zone', 'woocommerce-admin' ),
		),
		'path'      => add_query_arg(
			array(
				'page' => 'wc-settings',
				'tab'  => 'shipping',
			),
			$admin_page_base
		),
	)
);

// WooCommerce > Settings > Shipping > Shipping options.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-shipping-options',
		'parent'    => 'woocommerce-settings-shipping',
		'screen_id' => 'woocommerce_page_wc-settings-shipping-options',
		'title'     => __( 'Shipping options', 'woocommerce-admin' ),
	)
);

// WooCommerce > Settings > Shipping > Shipping classes.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-shipping-classes',
		'parent'    => 'woocommerce-settings-shipping',
		'screen_id' => 'woocommerce_page_wc-settings-shipping-classes',
		'title'     => __( 'Shipping classes', 'woocommerce-admin' ),
	)
);

// WooCommerce > Settings > Payments.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-payments',
		'parent'    => 'woocommerce-settings',
		'screen_id' => 'woocommerce_page_wc-settings-checkout',
		'title'     => __( 'Payments', 'woocommerce-admin' ),
		'path'      => add_query_arg(
			array(
				'page' => 'wc-settings',
				'tab'  => 'checkout',
			),
			$admin_page_base
		),
	)
);

// WooCommerce > Settings > Payments > Direct bank transfer.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-payments-bacs',
		'parent'    => 'woocommerce-settings-payments',
		'screen_id' => 'woocommerce_page_wc-settings-checkout-bacs',
		'title'     => __( 'Direct bank transfer', 'woocommerce-admin' ),
	)
);

// WooCommerce > Settings > Payments > Check payments.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-payments-cheque',
		'parent'    => 'woocommerce-settings-payments',
		'screen_id' => 'woocommerce_page_wc-settings-checkout-cheque',
		'title'     => __( 'Check payments', 'woocommerce-admin' ),
	)
);

// WooCommerce > Settings > Payments > Cash on delivery.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-payments-cod',
		'parent'    => 'woocommerce-settings-payments',
		'screen_id' => 'woocommerce_page_wc-settings-checkout-cod',
		'title'     => __( 'Cash on delivery', 'woocommerce-admin' ),
	)
);

// WooCommerce > Settings > Payments > PayPal.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-payments-paypal',
		'parent'    => 'woocommerce-settings-payments',
		'screen_id' => 'woocommerce_page_wc-settings-checkout-paypal',
		'title'     => __( 'PayPal', 'woocommerce-admin' ),
	)
);

// WooCommerce > Settings > Accounts & Privacy.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-accounts-privacy',
		'parent'    => 'woocommerce-settings',
		'screen_id' => 'woocommerce_page_wc-settings-account',
		'title'     => __( 'Accounts & Privacy', 'woocommerce-admin' ),
	)
);

// WooCommerce > Settings > Emails.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-email',
		'parent'    => 'woocommerce-settings',
		'screen_id' => 'woocommerce_page_wc-settings-email',
		'title'     => __( 'Emails', 'woocommerce-admin' ),
		'path'      => add_query_arg(
			array(
				'page' => 'wc-settings',
				'tab'  => 'email',
			),
			$admin_page_base
		),
	)
);

// WooCommerce > Settings > Emails > Edit email (all email editing).
$wc_emails    = WC_Emails::instance();
$wc_email_ids = array_map( 'sanitize_title', array_keys( $wc_emails->get_emails() ) );

foreach ( $wc_email_ids as $email_id ) {
	wc_admin_connect_page(
		array(
			'id'        => 'woocommerce-settings-email-' . $email_id,
			'parent'    => 'woocommerce-settings-email',
			'screen_id' => 'woocommerce_page_wc-settings-email-' . $email_id,
			'title'     => __( 'Edit email', 'woocommerce-admin' ),
		)
	);
}

// WooCommerce > Settings > Integration
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-integration',
		'parent'    => 'woocommerce-settings',
		'screen_id' => 'woocommerce_page_wc-settings-integration',
		'title'     => array(
			__( 'Integration', 'woocommerce-admin' ),
		),
		'path'      => add_query_arg(
			array(
				'page' => 'wc-settings',
				'tab'  => 'integration',
			),
			$admin_page_base
		),
	)
);

// WooCommerce > Settings > Advanced > Page setup (default tab).
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-advanced',
		'parent'    => 'woocommerce-settings',
		'screen_id' => 'woocommerce_page_wc-settings-advanced',
		'title'     => array(
			__( 'Advanced', 'woocommerce-admin' ),
			__( 'Page setup', 'woocommerce-admin' ),
		),
		'path'      => add_query_arg(
			array(
				'page' => 'wc-settings',
				'tab'  => 'advanced',
			),
			$admin_page_base
		),
	)
);

// WooCommerce > Settings > Advanced > REST API.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-advanced-rest-api',
		'parent'    => 'woocommerce-settings-advanced',
		'screen_id' => 'woocommerce_page_wc-settings-advanced-keys',
		'title'     => __( 'REST API', 'woocommerce-admin' ),
	)
);

// WooCommerce > Settings > Advanced > Webhooks.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-advanced-webhooks',
		'parent'    => 'woocommerce-settings-advanced',
		'screen_id' => 'woocommerce_page_wc-settings-advanced-webhooks',
		'title'     => __( 'Webhooks', 'woocommerce-admin' ),
	)
);

// WooCommerce > Settings > Advanced > Legacy API.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-advanced-legacy-api',
		'parent'    => 'woocommerce-settings-advanced',
		'screen_id' => 'woocommerce_page_wc-settings-advanced-legacy_api',
		'title'     => __( 'Legacy API', 'woocommerce-admin' ),
	)
);

// WooCommerce > Settings > Advanced > WooCommerce.com.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-settings-advanced-woocommerce-com',
		'parent'    => 'woocommerce-settings-advanced',
		'screen_id' => 'woocommerce_page_wc-settings-advanced-woocommerce_com',
		'title'     => __( 'WooCommerce.com', 'woocommerce-admin' ),
	)
);

// WooCommerce > Orders.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-orders',
		'screen_id' => 'edit-shop_order',
		'title'     => __( 'Orders', 'woocommerce-admin' ),
		'path'      => add_query_arg( 'post_type', 'shop_order', $posttype_list_base ),
	)
);

// WooCommerce > Orders > Add New.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-add-order',
		'parent'    => 'woocommerce-orders',
		'screen_id' => 'shop_order-add',
		'title'     => __( 'Add New', 'woocommerce-admin' ),
	)
);

// WooCommerce > Orders > Edit Order.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-edit-order',
		'parent'    => 'woocommerce-orders',
		'screen_id' => 'shop_order',
		'title'     => __( 'Edit Order', 'woocommerce-admin' ),
	)
);

// WooCommerce > Coupons.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-coupons',
		'screen_id' => 'edit-shop_coupon',
		'title'     => __( 'Coupons', 'woocommerce-admin' ),
		'path'      => add_query_arg( 'post_type', 'shop_coupon', $posttype_list_base ),
	)
);

// WooCommerce > Coupons > Add New.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-add-coupon',
		'parent'    => 'woocommerce-coupons',
		'screen_id' => 'shop_coupon-add',
		'title'     => __( 'Add New', 'woocommerce-admin' ),
	)
);

// WooCommerce > Coupons > Edit Coupon.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-edit-coupon',
		'parent'    => 'woocommerce-coupons',
		'screen_id' => 'shop_coupon',
		'title'     => __( 'Edit Coupon', 'woocommerce-admin' ),
	)
);

// WooCommerce > Reports > Orders (default tab).
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-reports',
		'screen_id' => 'woocommerce_page_wc-reports-orders',
		'title'     => array(
			__( 'Reports', 'woocommerce-admin' ),
			__( 'Orders', 'woocommerce-admin' ),
		),
		'path'      => add_query_arg( 'page', 'wc-reports', $admin_page_base ),
	)
);

// WooCommerce > Reports > Customers.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-reports-customers',
		'parent'    => 'woocommerce-reports',
		'screen_id' => 'woocommerce_page_wc-reports-customers',
		'title'     => __( 'Customers', 'woocommerce-admin' ),
	)
);

// WooCommerce > Reports > Stock.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-reports-stock',
		'parent'    => 'woocommerce-reports',
		'screen_id' => 'woocommerce_page_wc-reports-stock',
		'title'     => __( 'Stock', 'woocommerce-admin' ),
	)
);

// WooCommerce > Reports > Taxes.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-reports-taxes',
		'parent'    => 'woocommerce-reports',
		'screen_id' => 'woocommerce_page_wc-reports-taxes',
		'title'     => __( 'Taxes', 'woocommerce-admin' ),
	)
);

// WooCommerce > Status > System status (default tab).
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-status',
		'screen_id' => 'woocommerce_page_wc-status-status',
		'title'     => array(
			__( 'Status', 'woocommerce-admin' ),
			__( 'System status', 'woocommerce-admin' ),
		),
		'path'      => add_query_arg( 'page', 'wc-status', $admin_page_base ),
	)
);

// WooCommerce > Status > Tools.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-status-tools',
		'parent'    => 'woocommerce-status',
		'screen_id' => 'woocommerce_page_wc-status-tools',
		'title'     => __( 'Tools', 'woocommerce-admin' ),
	)
);

// WooCommerce > Status > Logs.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-status-logs',
		'parent'    => 'woocommerce-status',
		'screen_id' => 'woocommerce_page_wc-status-tools',
		'title'     => __( 'Tools', 'woocommerce-admin' ),
	)
);

// WooCommerce > Status > Scheduled Actions.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-status-action-scheduler',
		'parent'    => 'woocommerce-status',
		'screen_id' => 'woocommerce_page_wc-status-action-scheduler',
		'title'     => __( 'Scheduled Actions', 'woocommerce-admin' ),
	)
);

// WooCommerce > Extensions > Browse Extensions (default tab).
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-addons',
		'screen_id' => 'woocommerce_page_wc-addons-browse-extensions',
		'title'     => array(
			__( 'Extensions', 'woocommerce-admin' ),
			__( 'Browse Extensions', 'woocommerce-admin' ),
		),
		'path'      => add_query_arg( 'page', 'wc-addons', $admin_page_base ),
	)
);

// WooCommerce > Extensions > WooCommerce.com Subscriptions.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-addons-subscriptions',
		'parent'    => 'woocommerce-addons',
		'screen_id' => 'woocommerce_page_wc-addons-browse-extensions-helper',
		'title'     => __( 'WooCommerce.com Subscriptions', 'woocommerce-admin' ),
	)
);

// WooCommerce > Products.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-products',
		'screen_id' => 'edit-product',
		'title'     => __( 'Products', 'woocommerce-admin' ),
		'path'      => add_query_arg( 'post_type', 'product', $posttype_list_base ),
	)
);

// WooCommerce > Products > Add New.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-add-product',
		'parent'    => 'woocommerce-products',
		'screen_id' => 'product-add',
		'title'     => __( 'Add New', 'woocommerce-admin' ),
	)
);

// WooCommerce > Products > Edit Order.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-edit-product',
		'parent'    => 'woocommerce-products',
		'screen_id' => 'product',
		'title'     => __( 'Edit Product', 'woocommerce-admin' ),
	)
);

// WooCommerce > Products > Import Products.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-import-products',
		'parent'    => 'woocommerce-products',
		'screen_id' => 'product_page_product_importer',
		'title'     => __( 'Import Products', 'woocommerce-admin' ),
	)
);

// WooCommerce > Products > Export Products.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-export-products',
		'parent'    => 'woocommerce-products',
		'screen_id' => 'product_page_product_exporter',
		'title'     => __( 'Export Products', 'woocommerce-admin' ),
	)
);

// WooCommerce > Products > Product categories.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-product-categories',
		'parent'    => 'woocommerce-products',
		'screen_id' => 'edit-product_cat',
		'title'     => __( 'Product categories', 'woocommerce-admin' ),
	)
);

// WooCommerce > Products > Edit category.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-product-edit-category',
		'parent'    => 'woocommerce-products',
		'screen_id' => 'product_cat',
		'title'     => __( 'Edit category', 'woocommerce-admin' ),
	)
);

// WooCommerce > Products > Product tags.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-product-tags',
		'parent'    => 'woocommerce-products',
		'screen_id' => 'edit-product_tag',
		'title'     => __( 'Product tags', 'woocommerce-admin' ),
	)
);

// WooCommerce > Products > Edit tag.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-product-edit-tag',
		'parent'    => 'woocommerce-products',
		'screen_id' => 'product_tag',
		'title'     => __( 'Edit tag', 'woocommerce-admin' ),
	)
);

// WooCommerce > Products > Attributes.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-product-attributes',
		'parent'    => 'woocommerce-products',
		'screen_id' => 'product_page_product_attributes',
		'title'     => __( 'Attributes', 'woocommerce-admin' ),
	)
);

// WooCommerce > Products > Edit attribute.
wc_admin_connect_page(
	array(
		'id'        => 'woocommerce-product-edit-attribute',
		'parent'    => 'woocommerce-products',
		'screen_id' => 'product_page_product_attribute-edit',
		'title'     => __( 'Edit attribute', 'woocommerce-admin' ),
	)
);
