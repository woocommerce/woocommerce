<?php
/**
 * Unknown-key filtering shared by the public facades.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Support
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Support;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;

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
	 * Drop unknown keys from contract item rows, with a notice per key.
	 *
	 * @param array<int, array<string, mixed>> $rows          Item rows, shape already checked.
	 * @param string                           $function_name Function named in the notice; defaults to this method.
	 * @return array<int, array<string, mixed>>
	 */
	public static function filter_contract_items( array $rows, string $function_name = __METHOD__ ): array {
		$allowed  = array_fill_keys( Contract::ITEM_FIELDS, true );
		$filtered = array();
		foreach ( $rows as $row ) {
			$filtered[] = self::filter_known_keys( $function_name, $row, $allowed, 'item key' );
		}

		return $filtered;
	}

	/**
	 * Drop unknown keys from contract addresses, with a notice per key.
	 *
	 * @param array<string, array<int|string, mixed>> $addresses     Addresses by type, shape already checked.
	 * @param string                                  $function_name Function named in the notice; defaults to this method.
	 * @return array<string, array<string, mixed>>
	 */
	public static function filter_contract_addresses( array $addresses, string $function_name = __METHOD__ ): array {
		$allowed  = array_fill_keys( Contract::ADDRESS_FIELDS, true );
		$filtered = array();
		foreach ( $addresses as $type => $address ) {
			$filtered[ $type ] = self::filter_known_keys( $function_name, $address, $allowed, 'address key' );
		}

		return $filtered;
	}
}
