<?php
/**
 * Tax rates query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\TaxRateAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the ability that lists tax rates. Tax rates are table rows with
 * no object an extension could read or change, so they take no extension fields.
 */
class TaxRatesQuery extends AbstractDomainAbility implements AbilityDefinition {

	use TaxRateAbilityTrait;

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
		$output_schema                        = self::get_collection_output_schema( 'rates', self::get_rate_output_schema() );
		$output_schema['properties']['total'] = array(
			'type'        => 'integer',
			'description' => __( 'Total number of matching rates across all pages.', 'woocommerce' ),
		);

		return array(
			'label'               => __( 'Query tax rates', 'woocommerce' ),
			'description'         => __( 'List the tax rates of every tax class, in the order of the tax settings.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'include'  => array(
						'type'        => 'array',
						'description' => __( 'Return only these tax rate IDs.', 'woocommerce' ),
						'minItems'    => 1,
						'items'       => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
					),
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
			'output_schema'       => $output_schema,
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
		global $wpdb;

		$page     = $input['page'] ?? 1;
		$per_page = $input['per_page'] ?? 50;

		$conditions = array( '1=1' );
		if ( isset( $input['include'] ) ) {
			$include      = $input['include'];
			$placeholders = implode( ', ', array_fill( 0, count( $include ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- The placeholders are generated for each ID.
			$conditions[] = $wpdb->prepare( "tax_rate_id IN ({$placeholders})", $include );
		}
		if ( isset( $input['class'] ) ) {
			$tax_class = self::get_stored_tax_class( $input['class'] );
			if ( null === $tax_class ) {
				return array(
					'rates'       => array(),
					'total'       => 0,
					'total_pages' => 0,
					'page'        => $page,
					'per_page'    => $per_page,
				);
			}
			$conditions[] = $wpdb->prepare( 'tax_rate_class = %s', $tax_class );
		}
		if ( isset( $input['country'] ) ) {
			$conditions[] = $wpdb->prepare( "tax_rate_country IN ( '', %s )", strtoupper( $input['country'] ) );
		}
		$where = implode( ' AND ', $conditions );

		// Standard first, then the classes in the order the tax settings list them; rates of a deleted class last.
		$class_slugs = \WC_Tax::get_tax_class_slugs();
		$class_order = "WHEN '' THEN 0";
		foreach ( $class_slugs as $index => $slug ) {
			$class_order .= $wpdb->prepare( ' WHEN %s THEN %d', $slug, $index + 1 );
		}
		$class_order = "CASE tax_rate_class {$class_order} ELSE " . ( count( $class_slugs ) + 1 ) . ' END';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Each condition and the class order are prepared above.
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE {$where} ORDER BY {$class_order}, tax_rate_order, tax_rate_id LIMIT %d, %d",
				( $page - 1 ) * $per_page,
				$per_page
			)
		);
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rates WHERE {$where}" );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$locations = self::get_locations( array_map( 'intval', wp_list_pluck( $rows, 'tax_rate_id' ) ) );

		return array(
			'rates'       => array_map(
				static fn ( \stdClass $rate ): array => self::format_rate_for_response( $rate, $locations[ (int) $rate->tax_rate_id ] ?? array() ),
				$rows
			),
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
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
		return (bool) wc_rest_check_manager_permissions( 'settings', 'read' );
	}
}
