<?php
/**
 * Admin functions
 *
 * @package WC_Admin
 */

/**
 * Returns true if we are on a JS powered admin page.
 */
function wc_admin_is_admin_page() {
	global $hook_suffix;
	if ( in_array( $hook_suffix, array( 'woocommerce_page_wc-admin' ) ) ) {
		return true;
	}
	return false;
}

/**
 * Returns true if we are on a "classic" (non JS app) powered admin page.
 * `wc_get_screen_ids` will also return IDs for extensions that have properly registered themselves.
 */
function wc_admin_is_embed_enabled_wc_page() {
	if ( ! wc_admin_is_feature_enabled( 'activity-panels' ) ) {
		return false;
	}

	$screen_id = wc_admin_get_current_screen_id();
	if ( ! $screen_id ) {
		return false;
	}

	$screens = wc_admin_get_embed_enabled_screen_ids();

	if ( in_array( $screen_id, $screens ) ) {
		return true;
	}
	return false;
}

/**
 * Add a single page to a given parent top-level-item.
 *
 * @param array $options {
 *     Array describing the menu item.
 *
 *     @type string $title Menu title
 *     @type string $parent Parent path or menu ID
 *     @type string $path Path for this page, full path in app context; ex /analytics/report
 * }
 */
function wc_admin_register_page( $options ) {
	$defaults = array(
		'parent' => '/analytics',
	);
	$options  = wp_parse_args( $options, $defaults );
	add_submenu_page(
		'/' === $options['parent'][0] ? "wc-admin#{$options['parent']}" : $options['parent'],
		$options['title'],
		$options['title'],
		'manage_options',
		"wc-admin#{$options['path']}",
		'wc_admin_page'
	);
}

/**
 * Register menu pages for the Dashboard and Analytics sections.
 */
function wc_admin_register_pages() {
	global $menu, $submenu;

	if ( wc_admin_is_feature_enabled( 'dashboard' ) ) {
		add_submenu_page(
			'woocommerce',
			__( 'WooCommerce Dashboard', 'woocommerce-admin' ),
			__( 'Dashboard', 'woocommerce-admin' ),
			'manage_options',
			'wc-admin',
			'wc_admin_page'
		);
	}

	if ( wc_admin_is_feature_enabled( 'analytics' ) ) {
		add_menu_page(
			__( 'WooCommerce Analytics', 'woocommerce-admin' ),
			__( 'Analytics', 'woocommerce-admin' ),
			'manage_options',
			'wc-admin#/analytics/revenue',
			'wc_admin_page',
			'dashicons-chart-bar',
			56 // After WooCommerce & Product menu items.
		);

		wc_admin_register_page(
			array(
				'title'  => __( 'Revenue', 'woocommerce-admin' ),
				'parent' => '/analytics/revenue',
				'path'   => '/analytics/revenue',
			)
		);

		wc_admin_register_page(
			array(
				'title'  => __( 'Orders', 'woocommerce-admin' ),
				'parent' => '/analytics/revenue',
				'path'   => '/analytics/orders',
			)
		);

		wc_admin_register_page(
			array(
				'title'  => __( 'Products', 'woocommerce-admin' ),
				'parent' => '/analytics/revenue',
				'path'   => '/analytics/products',
			)
		);

		wc_admin_register_page(
			array(
				'title'  => __( 'Categories', 'woocommerce-admin' ),
				'parent' => '/analytics/revenue',
				'path'   => '/analytics/categories',
			)
		);

		wc_admin_register_page(
			array(
				'title'  => __( 'Coupons', 'woocommerce-admin' ),
				'parent' => '/analytics/revenue',
				'path'   => '/analytics/coupons',
			)
		);

		wc_admin_register_page(
			array(
				'title'  => __( 'Taxes', 'woocommerce-admin' ),
				'parent' => '/analytics/revenue',
				'path'   => '/analytics/taxes',
			)
		);

		wc_admin_register_page(
			array(
				'title'  => __( 'Downloads', 'woocommerce-admin' ),
				'parent' => '/analytics/revenue',
				'path'   => '/analytics/downloads',
			)
		);

		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {
			wc_admin_register_page(
				array(
					'title'  => __( 'Stock', 'woocommerce-admin' ),
					'parent' => '/analytics/revenue',
					'path'   => '/analytics/stock',
				)
			);
		}

		wc_admin_register_page(
			array(
				'title'  => __( 'Customers', 'woocommerce-admin' ),
				'parent' => '/analytics/revenue',
				'path'   => '/analytics/customers',
			)
		);

		wc_admin_register_page(
			array(
				'title'  => __( 'Settings', 'woocommerce-admin' ),
				'parent' => '/analytics/revenue',
				'path'   => '/analytics/settings',
			)
		);
	}

	if ( wc_admin_is_feature_enabled( 'devdocs' ) && defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		wc_admin_register_page(
			array(
				'title'  => 'DevDocs',
				'parent' => 'woocommerce', // Exposed on the main menu for now.
				'path'   => '/devdocs',
			)
		);
	}
}
add_action( 'admin_menu', 'wc_admin_register_pages' );

