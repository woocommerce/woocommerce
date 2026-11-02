<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the possible values of the 'woocommerce_default_customer_address' option.
 *
 * @since 10.8.0
 */
final class DefaultCustomerAddress {
	/**
	 * No default location.
	 *
	 * @var string
	 */
	public const NO_DEFAULT = '';

	/**
	 * Use the shop's base country/region as the default location.
	 *
	 * @var string
	 */
	public const BASE = 'base';

	/**
	 * Geolocate the customer's location.
	 *
	 * @var string
	 */
	public const GEOLOCATION = 'geolocation';

	/**
	 * Geolocate the customer's location with page caching support (via AJAX).
	 *
	 * @var string
	 */
	public const GEOLOCATION_AJAX = 'geolocation_ajax';
}
