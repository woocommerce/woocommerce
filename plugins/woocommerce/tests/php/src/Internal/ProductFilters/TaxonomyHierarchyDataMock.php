<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\TaxonomyHierarchyData;

defined( 'ABSPATH' ) || exit;

/**
 * Mock subclass for strategy testing.
 */
class TaxonomyHierarchyDataMock extends TaxonomyHierarchyData {
	/**
	 * Test threshold override.
	 *
	 * @var int|null
	 */
	private $test_threshold = null;

	/**
	 * Set threshold for testing.
	 *
	 * @param int $threshold The threshold value.
	 */
	public function set_threshold( int $threshold ): void {
		$this->test_threshold = $threshold;
	}

	/**
	 * Get threshold with test override.
	 *
	 * @return int The threshold value.
	 */
	protected function get_threshold(): int {
		return $this->test_threshold ?? parent::get_threshold();
	}
}
