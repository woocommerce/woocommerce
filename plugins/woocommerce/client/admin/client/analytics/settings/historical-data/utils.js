/**
 * External dependencies
 */
import { isNil } from 'lodash';
import moment from 'moment';

export const formatParams = ( dateFormat, period, skipChecked ) => {
	const params = {};
	if ( skipChecked ) {
		params.skip_existing = true;
	}
	if ( period.label !== 'all' ) {
		if ( period.label === 'custom' ) {
			const daysDifference = moment().diff(
				moment( period.date, dateFormat ),
				'days',
				true
			);
			params.days = Math.floor( daysDifference );
		} else {
			params.days = parseInt( period.label, 10 );
		}
	}

	return params;
};

export const getStatus = ( {
	cacheNeedsClearing,
	customersProgress,
	customersTotal,
	isError,
	inProgress,
	ordersProgress,
	ordersTotal,
} ) => {
	if ( isError ) {
		return 'error';
	}
	if ( inProgress ) {
		if (
			isNil( customersProgress ) ||
			isNil( ordersProgress ) ||
			isNil( customersTotal ) ||
			isNil( ordersTotal ) ||
			cacheNeedsClearing
		) {
			return 'initializing';
		}
		if ( customersProgress < customersTotal ) {
			return 'customers';
		}
		if ( ordersProgress < ordersTotal ) {
			return 'orders';
		}
		return 'finalizing';
	}
	if ( customersTotal > 0 || ordersTotal > 0 ) {
		if (
			customersProgress === customersTotal &&
			ordersProgress === ordersTotal
		) {
			return 'finished';
		}
		return 'ready';
	}
	return 'nothing';
};
