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
	 * @testdox Should link to activate the MCP Adapter plugin, without the modal scripts, when it's installed but inactive.
	 */
	public function test_removed_notice_links_to_activate_installed_adapter(): void {
		$this->set_up_removed_notice_conditions( false, true );
		$this->register_plugin_install_script();

		$output = $this->get_admin_notices_output();
		$this->sut->handle_admin_enqueue_scripts();

		$this->assertStringContainsString( 'Activate MCP Adapter', $output );
		$this->assertStringContainsString( 'action=activate&amp;plugin=mcp-adapter%2Fmcp-adapter.php', $output );
		$this->assertStringContainsString( '_wpnonce=', $output, 'The activation link should carry a nonce' );
		$this->assertStringNotContainsString( 'Install MCP Adapter', $output );
		$this->assertFalse( wp_script_is( 'plugin-install', 'enqueued' ), 'The modal scripts are only needed for the install link' );
	}

	/**
	 * @testdox Should delete the old feature option when the MCP Adapter plugin is activated from the notice's link.
	 */
	public function test_activating_adapter_from_notice_deletes_feature_option(): void {
		$this->set_up_removed_notice_conditions( false, true );
		$this->assertStringContainsString( 'wc-mcp-removal-notice=1', $this->get_admin_notices_output(), 'The activation link should identify the notice as its source' );

		$this->simulate_adapter_activation( true );

		$this->assertFalse( get_option( 'woocommerce_feature_mcp_integration_enabled' ), 'The old feature option should be deleted' );
		$this->assertSame( '', $this->get_admin_notices_output() );
	}

	/**
	 * @testdox Should keep the old feature option when the MCP Adapter plugin is activated outside the notice.
	 */
	public function test_activating_adapter_outside_notice_keeps_feature_option(): void {
		$this->set_up_removed_notice_conditions( false, true );

		$this->simulate_adapter_activation( false );

		$this->assertSame( 'yes', get_option( 'woocommerce_feature_mcp_integration_enabled' ) );
	}

	/**
	 * @testdox Should keep the old feature option when another plugin is activated from a link with the notice argument.
	 */
	public function test_activating_other_plugin_keeps_feature_option(): void {
		$this->set_up_removed_notice_conditions( false, true );
		$_GET['wc-mcp-removal-notice'] = '1';

		try {
			$this->sut->handle_activated_plugin( 'hello.php' );
		} finally {
			unset( $_GET['wc-mcp-removal-notice'] );
		}

		$this->assertSame( 'yes', get_option( 'woocommerce_feature_mcp_integration_enabled' ) );
	}

	/**
	 * @testdox Should delete the old feature option for the whole store when an admin dismisses the notice.
	 */
	public function test_dismissing_notice_deletes_feature_option(): void {
		$this->set_up_removed_notice_conditions( false );
		$_GET['wc-hide-notice']   = McpEndpointRemoval::REMOVED_NOTICE;
		$_GET['_wc_notice_nonce'] = wp_create_nonce( 'woocommerce_hide_notices_nonce' );

		try {
			\WC_Admin_Notices::hide_notices();
		} finally {
			unset( $_GET['wc-hide-notice'], $_GET['_wc_notice_nonce'] );
		}

		$this->assertFalse( get_option( 'woocommerce_feature_mcp_integration_enabled' ), 'The old feature option should be deleted' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertSame( '', $this->get_admin_notices_output(), 'Other admins should no longer see the notice' );
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
	 * @param bool $adapter_loaded   Whether an MCP adapter should appear to be loaded.
	 * @param bool $plugin_installed Whether the MCP Adapter plugin should appear to be installed.
	 */
	private function set_up_removed_notice_conditions( bool $adapter_loaded, bool $plugin_installed = false ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		update_option( 'woocommerce_feature_mcp_integration_enabled', 'yes' );
		$this->register_legacy_proxy_function_mocks(
			array(
				'class_exists' => static function ( $class_name ) use ( $adapter_loaded ) {
					return 'WP\\MCP\\Core\\McpAdapter' === $class_name ? $adapter_loaded : class_exists( $class_name );
				},
				'file_exists'  => static function ( $path ) use ( $plugin_installed ) {
					return WP_PLUGIN_DIR . '/mcp-adapter/mcp-adapter.php' === $path ? $plugin_installed : file_exists( $path );
				},
			)
		);
	}

	/**
	 * Fire activated_plugin for the MCP Adapter plugin, as core does during activation.
	 *
	 * @param bool $from_notice Whether the activation request came from the notice's link.
	 */
	private function simulate_adapter_activation( bool $from_notice ): void {
		if ( $from_notice ) {
			$_GET['wc-mcp-removal-notice'] = '1';
		}

		try {
			$this->sut->handle_activated_plugin( 'mcp-adapter/mcp-adapter.php' );
		} finally {
			unset( $_GET['wc-mcp-removal-notice'] );
		}
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
