/**
 * Internal dependencies
 */
import type { FilterOptionItem } from '../types';
import { sortFilterOptions } from './sort-filter-options';

/**
 * Sorts taxonomy options with parents followed by their matching descendants.
 */
export function createHierarchicalList(
	terms: FilterOptionItem[],
	sortOrder: string
) {
	const children = new Map();
	const termIds = new Set( terms.map( ( term ) => term.termId ) );

	// First: categorize terms by parent (numeric WP term ID)
	terms.forEach( ( term ) => {
		const parentId =
			term.parent && termIds.has( term.parent ) ? term.parent : 0;
		if ( ! children.has( parentId ) ) {
			children.set( parentId, [] );
		}
		children.get( parentId ).push( term );
	} );

	// Next: sort them
	children.keys().forEach( ( key ) => {
		children.set(
			key,
			sortFilterOptions( children.get( key ), sortOrder )
		);
	} );

	// Last: build hierarchical list
	const result: FilterOptionItem[] = [];
	function addTermsRecursively(
		termList: FilterOptionItem[],
		depth = 0,
		visited = new Set< number >()
	) {
		if ( depth > 10 ) {
			return;
		}
		termList.forEach( ( term ) => {
			if ( ! term.termId || visited.has( term.termId ) ) {
				return;
			}
			visited.add( term.termId );
			result.push( { ...term, depth } );
			const termChildren = children.get( term.termId ) || [];
			if ( termChildren.length > 0 ) {
				addTermsRecursively( termChildren, depth + 1, visited );
			}
		} );
	}

	addTermsRecursively( children.get( 0 ) ?? [] );
	return result;
}
