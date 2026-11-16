<?php
/**
 * System status tools query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Lists the status tools, including those added by extensions. Listing never
 * runs a tool.
 */
class SystemStatusToolsQuery extends AbstractDomainAbility implements AbilityDefinition {

	/**
	 * Ability ID, stable once released.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/system-status-tools-query';
	}

	/**
	 * Read-only, shop-manager-level registration.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Query status tools', 'woocommerce' ),
			'description'         => __(
				'List the maintenance tools under WooCommerce > Status > Tools, in screen order, and what each does. Run a runnable one with woocommerce/system-status-tool-run.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'include' => array(
						'type'        => 'array',
						'description' => __( 'Return only these tool IDs.', 'woocommerce' ),
						'items'       => array(
							'type'      => 'string',
							'minLength' => 1,
						),
					),
				),
				'additionalProperties' => false,
				'default'              => array(),
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'properties'           => array(
					'tools' => array(
						'type'  => 'array',
						'items' => self::get_tool_output_schema(),
					),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_query_tools' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => true,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * The tools in the order of the Tools screen.
	 *
	 * @param array $input Ability input.
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function execute( array $input ): array {
		$include = $input['include'] ?? null;
		$items   = array();

		foreach ( ( new \WC_REST_System_Status_Tools_Controller() )->get_tools() as $id => $tool ) {
			$id = (string) $id;

			if ( ! is_array( $tool ) || ( null !== $include && ! in_array( $id, $include, true ) ) ) {
				continue;
			}

			$items[] = array(
				'id'          => $id,
				'name'        => wp_strip_all_tags( (string) ( $tool['name'] ?? '' ) ),
				'description' => wp_strip_all_tags( (string) ( $tool['desc'] ?? '' ) ),
				'runnable'    => in_array( $id, SystemStatusToolRun::RUNNABLE_TOOLS, true ),
			);
		}

		return array( 'tools' => $items );
	}

	/**
	 * Same check as listing the tools through the REST API.
	 *
	 * @return bool
	 *
	 * @since 11.4.0
	 */
	public static function can_query_tools(): bool {
		return (bool) wc_rest_check_manager_permissions( 'system_status', 'read' );
	}

	/**
	 * Closed item shape; an extension tool's callback and other keys stay out.
	 *
	 * @return array
	 */
	private static function get_tool_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'          => array( 'type' => 'string' ),
				'name'        => array( 'type' => 'string' ),
				'description' => array( 'type' => 'string' ),
				'runnable'    => array(
					'type'        => 'boolean',
					'description' => __( 'Whether woocommerce/system-status-tool-run can run the tool.', 'woocommerce' ),
				),
			),
			'additionalProperties' => false,
		);
	}
}
