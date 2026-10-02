<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the capabilities a queue can be asked about through
 * Automattic\WooCommerce\Queue\OptionsAwareQueueInterface::supports().
 *
 * Each value is also the key of the scheduling option that requests it.
 */
final class QueueCapability {
	/**
	 * Scheduling an action at a given priority, so lower values run first.
	 *
	 * @var string
	 */
	public const PRIORITY = 'priority';

	/**
	 * Scheduling an action as unique, so a matching pending or in-progress action blocks it.
	 *
	 * @var string
	 */
	public const UNIQUE = 'unique';

	/**
	 * Returns every constant on this class, as a flat list of capability names.
	 *
	 * There is no filterable helper beside this one, so the list is the complete set of
	 * capabilities a queue is expected to answer for.
	 *
	 * @since 11.3.0
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return array(
			self::PRIORITY,
			self::UNIQUE,
		);
	}
}
