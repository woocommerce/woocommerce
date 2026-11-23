<?php
/**
 * Tax rate ability trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Reads, formats and writes tax rates for the tax abilities, with the field names of tax-rates-query.
 */
trait TaxRateAbilityTrait {

	/**
	 * Get the stored value of a tax class slug.
	 *
	 * @param string $slug Tax class slug; standard for the standard rates.
	 * @return string|null Empty for the standard rates, or null when there is no such class.
	 */
	private static function get_stored_tax_class( string $slug ): ?string {
		$slug = sanitize_title( $slug );
		if ( 'standard' === $slug ) {
			return '';
		}

		return in_array( $slug, \WC_Tax::get_tax_class_slugs(), true ) ? $slug : null;
	}

	/**
	 * Get the error for a tax class slug that no class has.
	 *
	 * @param string $slug Tax class slug.
	 * @return \WP_Error
	 */
	private static function get_tax_class_not_found_error( string $slug ): \WP_Error {
		return new \WP_Error(
			'woocommerce_ability_tax_rate_class_not_found',
			/* translators: 1: tax class slug, 2: comma-separated tax class slugs. */
			sprintf( __( 'There is no tax class %1$s. Use one of: %2$s.', 'woocommerce' ), $slug, implode( ', ', array_merge( array( 'standard' ), \WC_Tax::get_tax_class_slugs() ) ) ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Get a tax rate as the abilities return it.
	 *
	 * @param int $rate_id Tax rate ID.
	 * @return array|\WP_Error
	 */
	private static function get_rate_or_error( int $rate_id ) {
		$rate = \WC_Tax::_get_tax_rate( $rate_id, OBJECT );
		if ( ! $rate instanceof \stdClass ) {
			return new \WP_Error(
				'woocommerce_ability_tax_rate_not_found',
				/* translators: %d: tax rate ID. */
				sprintf( __( 'There is no tax rate with ID %d.', 'woocommerce' ), $rate_id ),
				array( 'status' => 404 )
			);
		}

		return self::format_rate_for_response( $rate, self::get_locations( array( $rate_id ) )[ $rate_id ] ?? array() );
	}

	/**
	 * Map the sent rate fields to tax rate table columns. Unsent fields are left out.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	private static function get_rate_columns( array $input ) {
		$columns = array();

		if ( isset( $input['rate'] ) ) {
			$columns['tax_rate'] = wc_format_decimal( $input['rate'] );
		}
		if ( isset( $input['country'] ) ) {
			$columns['tax_rate_country'] = strtoupper( $input['country'] );
		}
		if ( isset( $input['state'] ) ) {
			$columns['tax_rate_state'] = wc_clean( $input['state'] );
		}
		if ( isset( $input['name'] ) ) {
			$columns['tax_rate_name'] = wc_clean( $input['name'] );
		}
		if ( isset( $input['priority'] ) ) {
			$columns['tax_rate_priority'] = $input['priority'];
		}
		if ( isset( $input['compound'] ) ) {
			$columns['tax_rate_compound'] = $input['compound'] ? 1 : 0;
		}
		if ( isset( $input['shipping'] ) ) {
			$columns['tax_rate_shipping'] = $input['shipping'] ? 1 : 0;
		}
		if ( isset( $input['class'] ) ) {
			$tax_class = self::get_stored_tax_class( $input['class'] );
			if ( null === $tax_class ) {
				return self::get_tax_class_not_found_error( $input['class'] );
			}
			$columns['tax_rate_class'] = $tax_class;
		}

		return $columns;
	}

	/**
	 * Check the country and state a rate will have once the sent columns are written. Only a sent
	 * country or state is checked, so a stored rate can still change its other fields. A state is
	 * free text for a country with no state list, since checkout matches what the customer typed.
	 *
	 * @param array $columns Columns from get_rate_columns().
	 * @param array $current The rate as stored, empty on create.
	 * @return \WP_Error|null
	 */
	private static function check_rate_region( array $columns, array $current = array() ): ?\WP_Error {
		if ( ! isset( $columns['tax_rate_country'] ) && ! isset( $columns['tax_rate_state'] ) ) {
			return null;
		}

		$country    = $columns['tax_rate_country'] ?? $current['country'] ?? '';
		$sent_state = $columns['tax_rate_state'] ?? $current['state'] ?? '';
		// WC_Tax stores the state in this form.
		$state = strtoupper( sanitize_key( $sent_state ) );

		if ( '' === $country ) {
			return null;
		}

		if ( ! array_key_exists( $country, WC()->countries->get_countries() ) ) {
			return new \WP_Error(
				'woocommerce_ability_tax_rate_country_invalid',
				/* translators: %s: country code. */
				sprintf( __( '"%s" is not a valid country. Send an ISO 3166-1 alpha-2 country code, such as GB, or empty for every country.', 'woocommerce' ), $country ),
				array( 'status' => 400 )
			);
		}

		$states = WC()->countries->get_states( $country );
		if ( '' !== $state && ! empty( $states ) && ! array_key_exists( $state, $states ) ) {
			return new \WP_Error(
				'woocommerce_ability_tax_rate_state_invalid',
				/* translators: 1: state sent or kept from the rate, 2: country code, 3: comma-separated state codes. */
				sprintf( __( '"%1$s" is not a state of %2$s. Send one of these codes, or empty for every state: %3$s.', 'woocommerce' ), $sent_state, $country, implode( ', ', array_keys( $states ) ) ),
				array( 'status' => 400 )
			);
		}

		return null;
	}

	/**
	 * Clean the sent postcodes and cities as the tax settings screen does; unsent lists are left out.
	 * Postcodes lose their spaces and hyphens, since checkout only matches a spaced postcode in
	 * countries whose format has the space. WC_Tax drops an entry that cleans to nothing or *,
	 * which would lift the whole restriction, so such an entry is refused.
	 *
	 * @param array $input Ability input.
	 * @return array{postcodes?: string[], cities?: string[]}|\WP_Error
	 */
	private static function get_rate_locations( array $input ) {
		$locations = array();
		if ( isset( $input['postcodes'] ) ) {
			$locations['postcodes'] = array_map( 'wc_normalize_postcode', array_map( 'sanitize_text_field', $input['postcodes'] ) );
		}
		if ( isset( $input['cities'] ) ) {
			$locations['cities'] = array_map( 'sanitize_text_field', $input['cities'] );
		}

		foreach ( $locations as $field => $values ) {
			$refused = array_intersect( $values, array( '', '*' ) );
			if ( $refused ) {
				return new \WP_Error(
					'woocommerce_ability_tax_rate_location_empty',
					/* translators: 1: postcodes or cities, 2: zero-based position of the refused entry. */
					sprintf( __( '%1$s[%2$d] needs a letter or number once HTML tags and percent-encoded characters are removed.', 'woocommerce' ), $field, key( $refused ) ),
					array( 'status' => 400 )
				);
			}
		}

		return $locations;
	}

	/**
	 * Replace the postcodes and cities from get_rate_locations(). An empty list clears them.
	 * WC_Tax deletes the old entries before it inserts the new ones, so a failed write can lift the restriction.
	 *
	 * @param int   $rate_id   Tax rate ID.
	 * @param array $locations Cleaned postcodes and cities.
	 * @return \WP_Error|null
	 */
	private static function write_rate_locations( int $rate_id, array $locations ): ?\WP_Error {
		global $wpdb;

		// No hook or other query runs between a location write and its check, so last_error is the write's.
		if ( isset( $locations['postcodes'] ) ) {
			\WC_Tax::_update_tax_rate_postcodes( $rate_id, implode( ';', $locations['postcodes'] ) );
			if ( '' !== $wpdb->last_error ) {
				return self::get_locations_failed_error( $rate_id, 'postcodes' );
			}
		}
		if ( isset( $locations['cities'] ) ) {
			\WC_Tax::_update_tax_rate_cities( $rate_id, implode( ';', $locations['cities'] ) );
			if ( '' !== $wpdb->last_error ) {
				return self::get_locations_failed_error( $rate_id, 'cities' );
			}
		}

		return null;
	}

	/**
	 * Get the error for postcodes or cities that were not saved, after the rate itself was.
	 *
	 * @param int    $rate_id Tax rate ID.
	 * @param string $field   postcodes or cities.
	 * @return \WP_Error
	 */
	private static function get_locations_failed_error( int $rate_id, string $field ): \WP_Error {
		return new \WP_Error(
			'woocommerce_ability_tax_rate_locations_failed',
			/* translators: 1: tax rate ID, 2: postcodes or cities. */
			sprintf( __( 'Tax rate %1$d was saved, but its %2$s were not, so it may now apply everywhere. Send the %2$s again with tax-rate-update.', 'woocommerce' ), $rate_id, $field ),
			array(
				'status'  => 500,
				'rate_id' => $rate_id,
			)
		);
	}

	/**
	 * Saved cart shipping rates carry their taxes, so they must be recalculated after any rate change.
	 */
	private static function refresh_shipping_rates_cache(): void {
		\WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	/**
	 * Get the input schema of the fields that tax-rate-create and tax-rate-update write.
	 *
	 * @return array
	 */
	private static function get_rate_write_input_properties(): array {
		$list = array(
			'type'  => 'array',
			'items' => array(
				'type'      => 'string',
				// WC_Tax drops an entry that is empty or * once trimmed, which would lift the whole restriction.
				'pattern'   => '^[^;]*[^\s;*-][^;]*$',
				'maxLength' => 200,
			),
		);

		return array(
			'rate'      => array(
				'type'        => 'string',
				'description' => __( 'Percentage as a decimal string, such as "19" or "7.25".', 'woocommerce' ),
				'pattern'     => '^([0-9]{1,3}|[0-9]{0,3}\.[0-9]{1,4})$',
			),
			'country'   => array(
				'type'        => 'string',
				'description' => __( 'Two-letter country code, or empty for every country.', 'woocommerce' ),
				'pattern'     => '^([A-Za-z]{2})?$',
			),
			'state'     => array(
				'type'        => 'string',
				'description' => __( 'State code such as CA, or empty for every state. For a country with no state list, the state as customers enter it.', 'woocommerce' ),
				'maxLength'   => 200,
			),
			'postcodes' => $list + array(
				'description' => __( 'Postcodes the rate is limited to. Takes ranges such as 10000...19999 and wildcards such as 10*. Empty for every postcode.', 'woocommerce' ),
			),
			'cities'    => $list + array(
				'description' => __( 'Cities the rate is limited to. Empty for every city.', 'woocommerce' ),
			),
			'name'      => array(
				'type'        => 'string',
				'description' => __( 'Name shown on the cart, checkout and invoices. Empty saves the name Tax.', 'woocommerce' ),
				'maxLength'   => 200,
			),
			'priority'  => array(
				'type'        => 'integer',
				'description' => __( 'Only one matching rate per priority applies. Use a different priority to stack rates.', 'woocommerce' ),
				'minimum'     => 0,
			),
			'compound'  => array(
				'type'        => 'boolean',
				'description' => __( 'Whether the rate applies on top of the other taxes.', 'woocommerce' ),
			),
			'shipping'  => array(
				'type'        => 'boolean',
				'description' => __( 'Whether the rate also applies to shipping.', 'woocommerce' ),
			),
			'class'     => array(
				'type'        => 'string',
				'description' => __( 'Tax class slug; standard for the standard rates.', 'woocommerce' ),
			),
		);
	}

	/**
	 * Get the postcodes and cities of the given tax rates in one query.
	 *
	 * @param int[] $rate_ids Tax rate IDs.
	 * @return array<int, array{postcodes?: string[], cities?: string[]}> Locations keyed by tax rate ID.
	 */
	private static function get_locations( array $rate_ids ): array {
		global $wpdb;

		if ( empty( $rate_ids ) ) {
			return array();
		}

		$placeholders = implode( ', ', array_fill( 0, count( $rate_ids ), '%d' ) );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- The placeholders are generated for each ID.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tax_rate_id, location_code, location_type FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE tax_rate_id IN ({$placeholders}) ORDER BY location_id",
				$rate_ids
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$locations = array();
		foreach ( $rows as $row ) {
			$key = 'postcode' === $row->location_type ? 'postcodes' : ( 'city' === $row->location_type ? 'cities' : null );
			if ( null !== $key ) {
				$locations[ (int) $row->tax_rate_id ][ $key ][] = (string) $row->location_code;
			}
		}

		return $locations;
	}

	/**
	 * Format a tax rate row.
	 *
	 * @param \stdClass $rate      Row of the tax rates table.
	 * @param array     $locations Postcodes and cities of the rate.
	 * @return array
	 */
	private static function format_rate_for_response( \stdClass $rate, array $locations ): array {
		return array(
			'id'        => (int) $rate->tax_rate_id,
			'country'   => (string) $rate->tax_rate_country,
			'state'     => (string) $rate->tax_rate_state,
			'postcodes' => $locations['postcodes'] ?? array(),
			'cities'    => $locations['cities'] ?? array(),
			'rate'      => (string) $rate->tax_rate,
			'name'      => (string) $rate->tax_rate_name,
			'priority'  => (int) $rate->tax_rate_priority,
			'compound'  => (bool) $rate->tax_rate_compound,
			'shipping'  => (bool) $rate->tax_rate_shipping,
			'class'     => '' === (string) $rate->tax_rate_class ? 'standard' : (string) $rate->tax_rate_class,
		);
	}

	/**
	 * Get the schema of a tax rate in a response.
	 *
	 * @return array
	 */
	private static function get_rate_output_schema(): array {
		$list = array(
			'type'  => 'array',
			'items' => array( 'type' => 'string' ),
		);

		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'        => array( 'type' => 'integer' ),
				'country'   => array(
					'type'        => 'string',
					'description' => __( 'Empty for every country.', 'woocommerce' ),
				),
				'state'     => array( 'type' => 'string' ),
				'postcodes' => $list,
				'cities'    => $list,
				'rate'      => array(
					'type'        => 'string',
					'description' => __( 'Percentage as a decimal string.', 'woocommerce' ),
				),
				'name'      => array( 'type' => 'string' ),
				'priority'  => array( 'type' => 'integer' ),
				'compound'  => array( 'type' => 'boolean' ),
				'shipping'  => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the rate also applies to shipping.', 'woocommerce' ),
				),
				'class'     => array( 'type' => 'string' ),
			),
			'additionalProperties' => false,
		);
	}
}
