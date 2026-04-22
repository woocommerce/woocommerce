<?php
/**
 * Nested-discovery abilities bridge (experimental).
 *
 * Pattern D for the abilities-rollout verification loop: exposes only three
 * meta-abilities (tools-list, tools-describe, tools-invoke) via MCP. Real
 * commerce abilities live in an internal catalog and are reached progressively
 * by MCP clients. Baseline context cost is constant regardless of how many
 * real abilities exist behind the catalog.
 *
 * This is the original wpcom-mcp STRAP shape: progressive disclosure, not
 * a single-tool facade with discriminators.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use Automattic\WooCommerce\Internal\MCP\MCPAdapterProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Nested-discovery abilities bridge.
 */
class NestedDiscoveryAbilitiesBridge {

	/**
	 * Initialize.
	 */
	final public static function init(): void {
		add_action( 'abilities_api_init', array( __CLASS__, 'register_abilities' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
	}

	/**
	 * Register meta-abilities, gated on MCP request context.
	 */
	public static function register_abilities(): void {
		if ( ! MCPAdapterProvider::is_mcp_request() ) {
			return;
		}
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability( 'woocommerce/tools-list', self::tools_list() );
		wp_register_ability( 'woocommerce/tools-describe', self::tools_describe() );
		wp_register_ability( 'woocommerce/tools-invoke', self::tools_invoke() );
	}

	/**
	 * Internal catalog of commerce abilities. In a production implementation,
	 * this would be populated from other registered abilities via the Abilities
	 * API registry. Kept minimal here for measurement.
	 *
	 * @return array<string, array<string, string>>
	 */
	private static function catalog(): array {
		return array(
			'woocommerce/products-query'  => array(
				'category'    => 'products',
				'description' => 'List or fetch products. Provide id for a single product, or filter fields for a list.',
			),
			'woocommerce/products-manage' => array(
				'category'    => 'products',
				'description' => 'Create, update, or delete a product.',
			),
			'woocommerce/orders-query'    => array(
				'category'    => 'orders',
				'description' => 'List or fetch orders. Provide id for a single order, or filter fields for a list.',
			),
			'woocommerce/orders-manage'   => array(
				'category'    => 'orders',
				'description' => 'Create or update an order.',
			),
		);
	}

	private static function tools_list(): array {
		return array(
			'label'               => __( 'List available commerce tools', 'woocommerce' ),
			'description'         => __( 'Return available WooCommerce abilities, optionally filtered by category (products, orders). Call this before tools-describe or tools-invoke to discover what is available.', 'woocommerce' ),
			'category'            => 'woocommerce-rest',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'category' => array(
						'type'        => 'string',
						'description' => 'Optional category filter. Known: products, orders.',
					),
				),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'tools' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'name'        => array( 'type' => 'string' ),
								'category'    => array( 'type' => 'string' ),
								'description' => array( 'type' => 'string' ),
							),
							'required'   => array( 'name', 'description' ),
						),
					),
				),
				'required'   => array( 'tools' ),
			),
			'execute_callback'    => static function ( $input ) {
				$catalog = self::catalog();
				$filter  = isset( $input['category'] ) ? (string) $input['category'] : '';
				$tools   = array();
				foreach ( $catalog as $name => $meta ) {
					if ( '' === $filter || $meta['category'] === $filter ) {
						$tools[] = array(
							'name'        => $name,
							'category'    => $meta['category'],
							'description' => $meta['description'],
						);
					}
				}
				return array( 'tools' => $tools );
			},
			'permission_callback' => static function () {
				return current_user_can( 'read' );
			},
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array( 'readonly' => true ),
			),
		);
	}

	private static function tools_describe(): array {
		return array(
			'label'               => __( 'Describe a commerce tool', 'woocommerce' ),
			'description'         => __( 'Return the full input and output schema for a named ability. Use tools-list to discover names first.', 'woocommerce' ),
			'category'            => 'woocommerce-rest',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'name' => array(
						'type'        => 'string',
						'description' => 'Ability name as returned by tools-list.',
					),
				),
				'required'   => array( 'name' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'name'          => array( 'type' => 'string' ),
					'description'   => array( 'type' => 'string' ),
					'input_schema'  => array( 'type' => 'object' ),
					'output_schema' => array( 'type' => 'object' ),
				),
				'required'      => array( 'name', 'input_schema', 'output_schema' ),
			),
			'execute_callback'    => static function ( $input ) {
				$name    = isset( $input['name'] ) ? (string) $input['name'] : '';
				$catalog = self::catalog();
				if ( ! isset( $catalog[ $name ] ) ) {
					return array(
						'error' => array(
							'code'    => 'unknown_tool',
							'message' => 'Unknown tool name. Call tools-list for the catalog.',
						),
					);
				}
				// Stub schemas — real implementation would look up the underlying ability.
				return array(
					'name'          => $name,
					'description'   => $catalog[ $name ]['description'],
					'input_schema'  => array( 'type' => 'object' ),
					'output_schema' => array( 'type' => 'object' ),
				);
			},
			'permission_callback' => static function () {
				return current_user_can( 'read' );
			},
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array( 'readonly' => true ),
			),
		);
	}

	private static function tools_invoke(): array {
		return array(
			'label'               => __( 'Invoke a commerce tool', 'woocommerce' ),
			'description'         => __( 'Execute a named ability with the given input. Use tools-describe to discover the required input shape first.', 'woocommerce' ),
			'category'            => 'woocommerce-rest',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'name'  => array(
						'type'        => 'string',
						'description' => 'Ability name as returned by tools-list.',
					),
					'input' => array(
						'type'        => 'object',
						'description' => 'Ability-specific input. See tools-describe for the expected shape.',
					),
				),
				'required'   => array( 'name' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'data'    => array( 'description' => 'Ability result; shape depends on the called ability.' ),
					'error'   => array(
						'type'       => 'object',
						'properties' => array(
							'code'    => array( 'type' => 'string' ),
							'message' => array( 'type' => 'string' ),
						),
					),
				),
				'required'   => array( 'success' ),
			),
			'execute_callback'    => static function ( $input ) {
				return array(
					'success' => false,
					'error'   => array(
						'code'    => 'not_implemented',
						'message' => 'Stub implementation for measurement only.',
					),
				);
			},
			'permission_callback' => static function () {
				return current_user_can( 'read' );
			},
			'meta'                => array( 'show_in_rest' => true ),
		);
	}
}
