<?php
/**
 * Default tree for nested admin navigation.
 *
 * Returns a flat associative array keyed by slug. Each node declares its
 * `parent` (slug or null for the root), `title`, integer `position`, and
 * an optional `icon` (root only).
 *
 * Loaded once per admin pageload by Menu_Reconciler. Before consumption,
 * the tree is passed through the `woocommerce_admin_menu_tree` filter so
 * extension authors can override placement.
 */

defined( 'ABSPATH' ) || exit;

return array(
	'woocommerce'                                                            => array(
		'parent'   => null,
		'title'    => __( 'WooCommerce', 'woocommerce' ),
		'icon'     => 'dashicons-cart',
		'position' => 2,
	),
	'wc-admin'                                                               => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Home', 'woocommerce' ),
		'position' => 10,
	),
	// HPOS slug. The legacy CPT slug `edit.php?post_type=shop_order` is used
	// when HPOS is disabled; auto-attach picks it up in that case.
	'wc-orders'                                                              => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Orders', 'woocommerce' ),
		'position' => 20,
	),
	'edit.php?post_type=product'                                             => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Products', 'woocommerce' ),
		'position' => 30,
	),
	'wc-admin&path=/analytics/overview'                                      => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Analytics', 'woocommerce' ),
		'position' => 40,
	),
	'wc-admin&path=/customers'                                               => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Customers', 'woocommerce' ),
		'position' => 50,
	),
	// Keep `woocommerce-marketing` as the tree slug so hoisted children
	// ($submenu['woocommerce-marketing']) attach correctly, but override the
	// click-through URL to the real wc-admin React route — the `woocommerce-
	// marketing` slug itself is a placeholder registered with a null callback
	// and errors on direct load.
	'woocommerce-marketing'                                                  => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Marketing', 'woocommerce' ),
		'position' => 60,
		'url'      => 'admin.php?page=wc-admin&path=/marketing',
	),
	'wc-settings'                                                            => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Settings', 'woocommerce' ),
		'position' => 90,
	),
	'admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM'        => array(
		'parent'   => 'wc-settings',
		'title'    => __( 'Payments', 'woocommerce' ),
		'position' => 10,
	),
	'woocommerce-payments'                                                   => array(
		'parent'   => 'wc-settings',
		'title'    => __( 'WooPayments', 'woocommerce' ),
		'position' => 20,
	),
	'wc-addons'                                                              => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Extensions', 'woocommerce' ),
		'position' => 95,
	),
	'wc-status'                                                              => array(
		'parent'   => 'wc-settings',
		'title'    => __( 'Status', 'woocommerce' ),
		'position' => 99,
	),
);
