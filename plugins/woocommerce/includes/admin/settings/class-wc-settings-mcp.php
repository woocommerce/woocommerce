<?php
/**
 * WooCommerce MCP Settings
 *
 * @package WooCommerce\Admin\Settings
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesRestBridge;
use Automattic\WooCommerce\Internal\MCP\MCPAdapterProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Settings class for MCP (Model Context Protocol).
 */
class WC_Settings_MCP {

	/**
	 * Get available MCP tools from the abilities registry.
	 *
	 * @return array Array of MCP tools with their metadata.
	 */
	private static function get_available_mcp_tools(): array {
		// Check if abilities API is available.
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return array();
		}

		// Bypass MCP request check for settings display.
		add_filter( 'woocommerce_mcp_bypass_request_check', '__return_true' );

		// Get available MCP ability IDs (single source of truth).
		// This triggers wp_get_abilities() which fires the hook that calls register_abilities().
		$ability_ids = MCPAdapterProvider::get_woocommerce_mcp_abilities();

		// Get full ability data for each ID.
		$all_abilities = wp_get_abilities();
		$mcp_tools     = array();

		foreach ( $ability_ids as $ability_id ) {
			if ( isset( $all_abilities[ $ability_id ] ) ) {
				$mcp_tools[ $ability_id ] = $all_abilities[ $ability_id ];
			}
		}

		return $mcp_tools;
	}

	/**
	 * Get MCP settings array.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$description = __( 'The Model Context Protocol (MCP) enables AI assistants like Claude Code to manage your WooCommerce store through a standardized interface.', 'woocommerce' );

		// Check if permalinks are set correctly.
		$permalink_structure = get_option( 'permalink_structure' );
		if ( empty( $permalink_structure ) ) {
			$description .= ' <strong>' . __( 'WordPress permalinks must be set to anything other than "Plain" for MCP to work.', 'woocommerce' ) . '</strong>';
		}

		// Add documentation link.
		$description .= ' ' . sprintf(
			/* translators: %s: Documentation URL */
			__( '<a href="%s" target="_blank">Learn more about WooCommerce MCP</a>.', 'woocommerce' ),
			'https://github.com/woocommerce/woocommerce/blob/trunk/docs/features/mcp/README.md'
		);

		$settings = array(
			array(
				'title' => __( 'Model Context Protocol (MCP)', 'woocommerce' ),
				'type'  => 'title',
				'desc'  => $description,
				'id'    => 'mcp_options',
			),

			array(
				'title'    => __( 'Enable MCP', 'woocommerce' ),
				'desc'     => __( 'Enable WooCommerce MCP for AI-powered store operations', 'woocommerce' ),
				'id'       => 'woocommerce_feature_mcp_integration_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc_tip' => __( 'AI-generated results and actions can be unpredictable - please review before executing in your store.', 'woocommerce' ),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'mcp_options',
			),

			array(
				'title' => __( 'MCP tools', 'woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Select which MCP tools are available to AI assistants. All tools are enabled by default.', 'woocommerce' ),
				'id'    => 'mcp_tools_options',
			),
		);

		// Add dynamic tool checkboxes.
		$mcp_tools = self::get_available_mcp_tools();
		foreach ( $mcp_tools as $tool_id => $tool_data ) {
			$tool_name = str_replace( 'woocommerce/', '', $tool_id );
			$label     = method_exists( $tool_data, 'get_label' ) ? $tool_data->get_label() : ucfirst( $tool_name );
			$desc      = method_exists( $tool_data, 'get_description' ) ? $tool_data->get_description() : '';

			$settings[] = array(
				'title'    => $label,
				'desc'     => sprintf(
					/* translators: %s: Tool description */
					__( 'Enable %s', 'woocommerce' ),
					strtolower( $label )
				),
				'id'       => 'woocommerce_mcp_tool_' . $tool_name . '_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
				'desc_tip' => $desc,
			);
		}

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'mcp_tools_options',
		);

		/**
		 * Filters the MCP settings array.
		 *
		 * @since 10.4.0
		 *
		 * @param array $settings Array of MCP settings.
		 */
		return apply_filters( 'woocommerce_settings_mcp', $settings );
	}
}
