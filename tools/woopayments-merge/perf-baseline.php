<?php
/**
 * Per-surface payments performance probe (WooPayments → core merge, A0 harness, RULE 1).
 *
 * Measures the query count, wall time, and peak-memory delta of the payment surfaces that
 * design-spec §5.3 says must not regress. Query count is the deterministic signal (RULE 1
 * is enforced on it); time is captured as a best-of-N median for context. Emits one JSON
 * object so a baseline can be captured on the reference (current plugin) and later compared
 * against the target-native runtime.
 *
 * Run via the wrapper:
 *   WP="docker exec -i wcpay_wp_default wp --allow-root" perf-baseline.sh capture > baseline.json
 *
 * @package WooCommerce\Tools\WooPaymentsMerge
 */

global $wpdb;

if ( ! defined( 'SAVEQUERIES' ) ) {
	define( 'SAVEQUERIES', true );
}

/**
 * Measure one surface: query-count delta + median wall time over $iterations.
 *
 * @param string   $name       Surface name.
 * @param callable $op         The operation to measure.
 * @param int      $iterations How many times to run for the time median (query count uses the first warm run).
 * @return array<string,mixed>
 */
$measure = function ( string $name, callable $op, int $iterations = 5 ) use ( $wpdb ) {
	// Warm caches once so the measured cost is steady-state, not cache-cold one-offs.
	$op();

	$q_before = is_array( $wpdb->queries ) ? count( $wpdb->queries ) : 0;
	$op();
	$q_after = is_array( $wpdb->queries ) ? count( $wpdb->queries ) : 0;
	$queries = $q_after - $q_before;

	$times = array();
	for ( $i = 0; $i < $iterations; $i++ ) {
		$start   = microtime( true );
		$op();
		$times[] = ( microtime( true ) - $start ) * 1000.0;
	}
	sort( $times );
	$median_ms = $times[ (int) floor( count( $times ) / 2 ) ];

	return array(
		'queries'   => $queries,
		'median_ms' => round( $median_ms, 2 ),
	);
};

$surfaces = array();

// Surface 1: checkout gateway resolution — the hot path on every checkout render.
$surfaces['available_gateways'] = $measure(
	'available_gateways',
	function () {
		WC()->payment_gateways()->get_available_payment_gateways();
	}
);

// Surface 2: full gateway list construction (admin + checkout shared).
$surfaces['all_gateways'] = $measure(
	'all_gateways',
	function () {
		WC()->payment_gateways()->payment_gateways();
	}
);

// Surface 3: WooPayments gateway availability check (its is_available path).
$surfaces['wcpay_is_available'] = $measure(
	'wcpay_is_available',
	function () {
		$gateways = WC()->payment_gateways()->payment_gateways();
		foreach ( $gateways as $gateway ) {
			if ( 'woocommerce_payments' === $gateway->id ) {
				$gateway->is_available();
				break;
			}
		}
	}
);

$result = array(
	'php_version'  => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
	'wc_version'   => defined( 'WC_VERSION' ) ? WC_VERSION : 'unknown',
	'has_wcpay'    => class_exists( 'WC_Payments' ),
	'peak_mem_mb'  => round( memory_get_peak_usage( true ) / 1048576, 1 ),
	'surfaces'     => $surfaces,
);
ksort( $result );

WP_CLI::line( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
