/** @format */
/**
 * Internal dependencies
 */
import { DateRangeFilterPicker } from '@woocommerce/components';
import {
	getDateParamsFromQuery,
	getCurrentDates,
	isoDateFormat,
	loadLocaleData,
} from '@woocommerce/date';

/**
 * External dependencies
 */
import { partialRight } from 'lodash';

const query = {};

// Fetch locale from store settings and load for date functions.
const localeSettings = {
	userLocale: 'fr_FR',
	weekdaysShort: [ 'dim', 'lun', 'mar', 'mer', 'jeu', 'ven', 'sam' ],
};
loadLocaleData( localeSettings );

const defaultDateRange = 'period=month&compare=previous_year';
const storeGetDateParamsFromQuery = partialRight( getDateParamsFromQuery, defaultDateRange );
const storeGetCurrentDates = partialRight( getCurrentDates, defaultDateRange );
const { period, compare, before, after } = storeGetDateParamsFromQuery( query );
const { primary: primaryDate, secondary: secondaryDate } = storeGetCurrentDates( query );
const dateQuery = {
	period,
	compare,
	before,
	after,
	primaryDate,
	secondaryDate,
};

export default () => (
	<DateRangeFilterPicker
		key="daterange"
		query={ query }
		onRangeSelect={ () => {} }
		dateQuery={ dateQuery }
		isoDateFormat={ isoDateFormat }
	/>
);
