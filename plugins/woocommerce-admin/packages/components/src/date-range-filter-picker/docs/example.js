/** @format */
/**
 * Internal dependencies
 */
import { DateRangeFilterPicker } from '@woocommerce/components';

const query = {};

export default () => (
	<DateRangeFilterPicker
		key="daterange"
		query={ query }
		onRangeSelect={ () => {} }
	/>
);
