<?php
/**
 * MultiCurrencyRuntimeArbiter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;

/**
 * Decides which multi-currency runtime owns the price/currency pipeline.
 *
 * During the WooPayments merge, the standalone plugin still contains the legacy
 * multi-currency module. Core-native multi-currency must therefore use the same
 * ownership signal as native payments: plugin-mode keeps plugin multi-currency,
 * and native payments mode flips the price/currency pipeline to core.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyRuntimeArbiter {

	/**
	 * Owner value: the standalone WooPayments plugin owns multi-currency.
	 *
	 * @var string
	 */
	const OWNER_PLUGIN = 'plugin';

	/**
	 * Owner value: WooCommerce core owns multi-currency.
	 *
	 * @var string
	 */
	const OWNER_CORE = 'core';

	/**
	 * Owner value: no multi-currency runtime is active for this site.
	 *
	 * @var string
	 */
	const OWNER_NONE = 'none';

	/**
	 * Payments runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $payments_arbiter;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter $payments_arbiter Payments runtime owner arbiter.
	 */
	final public function init( NativePaymentsRuntimeArbiter $payments_arbiter ): void {
		$this->payments_arbiter = $payments_arbiter;
	}

	/**
	 * Get the multi-currency runtime owner for the current site.
	 *
	 * @return string One of self::OWNER_PLUGIN, self::OWNER_CORE, self::OWNER_NONE.
	 */
	public function get_runtime_owner(): string {
		$payments_owner = $this->payments_arbiter->get_runtime_owner();

		if ( NativePaymentsRuntimeArbiter::OWNER_PLUGIN === $payments_owner ) {
			return self::OWNER_PLUGIN;
		}

		if ( NativePaymentsRuntimeArbiter::OWNER_NATIVE === $payments_owner ) {
			return self::OWNER_CORE;
		}

		return self::OWNER_NONE;
	}

	/**
	 * Tell whether core multi-currency may register price/currency hooks.
	 *
	 * @return bool True when core owns multi-currency.
	 */
	public function should_core_register(): bool {
		return self::OWNER_CORE === $this->get_runtime_owner();
	}

	/**
	 * Tell whether the standalone plugin owns multi-currency.
	 *
	 * @return bool True when plugin multi-currency owns the price/currency pipeline.
	 */
	public function should_plugin_register(): bool {
		return self::OWNER_PLUGIN === $this->get_runtime_owner();
	}
}
