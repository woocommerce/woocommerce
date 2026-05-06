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
abstract class DomainAbility {

	/**
	 * Get common ability metadata.
	 *
	 * @param bool   $is_readonly Whether the ability is readonly.
	 * @param bool   $idempotent  Whether the ability is idempotent.
	 * @param bool   $destructive Whether the ability can mutate data.
	 * @return array
	 */
	protected static function get_ability_meta( bool $is_readonly, bool $idempotent, bool $destructive ): array {
		return array(
			'show_in_rest' => true,
			'mcp'          => array(
				'public' => true,
				'type'   => 'tool',
			),
			'annotations'  => array(
				'readonly'    => $is_readonly,
				'idempotent'  => $idempotent,
				'destructive' => $destructive,
			),
		);
	}

	/**
	 * Get a collection output schema.
	 *
	 * @param string $collection_key Collection property key.
	 * @return array
	 */
	protected static function get_collection_output_schema( string $collection_key ): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				$collection_key => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'additionalProperties' => true,
					),
				),
				'total'         => array( 'type' => 'integer' ),
				'page'          => array( 'type' => 'integer' ),
				'per_page'      => array( 'type' => 'integer' ),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get an entity output schema.
	 *
	 * @param string $entity_key Entity property key.
	 * @return array
	 */
	protected static function get_entity_output_schema( string $entity_key ): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				$entity_key => array(
					'type'                 => 'object',
					'additionalProperties' => true,
				),
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
		return is_array( $input ) && ! empty( $input['id'] ) ? absint( $input['id'] ) : 0;
	}

	/**
	 * Sanitize a per-page value.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	protected static function sanitize_per_page( $value ): int {
		return min( 100, max( 1, absint( $value ) ) );
	}
}
