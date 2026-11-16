<?php
/**
 * Unknown-key filtering shared by the public facades.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Support
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Drop unknown argument keys with a `_doing_it_wrong()` notice. Values are checked by
 * {@see \Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ArgumentValidator}.
 *
 * @internal Engine implementation detail shared by the `Api\` facades, not part of the public API.
 */
final class ArgumentFilter {

	/**
	 * Static helper only.
	 */
	private function __construct() {}

	/**
	 * Keep the keys in `$allowed`; each other key raises a `_doing_it_wrong()` notice and is dropped.
	 *
	 * @param string                   $method  Public facade method, for the notice.
	 * @param array<int|string, mixed> $args    Caller arguments.
	 * @param array<string, true>      $allowed Accepted keys, as a key map.
	 * @param string                   $what    What the keys belong to, for the notice.
	 * @return array<string, mixed> The arguments with known keys only.
	 */
	public static function filter_known_keys( string $method, array $args, array $allowed, string $what = 'key' ): array {
		$filtered = array();
		foreach ( $args as $key => $value ) {
			if ( isset( $allowed[ $key ] ) ) {
				$filtered[ (string) $key ] = $value;
				continue;
			}

			_doing_it_wrong(
				esc_html( $method ),
				sprintf( 'Unknown %s "%s" ignored.', esc_html( $what ), esc_html( (string) $key ) ),
				'0.0.1'
			);
		}

		return $filtered;
	}

	/**
	 * Apply {@see self::filter_known_keys()} to each row of a collection, keeping the row keys.
	 *
	 * @template TKey of array-key
	 * @param string                                $method  Public facade method, for the notice.
	 * @param array<TKey, array<int|string, mixed>> $rows    Rows, shape already checked.
	 * @param array<string, true>                   $allowed Accepted row keys, as a key map.
	 * @param string                                $what    What the keys belong to, for the notice.
	 * @return array<TKey, array<string, mixed>>
	 */
	public static function filter_known_keys_in_rows( string $method, array $rows, array $allowed, string $what = 'key' ): array {
		$filtered = array();
		foreach ( $rows as $index => $row ) {
			$filtered[ $index ] = self::filter_known_keys( $method, $row, $allowed, $what );
		}

		return $filtered;
	}
}
