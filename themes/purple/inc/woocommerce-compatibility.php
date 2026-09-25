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
	if ( ! current_user_can( 'manage_options' ) || ! $screen || 'themes' !== $screen->id ) {
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
			__( 'Purple requires WooCommerce %1$s or newer for its store features. You are running WooCommerce %2$s. Please update WooCommerce.', 'purple' ),
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
			__( 'Purple requires WooCommerce %s or newer for its store features. Please activate WooCommerce.', 'purple' ),
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
			__( 'Purple requires WooCommerce %s or newer for its store features. Please install and activate WooCommerce.', 'purple' ),
			$minimum
		)
	);
	if ( current_user_can( 'install_plugins' ) ) {
		$notice .= ' <a href="' . esc_url( self_admin_url( 'plugin-install.php?s=WooCommerce&tab=search&type=term' ) ) . '">' . esc_html__( 'Install WooCommerce', 'purple' ) . '</a>';
	}

	return $notice;
}

/**
 * Display the compatibility warning using the standard WordPress notice UI.
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
				'id'   => 'purple-woocommerce-notice',
				'type' => 'warning',
			)
		);
	}
}
add_action( 'admin_notices', 'purple_woocommerce_admin_notice' );
