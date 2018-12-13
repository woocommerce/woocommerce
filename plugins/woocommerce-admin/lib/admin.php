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

	add_submenu_page(
		'woocommerce',
		__( 'WooCommerce Dashboard', 'wc-admin' ),
		__( 'Dashboard', 'wc-admin' ),
		'manage_options',
		'wc-admin',
		'wc_admin_page'
	);

	add_menu_page(
		__( 'WooCommerce Analytics', 'wc-admin' ),
		__( 'Analytics', 'wc-admin' ),
		'manage_options',
		'wc-admin#/analytics/revenue',
		'wc_admin_page',
		'dashicons-chart-bar',
		56 // After WooCommerce & Product menu items.
	);

	wc_admin_register_page(
		array(
			'title'  => __( 'Revenue', 'wc-admin' ),
			'parent' => '/analytics/revenue',
			'path'   => '/analytics/revenue',
		)
	);

	wc_admin_register_page(
		array(
			'title'  => __( 'Orders', 'wc-admin' ),
			'parent' => '/analytics/revenue',
			'path'   => '/analytics/orders',
		)
	);

	wc_admin_register_page(
		array(
			'title'  => __( 'Products', 'wc-admin' ),
			'parent' => '/analytics/revenue',
			'path'   => '/analytics/products',
		)
	);

	wc_admin_register_page(
		array(
			'title'  => __( 'Categories', 'wc-admin' ),
			'parent' => '/analytics/revenue',
			'path'   => '/analytics/categories',
		)
	);

	wc_admin_register_page(
		array(
			'title'  => __( 'Coupons', 'wc-admin' ),
			'parent' => '/analytics/revenue',
			'path'   => '/analytics/coupons',
		)
	);

	wc_admin_register_page(
		array(
			'title'  => __( 'Taxes', 'wc-admin' ),
			'parent' => '/analytics/revenue',
			'path'   => '/analytics/taxes',
		)
	);

	wc_admin_register_page(
		array(
			'title'  => __( 'Customers', 'wc-admin' ),
			'parent' => '/analytics/revenue',
			'path'   => '/analytics/customers',
		)
	);

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
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
}
add_action( 'admin_enqueue_scripts', 'wc_admin_enqueue_script' );

/**
 * Adds an admin body class.
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
	$admin_body_class = implode( ' ', array_unique( $classes ) );
	return " $admin_body_class ";
}
add_filter( 'admin_body_class', 'wc_admin_admin_body_class' );

/**
 * Runs before admin notices action and hides them.
 */
function wc_admin_admin_before_notices() {
	if ( ! wc_admin_is_admin_page() && ! wc_admin_is_embed_enabled_wc_page() ) {
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
	if ( ! wc_admin_is_admin_page() && ! wc_admin_is_embed_enabled_wc_page() ) {
		return;
	}
	echo '</div>';
}
add_action( 'admin_notices', 'wc_admin_admin_after_notices', PHP_INT_MAX );

/**
 * Edits Admin title based on section of wc-admin.
 *
 * @TODO Can we do some URL rewriting so we can figure out which page they are on server side?
 *
 * @param string $admin_title Modifies admin title.
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
		$title = __( 'Dashboard', 'wc-admin' );
	}
	/* translators: %1$s: updated title, %2$s: blog info name */
	return sprintf( __( '%1$s &lsaquo; %2$s &#8212; WooCommerce', 'wc-admin' ), $title, get_bloginfo( 'name' ) );
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

 * TODO Icon Placeholders for the ActivityPanel, when we implement the new designs.
 */
function woocommerce_embed_page_header() {
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
add_action( 'in_admin_header', 'woocommerce_embed_page_header' );

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
