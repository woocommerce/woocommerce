/**
 * External dependencies
 */
import { useState, useEffect, useMemo, useRef } from '@wordpress/element';
/**
 * Internal dependencies
 */
import type { Item } from './types';

const EMPTY_ARRAY: Item[] = [];

export function useElements( {
	elements,
	getElements,
}: {
	elements?: Item[];
	getElements?: () => Promise< Item[] >;
} ) {
	const staticElements = useMemo(
		() =>
			Array.isArray( elements ) && elements.length > 0
				? elements
				: EMPTY_ARRAY,
		[ elements ]
	);
	const [ records, setRecords ] = useState< Item[] >( staticElements );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ refreshTrigger, setRefreshTrigger ] = useState( 0 );
	const getElementsRef = useRef( getElements );

	// Update ref when getElements changes
	useEffect( () => {
		getElementsRef.current = getElements;
	}, [ getElements ] );

	// Expose refresh function on window for abilities to call
	useEffect( () => {
		const handleRefresh = () => {
			setRefreshTrigger( ( prev ) => prev + 1 );
		};

		window.addEventListener(
			'woocommerce-refresh-product-taxonomies',
			handleRefresh
		);

		return () => {
			window.removeEventListener(
				'woocommerce-refresh-product-taxonomies',
				handleRefresh
			);
		};
	}, [] );

	useEffect( () => {
		if ( ! getElementsRef.current ) {
			setRecords( staticElements );
			return;
		}

		let cancelled = false;
		setIsLoading( true );
		getElementsRef
			.current()
			.then( ( fetchedElements ) => {
				if ( ! cancelled ) {
					const dynamicElements =
						Array.isArray( fetchedElements ) &&
						fetchedElements.length > 0
							? fetchedElements
							: staticElements;
					setRecords( dynamicElements );
				}
			} )
			.catch( ( error: unknown ) => {
				// eslint-disable-next-line no-console
				console.error(
					'[useElements] Failed to fetch elements:',
					error
				);
				if ( ! cancelled ) {
					setRecords( staticElements );
				}
			} )
			.finally( () => {
				if ( ! cancelled ) {
					setIsLoading( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ staticElements, refreshTrigger ] );

	return {
		elements: records,
		isLoading,
	};
}