/**
 * This method is temporary while this is a feature plugin. As a part of core,
 * we can integrate this better with wc-admin-menus.
 *
 * It makes dashboard the top level link for 'WooCommerce' and renames the first Analytics menu item.
 */
function wc_admin_link_structure() {
	global $submenu;
	// User does not have capabilites to see the submenu.
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$wc_admin_key = null;
	foreach ( $submenu['woocommerce'] as $submenu_key => $submenu_item ) {
		if ( 'wc-admin' === $submenu_item[2] ) {
			$wc_admin_key = $submenu_key;
			break;
		}
	}

	if ( ! $wc_admin_key ) {
		return;
	}

	$menu = $submenu['woocommerce'][ $wc_admin_key ];

	// Move menu item to top of array.
	unset( $submenu['woocommerce'][ $wc_admin_key ] );
	array_unshift( $submenu['woocommerce'], $menu );
}

// priority is 20 to run after https://github.com/woocommerce/woocommerce/blob/a55ae325306fc2179149ba9b97e66f32f84fdd9c/includes/admin/class-wc-admin-menus.php#L165.
add_action( 'admin_head', 'wc_admin_link_structure', 20 );

/**
 * Load the assets on the admin pages
 */
function wc_admin_enqueue_script() {
	if ( ! wc_admin_is_admin_page() && ! wc_admin_is_embed_enabled_wc_page() ) {
		return;
	}

	wp_enqueue_script( WC_ADMIN_APP );
	wp_enqueue_style( WC_ADMIN_APP );

	// Use server-side detection to prevent unneccessary stylesheet loading in other browsers.
	$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : ''; // WPCS: sanitization ok.
	preg_match( '/MSIE (.*?);/', $user_agent, $matches );
	if ( count( $matches ) < 2 ) {
		preg_match( '/Trident\/\d{1,2}.\d{1,2}; rv:([0-9]*)/', $user_agent, $matches );
	}
	if ( count( $matches ) > 1 ) {
		wp_enqueue_style( 'wc-components-ie' );
	}
}
add_action( 'admin_enqueue_scripts', 'wc_admin_enqueue_script' );

/**
 * Adds body classes to the main wp-admin wrapper, allowing us to better target elements in specific scenarios.
 *
 * @param string $admin_body_class Body class to add.
 */
function wc_admin_admin_body_class( $admin_body_class = '' ) {
	global $hook_suffix;

	if ( ! wc_admin_is_admin_page() && ! wc_admin_is_embed_enabled_wc_page() ) {
		return $admin_body_class;
	}

	$classes   = explode( ' ', trim( $admin_body_class ) );
	$classes[] = 'woocommerce-page';
	if ( wc_admin_is_embed_enabled_wc_page() ) {
		$classes[] = 'woocommerce-embed-page';
	}

	if ( function_exists( 'wc_admin_get_feature_config' ) ) {
		$features = wc_admin_get_feature_config();
		foreach ( $features as $feature_key => $bool ) {
			if ( true === $bool ) {
				$classes[] = sanitize_html_class( 'woocommerce-feature-enabled-' . $feature_key );
			} else {
				$classes[] = sanitize_html_class( 'woocommerce-feature-disabled-' . $feature_key );
			}
		}
	}

	$admin_body_class = implode( ' ', array_unique( $classes ) );
	return " $admin_body_class ";
}
add_filter( 'admin_body_class', 'wc_admin_admin_body_class' );

/**
 * Removes notices that should not be displayed on WC Admin pages.
 */
function wc_admin_remove_notices() {
	if ( ! wc_admin_is_admin_page() && ! wc_admin_is_embed_enabled_wc_page() ) {
		return;
	}

	// Hello Dolly.
	if ( function_exists( 'hello_dolly' ) ) {
		remove_action( 'admin_notices', 'hello_dolly' );
	}
}
add_action( 'admin_head', 'wc_admin_remove_notices' );

