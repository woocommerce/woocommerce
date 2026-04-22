<?php
/**
 * GeneralSettingsPage.
 *
 * @package WooCommerce\Admin
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsPages;

use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsPageInterface;

defined( 'ABSPATH' ) || exit;

/**
 * React-settings contract for the General tab.
 *
 * Supplies server-side option synthesis for the currency dropdown and the
 * country/state picker, which ship without inline `options` arrays. Ported
 * from ReactSettingsSchema's private get_general_field_options() helper.
 *
 * @since 10.8.0
 */
final class GeneralSettingsPage implements ReactSettingsPageInterface {

	/**
	 * {@inheritDoc}
	 */
	public function get_extra_type_map( string $section ): array {
		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_extra_supported_types( string $section ): array {
		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_field_options( string $field_id, array $field, string $section ): ?array {
		switch ( $field_id ) {
			case 'woocommerce_currency':
				return $this->get_currency_options();
			case 'woocommerce_default_country':
				return $this->get_country_options();
			case 'woocommerce_all_except_countries':
			case 'woocommerce_specific_allowed_countries':
			case 'woocommerce_specific_ship_to_countries':
				return $this->get_flat_country_options();
			default:
				return null;
		}
	}

	/**
	 * Build the currency list used by the General tab's currency select.
	 *
	 * @return array<int, array{label: string, value: string}>
	 */
	private function get_currency_options(): array {
		if ( ! function_exists( 'get_woocommerce_currencies' ) || ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
			return array();
		}

		$out = array();
		foreach ( get_woocommerce_currencies() as $code => $name ) {
			$label  = wp_specialchars_decode( (string) $name );
			$symbol = wp_specialchars_decode( (string) get_woocommerce_currency_symbol( $code ) );
			$out[]  = array(
				'label' => $label . ' (' . $symbol . ') — ' . $code,
				'value' => (string) $code,
			);
		}

		return $out;
	}

	/**
	 * Build the country/state list used by the General tab's default-country picker.
	 *
	 * Countries with states emit one entry per state with value `"$country:$state"`.
	 * Countries without states emit a single entry with the bare country code.
	 *
	 * @return array<int, array{label: string, value: string}>
	 */
	private function get_country_options(): array {
		if ( ! function_exists( 'WC' ) ) {
			return array();
		}

		$countries = WC()->countries->get_countries();
		$states    = WC()->countries->get_states();

		$out = array();
		foreach ( $countries as $country_code => $country_name ) {
			$country_states = $states[ $country_code ] ?? array();

			if ( empty( $country_states ) ) {
				$out[] = array(
					'label' => (string) $country_name,
					'value' => (string) $country_code,
				);
				continue;
			}

			foreach ( $country_states as $state_code => $state_name ) {
				$out[] = array(
					'label' => $country_name . ' — ' . $state_name,
					'value' => $country_code . ':' . $state_code,
				);
			}
		}

		return $out;
	}

	/**
	 * Build a flat country list (no state granularity) used by the three
	 * `multi_select_countries` fields on the General tab —
	 * `woocommerce_all_except_countries`,
	 * `woocommerce_specific_allowed_countries`, and
	 * `woocommerce_specific_ship_to_countries`.
	 *
	 * @return array<int, array{label: string, value: string}>
	 */
	private function get_flat_country_options(): array {
		if ( ! function_exists( 'WC' ) ) {
			return array();
		}

		$out = array();
		foreach ( WC()->countries->get_countries() as $country_code => $country_name ) {
			$out[] = array(
				'label' => (string) $country_name,
				'value' => (string) $country_code,
			);
		}

		return $out;
	}
}
