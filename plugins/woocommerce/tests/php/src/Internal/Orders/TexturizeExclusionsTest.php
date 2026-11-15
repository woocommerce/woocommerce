<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Internal\Orders\TexturizeExclusions;
use WC_Helper_Order;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the TexturizeExclusions class.
 *
 * @covers \Automattic\WooCommerce\Internal\Orders\TexturizeExclusions
 */
class TexturizeExclusionsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var TexturizeExclusions
	 */
	private $sut;

	/**
	 * Active theme before each test, restored in tearDown.
	 *
	 * @var string
	 */
	private $original_theme;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_theme = get_stylesheet();
		switch_theme( 'twentytwentyfour' );
		$this->sut = new TexturizeExclusions();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		switch_theme( $this->original_theme );
		parent::tearDown();
	}

	/**
	 * @testdox On the order received and view order pages, address content is not texturized, while other text is.
	 *
	 * @testWith ["order-received"]
	 *           ["view-order"]
	 *
	 * @param string $endpoint Order page endpoint.
	 */
	public function test_skips_address_texturize_on_order_pages( string $endpoint ): void {
		$this->go_to_order_page( $endpoint, WC_Helper_Order::create_order() );

		$this->sut->skip_address_texturize_on_order_pages();
		$text = $this->texturize_to_text( "<address>o'neil--shop@48x17.com</address><p>3x4 -- done</p>" );

		$this->assertStringContainsString( "o'neil--shop@48x17.com", $text, 'Address content should not be texturized.' );
		$this->assertStringContainsString( '3×4 — done', $text, 'Text outside the address should still be texturized.' );
	}

	/**
	 * @testdox Address content is texturized as usual on other pages.
	 */
	public function test_texturizes_address_on_other_pages(): void {
		$this->go_to( get_permalink( self::factory()->post->create( array( 'post_type' => 'page' ) ) ) );

		$this->sut->skip_address_texturize_on_order_pages();

		$this->assertStringNotContainsString(
			"o'neil--shop@48x17.com",
			$this->texturize_to_text( "<address>o'neil--shop@48x17.com</address>" ),
			'Address content should be texturized.'
		);
	}

	/**
	 * @testdox Address content is texturized as usual on order pages in classic themes.
	 */
	public function test_texturizes_address_on_order_pages_in_classic_themes(): void {
		switch_theme( 'storefront' );
		$this->assertFalse( wp_is_block_theme(), 'The test requires a classic theme.' );
		$this->go_to_order_page( 'view-order', WC_Helper_Order::create_order() );

		$this->sut->skip_address_texturize_on_order_pages();

		$this->assertStringNotContainsString(
			"o'neil--shop@48x17.com",
			$this->texturize_to_text( "<address>o'neil--shop@48x17.com</address>" ),
			'Address content should be texturized.'
		);
	}

	/**
	 * @testdox On the view order page, the customer details template shows the billing email and address as entered, after kses and wptexturize().
	 */
	public function test_customer_details_template_survives_texturize_on_view_order_page(): void {
		$order = WC_Helper_Order::create_order();
		$order->set_billing_email( "o'neil--shop@48x17.com" );
		$order->set_billing_address_1( '12--14 Main St' );
		$order->save();
		$this->go_to_order_page( 'view-order', $order );

		$this->sut->skip_address_texturize_on_order_pages();
		$text = $this->texturize_to_text( wp_kses_post( wc_get_template_html( 'order/order-details-customer.php', array( 'order' => $order ) ) ) );

		$this->assertStringContainsString( "o'neil--shop@48x17.com", $text, 'The billing email should not be texturized.' );
		$this->assertStringContainsString( '12--14 Main St', $text, 'The billing address should not be texturized.' );
	}

	/**
	 * Load an order endpoint page, so the WooCommerce conditional tags match.
	 *
	 * @param string   $endpoint Order page endpoint.
	 * @param WC_Order $order    Order to view.
	 */
	private function go_to_order_page( string $endpoint, WC_Order $order ): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'order-received' === $endpoint ? 'woocommerce_checkout_page_id' : 'woocommerce_myaccount_page_id', $page_id );

		$this->go_to( add_query_arg( $endpoint, $order->get_id(), get_permalink( $page_id ) ) );
	}

	/**
	 * Texturize HTML and return its decoded text.
	 *
	 * @param string $html HTML to texturize.
	 * @return string
	 */
	private function texturize_to_text( string $html ): string {
		return html_entity_decode( wp_strip_all_tags( wptexturize( $html ) ), ENT_QUOTES | ENT_HTML5 );
	}
}
