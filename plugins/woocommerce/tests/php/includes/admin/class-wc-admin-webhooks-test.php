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
	 * @testdox Bulk activation preserves the normal ping verification lifecycle.
	 * @testWith [200, false]
	 *           [500, true]
	 *           [0, true]
	 *
	 * @param int  $response_code    Ping response code, or zero for a connection error.
	 * @param bool $pending_delivery Expected pending delivery state.
	 */
	public function test_bulk_activate_preserves_ping_verification( int $response_code, bool $pending_delivery ): void {
		$pending       = $this->create_webhook( 'disabled', true );
		$verified      = $this->create_webhook( 'paused' );
		$captured_args = array();

		add_filter(
			'pre_http_request',
			static function ( $response, $parsed_args ) use ( &$captured_args, $response_code ) {
				$captured_args[] = $parsed_args;

				return $response_code ? array(
					'response' => array( 'code' => $response_code ),
					'body'     => '',
				) : new WP_Error( 'http_request_failed', 'Connection refused.' );
			},
			10,
			2
		);

		$updated = WC_Admin_Webhooks::bulk_update_status( array( $pending->get_id(), $verified->get_id() ), 'active' );

		$this->assertSame( 2, $updated, 'Both webhook statuses should be updated.' );
		$this->assertSame( 'active', wc_get_webhook( $pending->get_id() )->get_status(), 'The pending webhook should be active.' );
		$this->assertSame( 'active', wc_get_webhook( $verified->get_id() )->get_status(), 'The verified webhook should be active.' );
		$this->assertSame(
			$pending_delivery,
			wc_get_webhook( $pending->get_id() )->get_pending_delivery(),
			'Only a successful ping should clear pending delivery.'
		);
		$this->assertFalse( wc_get_webhook( $verified->get_id() )->get_pending_delivery(), 'The verified webhook should remain verified.' );
		$this->assertCount( 1, $captured_args, 'Only the pending webhook should be pinged.' );
		$this->assertTrue( $captured_args[0]['blocking'], 'Bulk activation should preserve the default HTTP arguments.' );
	}

	/**
	 * @testdox Bulk update notices only report changed webhooks.
	 * @testWith [0, []]
	 *           [1, ["1 webhook updated."]]
	 *           [2, ["2 webhooks updated."]]
	 *
	 * @param int   $updated  Number of changed webhooks.
	 * @param array $expected Expected notices.
	 */
	public function test_bulk_update_notices( int $updated, array $expected ): void {
		$messages = new ReflectionProperty( WC_Admin_Settings::class, 'messages' );
		$messages->setAccessible( true );
		$original_messages = $messages->getValue();
		$messages->setValue( null, array() );
		$_GET = array( 'bulk_updated' => $updated );

		try {
			WC_Admin_Webhooks::notices();

			$this->assertSame( $expected, $messages->getValue(), 'Only positive update counts should produce a success notice.' );
		} finally {
			$messages->setValue( null, $original_messages );
		}
	}

	/**
	 * @testdox Bulk actions preserve the status filter when redirecting.
	 * @testWith ["activate", "disabled", "active", "bulk_updated"]
	 *           ["disable", "active", "disabled", "bulk_updated"]
	 *           ["pause", "active", "paused", "bulk_updated"]
	 *           ["delete", "paused", "", "deleted"]
	 *
	 * @param string $action         Selected action.
	 * @param string $initial_status Initial webhook status.
	 * @param string $new_status     Expected status, or empty for deletion.
	 * @param string $count_key      Redirect count parameter.
	 */
	public function test_process_bulk_action( string $action, string $initial_status, string $new_status, string $count_key ): void {
		$webhook = $this->create_webhook( $initial_status );
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$_GET     = array( 'status' => $initial_status );
		$_REQUEST = array(
			'action'   => $action,
			'webhook'  => array( $webhook->get_id() ),
			'_wpnonce' => wp_create_nonce( 'woocommerce-settings' ),
		);
		$sut      = new WC_Admin_Webhooks_Table_List();
		$redirect = $this->capture_redirect(
			static function () use ( $sut ) {
				$sut->process_bulk_action();
			}
		);

		$this->assertSame( wp_parse_url( admin_url( 'admin.php' ), PHP_URL_PATH ), wp_parse_url( $redirect, PHP_URL_PATH ), 'The redirect should use the admin path.' );
		parse_str( wp_parse_url( $redirect, PHP_URL_QUERY ), $query );
		$this->assertSame(
			array(
				'page'     => 'wc-settings',
				'tab'      => 'advanced',
				'section'  => 'webhooks',
				'status'   => $initial_status,
				$count_key => '1',
			),
			$query,
			'The redirect should preserve the status filter and report the changed count.'
		);

		if ( $new_status ) {
			$this->assertSame( $new_status, wc_get_webhook( $webhook->get_id() )->get_status(), 'The requested status should be saved.' );
		} else {
			$this->assertNull( wc_get_webhook( $webhook->get_id() ), 'The selected webhook should be deleted.' );
		}
	}

	/**
	 * @testdox Bulk status changes drop the status filter and the count when neither applies.
	 */
	public function test_process_bulk_action_without_filter_or_changes(): void {
		$webhook = $this->create_webhook( 'paused' );
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$_GET     = array();
		$_REQUEST = array(
			'action'   => 'pause',
			'webhook'  => array( $webhook->get_id() ),
			'_wpnonce' => wp_create_nonce( 'woocommerce-settings' ),
		);

		$sut      = new WC_Admin_Webhooks_Table_List();
		$redirect = $this->capture_redirect(
			static function () use ( $sut ) {
				$sut->process_bulk_action();
			}
		);

		parse_str( wp_parse_url( $redirect, PHP_URL_QUERY ), $query );
		$this->assertSame(
			array(
				'page'    => 'wc-settings',
				'tab'     => 'advanced',
				'section' => 'webhooks',
			),
			$query,
			'A bulk action that changes nothing should add neither a status filter nor a count.'
		);
	}

	/**
	 * @testdox Deleting webhooks without a status filter omits the status parameter.
	 */
	public function test_bulk_delete_without_status_filter(): void {
		$webhook = $this->create_webhook( 'paused' );
		$_GET    = array();

		$redirect = $this->capture_redirect(
			static function () use ( $webhook ) {
				WC_Admin_Webhooks::bulk_delete( array( $webhook->get_id() ) );
			}
		);

		parse_str( wp_parse_url( $redirect, PHP_URL_QUERY ), $query );
		$this->assertSame(
			array(
				'page'    => 'wc-settings',
				'tab'     => 'advanced',
				'section' => 'webhooks',
				'deleted' => '1',
			),
			$query,
			'An unfiltered delete should not add an empty status filter.'
		);
	}

	/**
	 * Run a callback that redirects and return the location it redirected to.
	 *
	 * @param callable $callback Callback expected to redirect.
	 * @return string
	 */
	private function capture_redirect( callable $callback ): string {
		$redirect = '';

		add_filter(
			'wp_redirect',
			static function ( $location ) use ( &$redirect ) {
				$redirect = $location;
				throw new RuntimeException( 'Redirect intercepted.' );
			}
		);

		try {
			$callback();
		} catch ( RuntimeException $exception ) {
			$this->assertSame( 'Redirect intercepted.', $exception->getMessage(), 'The action should reach its redirect.' );
		}

		$this->assertNotEmpty( $redirect, 'The action should redirect to the webhook list.' );

		return $redirect;
	}

	/**
	 * Create a webhook, optionally one that still needs a delivery ping.
	 *
	 * @param string $status           Initial status.
	 * @param bool   $pending_delivery Whether the webhook still needs its first ping.
	 * @return WC_Webhook
	 */
	private function create_webhook( string $status, bool $pending_delivery = false ): WC_Webhook {
		$webhook = new WC_Webhook();
		$webhook->set_name( 'Bulk status test' );
		$webhook->set_topic( 'order.created' );
		$webhook->set_delivery_url( 'https://example.com/webhook' );
		$webhook->set_status( $status );
		$webhook->save();

		if ( ! $pending_delivery ) {
			$webhook->set_pending_delivery( false );
			$webhook->save();
		}

		return $webhook;
	}
}
