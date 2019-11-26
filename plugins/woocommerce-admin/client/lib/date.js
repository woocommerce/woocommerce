/** @format */
/**
 * External dependencies
 */
import { partialRight } from 'lodash';

/**
 * Internal dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * WooCommerce dependencies
 */
import {
	isoDateFormat,
	presetValues,
	periods,
	appendTimestamp,
	toMoment,
	getRangeLabel,
	getLastPeriod,
	getCurrentPeriod,
	getDateParamsFromQuery,
	getCurrentDates,
	getDateDifferenceInDays,
	getPreviousDate,
	getAllowedIntervalsForQuery,
	getIntervalForQuery,
	getChartTypeForQuery,
	dayTicksThreshold,
	weekTicksThreshold,
	defaultTableDateFormat,
	getDateFormatsForInterval,
	loadLocaleData,
	dateValidationMessages,
	validateDateInputForRange,
} from '@woocommerce/date';

// Load the store's locale.
const localeSettings = getSetting( 'locale' );
loadLocaleData( localeSettings );

// Compose methods with store settings.
const {
	woocommerce_default_date_range: defaultDateRange = 'period=month&compare=previous_year',
} = getSetting( 'wcAdminSettings', {} );
const storeGetDateParamsFromQuery = partialRight( getDateParamsFromQuery, defaultDateRange );
const storeGetCurrentDates = partialRight( getCurrentDates, defaultDateRange );

// Export the expected API for the consuming app.
export {
	isoDateFormat,
	presetValues,
	periods,
	appendTimestamp,
	toMoment,
	getRangeLabel,
	getLastPeriod,
	getCurrentPeriod,
	storeGetDateParamsFromQuery as getDateParamsFromQuery,
	storeGetCurrentDates as getCurrentDates,
	getDateDifferenceInDays,
	getPreviousDate,
	getAllowedIntervalsForQuery,
	getIntervalForQuery,
	getChartTypeForQuery,
	dayTicksThreshold,
	weekTicksThreshold,
	defaultTableDateFormat,
	getDateFormatsForInterval,
	loadLocaleData,
	dateValidationMessages,
	validateDateInputForRange,
};
