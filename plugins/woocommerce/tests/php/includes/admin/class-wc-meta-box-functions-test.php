<?php
declare( strict_types = 1 );

/**
 * Tests for the woocommerce_wp_* meta box helper functions in wc-meta-box-functions.php.
 *
 * Regression coverage for the HPOS issue where `woocommerce_wp_text_input` (and siblings) would
 * fail to auto-populate their value from order meta when no explicit `value` was passed and the
 * order edit screen was rendered without a global `$post` (the HPOS edit-order screen).
 *
 * @see https://github.com/woocommerce/woocommerce/issues/48454
 */
class WC_Meta_Box_Functions_Test extends WC_Unit_Test_Case {

	/**
	 * Ensure the wc-meta-box-functions.php file is loaded so the helpers are available in tests.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		require_once WC_ABSPATH . 'includes/admin/wc-meta-box-functions.php';
	}

	/**
	 * Reset globals touched by these tests between cases.
	 */
	public function tear_down() {
		global $post, $theorder;
		$post     = null;
		$theorder = null;
		parent::tear_down();
	}

	/**
	 * Render a field helper and return its captured output.
	 *
	 * @param callable $callable Helper function to invoke.
	 * @param array    $args     Arguments to pass.
	 * @return string Captured HTML output.
	 */
	private function capture( callable $callable, array $args ): string {
		ob_start();
		$callable( ...$args );
		return (string) ob_get_clean();
	}

	/**
	 * @testdox woocommerce_wp_text_input reads meta from $theorder when $post is empty (HPOS edit order screen).
	 */
	public function test_text_input_falls_back_to_theorder_when_post_is_empty() {
		global $post, $theorder;

		$order = new WC_Order();
		$order->update_meta_data( 'example_meta', 'hello_example' );
		$order->save();

		$post     = null;
		$theorder = $order;

		$output = $this->capture(
			'woocommerce_wp_text_input',
			array(
				array(
					'id'    => 'example_meta',
					'label' => 'Example meta',
				),
			)
		);

		$this->assertStringContainsString( 'value="hello_example"', $output );
		$this->assertStringContainsString( 'id="example_meta"', $output );
	}

	/**
	 * @testdox woocommerce_wp_text_input prefers an explicitly passed WC_Data object over the global $theorder.
	 */
	public function test_text_input_prefers_explicit_data_argument() {
		global $post, $theorder;

		$order_a = new WC_Order();
		$order_a->update_meta_data( 'example_meta', 'from_theorder' );
		$order_a->save();

		$order_b = new WC_Order();
		$order_b->update_meta_data( 'example_meta', 'from_argument' );
		$order_b->save();

		$post     = null;
		$theorder = $order_a;

		$output = $this->capture(
			'woocommerce_wp_text_input',
			array(
				array(
					'id'    => 'example_meta',
					'label' => 'Example meta',
				),
				$order_b,
			)
		);

		$this->assertStringContainsString( 'value="from_argument"', $output );
	}

	/**
	 * @testdox woocommerce_wp_text_input respects an explicit `value` in the field array.
	 */
	public function test_text_input_respects_explicit_value() {
		global $post, $theorder;

		$order = new WC_Order();
		$order->update_meta_data( 'example_meta', 'meta_value' );
		$order->save();

		$post     = null;
		$theorder = $order;

		$output = $this->capture(
			'woocommerce_wp_text_input',
			array(
				array(
					'id'    => 'example_meta',
					'label' => 'Example meta',
					'value' => 'explicit_value',
				),
			)
		);

		$this->assertStringContainsString( 'value="explicit_value"', $output );
		$this->assertStringNotContainsString( 'value="meta_value"', $output );
	}

	/**
	 * @testdox woocommerce_wp_textarea_input falls back to $theorder when $post is empty.
	 */
	public function test_textarea_input_falls_back_to_theorder() {
		global $post, $theorder;

		$order = new WC_Order();
		$order->update_meta_data( 'textarea_meta', 'multiline body' );
		$order->save();

		$post     = null;
		$theorder = $order;

		$output = $this->capture(
			'woocommerce_wp_textarea_input',
			array(
				array(
					'id'    => 'textarea_meta',
					'label' => 'Textarea meta',
				),
			)
		);

		$this->assertStringContainsString( '>multiline body</textarea>', $output );
	}

	/**
	 * @testdox woocommerce_wp_hidden_input falls back to $theorder when $post is empty.
	 */
	public function test_hidden_input_falls_back_to_theorder() {
		global $post, $theorder;

		$order = new WC_Order();
		$order->update_meta_data( 'hidden_meta', 'hidden_value' );
		$order->save();

		$post     = null;
		$theorder = $order;

		$output = $this->capture(
			'woocommerce_wp_hidden_input',
			array(
				array(
					'id' => 'hidden_meta',
				),
			)
		);

		$this->assertStringContainsString( 'value="hidden_value"', $output );
	}

	/**
	 * @testdox When neither $post nor $theorder is set, the field renders with an empty value (no warnings).
	 */
	public function test_text_input_with_no_context_renders_empty_value() {
		global $post, $theorder;

		$post     = null;
		$theorder = null;

		$output = $this->capture(
			'woocommerce_wp_text_input',
			array(
				array(
					'id'    => 'unknown_meta',
					'label' => 'Unknown meta',
				),
			)
		);

		$this->assertStringContainsString( 'value=""', $output );
	}
}
