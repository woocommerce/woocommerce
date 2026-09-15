<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders\WooPayments;

/**
 * Probe for detecting NOX profile object instantiation.
 */
final class NoxProfileObjectInstantiationProbe {

	/**
	 * Whether the probe was instantiated from a serialized value.
	 *
	 * @var bool
	 */
	public static bool $was_unserialized = false;

	/**
	 * Record object instantiation from a serialized value.
	 */
	public function __wakeup(): void {
		self::$was_unserialized = true;
	}
}
