<?php
/**
 * Store configuration read ability.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Enums\CurrencyPosition;
use Automattic\WooCommerce\Enums\DimensionUnit;
use Automattic\WooCommerce\Enums\TaxDisplayMode;
use Automattic\WooCommerce\Enums\WeightUnit;

defined( 'ABSPATH' ) || exit;

/**
 * Reads a fixed set of store options without credentials or onboarding email.
 */
class StoreSettingsGet implements AbilityDefinition {

	/**
	 * Get the ability name.
	 *
	 * @since 11.4.0
	 * @return string
	 */
	public static function get_name(): string {
		return 'woocommerce/store-settings-get';
	}

	/**
	 * Register the store configuration read.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Get store settings', 'woocommerce' ),
			'description'         => __( 'Read store location, currency, units, tax, stock and checkout configuration. For site identity use core/get-site-info.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'                 => 'object',
				'default'              => array(),
				'additionalProperties' => false,
			),
			'output_schema'       => self::get_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_read_settings' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => true,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Check access to store settings.
	 *
	 * @since 11.4.0
	 * @return bool
	 */
	public static function can_read_settings(): bool {
		return wc_rest_check_manager_permissions( 'settings', 'read' );
	}

	/**
	 * Read current store configuration.
	 *
	 * @since 11.4.0
	 *
	 * @param mixed $input The input parameters.
	 * @return array<string, mixed>
	 */
	public static function execute( $input = null ): array {
		unset( $input );

		// `woocommerce_default_country` stores country and state as "US:CA".
		$country_parts = explode( ':', (string) get_option( 'woocommerce_default_country', '' ), 2 );

		return array(
			'address'             => array(
				'street'       => trim(
					(string) get_option( 'woocommerce_store_address', '' )
					. ( get_option( 'woocommerce_store_address_2', '' ) ? ', ' . (string) get_option( 'woocommerce_store_address_2', '' ) : '' )
				),
				'city'         => (string) get_option( 'woocommerce_store_city', '' ),
				'postcode'     => (string) get_option( 'woocommerce_store_postcode', '' ),
				'country_code' => $country_parts[0],
				'state_code'   => $country_parts[1] ?? '',
			),
			'units'               => array(
				'dimension' => (string) get_option( 'woocommerce_dimension_unit', DimensionUnit::CENTIMETER ),
				'weight'    => (string) get_option( 'woocommerce_weight_unit', WeightUnit::KILOGRAM ),
			),
			'selling_locations'   => self::get_selling_locations(),
			'shipping_locations'  => self::get_shipping_locations(),
			'currency'            => self::get_currency(),
			'timezone'            => wp_timezone_string(),
			'gmt_offset'          => (float) get_option( 'gmt_offset', 0 ),
			'date_format'         => (string) get_option( 'date_format', '' ),
			'time_format'         => (string) get_option( 'time_format', '' ),
			'start_of_week'       => (int) get_option( 'start_of_week', 0 ),
			'tax'                 => array(
				'prices_include_tax' => 'yes' === get_option( 'woocommerce_prices_include_tax', 'no' ),
				'display_in_shop'    => (string) get_option( 'woocommerce_tax_display_shop', TaxDisplayMode::EXCLUSIVE ),
				'display_in_cart'    => (string) get_option( 'woocommerce_tax_display_cart', TaxDisplayMode::EXCLUSIVE ),
			),
			'stock'               => array(
				'manage_stock'            => 'yes' === get_option( 'woocommerce_manage_stock', 'yes' ),
				'low_stock_threshold'     => (int) get_option( 'woocommerce_notify_low_stock_amount', 2 ),
				'hide_out_of_stock_items' => 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' ),
			),
			'capabilities'        => array(
				'coupons_enabled'        => 'yes' === get_option( 'woocommerce_enable_coupons', 'yes' ),
				'guest_checkout'         => 'yes' === get_option( 'woocommerce_enable_guest_checkout', 'yes' ),
				'review_ratings_enabled' => 'yes' === get_option( 'woocommerce_enable_review_rating', 'yes' ),
			),
			'onboarding_profile'  => (object) self::get_onboarding_profile(),
			'woocommerce_version' => WC_VERSION,
			'installed_at'        => (int) get_option( 'woocommerce_admin_install_timestamp', 0 ),
		);
	}

