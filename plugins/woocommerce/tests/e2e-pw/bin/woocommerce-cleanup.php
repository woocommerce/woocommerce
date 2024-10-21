<?php
/**
 * Plugin Name: WooCommerce Cleanup
 * Description: Resets WooCommerce site to testing start state.
 * Version: 1.3
 * Author: Solaris Team
 * Requires at least: 6.6
 * Requires PHP: 8.0
 * @package WooCommerceCleanup
 *
 * This file contains the main functionality for the WooCommerce Cleanup plugin.
 * It provides functions to reset the WooCommerce site to a clean testing state.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Remove all media files and their database entries, except for specified files.
 */
function wc_cleanup_media() {
	$args = array(
		'post_type'   => 'attachment',
		'numberposts' => -1,
		'post_status' => null,
		'post_parent' => null,
	);

	$attachments         = get_posts( $args );
	$excluded_file_names = array( 'image-01', 'image-02', 'image-03' );

	if ( $attachments ) {
		foreach ( $attachments as $attachment ) {
			$file_name = pathinfo( get_attached_file( $attachment->ID ), PATHINFO_FILENAME );
			if ( ! in_array( $file_name, $excluded_file_names, true ) ) {
				wp_delete_attachment( $attachment->ID, true );
			}
		}
	}

	// Clean up the uploads directory, including CSV files.
	$upload_dir = wp_upload_dir();
	wc_cleanup_directory( $upload_dir['basedir'], $excluded_file_names );

	// Specifically target and remove CSV files.
	wc_cleanup_csv_files( $upload_dir['basedir'] );
}

/**
 * Recursively remove all files and subdirectories from a directory, except for specified files.
 *
 * @param string $dir The directory to clean.
 * @param array  $excluded_file_names Array of filenames (without extensions) to exclude from deletion.
 */
function wc_cleanup_directory( $dir, $excluded_file_names = array() ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}

	$files = array_diff( scandir( $dir ), array( '.', '..' ) );

	foreach ( $files as $file ) {
		$path = $dir . DIRECTORY_SEPARATOR . $file;

		if ( is_dir( $path ) ) {
			wc_cleanup_directory( $path, $excluded_file_names );
		} else {
			$file_name = pathinfo( $file, PATHINFO_FILENAME );
			if ( ! in_array( $file_name, $excluded_file_names, true ) ) {
				wp_delete_file( $path );
			}
		}
	}

	// Remove the empty directory if it's not the base upload directory.
	if ( wp_upload_dir()['basedir'] !== $dir ) {
		if ( function_exists( 'wp_delete_directory' ) ) {
			wp_delete_directory( $dir );
		} elseif ( function_exists( 'WP_Filesystem' ) ) {
			WP_Filesystem();
			global $wp_filesystem;
			$wp_filesystem->rmdir( $dir );
		} else {
			// Fallback for WordPress versions that don't have wp_delete_directory or WP_Filesystem.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			rmdir( $dir );
		}
	}
}

/**
 * Remove all WooCommerce taxes and tax classes.
 */
function wc_cleanup_taxes() {
	global $wpdb;

	// Remove all tax rates.
	$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_tax_rates" );
	$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_tax_rate_locations" );

	// Remove all tax classes except the default ones.
	$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_classes WHERE slug NOT IN ('standard', 'reduced-rate', 'zero-rate')" );

	// Reset tax options.
	update_option( 'woocommerce_tax_classes', 'Standard\nReduced rate\nZero rate' );
	update_option( 'woocommerce_tax_display_shop', 'excl' );
	update_option( 'woocommerce_tax_display_cart', 'excl' );
	update_option( 'woocommerce_tax_total_display', 'itemized' );

	// Disable taxes.
	update_option( 'woocommerce_calc_taxes', 'no' );

	// Clear the tax transients.
	WC_Cache_Helper::invalidate_cache_group( 'taxes' );
}

/**
 * Truncate a WooCommerce analytics table.
 *
 * @param string $table_name The name of the table to truncate, without the prefix.
 */
function wc_cleanup_analytics_table( $table_name ) {
	global $wpdb;
	$full_table_name = $wpdb->prefix . $table_name;

	// Check if the table exists before attempting to truncate.
	$table_exists = $wpdb->get_var(
		$wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$full_table_name
		)
	) === $full_table_name;

	if ( $table_exists ) {
		$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $full_table_name ) );
	} elseif ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		// Use WP_DEBUG_LOG instead of error_log.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( "Table $full_table_name does not exist." );
	}
}

/**
 * Reset the WooCommerce site.
 */
