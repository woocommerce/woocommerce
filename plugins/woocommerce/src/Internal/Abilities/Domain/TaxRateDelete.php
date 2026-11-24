<?php
/**
 * Tax rate delete ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\TaxRateAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the ability that deletes a tax rate. Tax rates have no trash, so the delete is permanent.
 */
class TaxRateDelete extends AbstractDomainAbility implements AbilityDefinition {

	use TaxRateAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/tax-rate-delete';
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
			'label'               => __( 'Delete tax rate', 'woocommerce' ),
			'description'         => __( 'Permanently delete a tax rate by its id from tax-rates-query. Returns the deleted rate; send its fields except id to tax-rate-create to add it back.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'id' => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
				),
				'required'             => array( 'id' ),
				'additionalProperties' => false,
			),
			'output_schema'       => self::get_entity_output_schema( 'rate', self::get_rate_output_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_delete_tax_rates' ),
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
	 * Delete the rate and its postcodes and cities.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 11.4.0
	 */
	public static function execute( array $input ) {
		global $wpdb;

		$rate = self::get_rate_or_error( $input['id'] );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		\WC_Tax::_delete_tax_rate( $input['id'] );
		// Before the check, since a failed check can follow a delete that did happen.
		self::refresh_shipping_rates_cache();

		// The deleted hook may run queries of its own, so check the row rather than $wpdb->rows_affected.
		// A failed check returns no row too, so its error counts as a failed delete.
		if ( \WC_Tax::_get_tax_rate( $input['id'] ) || '' !== $wpdb->last_error ) {
			return new \WP_Error( 'woocommerce_ability_tax_rate_delete_failed', __( 'The tax rate could not be deleted.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		return array( 'rate' => $rate );
	}

	/**
	 * Check tax settings delete access, as the REST taxes endpoint does.
	 *
	 * @return bool
	 *
	 * @since 11.4.0
	 */
	public static function can_delete_tax_rates(): bool {
		return (bool) wc_rest_check_manager_permissions( 'settings', 'delete' );
	}
}
