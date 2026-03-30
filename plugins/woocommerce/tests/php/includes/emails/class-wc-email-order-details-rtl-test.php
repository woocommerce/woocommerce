<?php
declare( strict_types = 1 );

/**
 * RTL support tests for `email-order-details.php` template.
 *
 * @covers `email-order-details.php` template
 */
class WC_Email_Order_Details_RTL_Test extends \WC_Unit_Test_Case {

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		update_option( 'woocommerce_feature_email_improvements_enabled', 'no' );
		$GLOBALS['wp_locale']->text_direction = 'ltr';
	}

	/**
	 * Renders the order details template and returns the HTML.
	 *
	 * @return string
	 */
	private function render_order_details_template(): string {
		$order = wc_create_order();
		return wc_get_template_html(
			'emails/email-order-details.php',
			array(
				'order'         => $order,
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email'         => new WC_Email(),
			)
		);
	}

	/**
	 * @testdox Table headers use CSS classes for alignment with email improvements enabled.
	 */
	public function test_th_elements_use_css_classes_with_improvements(): void {
		update_option( 'woocommerce_feature_email_improvements_enabled', 'yes' );

		$content = $this->render_order_details_template();

		$this->assertStringContainsString( 'class="td text-align-left" scope="col"', $content, 'Product header should use text-align-left CSS class' );
		$this->assertStringContainsString( 'class="td text-align-right" scope="col"', $content, 'Quantity/Price headers should use text-align-right CSS class' );
		$this->assertDoesNotMatchRegularExpression( '/<th[^>]*style="text-align:[^"]*"/', $content, 'No <th> elements should have inline text-align styles' );
	}

	/**
	 * @testdox Table headers use text-align-left CSS class when email improvements are disabled.
	 */
	public function test_th_elements_use_text_align_left_without_improvements(): void {
		update_option( 'woocommerce_feature_email_improvements_enabled', 'no' );

		$content = $this->render_order_details_template();

		$this->assertStringContainsString( 'class="td text-align-left" scope="col"', $content, 'All headers should use text-align-left CSS class when improvements disabled' );
		$this->assertDoesNotMatchRegularExpression( '/<th[^>]*text-align-right/', $content, 'No headers should use text-align-right when improvements disabled' );
		$this->assertDoesNotMatchRegularExpression( '/<th[^>]*style="text-align:[^"]*"/', $content, 'No <th> elements should have inline text-align styles' );
	}

	/**
	 * @testdox Table headers use correct CSS classes in RTL locale with email improvements enabled.
	 */
	public function test_th_elements_use_css_classes_in_rtl_with_improvements(): void {
		$GLOBALS['wp_locale']->text_direction = 'rtl';
		update_option( 'woocommerce_feature_email_improvements_enabled', 'yes' );

		$content = $this->render_order_details_template();

		$this->assertStringContainsString( 'class="td text-align-left" scope="col"', $content, 'Product header should use text-align-left class in RTL (CSS handles flipping)' );
		$this->assertStringContainsString( 'class="td text-align-right" scope="col"', $content, 'Quantity/Price headers should use text-align-right class in RTL (CSS handles flipping)' );
		$this->assertDoesNotMatchRegularExpression( '/<th[^>]*style="text-align:[^"]*"/', $content, 'No <th> elements should have inline text-align styles in RTL' );
	}

	/**
	 * @testdox Table headers use correct CSS classes in RTL locale with email improvements disabled.
	 */
	public function test_th_elements_use_css_classes_in_rtl_without_improvements(): void {
		$GLOBALS['wp_locale']->text_direction = 'rtl';
		update_option( 'woocommerce_feature_email_improvements_enabled', 'no' );

		$content = $this->render_order_details_template();

		$this->assertStringContainsString( 'class="td text-align-left" scope="col"', $content, 'All headers should use text-align-left class in RTL when improvements disabled' );
		$this->assertDoesNotMatchRegularExpression( '/<th[^>]*text-align-right/', $content, 'No headers should use text-align-right in RTL when improvements disabled' );
		$this->assertDoesNotMatchRegularExpression( '/<th[^>]*style="text-align:[^"]*"/', $content, 'No <th> elements should have inline text-align styles in RTL' );
	}
}
