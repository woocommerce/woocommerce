<?php

declare( strict_types = 1 );

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
		'icon'     => 'dashicons-admin-home',
		'position' => 10,
	),
	// HPOS slug. The legacy CPT slug `edit.php?post_type=shop_order` is used
	// when HPOS is disabled; auto-attach picks it up in that case.
	'wc-orders'                                                              => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Orders', 'woocommerce' ),
		'icon'     => 'dashicons-list-view',
		'position' => 20,
	),
	'edit.php?post_type=product'                                             => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Products', 'woocommerce' ),
		'icon'     => 'dashicons-products',
		'position' => 30,
	),
	'wc-admin&path=/analytics/overview'                                      => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Analytics', 'woocommerce' ),
		'icon'     => 'dashicons-chart-bar',
		'position' => 40,
	),
	'wc-admin&path=/customers'                                               => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Customers', 'woocommerce' ),
		'icon'     => 'dashicons-groups',
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
		'icon'     => 'dashicons-megaphone',
		'position' => 60,
		'url'      => 'admin.php?page=wc-admin&path=/marketing',
	),
	'wc-settings'                                                            => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Settings', 'woocommerce' ),
		'icon'     => 'dashicons-admin-settings',
		'position' => 90,
	),
	'woocommerce-payments'                                                   => array(
		'parent'   => 'wc-settings',
		'title'    => __( 'WooPayments', 'woocommerce' ),
		'position' => 20,
	),
	// Use the modern wc-admin Marketplace path so the rail highlights when
	// users land on Extensions (the classic `wc-addons` slug 302-redirects to
	// this URL anyway).
	'wc-admin&path=/extensions'                                              => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Extensions', 'woocommerce' ),
		'icon'     => 'dashicons-admin-plugins',
		'position' => 95,
	),
	// Status always sits at the end of Settings; use a high position so
	// later additions (Scheduled Actions at 100, third-party tabs, etc.)
	// still sort before it.
	'wc-status'                                                              => array(
		'parent'   => 'wc-settings',
		'title'    => __( 'Status', 'woocommerce' ),
		'position' => 9999,
	),
	// Action Scheduler registers itself as a submenu of Tools with the bare
	// slug `action-scheduler`; surface it inside Settings here and override
	// the click-through URL to point at `tools.php?page=...`. The Tools
	// entry is hidden via Menu_Reconciler::hide_non_woo_relocated_items().
	'action-scheduler'                                                       => array(
		'parent'   => 'wc-settings',
		'title'    => __( 'Scheduled Actions', 'woocommerce' ),
		'position' => 100,
		'url'      => 'tools.php?page=action-scheduler',
	),
);
