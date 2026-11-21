<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Flat-rate shipping behavior for a product shipping class.
 *
 * @since 11.3.0
 */
final class ShippingClassCostBehavior {

	/**
	 * Add the configured class cost.
	 *
	 * @var string
	 */
	public const ADD = 'add';

	/**
	 * Do not add a class cost.
	 *
	 * @var string
	 */
	public const NONE = 'none';

	/**
	 * Do not offer this method for the class.
	 *
	 * @var string
	 */
	public const UNAVAILABLE = 'unavailable';
}
