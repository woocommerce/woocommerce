<?php
/**
 * System status tool run ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Runs a status tool from an allowlist of tools that only clear caches or
 * rebuild derived data. Tools that delete store data, reset roles or update
 * the database, and every tool an extension adds, cannot be run.
 */
class SystemStatusToolRun extends AbstractDomainAbility implements AbilityDefinition {

	/**
	 * Tools that can be run. An extension tool's callback is unknown code, so none is listed.
	 */
	public const RUNNABLE_TOOLS = array(
		'clear_transients',
		'clear_expired_transients',
		'clear_template_cache',
		'clear_system_status_theme_info_cache',
		'recount_terms',
		'regenerate_product_lookup_tables',
		'repair_coupons_lookup_table',
	);

	/**
	 * Ability ID, stable once released.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/system-status-tool-run';
	}

	/**
	 * Write registration; running a tool twice has the same effect as once.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Run status tool', 'woocommerce' ),
			'description'         => __(
				'Run a maintenance tool from WooCommerce > Status > Tools that clears caches or rebuilds derived data. Only tools that woocommerce/system-status-tools-query marks as runnable can be run.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_run_tools' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Run the tool through the tools controller, as the Tools screen does.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 11.4.0
	 */
	public static function execute( array $input ) {
		$id         = $input['id'];
		$controller = new \WC_REST_System_Status_Tools_Controller();
		$tools      = $controller->get_tools();

		if ( ! isset( $tools[ $id ] ) || ! is_array( $tools[ $id ] ) ) {
			return new \WP_Error(
				'woocommerce_ability_system_status_tool_not_found',
				__( 'This status tool is not available on this store.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$result = $controller->execute_tool( $id );
		$tool   = $tools[ $id ];

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
		/** This action is documented in includes/admin/class-wc-admin-status.php */
		do_action(
			'woocommerce_system_status_tool_executed',
			array_merge(
				array(
					'id'          => $id,
					'name'        => $tool['name'] ?? '',
					'action'      => $tool['button'] ?? '',
					'description' => $tool['desc'] ?? '',
					'disabled'    => $tool['disabled'] ?? false,
				),
				$result
			)
		);
		// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingSinceComment

		$message = wp_strip_all_tags( (string) ( $result['message'] ?? '' ) );

		if ( empty( $result['success'] ) ) {
			return new \WP_Error(
				'woocommerce_ability_system_status_tool_failed',
				'' !== $message ? $message : __( 'The status tool did not run.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return array(
			'id'      => $id,
			'message' => $message,
		);
	}

	/**
	 * Same check as running a tool through the REST API.
	 *
	 * @return bool
	 *
	 * @since 11.4.0
	 */
	public static function can_run_tools(): bool {
		return (bool) wc_rest_check_manager_permissions( 'system_status', 'edit' );
	}

	/**
	 * The enum refuses every tool outside the allowlist before the callback runs.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id' => array(
					'type'        => 'string',
					'description' => __( 'Status tool ID.', 'woocommerce' ),
					'enum'        => self::RUNNABLE_TOOLS,
				),
			),
			'required'             => array( 'id' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * A tool that schedules work reports it as scheduled, not done.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'      => array( 'type' => 'string' ),
				'message' => array(
					'type'        => 'string',
					'description' => __( 'What the tool did, or that it scheduled the work to run in the background.', 'woocommerce' ),
				),
			),
			'additionalProperties' => false,
		);
	}
}
