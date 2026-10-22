<?php
/**
 * Personal data class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Decides whether Core's abilities return personal data, also known as PII
 * (personally identifiable information), such as customer emails and the
 * extension fields flagged as personal data. ExtensibleAbilities applies it to
 * every formatted object, so the abilities themselves do not check it.
 *
 * @since 11.4.0
 */
class PersonalData {

	/**
	 * Filter that switches personal data off for AI agents and other ability clients.
	 */
	public const FILTER = 'woocommerce_abilities_share_personal_data';

	/**
	 * Core fields that hold personal data, keyed by resource.
	 */
	private const FIELDS = array(
		'order' => array( 'billing_email' ),
	);

	/**
	 * Whether the abilities return the personal data of a resource. Any filter
	 * value other than true withholds it, so a broken callback fails closed.
	 *
	 * @param string $resource_name Resource being formatted, such as `order`.
	 * @return bool
	 */
	public static function is_shared( string $resource_name ): bool {
		/**
		 * Filters whether WooCommerce abilities return personal data.
		 *
		 * Return false to leave out customer details, such as billing emails, and
		 * the extension fields registered with `personal_data`.
		 *
		 * @since 11.4.0
		 *
		 * @param bool   $shared        Whether personal data is returned. Default true.
		 * @param string $resource_name Resource being formatted, such as `order`.
		 */
		return true === apply_filters( self::FILTER, true, $resource_name );
	}

	/**
	 * Null the personal data fields of a formatted object while personal data is withheld.
	 *
	 * @param array<string, mixed> $output        Formatted object.
	 * @param string               $resource_name Resource.
	 * @return array<string, mixed>
	 */
	public static function withhold_fields( array $output, string $resource_name ): array {
		if ( empty( self::FIELDS[ $resource_name ] ) || self::is_shared( $resource_name ) ) {
			return $output;
		}

		foreach ( self::FIELDS[ $resource_name ] as $field ) {
			if ( array_key_exists( $field, $output ) ) {
				$output[ $field ] = null;
			}
		}
		return $output;
	}
}