/**
 * Runs before admin notices action and hides them.
 */
function wc_admin_admin_before_notices() {
	if ( ( ! wc_admin_is_admin_page() && ! wc_admin_is_embed_enabled_wc_page() ) || ! wc_admin_is_feature_enabled( 'activity-panels' ) ) {
		return;
	}
	echo '<div class="woocommerce-layout__notice-list-hide" id="wp__notice-list">';
	echo '<div class="wp-header-end" id="woocommerce-layout__notice-catcher"></div>'; // https://github.com/WordPress/WordPress/blob/f6a37e7d39e2534d05b9e542045174498edfe536/wp-admin/js/common.js#L737.
}
add_action( 'admin_notices', 'wc_admin_admin_before_notices', 0 );

/**
 * Runs after admin notices and closes div.
 */
function wc_admin_admin_after_notices() {
	if ( ( ! wc_admin_is_admin_page() && ! wc_admin_is_embed_enabled_wc_page() ) || ! wc_admin_is_feature_enabled( 'activity-panels' ) ) {
		return;
	}
	echo '</div>';
}
add_action( 'admin_notices', 'wc_admin_admin_after_notices', PHP_INT_MAX );

/**
 * Edits Admin title based on section of wc-admin.
 *
 * @param string $admin_title Modifies admin title.
 * @todo Can we do some URL rewriting so we can figure out which page they are on server side?
 */
function wc_admin_admin_title( $admin_title ) {
	if ( ! wc_admin_is_admin_page() && ! wc_admin_is_embed_enabled_wc_page() ) {
		return $admin_title;
	}

	if ( wc_admin_is_embed_enabled_wc_page() ) {
		$sections = wc_admin_get_embed_breadcrumbs();
		$sections = is_array( $sections ) ? $sections : array( $sections );
		$pieces   = array();

		foreach ( $sections as $section ) {
			$pieces[] = is_array( $section ) ? $section[1] : $section;
		}

		$pieces = array_reverse( $pieces );
		$title  = implode( ' &lsaquo; ', $pieces );
	} else {
		$title = __( 'Dashboard', 'woocommerce-admin' );
	}
	/* translators: %1$s: updated title, %2$s: blog info name */
	return sprintf( __( '%1$s &lsaquo; %2$s &#8212; WooCommerce', 'woocommerce-admin' ), $title, get_bloginfo( 'name' ) );
}
add_filter( 'admin_title', 'wc_admin_admin_title' );

/**
 * Set up a div for the app to render into.
 */
function wc_admin_page() {
	?>
	<div class="wrap">
		<div id="root"></div>
	</div>
	<?php
}

/**
 * Outputs a breadcrumb
 *
 * @param array $section Section to create breadcrumb from.
 */
function wc_admin_output_breadcrumb( $section ) {
	?>
	<span>
	<?php if ( is_array( $section ) ) : ?>
		<a href="<?php echo esc_url( admin_url( $section[0] ) ); ?>">
			<?php echo esc_html( $section[1] ); ?>
		</a>
	<?php else : ?>
		<?php echo esc_html( $section ); ?>
	<?php endif; ?>
	</span>
	<?php
}

/**
 * Set up a div for the header embed to render into.
 * The initial contents here are meant as a place loader for when the PHP page initialy loads.
 *
 * @todo Icon Placeholders for the ActivityPanel, when we implement the new designs.
 */
function wc_admin_embed_page_header() {
	if ( ! wc_admin_is_embed_enabled_wc_page() ) {
		return;
	}

	$sections = wc_admin_get_embed_breadcrumbs();
	$sections = is_array( $sections ) ? $sections : array( $sections );
	?>
	<div id="woocommerce-embedded-root">
		<div class="woocommerce-layout">
			<div class="woocommerce-layout__header is-embed-loading">
				<h1 class="woocommerce-layout__header-breadcrumbs">
					<span><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-admin#/' ) ); ?>">WooCommerce</a></span>
					<?php foreach ( $sections as $section ) : ?>
						<?php wc_admin_output_breadcrumb( $section ); ?>
					<?php endforeach; ?>
				</h1>
			</div>
		</div>
		<div class="woocommerce-layout__primary is-embed-loading" id="woocommerce-layout__primary">
			<div id="woocommerce-layout__notice-list" class="woocommerce-layout__notice-list"></div>
		</div>
	</div>
	<?php
}
add_action( 'in_admin_header', 'wc_admin_embed_page_header' );

/**
 * Registers WooCommerce specific user data to the WordPress user API.
 */
