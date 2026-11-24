<?php
/**
 * Tax rate create ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\TaxRateAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the ability that adds a tax rate. Tax rates are table rows with
 * no object an extension could read or change, so they take no extension fields.
 */
class TaxRateCreate extends AbstractDomainAbility implements AbilityDefinition {

	use TaxRateAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/tax-rate-create';
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
			'label'               => __( 'Create tax rate', 'woocommerce' ),
			'description'         => __( 'Add a tax rate to a tax class. Takes the fields that tax-rates-query returns, so check there first that no rate already covers the location.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => self::get_rate_write_input_properties(),
				'required'             => array( 'rate' ),
				'additionalProperties' => false,
			),
			'output_schema'       => self::get_entity_output_schema( 'rate', self::get_rate_output_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_create_tax_rates' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => false,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Add the rate. Unsent fields get the defaults of the tax settings screen.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 11.4.0
	 */
	public static function execute( array $input ) {
		global $wpdb;

		$columns = self::get_rate_columns( $input );
		if ( is_wp_error( $columns ) ) {
			return $columns;
		}

		$region_error = self::check_rate_region( $columns );
		if ( $region_error ) {
			return $region_error;
		}

		$locations = self::get_rate_locations( $input );
		if ( is_wp_error( $locations ) ) {
			return $locations;
		}

		$columns += array(
			'tax_rate_name'     => '',
			'tax_rate_priority' => 1,
			'tax_rate_compound' => 0,
			'tax_rate_shipping' => 1,
			'tax_rate_class'    => '',
		);

		$last_order                = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(tax_rate_order) FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_class = %s", $columns['tax_rate_class'] ) );
		$columns['tax_rate_order'] = null === $last_order ? 0 : (int) $last_order + 1;

		$rate_id = (int) \WC_Tax::_insert_tax_rate( $columns );
		if ( ! $rate_id ) {
			return new \WP_Error( 'woocommerce_ability_tax_rate_create_failed', __( 'The tax rate could not be saved.', 'woocommerce' ), array( 'status' => 500 ) );
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
	 * Check tax settings create access, as the REST taxes endpoint does.
	 *
	 * @return bool
	 *
	 * @since 11.4.0
	 */
	public static function can_create_tax_rates(): bool {
		return (bool) wc_rest_check_manager_permissions( 'settings', 'create' );
	}
}
