<?php
declare( strict_types = 1 );

/**
 * Heading hierarchy tests for the order data meta box.
 *
 * @package WooCommerce\Tests\Admin\MetaBoxes
 */

/**
 * Class WC_Meta_Box_Order_Data_Heading_Hierarchy_Test
 *
 * Regression coverage for RSMAPGJ-266 / woo#60963 (incorrect heading order on the admin
 * single-order screen). The WordPress meta-box chrome wraps the box body in an `<h2 class="hndle">`
 * title, so any headings rendered inside the box body must use `<h3>` or smaller to avoid skipping
 * heading levels for assistive technology.
 */
class WC_Meta_Box_Order_Data_Heading_Hierarchy_Test extends WC_Unit_Test_Case {

	/**
	 * Render the order data meta box for a freshly created order and return the markup.
	 *
	 * @return string Rendered HTML.
	 */
	private function render_meta_box_output(): string {
		// Ensure the meta-box class is loaded.
		if ( ! class_exists( 'WC_Meta_Box_Order_Data' ) ) {
			require_once WC_ABSPATH . 'includes/admin/meta-boxes/class-wc-meta-box-order-data.php';
		}

		$order = wc_create_order();

		ob_start();
		WC_Meta_Box_Order_Data::output( $order );
		return (string) ob_get_clean();
	}

	/**
	 * @testdox Inner order details heading should be h3 (one level below the meta-box h2 hndle).
	 */
	public function test_order_details_heading_is_h3(): void {
		$markup = $this->render_meta_box_output();

		$this->assertMatchesRegularExpression(
			'#<h3[^>]*class="[^"]*woocommerce-order-data__heading[^"]*"#',
			$markup,
			'The "Order #N details" heading inside the order data meta box must render as <h3> so it nests below the meta-box <h2 class="hndle"> title without skipping heading levels.'
		);
		$this->assertDoesNotMatchRegularExpression(
			'#<h2[^>]*class="[^"]*woocommerce-order-data__heading[^"]*"#',
			$markup,
			'The legacy <h2 class="woocommerce-order-data__heading"> must not be re-introduced; it duplicated the meta-box title heading level.'
		);
	}

	/**
	 * @testdox Section sub-headings (General, Billing, Shipping) should be h4.
	 */
	public function test_section_sub_headings_are_h4(): void {
		$markup = $this->render_meta_box_output();

		$this->assertStringContainsString(
			'<h4>General</h4>',
			$markup,
			'The "General" section heading inside the order data meta box must render as <h4>.'
		);

		// Billing and Shipping h4 elements contain inline action links, so match the opening tag and label.
		$this->assertMatchesRegularExpression(
			'#<h4>\s*Billing#',
			$markup,
			'The "Billing" section heading inside the order data meta box must render as <h4>.'
		);
		$this->assertMatchesRegularExpression(
			'#<h4>\s*Shipping#',
			$markup,
			'The "Shipping" section heading inside the order data meta box must render as <h4>.'
		);
	}

	/**
	 * @testdox Order data meta box markup must not skip heading levels.
	 *
	 * Assistive technology relies on monotonically increasing heading levels; jumping from h3 to h5
	 * (or similar) inside this meta-box body would be a regression.
	 */
	public function test_no_heading_level_skips_in_meta_box(): void {
		$markup = $this->render_meta_box_output();

		preg_match_all( '#<h([1-6])\b#i', $markup, $matches );
		$levels = array_map( 'intval', $matches[1] );

		$this->assertNotEmpty( $levels, 'Expected at least one heading inside the order data meta box body.' );

		// Every heading inside the meta-box body must be h3 or deeper (h2 is owned by the WordPress meta-box chrome).
		foreach ( $levels as $level ) {
			$this->assertGreaterThanOrEqual( 3, $level, 'Headings inside the order data meta box body must be h3 or deeper.' );
		}

		// Walk the headings in document order and confirm no jump greater than +1.
		$previous = $levels[0];
		foreach ( $levels as $level ) {
			$this->assertLessThanOrEqual(
				$previous + 1,
				$level,
				sprintf( 'Heading level jumped from h%d to h%d, which skips a level.', $previous, $level )
			);
			if ( $level > $previous ) {
				$previous = $level;
			} elseif ( $level < $previous ) {
				$previous = $level;
			}
		}
	}
}
