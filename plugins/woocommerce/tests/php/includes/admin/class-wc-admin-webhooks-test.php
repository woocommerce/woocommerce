<?php
declare( strict_types = 1 );

/**
 * Tests for webhook bulk status changes.
 */
class WC_Admin_Webhooks_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Bulk actions save each supported webhook status.
	 * @testWith ["disabled", "active"]
	 *           ["active", "disabled"]
	 *           ["active", "paused"]
	 *
	 * @param string $initial_status Initial webhook status.
	 * @param string $new_status     Status selected in the bulk action.
	 */
	public function test_bulk_update_status( string $initial_status, string $new_status ): void {
		$webhook = $this->create_webhook( $initial_status );

		$updated = WC_Admin_Webhooks::bulk_update_status( array( $webhook->get_id() ), $new_status );

		$this->assertSame( 1, $updated, 'One webhook should be updated.' );
		$this->assertSame( $new_status, wc_get_webhook( $webhook->get_id() )->get_status(), 'The selected status should be saved.' );
	}

	/**
	 * @testdox Bulk status changes skip duplicate, missing, and already updated webhooks.
	 */
	public function test_bulk_update_status_only_counts_changed_webhooks(): void {
		$webhook   = $this->create_webhook( 'disabled' );
		$unchanged = $this->create_webhook( 'paused' );

		$updated = WC_Admin_Webhooks::bulk_update_status( array( $webhook->get_id(), $webhook->get_id(), 0, 99999999, $unchanged->get_id() ), 'paused' );

		$this->assertSame( 1, $updated, 'Only the webhook with a changed status should be counted.' );
		$this->assertSame( 'paused', wc_get_webhook( $webhook->get_id() )->get_status(), 'The changed webhook should be paused.' );
	}

	/**
	 * @testdox Bulk status changes reject unsupported statuses without changing webhooks.
	 */
	public function test_bulk_update_status_rejects_unknown_status(): void {
		$webhook = $this->create_webhook( 'active' );

		$updated = WC_Admin_Webhooks::bulk_update_status( array( $webhook->get_id() ), 'unknown' );

		$this->assertSame( 0, $updated, 'No webhook should be updated for an unsupported status.' );
		$this->assertSame( 'active', wc_get_webhook( $webhook->get_id() )->get_status(), 'The webhook should retain its status.' );
	}

	/**
	 * Create a webhook without a pending ping.
	 *
	 * @param string $status Initial status.
	 * @return WC_Webhook
	 */
	private function create_webhook( string $status ): WC_Webhook {
		$webhook = new WC_Webhook();
		$webhook->set_name( 'Bulk status test' );
		$webhook->set_topic( 'order.created' );
		$webhook->set_delivery_url( 'https://example.com/webhook' );
		$webhook->set_status( $status );
		$webhook->set_pending_delivery( false );
		$webhook->save();

		return $webhook;
	}
}
