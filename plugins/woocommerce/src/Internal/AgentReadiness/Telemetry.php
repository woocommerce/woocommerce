<?php
/**
 * Agent readiness fields in the WC Tracker snapshot.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the readiness check to the weekly WC Tracker snapshot, which only stores that opted in to usage tracking send.
 * Check ids and statuses, and counts of catalog search checks: no URLs, product names or page content.
 */
class Telemetry implements RegisterHooksInterface {

	public const PASS    = 'pass';
	public const PROBLEM = 'problem';
	public const UNKNOWN = 'unknown';


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
	 * @param AgentReadiness $agent_readiness     Readiness report.
	 */
	final public function init( AgentReadiness $agent_readiness ): void {
		$this->agent_readiness = $agent_readiness;
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
	 * When the last scan ran, whether it stopped at the product limit, and each check's status.
	 * The store conditions are read now; output and product checks are as the last scan left them.
	 * `ucp_search` counts the catalog search checks, to see how often they fail and whether the retry helps.
	 *
	 * @return array{feature_enabled:string,last_scan:string,truncated:string,store_checks:array<string,string>,product_checks:array<string,string>,ucp_search?:array{since:string,checks:int,first_try_failed:int,failed:int,no_answer:int,last_seen:string,last_seen_on:string,last_recovered:string,last_recovered_on:string}}
	 */
	public function snapshot(): array {
		$snapshot               = array(
			// Always yes since the feature flag was removed; kept so readers of the snapshot keep working.
			'feature_enabled' => 'yes',
			'last_scan'       => '',
			'truncated'       => 'no',
			'store_checks'    => array(),
			'product_checks'  => array(),
		);
		$snapshot['ucp_search'] = StoreOutputChecks::search_counts();

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
