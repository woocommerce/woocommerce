<?php
/**
 * WCTracksObservabilityHandler class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\MCP;

use WP\MCP\Infrastructure\Observability\Contracts\McpObservabilityHandlerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Tracks observability handler for MCP.
 *
 * Sends MCP tool usage events to WooCommerce Tracks when tracking is enabled.
 * Only tracks which tools are used and which parameter fields are passed,
 * not the actual parameter values.
 *
 * @since 10.4.0
 */
class WCTracksObservabilityHandler implements McpObservabilityHandlerInterface {

	/**
	 * Record an MCP event to WooCommerce Tracks.
	 *
	 * Only records tool/call events when tracking is enabled.
	 *
	 * @param string     $event       The event name (e.g., 'mcp.request').
	 * @param array      $tags        Event tags including method, params, status.
	 * @param float|null $duration_ms Duration in milliseconds.
	 */
	public function record_event( string $event, array $tags = array(), ?float $duration_ms = null ): void {
		// Check if tracking is enabled.
		if ( 'yes' !== get_option( 'woocommerce_allow_tracking', 'no' ) ) {
			return;
		}

		// Only track tool calls.
		if ( ! isset( $tags['method'] ) || 'tools/call' !== $tags['method'] ) {
			return;
		}

		// Extract data from tags.
		$tool_name    = $this->extract_tool_name( $tags );
		$param_fields = $this->extract_param_fields( $tags );
		$status       = $tags['status'] ?? 'unknown';

		// Build properties for Tracks.
		$properties = array(
			'tool_name'    => $this->sanitize_property_value( $tool_name ),
			'param_fields' => implode( ',', array_map( array( $this, 'sanitize_property_value' ), $param_fields ) ),
			'status'       => $status,
		);

		if ( null !== $duration_ms ) {
			$properties['duration_ms'] = round( $duration_ms, 2 );
		}

		// Send to WC Tracks.
		if ( class_exists( 'WC_Tracks' ) ) {
			\WC_Tracks::record_event( 'mcp_tool_call', $properties );
		}
	}

	/**
	 * Extract tool name from tags.
	 *
	 * @param array $tags Event tags.
	 * @return string Tool name or 'unknown'.
	 */
	private function extract_tool_name( array $tags ): string {
		if ( isset( $tags['params']['name'] ) && is_string( $tags['params']['name'] ) ) {
			return $tags['params']['name'];
		}
		return 'unknown';
	}

	/**
	 * Extract parameter field names from tags.
	 *
	 * @param array $tags Event tags.
	 * @return array Array of parameter field names.
	 */
	private function extract_param_fields( array $tags ): array {
		if ( isset( $tags['params']['arguments_keys'] ) && is_array( $tags['params']['arguments_keys'] ) ) {
			return $tags['params']['arguments_keys'];
		}
		return array();
	}

	/**
	 * Sanitize a value for Tracks property.
	 *
	 * Converts to lowercase and replaces non-alphanumeric characters with underscores.
	 * Tracks property names must match regex: ^[a-z_][a-z0-9_]*$
	 *
	 * @param string $value The value to sanitize.
	 * @return string Sanitized value.
	 */
	private function sanitize_property_value( string $value ): string {
		return preg_replace( '/[^a-z0-9_]/', '_', strtolower( $value ) );
	}
}
