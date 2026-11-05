<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use WC_Data_Store;
use WC_Shipping_Zone;
use WC_Shipping_Zone_Data_Store;
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
		if ( ! function_exists( 'WC' ) || ! WC() || ! wc_shipping_enabled() ) {
			return array();
		}

		$components      = get_object_vars( WC() );
		$country_service = $components['countries'] ?? null;
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
		$store    = WC_Data_Store::load( 'shipping-zone' );
		$batched  = WC_Shipping_Zone_Data_Store::class === $store->get_current_class_name();
		if ( $batched ) {
			ShippingMethodBatch::begin( array_keys( $zones ) );
		}

		$details = array();
		$claimed = array();
		// Limit the size of the generated markup when expanding postcode ranges.
		$range_budget = 1000;
		$uncertain    = false;
		try {
			foreach ( $zones as $zone ) {
				// Checkout matches stored locations, while the view getter applies a filter.
				$unfiltered_locations = has_filter( 'woocommerce_shipping_zone_get_zone_locations' ) ? wp_json_encode( $zone->get_zone_locations( 'edit' ) ) : null;
				$locations            = $zone->get_zone_locations();
				if ( ! is_array( $locations ) ) {
					return $details;
				}
				if ( null !== $unfiltered_locations && ( false === $unfiltered_locations || wp_json_encode( $locations ) !== $unfiltered_locations ) ) {
					// Continue processing so later shipping filters still run.
					$uncertain = true;
				}
				$areas     = array();
				$postcodes = array();
				$has_area  = false;
				foreach ( $locations as $location ) {
					if ( ! is_object( $location ) || ! isset( $location->type, $location->code ) || ! is_string( $location->code ) ) {
						return $details;
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
									return $details;
							}
							foreach ( $members as $country ) {
								if ( is_string( $country ) && isset( $countries[ $country ] ) && ( $country_continents[ $country ] ?? null ) === $code ) {
									$areas[ $country ] = array( '*' => true );
								}
							}
							break;
						default:
							// A custom location type cannot be mapped without its matching rules.
							return $details;
					}
				}
				if ( ! $has_area ) {
					foreach ( $countries as $country => $name ) {
						$areas[ $country ] = array( '*' => true );
					}
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
								'all'     => false,
								'unknown' => false,
								'codes'   => array(),
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
								$normalized = wc_normalize_postcode( $code );
								if ( ! $this->postcode_is_claimed( $previous, $normalized ) ) {
									$exact[] = $code;
								}
								if ( isset( $restriction['postalCode'] ) ) {
									$claimed[ $country ][ $state ]['codes'][ $normalized ] = true;
								}
							}
							if ( ! isset( $restriction['postalCode'] ) ) {
								$claimed[ $country ][ $state ]['unknown'] = true;
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
					if ( ! $uncertain ) {
						$details[] = array(
							'@type'               => 'OfferShippingDetails',
							'shippingDestination' => $destinations,
						);
					}
					break;
				}
			}
		} finally {
			if ( $batched ) {
				ShippingMethodBatch::end();
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
	 * @param string $postcode Normalized postcode.
	 * @return bool
	 */
	private function postcode_is_claimed( array $claims, string $postcode ): bool {
		foreach ( $claims as $claim ) {
			if ( ! empty( $claim['all'] ) || ! empty( $claim['unknown'] ) || isset( $claim['codes'][ $postcode ] ) ) {
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
