<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsEventIngestor;

/**
 * Recording ingestor test double.
 */
class RecordingEventIngestor extends WooPaymentsEventIngestor {

	/**
	 * Processed events.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public array $processed_events = array();

	/**
	 * Record an event.
	 *
	 * @param array<string,mixed> $event Event payload.
	 */
	public function process( array $event ): void {
		$this->processed_events[] = $event;
	}
}
