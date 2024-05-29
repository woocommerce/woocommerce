<?php
namespace Automattic\WooCommerce\Blocks\Domain\Services;

/**
 * Service class that used to handle feature flags. That functionality
 * is removed now and it is only used to determine "environment".
 *
 * @internal
 */
class FeatureGating {
	/**
	 * Current environment
	 *
	 * @var string
	 */
	private $environment;

	const PRODUCTION_ENVIRONMENT  = 'production';
	const DEVELOPMENT_ENVIRONMENT = 'development';
	const TEST_ENVIRONMENT        = 'test';

	/**
	 * Constructor
	 *
	 * @param string $environment Hardcoded environment value. Useful for tests.
	 */
	public function __construct( $environment = 'unset' ) {
		$this->environment = $environment;
		$this->load_environment();
	}

	/**
	 * Set correct environment.
	 */
	public function load_environment() {
		if ( 'unset' === $this->environment ) {
			if ( file_exists( __DIR__ . '/../../../../blocks.ini' ) ) {
				$allowed_environments = [ self::PRODUCTION_ENVIRONMENT, self::DEVELOPMENT_ENVIRONMENT, self::TEST_ENVIRONMENT ];
				$woo_options          = parse_ini_file( __DIR__ . '/../../../../blocks.ini' );
				$this->environment    = is_array( $woo_options ) && in_array( $woo_options['woocommerce_blocks_env'], $allowed_environments, true ) ? $woo_options['woocommerce_blocks_env'] : self::PRODUCTION_ENVIRONMENT;
			} else {
				$this->environment = self::PRODUCTION_ENVIRONMENT;
			}
		}
	}

	/**
	 * Returns the current environment value.
	 *
	 * @return string
	 */
	public function get_environment() {
		return $this->environment;
	}

	/**
	 * Checks if we're executing the code in an development environment.
	 *
	 * @return boolean
	 */
	public function is_development_environment() {
		return self::DEVELOPMENT_ENVIRONMENT === $this->environment;
	}

	/**
	 * Checks if we're executing the code in a production environment.
	 *
	 * @return boolean
	 */
	public function is_production_environment() {
		return self::PRODUCTION_ENVIRONMENT === $this->environment;
	}

	/**
	 * Checks if we're executing the code in a test environment.
	 *
	 * @return boolean
	 */
	public function is_test_environment() {
		return self::TEST_ENVIRONMENT === $this->environment;
	}
}
