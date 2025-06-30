<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Suggestions\Mocks;

use Automattic\WooCommerce\Internal\Admin\Suggestions\Incentives\Incentive;

/**
 * Fake incentive provider for testing.
 */
class FakeIncentive extends Incentive {
	/**
	 * Whether the extension should be reported as active.
	 *
	 * @var bool
	 */
	public bool $extension_active = false;

	/**
	 * Check if the extension is active.
	 *
	 * @return bool
	 */
	protected function is_extension_active(): bool {
		return $this->extension_active;
	}

	/**
	 * Get the incentives list for a specific payment extension suggestion.
	 *
	 * @param string $country_code The business location country code to get incentives for.
	 *
	 * @return array The incentives list with details for each incentive.
	 */
	protected function get_incentives( string $country_code ): array {
		return array();
	}
}
