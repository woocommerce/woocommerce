<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;

defined( 'ABSPATH' ) || exit;

/**
 * Abilities that let an agent connected to the store (over MCP) read the
 * readiness report and check again after a fix. Registered only while the agent_readiness feature is
 * enabled, and only for users who can manage WooCommerce.
 *
 * AbilitiesLoader registers them through the definition classes in this directory,
 * which read their arguments from registration_args().
 *
 * @since 11.4.0
 */
class ReadinessAbilities {

	public const GET_REPORT = 'woocommerce/get-agent-readiness';

	public const RUN_CHECK = 'woocommerce/run-agent-readiness-check';

	/**
	 * Features controller.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Readiness service.
	 *
	 * @var AgentReadiness
	 */
	private AgentReadiness $agent_readiness;

	/**
	 * Background scan.
	 *
	 * @var ScheduledScan
	 */
	private ScheduledScan $scheduled_scan;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FeaturesController $features_controller Features controller.
	 * @param AgentReadiness     $agent_readiness     Readiness service.
	 * @param ScheduledScan      $scheduled_scan      Background scan.
	 */
	final public function init( FeaturesController $features_controller, AgentReadiness $agent_readiness, ScheduledScan $scheduled_scan ): void {
		$this->features_controller = $features_controller;
		$this->agent_readiness     = $agent_readiness;
		$this->scheduled_scan      = $scheduled_scan;
	}

	/**
	 * Whether the abilities are registered: while the feature is enabled.
	 *
	 * @internal
	 */
	public function is_enabled(): bool {
		return $this->features_controller->feature_is_enabled( AgenticCommerceSettings::FEATURE_ID );
	}

