<?php
/**
 * WooCommerce countries
 *
 * @package WooCommerce\l10n
 * @version 3.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The WooCommerce countries class stores country/state data.
 */
class WC_Countries {

	/**
	 * Locales list.
	 *
	 * @var array
	 */
	public $locale = array();

	/**
	 * List of address formats for locales.
	 *
	 * @var array
	 */
	public $address_formats = array();

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param  mixed $key Key.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( 'countries' === $key ) {
			return $this->get_countries();
		} elseif ( 'states' === $key ) {
			return $this->get_states();
		}
	}

	/**
	 * Get all countries.
	 *
	 * @return array
	 */
	public function get_countries() {
		if ( empty( $this->countries ) ) {
			$this->countries = apply_filters( 'woocommerce_countries', include WC()->plugin_path() . '/i18n/countries.php' );
			if ( apply_filters( 'woocommerce_sort_countries', true ) ) {
				uasort( $this->countries, 'wc_ascii_uasort_comparison' );
			}
		}

		return $this->countries;
	}

	/**
	 * Get all continents.
	 *
	 * @return array
	 */
	public function get_continents() {
		if ( empty( $this->continents ) ) {
			$this->continents = apply_filters( 'woocommerce_continents', include WC()->plugin_path() . '/i18n/continents.php' );
		}

		return $this->continents;
	}

	/**
	 * Get continent code for a country code.
	 *
	 * @since 2.6.0
	 * @param string $cc Country code.
	 * @return string
	 */
	public function get_continent_code_for_country( $cc ) {
		$cc                 = trim( strtoupper( $cc ) );
		$continents         = $this->get_continents();
		$continents_and_ccs = wp_list_pluck( $continents, 'countries' );
		foreach ( $continents_and_ccs as $continent_code => $countries ) {
			if ( false !== array_search( $cc, $countries, true ) ) {
				return $continent_code;
			}
		}

		return '';
	}

	/**
	 * Get calling code for a country code.
	 *
	 * @since 3.6.0
	 * @param string $cc Country code.
	 * @return string|array Some countries have multiple. The code will be stripped of - and spaces and always be prefixed with +.
	 */
	public function get_country_calling_code( $cc ) {
		$codes = wp_cache_get( 'calling-codes', 'countries' );

		if ( ! $codes ) {
			$codes = include WC()->plugin_path() . '/i18n/phone.php';
			wp_cache_set( 'calling-codes', $codes, 'countries' );
		}

		$calling_code = isset( $codes[ $cc ] ) ? $codes[ $cc ] : '';

		if ( is_array( $calling_code ) ) {
			$calling_code = $calling_code[0];
		}

		return $calling_code;
	}

	/**
	 * Get continents that the store ships to.
	 *
	 * @since 3.6.0
	 * @return array
	 */
	public function get_shipping_continents() {
		$continents             = $this->get_continents();
		$shipping_countries     = $this->get_shipping_countries();
		$shipping_country_codes = array_keys( $shipping_countries );
		$shipping_continents    = array();

		foreach ( $continents as $continent_code => $continent ) {
			if ( count( array_intersect( $continent['countries'], $shipping_country_codes ) ) ) {
				$shipping_continents[ $continent_code ] = $continent;
			}
		}

		return $shipping_continents;
	}

	/**
	 * Load the states.
	 *
	 * @deprecated 3.6.0 This method was used to load state files, but is no longer needed. @see get_states().
	 */
	public function load_country_states() {
		global $states;

		$states       = include WC()->plugin_path() . '/i18n/states.php';
		$this->states = apply_filters( 'woocommerce_states', $states );
	}

	/**
	 * Get the states for a country.
	 *
	 * @param  string $cc Country code.
	 * @return false|array of states
	 */
	public function get_states( $cc = null ) {
		if ( ! isset( $this->states ) ) {
			$this->states = apply_filters( 'woocommerce_states', include WC()->plugin_path() . '/i18n/states.php' );
		}

		if ( ! is_null( $cc ) ) {
			return isset( $this->states[ $cc ] ) ? $this->states[ $cc ] : false;
		} else {
			return $this->states;
		}
	}

	/**
	 * Get the base address (first line) for the store.
	 *
	 * @since 3.1.1
	 * @return string
	 */
	public function get_base_address() {
		$base_address = get_option( 'woocommerce_store_address', '' );
		return apply_filters( 'woocommerce_countries_base_address', $base_address );
	}

	/**
	 * Get the base address (second line) for the store.
	 *
	 * @since 3.1.1
	 * @return string
	 */
	public function get_base_address_2() {
		$base_address_2 = get_option( 'woocommerce_store_address_2', '' );
		return apply_filters( 'woocommerce_countries_base_address_2', $base_address_2 );
	}

	/**
	 * Get the base country for the store.
	 *
	 * @return string
	 */
	public function get_base_country() {
		$default = wc_get_base_location();
		return apply_filters( 'woocommerce_countries_base_country', $default['country'] );
	}

	/**
	 * Get the base state for the store.
	 *
	 * @return string
	 */
	public function get_base_state() {
		$default = wc_get_base_location();
		return apply_filters( 'woocommerce_countries_base_state', $default['state'] );
	}

	/**
	 * Get the base city for the store.
	 *
	 * @version 3.1.1
	 * @return string
	 */
	public function get_base_city() {
		$base_city = get_option( 'woocommerce_store_city', '' );
		return apply_filters( 'woocommerce_countries_base_city', $base_city );
	}

	/**
	 * Get the base postcode for the store.
	 *
	 * @since 3.1.1
	 * @return string
	 */
	public function get_base_postcode() {
		$base_postcode = get_option( 'woocommerce_store_postcode', '' );
		return apply_filters( 'woocommerce_countries_base_postcode', $base_postcode );
	}

	/**
	 * Get countries that the store sells to.
	 *
	 * @return array
	 */
	public function get_allowed_countries() {
		if ( 'all' === get_option( 'woocommerce_allowed_countries' ) ) {
			return apply_filters( 'woocommerce_countries_allowed_countries', $this->countries );
		}

		if ( 'all_except' === get_option( 'woocommerce_allowed_countries' ) ) {
			$except_countries = get_option( 'woocommerce_all_except_countries', array() );

			if ( ! $except_countries ) {
				return $this->countries;
			} else {
				$all_except_countries = $this->countries;
				foreach ( $except_countries as $country ) {
					unset( $all_except_countries[ $country ] );
				}
				return apply_filters( 'woocommerce_countries_allowed_countries', $all_except_countries );
			}
		}

		$countries = array();

		$raw_countries = get_option( 'woocommerce_specific_allowed_countries', array() );

		if ( $raw_countries ) {
			foreach ( $raw_countries as $country ) {
				$countries[ $country ] = $this->countries[ $country ];
			}
		}

		return apply_filters( 'woocommerce_countries_allowed_countries', $countries );
	}

	/**
	 * Get countries that the store ships to.
	 *
	 * @return array
	 */
	public function get_shipping_countries() {
		if ( '' === get_option( 'woocommerce_ship_to_countries' ) ) {
			return $this->get_allowed_countries();
		}

		if ( 'all' === get_option( 'woocommerce_ship_to_countries' ) ) {
			return $this->countries;
		}

		$countries = array();

		$raw_countries = get_option( 'woocommerce_specific_ship_to_countries' );

		if ( $raw_countries ) {
			foreach ( $raw_countries as $country ) {
				$countries[ $country ] = $this->countries[ $country ];
			}
		}

		return apply_filters( 'woocommerce_countries_shipping_countries', $countries );
	}

	/**
	 * Get allowed country states.
	 *
	 * @return array
	 */
	public function get_allowed_country_states() {
		if ( get_option( 'woocommerce_allowed_countries' ) !== 'specific' ) {
			return $this->states;
		}

		$states = array();

		$raw_countries = get_option( 'woocommerce_specific_allowed_countries' );

		if ( $raw_countries ) {
			foreach ( $raw_countries as $country ) {
				if ( isset( $this->states[ $country ] ) ) {
					$states[ $country ] = $this->states[ $country ];
				}
			}
		}

		return apply_filters( 'woocommerce_countries_allowed_country_states', $states );
	}

	/**
	 * Get shipping country states.
	 *
	 * @return array
	 */
	public function get_shipping_country_states() {
		if ( get_option( 'woocommerce_ship_to_countries' ) === '' ) {
			return $this->get_allowed_country_states();
		}

		if ( get_option( 'woocommerce_ship_to_countries' ) !== 'specific' ) {
			return $this->states;
		}

		$states = array();

		$raw_countries = get_option( 'woocommerce_specific_ship_to_countries' );

		if ( $raw_countries ) {
			foreach ( $raw_countries as $country ) {
				if ( ! empty( $this->states[ $country ] ) ) {
					$states[ $country ] = $this->states[ $country ];
				}
			}
		}

		return apply_filters( 'woocommerce_countries_shipping_country_states', $states );
	}

	/**
	 * Gets an array of countries in the EU.
	 *
	 * MC (monaco) and IM (isle of man, part of UK) also use VAT.
	 *
	 * @param  string $type Type of countries to retrieve. Blank for EU member countries. eu_vat for EU VAT countries.
	 * @return string[]
	 */
	public function get_european_union_countries( $type = '' ) {
		$countries = array( 'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HU', 'HR', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK' );

		if ( 'eu_vat' === $type ) {
			$countries[] = 'MC';
			$countries[] = 'IM';
		}

		return $countries;
	}

	/**
	 * Gets the correct string for shipping - either 'to the' or 'to'.
	 *
	 * @param string $country_code Country code.
	 * @return string
	 */
	public function shipping_to_prefix( $country_code = '' ) {
		$country_code = $country_code ? $country_code : WC()->customer->get_shipping_country();
		$countries    = array( 'GB', 'US', 'AE', 'CZ', 'DO', 'NL', 'PH', 'USAF' );
		$return       = in_array( $country_code, $countries, true ) ? __( 'to the', 'woocommerce' ) : __( 'to', 'woocommerce' );

		return apply_filters( 'woocommerce_countries_shipping_to_prefix', $return, $country_code );
	}

	/**
	 * Prefix certain countries with 'the'.
	 *
	 * @param string $country_code Country code.
	 * @return string
	 */
	public function estimated_for_prefix( $country_code = '' ) {
		$country_code = $country_code ? $country_code : $this->get_base_country();
		$countries    = array( 'GB', 'US', 'AE', 'CZ', 'DO', 'NL', 'PH', 'USAF' );
		$return       = in_array( $country_code, $countries, true ) ? __( 'the', 'woocommerce' ) . ' ' : '';

		return apply_filters( 'woocommerce_countries_estimated_for_prefix', $return, $country_code );
	}

	/**
	 * Correctly name tax in some countries VAT on the frontend.
	 *
	 * @return string
	 */
	public function tax_or_vat() {
		$return = in_array( $this->get_base_country(), array_merge( $this->get_european_union_countries( 'eu_vat' ), array( 'NO' ) ), true ) ? __( 'VAT', 'woocommerce' ) : __( 'Tax', 'woocommerce' );

		return apply_filters( 'woocommerce_countries_tax_or_vat', $return );
	}

	/**
	 * Include the Inc Tax label.
	 *
	 * @return string
	 */
	public function inc_tax_or_vat() {
		$return = in_array( $this->get_base_country(), array_merge( $this->get_european_union_countries( 'eu_vat' ), array( 'NO' ) ), true ) ? __( '(incl. VAT)', 'woocommerce' ) : __( '(incl. tax)', 'woocommerce' );

		return apply_filters( 'woocommerce_countries_inc_tax_or_vat', $return );
	}

	/**
	 * Include the Ex Tax label.
	 *
	 * @return string
	 */
	public function ex_tax_or_vat() {
		$return = in_array( $this->get_base_country(), array_merge( $this->get_european_union_countries( 'eu_vat' ), array( 'NO' ) ), true ) ? __( '(ex. VAT)', 'woocommerce' ) : __( '(ex. tax)', 'woocommerce' );

		return apply_filters( 'woocommerce_countries_ex_tax_or_vat', $return );
	}

	/**
	 * Outputs the list of countries and states for use in dropdown boxes.
	 *
	 * @param string $selected_country Selected country.
	 * @param string $selected_state   Selected state.
	 * @param bool   $escape           If we should escape HTML.
	 */
	public function country_dropdown_options( $selected_country = '', $selected_state = '', $escape = false ) {
		if ( $this->countries ) {
			foreach ( $this->countries as $key => $value ) {
				$states = $this->get_states( $key );
				if ( $states ) {
					echo '<optgroup label="' . esc_attr( $value ) . '">';
					foreach ( $states as $state_key => $state_value ) {
						echo '<option value="' . esc_attr( $key ) . ':' . esc_attr( $state_key ) . '"';

						if ( $selected_country === $key && $selected_state === $state_key ) {
							echo ' selected="selected"';
						}

						echo '>' . esc_html( $value ) . ' &mdash; ' . ( $escape ? esc_js( $state_value ) : $state_value ) . '</option>'; // WPCS: XSS ok.
					}
					echo '</optgroup>';
				} else {
					echo '<option';
					if ( $selected_country === $key && '*' === $selected_state ) {
						echo ' selected="selected"';
					}
					echo ' value="' . esc_attr( $key ) . '">' . ( $escape ? esc_js( $value ) : $value ) . '</option>'; // WPCS: XSS ok.
				}
			}
		}
	}

	/**
	 * Get country address formats.
	 *
	 * These define how addresses are formatted for display in various countries.
	 *
	 * @return array
	 */
	public function get_address_formats() {
		if ( empty( $this->address_formats ) ) {
			$this->address_formats = apply_filters(
				'woocommerce_localisation_address_formats',
				array(
					'default' => "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}",
					'AU'      => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {state} {postcode}\n{country}",
					'AT'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'BE'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'CA'      => "{company}\n{name}\n{address_1}\n{address_2}\n{city} {state_code}&nbsp;&nbsp;{postcode}\n{country}",
					'CH'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'CL'      => "{company}\n{name}\n{address_1}\n{address_2}\n{state}\n{postcode} {city}\n{country}",
					'CN'      => "{country} {postcode}\n{state}, {city}, {address_2}, {address_1}\n{company}\n{name}",
					'CZ'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'DE'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'EE'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'FI'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'DK'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'FR'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city_upper}\n{country}",
					'HK'      => "{company}\n{first_name} {last_name_upper}\n{address_1}\n{address_2}\n{city_upper}\n{state_upper}\n{country}",
					'HU'      => "{name}\n{company}\n{city}\n{address_1}\n{address_2}\n{postcode}\n{country}",
					'IN'      => "{company}\n{name}\n{address_1}\n{address_2}\n{city} {postcode}\n{state}, {country}",
					'IS'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'IT'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode}\n{city}\n{state_upper}\n{country}",
					'JP'      => "{postcode}\n{state} {city} {address_1}\n{address_2}\n{company}\n{last_name} {first_name}\n{country}",
					'TW'      => "{company}\n{last_name} {first_name}\n{address_1}\n{address_2}\n{state}, {city} {postcode}\n{country}",
					'LI'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'NL'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'NZ'      => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {postcode}\n{country}",
					'NO'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'PL'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'PT'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'SK'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'SI'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'ES'      => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city}\n{state}\n{country}",
					'SE'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'TR'      => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city} {state}\n{country}",
					'US'      => "{name}\n{company}\n{address_1}\n{address_2}\n{city}, {state_code} {postcode}\n{country}",
					'VN'      => "{name}\n{company}\n{address_1}\n{city}\n{country}",
				)
			);
		}
		return $this->address_formats;
	}

	/**
	 * Get country address format.
	 *
	 * @param  array  $args Arguments.
	 * @param  string $separator How to separate address lines. @since 3.5.0.
	 * @return string
	 */
	public function get_formatted_address( $args = array(), $separator = '<br/>' ) {
		$default_args = array(
			'first_name' => '',
			'last_name'  => '',
			'company'    => '',
			'address_1'  => '',
			'address_2'  => '',
			'city'       => '',
			'state'      => '',
			'postcode'   => '',
			'country'    => '',
		);

		$args    = array_map( 'trim', wp_parse_args( $args, $default_args ) );
		$state   = $args['state'];
		$country = $args['country'];

		// Get all formats.
		$formats = $this->get_address_formats();

		// Get format for the address' country.
		$format = ( $country && isset( $formats[ $country ] ) ) ? $formats[ $country ] : $formats['default'];

		// Handle full country name.
		$full_country = ( isset( $this->countries[ $country ] ) ) ? $this->countries[ $country ] : $country;

		// Country is not needed if the same as base.
		if ( $country === $this->get_base_country() && ! apply_filters( 'woocommerce_formatted_address_force_country_display', false ) ) {
			$format = str_replace( '{country}', '', $format );
		}

		// Handle full state name.
		$full_state = ( $country && $state && isset( $this->states[ $country ][ $state ] ) ) ? $this->states[ $country ][ $state ] : $state;

		// Substitute address parts into the string.
		$replace = array_map(
			'esc_html',
			apply_filters(
				'woocommerce_formatted_address_replacements',
				array(
					'{first_name}'       => $args['first_name'],
					'{last_name}'        => $args['last_name'],
					'{name}'             => $args['first_name'] . ' ' . $args['last_name'],
					'{company}'          => $args['company'],
					'{address_1}'        => $args['address_1'],
					'{address_2}'        => $args['address_2'],
					'{city}'             => $args['city'],
					'{state}'            => $full_state,
					'{postcode}'         => $args['postcode'],
					'{country}'          => $full_country,
					'{first_name_upper}' => wc_strtoupper( $args['first_name'] ),
					'{last_name_upper}'  => wc_strtoupper( $args['last_name'] ),
					'{name_upper}'       => wc_strtoupper( $args['first_name'] . ' ' . $args['last_name'] ),
					'{company_upper}'    => wc_strtoupper( $args['company'] ),
					'{address_1_upper}'  => wc_strtoupper( $args['address_1'] ),
					'{address_2_upper}'  => wc_strtoupper( $args['address_2'] ),
					'{city_upper}'       => wc_strtoupper( $args['city'] ),
					'{state_upper}'      => wc_strtoupper( $full_state ),
					'{state_code}'       => wc_strtoupper( $state ),
					'{postcode_upper}'   => wc_strtoupper( $args['postcode'] ),
					'{country_upper}'    => wc_strtoupper( $full_country ),
				),
				$args
			)
		);

		$formatted_address = str_replace( array_keys( $replace ), $replace, $format );

		// Clean up white space.
		$formatted_address = preg_replace( '/  +/', ' ', trim( $formatted_address ) );
		$formatted_address = preg_replace( '/\n\n+/', "\n", $formatted_address );

		// Break newlines apart and remove empty lines/trim commas and white space.
		$formatted_address = array_filter( array_map( array( $this, 'trim_formatted_address_line' ), explode( "\n", $formatted_address ) ) );

		// Add html breaks.
		$formatted_address = implode( $separator, $formatted_address );

		// We're done!
		return $formatted_address;
	}

	/**
	 * Trim white space and commas off a line.
	 *
	 * @param  string $line Line.
	 * @return string
	 */
	private function trim_formatted_address_line( $line ) {
		return trim( $line, ', ' );
	}

	/**
	 * Returns the fields we show by default. This can be filtered later on.
	 *
	 * @return array
	 */
	public function get_default_address_fields() {
		if ( 'optional' === get_option( 'woocommerce_checkout_address_2_field', 'optional' ) ) {
			$address_2_placeholder = __( 'Apartment, suite, unit etc. (optional)', 'woocommerce' );
		} else {
			$address_2_placeholder = __( 'Apartment, suite, unit etc.', 'woocommerce' );
		}

		$fields = array(
			'first_name' => array(
				'label'        => __( 'First name', 'woocommerce' ),
				'required'     => true,
				'class'        => array( 'form-row-first' ),
				'autocomplete' => 'given-name',
				'priority'     => 10,
			),
			'last_name'  => array(
				'label'        => __( 'Last name', 'woocommerce' ),
				'required'     => true,
				'class'        => array( 'form-row-last' ),
				'autocomplete' => 'family-name',
				'priority'     => 20,
			),
			'company'    => array(
				'label'        => __( 'Company name', 'woocommerce' ),
				'class'        => array( 'form-row-wide' ),
				'autocomplete' => 'organization',
				'priority'     => 30,
				'required'     => 'required' === get_option( 'woocommerce_checkout_company_field', 'optional' ),
			),
			'country'    => array(
				'type'         => 'country',
				'label'        => __( 'Country', 'woocommerce' ),
				'required'     => true,
				'class'        => array( 'form-row-wide', 'address-field', 'update_totals_on_change' ),
				'autocomplete' => 'country',
				'priority'     => 40,
			),
			'address_1'  => array(
				'label'        => __( 'Street address', 'woocommerce' ),
				/* translators: use local order of street name and house number. */
				'placeholder'  => esc_attr__( 'House number and street name', 'woocommerce' ),
				'required'     => true,
				'class'        => array( 'form-row-wide', 'address-field' ),
				'autocomplete' => 'address-line1',
				'priority'     => 50,
			),
			'address_2'  => array(
				'placeholder'  => esc_attr( $address_2_placeholder ),
				'class'        => array( 'form-row-wide', 'address-field' ),
				'autocomplete' => 'address-line2',
				'priority'     => 60,
				'required'     => 'required' === get_option( 'woocommerce_checkout_address_2_field', 'optional' ),
			),
			'city'       => array(
				'label'        => __( 'Town / City', 'woocommerce' ),
				'required'     => true,
				'class'        => array( 'form-row-wide', 'address-field' ),
				'autocomplete' => 'address-level2',
				'priority'     => 70,
			),
			'state'      => array(
				'type'         => 'state',
				'label'        => __( 'State / County', 'woocommerce' ),
				'required'     => true,
				'class'        => array( 'form-row-wide', 'address-field' ),
				'validate'     => array( 'state' ),
				'autocomplete' => 'address-level1',
				'priority'     => 80,
			),
			'postcode'   => array(
				'label'        => __( 'Postcode / ZIP', 'woocommerce' ),
				'required'     => true,
				'class'        => array( 'form-row-wide', 'address-field' ),
				'validate'     => array( 'postcode' ),
				'autocomplete' => 'postal-code',
				'priority'     => 90,
			),
		);

		if ( 'hidden' === get_option( 'woocommerce_checkout_company_field', 'optional' ) ) {
			unset( $fields['company'] );
		}

		if ( 'hidden' === get_option( 'woocommerce_checkout_address_2_field', 'optional' ) ) {
			unset( $fields['address_2'] );
		}

		$default_address_fields = apply_filters( 'woocommerce_default_address_fields', $fields );
		// Sort each of the fields based on priority.
		uasort( $default_address_fields, 'wc_checkout_fields_uasort_comparison' );

		return $default_address_fields;
	}

	/**
	 * Get JS selectors for fields which are shown/hidden depending on the locale.
	 *
	 * @return array
	 */
	public function get_country_locale_field_selectors() {
		$locale_fields = array(
			'address_1' => '#billing_address_1_field, #shipping_address_1_field',
			'address_2' => '#billing_address_2_field, #shipping_address_2_field',
			'state'     => '#billing_state_field, #shipping_state_field, #calc_shipping_state_field',
			'postcode'  => '#billing_postcode_field, #shipping_postcode_field, #calc_shipping_postcode_field',
			'city'      => '#billing_city_field, #shipping_city_field, #calc_shipping_city_field',
		);
		return apply_filters( 'woocommerce_country_locale_field_selectors', $locale_fields );
	}

	/**
	 * Get country locale settings.
	 *
	 * These locales override the default country selections after a country is chosen.
	 *
	 * @return array
	 */
	public function get_country_locale() {
		if ( empty( $this->locale ) ) {
			$this->locale = apply_filters(
				'woocommerce_get_country_locale',
				array(
					'AE' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'AF' => array(
						'state' => array(
							'required' => false,
						),
					),
					'AO' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
						'state'    => array(
							'label' => __( 'Province', 'woocommerce' ),
						),
					),
					'AT' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'AU' => array(
						'city'     => array(
							'label' => __( 'Suburb', 'woocommerce' ),
						),
						'postcode' => array(
							'label' => __( 'Postcode', 'woocommerce' ),
						),
						'state'    => array(
							'label' => __( 'State', 'woocommerce' ),
						),
					),
					'AX' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'BD' => array(
						'postcode' => array(
							'required' => false,
						),
						'state'    => array(
							'label' => __( 'District', 'woocommerce' ),
						),
					),
					'BE' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
							'label'    => __( 'Province', 'woocommerce' ),
						),
					),
					'BH' => array(
						'postcode' => array(
							'required' => false,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'BI' => array(
						'state' => array(
							'required' => false,
						),
					),
					'BO' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'BS' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'CA' => array(
						'state' => array(
							'label' => __( 'Province', 'woocommerce' ),
						),
					),
					'CH' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'label'    => __( 'Canton', 'woocommerce' ),
							'required' => false,
						),
					),
					'CL' => array(
						'city'     => array(
							'required' => true,
						),
						'postcode' => array(
							'required' => false,
						),
						'state'    => array(
							'label' => __( 'Region', 'woocommerce' ),
						),
					),
					'CN' => array(
						'state' => array(
							'label' => __( 'Province', 'woocommerce' ),
						),
					),
					'CO' => array(
						'postcode' => array(
							'required' => false,
						),
					),
					'CZ' => array(
						'state' => array(
							'required' => false,
						),
					),
					'DE' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'DK' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'EE' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'FI' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'FR' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'GP' => array(
						'state' => array(
							'required' => false,
						),
					),
					'GF' => array(
						'state' => array(
							'required' => false,
						),
					),
					'GR' => array(
						'state' => array(
							'required' => false,
						),
					),
					'HK' => array(
						'postcode' => array(
							'required' => false,
						),
						'city'     => array(
							'label' => __( 'Town / District', 'woocommerce' ),
						),
						'state'    => array(
							'label' => __( 'Region', 'woocommerce' ),
						),
					),
					'HU' => array(
						'state' => array(
							'label' => __( 'County', 'woocommerce' ),
						),
					),
					'ID' => array(
						'state' => array(
							'label' => __( 'Province', 'woocommerce' ),
						),
					),
					'IE' => array(
						'postcode' => array(
							'required' => false,
							'label'    => __( 'Eircode', 'woocommerce' ),
						),
						'state'    => array(
							'label' => __( 'County', 'woocommerce' ),
						),
					),
					'IS' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'IL' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'IM' => array(
						'state' => array(
							'required' => false,
						),
					),
					'IT' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => true,
							'label'    => __( 'Province', 'woocommerce' ),
						),
					),
					'JP' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'label'    => __( 'Prefecture', 'woocommerce' ),
							'priority' => 66,
						),
						'city' => array(
							'priority' => 67,
						),
						'address_1' => array(
							'priority' => 68,
						),
						'address_2' => array(
							'priority' => 69,
						),
					),
					'KR' => array(
						'state' => array(
							'required' => false,
						),
					),
					'KW' => array(
						'state' => array(
							'required' => false,
						),
					),
					'LV' => array(
						'state' => array(
							'label'    => __( 'Municipality', 'woocommerce' ),
							'required' => false,
						),
					),
					'LB' => array(
						'state' => array(
							'required' => false,
						),
					),
					'MQ' => array(
						'state' => array(
							'required' => false,
						),
					),
					'MT' => array(
						'state' => array(
							'required' => false,
						),
					),
					'NL' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
							'label'    => __( 'Province', 'woocommerce' ),
						),
					),
					'NG' => array(
						'postcode' => array(
							'label'    => __( 'Postcode', 'woocommerce' ),
							'required' => false,
							'hidden'   => true,
						),
						'state'    => array(
							'label' => __( 'State', 'woocommerce' ),
						),
					),
					'NZ' => array(
						'postcode' => array(
							'label' => __( 'Postcode', 'woocommerce' ),
						),
						'state'    => array(
							'required' => false,
							'label'    => __( 'Region', 'woocommerce' ),
						),
					),
					'NO' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'NP' => array(
						'state'    => array(
							'label' => __( 'State / Zone', 'woocommerce' ),
						),
						'postcode' => array(
							'required' => false,
						),
					),
					'PL' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'PT' => array(
						'state' => array(
							'required' => false,
						),
					),
					'RE' => array(
						'state' => array(
							'required' => false,
						),
					),
					'RO' => array(
						'state' => array(
							'label'    => __( 'County', 'woocommerce' ),
							'required' => true,
						),
					),
					'SG' => array(
						'state' => array(
							'required' => false,
						),
						'city'  => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'SK' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'SI' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'SR' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'ES' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'label' => __( 'Province', 'woocommerce' ),
						),
					),
					'LI' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'label'    => __( 'Municipality', 'woocommerce' ),
							'required' => false,
						),
					),
					'LK' => array(
						'state' => array(
							'required' => false,
						),
					),
					'LU' => array(
						'state' => array(
							'required' => false,
						),
					),
					'MD' => array(
						'state' => array(
							'label' => __( 'Municipality / District', 'woocommerce' ),
						),
					),
					'SE' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'TR' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'label' => __( 'Province', 'woocommerce' ),
						),
					),
					'US' => array(
						'postcode' => array(
							'label' => __( 'ZIP', 'woocommerce' ),
						),
						'state'    => array(
							'label' => __( 'State', 'woocommerce' ),
						),
					),
					'GB' => array(
						'postcode' => array(
							'label' => __( 'Postcode', 'woocommerce' ),
						),
						'state'    => array(
							'label'    => __( 'County', 'woocommerce' ),
							'required' => false,
						),
					),
					'ST' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
						'state'    => array(
							'label' => __( 'District', 'woocommerce' ),
						),
					),
					'VN' => array(
						'state'     => array(
							'required' => false,
						),
						'postcode'  => array(
							'priority' => 65,
							'required' => false,
							'hidden'   => false,
						),
						'address_2' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'WS' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'YT' => array(
						'state' => array(
							'required' => false,
						),
					),
					'ZA' => array(
						'state' => array(
							'label' => __( 'Province', 'woocommerce' ),
						),
					),
					'ZW' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
				)
			);

			$this->locale = array_intersect_key( $this->locale, array_merge( $this->get_allowed_countries(), $this->get_shipping_countries() ) );

			// Default Locale Can be filtered to override fields in get_address_fields(). Countries with no specific locale will use default.
			$this->locale['default'] = apply_filters( 'woocommerce_get_country_locale_default', $this->get_default_address_fields() );

			// Filter default AND shop base locales to allow overides via a single function. These will be used when changing countries on the checkout.
			if ( ! isset( $this->locale[ $this->get_base_country() ] ) ) {
				$this->locale[ $this->get_base_country() ] = $this->locale['default'];
			}

			$this->locale['default']                   = apply_filters( 'woocommerce_get_country_locale_base', $this->locale['default'] );
			$this->locale[ $this->get_base_country() ] = apply_filters( 'woocommerce_get_country_locale_base', $this->locale[ $this->get_base_country() ] );
		}

		return $this->locale;
	}

	/**
	 * Apply locale and get address fields.
	 *
	 * @param  mixed  $country Country.
	 * @param  string $type    Address type, defaults to 'billing_'.
	 * @return array
	 */
	public function get_address_fields( $country = '', $type = 'billing_' ) {
		if ( ! $country ) {
			$country = $this->get_base_country();
		}

		$fields = $this->get_default_address_fields();
		$locale = $this->get_country_locale();

		if ( isset( $locale[ $country ] ) ) {
			$fields = wc_array_overlay( $fields, $locale[ $country ] );
		}

		// Prepend field keys.
		$address_fields = array();

		foreach ( $fields as $key => $value ) {
			if ( 'state' === $key ) {
				$value['country_field'] = $type . 'country';
				$value['country']       = $country;
			}
			$address_fields[ $type . $key ] = $value;
		}

		// Add email and phone fields.
		if ( 'billing_' === $type ) {
			if ( 'hidden' !== get_option( 'woocommerce_checkout_phone_field', 'required' ) ) {
				$address_fields['billing_phone'] = array(
					'label'        => __( 'Phone', 'woocommerce' ),
					'required'     => 'required' === get_option( 'woocommerce_checkout_phone_field', 'required' ),
					'type'         => 'tel',
					'class'        => array( 'form-row-wide' ),
					'validate'     => array( 'phone' ),
					'autocomplete' => 'tel',
					'priority'     => 100,
				);
			}
			$address_fields['billing_email'] = array(
				'label'        => __( 'Email address', 'woocommerce' ),
				'required'     => true,
				'type'         => 'email',
				'class'        => array( 'form-row-wide' ),
				'validate'     => array( 'email' ),
				'autocomplete' => 'no' === get_option( 'woocommerce_registration_generate_username' ) ? 'email' : 'email username',
				'priority'     => 110,
			);
		}

		/**
		 * Important note on this filter: Changes to address fields can and will be overridden by
		 * the woocommerce_default_address_fields. The locales/default locales apply on top based
		 * on country selection. If you want to change things like the required status of an
		 * address field, filter woocommerce_default_address_fields instead.
		 */
		$address_fields = apply_filters( 'woocommerce_' . $type . 'fields', $address_fields, $country );
		// Sort each of the fields based on priority.
		uasort( $address_fields, 'wc_checkout_fields_uasort_comparison' );

		return $address_fields;
	}
}
