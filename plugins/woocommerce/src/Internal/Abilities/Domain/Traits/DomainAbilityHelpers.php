<?php
/**
 * Domain ability helpers trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Shared helpers for WooCommerce domain abilities.
 */
trait DomainAbilityHelpers {

	/**
	 * Format an ability date with its store or UTC offset without changing the object.
	 *
	 * @param \WC_DateTime|null $date Date to format.
	 * @param bool              $utc  Whether to use UTC instead of the store timezone.
	 * @return string|null
	 */
	protected static function format_date_for_response( ?\WC_DateTime $date, bool $utc = true ): ?string {
		if ( null === $date ) {
			return null;
		}

		$date = clone $date;
		$date->setTimezone( $utc ? new \DateTimeZone( 'UTC' ) : wp_timezone() );
		return $date->format( DATE_ATOM );
	}

	/**
	 * Get a price or total as the decimal string that the output schema needs. A
	 * filter can turn the value into a number. Any other type stays as it is, so
	 * the output validation refuses it.
	 *
	 * @param mixed $amount Value from a getter of the object.
	 * @return mixed
	 */
	protected static function format_amount_for_response( $amount ) {
		return is_int( $amount ) || is_float( $amount ) ? wc_float_to_string( (float) $amount ) : $amount;
	}

	/**
	 * Get a collection output schema.
	 *
	 * @param string $collection_key Collection property key.
	 * @param array  $item_schema    JSON schema describing a single item in the collection.
	 * @return array
	 */
	protected static function get_collection_output_schema( string $collection_key, array $item_schema ): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				$collection_key => array(
					'type'        => 'array',
					'description' => sprintf(
						/* translators: %s: Collection key, such as products or orders. */
						__( 'Returned %s for the current page.', 'woocommerce' ),
						$collection_key
					),
					'items'       => $item_schema,
				),
				'total_pages'   => array(
					'type'        => 'integer',
					'description' => __( 'Total number of result pages available for the current query.', 'woocommerce' ),
				),
				'page'          => array(
					'type'        => 'integer',
					'description' => __( 'Current result page.', 'woocommerce' ),
				),
				'per_page'      => array(
					'type'        => 'integer',
					'description' => __( 'Maximum number of items requested per page.', 'woocommerce' ),
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get an entity output schema.
	 *
	 * @param string $entity_key  Entity property key.
	 * @param array  $item_schema JSON schema describing the entity.
	 * @return array
	 */
	protected static function get_entity_output_schema( string $entity_key, array $item_schema ): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				$entity_key => $item_schema,
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get a delete output schema.
	 *
	 * @return array
	 */
	protected static function get_delete_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'deleted' => array( 'type' => 'boolean' ),
				'id'      => array( 'type' => 'integer' ),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get an ID value from ability input.
	 *
	 * @param mixed $input Ability input.
	 * @return int
	 */
	protected static function get_id_from_input( $input ): int {
		return is_array( $input ) && ! empty( $input['id'] ) ? (int) $input['id'] : 0;
	}
}
