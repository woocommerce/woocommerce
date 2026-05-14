<?php
/**
 * Domain ability base class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * Shared helpers for WooCommerce domain ability definitions.
 */
abstract class AbstractDomainAbility {

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
