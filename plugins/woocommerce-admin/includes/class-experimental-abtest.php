<?php
/**
 * NOTE: this is a temporary class and can be replaced by jetpack-abtest after
 * https://github.com/Automattic/jetpack/issues/19596 has been fixed.
 *
 * A class that interacts with Explat A/B tests.
 *
 * This class is experimental. It is a fork of the jetpack-abtest package and
 * updated for use with ExPlat. These changes are planned to be contributed
 * back to the upstream Jetpack package. If accepted, this class should then
 * be superseded by the Jetpack class using Composer.
 *
 * This class should not be used externally.
 *
 * @package WooCommerce\Admin
 * @link https://packagist.org/packages/automattic/jetpack-abtest
 */

namespace WooCommerce\Admin;

/**
 * This class provides an interface to the Explat A/B tests.
 *
 * @internal This class is experimental and should not be used externally due to planned breaking changes.
 */
final class Experimental_Abtest {

	/**
	 * A variable to hold the tests we fetched, and their variations for the current user.
	 *
	 * @var array
	 */
	private $tests = array();

	/**
	 * ExPlat Anonymous ID.
	 *
	 * @var string
	 */
	private $anon_id = null;

	/**
	 * ExPlat Platform name.
	 *
	 * @var string
	 */
	private $platform = 'woocommerce';

	/**
	 * Whether trcking consent is given.
	 *
	 * @var bool
	 */
	private $consent = false;

	/**
	 * Constructor.
	 *
	 * @param string $anon_id ExPlat anonymous ID.
	 * @param string $platform ExPlat platform name.
	 * @param bool   $consent Whether tracking consent is given.
	 */
	public function __construct( string $anon_id, string $platform, bool $consent ) {
		$this->anon_id  = $anon_id;
		$this->platform = $platform;
		$this->consent  = $consent;
	}

	/**
	 * Retrieve the test variation for a provided A/B test.
	 *
	 * @param string $test_name Name of the A/B test.
	 * @return mixed|null A/B test variation, or null on failure.
	 */
	public function get_variation( $test_name ) {
		// Default to the control variation when users haven't consented to tracking.
		if ( ! $this->consent ) {
			return 'control';
		}

		$variation = $this->fetch_variation( $test_name );

		// If there was an error retrieving a variation, conceal the error for the consumer.
		if ( is_wp_error( $variation ) ) {
			return 'control';
		}

		return $variation;
	}

	/**
	 * Fetch and cache the test variation for a provided A/B test from WP.com.
	 *
	 * ExPlat returns a null value when the assigned variation is control or
	 * an assignment has not been set. In these instances, this method returns
	 * a value of "control".
	 *
	 * @param string $test_name Name of the A/B test.
	 * @return array|\WP_Error A/B test variation, or error on failure.
	 */
	protected function fetch_variation( $test_name ) {
		// Make sure test name exists.
		if ( ! $test_name ) {
			return new \WP_Error( 'test_name_not_provided', 'A/B test name has not been provided.' );
		}

		// Make sure test name is a valid one.
		if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $test_name ) ) {
			return new \WP_Error( 'invalid_test_name', 'Invalid A/B test name.' );
		}

		// Return internal-cached test variations.
		if ( isset( $this->tests[ $test_name ] ) ) {
			return $this->tests[ $test_name ];
		}

		// Return external-cached test variations.
		if ( ! empty( get_transient( 'abtest_variation_' . $test_name ) ) ) {
			return get_transient( 'abtest_variation_' . $test_name );
		}

		// Make the request to the WP.com API.
		$response = $this->request_variation( $test_name );

		// Bail if there was an error or malformed response.
		if ( is_wp_error( $response ) || ! is_array( $response ) || ! isset( $response['body'] ) ) {
			return new \WP_Error( 'failed_to_fetch_data', 'Unable to fetch the requested data.' );
		}

		// Decode the results.
		$results = json_decode( $response['body'], true );

		// Bail if there were no resultsreturned.
		if ( ! is_array( $results ) ) {
			return new \WP_Error( 'unexpected_data_format', 'Data was not returned in the expected format.' );
		}

		// Store the variation in our internal cache.
		$this->tests[ $test_name ] = $results['variations'][ $test_name ] ?? null;

		$variation = $results['variations'][ $test_name ] ?? 'control';

		// Store the variation in our external cache.
		if ( ! empty( $results['ttl'] ) ) {
			set_transient( 'abtest_variation_' . $test_name, $variation, $results['ttl'] );
		}

		return $variation;
	}

	/**
	 * Perform the request for a variation of a provided A/B test from WP.com.
	 *
	 * @param string $test_name Name of the A/B test.
	 * @return array|\WP_Error A/B test variation error on failure.
	 */
	protected function request_variation( $test_name ) {
		$args = array(
			'experiment_name'  => $test_name,
			'anon_id'          => rawurlencode( $this->anon_id ),
			'woo_country_code' => rawurlencode( get_option( 'woocommerce_default_country', 'US:CA' ) ),
		);

		$url = add_query_arg(
			$args,
			sprintf(
				'https://public-api.wordpress.com/wpcom/v2/experiments/0.1.0/assignments/%s',
				$this->platform
			)
		);

		$get = wp_remote_get( $url );

		return $get;
	}
}

