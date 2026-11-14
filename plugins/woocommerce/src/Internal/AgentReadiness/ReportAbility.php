<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Ability that reads the readiness report, for AbilitiesLoader.
 * No name while the feature is disabled, so the loader skips it.
 *
 * @since 11.4.0
 */
class ReportAbility implements AbilityDefinition {

	use ReadinessAbility;

	public const NAME = 'woocommerce/get-agent-readiness';

	/**
	 * Ability name, or an empty string while the feature is disabled.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return self::name_when_enabled( self::NAME );
	}

	/**
	 * Registration arguments.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_registration_args(): array {
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
			'output_schema'       => self::output_schema(),
			'execute_callback'    => array( self::class, 'execute' ),
			'permission_callback' => array( self::class, 'can_manage' ),
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
	 * The stored report with store checks as they stand now, whether a scan is pending, and whether it has stalled.
	 *
	 * @param array<string, mixed> $input Ability input; this ability takes none.
	 * @return array{report:array<string,mixed>|null,scan_pending:bool,scan_stalled:bool}
	 */
	public static function execute( array $input ): array {
		unset( $input );
		$readiness = wc_get_container()->get( AgentReadiness::class );
		$scan      = wc_get_container()->get( ScheduledScan::class );
		$pending   = $scan->is_pending();
		return array(
			'report'       => $readiness->get_served_report(),
			'scan_pending' => $pending,
			'scan_stalled' => $pending && $scan->is_stalled(),
		);
	}

	/**
	 * Output schema, following the report format in this directory's README.
	 *
	 * @return array<string, mixed>
	 */
	private static function output_schema(): array {
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
