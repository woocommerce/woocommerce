/**
 * External dependencies
 */
import { find, get } from 'lodash';
import { flattenFilters } from '@woocommerce/navigation';
import { format as formatDate } from '@wordpress/date';
import { containsLeapYear } from '@woocommerce/data';

export const DEFAULT_FILTER = 'all';

export function getSelectedFilter( filters, query, selectedFilterArgs = {} ) {
	if ( ! filters || filters.length === 0 ) {
		return null;
	}

	const clonedFilters = filters.slice( 0 );
	const filterConfig = clonedFilters.pop();

	if ( filterConfig.showFilters( query, selectedFilterArgs ) ) {
		const allFilters = flattenFilters( filterConfig.filters );
		const value =
			query[ filterConfig.param ] ||
			filterConfig.defaultValue ||
			DEFAULT_FILTER;
		return find( allFilters, { value } );
	}

	return getSelectedFilter( clonedFilters, query, selectedFilterArgs );
}

export function getChartMode( selectedFilter, query ) {
	if ( selectedFilter && query ) {
		const selectedFilterParam = get( selectedFilter, [
			'settings',
			'param',
		] );

		if (
			! selectedFilterParam ||
			Object.keys( query ).includes( selectedFilterParam )
		) {
			return get( selectedFilter, [ 'chartMode' ] );
		}
	}

	return null;
}

export function createDateFormatter( format ) {
	return ( date ) => formatDate( format, date );
}

export function dataHasLeapYear( data ) {
	if ( data.data.intervals.length > 1 ) {
		const start = data.data.intervals[ 0 ].date_start;
		const end =
			data.data.intervals[ data.data.intervals.length - 1 ].date_end;

		if ( containsLeapYear( start, end ) ) {
			return true;
		}
	}
	return false;
}
