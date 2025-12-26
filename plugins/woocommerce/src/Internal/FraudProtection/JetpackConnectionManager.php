<?php
/**
 * JetpackConnectionManager class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\Jetpack\JetpackConnection;

defined( 'ABSPATH' ) || exit;

/**
 * Manages Jetpack connection status and validation for fraud protection.
 *
 * Provides centralized methods to check connection status, validate requirements,
 * and handle connection-related errors gracefully.
 *
 * @since 10.5.0
 */
class JetpackConnectionManager {

	/**
	 * Get the Jetpack blog ID.
	 *
	 * @return int|null Blog ID if available, null otherwise.
	 */
	public function get_blog_id(): ?int {
		// Get blog ID from Jetpack options.
		$blog_id = \Jetpack_Options::get_option( 'id' );

		return $blog_id ? (int) $blog_id : null;
	}

	/**
	 * Get connection status with detailed error information.
	 *
	 * Returns an array with connection status and any error details.
	 *
	 * @return array {
	 *     Connection status information.
	 *
	 *     @type bool   $connected    Whether the site is connected.
	 *     @type string $error        Error message if not connected.
	 *     @type string $error_code   Error code if not connected.
	 *     @type int    $blog_id      Blog ID if available.
	 * }
	 */
	public function get_connection_status(): array {
		$status = array(
			'connected'  => false,
			'error'      => '',
			'error_code' => '',
			'blog_id'    => null,
		);

		// Check if connected.
		if ( ! JetpackConnection::get_manager()->is_connected() ) {
			$status['error']      = __( 'Site is not connected to WordPress.com. Please connect your site to enable fraud protection.', 'woocommerce' );
			$status['error_code'] = 'not_connected';
			return $status;
		}

		// Get blog ID.
		$blog_id = $this->get_blog_id();
		if ( ! $blog_id ) {
			$status['error']      = __( 'Jetpack blog ID not found. Please reconnect your site to WordPress.com.', 'woocommerce' );
			$status['error_code'] = 'no_blog_id';
			return $status;
		}

		// All checks passed.
		$status['connected'] = true;
		$status['blog_id']   = $blog_id;

		return $status;
	}

	/**
	 * Get the Jetpack authorization URL for connecting the site.
	 *
	 * @param string $redirect_url URL to redirect to after authorization.
	 * @return string|null Authorization URL or null on error.
	 */
	public function get_authorization_url( string $redirect_url = '' ): ?string {
		// If no redirect URL provided, use current admin URL.
		if ( empty( $redirect_url ) ) {
			$redirect_url = admin_url( 'admin.php?page=wc-settings&tab=advanced&section=features' );
		}

		$authorization_data = JetpackConnection::get_authorization_url( $redirect_url, 'woocommerce-fraud-protection' );

		if ( ! $authorization_data['success'] ) {
			FraudProtectionController::log(
				'error',
				'Failed to get Jetpack authorization URL.',
				$authorization_data['errors']
			);
			return null;
		}

		return $authorization_data['url'];
	}
}