	/**
	 * Registration arguments of an ability, for the definition class AbilitiesLoader registers it with.
	 *
	 * @internal
	 *
	 * @param string $name Ability name.
	 * @return array<string, mixed>
	 */
	public function registration_args( string $name ): array {
		if ( self::GET_REPORT === $name ) {
			return array(
				'label'               => __( 'Get agent readiness', 'woocommerce' ),
				'description'         => __( 'Read the last agent readiness check: store settings and pages that keep AI shopping agents from finding or buying products, and product problems per channel (ChatGPT, Google, Copilot), each with why it matters and what to change. Store settings are read now; product results come from the last background check.', 'woocommerce' ),
				'category'            => 'woocommerce',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(),
					'additionalProperties' => false,
					'default'              => array(),
				),
				'output_schema'       => self::report_schema(),
				'execute_callback'    => array( $this, 'get_report' ),
				'permission_callback' => array( $this, 'can_manage' ),
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
		if ( self::RUN_CHECK === $name ) {
			return array(
				'label'               => __( 'Run agent readiness check', 'woocommerce' ),
				'description'         => __( 'Check one product again now, after fixing it, and get its failing checks and status per channel. Without a product id, queue a check of the whole store in the background; read the result with woocommerce/get-agent-readiness once scan_pending is false. When scan_stalled is true, scheduled actions are not running on the store and the check waits for them.', 'woocommerce' ),
				'category'            => 'woocommerce',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'product_id' => array(
							'type'        => 'integer',
							'description' => __( 'Product or variation to check now. A variation is checked as its parent product.', 'woocommerce' ),
							'minimum'     => 1,
						),
					),
					'additionalProperties' => false,
					'default'              => array(),
				),
				'output_schema'       => self::run_schema(),
				'execute_callback'    => array( $this, 'run_check' ),
				'permission_callback' => array( $this, 'can_manage' ),
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
		return array();
	}

	/**
	 * Whether the current user may use the abilities.
	 *
	 * @internal
	 */
	public function can_manage(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * The stored report with store checks as they stand now, whether a scan is pending, and whether it has stalled.
	 *
	 * @internal
	 *
	 * @param mixed $input Ability input; this ability takes none.
	 * @return array{report:array<string,mixed>|null,scan_pending:bool,scan_stalled:bool}
	 */
	public function get_report( $input = null ): array {
		unset( $input );
		$report = $this->agent_readiness->get_cached_report();
		if ( null !== $report ) {
			$report['store_checks'] = $this->agent_readiness->get_store_checks( $report );
		}
		$pending = $this->scheduled_scan->is_pending();
		return array(
			'report'       => $report,
			'scan_pending' => $pending,
			'scan_stalled' => $pending && $this->scheduled_scan->is_stalled(),
		);
	}

	/**
	 * Check one product now, or queue a check of the whole store.
	 *
	 * @internal
	 *
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function run_check( $input = null ) {
		$product_id = is_array( $input ) ? absint( $input['product_id'] ?? 0 ) : 0;
		if ( 0 === $product_id ) {
			$this->scheduled_scan->queue();
			$report  = $this->agent_readiness->get_cached_report();
			$pending = $this->scheduled_scan->is_pending();
			return array(
				'scan_pending'        => $pending,
				'scan_stalled'        => $pending && $this->scheduled_scan->is_stalled(),
				'report_generated_at' => is_string( $report['generated_at'] ?? null ) ? $report['generated_at'] : null,
			);
		}

		$product = wc_get_product( $product_id );
		if ( ! $product instanceof \WC_Product ) {
			return new \WP_Error( 'woocommerce_agent_readiness_product_not_found', __( 'Product not found.', 'woocommerce' ), array( 'status' => 404 ) );
		}
		$result = $this->agent_readiness->check_product( $product );
		if ( null === $result ) {
			return new \WP_Error( 'woocommerce_agent_readiness_product_not_checked', __( 'Only published simple and variable products shown in the shop catalog are checked.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$rules  = $this->agent_readiness->get_default_rules();
		$checks = array();
		foreach ( $result['checks'] as $id => $channels ) {
			$checks[] = array(
				'id'       => (string) $id,
				'label'    => isset( $rules[ $id ] ) ? $rules[ $id ]->label() : '',
				'fix'      => isset( $rules[ $id ] ) ? $rules[ $id ]->fix() : '',
				'channels' => $channels,
			);
		}
		return array(
			'product' => array(
				'product_id' => $result['product_id'],
				'edit_url'   => admin_url( 'post.php?post=' . $result['product_id'] . '&action=edit' ),
				'statuses'   => $result['statuses'],
				'checks'     => $checks,
			),
		);
	}

	/**
	 * Output schema of the run ability.
	 *
	 * @return array<string, mixed>
	 */
	private static function run_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'scan_pending'        => array(
					'type'        => 'boolean',
					'description' => __( 'Whole store: whether the queued check has not finished yet.', 'woocommerce' ),
				),
				'scan_stalled'        => array(
					'type'        => 'boolean',
					'description' => __( 'Whole store: whether the queued check has not started because scheduled actions are not running on the store. It runs once they do; until then the report does not change.', 'woocommerce' ),
				),
				'report_generated_at' => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Whole store: when the report agents can read now was made; it changes when the queued check finishes.', 'woocommerce' ),
				),
				'product'             => array(
					'type'       => 'object',
					'properties' => array(
						'product_id' => array( 'type' => 'integer' ),
						'edit_url'   => array( 'type' => 'string' ),
						'statuses'   => array(
							'type'                 => 'object',
							'description'          => __( 'Status by channel id: not_listable, with_gaps or complete.', 'woocommerce' ),
							'additionalProperties' => array(
								'type' => 'string',
								'enum' => array( ProductScan::NOT_LISTABLE, ProductScan::WITH_GAPS, ProductScan::COMPLETE ),
							),
						),
						'checks'     => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'       => array( 'type' => 'string' ),
									'label'    => array( 'type' => 'string' ),
									'fix'      => array( 'type' => 'string' ),
									'channels' => self::channel_map_schema(),
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Schema of a check's field and effect by channel.
	 *
	 * @return array<string, mixed>
	 */
	private static function channel_map_schema(): array {
		return array(
			'type'                 => 'object',
			'description'          => __( 'Feed field and effect by channel id: not_listable when the channel requires the field, gaps when the product lists with a gap. Empty for checks about the product page.', 'woocommerce' ),
			'additionalProperties' => array(
				'type'       => 'object',
				'properties' => array(
					'field'  => array( 'type' => 'string' ),
					'effect' => array(
						'type' => 'string',
						'enum' => array( ReadinessRule::EFFECT_NOT_LISTABLE, ReadinessRule::EFFECT_GAPS ),
					),
				),
			),
		);
	}

	/**
	 * Output schema of the report ability, following the report format in this directory's README.
	 *
	 * @return array<string, mixed>
	 */
	private static function report_schema(): array {
		$channel_map = self::channel_map_schema();
		return array(
			'type'       => 'object',
			'properties' => array(
				'report'       => array(
					'type'        => array( 'object', 'null' ),
					'description' => __( 'The last agent readiness check, or null before the first one.', 'woocommerce' ),
					'properties'  => array(
						'generated_at'        => array(
							'type'   => 'string',
							'format' => 'date-time',
						),
						'products'            => array(
							'type'        => 'integer',
							'description' => __( 'Published simple and variable products checked.', 'woocommerce' ),
						),
						'hidden_from_catalog' => array(
							'type'        => 'integer',
							'description' => __( 'Products not checked because the merchant hid them from the shop catalog.', 'woocommerce' ),
						),
						'unsupported_types'   => array(
							'type'        => 'integer',
							'description' => __( 'Products not checked because agents don\'t support their type yet.', 'woocommerce' ),
						),
						'store_checks'        => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'       => array( 'type' => 'string' ),
									'ok'       => array(
										'type'        => array( 'boolean', 'null' ),
										'description' => __( 'True when the store passes, false when it fails, null when the check could not run.', 'woocommerce' ),
									),
									'blocks'   => array(
										'type'        => 'string',
										'description' => __( 'What a failure stops: listing (every product everywhere), discovery (harder for agents to find and read the store), ucp (agents using UCP) or checkout (agent orders).', 'woocommerce' ),
									),
									'level'    => array(
										'type' => 'string',
										'enum' => array( StoreConditions::LEVEL_REQUIRED, StoreConditions::LEVEL_RECOMMENDED ),
									),
									'name'     => array( 'type' => 'string' ),
									'label'    => array( 'type' => 'string' ),
									'why'      => array( 'type' => 'string' ),
									'fix'      => array( 'type' => 'string' ),
									'fix_url'  => array( 'type' => 'string' ),
									'conflict' => array(
										'type'        => 'boolean',
										'description' => __( 'On output checks: true only when the check failed and the store is set to send this, so something else is likely replacing it.', 'woocommerce' ),
									),
									'pending'  => array(
										'type'        => 'boolean',
										'description' => __( 'On output checks: true for a check of output a coming WooCommerce update builds; it does not run yet.', 'woocommerce' ),
									),
								),
							),
						),
						'channels'            => array(
							'type'                 => 'object',
							'additionalProperties' => array(
								'type'       => 'object',
								'properties' => array(
									'name'         => array( 'type' => 'string' ),
									'listable'     => array( 'type' => 'integer' ),
									'not_listable' => array( 'type' => 'integer' ),
									'with_gaps'    => array( 'type' => 'integer' ),
									'complete'     => array( 'type' => 'integer' ),
								),
							),
						),
						'checks'              => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'          => array( 'type' => 'string' ),
									'label'       => array( 'type' => 'string' ),
									'why'         => array( 'type' => 'string' ),
									'fix'         => array( 'type' => 'string' ),
									'channels'    => $channel_map,
									'count'       => array( 'type' => 'integer' ),
									'product_ids' => array(
										'type'        => 'array',
										'description' => __( 'Up to 10 failing products, most recently modified first.', 'woocommerce' ),
										'items'       => array( 'type' => 'integer' ),
									),
									'fix_url'     => array( 'type' => 'string' ),
								),
							),
						),
						'declared'            => array(
							'type'        => 'array',
							'description' => __( 'Protocols and files the store declares to agents. Information only: found or not found, never a problem.', 'woocommerce' ),
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'    => array( 'type' => 'string' ),
									'name'  => array( 'type' => 'string' ),
									'found' => array( 'type' => array( 'boolean', 'null' ) ),
								),
							),
						),
						'truncated'           => array( 'type' => 'boolean' ),
					),
				),
				'scan_pending' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether a requested check has not finished yet.', 'woocommerce' ),
				),
				'scan_stalled' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the requested check has not started because scheduled actions are not running on the store. It stays queued and runs once they do; until then the report does not change.', 'woocommerce' ),
				),
			),
		);
	}
}
