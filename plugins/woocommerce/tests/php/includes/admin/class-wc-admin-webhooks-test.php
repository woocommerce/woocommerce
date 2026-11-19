<?php
/**
 * Tests for the webhook settings form.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

/**
 * Tests for WC_Admin_Webhooks.
 */
class WC_Admin_Webhooks_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Admin_Webhooks
	 */
	private $sut;

	/**
	 * Set up the webhook editor and sign in as a store administrator.
	 */
	public function setUp(): void {
		parent::setUp();
		require_once WC_ABSPATH . 'includes/admin/class-wc-admin-webhooks.php';
		$this->sut = new WC_Admin_Webhooks();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * @testdox An invalid topic prevents creation, and an active webhook requires a topic.
	 * @testWith ["active", "action", "invalid_action"]
	 *           ["active", "", ""]
	 *           ["disabled", "order", ""]
	 * @param string $status Status selected in the form.
	 * @param string $topic Topic selected in the form.
	 * @param string $action_event Custom action entered in the form.
	 */
	public function test_webhook_creation_rejects_invalid_topic( string $status, string $topic, string $action_event ): void {
		$data_store = WC_Data_Store::load( 'webhook' );
		$before     = $data_store->get_webhooks_ids();

		$query = $this->submit_form(
			array(
				'webhook_status'       => $status,
				'webhook_topic'        => $topic,
				'webhook_action_event' => $action_event,
			)
		);

		$this->assertArrayHasKey( 'error', $query, 'The editor should show a validation error.' );
		$this->assertSame( 'Webhook topic unknown. Please select a valid topic.', $query['error'] );
		$this->assertSame( '0', $query['edit-webhook'], 'The editor should return to the new webhook form.' );
		$this->assertSame( $before, $data_store->get_webhooks_ids(), 'The invalid submission should create no webhook.' );
	}

	/**
	 * @testdox A rejected topic leaves the saved webhook settings intact.
	 */
	public function test_invalid_topic_does_not_update_webhook(): void {
		$webhook = new WC_Webhook();
		$webhook->set_name( 'Original webhook' );
		$webhook->set_status( 'disabled' );
		$webhook->set_topic( 'order.created' );
		$webhook->set_delivery_url( 'https://example.com/original' );
		$webhook->save();
		$save_actions = did_action( 'woocommerce_webhook_options_save' );

		$query = $this->submit_form(
			array(
				'webhook_id'           => $webhook->get_id(),
				'webhook_name'         => 'Changed webhook',
				'webhook_status'       => 'active',
				'webhook_topic'        => 'order.unknown',
				'webhook_delivery_url' => 'https://example.com/changed',
			)
		);

		$saved = new WC_Webhook( $webhook->get_id() );
		$this->assertArrayHasKey( 'error', $query, 'The editor should show a validation error.' );
		$this->assertSame( (string) $webhook->get_id(), $query['edit-webhook'] );
		$this->assertSame( $webhook->get_name(), $saved->get_name() );
		$this->assertSame( $webhook->get_status(), $saved->get_status() );
		$this->assertSame( $webhook->get_topic(), $saved->get_topic() );
		$this->assertSame( $webhook->get_delivery_url(), $saved->get_delivery_url() );
		$this->assertSame( $save_actions, did_action( 'woocommerce_webhook_options_save' ) );
	}

	/**
	 * @testdox An inactive webhook can still be saved with a valid topic or no topic.
	 * @testWith ["", "", ""]
	 *           ["order.created", "", "order.created"]
	 *           ["action", "woocommerce_test_event", "action.woocommerce_test_event"]
	 * @param string $topic Topic selected in the form.
	 * @param string $action_event Custom action entered in the form.
	 * @param string $saved_topic Expected stored topic.
	 */
	public function test_valid_disabled_webhook_can_be_saved( string $topic, string $action_event, string $saved_topic ): void {
		$query = $this->submit_form(
			array(
				'webhook_status'       => 'disabled',
				'webhook_topic'        => $topic,
				'webhook_action_event' => $action_event,
			)
		);

		$this->assertSame( '1', $query['updated'] );
		$this->assertGreaterThan( 0, (int) $query['edit-webhook'] );
		$saved = new WC_Webhook( (int) $query['edit-webhook'] );
		$this->assertSame( 'disabled', $saved->get_status() );
		$this->assertSame( $saved_topic, $saved->get_topic() );
	}

	/**
	 * @testdox Valid extension topics remain selected in the editor and can be saved.
	 * @testWith ["order.status_changed"]
	 *           ["subscription.created"]
	 * @param string $topic Extension topic.
	 */
	public function test_extension_topic_can_be_edited( string $topic ): void {
		add_filter(
			'woocommerce_valid_webhook_events',
			static function ( $events ) {
				$events[] = 'status_changed';
				return $events;
			}
		);
		add_filter(
			'woocommerce_valid_webhook_resources',
			static function ( $resources ) {
				$resources[] = 'subscription';
				return $resources;
			}
		);
		$webhook = new WC_Webhook();
		$webhook->set_topic( $topic );
		$webhook->set_status( 'paused' );
		$webhook->save();

		ob_start();
		include WC_ABSPATH . 'includes/admin/settings/views/html-webhooks-edit.php';
		$html = ob_get_clean();
		$this->assertStringContainsString( '<option value="' . $topic . '"  selected=\'selected\'>' . $topic . '</option>', $html, 'The saved extension topic should remain selected and use its ID as its label.' );

		$query = $this->submit_form(
			array(
				'webhook_id'     => $webhook->get_id(),
				'webhook_topic'  => $topic,
				'webhook_status' => 'paused',
				'webhook_name'   => 'Updated extension webhook',
			)
		);
		$this->assertSame( '1', $query['updated'] );
		$saved = new WC_Webhook( $webhook->get_id() );
		$this->assertSame( $topic, $saved->get_topic() );
		$this->assertSame( 'Updated extension webhook', $saved->get_name() );
	}

	/**
	 * @testdox The editor preserves supplied labels and excludes invalid or unsaved topics.
	 * @testWith ["order.created", true, "order.created", "Custom label"]
	 *           ["order.unknown", true, "order.unknown", null]
	 *           ["order.created", false, "order.created", null]
	 *           ["action.woocommerce_test_event", true, "action", "Action"]
	 * @param string      $topic Stored topic.
	 * @param bool        $saved Whether the webhook has been saved.
	 * @param string      $option Option to inspect.
	 * @param string|null $label Expected label, or null when the option should be absent.
	 */
	public function test_topic_dropdown_preserves_existing_options( string $topic, bool $saved, string $option, ?string $label ): void {
		add_filter(
			'woocommerce_webhook_topics',
			static function ( $topics ) {
				unset( $topics['order.created'] );
				return $topics;
			}
		);
		if ( null !== $label && 'order.created' === $topic ) {
			add_filter(
				'woocommerce_webhook_topics',
				static function ( $topics ) {
					$topics['order.created'] = 'Custom label';
					return $topics;
				}
			);
		}
		$webhook = new WC_Webhook();
		$webhook->set_topic( $topic );
		if ( $saved ) {
			$webhook->save();
		}
		ob_start();
		include WC_ABSPATH . 'includes/admin/settings/views/html-webhooks-edit.php';
		$html = ob_get_clean();
		if ( null === $label ) {
			$this->assertStringNotContainsString( '<option value="' . $option . '"', $html );
		} else {
			$this->assertStringContainsString( '<option value="' . $option . '"  selected=\'selected\'>' . $label . '</option>', $html );
		}
	}

	/**
	 * Submit the settings form and read the query parameters from its redirect.
	 *
	 * @param array $fields Submitted form fields.
	 * @return array Query parameters from the redirect.
	 */
	private function submit_form( array $fields ): array {
		// phpcs:disable WordPress.Security.NonceVerification -- Set up and restore a simulated form request.
		$original_get     = $_GET;
		$original_post    = $_POST;
		$original_request = $_REQUEST;
		$original_screen  = get_current_screen();
		$redirect_url     = null;
		$capture_redirect = static function ( $url ) use ( &$redirect_url ) {
			$redirect_url = $url;
			throw new RuntimeException( 'Intercepted webhook editor redirect.' );
		};

		set_current_screen( 'woocommerce_page_wc-settings' );
		$_GET     = array(
			'page'    => 'wc-settings',
			'tab'     => 'advanced',
			'section' => 'webhooks',
		);
		$_POST    = $fields + array(
			'save'       => '',
			'webhook_id' => 0,
			'_wpnonce'   => wp_create_nonce( 'woocommerce-settings' ),
		);
		$_REQUEST = $_POST + $_GET;
		add_filter( 'wp_redirect', $capture_redirect );

		try {
			$this->sut->actions();
		} catch ( RuntimeException $e ) {
			if ( null === $redirect_url ) {
				throw $e;
			}
		} finally {
			remove_filter( 'wp_redirect', $capture_redirect );
			$_GET     = $original_get;
			$_POST    = $original_post;
			$_REQUEST = $original_request;
			set_current_screen( $original_screen ?? 'front' );
		}
		// phpcs:enable WordPress.Security.NonceVerification

		$this->assertNotNull( $redirect_url, 'The editor should redirect after submission.' );
		parse_str( wp_parse_url( $redirect_url, PHP_URL_QUERY ), $query );
		return $query;
	}
}
