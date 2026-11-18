<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use WC_Shipping_Zone;
use WC_Shipping_Zones;

/**
 * Builds shipping destinations from the store's shipping zones.
 *
 * @internal
 */
class ShippingDestinations {
	/** Maximum postcode rules across all zone and country combinations. */
	private const MAX_POSTCODE_RULE_COUNTRY_PAIRS = 10000;

	/** Maximum number of postcodes to expand from ranges. */
	private const MAX_RANGE_POSTCODES = 1000;

	/**
	 * Get destinations that can be described without widening a zone.
	 *
	 * @since 11.4.0
	 *
	 * @return array Shipping details.
	 */
	public function get_shipping_details(): array {
		if ( ! wc_shipping_enabled() || has_filter( 'woocommerce_get_zone_criteria' ) ) {
			return array();
		}

		$country_service = WC()->countries;
		if ( ! $country_service instanceof \WC_Countries ) {
			return array();
		}
		$countries  = $country_service->get_shipping_countries();
		$continents = $country_service->get_continents();
		if ( ! is_array( $countries ) || ! is_array( $continents ) ) {
			return array();
		}
		$countries = array_filter(
			$countries,
			static function ( $code ) {
				return is_string( $code ) && 1 === preg_match( '/^[A-Z]{2}$/D', $code );
			},
			ARRAY_FILTER_USE_KEY
		);
		if ( ! $countries ) {
			return array();
		}
		$country_continents = array();
		foreach ( $continents as $continent_code => $continent ) {
			$members = $continent['countries'] ?? array();
			if ( is_array( $members ) ) {
				foreach ( $members as $country ) {
					if ( is_string( $country ) && ! isset( $country_continents[ $country ] ) ) {
						$country_continents[ $country ] = $continent_code;
					}
				}
			}
		}

		$zones    = WC_Shipping_Zones::get_shipping_zones();
		$zones[0] = new WC_Shipping_Zone( 0 );

		$details             = array();
		$claimed             = array();
		$postcode_rule_count = 0;
		// Limit the size of the generated markup when expanding postcode ranges.
		$range_count = 0;
		foreach ( $zones as $zone ) {
			$locations = $zone->get_zone_locations( 'edit' );
			if ( ! is_array( $locations ) ) {
				return array();
			}
			$areas     = array();
			$postcodes = array();
			$has_area  = false;
			foreach ( $locations as $location ) {
				if ( ! is_object( $location ) || ! isset( $location->type, $location->code ) || ! is_string( $location->code ) ) {
					return array();
				}
				$code = $location->code;
				switch ( $location->type ) {
					case 'postcode':
						$postcodes[] = $code;
						break;
					case 'country':
						$has_area = true;
						if ( isset( $countries[ $code ] ) ) {
							$areas[ $code ] = array( '*' => true );
						}
						break;
					case 'state':
						$has_area = true;
						$parts    = explode( ':', $code, 2 );
						if ( 2 === count( $parts ) && isset( $countries[ $parts[0] ] ) && '' !== $parts[1] && ! isset( $areas[ $parts[0] ]['*'] ) ) {
							$areas[ $parts[0] ][ $parts[1] ] = true;
						}
						break;
					case 'continent':
						$has_area = true;
						$members  = $continents[ $code ]['countries'] ?? array();
						if ( ! is_array( $members ) ) {
							return array();
						}
						foreach ( $members as $country ) {
							if ( is_string( $country ) && isset( $countries[ $country ] ) && ( $country_continents[ $country ] ?? null ) === $code ) {
								$areas[ $country ] = array( '*' => true );
							}
						}
						break;
					default:
						// A custom location type cannot be mapped without its matching rules.
						return array();
				}
			}
			if ( ! $has_area ) {
				foreach ( $countries as $country => $name ) {
					$areas[ $country ] = array( '*' => true );
				}
			}
			$postcode_rule_count += count( $postcodes ) * count( $areas );
			if ( $postcode_rule_count > self::MAX_POSTCODE_RULE_COUNTRY_PAIRS ) {
				return array();
			}
			$postcode_patterns = array();
			$postcode_ranges   = array();
			foreach ( $postcodes as $postcode ) {
				if ( false !== strpos( $postcode, '...' ) ) {
					$postcode_ranges[] = (object) array(
						'zone_id'       => 1,
						'location_code' => $postcode,
					);
				} else {
					$postcode_patterns[ $postcode ] = true;
				}
			}

			$destinations = array();
			foreach ( $areas as $country => $states ) {
				$restrictions = array();
				foreach ( $postcodes as $postcode ) {
					$restrictions[] = $this->get_postcode_restriction( $postcode, $country, $range_count, count( $states ) );
				}
				foreach ( array_keys( $states ) as $state ) {
					$state    = (string) $state;
					$previous = $this->get_claims( $claimed[ $country ] ?? array(), $state );
					if ( ! isset( $claimed[ $country ][ $state ] ) ) {
						$claimed[ $country ][ $state ] = array(
							'all'      => false,
							'patterns' => array(),
							'ranges'   => array(),
						);
					}
					$region = array(
						'@type'          => 'DefinedRegion',
						'addressCountry' => $country,
					);
					if ( '*' !== $state ) {
						$region['addressRegion'] = $state;
					}
					if ( ! $postcodes ) {
						if ( ! $previous ) {
							$destinations[] = $region;
						} elseif ( '*' === $state ) {
							$known_states = $country_service->get_states( $country );
							if ( is_array( $known_states ) ) {
								foreach ( array_keys( $known_states ) as $known_state ) {
									$known_state = (string) $known_state;
									if ( '' !== $known_state && '*' !== $known_state && ! $this->get_claims( $previous, $known_state ) ) {
										$destinations[] = $region + array( 'addressRegion' => $known_state );
									}
								}
							}
						}
						$claimed[ $country ][ $state ]['all'] = true;
						continue;
					}
					// Earlier zones take precedence even when they have no shipping methods.
					$claimed[ $country ][ $state ]['patterns'] += $postcode_patterns;
					$claimed[ $country ][ $state ]['ranges']    = array_merge( $claimed[ $country ][ $state ]['ranges'], $postcode_ranges );
					// A postcode alone may include addresses outside the state.
					if ( '*' !== $state ) {
						continue;
					}
					$exact = array();
					foreach ( $restrictions as $restriction ) {
						if ( ! $previous && ( isset( $restriction['postalCodePrefix'] ) || isset( $restriction['postalCodeRange'] ) ) ) {
							$destinations[] = $region + $restriction;
						}
						$codes = $restriction['postalCodes'] ?? ( isset( $restriction['postalCode'] ) ? array( $restriction['postalCode'] ) : array() );
						foreach ( $codes as $code ) {
							if ( ! $this->postcode_is_claimed( $previous, $code, $country ) ) {
								$exact[] = $code;
							}
						}
					}
					if ( $exact ) {
						$region['postalCode'] = array_values( array_unique( $exact ) );
						$destinations[]       = $region;
					}
				}
			}

			if ( ! $destinations ) {
				continue;
			}
			$methods = $zone->get_shipping_methods( true );
			if ( ! is_array( $methods ) ) {
				continue;
			}
			foreach ( $methods as $method ) {
				if ( ! $method instanceof \WC_Shipping_Method || ! $method->is_enabled() || 'local_pickup' === $method->id || $method->supports( 'local-pickup' ) ) {
					continue;
				}
				$details[] = array(
					'@type'               => 'OfferShippingDetails',
					'shippingDestination' => $destinations,
				);
				break;
			}
		}
		return $details;
	}

