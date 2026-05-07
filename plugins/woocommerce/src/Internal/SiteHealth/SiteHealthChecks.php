<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth;

use Automattic\WooCommerce\Internal\SiteHealth\Cache\CheckResultCache;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates registration of WooCommerce-specific WordPress Site Health tests.
 *
 * @internal
 */
class SiteHealthChecks {

	/**
	 * The check result cache.
	 *
	 * @var CheckResultCache
	 * Consumed by async check methods added in later tasks.
	 * @phpstan-ignore property.onlyWritten
	 */
	private CheckResultCache $cache;

	/**
	 * Initialize the class instance.
	 *
	 * @param CheckResultCache $cache The check result cache.
	 *
	 * @internal
	 */
	final public function init( CheckResultCache $cache ): void {
		$this->cache = $cache;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'site_status_tests', array( $this, 'register_tests' ) );
	}

	/**
	 * Add WooCommerce tests to the Site Health test list.
	 *
	 * @param array $tests Existing tests array with 'direct' and 'async' keys.
	 * @return array
	 */
	public function register_tests( array $tests ): array {
		// Direct and async test entries will be filled in by later tasks.
		return $tests;
	}
}
