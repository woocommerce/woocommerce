<?php
/**
 * Detects persisted session changes during checkout.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Utilities;

use WC_Session;

/**
 * Retains persisted session state most recently loaded or saved by each handler.
 *
 * @internal
 */
final class SessionConcurrencyGuard {

	/**
	 * Persisted session hashes keyed by session-handler object.
	 *
	 * @var \SplObjectStorage<WC_Session, string>|null
	 */
	private static $baseline_hashes;

	/**
	 * Capture persisted session data loaded or saved by a handler.
	 *
	 * @param WC_Session $session Session handler.
	 * @param array      $data Persisted session data that was loaded or saved.
	 */
	public static function capture( WC_Session $session, array $data ): void {
		self::get_baseline_hashes()[ $session ] = self::hash( $data );
	}

	/**
	 * Check whether persisted data changed after it was last loaded or saved by this handler.
	 *
	 * @param WC_Session $session Session handler.
	 * @return bool
	 */
	public static function has_changed( WC_Session $session ): bool {
		$baseline_hashes = self::get_baseline_hashes();

		if ( ! $baseline_hashes->contains( $session ) ) {
			return false;
		}

		if ( method_exists( $session, 'get_session_data_from_storage' ) ) {
			$persisted_data = $session->get_session_data_from_storage();
		} elseif ( method_exists( $session, 'get_session_data' ) ) {
			$persisted_data = $session->get_session_data();
		} else {
			return false;
		}

		return ! hash_equals( $baseline_hashes[ $session ], self::hash( (array) $persisted_data ) );
	}

	/**
	 * Get the persisted session hash registry.
	 *
	 * @return \SplObjectStorage<WC_Session, string>
	 */
	private static function get_baseline_hashes(): \SplObjectStorage {
		if ( ! self::$baseline_hashes ) {
			/**
			 * Persisted session hash registry.
			 *
			 * @var \SplObjectStorage<WC_Session, string> $baseline_hashes
			 */
			$baseline_hashes       = new \SplObjectStorage();
			self::$baseline_hashes = $baseline_hashes;
		}

		return self::$baseline_hashes;
	}

	/**
	 * Get a stable hash for persisted session data.
	 *
	 * @param array $data Session data to hash.
	 * @return string
	 */
	private static function hash( array $data ): string {
		return hash( 'sha256', maybe_serialize( $data ) );
	}
}
