<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

use Automattic\WooCommerce\Blocks\BlockTypes\Checkout;

/**
 * A mock class.
 */
class CheckoutMock extends Checkout {

	/**
	 * Mock the enqueue_data method so we can call it from tests.
	 *
	 * @return void
	 */
	public function mock_enqueue_data() {
		$this->enqueue_data();
	}

	/**
	 * Expose the protected render method so tests can exercise it directly.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param mixed  $block      Block instance.
	 * @return string
	 */
	public function call_render( $attributes = array(), $content = '', $block = null ) {
		return $this->render( $attributes, $content, $block );
	}
}
