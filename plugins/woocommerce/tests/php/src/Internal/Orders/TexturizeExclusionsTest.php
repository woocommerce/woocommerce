<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Internal\OrderReviews\Endpoint;
use Automattic\WooCommerce\Internal\Orders\TexturizeExclusions;
use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalController;
use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalEndpoint;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\MyAccountEndpoint;
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
	 * @testdox On the Review Order, order withdrawal, and stock notification pages, address content is not texturized, while other text is.
	 *
	 * @testWith ["review-order"]
	 *           ["order-withdrawal"]
	 *           ["stock-notifications"]
	 *
	 * @param string $page Email page under test.
	 */
	public function test_skips_address_texturize_on_block_theme_email_pages( string $page ): void {
		$this->open_block_theme_email_page( $page );

		$this->sut->skip_address_texturize_on_order_pages();
		$text = $this->texturize_to_text( "<address>o'neil--shop@48x17.com</address><p>3x4 -- done</p>" );

		$this->assertStringContainsString( "o'neil--shop@48x17.com", $text, 'Address content should not be texturized.' );
		$this->assertStringContainsString( '3×4 — done', $text, 'Text outside the address should still be texturized.' );
	}

	/**
	 * @testdox A review-order query var on another page still texturizes address content.
	 */
	public function test_texturizes_address_when_review_order_query_var_is_on_another_page(): void {
		global $wp;

		$other_page_id  = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$review_page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'woocommerce_review_order_page_id', $review_page_id );
		$this->go_to( get_permalink( $other_page_id ) );
		$wp->query_vars[ Endpoint::QUERY_VAR ] = '15';

		$this->sut->skip_address_texturize_on_order_pages();

		$this->assertStringNotContainsString(
			"o'neil--shop@48x17.com",
			$this->texturize_to_text( "<address>o'neil--shop@48x17.com</address>" ),
			'Address content should be texturized.'
		);
	}

	/**
	 * @testdox A queued notice that contains an address element skips texturizing that element, and other text still changes.
	 */
	public function test_skips_address_texturize_when_a_queued_notice_contains_address_markup(): void {
		$this->go_to( get_permalink( self::factory()->post->create( array( 'post_type' => 'page' ) ) ) );
		wc_add_notice( "<address>o'neil--shop@48x17.com</address>", 'success' );

		$this->sut->skip_address_texturize_on_order_pages();
		$text = $this->texturize_to_text( "<address>o'neil--shop@48x17.com</address><p>3x4 -- done</p>" );

		$this->assertStringContainsString( "o'neil--shop@48x17.com", $text, 'Address content should not be texturized.' );
		$this->assertStringContainsString( '3×4 — done', $text, 'Text outside the address should still be texturized.' );
	}

	/**
	 * @testdox A queued notice without an address element still texturizes address content.
	 */
	public function test_texturizes_address_when_queued_notices_have_no_address_markup(): void {
		$this->go_to( get_permalink( self::factory()->post->create( array( 'post_type' => 'page' ) ) ) );
		wc_add_notice( 'Please confirm your address.', 'success' );

		$this->sut->skip_address_texturize_on_order_pages();

		$this->assertStringNotContainsString(
			"o'neil--shop@48x17.com",
			$this->texturize_to_text( "<address>o'neil--shop@48x17.com</address>" ),
			'Address content should be texturized.'
		);
	}

	/**
	 * @testdox Address content is texturized when the WooCommerce session is missing.
	 */
	public function test_texturizes_address_when_the_session_is_missing(): void {
		$this->go_to( get_permalink( self::factory()->post->create( array( 'post_type' => 'page' ) ) ) );
		$session      = WC()->session;
		WC()->session = null;

		try {
			$this->sut->skip_address_texturize_on_order_pages();
			$this->assertStringNotContainsString(
				"o'neil--shop@48x17.com",
				$this->texturize_to_text( "<address>o'neil--shop@48x17.com</address>" ),
				'Address content should be texturized.'
			);
		} finally {
			WC()->session = $session;
		}
	}

	/**
	 * @testdox Classic themes still texturize address content when a notice contains an address element.
	 */
	public function test_texturizes_address_on_classic_themes_when_a_notice_contains_address_markup(): void {
		switch_theme( 'storefront' );
		$this->assertFalse( wp_is_block_theme(), 'The test requires a classic theme.' );
		$this->go_to( get_permalink( self::factory()->post->create( array( 'post_type' => 'page' ) ) ) );
		wc_add_notice( '<address>kept</address>', 'success' );

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
	 * Open a block-theme page that shows a customer email.
	 *
	 * @param string $page review-order, order-withdrawal, or stock-notifications.
	 */
	private function open_block_theme_email_page( string $page ): void {
		if ( 'review-order' === $page ) {
			$this->go_to_review_order_page();
			return;
		}

		if ( 'order-withdrawal' === $page ) {
			update_option( 'woocommerce_feature_order_withdrawal_enabled', 'yes' );
			wc_get_container()->get( OrderWithdrawalController::class )->register_feature_hooks();
			$this->go_to_account_endpoint( OrderWithdrawalEndpoint::ENDPOINT_KEY );
			return;
		}

		if ( 'stock-notifications' === $page ) {
			new MyAccountEndpoint();
			$this->go_to_account_endpoint( MyAccountEndpoint::ENDPOINT );
			return;
		}

		$this->fail( 'Unknown email page.' );
	}

	/**
	 * Load the Review Order page with its order query var set.
	 */
	private function go_to_review_order_page(): void {
		global $wp;

		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'woocommerce_review_order_page_id', $page_id );
		$this->go_to( add_query_arg( Endpoint::QUERY_VAR, '15', get_permalink( $page_id ) ) );

		// go_to() only keeps query vars WordPress already lists. The endpoint guard reads this one.
		if ( ! isset( $wp->query_vars[ Endpoint::QUERY_VAR ] ) ) {
			$wp->query_vars[ Endpoint::QUERY_VAR ] = '15';
		}
	}

	/**
	 * Load a My Account endpoint page.
	 *
	 * is_wc_endpoint_url() reads the slug query var. Rewrite rules set that on a pretty-permalink request. This helper sets it when the query-argument request did not.
	 *
	 * @param string $key Endpoint key passed to is_wc_endpoint_url().
	 */
	private function go_to_account_endpoint( string $key ): void {
		global $wp;

		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'woocommerce_myaccount_page_id', $page_id );

		$query_vars = WC()->query->get_query_vars();
		$slug       = isset( $query_vars[ $key ] ) && is_string( $query_vars[ $key ] ) && '' !== $query_vars[ $key ] ? $query_vars[ $key ] : $key;

		$this->go_to( add_query_arg( $slug, '', get_permalink( $page_id ) ) );

		if ( ! isset( $wp->query_vars[ $slug ] ) ) {
			$wp->query_vars[ $slug ] = '';
		}
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
