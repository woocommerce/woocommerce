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
	 * Countries supported by PayPal.
	 * https://developer.paypal.com/reference/country-codes/
	 *
	 * @var array<string, string>
	 */
	const SUPPORTED_COUNTRIES = array(
		'AL' => 'Albania',
		'DZ' => 'Algeria',
		'AD' => 'Andorra',
		'AO' => 'Angola',
		'AI' => 'Anguilla',
		'AG' => 'Antigua & Barbuda',
		'AR' => 'Argentina',
		'AM' => 'Armenia',
		'AW' => 'Aruba',
		'AU' => 'Australia',
		'AT' => 'Austria',
		'AZ' => 'Azerbaijan',
		'BS' => 'Bahamas',
		'BH' => 'Bahrain',
		'BB' => 'Barbados',
		'BY' => 'Belarus',
		'BE' => 'Belgium',
		'BZ' => 'Belize',
		'BJ' => 'Benin',
		'BM' => 'Bermuda',
		'BT' => 'Bhutan',
		'BO' => 'Bolivia',
		'BA' => 'Bosnia & Herzegovina',
		'BW' => 'Botswana',
		'BR' => 'Brazil',
		'VG' => 'British Virgin Islands',
		'BN' => 'Brunei',
		'BG' => 'Bulgaria',
		'BF' => 'Burkina Faso',
		'BI' => 'Burundi',
		'KH' => 'Cambodia',
		'CM' => 'Cameroon',
		'CA' => 'Canada',
		'CV' => 'Cape Verde',
		'KY' => 'Cayman Islands',
		'TD' => 'Chad',
		'CL' => 'Chile',
		'CN' => 'China',
		'CO' => 'Colombia',
		'KM' => 'Comoros',
		'CG' => 'Congo - Brazzaville',
		'CD' => 'Congo - Kinshasa',
		'CK' => 'Cook Islands',
		'CR' => 'Costa Rica',
		'CI' => 'Côte d\'Ivoire',
		'HR' => 'Croatia',
		'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		'DK' => 'Denmark',
		'DJ' => 'Djibouti',
		'DM' => 'Dominica',
		'DO' => 'Dominican Republic',
		'EC' => 'Ecuador',
		'EG' => 'Egypt',
		'SV' => 'El Salvador',
		'ER' => 'Eritrea',
		'EE' => 'Estonia',
		'ET' => 'Ethiopia',
		'FK' => 'Falkland Islands',
		'FO' => 'Faroe Islands',
		'FJ' => 'Fiji',
		'FI' => 'Finland',
		'FR' => 'France',
		'GF' => 'French Guiana',
		'PF' => 'French Polynesia',
		'GA' => 'Gabon',
		'GM' => 'Gambia',
		'GE' => 'Georgia',
		'DE' => 'Germany',
		'GI' => 'Gibraltar',
		'GR' => 'Greece',
		'GL' => 'Greenland',
		'GD' => 'Grenada',
		'GP' => 'Guadeloupe',
		'GT' => 'Guatemala',
		'GN' => 'Guinea',
		'GW' => 'Guinea-Bissau',
		'GY' => 'Guyana',
		'HN' => 'Honduras',
		'HK' => 'Hong Kong SAR China',
		'HU' => 'Hungary',
		'IS' => 'Iceland',
		'IN' => 'India',
		'ID' => 'Indonesia',
		'IE' => 'Ireland',
		'IL' => 'Israel',
		'IT' => 'Italy',
		'JM' => 'Jamaica',
		'JP' => 'Japan',
		'JO' => 'Jordan',
		'KZ' => 'Kazakhstan',
		'KE' => 'Kenya',
		'KI' => 'Kiribati',
		'KW' => 'Kuwait',
		'KG' => 'Kyrgyzstan',
		'LA' => 'Laos',
		'LV' => 'Latvia',
		'LS' => 'Lesotho',
		'LI' => 'Liechtenstein',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'MK' => 'Macedonia',
		'MG' => 'Madagascar',
		'MW' => 'Malawi',
		'MY' => 'Malaysia',
		'MV' => 'Maldives',
		'ML' => 'Mali',
		'MT' => 'Malta',
		'MH' => 'Marshall Islands',
		'MQ' => 'Martinique',
		'MR' => 'Mauritania',
		'MU' => 'Mauritius',
		'YT' => 'Mayotte',
		'MX' => 'Mexico',
		'FM' => 'Micronesia',
		'MD' => 'Moldova',
		'MC' => 'Monaco',
		'MN' => 'Mongolia',
		'ME' => 'Montenegro',
		'MS' => 'Montserrat',
		'MA' => 'Morocco',
		'MZ' => 'Mozambique',
		'NA' => 'Namibia',
		'NR' => 'Nauru',
		'NP' => 'Nepal',
		'NL' => 'Netherlands',
		'NC' => 'New Caledonia',
		'NZ' => 'New Zealand',
		'NI' => 'Nicaragua',
		'NE' => 'Niger',
		'NG' => 'Nigeria',
		'NU' => 'Niue',
		'NF' => 'Norfolk Island',
		'NO' => 'Norway',
		'OM' => 'Oman',
		'PW' => 'Palau',
		'PA' => 'Panama',
		'PG' => 'Papua New Guinea',
		'PY' => 'Paraguay',
		'PE' => 'Peru',
		'PH' => 'Philippines',
		'PN' => 'Pitcairn Islands',
		'PL' => 'Poland',
		'PT' => 'Portugal',
		'QA' => 'Qatar',
		'RE' => 'Réunion',
		'RO' => 'Romania',
		'RU' => 'Russia',
		'RW' => 'Rwanda',
		'WS' => 'Samoa',
		'SM' => 'San Marino',
		'ST' => 'São Tomé & Príncipe',
		'SA' => 'Saudi Arabia',
		'SN' => 'Senegal',
		'RS' => 'Serbia',
		'SC' => 'Seychelles',
		'SL' => 'Sierra Leone',
		'SG' => 'Singapore',
		'SK' => 'Slovakia',
		'SI' => 'Slovenia',
		'SB' => 'Solomon Islands',
		'SO' => 'Somalia',
		'ZA' => 'South Africa',
		'KR' => 'South Korea',
		'ES' => 'Spain',
		'LK' => 'Sri Lanka',
		'SH' => 'St. Helena',
		'KN' => 'St. Kitts & Nevis',
		'LC' => 'St. Lucia',
		'PM' => 'St. Pierre & Miquelon',
		'VC' => 'St. Vincent & Grenadines',
		'SR' => 'Suriname',
		'SJ' => 'Svalbard & Jan Mayen',
		'SZ' => 'Swaziland',
		'SE' => 'Sweden',
		'CH' => 'Switzerland',
		'TW' => 'Taiwan',
		'TJ' => 'Tajikistan',
		'TZ' => 'Tanzania',
		'TH' => 'Thailand',
		'TG' => 'Togo',
		'TO' => 'Tonga',
		'TT' => 'Trinidad & Tobago',
		'TN' => 'Tunisia',
		'TM' => 'Turkmenistan',
		'TC' => 'Turks & Caicos Islands',
		'TV' => 'Tuvalu',
		'UG' => 'Uganda',
		'UA' => 'Ukraine',
		'AE' => 'United Arab Emirates',
		'GB' => 'United Kingdom',
		'US' => 'United States',
		'UY' => 'Uruguay',
		'VU' => 'Vanuatu',
		'VA' => 'Vatican City',
		'VE' => 'Venezuela',
		'VN' => 'Vietnam',
		'WF' => 'Wallis & Futuna',
		'YE' => 'Yemen',
		'ZM' => 'Zambia',
		'ZW' => 'Zimbabwe',
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
	 * PayPal duplicate invoice ID issue code.
	 *
	 * @var string
	 */
	const PAYPAL_ISSUE_DUPLICATE_INVOICE_ID = 'DUPLICATE_INVOICE_ID';

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
