<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Gateways\PayPal;

/**
 * PayPal Gateway Constants.
 *
 * Provides constants for PayPal payment statuses, intents, and other PayPal-related values.
 *
 * @since 10.5.0
 */
class Constants {
	/**
	 * PayPal proxy request timeout.
	 */
	const WPCOM_PROXY_REQUEST_TIMEOUT = 60;

	/**
	 * PayPal payment statuses.
	 */
	const STATUS_COMPLETED             = 'COMPLETED';
	const STATUS_APPROVED              = 'APPROVED';
	const STATUS_CAPTURED              = 'CAPTURED';
	const STATUS_AUTHORIZED            = 'AUTHORIZED';
	const STATUS_PAYER_ACTION_REQUIRED = 'PAYER_ACTION_REQUIRED';
	const VOIDED                       = 'VOIDED';

	/**
	 * PayPal payment intents.
	 */
	const INTENT_CAPTURE   = 'CAPTURE';
	const INTENT_AUTHORIZE = 'AUTHORIZE';

	/**
	 * PayPal payment actions.
	 */
	const PAYMENT_ACTION_CAPTURE   = 'capture';
	const PAYMENT_ACTION_AUTHORIZE = 'authorize';

	/**
	 * PayPal shipping preferences.
	 */
	const SHIPPING_NO_SHIPPING          = 'NO_SHIPPING';
	const SHIPPING_GET_FROM_FILE        = 'GET_FROM_FILE';
	const SHIPPING_SET_PROVIDED_ADDRESS = 'SET_PROVIDED_ADDRESS';

	/**
	 * PayPal user actions.
	 */
	const USER_ACTION_PAY_NOW = 'PAY_NOW';

	/**
	 * Maximum lengths for PayPal fields.
	 */
	const PAYPAL_ORDER_ITEM_NAME_MAX_LENGTH = 127;
	const PAYPAL_INVOICE_ID_MAX_LENGTH      = 127;
	const PAYPAL_ADDRESS_LINE_MAX_LENGTH    = 300;
	const PAYPAL_COUNTRY_CODE_LENGTH        = 2;
	const PAYPAL_STATE_MAX_LENGTH           = 300;
	const PAYPAL_CITY_MAX_LENGTH            = 120;
	const PAYPAL_POSTAL_CODE_MAX_LENGTH     = 60;
	const PAYPAL_LOCALE_MAX_LENGTH          = 10;

	/**
	 * Supported payment sources.
	 */
	const PAYMENT_SOURCE_PAYPAL     = 'paypal';
	const PAYMENT_SOURCE_VENMO      = 'venmo';
	const PAYMENT_SOURCE_PAYLATER   = 'paylater';
	const SUPPORTED_PAYMENT_SOURCES = array( self::PAYMENT_SOURCE_PAYPAL, self::PAYMENT_SOURCE_VENMO, self::PAYMENT_SOURCE_PAYLATER );

	/**
	 * Fields to redact from logs.
	 *
	 * @var array
	 */
	const FIELDS_TO_REDACT = array(
		'given_name',
		'surname',
		'full_name',
		'address_line_1',
		'address_line_2',
		'admin_area_1',
		'admin_area_2',
		'postal_code',
		'phone',
		'phone_number',
		'national_number',
	);

	/**
	 * List of currencies supported by PayPal (Orders API V2).
	 *
	 * @var array<string>
	 */
	const SUPPORTED_CURRENCIES = array(
		'AUD', // Australian Dollar.
		'BRL', // Brazilian Real.
		'CAD', // Canadian Dollar.
		'CNY', // Chinese Renminbi.
		'CZK', // Czech Koruna.
		'DKK', // Danish Krone.
		'EUR', // Euro.
		'HKD', // Hong Kong Dollar.
		'HUF', // Hungarian Forint.
		'ILS', // Israeli New Shekel.
		'JPY', // Japanese Yen.
		'MYR', // Malaysian Ringgit.
		'MXN', // Mexican Peso.
		'TWD', // New Taiwan Dollar.
		'NZD', // New Zealand Dollar.
		'NOK', // Norwegian Krone.
		'PHP', // Philippine Peso.
		'PLN', // Polish Złoty.
		'GBP', // Pound Sterling.
		'SGD', // Singapore Dollar.
		'SEK', // Swedish Krona.
		'CHF', // Swiss Franc.
		'THB', // Thai Baht.
		'USD', // United States Dollar.
		'RUB', // Russian Ruble.
	);

	/**
	 * PayPal authorization already captured issue code.
	 *
	 * @var string
	 */
	const PAYPAL_ISSUE_AUTHORIZATION_ALREADY_CAPTURED = 'AUTHORIZATION_ALREADY_CAPTURED';

	/**
	 * PayPal account locked or closed issue code.
	 *
	 * @var string
	 */
	const PAYPAL_ISSUE_PAYEE_ACCOUNT_LOCKED_OR_CLOSED = 'PAYEE_ACCOUNT_LOCKED_OR_CLOSED';

	/**
	 * PayPal account restricted issue code.
	 *
	 * @var string
	 */
	const PAYPAL_ISSUE_PAYEE_ACCOUNT_RESTRICTED = 'PAYEE_ACCOUNT_RESTRICTED';

	/**
	 * Meta key for storing PayPal payment status in order meta.
	 *
	 * @var string
	 *
	 * @since 10.5.0
	 */
	public const PAYPAL_ORDER_META_STATUS = '_paypal_status';

	/**
	 * Meta key for storing PayPal capture ID in order meta.
	 *
	 * @var string
	 *
	 * @since 10.5.0
	 */
	public const PAYPAL_ORDER_META_CAPTURE_ID = '_paypal_capture_id';

	/**
	 * Meta key for storing PayPal authorization ID in order meta.
	 *
	 * @var string
	 *
	 * @since 10.5.0
	 */
	public const PAYPAL_ORDER_META_AUTHORIZATION_ID = '_paypal_authorization_id';

	/**
	 * Meta key for storing PayPal authorization checked flag in order meta.
	 *
	 * @var string
	 *
	 * @since 10.5.0
	 */
	public const PAYPAL_ORDER_META_AUTHORIZATION_CHECKED = '_paypal_authorization_checked';

	/**
	 * Meta key for storing PayPal order ID in order meta.
	 *
	 * @var string
	 *
	 * @since 10.5.0
	 */
	public const PAYPAL_ORDER_META_ORDER_ID = '_paypal_order_id';

	/**
	 * Meta key for storing PayPal addresses updated flag in order meta.
	 *
	 * @var string
	 *
	 * @since 10.5.0
	 */
	public const PAYPAL_ORDER_META_ADDRESSES_UPDATED = '_paypal_addresses_updated';

	/**
	 * Meta key for storing PayPal payment source in order meta.
	 *
	 * @var string
	 *
	 * @since 10.5.0
	 */
	public const PAYPAL_ORDER_META_PAYMENT_SOURCE = '_paypal_payment_source';

	/**
	 * Meta key for storing PayPal shipping callback token in order meta.
	 *
	 * @var string
	 *
	 * @since 10.5.0
	 */
	public const PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN = '_paypal_shipping_callback_token';
}
