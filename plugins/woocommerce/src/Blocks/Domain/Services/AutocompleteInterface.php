<?php

namespace Automattic\WooCommerce\Blocks\Domain\Services;

/**
 * Interface describing address autocomplete providers.
 */
interface AutocompleteInterface {
	public function search( string $query ): array;
	public function get_address( mixed $id ): array;
	public function get_fields(): array;
}
