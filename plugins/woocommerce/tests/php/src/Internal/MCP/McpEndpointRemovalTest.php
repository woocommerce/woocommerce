<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MCP;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\MCP\McpEndpointRemoval;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the McpEndpointRemoval class.
 */
class McpEndpointRemovalTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var McpEndpointRemoval
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new McpEndpointRemoval();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			// LegacyProxy mocks live on the container instance, which no base class resets.
			$this->reset_legacy_proxy_mocks();
			// Script queues are globals that no base class resets.
			wp_dequeue_script( 'plugin-install' );
			wp_dequeue_script( 'thickbox' );
			wp_dequeue_style( 'thickbox' );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should answer MCP requests to the removed endpoint with a JSON-RPC error and HTTP 410.
	 */
	public function test_removed_endpoint_returns_json_rpc_error(): void {
		$request = new WP_REST_Request( 'POST', '/woocommerce/mcp' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'jsonrpc' => '2.0',
					'id'      => 7,
					'method'  => 'initialize',
				)
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 410, $response->get_status(), 'The removed endpoint should respond with 410 Gone' );
		$this->assertSame( '2.0', $data['jsonrpc'] );
		$this->assertSame( 7, $data['id'], 'The JSON-RPC request id should be echoed' );
		$this->assertSame( -32000, $data['error']['code'] );
		$this->assertStringContainsString( 'MCP Adapter plugin', $data['error']['message'] );
		$this->assertStringContainsString( 'mcp/mcp-adapter-default-server', $data['error']['data']['replacement_endpoint'] );
		$this->assertSame( McpEndpointRemoval::DOCS_URL, $data['error']['data']['documentation_url'] );
	}

	/**
	 * @testdox Should answer non-JSON-RPC requests to the removed endpoint with a null JSON-RPC id.
	 */
	public function test_removed_endpoint_handles_requests_without_json_rpc_id(): void {
		$response = rest_do_request( new WP_REST_Request( 'GET', '/woocommerce/mcp' ) );

		$this->assertSame( 410, $response->get_status() );
		$this->assertNull( $response->get_data()['id'], 'Requests without a JSON-RPC id should get a null id' );
	}

	/**
	 * @testdox Should report the WooCommerce MCP feature as disabled even when its old option is enabled.
	 */
	public function test_mcp_feature_is_always_disabled(): void {
		update_option( 'woocommerce_feature_mcp_integration_enabled', 'yes' );

		$this->assertFalse( wc_get_container()->get( FeaturesController::class )->feature_is_enabled( 'mcp_integration' ) );
	}

	/**
	 * @testdox Should tell stores that had WooCommerce MCP enabled what was removed, with docs and install links.
	 */
	public function test_removed_notice_shown_when_feature_was_enabled(): void {
		$this->set_up_removed_notice_conditions( false );

		$output = $this->get_admin_notices_output();

		$this->assertStringContainsString( 'WooCommerce MCP has been removed.', $output );
		$this->assertStringContainsString( McpEndpointRemoval::DOCS_URL, $output );
		$this->assertStringContainsString( 'Install MCP Adapter', $output, 'Should link to the installer when no MCP adapter is loaded' );
		$this->assertStringContainsString( 'tab=plugin-information&amp;plugin=mcp-adapter', $output, 'Should link to the MCP Adapter plugin details, not a search' );
		$this->assertStringContainsString( 'class="thickbox open-plugin-details-modal"', $output, 'Should open the plugin details in a modal' );
		$this->assertStringContainsString( 'wc-hide-notice=' . McpEndpointRemoval::REMOVED_NOTICE, $output );
	}

	/**
	 * @testdox Should leave out the install link when an MCP adapter is already loaded.
	 */
	public function test_removed_notice_omits_install_link_when_adapter_loaded(): void {
		$this->set_up_removed_notice_conditions( true );

		$output = $this->get_admin_notices_output();

		$this->assertStringContainsString( 'WooCommerce MCP has been removed.', $output );
		$this->assertStringNotContainsString( 'Install MCP Adapter', $output );
	}

	/**
	 * @testdox Should load the plugin details modal scripts only when the notice shows the install link.
	 *
	 * @testWith [false, true]
	 *           [true, false]
	 *
	 * @param bool $adapter_loaded  Whether an MCP adapter appears to be loaded.
	 * @param bool $expect_enqueued Whether the modal scripts should be enqueued.
	 */
	public function test_modal_scripts_enqueued_only_with_install_link( bool $adapter_loaded, bool $expect_enqueued ): void {
		$this->set_up_removed_notice_conditions( $adapter_loaded );
		$this->register_plugin_install_script();

		$this->sut->handle_admin_enqueue_scripts();

		$this->assertSame( $expect_enqueued, wp_script_is( 'plugin-install', 'enqueued' ) );
		$this->assertSame( $expect_enqueued, wp_script_is( 'thickbox', 'enqueued' ) );
	}

	/**
	 * @testdox Should not load the plugin details modal scripts when the notice doesn't show.
	 */
	public function test_modal_scripts_not_enqueued_when_notice_hidden(): void {
		$this->set_up_removed_notice_conditions( false );
		update_option( 'woocommerce_feature_mcp_integration_enabled', 'no' );
		$this->register_plugin_install_script();

		$this->sut->handle_admin_enqueue_scripts();

		$this->assertFalse( wp_script_is( 'plugin-install', 'enqueued' ) );
	}

	/**
	 * @testdox Should not show the removal notice when WooCommerce MCP wasn't enabled.
	 */
	public function test_removed_notice_hidden_when_feature_was_not_enabled(): void {
		$this->set_up_removed_notice_conditions( false );
		update_option( 'woocommerce_feature_mcp_integration_enabled', 'no' );

		$this->assertSame( '', $this->get_admin_notices_output() );
	}

	/**
	 * @testdox Should not show the removal notice after the user dismisses it.
	 */
	public function test_removed_notice_hidden_when_dismissed(): void {
		$this->set_up_removed_notice_conditions( false );
		update_user_meta( get_current_user_id(), 'dismissed_' . McpEndpointRemoval::REMOVED_NOTICE . '_notice', true );

		$this->assertSame( '', $this->get_admin_notices_output() );
	}

	/**
	 * @testdox Should not show the removal notice to users who can't manage WooCommerce.
	 */
	public function test_removed_notice_hidden_for_users_without_capability(): void {
		$this->set_up_removed_notice_conditions( false );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$this->assertSame( '', $this->get_admin_notices_output() );
	}

	/**
	 * Log in as an administrator, mark WooCommerce MCP as previously enabled, and control MCP adapter detection.
	 *
	 * @param bool $adapter_loaded Whether an MCP adapter should appear to be loaded.
	 */
	private function set_up_removed_notice_conditions( bool $adapter_loaded ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		update_option( 'woocommerce_feature_mcp_integration_enabled', 'yes' );
		$this->register_legacy_proxy_function_mocks(
			array(
				'class_exists' => static function ( $class_name ) use ( $adapter_loaded ) {
					return 'WP\\MCP\\Core\\McpAdapter' === $class_name ? $adapter_loaded : class_exists( $class_name );
				},
			)
		);
	}

	/**
	 * Register the plugin-install script, which core only registers in wp-admin.
	 */
	private function register_plugin_install_script(): void {
		if ( ! wp_script_is( 'plugin-install', 'registered' ) ) {
			wp_register_script( 'plugin-install', '/wp-admin/js/plugin-install.js', array( 'jquery', 'jquery-ui-core', 'thickbox' ), '1.0.0', true );
		}
	}

	/**
	 * Get the output of the admin notices callback.
	 *
	 * @return string
	 */
	private function get_admin_notices_output(): string {
		ob_start();
		$this->sut->handle_admin_notices();
		return (string) ob_get_clean();
	}
}
