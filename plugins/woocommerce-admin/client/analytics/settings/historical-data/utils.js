/** @format */
/**
 * External dependencies
 */
import moment from 'moment';

export const formatParams = ( period, skipChecked ) => {
	const params = {};
	if ( skipChecked ) {
		params.skip_existing = true;
	}
	if ( period.label !== 'all' ) {
		if ( period.label === 'custom' ) {
			const daysDifference = moment().diff( moment( period.date, this.dateFormat ), 'days', true );
			params.days = Math.ceil( daysDifference );
		} else {
			params.days = parseInt( period.label, 10 );
		}
	}

	return params;
};
