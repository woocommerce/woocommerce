/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { resolveSelect } from '@wordpress/data';
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import type { Item } from './types';

const DEBOUNCE_MS = 300;
const SEARCH_PER_PAGE = 20;

function isTermArray(
	value: unknown
): value is Array< { id: number; name: string } > {
	return (
		Array.isArray( value ) &&
		value.every( ( item: unknown ) => {
			if ( typeof item !== 'object' || item === null ) {
				return false;
			}
			return (
				'id' in item &&
				typeof ( item as { id: unknown } ).id === 'number' &&
				'name' in item &&
				typeof ( item as { name: unknown } ).name === 'string'
			);
		} )
	);
}

function termsToItems( terms: unknown ): Item[] {
	if ( ! isTermArray( terms ) ) {
		return [];
	}
	return terms.map( ( { id, name } ) => ( {
		value: id.toString(),
		label: decodeEntities( name ),
	} ) );
}

/**
 * Hook that adaptively fetches taxonomy terms. Uses a provided term count
 * to decide between client-side filtering (all terms loaded) and
 * server-side search (debounced REST API queries).
 *
 * @param root0            Hook parameters.
 * @param root0.taxonomy   The taxonomy slug.
 * @param root0.inputValue Current search input value.
 * @param root0.knownItems Items to always include in results (selected/created).
 * @param root0.threshold  Max terms for client-side mode. Undefined = always client-side.
 * @param root0.termCount  Known total term count (e.g. from hydrated boot data).
 */
export function useAdaptiveTaxonomy( {
	taxonomy,
	inputValue,
	knownItems,
	threshold,
	termCount,
}: {
	taxonomy: string;
	inputValue: string;
	knownItems: Item[];
	threshold?: number;
	termCount?: number;
} ) {
	const isServerSearch =
		threshold !== undefined &&
		termCount !== undefined &&
		termCount > threshold;

	const [ allTerms, setAllTerms ] = useState< Item[] >( [] );
	const [ searchResults, setSearchResults ] = useState< Item[] >( [] );
	const [ isLoading, setIsLoading ] = useState( ! isServerSearch );
	const [ isSearching, setIsSearching ] = useState( false );
	const requestIdRef = useRef( 0 );

	// Client-side mode: fetch all terms once.
	useEffect( () => {
		if ( isServerSearch ) {
			return;
		}

		let cancelled = false;
		setIsLoading( true );

		resolveSelect( coreStore )
			.getEntityRecords( 'taxonomy', taxonomy, { per_page: -1 } )
			.then( ( records: unknown ) => {
				if ( ! cancelled ) {
					setAllTerms( termsToItems( records ) );
				}
			} )
			.catch( () => {
				// Silently fail.
			} )
			.finally( () => {
				if ( ! cancelled ) {
					setIsLoading( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ taxonomy, isServerSearch ] );

	// Server-side mode: debounced search on input change.
	useEffect( () => {
		if ( ! isServerSearch ) {
			return;
		}

		const query = inputValue.trim();
		if ( ! query ) {
			setSearchResults( [] );
			setIsSearching( false );
			return;
		}

		setIsSearching( true );
		requestIdRef.current += 1;
		const currentRequestId = requestIdRef.current;

		const timer = setTimeout( () => {
			resolveSelect( coreStore )
				.getEntityRecords( 'taxonomy', taxonomy, {
					search: query,
					per_page: SEARCH_PER_PAGE,
				} )
				.then( ( records: unknown ) => {
					if ( currentRequestId === requestIdRef.current ) {
						setSearchResults( termsToItems( records ) );
						setIsSearching( false );
					}
				} )
				.catch( () => {
					if ( currentRequestId === requestIdRef.current ) {
						setIsSearching( false );
					}
				} );
		}, DEBOUNCE_MS );

		return () => {
			clearTimeout( timer );
		};
	}, [ inputValue, taxonomy, isServerSearch ] );

	// Merge known items with current results so chips persist.
	const items = useMemo( () => {
		const baseItems = isServerSearch ? searchResults : allTerms;
		const resultValues = new Set( baseItems.map( ( item ) => item.value ) );
		const missingKnown = knownItems.filter(
			( item ) => ! resultValues.has( item.value )
		);
		return [ ...missingKnown, ...baseItems ];
	}, [ isServerSearch, searchResults, allTerms, knownItems ] );

	return {
		items,
		isLoading: isLoading || isSearching,
		isServerSearch,
	};
}
