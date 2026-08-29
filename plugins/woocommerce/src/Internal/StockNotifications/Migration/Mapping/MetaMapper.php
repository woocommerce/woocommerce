<?php
/**
 * MetaMapper class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping;

defined( 'ABSPATH' ) || exit;

/**
 * Maps one legacy notification's meta bag to the Core meta rows to write.
 *
 * Pure: no database access. Implements the Meta table in the migration plan. Migration
 * markers (`_wc_bis_legacy_id`, `_wc_bis_legacy_unsub_hash`) are owned by the migrator,
 * not this class.
 */
final class MetaMapper {

	/**
	 * Legacy meta keys carried across to Core unchanged.
	 *
	 * @var string[]
	 */
	private const CARRIED_KEYS = array( '_customer_locale', '_customer_location_data' );

	/**
	 * Map a legacy meta bag to the Core meta rows to write.
	 *
	 * Carries `_customer_locale`, `_customer_location_data` and `posted_attributes`
	 * across unchanged (unserialized), and drops `_hash_key`, `_hash_iv`,
	 * `awaiting_verification` and `_verification_*` along with any other legacy key
	 * not named here. The writer is the sole owner of `maybe_serialize()`; serializing
	 * here too would double-serialize the value once it reaches the writer.
	 *
	 * @param array<string,mixed> $legacy_meta Legacy meta bag, keyed by meta key.
	 * @return array<int,array{0:string,1:mixed}> Meta rows in Writer shape.
	 */
	public static function map( array $legacy_meta ): array {
		$rows = array();

		foreach ( self::CARRIED_KEYS as $key ) {
			if ( array_key_exists( $key, $legacy_meta ) ) {
				$rows[] = array( $key, $legacy_meta[ $key ] );
			}
		}

		if ( array_key_exists( 'posted_attributes', $legacy_meta ) ) {
			$rows[] = array( 'posted_attributes', $legacy_meta['posted_attributes'] );
		}

		return $rows;
	}
}
