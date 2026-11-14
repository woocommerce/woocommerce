<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTags\OrderTagsProvider;
use WC_Helper_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the OrderTagsProvider class.
 */
class OrderTagsProviderTest extends WC_Unit_Test_Case {

	/**
	 * Registry with the order tags registered.
	 *
	 * @var Personalization_Tags_Registry
	 */
	private $registry;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->registry = new Personalization_Tags_Registry( new Email_Editor_Logger() );
		( new OrderTagsProvider() )->register_tags( $this->registry );
	}

	/**
	 * @testdox Registers the order review URL tag.
	 */
	public function test_registers_order_review_url_tag(): void {
		$tag = $this->registry->get_by_token( '[woocommerce/order-review-url]' );

		$this->assertNotNull( $tag, 'The order review URL tag should be registered.' );
		$this->assertSame( __( 'Order', 'woocommerce' ), $tag->get_category() );
	}

	/**
	 * @testdox The order review URL tag resolves to the order's review URL.
	 */
	public function test_order_review_url_tag_resolves_to_review_url(): void {
		$order = WC_Helper_Order::create_order();

		// The review-order page is not seeded in the test environment, so pin the
		// URL through the public filter to prove the tag delegates to it per order.
		$expected = 'https://example.test/review-order/' . $order->get_id() . '/?key=' . $order->get_order_key();
		$filter   = function ( $url, $filter_order ) use ( $order, $expected ) {
			unset( $url );
			return $filter_order->get_id() === $order->get_id() ? $expected : '';
		};
		add_filter( 'woocommerce_review_order_url', $filter, 10, 2 );

		$url = $this->registry->get_by_token( '[woocommerce/order-review-url]' )->execute_callback( array( 'order' => $order ) );

		remove_filter( 'woocommerce_review_order_url', $filter, 10 );

		$this->assertSame( $expected, $url );
	}

	/**
	 * @testdox The order review URL tag returns an empty string without order context.
	 */
	public function test_order_review_url_tag_without_order_context(): void {
		$tag = $this->registry->get_by_token( '[woocommerce/order-review-url]' );

		$this->assertSame( '', $tag->execute_callback( array() ) );
	}

	/**
	 * @testdox The order date tag escapes markup that its format argument produces in HTML.
	 */
	public function test_order_date_tag_escapes_markup_from_format(): void {
		$order = WC_Helper_Order::create_order();
		$order->set_date_created( '2024-03-15 10:00:00' );
		$order->save();
		$personalizer = new Personalizer( $this->registry );
		$personalizer->set_context( array( 'order' => $order ) );

		$html = $personalizer->personalize_content( '<p><!--[woocommerce/order-date format="&lt;\\m\\a\\r\\k&gt;Y&lt;/\\m\\a\\r\\k&gt;"]--></p>' );

		$this->assertSame( '<p>&lt;mark&gt;2024&lt;/mark&gt;</p>', $html );
	}

	/**
	 * @testdox The order custom field tag finds a field whose key has an apostrophe and shows its value as text.
	 */
	public function test_order_custom_field_tag_reads_key_with_apostrophe_and_shows_text(): void {
		$order = wc_create_order();
		$order->update_meta_data( "Customer's note", '<b>Leave</b> at the door' );
		$order->save();
		$personalizer = new Personalizer( $this->registry );
		$personalizer->set_context( array( 'order' => $order ) );

		$html = $personalizer->personalize_content( '<p><!--[woocommerce/order-custom-field key="Customer&#039;s note"]--></p>' );

		$this->assertSame( '<p>&lt;b&gt;Leave&lt;/b&gt; at the door</p>', $html );
	}
}
