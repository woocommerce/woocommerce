<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

/**
 * A class outside WC_Data with a saving method and its stage method.
 */
class TestStagedService {

	/**
	 * Save an item.
	 */
	public function add_item(): void {}

	/**
	 * Stage an item.
	 */
	public function stage_item(): void {}
}
