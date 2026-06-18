<?php
/**
 * WooPaymentsPlatformConnectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\Jetpack\JetpackConnection;

/**
 * Provider-owned readiness checks for WooPayments platform connection operations.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsPlatformConnectionService {

	/**
	 * Get cutover preflight failures for post-plugin platform connection operations.
	 *
	 * @return array<int,string> Failure codes.
	 */
	public function get_cutover_preflight_failures(): array {
		$failures = $this->get_connection_readiness_failures( true );

		return array_values(
			array_unique(
				array_filter(
					array_map( 'strval', $failures ),
					static fn( string $failure ): bool => '' !== $failure
				)
			)
		);
	}

	/**
	 * Get local WPCOM/Jetpack connection readiness failures.
	 *
	 * @param bool $require_user_token Whether connection-owner user-token readiness is required.
	 * @return array<int,string> Failure codes.
	 */
	protected function get_connection_readiness_failures( bool $require_user_token = false ): array {
		$failures = array();
		$manager  = $this->get_connection_manager();

		if ( null === $manager ) {
			return array( 'wpcom_connection_unavailable' );
		}

		try {
			if ( ! $manager->is_connected() ) {
				$failures[] = 'wpcom_connection_unavailable';
			}
		} catch ( \Throwable $e ) {
			$failures[] = 'wpcom_connection_unavailable';
		}

		if ( null === $this->get_blog_id() ) {
			$failures[] = 'wpcom_blog_id_unavailable';
		}

		$connection_owner_id = $this->get_connection_owner_id( $manager );
		if ( $connection_owner_id <= 0 ) {
			$failures[] = 'wpcom_connection_owner_unavailable';
		} elseif ( $require_user_token && ! $this->is_user_connected( $manager, $connection_owner_id ) ) {
			$failures[] = 'wpcom_connection_owner_user_token_unavailable';
		}

		return array_values( array_unique( $failures ) );
	}

	/**
	 * Get the Jetpack connection manager.
	 *
	 * @return JetpackConnectionManager|null
	 */
	protected function get_connection_manager(): ?JetpackConnectionManager {
		try {
			return JetpackConnection::get_manager();
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	/**
	 * Get the connected WPCOM blog ID.
	 *
	 * @return int|null
	 */
	protected function get_blog_id(): ?int {
		if ( ! class_exists( 'Jetpack_Options' ) ) {
			return null;
		}

		$blog_id = \Jetpack_Options::get_option( 'id' );

		return is_numeric( $blog_id ) && (int) $blog_id > 0 ? (int) $blog_id : null;
	}

	/**
	 * Get the connection owner ID.
	 *
	 * @param JetpackConnectionManager $manager Jetpack connection manager.
	 * @return int
	 */
	private function get_connection_owner_id( JetpackConnectionManager $manager ): int {
		try {
			return (int) $manager->get_connection_owner_id();
		} catch ( \Throwable $e ) {
			return 0;
		}
	}

	/**
	 * Tell whether the connection owner has a user token.
	 *
	 * @param JetpackConnectionManager $manager             Jetpack connection manager.
	 * @param int                      $connection_owner_id Connection owner user ID.
	 * @return bool
	 */
	private function is_user_connected( JetpackConnectionManager $manager, int $connection_owner_id ): bool {
		try {
			return (bool) $manager->is_user_connected( $connection_owner_id );
		} catch ( \Throwable $e ) {
			return false;
		}
	}
}
