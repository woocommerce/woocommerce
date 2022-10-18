<?php
/**
 * Mock product query.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

/**
 * Mock product query.
 */
class MockProductQuery {
	/**
	 * Construct the mock product query with the given number of products.
	 *
	 * @param integer $total The number of products.
	 */
	public function __construct( $total ) {
		$this->total = $total;
	}

	/**
	 * Gets the mock products
	 *
	 * @return array
	 */
	public function get_products() {
		return (object) array(
			'total' => $this->total,
		);
	}
}

