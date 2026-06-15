<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsFailedEventsProvider;

/**
 * Static failed-events provider test double.
 */
class StaticFailedEventsProvider extends WooPaymentsFailedEventsProvider {

	/**
	 * Failed-events response.
	 *
	 * @var array<string,mixed>
	 */
	private array $response;

	/**
	 * Constructor.
	 *
	 * @param array<string,mixed> $response Failed-events response.
	 */
	public function __construct(
		array $response = array(
			'data'     => array(),
			'has_more' => false,
		)
	) {
		$this->response = $response;
	}

	/**
	 * Get failed webhook events.
	 *
	 * @return array<string,mixed>
	 */
	public function get_failed_webhook_events(): array {
		return $this->response;
	}
}
