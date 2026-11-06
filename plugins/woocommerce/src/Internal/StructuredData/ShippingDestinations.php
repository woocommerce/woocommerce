<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use WC_Shipping_Zone;
use WC_Shipping_Zones;

/**
 * Builds shipping destinations from the store's shipping zones.
 */
class ShippingDestinations {
	/**
	 * Get destinations that can be described without widening a zone.
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

		$details = array();
		$claimed = array();
		// Limit the size of the generated markup when expanding postcode ranges.
		$range_budget = 1000;
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
			$postcode_rules = array();
			foreach ( $postcodes as $postcode ) {
				$postcode_rules[] = (object) array(
					'zone_id'       => 1,
					'location_code' => $postcode,
				);
			}

			$destinations = array();
			foreach ( $areas as $country => $states ) {
				$restrictions    = array();
				$country_budget  = intdiv( $range_budget, count( $states ) );
				$starting_budget = $country_budget;
				foreach ( $postcodes as $postcode ) {
					$restrictions[] = $this->get_postcode_restriction( $postcode, $country, $country_budget );
				}
				$range_budget -= ( $starting_budget - $country_budget ) * count( $states );
				foreach ( array_keys( $states ) as $state ) {
					$state    = (string) $state;
					$previous = $this->get_claims( $claimed[ $country ] ?? array(), $state );
					if ( ! isset( $claimed[ $country ][ $state ] ) ) {
						$claimed[ $country ][ $state ] = array(
							'all'   => false,
							'rules' => array(),
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
					$exact = array();
					// Earlier zones take precedence even when they have no shipping methods.
					foreach ( $restrictions as $restriction ) {
						if ( isset( $restriction['postalCodePrefix'] ) && ! $previous ) {
							$destinations[] = $region + $restriction;
						}
						$codes = $restriction['postalCodes'] ?? ( isset( $restriction['postalCode'] ) ? array( $restriction['postalCode'] ) : array() );
						foreach ( $codes as $code ) {
							if ( ! $this->postcode_is_claimed( $previous, $code, $country ) ) {
								$exact[] = $code;
							}
						}
					}
					$claimed[ $country ][ $state ]['rules'] = array_merge( $claimed[ $country ][ $state ]['rules'], $postcode_rules );
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
		foreach ( $claims as $claim ) {
			if ( ! empty( $claim['all'] ) || wc_postcode_location_matcher( $postcode, $claim['rules'], 'zone_id', 'location_code', $country ) ) {
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
	 * @param int    $range_budget Remaining postcodes that can be expanded from ranges.
	 * @return array Postcode property, or an empty array when unsupported.
	 */
	private function get_postcode_restriction( string $postcode, string $country, int &$range_budget ): array {
		$postcode = trim( $postcode );
		// These countries insert a hyphen only after the first five characters.
		if ( in_array( $country, array( 'US', 'PR', 'MN' ), true ) && ! has_filter( 'woocommerce_format_postcode' ) && preg_match( '/^([0-9]{1,5})\*$/D', $postcode, $matches ) ) {
			return array( 'postalCodePrefix' => $matches[1] );
		}
		if ( preg_match( '/^([0-9]{1,9})\.\.\.([0-9]{1,9})$/D', $postcode, $matches ) && strlen( $matches[1] ) === strlen( $matches[2] ) && strcmp( $matches[1], $matches[2] ) <= 0 ) {
			$start = (int) $matches[1];
			$end   = (int) $matches[2];
			$count = $end - $start + 1;
			if ( $count > $range_budget ) {
				return array();
			}
			$range_budget -= $count;
			$codes         = array();
			for ( $number = $start; $number <= $end; $number++ ) {
				$code      = str_pad( (string) $number, strlen( $matches[1] ), '0', STR_PAD_LEFT );
				$formatted = wc_format_postcode( $code, $country );
				if ( is_string( $formatted ) && wc_normalize_postcode( $formatted ) === $code ) {
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
