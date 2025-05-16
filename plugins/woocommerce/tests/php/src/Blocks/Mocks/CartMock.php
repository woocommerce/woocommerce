<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

use Automattic\WooCommerce\Blocks\BlockTypes\Cart;

/**
 * A mock class.
 */
class CartMock extends Cart {

	/**
	 * Mock the enqueue_data method so we can call it from tests.
	 *
	 * @return void
	 */
	public function mock_enqueue_data() {
		$this->enqueue_data();
	}
}
