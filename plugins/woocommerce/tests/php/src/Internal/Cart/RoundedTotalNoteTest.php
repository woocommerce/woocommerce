<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Cart;

use Automattic\WooCommerce\Internal\Cart\RoundedTotalNote;
use WC_Helper_Product;
use WC_Tax;
use WC_Unit_Test_Case;

/**
 * Tests for the RoundedTotalNote class.
 */
class RoundedTotalNoteTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var RoundedTotalNote
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new RoundedTotalNote();

		update_option( 'woocommerce_currency', 'GBP' );
		update_option( 'woocommerce_default_country', 'GB' );
		update_option( 'woocommerce_tax_based_on', 'billing' );
		foreach ( array(
			'GB' => '20.0000',
			'NL' => '21.0000',
		) as $country => $rate ) {
			WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country'  => $country,
					'tax_rate'          => $rate,
					'tax_rate_name'     => 'VAT',
					'tax_rate_priority' => '1',
					'tax_rate_order'    => '1',
				)
			);
		}
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		WC()->cart->empty_cart();
		WC()->customer->set_is_vat_exempt( false );
		parent::tearDown();
	}

	/**
	 * @testdox Explains a line total that doesn't equal the unit price shown × quantity in a tooltip after it.
	 * @testWith ["1.66", 3, "yes", "no", "incl", "GB", "Rounded to £5.98 from 3 units priced £1.992 (incl. VAT), shown as £1.99 in your cart."]
	 *           ["9.99", 3, "yes", "yes", "excl", "GB", "Rounded to £24.98 from 3 units priced £8.325 (excl. VAT), shown as £8.33 in your cart."]
	 *           ["1.995", 1, "no", "no", "excl", "GB", "Rounded to £2.00 from 1 unit priced £1.995."]
	 *           ["0.207", 2, "no", "no", "excl", "GB", "Rounded to £0.41 from 2 units priced £0.207."]
	 *           ["1.41", 7, "yes", "yes", "incl", "NL", "Rounded to £9.95 from 7 units priced £1.42175 (incl. VAT), shown as £1.42 in your cart."]
	 *           ["1.99", 3, "yes", "yes", "incl", "NL", "Rounded to £6.02 from 3 units priced £2.006583 (incl. VAT), shown as £2.01 in your cart."]
	 *           ["9.99", 3, "yes", "yes", "incl", "US", "Rounded to £24.98 from 3 units priced £8.325, shown as £8.33 in your cart."]
	 *           ["0.207", 3, "yes", "no", "incl", "GB", "Rounded to £0.74 after adding VAT to the line."]
	 *
	 * @param string $price              Product price.
	 * @param int    $quantity           Quantity.
	 * @param string $calc_taxes         Whether taxes are enabled.
	 * @param string $prices_include_tax Whether prices are entered including tax.
	 * @param string $display            Cart tax display.
	 * @param string $country            Customer country.
	 * @param string $expected           Note text.
	 */
	public function test_explains_lines_that_do_not_add_up( string $price, int $quantity, string $calc_taxes, string $prices_include_tax, string $display, string $country, string $expected ): void {
		$cart_item = $this->add_to_cart( $price, $quantity, $calc_taxes, $prices_include_tax, $display, $country );

		$html = $this->sut->add_mini_cart_item_quantity_note( 'ROW', $cart_item );

		$this->assertSame( 'ROW ' . $expected, $this->to_text( $html ) );
	}

	/**
	 * @testdox Makes the total a button, with an info icon first, that opens the note in a popover right after it.
	 */
	public function test_renders_total_as_button_with_popover(): void {
		$cart_item = $this->add_to_cart( '1.66', 3, 'yes', 'no', 'incl', 'GB' );

		$html = $this->sut->add_mini_cart_item_quantity_note( 'ROW', $cart_item );

		$this->assertStringStartsWith( '<span class="wc-rounded-total-info"><button type="button" class="wc-rounded-total-info__button" popovertarget="', $html );
		$id = substr( $html, strpos( $html, 'popovertarget="' ) + 15 );
		$id = substr( $id, 0, (int) strpos( $id, '"' ) );
		$this->assertNotEmpty( $id );
		$this->assertStringContainsString( 'popovertargetaction="show" aria-expanded="false" aria-describedby="' . $id . '"><svg class="wc-rounded-total-info__icon"', $html );
		$this->assertStringContainsString( 'aria-hidden="true"', $html );
		$this->assertStringContainsString( '</svg><span class="wc-rounded-total-info__amount">ROW</span><span class="wc-rounded-total-info__label">Rounding details</span></button><span id="' . $id . '" class="wc-rounded-total-info__tooltip" popover="auto" role="tooltip">Rounded to £5.98 from 3 units priced £1.992 (incl. VAT), shown as £1.99 in your cart.</span></span>', $html );
		$this->assertTrue( wp_script_is( 'wc-rounded-total-info', 'enqueued' ), 'Script enqueued' );
		$this->assertTrue( wp_style_is( 'wc-rounded-total-info', 'enqueued' ), 'Style enqueued' );
	}

	/**
	 * @testdox Loads the assets with a classic Mini-Cart, even an empty one, so totals added later over AJAX work too.
	 */
	public function test_enqueues_assets_with_classic_mini_cart(): void {
		wp_dequeue_script( 'wc-rounded-total-info' );
		wp_dequeue_style( 'wc-rounded-total-info' );

		do_action( 'woocommerce_before_mini_cart' );

		$this->assertTrue( wp_script_is( 'wc-rounded-total-info', 'enqueued' ), 'Script enqueued' );
		$this->assertTrue( wp_style_is( 'wc-rounded-total-info', 'enqueued' ), 'Style enqueued' );
	}

	/**
	 * @testdox Keeps a total with a link outside the button, because a button can't contain one.
	 */
	public function test_keeps_interactive_total_outside_button(): void {
		$cart_item = $this->add_to_cart( '1.66', 3, 'yes', 'no', 'incl', 'GB' );

		$html = $this->sut->add_mini_cart_item_quantity_note( 'ROW <a href="#plan">Plan</a>', $cart_item );

		$this->assertStringContainsString( '<span class="wc-rounded-total-info__label">Rounding details</span></button>', $html );
		$this->assertStringNotContainsString( 'wc-rounded-total-info__amount', $html );
		$this->assertStringEndsWith( '</span>ROW <a href="#plan">Plan</a></span>', $html );
	}

	/**
	 * @testdox Leaves a line alone when the unit price shown × quantity equals the line total.
	 * @testWith ["1.99", 3, "yes", "yes", "incl", "GB"]
	 *           ["1.41", 1, "yes", "yes", "incl", "NL"]
	 *           ["2.10", 4, "no", "no", "excl", "GB"]
	 *           ["1.995", 2, "no", "no", "excl", "GB"]
	 *
	 * @param string $price              Product price.
	 * @param int    $quantity           Quantity.
	 * @param string $calc_taxes         Whether taxes are enabled.
	 * @param string $prices_include_tax Whether prices are entered including tax.
	 * @param string $display            Cart tax display.
	 * @param string $country            Customer country.
	 */
	public function test_leaves_lines_that_add_up( string $price, int $quantity, string $calc_taxes, string $prices_include_tax, string $display, string $country ): void {
		$cart_item = $this->add_to_cart( $price, $quantity, $calc_taxes, $prices_include_tax, $display, $country );

		$this->assertSame( 'ROW', $this->sut->add_mini_cart_item_quantity_note( 'ROW', $cart_item ) );
	}

	/**
	 * @testdox Explains a line in a currency without decimals.
	 */
	public function test_explains_lines_in_currencies_without_decimals(): void {
		update_option( 'woocommerce_currency', 'JPY' );
		update_option( 'woocommerce_price_num_decimals', '0' );
		$cart_item = $this->add_to_cart( '199.5', 1, 'no', 'no', 'excl', 'GB' );

		$text = $this->to_text( $this->sut->add_mini_cart_item_quantity_note( 'ROW', $cart_item ) );

		$this->assertSame( 'ROW Rounded to ¥200 from 1 unit priced ¥199.5.', $text );
	}

	/**
	 * @testdox Adds a note under a subtotal that doesn't equal the sum of the lines when tax is rounded at subtotal level.
	 */
	public function test_adds_note_to_subtotal_when_tax_is_rounded_at_subtotal(): void {
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );
		$this->add_to_cart( '1.66', 3, 'yes', 'no', 'incl', 'GB' );
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => '1.66',
				'virtual'       => true,
			)
		);
		WC()->cart->add_to_cart( $product->get_id(), 3 );
		WC()->cart->calculate_totals();
		add_filter( 'woocommerce_is_cart', '__return_true' );
		$text = $this->to_text( $this->sut->add_cart_subtotal_note( 'SUBTOTAL', false ) );
		remove_filter( 'woocommerce_is_cart', '__return_true' );

		$this->assertSame( 'SUBTOTAL Rounded to £11.95 after adding VAT to the subtotal.', $text );
	}

	/**
	 * Configure taxes, then add a product to an empty cart.
	 *
	 * @param string $price              Product price.
	 * @param int    $quantity           Quantity.
	 * @param string $calc_taxes         Whether taxes are enabled.
	 * @param string $prices_include_tax Whether prices are entered including tax.
	 * @param string $display            Cart tax display.
	 * @param string $country            Customer country.
	 * @return array The cart item.
	 */
	private function add_to_cart( string $price, int $quantity, string $calc_taxes, string $prices_include_tax, string $display, string $country ): array {
		update_option( 'woocommerce_calc_taxes', $calc_taxes );
		update_option( 'woocommerce_prices_include_tax', $prices_include_tax );
		update_option( 'woocommerce_tax_display_cart', $display );
		WC()->customer->set_billing_country( $country );
		WC()->customer->set_shipping_country( $country );
		WC()->cart->empty_cart();

		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => $price,
				'virtual'       => true,
			)
		);
		WC()->cart->add_to_cart( $product->get_id(), $quantity );
		WC()->cart->calculate_totals();

		return current( WC()->cart->get_cart() );
	}

	/**
	 * Convert HTML to plain text, with a space before the info icon.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	private function to_text( string $html ): string {
		$html = str_replace( '<span class="wc-rounded-total-info__label">Rounding details</span>', '', $html );
		$text = html_entity_decode( wp_strip_all_tags( str_replace( '<span id=', ' <span id=', $html ) ) );

		return trim( implode( ' ', array_filter( explode( ' ', str_replace( array( "\n", "\t", "\u{00A0}" ), ' ', $text ) ) ) ) );
	}
}
