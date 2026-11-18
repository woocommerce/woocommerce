<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Webhooks;

use Automattic\WooCommerce\Internal\Webhooks\WebhookTopicNotices;
use WC_Admin_Notices;
use WC_Data_Store;
use WC_Unit_Test_Case;
use WC_Webhook;

/**
 * Tests for the WebhookTopicNotices class.
 */
class WebhookTopicNoticesTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WebhookTopicNotices
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		delete_option( WebhookTopicNotices::TOPIC_CHECK_OPTION );
		$this->sut = new WebhookTopicNotices();
	}

	/**
	 * @testdox Should disable stored webhooks on refused topics, keep their topic, and list them in a notice.
	 */
	public function test_disables_webhooks_on_refused_topics_and_adds_notice(): void {
		$blocked = $this->create_webhook( 'Mail webhook', 'action.woocommerce_add_to_cart' );
		$this->set_raw_topic( $blocked->get_id(), 'action.woocommerce_mail_content' );
		$allowed = $this->create_webhook( 'Cart tracker', 'action.woocommerce_add_to_cart' );

		$data_store = WC_Data_Store::load( 'webhook' );
		$this->assertContains( $blocked->get_id(), $data_store->get_webhooks_ids( 'active' ) );
		$updated_ids = array();
		add_action(
			'woocommerce_webhook_updated',
			function ( $webhook_id ) use ( &$updated_ids, $data_store ) {
				$updated_ids[] = $webhook_id;
				$this->assertSame( 'disabled', wc_get_webhook( $webhook_id )->get_status() );
				$this->assertNotContains( $webhook_id, $data_store->get_webhooks_ids( 'active' ) );
			}
		);

		$this->sut->handle_admin_init();
		$this->sut->handle_admin_init();

		$this->assertSame( array( $blocked->get_id() ), $updated_ids, 'Only the webhook that was disabled should send one update notification.' );
		$this->assertSame( 'disabled', $this->get_raw_column( $blocked->get_id(), 'status' ), 'The webhook on a refused topic should be disabled' );
		$this->assertSame( 'action.woocommerce_mail_content', $this->get_raw_column( $blocked->get_id(), 'topic' ), 'The stored topic should be kept' );
		$this->assertSame( 'active', wc_get_webhook( $allowed->get_id() )->get_status(), 'A webhook on an allowed topic should stay active' );
		$this->assertTrue( WC_Admin_Notices::has_notice( WebhookTopicNotices::BLOCKED_TOPICS_NOTICE ), 'A notice should be added' );
		$this->assertStringContainsString( 'action.woocommerce_mail_content', (string) get_option( 'woocommerce_admin_notice_' . WebhookTopicNotices::BLOCKED_TOPICS_NOTICE ), 'The notice should name the refused topic' );
	}

	/**
	 * @testdox Lists already-disabled webhooks without claiming that their status changed.
	 */
	public function test_notice_includes_already_disabled_webhook(): void {
		$webhook = $this->create_webhook( 'Old PayPal webhook', 'action.woocommerce_add_to_cart' );
		$webhook->set_status( 'disabled' );
		$webhook->save();
		$this->set_raw_topic( $webhook->get_id(), 'action.woocommerce_paypal_refund_request' );
		$updates = did_action( 'woocommerce_webhook_updated' );

		$this->sut->handle_admin_init();

		$notice = (string) get_option( 'woocommerce_admin_notice_' . WebhookTopicNotices::BLOCKED_TOPICS_NOTICE );
		$this->assertStringContainsString( 'Some webhooks use topics that are no longer supported.', $notice );
		$this->assertStringContainsString( 'Old PayPal webhook', $notice );
		$this->assertSame( 'disabled', $this->get_raw_column( $webhook->get_id(), 'status' ) );
		$this->assertSame( $updates, did_action( 'woocommerce_webhook_updated' ), 'Already-disabled webhooks should only appear in the notice.' );
	}

	/**
	 * @testdox Should check stored webhooks only once per topic rules version.
	 */
	public function test_checks_stored_webhooks_once(): void {
		$this->sut->handle_admin_init();

		$blocked = $this->create_webhook( 'Mail webhook', 'action.woocommerce_add_to_cart' );
		$this->set_raw_topic( $blocked->get_id(), 'action.woocommerce_mail_content' );
		$this->sut->handle_admin_init();

		$this->assertSame( 'active', $this->get_raw_column( $blocked->get_id(), 'status' ), 'A later admin page load should not scan the webhooks again' );
	}

	/**
	 * Create an active webhook.
	 *
	 * @param string $name  Webhook name.
	 * @param string $topic Webhook topic.
	 * @return WC_Webhook
	 */
	private function create_webhook( string $name, string $topic ): WC_Webhook {
		$webhook = new WC_Webhook();
		$webhook->set_name( $name );
		$webhook->set_status( 'active' );
		$webhook->set_topic( $topic );
		$webhook->set_delivery_url( 'https://example.com/webhook' );
		$webhook->set_user_id( 1 );
		$webhook->set_pending_delivery( false );
		$webhook->save();

		return $webhook;
	}

	/**
	 * Store a topic directly, as a webhook saved before the topic was refused would have it.
	 *
	 * @param int    $webhook_id Webhook ID.
	 * @param string $topic      Raw topic.
	 */
	private function set_raw_topic( int $webhook_id, string $topic ): void {
		global $wpdb;
		$wpdb->update( "{$wpdb->prefix}wc_webhooks", array( 'topic' => $topic ), array( 'webhook_id' => $webhook_id ) );
		wp_cache_delete( $webhook_id, 'webhooks' );
	}

	/**
	 * Read a stored webhook column.
	 *
	 * @param int    $webhook_id Webhook ID.
	 * @param string $column     Column name.
	 * @return string
	 */
	private function get_raw_column( int $webhook_id, string $column ): string {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Column name is a test constant.
		return (string) $wpdb->get_var( $wpdb->prepare( "SELECT {$column} FROM {$wpdb->prefix}wc_webhooks WHERE webhook_id = %d", $webhook_id ) );
	}
}
