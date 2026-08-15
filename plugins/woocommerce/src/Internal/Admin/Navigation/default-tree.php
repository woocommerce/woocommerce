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

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'woocommerce'                           => array(
		'parent'   => null,
		'title'    => __( 'WooCommerce', 'woocommerce' ),
		'icon'     => 'dashicons-cart',
		'position' => 2,
	),
	'wc-admin'                              => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Home', 'woocommerce' ),
		// WooCommerce logo — CSS mask in admin-navigation-v2.scss targets
		// li#toplevel_page_wc-admin for color-scheme-aware coloring.
		'icon'     => 'data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmlld0JveD0iMCAwIDg1LjkgNDcuNiI+PHBhdGggZmlsbD0iI2EyYWFiMiIgZD0iTTc3LjQsMC4xYy00LjMsMC03LjEsMS40LTkuNiw2LjFMNTYuNCwyNy43VjguNmMwLTUuNy0yLjctOC41LTcuNy04LjVzLTcuMSwxLjctOS42LDYuNUwyOC4zLDI3LjdWOC44YzAtNi4xLTIuNS04LjctOC42LTguN0g3LjNDMi42LDAuMSwwLDIuMywwLDYuM3MyLjUsNi40LDcuMSw2LjRoNS4xdjI0LjFjMCw2LjgsNC42LDEwLjgsMTEuMiwxMC44UzMzLDQ1LDM2LjMsMzguOWw3LjItMTMuNXYxMS40YzAsNi43LDQuNCwxMC44LDExLjEsMTAuOHM5LjItMi4zLDEzLTguN2wxNi42LTI4YzMuNi02LjEsMS4xLTEwLjgtNi45LTEwLjhDNzcuMywwLjEsNzcuMywwLjEsNzcuNCwwLjF6Ii8+PC9zdmc+',
		'position' => 10,
	),
	// HPOS slug. The legacy CPT slug `edit.php?post_type=shop_order` is used
	// when HPOS is disabled; auto-attach picks it up in that case.
	'wc-orders'                             => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Orders', 'woocommerce' ),
		'icon'     => 'dashicons-list-view',
		'position' => 20,
	),
	'edit.php?post_type=product'            => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Products', 'woocommerce' ),
		'icon'     => 'dashicons-products',
		'position' => 30,
	),
	'post-new.php?post_type=product'        => array(
		'parent'   => 'edit.php?post_type=product',
		'title'    => __( 'Add product', 'woocommerce' ),
		'position' => 2,
	),
	'wc-admin&path=/analytics/overview'     => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Analytics', 'woocommerce' ),
		'icon'     => 'dashicons-chart-bar',
		'position' => 40,
	),
	'wc-admin&path=/customers'              => array(
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
	'woocommerce-marketing'                 => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Marketing', 'woocommerce' ),
		'icon'     => 'dashicons-megaphone',
		'position' => 60,
		'url'      => 'admin.php?page=wc-admin&path=/marketing',
	),
	'wc-settings'                           => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Settings', 'woocommerce' ),
		'icon'     => 'dashicons-admin-settings',
		'position' => 90,
	),
	// WC Settings > Payments tab surfaced directly on the rail. Using the
	// tab slug as the key with a `url` override makes it a synthetic node
	// (bypasses the registered-slugs check) and causes add_settings_tabs()
	// to skip adding it a second time under Settings.
	'wc-settings&tab=checkout'              => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Payments', 'woocommerce' ),
		// Dollar-sign-in-rounded-rectangle: same icon WC's PaymentsController registers.
		// CSS in admin-navigation-v2.scss replaces this with a mask+currentColor
		// approach so it responds correctly to all WP admin color schemes.
		'icon'     => 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4NTIiIGhlaWdodD0iNjg0Ij48cGF0aCBmaWxsPSIjYTJhYWIyIiBkPSJNODIgODZ2NTEyaDY4NFY4NlptMCA1OThjLTQ4IDAtODQtMzgtODQtODZWODZDLTIgMzggMzQgMCA4MiAwaDY4NGM0OCAwIDg0IDM4IDg0IDg2djUxMmMwIDQ4LTM2IDg2LTg0IDg2em0zODQtNTU2djQ0aDg2djg0SDM4MnY0NGgxMjhjMjQgMCA0MiAxOCA0MiA0MnYxMjhjMCAyNC0xOCA0Mi00MiA0MmgtNDR2NDRoLTg0di00NGgtODZ2LTg0aDE3MHYtNDRIMzM4Yy0yNCAwLTQyLTE4LTQyLTQyVjIxNGMwLTI0IDE4LTQyIDQyLTQyaDQ0di00NHoiLz48L3N2Zz4=',
		'position' => 35,
		'url'      => 'admin.php?page=wc-settings&tab=checkout',
	),
	'woocommerce-payments'                  => array(
		'parent'   => 'wc-settings',
		'title'    => __( 'WooPayments', 'woocommerce' ),
		'position' => 20,
	),
	// Use the modern wc-admin Marketplace path so the rail highlights when
	// users land on Extensions (the classic `wc-addons` slug 302-redirects to
	// this URL anyway).
	'wc-admin&path=/extensions'             => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Extensions', 'woocommerce' ),
		'icon'     => 'dashicons-admin-plugins',
		'position' => 95,
	),
	// Default landing item under Extensions: the Marketplace listing
	// (WooCommerce > Extensions in the classic menu). The synthetic key
	// keeps the tree out of a key collision with the rail-root above; the
	// `url` override points the click through to the real Marketplace URL.
	'wc-admin&path=/extensions&marketplace' => array(
		'parent'   => 'wc-admin&path=/extensions',
		'title'    => __( 'Browse marketplace', 'woocommerce' ),
		'position' => 1,
		'url'      => 'wc-admin&path=/extensions',
	),
	// Status always sits at the end of Settings; use a high position so
	// later additions (Scheduled Actions at 100, third-party tabs, etc.)
	// still sort before it.
	'wc-status'                             => array(
		'parent'   => 'wc-settings',
		'title'    => __( 'Status', 'woocommerce' ),
		'position' => 9999,
	),
	// Action Scheduler registers itself as a submenu of Tools with the bare
	// slug `action-scheduler`; surface it inside Settings here and override
	// the click-through URL to point at `tools.php?page=...`. The Tools
	// entry is hidden via Menu_Reconciler::hide_non_woo_relocated_items().
	'action-scheduler'                      => array(
		'parent'   => 'wc-settings',
		'title'    => __( 'Scheduled actions', 'woocommerce' ),
		'position' => 100,
		'url'      => 'tools.php?page=action-scheduler',
	),
);
