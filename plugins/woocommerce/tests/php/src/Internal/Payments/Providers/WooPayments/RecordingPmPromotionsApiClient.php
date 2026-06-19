<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;

/**
 * Recording API client for promotion service tests.
 */
class RecordingPmPromotionsApiClient extends WooPaymentsApiClient {

	/**
	 * Promotions response.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public array $promotions_response = array();

	/**
	 * Activated promotion IDs.
	 *
	 * @var string[]
	 */
	public array $activated_promotion_ids = array();

	/**
	 * Number of promotion fetches.
	 *
	 * @var int
	 */
	public int $get_pm_promotions_calls = 0;

	/**
	 * Retrieve PM promotions.
	 *
	 * @param array<string,mixed> $store_context Store context.
	 * @return array<string,mixed>
	 */
	public function get_pm_promotions( array $store_context ): array {
		++$this->get_pm_promotions_calls;

		return $this->promotions_response;
	}

	/**
	 * Activate a PM promotion.
	 *
	 * @param string $promotion_id Promotion ID.
	 * @return array<string,mixed>
	 */
	public function activate_pm_promotion( string $promotion_id ): array {
		$this->activated_promotion_ids[] = $promotion_id;

		return array( 'success' => true );
	}
}
