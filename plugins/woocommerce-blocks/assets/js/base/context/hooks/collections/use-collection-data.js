/**
 * External dependencies
 */
import { useState, useEffect, useMemo } from '@wordpress/element';
import { useDebounce } from 'use-debounce';
import { sortBy } from 'lodash';
import { useShallowEqual } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import { useQueryStateByContext, useQueryStateByKey } from '../use-query-state';
import { useCollection } from './use-collection';
import { useQueryStateContext } from '../../providers/query-state-context';

const buildCollectionDataQuery = ( collectionDataQueryState ) => {
	const query = collectionDataQueryState;

	if ( collectionDataQueryState.calculate_attribute_counts ) {
		query.calculate_attribute_counts = sortBy(
			collectionDataQueryState.calculate_attribute_counts.map(
				( { taxonomy, queryType } ) => {
					return {
						taxonomy,
						query_type: queryType,
					};
				}
			),
			[ 'taxonomy', 'query_type' ]
		);
	}

	return query;
};

export const useCollectionData = ( {
	queryAttribute,
	queryPrices,
	queryStock,
	queryState,
} ) => {
	let context = useQueryStateContext();
	context = `${ context }-collection-data`;

	const [ collectionDataQueryState ] = useQueryStateByContext( context );
	const [
		calculateAttributesQueryState,
		setCalculateAttributesQueryState,
	] = useQueryStateByKey( 'calculate_attribute_counts', [], context );
	const [
		calculatePriceRangeQueryState,
		setCalculatePriceRangeQueryState,
	] = useQueryStateByKey( 'calculate_price_range', null, context );
	const [
		calculateStockStatusQueryState,
		setCalculateStockStatusQueryState,
	] = useQueryStateByKey( 'calculate_stock_status_counts', null, context );

	const currentQueryAttribute = useShallowEqual( queryAttribute || {} );
	const currentQueryPrices = useShallowEqual( queryPrices );
	const currentQueryStock = useShallowEqual( queryStock );

	useEffect( () => {
		if (
			typeof currentQueryAttribute === 'object' &&
			Object.keys( currentQueryAttribute ).length
		) {
			const foundAttribute = calculateAttributesQueryState.find(
				( attribute ) => {
					return (
						attribute.taxonomy === currentQueryAttribute.taxonomy
					);
				}
			);

			if ( ! foundAttribute ) {
				setCalculateAttributesQueryState( [
					...calculateAttributesQueryState,
					currentQueryAttribute,
				] );
			}
		}
	}, [
		currentQueryAttribute,
		calculateAttributesQueryState,
		setCalculateAttributesQueryState,
	] );

	useEffect( () => {
		if (
			calculatePriceRangeQueryState !== currentQueryPrices &&
			currentQueryPrices !== undefined
		) {
			setCalculatePriceRangeQueryState( currentQueryPrices );
		}
	}, [
		currentQueryPrices,
		setCalculatePriceRangeQueryState,
		calculatePriceRangeQueryState,
	] );

	useEffect( () => {
		if (
			calculateStockStatusQueryState !== currentQueryStock &&
			currentQueryStock !== undefined
		) {
			setCalculateStockStatusQueryState( currentQueryStock );
		}
	}, [
		currentQueryStock,
		setCalculateStockStatusQueryState,
		calculateStockStatusQueryState,
	] );

	// Defer the select query so all collection-data query vars can be gathered.
	const [ shouldSelect, setShouldSelect ] = useState( false );
	const [ debouncedShouldSelect ] = useDebounce( shouldSelect, 200 );

	if ( ! shouldSelect ) {
		setShouldSelect( true );
	}

	const collectionDataQueryVars = useMemo( () => {
		return buildCollectionDataQuery( collectionDataQueryState );
	}, [ collectionDataQueryState ] );

	return useCollection( {
		namespace: '/wc/store/v1',
		resourceName: 'products/collection-data',
		query: {
			...queryState,
			page: undefined,
			per_page: undefined,
			orderby: undefined,
			order: undefined,
			...collectionDataQueryVars,
		},
		shouldSelect: debouncedShouldSelect,
	} );
};
