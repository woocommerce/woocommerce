<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Webhooks;

use WC_Unit_Test_Case;
use WC_Webhook;

/**
 * Tests for the WebhookScheduledActionArgs class.
 */
class WebhookScheduledActionArgsTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should show queued custom action webhook deliveries without their internal fields.
	 */
	public function test_redacts_custom_action_delivery_args(): void {
		$webhook = $this->create_webhook( 'action.woocommerce_checkout_posted_data' );

		$html = $this->get_args_column( $webhook->get_id(), array( 'account_password' => 'fixture-value' ) );

		$this->assertStringNotContainsString( 'fixture-value', $html );
		$this->assertStringContainsString( '[redacted]', $html );
	}

	/**
	 * @testdox Should hide the whole argument of deliveries for deleted webhooks.
	 */
	public function test_hides_args_of_deleted_webhooks(): void {
		$html = $this->get_args_column( 999999, 'https://example.com/?secret_value=fixture-value' );

		$this->assertStringNotContainsString( 'fixture-value', $html );
	}

	/**
	 * @testdox Should hide arguments queued under an earlier action topic after the webhook moved to a resource topic.
	 */
	public function test_hides_non_id_args_of_resource_webhooks(): void {
		$webhook = $this->create_webhook( 'order.created' );

		$html = $this->get_args_column( $webhook->get_id(), array( 'account_password' => 'fixture-value' ) );

		$this->assertStringNotContainsString( 'fixture-value', $html );
	}

	/**
	 * @testdox Should leave resource webhook deliveries, which carry an ID, as they are.
	 */
	public function test_leaves_resource_delivery_args_unchanged(): void {
		$webhook = $this->create_webhook( 'order.created' );

		$html = $this->get_args_column( $webhook->get_id(), 42 );

		$this->assertSame( 'raw: 42', $html );
	}

	/**
	 * @testdox Should show extension resource deliveries that carry an ID as they are.
	 */
	public function test_leaves_extension_resource_ids_unchanged(): void {
		add_filter( 'woocommerce_valid_webhook_resources', fn( $resources ) => array_merge( $resources, array( 'booking' ) ) );
		$webhook = $this->create_webhook( 'booking.created' );

		$this->assertSame( 'raw: 42', $this->get_args_column( $webhook->get_id(), 42 ) );
	}

	/**
	 * Run the Scheduled Actions args column filter for a webhook delivery row.
	 *
	 * @param int   $webhook_id The webhook ID.
	 * @param mixed $arg        The queued hook argument.
	 * @return string
	 */
	private function get_args_column( int $webhook_id, $arg ): string {
		$row = array(
			'hook' => 'woocommerce_deliver_webhook_async',
			'args' => array(
				'webhook_id' => $webhook_id,
				'arg'        => $arg,
			),
		);

		// Action Scheduler's own column shows the raw stored argument.
		$raw_html = 'raw: ' . wp_json_encode( $arg );

		return (string) apply_filters( 'action_scheduler_list_table_column_args', $raw_html, $row );
	}

	/**
	 * Create an active webhook.
	 *
	 * @param string $topic The topic.
	 * @return WC_Webhook
	 */
	private function create_webhook( string $topic ): WC_Webhook {
		$webhook = new WC_Webhook();
		$webhook->set_status( 'active' );
		$webhook->set_topic( $topic );
		$webhook->set_delivery_url( 'https://example.com/webhook' );
		$webhook->set_user_id( 1 );
		$webhook->set_pending_delivery( false );
		$webhook->save();

		return $webhook;
	}
}
