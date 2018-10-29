/** @format */
/**
 * External dependencies
 */
import { omit } from 'lodash';

export function flatenFilters( filters ) {
	const allFilters = [];
	filters.forEach( f => {
		if ( ! f.subFilters ) {
			allFilters.push( f );
		} else {
			allFilters.push( omit( f, 'subFilters' ) );
			const subFilters = flatenFilters( f.subFilters );
			allFilters.push( ...subFilters );
		}
	} );
	return allFilters;
}