	/**
	 * Get earlier claims that could overlap a country or state.
	 *
	 * @param array  $country_claims Earlier claims in the country.
	 * @param string $state State code or * for the whole country.
	 * @return array Earlier claims.
	 */
	private function get_claims( array $country_claims, string $state ): array {
		if ( '*' === $state ) {
			return $country_claims;
		}
		return array_intersect_key(
			$country_claims,
			array(
				'*'    => true,
				$state => true,
			)
		);
	}

	/**
	 * Check whether an earlier zone might match a postcode.
	 *
	 * @param array  $claims Earlier claims.
	 * @param string $postcode Postcode.
	 * @param string $country Country code.
	 * @return bool
	 */
	private function postcode_is_claimed( array $claims, string $postcode, string $country ): bool {
		$candidates = null;
		foreach ( $claims as $claim ) {
			if ( ! empty( $claim['all'] ) ) {
				return true;
			}
			if ( $claim['patterns'] ) {
				if ( null === $candidates ) {
					// Use the same postcode candidates as WooCommerce's zone matcher.
					$candidates = array_map( 'wc_clean', wc_get_wildcard_postcodes( wc_normalize_postcode( $postcode ), $country ) );
				}
				foreach ( $candidates as $candidate ) {
					if ( isset( $claim['patterns'][ $candidate ] ) ) {
						return true;
					}
				}
			}
			if ( $claim['ranges'] && wc_postcode_location_matcher( $postcode, $claim['ranges'], 'zone_id', 'location_code', $country ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Translate supported postcode rules into schema.org properties.
	 *
	 * @param string $postcode Zone postcode rule.
	 * @param string $country Country code.
	 * @param int    $range_count Postcodes expanded from ranges so far.
	 * @param int    $state_count Number of states receiving the postcodes.
	 * @return array Postcode property, or an empty array when unsupported.
	 */
	private function get_postcode_restriction( string $postcode, string $country, int &$range_count, int $state_count ): array {
		$postcode = trim( $postcode );
		if ( ! has_filter( 'woocommerce_format_postcode' ) ) {
			if ( in_array( $country, array( 'US', 'PR', 'MN' ), true ) && preg_match( '/^([0-9]{1,5})\*$/D', $postcode, $matches ) ) {
				return array( 'postalCodePrefix' => $matches[1] );
			}
			if ( 'GB' === $country && preg_match( '/^([A-Z]{1,2}[0-9][A-Z0-9]? )\*$/D', $postcode, $matches ) ) {
				return array( 'postalCodePrefix' => $matches[1] );
			}
		}
		if ( preg_match( '/^([0-9]{1,9})\.\.\.([0-9]{1,9})$/D', $postcode, $matches ) ) {
			$start = (int) $matches[1];
			$end   = (int) $matches[2];
			$count = $end - $start + 1;
			if ( $count > 1 && $range_count + $count * $state_count > self::MAX_RANGE_POSTCODES && in_array( $country, array( 'AU', 'NZ' ), true ) && 4 === strlen( $matches[1] ) && 4 === strlen( $matches[2] ) && ! has_filter( 'woocommerce_format_postcode' ) ) {
				return array(
					'postalCodeRange' => array(
						'@type'           => 'PostalCodeRangeSpecification',
						'postalCodeBegin' => $matches[1],
						'postalCodeEnd'   => $matches[2],
					),
				);
			}
			if ( $count <= 0 || $range_count + $count * $state_count > self::MAX_RANGE_POSTCODES ) {
				return array();
			}
			$range_count += $count * $state_count;
			$codes        = array();
			$rule         = (object) array( 'code' => $postcode );
			for ( $number = $start; $number <= $end; $number++ ) {
				$code      = str_pad( (string) $number, strlen( $matches[1] ), '0', STR_PAD_LEFT );
				$formatted = wc_format_postcode( $code, $country );
				if ( is_string( $formatted ) && wc_normalize_postcode( $formatted ) === $code && wc_postcode_location_matcher( $formatted, array( $rule ), 'code', 'code', $country ) ) {
					$codes[] = $formatted;
				}
			}
			return array( 'postalCodes' => $codes );
		}
		if ( preg_match( '/^[A-Z0-9][A-Z0-9 -]*$/D', $postcode ) ) {
			$normalized = wc_normalize_postcode( $postcode );
			$formatted  = wc_format_postcode( $postcode, $country );
			return is_string( $formatted ) && '' !== $formatted && wc_normalize_postcode( $formatted ) === $normalized && in_array( $postcode, array( $normalized, $formatted ), true ) ? array( 'postalCode' => $formatted ) : array();
		}
		return array();
	}
}
