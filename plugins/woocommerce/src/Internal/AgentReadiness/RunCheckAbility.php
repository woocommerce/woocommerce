<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Ability that checks a product again or queues a store check, for AbilitiesLoader.
 * No name while the feature is disabled, so the loader skips it.
 *
 * @since 11.4.0
 */
class RunCheckAbility implements AbilityDefinition {

	use ReadinessAbility;

	public const NAME = 'woocommerce/run-agent-readiness-check';

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
					'readonly'    => false,
					'idempotent'  => false,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Check one product now, or queue a check of the whole store.
	 *
	 * @param array<string, mixed> $input Ability input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function execute( array $input ) {
		$product_id = $input['product_id'] ?? 0;
		if ( 0 === $product_id ) {
			$scan = wc_get_container()->get( ScheduledScan::class );
			$scan->queue();
			$report       = wc_get_container()->get( AgentReadiness::class )->get_cached_report();
			$pending      = $scan->is_pending();
			$generated_at = is_array( $report ) ? ( $report['generated_at'] ?? null ) : null;
			return array(
				'scan_pending'        => $pending,
				'scan_stalled'        => $pending && $scan->is_stalled(),
				'report_generated_at' => is_string( $generated_at ) ? $generated_at : null,
			);
		}

		$readiness = wc_get_container()->get( AgentReadiness::class );
		$product   = wc_get_product( $product_id );
		if ( ! $product instanceof \WC_Product ) {
			return new \WP_Error( 'woocommerce_agent_readiness_product_not_found', __( 'Product not found.', 'woocommerce' ), array( 'status' => 404 ) );
		}
		$result = $readiness->check_product( $product );
		if ( null === $result ) {
			return new \WP_Error( 'woocommerce_agent_readiness_product_not_checked', __( 'Only published simple and variable products shown in the shop catalog are checked.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$rules  = $readiness->get_default_rules();
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
	private static function output_schema(): array {
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
}