function wc_cleanup_reset_site() {

	// Remove all coupons.
	$coupons = get_posts(
		array(
			'post_type'   => 'shop_coupon',
			'numberposts' => -1,
		)
	);
	foreach ( $coupons as $coupon ) {
		wp_delete_post( $coupon->ID, true );
	}

	// Remove all coupons that are in the trash.
	$trash_coupons = get_posts(
		array(
			'post_type'   => 'shop_coupon',
			'post_status' => 'trash',
			'numberposts' => -1,
		)
	);

	foreach ( $trash_coupons as $coupon ) {
		wp_delete_post( $coupon->ID, true );
	}

	// Remove all orders.
	if ( wc_get_container()->get( Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
		// HPOS is enabled.
		global $wpdb;
		$table_name = $wpdb->prefix . 'wc_orders';
		$order_ids  = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM %i', $table_name ) );

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$order->delete( true );
			}
		}
	} else {
		// Traditional post-based orders.
		$orders = get_posts(
			array(
				'post_type'   => 'shop_order',
				'numberposts' => -1,
			)
		);
		foreach ( $orders as $order ) {
			wp_delete_post( $order->ID, true );
		}
	}

	// Remove all products.
	$products = get_posts(
		array(
			'post_type'   => 'product',
			'numberposts' => -1,
		)
	);
	foreach ( $products as $product ) {
		wp_delete_post( $product->ID, true );
	}

	// Delete all product categories.
	$product_categories = get_terms( 'product_cat' );
	foreach ( $product_categories as $product_category ) {
		wp_delete_term( $product_category->term_id, 'product_cat' );
	}

	// Delete all product tags.
	$product_tags = get_terms( 'product_tag' );
	foreach ( $product_tags as $product_tag ) {
		wp_delete_term( $product_tag->term_id, 'product_tag' );
	}

	// Delete all product attributes.
	$product_attributes = wc_get_attribute_taxonomies();
	foreach ( $product_attributes as $attribute ) {
		wp_delete_term( $attribute->attribute_id, 'pa_' . $attribute->attribute_name );
	}

	// Delete all product reviews.
	$comments = get_comments( array( 'post_type' => 'product' ) );
	foreach ( $comments as $comment ) {
		wp_delete_comment( $comment->comment_ID, true );
	}

	// Remove all non-WooCommerce pages.
	$pages         = get_posts(
		array(
			'post_type'   => 'page',
			'numberposts' => -1,
		)
	);
	$ignore_titles = array( 'Cart', 'Checkout', 'My account', 'Shop', 'Refund and Returns Policy', 'Privacy Policy', 'Sample Page' );

	foreach ( $pages as $page ) {
		if ( ! has_shortcode( $page->post_content, 'woocommerce' ) && ! in_array( $page->post_title, $ignore_titles, true ) ) {
			wp_delete_post( $page->ID, true );
		}

		// Set the active theme to Twenty Twenty-Three.
		switch_theme( 'twentytwentythree' );
	}

	// Clean up the WooCommerce analytics tables.
	wc_cleanup_analytics_table( 'wc_order_stats' );
	wc_cleanup_analytics_table( 'wc_customer_lookup' );

	// Remove all taxes.
	wc_cleanup_taxes();

	// Remove all media files.
	wc_cleanup_media();

	// Optionally, you can also clear WooCommerce transients.
	wc_delete_product_transients();

	// Set the site title.
	update_option( 'blogname', 'WooCommerce Core E2E Test Suite' );

	// Set the WooCommerce default country/state.
	update_option( 'woocommerce_default_country', 'US:CA' );

	// Set the WooCommerce selling locations to "Sell to all countries".
	update_option( 'woocommerce_allowed_countries', 'all' );

	// Set the WooCommerce shipping locations to "Ship to all countries you sell to".
	update_option( 'woocommerce_ship_to_countries', 'all' );

	// Disable taxes.
	update_option( 'woocommerce_calc_taxes', 'no' );

	// Enable coupons.
	update_option( 'woocommerce_enable_coupons', 'yes' );

	// Disable sequential coupon discounts.
	update_option( 'woocommerce_calc_discounts_sequentially', 'no' );

	// Set the WooCommerce currency to "United States (US) dollar".
	update_option( 'woocommerce_currency', 'USD' );

	// Set the WooCommerce currency position to "left".
	update_option( 'woocommerce_currency_pos', 'left' );

	// Set the WooCommerce thousand separator to a comma.
	update_option( 'woocommerce_price_thousand_sep', ',' );

	// Set the WooCommerce decimal separator to a period.
	update_option( 'woocommerce_price_decimal_sep', '.' );

	// Set the WooCommerce number of decimals to 2.
	update_option( 'woocommerce_price_num_decimals', '2' );

	// Delete all shipping zones.
	$shipping_zones = WC_Shipping_Zones::get_zones();
	foreach ( $shipping_zones as $zone ) {
		$zone_instance = new WC_Shipping_Zone( $zone['zone_id'] );
		$zone_instance->delete();
	}

	// Delete the default shipping zone.
	$default_zone = new WC_Shipping_Zone( 0 );
	$default_zone->delete();

	// Delete all shipping classes.
	$shipping_classes = get_terms( 'product_shipping_class' );
	foreach ( $shipping_classes as $shipping_class ) {
		wp_delete_term( $shipping_class->term_id, 'product_shipping_class' );
	}

	// Disable all payment methods.
	$payment_gateways = WC()->payment_gateways->payment_gateways();
	foreach ( $payment_gateways as $gateway ) {
		update_option( 'woocommerce_' . $gateway->id . '_settings', array_merge( get_option( 'woocommerce_' . $gateway->id . '_settings', array() ), array( 'enabled' => 'no' ) ) );
	}

	// Enable guest checkout.
	update_option( 'woocommerce_enable_guest_checkout', 'yes' );

	// Set the WooCommerce "From" email name.
	update_option( 'woocommerce_email_from_name', 'woocommerce' );

	// Set the WooCommerce "From" email address.
	update_option( 'woocommerce_email_from_address', 'wordpress@example.com' );
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'woocommerce',
			'WooCommerce Cleanup',
			'WooCommerce Cleanup',
			'manage_options',
			'woocommerce-cleanup',
			function () {
				if ( isset( $_POST['wc_cleanup_reset'] ) ) {
					// Verify the nonce before processing the form data.
					if ( check_admin_referer( 'wc_cleanup_reset_action', 'wc_cleanup_reset_nonce' ) ) {
						wc_cleanup_reset_site();
						echo '<div class="updated"><p>WooCommerce site has been reset.</p></div>';
					} else {
						echo '<div class="error"><p>Nonce verification failed. Please try again.</p></div>';
					}
				}
				?>
				<div class="wrap">
					<h1>WooCommerce Cleanup</h1>
					<form method="post">
						<?php wp_nonce_field( 'wc_cleanup_reset_action', 'wc_cleanup_reset_nonce' ); ?>
						<p>Click the button below to reset the WooCommerce site to a clean testing state.</p>
						<p><input type="submit" name="wc_cleanup_reset" class="button button-primary" value="Reset WooCommerce Site"></p>
					</form>
				</div>
				<?php
			}
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wc-cleanup/v1',
			'/reset',
			array(
				'methods'             => 'GET',
				'callback'            => 'wc_cleanup_reset_site_via_api',
				'permission_callback' => function () {
					$auth_header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) ) : '';
					if ( strpos( $auth_header, 'Basic ' ) === 0 ) {
						$auth_header = substr( $auth_header, 6 );
						$credentials = explode( ':', base64_decode( $auth_header ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
						$user = wp_authenticate( $credentials[0], $credentials[1] );
						if ( is_wp_error( $user ) || ! user_can( $user, 'manage_woocommerce' ) ) {
							return new WP_Error( 'forbidden', 'Unauthorized', array( 'status' => 403 ) );
						}

						return true;
					}

					return new WP_Error( 'forbidden', 'Unauthorized', array( 'status' => 403 ) );
				},
			)
		);
	}
);

