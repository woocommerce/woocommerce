<?php
/**
 * TestableWooPaymentsPlatformConnectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsPlatformConnectionService;

/**
 * Testable WooPayments platform connection service.
 */
class TestableWooPaymentsPlatformConnectionService extends WooPaymentsPlatformConnectionService {

	/**
	 * Jetpack connection manager.
	 *
	 * @var JetpackConnectionManager|null
	 */
	public ?JetpackConnectionManager $manager = null;

	/**
	 * WPCOM blog ID.
	 *
	 * @var int|null
	 */
	public ?int $blog_id = 123;

	/**
	 * Get the test Jetpack connection manager.
	 *
	 * @return JetpackConnectionManager|null
	 */
	protected function get_connection_manager(): ?JetpackConnectionManager {
		return $this->manager;
	}

	/**
	 * Get the test WPCOM blog ID.
	 *
	 * @return int|null
	 */
	protected function get_blog_id(): ?int {
		return $this->blog_id;
	}
}
