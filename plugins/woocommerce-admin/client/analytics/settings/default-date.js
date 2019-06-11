/** @format */
/**
 * External dependencies
 */
import { parse, stringify } from 'qs';
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
	return <DateRangeFilterPicker query={ query } onRangeSelect={ change } />;
};

export default DefaultDate;
