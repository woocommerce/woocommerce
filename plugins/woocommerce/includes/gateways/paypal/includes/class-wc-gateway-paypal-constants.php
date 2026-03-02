<?php
/**
 * PayPal Gateway Constants.
 *
 * Provides constants for PayPal payment statuses, intents, and other PayPal-related values.
 *
 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants instead. This class will be removed in 11.0.0.
 * @version    10.3.0
 * @package    WooCommerce\Gateways
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;

/**
 * WC_Gateway_Paypal_Constants Class.
 *
 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants instead. This class will be removed in 11.0.0.
 */
class WC_Gateway_Paypal_Constants {
	/**
	 * PayPal proxy request timeout.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::WPCOM_PROXY_REQUEST_TIMEOUT instead.
	 */
	const WPCOM_PROXY_REQUEST_TIMEOUT = PayPalConstants::WPCOM_PROXY_REQUEST_TIMEOUT;

	/**
	 * PayPal payment statuses.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::STATUS_* instead.
	 */
	const STATUS_COMPLETED             = PayPalConstants::STATUS_COMPLETED;
	const STATUS_APPROVED              = PayPalConstants::STATUS_APPROVED;
	const STATUS_CAPTURED              = PayPalConstants::STATUS_CAPTURED;
	const STATUS_AUTHORIZED            = PayPalConstants::STATUS_AUTHORIZED;
	const STATUS_PAYER_ACTION_REQUIRED = PayPalConstants::STATUS_PAYER_ACTION_REQUIRED;
	const VOIDED                       = PayPalConstants::VOIDED;

	/**
	 * PayPal payment intents.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::INTENT_* instead.
	 */
	const INTENT_CAPTURE   = PayPalConstants::INTENT_CAPTURE;
	const INTENT_AUTHORIZE = PayPalConstants::INTENT_AUTHORIZE;

	/**
	 * PayPal payment actions.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::PAYMENT_ACTION_* instead.
	 */
	const PAYMENT_ACTION_CAPTURE   = PayPalConstants::PAYMENT_ACTION_CAPTURE;
	const PAYMENT_ACTION_AUTHORIZE = PayPalConstants::PAYMENT_ACTION_AUTHORIZE;

	/**
	 * PayPal shipping preferences.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::SHIPPING_* instead.
	 */
	const SHIPPING_NO_SHIPPING          = PayPalConstants::SHIPPING_NO_SHIPPING;
	const SHIPPING_GET_FROM_FILE        = PayPalConstants::SHIPPING_GET_FROM_FILE;
	const SHIPPING_SET_PROVIDED_ADDRESS = PayPalConstants::SHIPPING_SET_PROVIDED_ADDRESS;

	/**
	 * PayPal user actions.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::USER_ACTION_* instead.
	 */
	const USER_ACTION_PAY_NOW = PayPalConstants::USER_ACTION_PAY_NOW;

	/**
	 * Maximum lengths for PayPal fields.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::PAYPAL_* instead.
	 */
	const PAYPAL_ORDER_ITEM_NAME_MAX_LENGTH = PayPalConstants::PAYPAL_ORDER_ITEM_NAME_MAX_LENGTH;
	const PAYPAL_INVOICE_ID_MAX_LENGTH      = PayPalConstants::PAYPAL_INVOICE_ID_MAX_LENGTH;
	const PAYPAL_ADDRESS_LINE_MAX_LENGTH    = PayPalConstants::PAYPAL_ADDRESS_LINE_MAX_LENGTH;
	const PAYPAL_COUNTRY_CODE_LENGTH        = PayPalConstants::PAYPAL_COUNTRY_CODE_LENGTH;
	const PAYPAL_STATE_MAX_LENGTH           = PayPalConstants::PAYPAL_STATE_MAX_LENGTH;
	const PAYPAL_CITY_MAX_LENGTH            = PayPalConstants::PAYPAL_CITY_MAX_LENGTH;
	const PAYPAL_POSTAL_CODE_MAX_LENGTH     = PayPalConstants::PAYPAL_POSTAL_CODE_MAX_LENGTH;
	const PAYPAL_LOCALE_MAX_LENGTH          = PayPalConstants::PAYPAL_LOCALE_MAX_LENGTH;

	/**
	 * Supported payment sources.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::PAYMENT_SOURCE_* instead.
	 */
	const PAYMENT_SOURCE_PAYPAL     = PayPalConstants::PAYMENT_SOURCE_PAYPAL;
	const PAYMENT_SOURCE_VENMO      = PayPalConstants::PAYMENT_SOURCE_VENMO;
	const PAYMENT_SOURCE_PAYLATER   = PayPalConstants::PAYMENT_SOURCE_PAYLATER;
	const SUPPORTED_PAYMENT_SOURCES = PayPalConstants::SUPPORTED_PAYMENT_SOURCES;

	/**
	 * Fields to redact from logs.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::FIELDS_TO_REDACT instead.
	 * @var array
	 */
	const FIELDS_TO_REDACT = PayPalConstants::FIELDS_TO_REDACT;

	/**
	 * List of currencies supported by PayPal (Orders API V2).
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::SUPPORTED_CURRENCIES instead.
	 * @var array<string>
	 */
	const SUPPORTED_CURRENCIES = PayPalConstants::SUPPORTED_CURRENCIES;
}
