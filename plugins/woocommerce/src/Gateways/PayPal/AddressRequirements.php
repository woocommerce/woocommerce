<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Gateways\PayPal;

/**
 * Class AddressRequirements
 *
 * This helper class checks country-specific address requirements.
 * This was built based on https://developer.paypal.com/api/rest/reference/orders/v2/country-address-requirements/
 */
class AddressRequirements {
	/**
	 * Countries that require a city in the address.
	 *
	 * @var array
	 */
	private const COUNTRIES_REQUIRING_CITY = [
		'AF', 'AX', 'AL', 'DZ', 'AS', 'AD', 'AO', 'AI', 'AQ', 'AG',
		'AR', 'AM', 'AW', 'AU', 'AT', 'AZ', 'BS', 'BH', 'BD', 'BB',
		'BY', 'BE', 'BZ', 'BJ', 'BM', 'BT', 'BO', 'BA', 'BW', 'BV',
		'BR', 'IO', 'BN', 'BG', 'BF', 'BI', 'KH', 'CM', 'CA', 'CV',
		'KY', 'CF', 'TD', 'CL', 'C2', 'CN', 'CX', 'CC', 'CO', 'KM',
		'CD', 'ZR', 'CG', 'CK', 'CR', 'CI', 'HR', 'CU', 'CY', 'CZ',
		'DK', 'DJ', 'DM', 'DO', 'TP', 'EC', 'EG', 'SV', 'GQ', 'ER',
		'EE', 'ET', 'FK', 'FO', 'FM', 'FJ', 'FI', 'FR', 'GF', 'PF',
		'TF', 'GA', 'GM', 'GE', 'DE', 'GH', 'GI', 'GR', 'GL', 'GD',
		'GP', 'GU', 'GT', 'GG', 'GN', 'GW', 'GY', 'HT', 'HM', 'HN',
		'HK', 'HU', 'IS', 'IN', 'ID', 'IR', 'IQ', 'IE', 'IM', 'IL',
		'IT', 'JM', 'JP', 'JE', 'JO', 'KZ', 'KE', 'KI', 'KW', 'KG',
		'LA', 'LV', 'LB', 'LS', 'LR', 'LY', 'LI', 'LT', 'LU', 'MO',
		'MK', 'MG', 'MW', 'MY', 'MV', 'ML', 'MT', 'MH', 'MQ', 'MR',
		'MU', 'YT', 'MX', 'MD', 'MC', 'MN', 'ME', 'MS', 'MA', 'MZ',
		'MM', 'NA', 'NR', 'NP', 'NL', 'AN', 'NC', 'NZ', 'NI', 'NE',
		'NG', 'NU', 'NF', 'MP', 'NO', 'OM', 'PK', 'PW', 'PS', 'PA',
		'PG', 'PY', 'PE', 'PH', 'PN', 'PL', 'PT', 'PR', 'QA', 'RE',
		'RO', 'RU', 'RW', 'WS', 'SM', 'ST', 'SA', 'SN', 'RS', 'CS',
		'SC', 'SL', 'SG', 'SK', 'SI', 'SB', 'SO', 'ZA', 'GS', 'KR',
		'ES', 'LK', 'SH', 'KN', 'LC', 'PM', 'VC', 'SD', 'SR', 'SJ',
		'SZ', 'SE', 'CH', 'SY', 'TW', 'TJ', 'TZ', 'TH', 'TL', 'TG',
		'TK', 'TO', 'TT', 'TN', 'TR', 'TM', 'TC', 'TV', 'UG', 'UA',
		'AE', 'GB', 'US', 'UY', 'UM', 'UZ', 'VU', 'VA', 'VE', 'VN',
		'VG', 'VI', 'WF', 'EH', 'YE', 'YU', 'ZM', 'ZW',
	];

	/**
	 * Countries that require a postal code in the address.
	 *
	 * @var array
	 */
	private const COUNTRIES_REQUIRING_POSTAL_CODE = [
		'AR', 'AU', 'AT', 'BT', 'BR', 'CA', 'C2', 'CN', 'CC', 'KM',
		'DK', 'FK', 'FO', 'FR', 'TF', 'GM', 'DE', 'GL', 'IT', 'JP',
		'KI', 'KG', 'MR', 'YT', 'MX', 'NR', 'NL', 'NE', 'NU', 'NF',
		'NO', 'PN', 'PL', 'SM', 'SG', 'ES', 'SH', 'PM', 'SR', 'SJ',
		'SE', 'CH', 'TH', 'TK', 'TV', 'GB', 'US', 'UM', 'VA', 'WF',
		'EH',
	];

	/**
	 * Check if a country requires a city in the address.
	 *
	 * @param string $country_code The ISO 3166-1 alpha-2 country code.
	 * @return bool
	 */
	public static function country_requires_city( $country_code ) {
		return in_array( strtoupper( $country_code ), self::COUNTRIES_REQUIRING_CITY, true );
	}

	/**
	 * Check if a country requires a postal code in the address.
	 *
	 * @param string $country_code The ISO 3166-1 alpha-2 country code.
	 * @return bool
	 */
	public static function country_requires_postal_code( $country_code ) {
		return in_array( strtoupper( $country_code ), self::COUNTRIES_REQUIRING_POSTAL_CODE, true );
	}
}
