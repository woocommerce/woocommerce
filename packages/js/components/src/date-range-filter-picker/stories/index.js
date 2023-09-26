/**
 * External dependencies
 */
import { DateRangeFilterPicker } from '@woocommerce/components';
import {
	getDateParamsFromQuery,
	getCurrentDates,
	isoDateFormat,
} from '@woocommerce/date';

/**
 * External dependencies
 */
import { partialRight } from 'lodash';

const query = {};

const defaultDateRange = 'period=month&compare=previous_year';
const storeGetDateParamsFromQuery = partialRight(
	getDateParamsFromQuery,
	defaultDateRange
);
const storeGetCurrentDates = partialRight( getCurrentDates, defaultDateRange );
const { period, compare, before, after } = storeGetDateParamsFromQuery( query );
const { primary: primaryDate, secondary: secondaryDate } =
	storeGetCurrentDates( query );
const dateQuery = {
	period,
	compare,
	before,
	after,
	primaryDate,
	secondaryDate,
};

export const Basic = () => (
	<DateRangeFilterPicker
		key="daterange"
		query={ query }
		onRangeSelect={ () => {} }
		dateQuery={ dateQuery }
		isoDateFormat={ isoDateFormat }
	/>
);

export default {
	title: 'WooCommerce Admin/components/DateRangeFilterPicker',
	component: DateRangeFilterPicker,
};
