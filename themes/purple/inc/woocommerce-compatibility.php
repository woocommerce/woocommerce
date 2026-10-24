<?php
/**
 * WooCommerce compatibility notices.
 *
 * @internal
 * @package purple
 */

declare( strict_types = 1 );

/**
 * Build a notice when WooCommerce does not meet the theme's requirements.
 *
 * @internal
 *
 * @return string Notice HTML, or an empty string when no notice is needed.
 */
function purple_get_woocommerce_notice(): string {
	$screen = get_current_screen();
	if ( ! $screen || 'themes' !== $screen->id ) {
		return '';
	}

	if ( get_user_meta( get_current_user_id(), 'purple_woocommerce_notice_dismissed', true ) ) {
		return '';
	}

	// Read the parent theme directly: WooCommerce may not be loaded to register its custom header.
	$headers = get_file_data( get_template_directory() . '/style.css', array( 'minimum_wc' => 'WC requires at least' ) );
	$minimum = $headers['minimum_wc'];
	if ( '' === $minimum ) {
		return '';
	}

	if ( defined( 'WC_VERSION' ) ) {
		return purple_get_woocommerce_outdated_notice( $minimum, WC_VERSION );
	}

	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$plugins = get_plugins();
	if ( isset( $plugins['woocommerce/woocommerce.php'] ) ) {
		return purple_get_woocommerce_inactive_notice( $minimum );
	}

	return purple_get_woocommerce_missing_notice( $minimum );
}

/**
 * Build the update notice when the active WooCommerce version is too old.
 *
 * @internal
 *
 * @param string $minimum Minimum required WooCommerce version.
 * @param string $current Active WooCommerce version.
 * @return string Notice HTML, or an empty string for a compatible version.
 */
function purple_get_woocommerce_outdated_notice( string $minimum, string $current ): string {
	if ( version_compare( $current, $minimum, '>=' ) ) {
		return '';
	}

	$notice = esc_html(
		sprintf(
			/* translators: 1: Minimum WooCommerce version, 2: Active WooCommerce version. */
			__( 'For the best experience with Purple, we recommend WooCommerce %1$s or newer. You are currently running WooCommerce %2$s.', 'purple' ),
			$minimum,
			$current
		)
	);
	if ( current_user_can( 'update_plugins' ) ) {
		$notice .= ' <a href="' . esc_url( self_admin_url( 'plugins.php?plugin_status=upgrade' ) ) . '">' . esc_html__( 'View updates', 'purple' ) . '</a>';
	}

	return $notice;
}

/**
 * Build the activation notice for an installed but inactive WooCommerce plugin.
 *
 * @internal
 *
 * @param string $minimum Minimum required WooCommerce version.
 * @return string Notice HTML.
 */
function purple_get_woocommerce_inactive_notice( string $minimum ): string {
	$notice = esc_html(
		sprintf(
			/* translators: %s: Minimum WooCommerce version. */
			__( 'For the best experience with Purple, we recommend activating WooCommerce %s or newer.', 'purple' ),
			$minimum
		)
	);
	if ( current_user_can( 'activate_plugins' ) ) {
		$notice .= ' <a href="' . esc_url( self_admin_url( 'plugins.php?plugin_status=inactive' ) ) . '">' . esc_html__( 'Manage plugins', 'purple' ) . '</a>';
	}

	return $notice;
}

/**
 * Build the installation notice when WooCommerce is missing.
 *
 * @internal
 *
 * @param string $minimum Minimum required WooCommerce version.
 * @return string Notice HTML.
 */
function purple_get_woocommerce_missing_notice( string $minimum ): string {
	$notice = esc_html(
		sprintf(
			/* translators: %s: Minimum WooCommerce version. */
			__( 'For the best experience with Purple, we recommend installing and activating WooCommerce %s or newer.', 'purple' ),
			$minimum
		)
	);
	if ( current_user_can( 'install_plugins' ) ) {
		$notice .= ' <a href="' . esc_url( self_admin_url( 'plugin-install.php?s=WooCommerce&tab=search&type=term' ) ) . '">' . esc_html__( 'Install WooCommerce', 'purple' ) . '</a>';
	}

	return $notice;
}

/**
 * Display the compatibility recommendation using the standard WordPress notice UI.
 *
 * @internal
 *
 * @return void
 */
function purple_woocommerce_admin_notice(): void {
	$notice = purple_get_woocommerce_notice();
	if ( '' !== $notice ) {
		wp_admin_notice(
			$notice,
			array(
				'id'          => 'purple-woocommerce-notice',
				'type'        => 'warning',
				'dismissible' => true,
			)
		);
	}
}
add_action( 'admin_notices', 'purple_woocommerce_admin_notice' );

/**
 * Load the persistence handler only when the notice is visible.
 *
 * @internal
 *
 * @return void
 */
function purple_enqueue_woocommerce_notice_script(): void {
	if ( '' === purple_get_woocommerce_notice() ) {
		return;
	}

	wp_enqueue_script(
		'purple-woocommerce-notice',
		get_template_directory_uri() . '/assets/js/woocommerce-notice.js',
		array( 'jquery', 'common' ),
		wp_get_theme( get_template() )->get( 'Version' ),
		true
	);
	wp_localize_script(
		'purple-woocommerce-notice',
		'purpleWooCommerceNotice',
		array(
			'nonce' => wp_create_nonce( 'purple_dismiss_woocommerce_notice' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'purple_enqueue_woocommerce_notice_script' );

/**
 * Remember the current user's dismissal of the WooCommerce recommendation.
 *
 * @internal
 *
 * @return void
 */
function purple_dismiss_woocommerce_notice(): void {
	check_ajax_referer( 'purple_dismiss_woocommerce_notice', 'nonce' );

	update_user_meta( get_current_user_id(), 'purple_woocommerce_notice_dismissed', true );
	wp_send_json_success();
}
add_action( 'wp_ajax_purple_dismiss_woocommerce_notice', 'purple_dismiss_woocommerce_notice' );
