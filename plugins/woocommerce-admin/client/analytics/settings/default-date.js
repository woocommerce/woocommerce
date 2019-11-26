/** @format */
/**
 * External dependencies
 */
import { parse, stringify } from 'qs';

/**
 * Internal dependencies
 */
import { getCurrentDates, getDateParamsFromQuery, isoDateFormat } from 'lib/date';

/**
 * WooCommerce dependencies
 */
import { DateRangeFilterPicker } from '@woocommerce/components';

const DefaultDate = ( { value, onChange } ) => {
	const change = query => {
		onChange( {
			target: {
				name: 'woocommerce_default_date_range',
				value: stringify( query ),
			},
		} );
	};
	const query = parse( value.replace( /&amp;/g, '&' ) );
	const { period, compare, before, after } = getDateParamsFromQuery( query );
	const { primary: primaryDate, secondary: secondaryDate } = getCurrentDates( query );
	const dateQuery = {
		period,
		compare,
		before,
		after,
		primaryDate,
		secondaryDate,
	};
	return (
		<DateRangeFilterPicker
			query={ query }
			onRangeSelect={ change }
			dateQuery={ dateQuery }
			isoDateFormat={ isoDateFormat }
		/>
	);
};

export default DefaultDate;