	/**
	 * Read the active selling scope and country list.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_selling_locations(): array {
		$scope = (string) get_option( 'woocommerce_allowed_countries', 'all' );

		if ( 'specific' === $scope ) {
			return array(
				'scope'              => $scope,
				'included_countries' => self::country_codes( get_option( 'woocommerce_specific_allowed_countries', array() ) ),
			);
		}

		if ( 'all_except' === $scope ) {
			return array(
				'scope'              => $scope,
				'excluded_countries' => self::country_codes( get_option( 'woocommerce_all_except_countries', array() ) ),
			);
		}

		return array( 'scope' => 'all' );
	}

	/**
	 * Read the active shipping scope and country list.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_shipping_locations(): array {
		$scope = (string) get_option( 'woocommerce_ship_to_countries', '' );

		if ( 'specific' === $scope ) {
			return array(
				'scope'              => $scope,
				'included_countries' => self::country_codes( get_option( 'woocommerce_specific_ship_to_countries', array() ) ),
			);
		}

		if ( 'all' === $scope || 'disabled' === $scope ) {
			return array( 'scope' => $scope );
		}

		return array( 'scope' => 'same_as_selling_locations' );
	}

	/**
	 * Normalize a stored country list, which is an empty string until saved once.
	 *
	 * @param mixed $value Raw option value.
	 * @return array<int, string>
	 */
	private static function country_codes( $value ): array {
		return is_array( $value ) ? array_values( array_map( 'strval', $value ) ) : array();
	}

