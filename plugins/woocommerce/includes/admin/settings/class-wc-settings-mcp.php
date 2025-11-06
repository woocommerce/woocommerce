<?php
/**
 * WooCommerce MCP Settings
 *
 * @package WooCommerce\Admin\Settings
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Settings class for MCP (Model Context Protocol).
 */
class WC_Settings_MCP {

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