function wc_admin_register_user_data() {
	register_rest_field(
		'user',
		'woocommerce_meta',
		array(
			'get_callback'    => 'wc_admin_get_user_data_values',
			'update_callback' => 'wc_admin_update_user_data_values',
			'schema'          => null,
		)
	);
}
 add_action( 'rest_api_init', 'wc_admin_register_user_data' );

/**
 * We store some WooCommerce specific user meta attached to users endpoint,
 * so that we can track certain preferences or values such as the inbox activity panel last open time.
 * Additional fields can be added in the function below, and then used via wc-admin's currentUser data.
 *
 * @return array Fields to expose over the WP user endpoint.
 */
function wc_admin_get_user_data_fields() {
	$user_data_fields = array(
		'categories_report_columns',
		'coupons_report_columns',
		'customers_report_columns',
		'orders_report_columns',
		'products_report_columns',
		'revenue_report_columns',
		'taxes_report_columns',
		'variations_report_columns',
		'dashboard_performance_indicators',
		'dashboard_charts',
		'dashboard_chart_type',
		'dashboard_chart_interval',
		'dashboard_leaderboards',
		'dashboard_leaderboard_rows',
		'activity_panel_inbox_last_read',
	);

	return apply_filters( 'wc_admin_get_user_data_fields', $user_data_fields );
}

/**
 * For all the registered user data fields (  wc_admin_get_user_data_fields ), fetch the data
 * for returning via the REST API.
 *
 * @param WP_User $user Current user.
 */
function wc_admin_get_user_data_values( $user ) {
	$values = array();
	foreach ( wc_admin_get_user_data_fields() as $field ) {
		$values[ $field ] = get_user_meta( $user['id'], 'wc_admin_' . $field, true );
	}
	return $values;
}

/**
 * For all the registered user data fields (  wc_admin_get_user_data_fields ), update the data
 * for the REST API.
 *
 * @param array   $values   The new values for the meta.
 * @param WP_User $user     The current user.
 * @param string  $field_id The field id for the user meta.
 */
function wc_admin_update_user_data_values( $values, $user, $field_id ) {
	if ( empty( $values ) || ! is_array( $values ) || 'woocommerce_meta' !== $field_id ) {
		return;
	}
	$fields  = wc_admin_get_user_data_fields();
	$updates = array();
	foreach ( $values as $field => $value ) {
		if ( in_array( $field, $fields, true ) ) {
			$updates[ $field ] = $value;
			update_user_meta( $user->ID, 'wc_admin_' . $field, $value );
		}
	}

	return $updates;
}

/**
 * Register the admin settings for use in the WC REST API
 *
 * @param array $groups Array of setting groups.
 * @return array
 */
function wc_admin_add_settings_group( $groups ) {
	$groups[] = array(
		'id'          => 'wc_admin',
		'label'       => __( 'WooCommerce Admin', 'woocommerce-admin' ),
		'description' => __( 'Settings for WooCommerce admin reporting.', 'woocommerce-admin' ),
	);
	return $groups;
}
add_filter( 'woocommerce_settings_groups', 'wc_admin_add_settings_group' );

/**
 * Add WC Admin specific settings
 *
 * @param array $settings Array of settings in wc admin group.
 * @return array
 */
function wc_admin_add_settings( $settings ) {
	$settings[] = array(
		'id'          => 'woocommerce_excluded_report_order_statuses',
		'option_key'  => 'woocommerce_excluded_report_order_statuses',
		'label'       => __( 'Excluded report order statuses', 'woocommerce-admin' ),
		'description' => __( 'Statuses that should not be included when calculating report totals.', 'woocommerce-admin' ),
		'default'     => array( 'pending', 'cancelled', 'failed' ),
		'type'        => 'multiselect',
		'options'     => wc_admin_format_order_statuses( wc_get_order_statuses() ),
	);
	$settings[] = array(
		'id'          => 'woocommerce_actionable_order_statuses',
		'option_key'  => 'woocommerce_actionable_order_statuses',
		'label'       => __( 'Actionable order statuses', 'woocommerce-admin' ),
		'description' => __( 'Statuses that require extra action on behalf of the store admin.', 'woocommerce-admin' ),
		'default'     => array( 'processing', 'on-hold' ),
		'type'        => 'multiselect',
		'options'     => wc_admin_format_order_statuses( wc_get_order_statuses() ),
	);
	return $settings;
};
add_filter( 'woocommerce_settings-wc_admin', 'wc_admin_add_settings' );