	/**
	 * Mirrors how the storefront itself formats prices.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_currency(): array {
		return array(
			'code'               => get_woocommerce_currency(),
			// WooCommerce stores the symbol as an HTML entity; the agent reads it as text.
			'symbol'             => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
			'precision'          => wc_get_price_decimals(),
			'symbol_position'    => (string) get_option( 'woocommerce_currency_pos', CurrencyPosition::LEFT ),
			'decimal_separator'  => wc_get_price_decimal_separator(),
			'thousand_separator' => wc_get_price_thousand_separator(),
			'price_format'       => get_woocommerce_price_format(),
		);
	}

	/**
	 * Get the reviewed onboarding field schema.
	 *
	 * @return array
	 */
	private static function get_onboarding_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'completed'               => array(
					'type' => 'boolean',
				),
				'skipped'                 => array(
					'type' => 'boolean',
				),
				'is_agree_marketing'      => array(
					'type' => 'boolean',
				),
				'is_store_country_set'    => array(
					'type' => 'boolean',
				),
				'is_plugins_page_skipped' => array(
					'type' => 'boolean',
				),
				'business_choice'         => array(
					'type' => array( 'string', 'null' ),
				),
				'selling_online_answer'   => array(
					'type' => array( 'string', 'null' ),
				),
				'industry'                => array(
					'type'  => array( 'array', 'null' ),
					'items' => array(
						'type' => array( 'string', 'null' ),
					),
				),
				'business_extensions'     => array(
					'type'  => array( 'array', 'null' ),
					'items' => array(
						'type' => array( 'string', 'null' ),
					),
				),
				'selling_platforms'       => array(
					'type'  => array( 'array', 'null' ),
					'items' => array(
						'type' => array( 'string', 'null' ),
					),
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Exclude unknown fields and values that do not match the reviewed shape.
	 *
	 * @return array
	 */
	private static function get_onboarding_profile(): array {
		$profile = get_option( 'woocommerce_onboarding_profile', array() );
		$result  = array();
		foreach ( self::get_onboarding_schema()['properties'] as $key => $schema ) {
			if ( is_array( $profile ) && array_key_exists( $key, $profile ) && true === rest_validate_value_from_schema( $profile[ $key ], $schema ) ) {
				$result[ $key ] = $profile[ $key ];
			}
		}
		return $result;
	}

	/**
	 * Get the closed store configuration schema.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		$schema                                     = array(
			'type'                 => 'object',
			'properties'           => array(
				'address'             => array(
					'type'                 => 'object',
					'properties'           => array(
						'street'       => array(
							'type' => 'string',
						),
						'city'         => array(
							'type' => 'string',
						),
						'postcode'     => array(
							'type' => 'string',
						),
						'country_code' => array(
							'type' => 'string',
						),
						'state_code'   => array(
							'type' => 'string',
						),
					),
					'additionalProperties' => false,
				),
				'units'               => array(
					'type'                 => 'object',
					'properties'           => array(
						'dimension' => array(
							'type' => 'string',
						),
						'weight'    => array(
							'type' => 'string',
						),
					),
					'additionalProperties' => false,
				),
				'currency'            => array(
					'type'                 => 'object',
					'properties'           => array(
						'code'               => array(
							'type' => 'string',
						),
						'symbol'             => array(
							'type' => 'string',
						),
						'precision'          => array(
							'type' => 'integer',
						),
						'symbol_position'    => array(
							'type' => 'string',
						),
						'decimal_separator'  => array(
							'type' => 'string',
						),
						'thousand_separator' => array(
							'type' => 'string',
						),
						'price_format'       => array(
							'type' => 'string',
						),
					),
					'additionalProperties' => false,
				),
				'timezone'            => array(
					'type' => 'string',
				),
				'gmt_offset'          => array(
					'type' => 'number',
				),
				'date_format'         => array(
					'type' => 'string',
				),
				'time_format'         => array(
					'type' => 'string',
				),
				'start_of_week'       => array(
					'type' => 'integer',
				),
				'tax'                 => array(
					'type'                 => 'object',
					'properties'           => array(
						'prices_include_tax' => array(
							'type' => 'boolean',
						),
						'display_in_shop'    => array(
							'type' => 'string',
						),
						'display_in_cart'    => array(
							'type' => 'string',
						),
					),
					'additionalProperties' => false,
				),
				'stock'               => array(
					'type'                 => 'object',
					'properties'           => array(
						'manage_stock'            => array(
							'type' => 'boolean',
						),
						'low_stock_threshold'     => array(
							'type' => 'integer',
						),
						'hide_out_of_stock_items' => array(
							'type' => 'boolean',
						),
					),
					'additionalProperties' => false,
				),
				'capabilities'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'coupons_enabled'        => array(
							'type' => 'boolean',
						),
						'guest_checkout'         => array(
							'type' => 'boolean',
						),
						'review_ratings_enabled' => array(
							'type' => 'boolean',
						),
					),
					'additionalProperties' => false,
				),
				'woocommerce_version' => array(
					'type' => 'string',
				),
				'installed_at'        => array(
					'type' => 'integer',
				),
				'selling_locations'   => array(
					'type'                 => 'object',
					'properties'           => array(
						'scope'              => array(
							'type' => 'string',
							'enum' => array( 'all', 'all_except', 'specific' ),
						),
						'included_countries' => array(
							'type'  => 'array',
							'items' => array(
								'type' => 'string',
							),
						),
						'excluded_countries' => array(
							'type'  => 'array',
							'items' => array(
								'type' => 'string',
							),
						),
					),
					'additionalProperties' => false,
				),
				'shipping_locations'  => array(
					'type'                 => 'object',
					'properties'           => array(
						'scope'              => array(
							'type' => 'string',
							'enum' => array( 'same_as_selling_locations', 'all', 'specific', 'disabled' ),
						),
						'included_countries' => array(
							'type'  => 'array',
							'items' => array(
								'type' => 'string',
							),
						),
					),
					'additionalProperties' => false,
				),
			),
			'additionalProperties' => false,
		);
		$schema['properties']['onboarding_profile'] = self::get_onboarding_schema();
		return $schema;
	}
}
