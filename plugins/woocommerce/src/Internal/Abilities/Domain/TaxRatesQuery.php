<?php
/**
 * Tax rates query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the ability that lists tax rates. Tax rates are table rows with
 * no object an extension could read or change, so they take no extension fields.
 */
class TaxRatesQuery extends AbstractDomainAbility implements AbilityDefinition {

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/tax-rates-query';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Query tax rates', 'woocommerce' ),
			'description'         => __( 'List the tax rates of every tax class, in the order WooCommerce matches them.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'country'  => array(
						'type'        => 'string',
						'description' => __( 'Two-letter country code. Rates for every country are included too.', 'woocommerce' ),
						'pattern'     => '^[A-Za-z]{2}$',
					),
					'class'    => array(
						'type'        => 'string',
						'description' => __( 'Tax class slug; standard for the standard rates.', 'woocommerce' ),
					),
					'page'     => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
					'per_page' => array(
						'type'    => 'integer',
						'default' => 50,
						'minimum' => 1,
						'maximum' => 100,
					),
				),
				'additionalProperties' => false,
				'default'              => array(),
			),
			'output_schema'       => self::get_collection_output_schema( 'rates', self::get_rate_output_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_query_taxes' ),
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
	 * List tax rates.
	 *
	 * @param array $input Ability input.
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function execute( array $input ): array {
		$classes = array_merge( array( 'standard' ), \WC_Tax::get_tax_class_slugs() );
		if ( isset( $input['class'] ) ) {
			$classes = array_intersect( $classes, array( sanitize_title( (string) $input['class'] ) ) );
		}
		$country = isset( $input['country'] ) ? strtoupper( (string) $input['country'] ) : '';

		$rates = array();
		foreach ( $classes as $tax_class ) {
			foreach ( (array) \WC_Tax::get_rates_for_tax_class( 'standard' === $tax_class ? '' : $tax_class ) as $rate ) {
				if ( $rate instanceof \stdClass && ( '' === $country || '' === $rate->tax_rate_country || $country === $rate->tax_rate_country ) ) {
					$rates[] = self::format_rate_for_response( $rate, $tax_class );
				}
			}
		}

		$page     = (int) ( $input['page'] ?? 1 );
		$per_page = (int) ( $input['per_page'] ?? 50 );

		return array(
			'rates'       => array_slice( $rates, ( $page - 1 ) * $per_page, $per_page ),
			'total_pages' => (int) ceil( count( $rates ) / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Check tax settings read access.
	 *
	 * @return bool
	 *
	 * @since 11.4.0
	 */
	public static function can_query_taxes(): bool {
		return wc_rest_check_manager_permissions( 'settings', 'read' );
	}

	/**
	 * Format a tax rate row.
	 *
	 * @param \stdClass $rate      Rate row with its postcode and city lists.
	 * @param string    $tax_class Tax class slug.
	 * @return array
	 */
	private static function format_rate_for_response( \stdClass $rate, string $tax_class ): array {
		return array(
			'id'        => (int) $rate->tax_rate_id,
			'country'   => (string) $rate->tax_rate_country,
			'state'     => (string) $rate->tax_rate_state,
			'postcodes' => array_values( (array) ( $rate->postcode ?? array() ) ),
			'cities'    => array_values( (array) ( $rate->city ?? array() ) ),
			'rate'      => (string) $rate->tax_rate,
			'name'      => (string) $rate->tax_rate_name,
			'priority'  => (int) $rate->tax_rate_priority,
			'compound'  => (bool) $rate->tax_rate_compound,
			'shipping'  => (bool) $rate->tax_rate_shipping,
			'class'     => $tax_class,
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
