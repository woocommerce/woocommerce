/** @format */
/**
 * External dependencies
 */
import moment from 'moment';

export function getOutsideRange( invalidDays ) {
	if ( 'string' === typeof invalidDays ) {
		switch ( invalidDays ) {
			case 'past':
				return day => moment().isAfter( day, 'day' );
			case 'future':
				return day => moment().isBefore( day, 'day' );
			case 'none':
			default:
				return undefined;
		}
	}
	return 'function' === typeof invalidDays ? invalidDays : undefined;
}
