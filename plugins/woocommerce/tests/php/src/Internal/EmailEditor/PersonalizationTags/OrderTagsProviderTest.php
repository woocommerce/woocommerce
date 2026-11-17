<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\PersonalizationTags;

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTags\OrderTagsProvider;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
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

	/**
	 * @testdox Registers the fulfillment heading tag.
	 */
	public function test_registers_fulfillment_heading_tag(): void {
		$tag = $this->registry->get_by_token( '[woocommerce/fulfillment-heading]' );

		$this->assertNotNull( $tag, 'The fulfillment heading tag should be registered.' );
		$this->assertSame( __( 'Order', 'woocommerce' ), $tag->get_category() );
	}

	/**
	 * @testdox The fulfillment heading tag uses the plural heading when the fulfillment has several items.
	 */
	public function test_fulfillment_heading_tag_uses_plural_heading(): void {
		$email = $this->create_fulfillment_email( 2 );

		$heading = $this->registry->get_by_token( '[woocommerce/fulfillment-heading]' )->execute_callback( array( 'wc_email' => $email ) );

		$this->assertSame( 'Your items are on the way!', $heading );
	}

	/**
	 * @testdox The fulfillment heading tag uses the singular heading when the fulfillment has one item.
	 */
	public function test_fulfillment_heading_tag_uses_singular_heading(): void {
		$email = $this->create_fulfillment_email( 1 );

		$heading = $this->registry->get_by_token( '[woocommerce/fulfillment-heading]' )->execute_callback( array( 'wc_email' => $email ) );

		$this->assertSame( 'Your item is on the way!', $heading );
	}

	/**
	 * @testdox The fulfillment heading tag returns an empty string for an email that is not about a fulfillment.
	 */
	public function test_fulfillment_heading_tag_for_another_email_type(): void {
		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-completed-order.php';

		$heading = $this->registry->get_by_token( '[woocommerce/fulfillment-heading]' )->execute_callback( array( 'wc_email' => new \WC_Email_Customer_Completed_Order() ) );

		$this->assertSame( '', $heading );
	}

	/**
	 * @testdox The fulfillment heading tag escapes the heading exactly once in HTML.
	 */
	public function test_fulfillment_heading_tag_escapes_the_heading_once_in_html(): void {
		$email  = $this->create_fulfillment_email( 1 );
		$filter = function () {
			return 'Packed & ready';
		};
		add_filter( 'woocommerce_email_heading_customer_fulfillment_created', $filter );

		$personalizer = new Personalizer( $this->registry );
		$personalizer->set_context( array( 'wc_email' => $email ) );

		$html = $personalizer->personalize_content( '<h2><!--[woocommerce/fulfillment-heading]--></h2>' );

		remove_filter( 'woocommerce_email_heading_customer_fulfillment_created', $filter );

		$this->assertSame( '<h2>Packed &amp; ready</h2>', $html );
	}

	/**
	 * @testdox The block fulfillment created template takes its heading from the tag.
	 */
	public function test_block_template_heading_comes_from_the_tag(): void {
		$email  = $this->create_fulfillment_email( 2 );
		$markup = WCTransactionalEmailPostsGenerator::render_block_template_html( $email );

		$personalizer = new Personalizer( $this->registry );
		$personalizer->set_context( array( 'wc_email' => $email ) );

		$this->assertStringContainsString( 'Your items are on the way!', $personalizer->personalize_content( $markup ) );
	}

	/**
	 * @testdox The fulfillment heading tag returns an empty string without email context.
	 */
	public function test_fulfillment_heading_tag_without_email_context(): void {
		$tag = $this->registry->get_by_token( '[woocommerce/fulfillment-heading]' );

		$this->assertSame( '', $tag->execute_callback( array() ) );
	}

	/**
	 * Build a triggered fulfillment created email whose fulfillment covers the order at the given quantity.
	 *
	 * @param int $quantity Quantity fulfilled per order item.
	 * @return \WC_Email_Customer_Fulfillment_Created
	 */
	private function create_fulfillment_email( int $quantity ): \WC_Email_Customer_Fulfillment_Created {
		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-fulfillment-created.php';

		$order = WC_Helper_Order::create_order();
		$items = array();
		foreach ( array_keys( $order->get_items() ) as $item_id ) {
			$items[] = array(
				'item_id' => $item_id,
				'qty'     => $quantity,
			);
		}

		$fulfillment = new Fulfillment();
		$fulfillment->set_items( $items );

		// The fulfillment is private on the email, so triggering is the only way to attach it.
		$email = new \WC_Email_Customer_Fulfillment_Created();
		$email->trigger( $order->get_id(), $fulfillment, $order );

		return $email;
	}
}
