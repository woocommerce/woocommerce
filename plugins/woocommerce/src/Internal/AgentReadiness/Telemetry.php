<?php
/**
 * Agent readiness fields in the WC Tracker snapshot.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the readiness check to the weekly WC Tracker snapshot, which only stores that opted in to usage tracking send.
 * Check ids and statuses only: no URLs, product names or store content.
 */
class Telemetry implements RegisterHooksInterface {

	public const PASS    = 'pass';
	public const PROBLEM = 'problem';
	public const UNKNOWN = 'unknown';

	/**
	 * Feature flags.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Readiness report.
	 *
	 * @var AgentReadiness
	 */
	private AgentReadiness $agent_readiness;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FeaturesController $features_controller Feature flags.
	 * @param AgentReadiness     $agent_readiness     Readiness report.
	 */
	final public function init( FeaturesController $features_controller, AgentReadiness $agent_readiness ): void {
		$this->features_controller = $features_controller;
		$this->agent_readiness     = $agent_readiness;
	}

	/**
	 * Register the tracker snapshot filter.
	 */
	public function register(): void {
		add_filter( 'woocommerce_tracker_data', array( $this, 'add_snapshot_to_tracker_data' ) );
	}

	/**
	 * Add the agent_readiness block to WC_Tracker's payload.
	 *
	 * @internal
	 *
	 * @param mixed $data Tracker data.
	 * @return mixed
	 */
	public function add_snapshot_to_tracker_data( $data ) {
		if ( is_array( $data ) ) {
			$data['agent_readiness'] = $this->snapshot();
		}
		return $data;
	}

	/**
	 * The feature flag, when the last scan ran, whether it stopped at the product limit, and each check's status.
	 * The store conditions are read now; output and product checks are as the last scan left them.
	 *
	 * @return array{feature_enabled:string,last_scan:string,truncated:string,store_checks:array<string,string>,product_checks:array<string,string>}
	 */
	public function snapshot(): array {
		$snapshot = array(
			'feature_enabled' => 'no',
			'last_scan'       => '',
			'truncated'       => 'no',
			'store_checks'    => array(),
			'product_checks'  => array(),
		);
		if ( ! $this->features_controller->feature_is_enabled( AgenticCommerceSettings::FEATURE_ID ) ) {
			return $snapshot;
		}
		$snapshot['feature_enabled'] = 'yes';

		// Setup steps are read now, so they are known before the first scan too.
		$report = $this->agent_readiness->get_served_report();
		if ( null !== $report ) {
			$scanned               = strtotime( (string) ( $report['generated_at'] ?? '' ) );
			$snapshot['last_scan'] = false !== $scanned ? gmdate( 'Y-m-d', $scanned ) : '';
			$snapshot['truncated'] = empty( $report['truncated'] ) ? 'no' : 'yes';
		}

		foreach ( (array) ( $report['store_checks'] ?? $this->agent_readiness->get_store_checks( null ) ) as $check ) {
			if ( ! is_array( $check ) || ! isset( $check['id'] ) ) {
				continue;
			}
			$ok = empty( $check['pending'] ) ? ( $check['ok'] ?? null ) : null;
			$snapshot['store_checks'][ (string) $check['id'] ] = null === $ok ? self::UNKNOWN : ( $ok ? self::PASS : self::PROBLEM );
		}
		foreach ( (array) ( $report['checks'] ?? array() ) as $check ) {
			if ( is_array( $check ) && isset( $check['id'] ) ) {
				$snapshot['product_checks'][ (string) $check['id'] ] = (int) ( $check['count'] ?? 0 ) > 0 ? self::PROBLEM : self::PASS;
			}
		}
		return $snapshot;
	}
}
