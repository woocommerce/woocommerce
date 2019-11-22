/**
 * External dependencies
 *
 * @format
 */

import * as SHARED from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import * as FALLBACKS from './fallbacks';

// If `getSetting` is not set, then it was not available so let's do
// defaults.
const SOURCE = ! SHARED || typeof SHARED.getSetting === 'undefined' ? FALLBACKS : SHARED;

export const ADMIN_URL = SOURCE.ADMIN_URL;
export const COUNTRIES = SOURCE.COUNTRIES;
export const CURRENCY = SOURCE.CURRENCY;
export const LOCALE = SOURCE.LOCALE;
export const ORDER_STATUSES = SOURCE.ORDER_STATUSES;
export const SITE_TITLE = SOURCE.SITE_TITLE;
export const WC_ASSET_URL = SOURCE.WC_ASSET_URL;
export const DEFAULT_DATE_RANGE = SOURCE.DEFAULT_DATE_RANGE;

export const getSetting = SOURCE.getSetting;
export const setSetting = SOURCE.setSetting;
export const getAdminLink = SOURCE.getAdminLink;
