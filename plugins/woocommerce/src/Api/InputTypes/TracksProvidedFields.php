<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\InputTypes;

/**
 * Trait for input types to track which fields were explicitly provided in the GraphQL request.
 *
 * This allows mutations to distinguish between a field being missing (don't change it)
 * and explicitly set to null (clear it).
 */
trait TracksProvidedFields {
	/**
	 * Fields that were explicitly provided in the input.
	 *
	 * Using an underscore prefix to keep it invisible to the ApiBuilder
	 * (which only scans public properties for GraphQL fields).
	 *
	 * @var array<string, true>
	 */
	protected array $provided_fields = array(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase -- internal tracking array

	/**
	 * Mark a field as explicitly provided in the input.
	 *
	 * @param string $field The field name.
	 */
	public function mark_provided( string $field ): void {
		$this->provided_fields[ $field ] = true;
	}

	/**
	 * Check whether a field was explicitly provided in the input.
	 *
	 * @param string $field The field name.
	 * @return bool
	 */
	public function was_provided( string $field ): bool {
		return isset( $this->provided_fields[ $field ] );
	}
}
