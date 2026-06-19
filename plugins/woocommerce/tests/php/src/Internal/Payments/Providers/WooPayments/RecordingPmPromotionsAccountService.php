<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;

/**
 * Recording account service for promotion service tests.
 */
class RecordingPmPromotionsAccountService extends WooPaymentsAccountService {

	/**
	 * Cached account data.
	 *
	 * @var array<string,mixed>
	 */
	public array $cached_account_data = array();

	/**
	 * Clear cache calls.
	 *
	 * @var int
	 */
	public int $clear_cache_calls = 0;

	/**
	 * Get cached account data.
	 *
	 * @param bool $force_refresh Whether to force refresh.
	 * @return array<string,mixed>
	 */
	public function get_cached_account_data( bool $force_refresh = false ): array {
		return $this->cached_account_data;
	}

	/**
	 * Clear account cache.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		++$this->clear_cache_calls;
	}
}
