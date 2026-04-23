/**
 * External dependencies
 */
import { FormTokenField } from '@wordpress/components';
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

interface Item {
	id: number;
	title: string;
}

interface ProductSearchProps {
	label: string;
	value: Item[];
	onChange: ( items: Item[] ) => void;
	endpoint: 'products' | 'products/categories';
}

interface ApiProduct {
	id: number;
	name: string;
}

export function ProductSearch( {
	label,
	value,
	onChange,
	endpoint,
}: ProductSearchProps ): JSX.Element {
	const [ suggestions, setSuggestions ] = useState< string[] >( [] );
	const [ searchResults, setSearchResults ] = useState< ApiProduct[] >( [] );
	const debounceRef = useRef< ReturnType< typeof setTimeout > | null >(
		null
	);
	const abortRef = useRef< AbortController | null >( null );

	const search = useCallback(
		( query: string ) => {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}

			if ( query.length < 2 ) {
				setSuggestions( [] );
				setSearchResults( [] );
				return;
			}

			abortRef.current = new AbortController();

			apiFetch< ApiProduct[] >( {
				path: `/wc/v3/${ endpoint }?search=${ encodeURIComponent(
					query
				) }&per_page=20`,
				signal: abortRef.current.signal,
			} )
				.then( ( results ) => {
					setSearchResults( results );
					setSuggestions( results.map( ( item ) => item.name ) );
				} )
				.catch( ( error ) => {
					if (
						error instanceof Error &&
						error.name === 'AbortError'
					) {
						return;
					}
					setSuggestions( [] );
					setSearchResults( [] );
				} );
		},
		[ endpoint ]
	);

	useEffect( () => {
		return () => {
			if ( debounceRef.current ) {
				clearTimeout( debounceRef.current );
			}
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [] );

	const tokenValues = value.map( ( item ) => item.title );

	return (
		<div style={ { marginBottom: '24px' } }>
			<FormTokenField
				label={ label }
				value={ tokenValues }
				suggestions={ suggestions }
				onInputChange={ ( query ) => {
					if ( debounceRef.current ) {
						clearTimeout( debounceRef.current );
					}
					debounceRef.current = setTimeout( () => {
						search( query );
					}, 300 );
				} }
				onChange={ ( tokens ) => {
					const items: Item[] = tokens
						.map( ( token ) => {
							const existing = value.find(
								( v ) => v.title === token
							);
							if ( existing ) {
								return existing;
							}
							const result = searchResults.find(
								( r ) => r.name === token
							);
							if ( result ) {
								return { id: result.id, title: result.name };
							}
							return null;
						} )
						.filter( ( item ): item is Item => item !== null );
					onChange( items );
				} }
				__experimentalExpandOnFocus
				__next40pxDefaultSize
			/>
		</div>
	);
}
