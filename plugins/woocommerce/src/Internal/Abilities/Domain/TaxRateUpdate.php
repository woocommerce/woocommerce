<?php
/**
 * Tax rate update ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\TaxRateAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the ability that changes a tax rate. Only the sent fields change.
 */
class TaxRateUpdate extends AbstractDomainAbility implements AbilityDefinition {

	use TaxRateAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/tax-rate-update';
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
			'label'               => __( 'Update tax rate', 'woocommerce' ),
			'description'         => __( 'Change a tax rate. Send its id from tax-rates-query and only the fields to change. An empty postcodes or cities list makes the rate apply to every postcode or city.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array_merge(
					array(
						'id' => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
					),
					self::get_rate_write_input_properties()
				),
				'required'             => array( 'id' ),
				'additionalProperties' => false,
			),
			'output_schema'       => self::get_entity_output_schema( 'rate', self::get_rate_output_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_edit_tax_rates' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => true,
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * Write the sent fields to the rate.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 11.4.0
	 */
	public static function execute( array $input ) {
		$rate_id = $input['id'];

		$current = self::get_rate_or_error( $rate_id );
		if ( is_wp_error( $current ) ) {
			return $current;
		}

		$columns = self::get_rate_columns( $input );
		if ( is_wp_error( $columns ) ) {
			return $columns;
		}

		$region_error = self::check_rate_region( $columns, $current );
		if ( $region_error ) {
			return $region_error;
		}

		$locations = self::get_rate_locations( $input );
		if ( is_wp_error( $locations ) ) {
			return $locations;
		}

		if ( empty( $columns ) && empty( $locations ) ) {
			return array( 'rate' => $current );
		}

		if ( ! empty( $columns ) ) {
			\WC_Tax::_update_tax_rate( $rate_id, $columns );
		}
		$locations_error = self::write_rate_locations( $rate_id, $locations );
		self::refresh_shipping_rates_cache();
		if ( $locations_error ) {
			return $locations_error;
		}

		$rate = self::get_rate_or_error( $rate_id );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		return array( 'rate' => $rate );
	}

	/**
	 * Check tax settings edit access, as the REST taxes endpoint does.
	 *
	 * @return bool
	 *
	 * @since 11.4.0
	 */
	public static function can_edit_tax_rates(): bool {
		return (bool) wc_rest_check_manager_permissions( 'settings', 'edit' );
	}
}
