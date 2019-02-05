/** @format */

/**
 * External dependencies
 */
import { find, get } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { flattenFilters } from '@woocommerce/navigation';

export const DEFAULT_FILTER = 'all';

export function getSelectedFilter( filters, query, selectedFilterArgs = {} ) {
	if ( filters.length === 0 ) {
		return null;
	}

	const filterConfig = filters.pop();

	if ( filterConfig.showFilters( query, selectedFilterArgs ) ) {
		const allFilters = flattenFilters( filterConfig.filters );
		const value = query[ filterConfig.param ] || DEFAULT_FILTER;
		const selectedFilter = find( allFilters, { value } );
		const selectedFilterParam = get( selectedFilter, [ 'settings', 'param' ] );

		if ( ! selectedFilterParam || Object.keys( query ).includes( selectedFilterParam ) ) {
			return selectedFilter;
		}
	}

	return getSelectedFilter( filters, query, selectedFilterArgs );
}

export function getChartMode( filters, query, selectedFilterArgs ) {
	if ( ! filters ) {
		return;
	}
	const clonedFilters = filters.slice( 0 );
	const selectedFilter = getSelectedFilter( clonedFilters, query, selectedFilterArgs );

	return get( selectedFilter, [ 'chartMode' ] );
}
