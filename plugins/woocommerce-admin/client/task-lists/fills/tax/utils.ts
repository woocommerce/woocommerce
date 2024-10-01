/**
 * External dependencies
 */
import { getAdminLink } from '@woocommerce/settings';
import { TaskType } from '@woocommerce/data';

/**
 * Plugins required to automate taxes.
 */
export const AUTOMATION_PLUGINS = [ 'woocommerce-services' ];

/**
 * Check if a store has a complete address given general settings.
 *
 * @param {Object} generalSettings                             General settings.
 * @param {Object} generalSettings.woocommerce_store_address   Store address.
 * @param {Object} generalSettings.woocommerce_default_country Store default country.
 * @param {Object} generalSettings.woocommerce_store_postcode  Store postal code.
 */
export const hasCompleteAddress = (
	generalSettings: Record< string, string >,
	requiresPostcode = true
): boolean => {
	const {
		woocommerce_store_address: storeAddress,
		woocommerce_default_country: defaultCountry,
		woocommerce_store_postcode: storePostCode,
	} = generalSettings;
	if ( requiresPostcode ) {
		return Boolean( storeAddress && defaultCountry && storePostCode );
	}
	return Boolean( storeAddress && defaultCountry );
};

/**
 * Redirect to the core tax settings screen.
 */
export const redirectToTaxSettings = (): void => {
	window.location.href = getAdminLink(
		'admin.php?page=wc-settings&tab=tax&section=standard&wc_onboarding_active_task=tax'
	);
};

/**
 * Types for child tax components.
 */
export type TaxChildProps = {
	isPending: boolean;
	onAutomate: () => void;
	onManual: () => void;
	onDisable: () => void;
	task: TaskType;
};

/**
 * Check if a given country is supported by Avalara.
 *
 * @param {string} countryCode Country code.
 * @return {boolean} If the country is supported.
 */
export const supportsAvalara = ( countryCode: string ): boolean => {
	const countries = [
		'AF',
		'AL',
		'DZ',
		'AD',
		'AO',
		'AI',
		'AG',
		'AR',
		'AM',
		'AW',
		'AU',
		'AT',
		'AZ',
		'BS',
		'BH',
		'BD',
		'BB',
		'BY',
		'BE',
		'BZ',
		'BJ',
		'BM',
		'BO',
		'BA',
		'BW',
		'BR',
		'BN',
		'BG',
		'BF',
		'BI',
		'KH',
		'CM',
		'CA',
		'IC',
		'CV',
		'KY',
		'CF',
		'TD',
		'CL',
		'CN',
		'CC',
		'CO',
		'KM',
		'CD',
		'CK',
		'CR',
		'CI',
		'HR',
		'CU',
		'CW',
		'CY',
		'CZ',
		'DK',
		'DJ',
		'DM',
		'DO',
		'EC',
		'EG',
		'SV',
		'GQ',
		'ER',
		'EE',
		'ET',
		'FK',
		'FO',
		'FJ',
		'FI',
		'FR',
		'PF',
		'TF',
		'GA',
		'GM',
		'GE',
		'DE',
		'GH',
		'GI',
		'GR',
		'GL',
		'GD',
		'GP',
		'GT',
		'GG',
		'GN',
		'GW',
		'GY',
		'HT',
		'HN',
		'HK',
		'HU',
		'IS',
		'IN',
		'ID',
		'IR',
		'IQ',
		'IE',
		'IL',
		'IT',
		'JM',
		'JP',
		'JE',
		'JO',
		'KZ',
		'KE',
		'KI',
		'KP',
		'KV',
		'KW',
		'KG',
		'LA',
		'LV',
		'LB',
		'LS',
		'LR',
		'LY',
		'LI',
		'LT',
		'LU',
		'MO',
		'MK',
		'MG',
		'MW',
		'MY',
		'MV',
		'ML',
		'MT',
		'MQ',
		'MR',
		'MU',
		'MX',
		'MD',
		'MC',
		'MN',
		'ME',
		'MS',
		'MA',
		'MZ',
		'MM',
		'NA',
		'NR',
		'NP',
		'NL',
		'NZ',
		'NI',
		'NE',
		'NG',
		'NU',
		'NF',
		'NO',
		'OM',
		'PK',
		'PS',
		'PA',
		'PG',
		'PY',
		'PE',
		'PH',
		'PL',
		'PT',
		'QA',
		'KR',
		'RE',
		'RO',
		'RU',
		'RW',
		'SH',
		'KN',
		'LC',
		'MF',
		'VC',
		'WS',
		'SM',
		'ST',
		'SA',
		'SN',
		'RS',
		'SC',
		'SL',
		'SG',
		'SX',
		'SK',
		'SI',
		'SB',
		'SO',
		'ZA',
		'SD',
		'ES',
		'LK',
		'SD',
		'SR',
		'SZ',
		'SE',
		'CH',
		'SY',
		'TW',
		'TJ',
		'TZ',
		'TH',
		'TL',
		'TG',
		'TO',
		'TT',
		'TN',
		'TR',
		'TM',
		'TC',
		'TV',
		'UG',
		'UA',
		'AE',
		'GB',
		'US',
		'UY',
		'UZ',
		'VU',
		'VE',
		'VN',
		'VG',
		'YE',
		'ZM',
		'ZW',
	];

	return countries.includes( countryCode );
};
