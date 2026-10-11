<?php
declare( strict_types = 1 );

/**
 * Shipping row tests for the `email-order-details.php` templates.
 *
 * @covers `email-order-details.php` template
 */
class WC_Email_Order_Details_Shipping_Test extends \WC_Unit_Test_Case {

	private const METHOD_NAME = 'Shipping TBD';

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_feature_email_improvements_enabled', 'yes' );
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_feature_email_improvements_enabled', 'no' );
		parent::tearDown();
	}

	/**
	 * Shipping methods that cost nothing, paired with what the value cell should then say.
	 *
	 * Every case uses the same method name so the shipping method id is the only thing that varies.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
	 */
	public function provide_shipping_methods(): array {
		return array(
			'free shipping'          => array( 'free_shipping', '0', 'Free!', self::METHOD_NAME ),
			'zero cost flat rate'    => array( 'flat_rate', '0', self::METHOD_NAME, 'Free!' ),
			'zero cost local pickup' => array( 'local_pickup', '0', self::METHOD_NAME, 'Free!' ),
			'paid flat rate'         => array( 'flat_rate', '5.00', '5.00', 'Free!' ),
		);
	}

	/**
	 * @testdox HTML email shows the right shipping value for the method and cost.
	 * @dataProvider provide_shipping_methods
	 *
	 * @param string $method_id    Shipping method id on the order.
	 * @param string $total        Shipping cost.
	 * @param string $expected     Text the value cell should contain.
	 * @param string $not_expected Text the value cell should not contain.
	 */
	public function test_html_shipping_value( string $method_id, string $total, string $expected, string $not_expected ): void {
		$order = $this->create_order_with_shipping( $method_id, $total );

		$value = $this->get_html_shipping_value( $this->render( 'emails/email-order-details.php', $order, false ) );

		$this->assertStringContainsString( $expected, $value, "Shipping value cell for a {$method_id} costing {$total} should say '{$expected}'" );
		$this->assertStringNotContainsString( $not_expected, $value, "Shipping value cell for a {$method_id} costing {$total} should not say '{$not_expected}'" );
	}

	/**
	 * @testdox Plain text email shows the right shipping value for the method and cost.
	 * @dataProvider provide_shipping_methods
	 *
	 * @param string $method_id    Shipping method id on the order.
	 * @param string $total        Shipping cost.
	 * @param string $expected     Text the value column should contain.
	 * @param string $not_expected Text the value column should not contain.
	 */
	public function test_plain_shipping_value( string $method_id, string $total, string $expected, string $not_expected ): void {
		$order = $this->create_order_with_shipping( $method_id, $total );

		$value = $this->get_plain_shipping_value( $this->render( 'emails/plain/email-order-details.php', $order, true ) );

		$this->assertStringContainsString( $expected, $value, "Shipping value for a {$method_id} costing {$total} should say '{$expected}'" );
		$this->assertStringNotContainsString( $not_expected, $value, "Shipping value for a {$method_id} costing {$total} should not say '{$not_expected}'" );
	}

	/**
	 * @testdox Method name stays in the row header even when the value cell says 'Free!'.
	 */
	public function test_free_shipping_keeps_method_name_in_header(): void {
		$order = $this->create_order_with_shipping( 'free_shipping', '0' );

		$header = $this->get_html_shipping_header( $this->render( 'emails/email-order-details.php', $order, false ) );

		$this->assertStringContainsString( self::METHOD_NAME, $header, 'Replacing the value cell must not cost the customer the method name' );
	}

	/**
	 * @testdox A filter can force the free label on for a method that is not free shipping.
	 */
	public function test_filter_can_force_free_label_on(): void {
		add_filter( 'woocommerce_email_order_shipping_show_free_label', '__return_true' );
		$order = $this->create_order_with_shipping( 'flat_rate', '0' );

		$value = $this->get_html_shipping_value( $this->render( 'emails/email-order-details.php', $order, false ) );

		$this->assertSame( 'Free!', $value, 'A store that means a zero cost Flat Rate as free should be able to say so' );
	}

	/**
	 * @testdox A filter can force the free label off for free shipping.
	 */
	public function test_filter_can_force_free_label_off(): void {
		add_filter( 'woocommerce_email_order_shipping_show_free_label', '__return_false' );
		$order = $this->create_order_with_shipping( 'free_shipping', '0' );

		$value = $this->get_html_shipping_value( $this->render( 'emails/email-order-details.php', $order, false ) );

		$this->assertSame( self::METHOD_NAME, $value, 'Turning the label off should restore the method name as the value' );
	}

	/**
	 * @testdox The filter receives the order and the default for the method on it.
	 */
	public function test_filter_receives_order_and_default(): void {
		$received = array();
		add_filter(
			'woocommerce_email_order_shipping_show_free_label',
			function ( $show_free_label, $order ) use ( &$received ) {
				$received[] = array( $show_free_label, $order->get_id() );
				return $show_free_label;
			},
			10,
			2
		);
		$order = $this->create_order_with_shipping( 'flat_rate', '0' );

		$this->render( 'emails/email-order-details.php', $order, false );

		$this->assertSame( array( array( false, $order->get_id() ) ), $received );
	}

	/**
	 * @testdox The filter also applies to plain text emails.
	 */
	public function test_filter_applies_to_plain_text(): void {
		add_filter( 'woocommerce_email_order_shipping_show_free_label', '__return_true' );
		$order = $this->create_order_with_shipping( 'flat_rate', '0' );

		$value = $this->get_plain_shipping_value( $this->render( 'emails/plain/email-order-details.php', $order, true ) );

		$this->assertSame( 'Free!', $value );
	}

	/**
	 * @testdox The filter does not run for a paid shipping method.
	 */
	public function test_filter_does_not_run_for_paid_shipping(): void {
		add_filter( 'woocommerce_email_order_shipping_show_free_label', '__return_true' );
		$order = $this->create_order_with_shipping( 'flat_rate', '5.00' );

		$value = $this->get_html_shipping_value( $this->render( 'emails/email-order-details.php', $order, false ) );

		$this->assertStringNotContainsString( 'Free!', $value, 'A paid method must stay out of reach of the filter' );
	}

	/**
	 * @testdox The 'Free!' label is off when the email improvements feature is disabled.
	 */
	public function test_no_free_label_without_email_improvements(): void {
		update_option( 'woocommerce_feature_email_improvements_enabled', 'no' );
		$order = $this->create_order_with_shipping( 'free_shipping', '0' );

		$content = $this->render( 'emails/email-order-details.php', $order, false );

		$this->assertStringNotContainsString( 'Free!', $content );
	}

	/**
	 * Builds an order carrying a single shipping line.
	 *
	 * @param string $method_id Shipping method id, such as `flat_rate`.
	 * @param string $total     Shipping cost.
	 * @return WC_Order
	 */
	private function create_order_with_shipping( string $method_id, string $total ): WC_Order {
		$item = new WC_Order_Item_Shipping();
		$item->set_props(
			array(
				'method_title' => self::METHOD_NAME,
				'method_id'    => $method_id,
				'total'        => wc_format_decimal( $total ),
			)
		);

		$order = wc_create_order();
		$order->add_item( $item );
		$order->calculate_totals( false );
		$order->save();

		return $order;
	}

	/**
	 * Renders an order details template.
	 *
	 * @param string   $template   Template path.
	 * @param WC_Order $order      Order to render.
	 * @param bool     $plain_text Whether the template is the plain text one.
	 * @return string
	 */
	private function render( string $template, WC_Order $order, bool $plain_text ): string {
		return wc_get_template_html(
			$template,
			array(
				'order'         => $order,
				'sent_to_admin' => false,
				'plain_text'    => $plain_text,
				'email'         => new WC_Email(),
			)
		);
	}

	/**
	 * Reads the value cell out of the shipping row of a rendered HTML email.
	 *
	 * @param string $content Rendered template.
	 * @return string
	 */
	private function get_html_shipping_value( string $content ): string {
		return $this->match_html_shipping_row( $content, 2 );
	}

	/**
	 * Reads the row header out of the shipping row of a rendered HTML email.
	 *
	 * @param string $content Rendered template.
	 * @return string
	 */
	private function get_html_shipping_header( string $content ): string {
		return $this->match_html_shipping_row( $content, 1 );
	}

	/**
	 * Reads one cell out of the shipping row of a rendered HTML email.
	 *
	 * @param string $content Rendered template.
	 * @param int    $group   1 for the row header, 2 for the value cell.
	 * @return string
	 */
	private function match_html_shipping_row( string $content, int $group ): string {
		$matched = preg_match(
			'#<tr class="order-totals order-totals-shipping[^"]*">\s*<th[^>]*>(.*?)</th>\s*<td[^>]*>(.*?)</td>#s',
			$content,
			$matches
		);

		$this->assertSame( 1, $matched, 'Rendered email should contain a shipping totals row' );

		return trim( wp_strip_all_tags( $matches[ $group ] ) );
	}

	/**
	 * Reads the value column out of the shipping line of a rendered plain text email.
	 *
	 * The template pads the label to 40 characters, then a space, then the value.
	 *
	 * @param string $content Rendered template.
	 * @return string
	 */
	private function get_plain_shipping_value( string $content ): string {
		foreach ( explode( "\n", $content ) as $line ) {
			if ( 0 === strpos( $line, 'Shipping:' ) ) {
				return trim( substr( $line, 40 ) );
			}
		}

		$this->fail( 'Rendered plain text email should contain a shipping line' );
	}
}
