<?php
/**
 * STRAP abilities bridge (experimental).
 *
 * Pattern C for the abilities-rollout verification loop: a single facade
 * ability that accepts an entity + operation discriminator and an opaque
 * params object. Minimal schema surface by design — trades client-side
 * type safety for the lowest possible context cost. Registered only on
 * MCP requests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use Automattic\WooCommerce\Internal\MCP\MCPAdapterProvider;

defined( 'ABSPATH' ) || exit;

/**
 * STRAP abilities bridge.
 */
class StrapAbilitiesBridge {

	/**
	 * Initialize.
	 */
	final public static function init(): void {
		add_action( 'abilities_api_init', array( __CLASS__, 'register_abilities' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
	}

	/**
	 * Register abilities, gated on MCP request context.
	 */
	public static function register_abilities(): void {
		if ( ! MCPAdapterProvider::is_mcp_request() ) {
			return;
		}
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability( 'woocommerce/commerce-operations', self::commerce_operations() );
	}

	private static function commerce_operations(): array {
		return array(
			'label'               => __( 'WooCommerce Commerce Operations', 'woocommerce' ),
			'description'         => __(
				'Perform CRUD operations against WooCommerce commerce entities. Supply the entity (products or orders), the operation (query, create, update, or delete), and an opaque params object whose accepted keys depend on the entity/operation pair. Discover supported params by calling query without filters first.',
				'woocommerce'
			),
			'category'            => 'woocommerce-rest',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'entity'    => array(
						'type'        => 'string',
						'enum'        => array( 'products', 'orders' ),
						'description' => 'The commerce entity to operate on.',
					),
					'operation' => array(
						'type'        => 'string',
						'enum'        => array( 'query', 'create', 'update', 'delete' ),
						'description' => 'The CRUD operation. Delete is not supported for orders.',
					),
					'id'        => array(
						'type'        => 'integer',
						'description' => 'Required for update and delete; optional for query (returns single).',
					),
					'params'    => array(
						'type'        => 'object',
						'description' => 'Operation parameters. Accepted keys vary by entity/operation.',
					),
				),
				'required'   => array( 'entity', 'operation' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'data'    => array( 'description' => 'Operation result. Shape depends on entity/operation.' ),
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
				return array( 'success' => false, 'error' => array( 'code' => 'not_implemented', 'message' => 'Stub implementation for measurement only.' ) );
			},
			'permission_callback' => static function () {
				return current_user_can( 'manage_woocommerce' );
			},
			'meta'                => array( 'show_in_rest' => true ),
		);
	}
}
