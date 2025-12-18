<?php
/**
 * JetpackConnectionManager class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\Jetpack\Connection\Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Manages Jetpack connection status and validation for fraud protection.
 *
 * Provides centralized methods to check connection status, validate requirements,
 * and handle connection-related errors gracefully.
 *
 * @since 10.4.0
 */
class JetpackConnectionManager {

	/**
	 * Logger source identifier.
	 */
	private const LOGGER_SOURCE = 'woo-fraud-protection';

	/**
	 * Check if Jetpack Connection is available and properly configured.
	 *
	 * This method checks:
	 * 1. If Jetpack Connection class exists
	 * 2. If the site is connected to WordPress.com
	 * 3. If we can retrieve the blog ID
	 *
	 * @return bool True if connection is available and valid.
	 */
	public function is_connected(): bool {
		// Check if Jetpack Connection is available.
		if ( ! $this->is_jetpack_connection_available() ) {
			return false;
		}

		// Get connection manager instance.
		$manager = $this->get_connection_manager();
		if ( ! $manager ) {
			return false;
		}

		// Check if site is connected.
		return $manager->is_connected();
	}

	/**
	 * Check if Jetpack Connection class is available.
	 *
	 * @return bool True if Jetpack Connection class exists.
	 */
	public function is_jetpack_connection_available(): bool {
		return class_exists( Manager::class );
	}

	/**
	 * Get the Jetpack blog ID.
	 *
	 * @return int|null Blog ID if available, null otherwise.
	 */
	public function get_blog_id(): ?int {
		if ( ! $this->is_jetpack_connection_available() ) {
			return null;
		}

		// Get blog ID from Jetpack options.
		$blog_id = \Jetpack_Options::get_option( 'id' );

		return $blog_id ? (int) $blog_id : null;
	}

	/**
	 * Get connection manager instance.
	 *
	 * @return Manager|null Connection manager instance or null if not available.
	 */
	private function get_connection_manager(): ?Manager {
		if ( ! $this->is_jetpack_connection_available() ) {
			return null;
		}

		try {
			return new Manager( 'woocommerce' );
		} catch ( \Exception $e ) {
			$this->log_error(
				sprintf(
					'Failed to initialize Jetpack Connection Manager: %s',
					$e->getMessage()
				)
			);
			return null;
		}
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

		// Check if Jetpack Connection class exists.
		if ( ! $this->is_jetpack_connection_available() ) {
			$status['error']      = __( 'Jetpack Connection is not available. Please install and activate Jetpack.', 'woocommerce' );
			$status['error_code'] = 'jetpack_not_available';
			return $status;
		}

		// Get connection manager.
		$manager = $this->get_connection_manager();
		if ( ! $manager ) {
			$status['error']      = __( 'Failed to initialize Jetpack Connection Manager.', 'woocommerce' );
			$status['error_code'] = 'manager_init_failed';
			return $status;
		}

		// Check if connected.
		if ( ! $manager->is_connected() ) {
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
		if ( ! $this->is_jetpack_connection_available() ) {
			return null;
		}

		$manager = $this->get_connection_manager();
		if ( ! $manager ) {
			return null;
		}

		// If no redirect URL provided, use current admin URL.
		if ( empty( $redirect_url ) ) {
			$redirect_url = admin_url( 'admin.php?page=wc-settings&tab=advanced&section=features' );
		}

		try {
			// Use the OnboardingPlugins class to get authorization URL.
			if ( class_exists( '\Automattic\WooCommerce\Admin\API\OnboardingPlugins' ) ) {
				$request = new \WP_REST_Request();
				$request->set_param( 'redirect_url', $redirect_url );
				$plugin_onboarding = new \Automattic\WooCommerce\Admin\API\OnboardingPlugins();
				$result            = $plugin_onboarding->get_jetpack_authorization_url( $request );

				if ( ! empty( $result['url'] ) ) {
					// Customize the URL parameters for fraud protection.
					$url = add_query_arg(
						array(
							'redirect_uri' => $redirect_url,
							'from'         => 'woocommerce-fraud-protection',
							'plugin_name'  => 'woocommerce',
						),
						$result['url']
					);
					return $url;
				}
			}

			return null;
		} catch ( \Exception $e ) {
			$this->log_error(
				sprintf(
					'Failed to get Jetpack authorization URL: %s',
					$e->getMessage()
				)
			);
			return null;
		}
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Error message.
	 * @return void
	 */
	private function log_error( string $message ): void {
		$logger = wc_get_logger();
		$logger->error( $message, array( 'source' => self::LOGGER_SOURCE ) );
	}
}
