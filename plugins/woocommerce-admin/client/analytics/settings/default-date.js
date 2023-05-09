/**
 * External dependencies
 */
import { DateRangeFilterPicker } from '@woocommerce/components';
import { useSettings } from '@woocommerce/data';
import {
	getCurrentDates,
	getDateParamsFromQuery,
	isoDateFormat,
} from '@woocommerce/date';

const DefaultDate = ( { value, onChange } ) => {
	const { wcAdminSettings } = useSettings( 'wc_admin', [
		'wcAdminSettings',
	] );
	const { woocommerce_default_date_range: defaultDateRange } =
		wcAdminSettings;
	const change = ( query ) => {
		onChange( {
			target: {
				name: 'woocommerce_default_date_range',
				value: new URLSearchParams( query ).toString(),
			},
		} );
	};
	const query = Object.fromEntries(
		new URLSearchParams( value.replace( /&amp;/g, '&' ) )
	);
	const { period, compare, before, after } = getDateParamsFromQuery(
		query,
		defaultDateRange
	);
	const { primary: primaryDate, secondary: secondaryDate } = getCurrentDates(
		query,
		defaultDateRange
	);
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
