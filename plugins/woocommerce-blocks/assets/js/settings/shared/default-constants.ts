/**
 * External dependencies
 */
import type { Currency, SymbolPosition } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { allSettings } from './settings-init';
import { getCurrencyPrefix, getCurrencySuffix } from './utils';

/**
 * This exports all default core settings as constants.
 */
export const ADMIN_URL = allSettings.adminUrl;
export const COUNTRIES = allSettings.countries;
export const CURRENT_USER_IS_ADMIN = allSettings.currentUserIsAdmin as boolean;
export const HOME_URL = allSettings.homeUrl as string | undefined;
export const LOCALE = allSettings.locale;
export const ORDER_STATUSES = allSettings.orderStatuses;
export const PLACEHOLDER_IMG_SRC = allSettings.placeholderImgSrc as string;
export const SITE_TITLE = allSettings.siteTitle;
export const STORE_PAGES = allSettings.storePages as Record<
	string,
	{
		id: 0;
		title: '';
		permalink: '';
	}
>;
export const WC_ASSET_URL = allSettings.wcAssetUrl;
export const WC_VERSION = allSettings.wcVersion;
export const WP_LOGIN_URL = allSettings.wpLoginUrl;
export const WP_VERSION = allSettings.wpVersion;

// Settings from the server in WooCommerceSiteCurrency format.
export const CURRENCY = allSettings.currency;
// Convert WooCommerceSiteCurrency format to Currency format.
export const SITE_CURRENCY: Currency = {
	code: CURRENCY.code,
	symbol: CURRENCY.symbol,
	thousandSeparator: CURRENCY.thousandSeparator,
	decimalSeparator: CURRENCY.decimalSeparator,
	minorUnit: CURRENCY.precision,
	prefix: getCurrencyPrefix(
		CURRENCY.symbol,
		CURRENCY.symbolPosition as SymbolPosition
	),
	suffix: getCurrencySuffix(
		CURRENCY.symbol,
		CURRENCY.symbolPosition as SymbolPosition
	),
};