/**
 * Reset the WooCommerce site via API.
 *
 * @return WP_REST_Response
 */
function wc_cleanup_reset_site_via_api() {
	wc_cleanup_reset_site();
	return new WP_REST_Response( 'WooCommerce site has been reset.', 200 );
}

/**
 * Clean up CSV files in the given directory and remove them from the media library.
 *
 * @param string $dir The directory to clean up.
 */
function wc_cleanup_csv_files( $dir ) {
	global $wpdb;
	$files = glob( $dir . '/*.csv' );
	foreach ( $files as $file ) {
		if ( is_file( $file ) ) {
			// Get the attachment ID by file path.
			$relative_path = str_replace( wp_upload_dir()['basedir'] . '/', '', $file );
			$attachment_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s",
					$relative_path
				)
			);

			if ( $attachment_id ) {
				// If found in media library, delete the attachment.
				wp_delete_attachment( $attachment_id, true );
			} else {
				// If not found in media library, just delete the file.
				wp_delete_file( $file );
			}
		}
	}

	// Recursively search subdirectories for CSV files.
	$subdirs = glob( $dir . '/*', GLOB_ONLYDIR );
	foreach ( $subdirs as $subdir ) {
		wc_cleanup_csv_files( $subdir );
	}

	// Clean up any orphaned database entries for CSV files.
	$wpdb->query(
		"
        DELETE p, pm
        FROM $wpdb->posts p
        LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
        WHERE p.post_type = 'attachment'
        AND p.post_mime_type = 'text/csv'
    "
	);
}
