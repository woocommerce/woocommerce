import { afterEach, describe, expect, test, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	useQueryStateByContext,
	useQueryStateByKey,
} from '../../use-query-state';
import { useQueryStateContext } from '../../../providers/query-state-context';
import { useCollection } from '../use-collection';
import { useCollectionData } from '../use-collection-data';

vi.mock( '../../use-query-state' );
vi.mock( '../../../providers/query-state-context' );
vi.mock( '../use-collection' );

describe( 'useCollectionData', () => {
	afterEach( () => {
		vi.useRealTimers();
		vi.clearAllMocks();
	} );

	test( 'passes active attribute and price filters to the collection-data request', () => {
		vi.useFakeTimers();

		const queryAttribute = {
			taxonomy: 'pa_size',
			queryType: 'or',
		};
		let registeredAttributeCounts: ( typeof queryAttribute )[] = [];
		const queryState = {
			attributes: [
				{
					attribute: 'pa_size',
					operator: 'in',
					slug: [ 'small' ],
				},
			],
			min_price: '1500',
			max_price: '4000',
		};
		let collectionDataQueryState: Record< string, unknown > = {};
		const setCalculateAttributeCounts = vi.fn();
		const setCollectionDataQueryState = vi.fn();
		const setOtherQueryState = vi.fn();

		( useQueryStateContext as Mock ).mockReturnValue( 'page' );
		( useQueryStateByContext as Mock ).mockImplementation( () => [
			collectionDataQueryState,
			setCollectionDataQueryState,
		] );
		( useQueryStateByKey as Mock ).mockImplementation(
			( queryKey, defaultValue ) => [
				queryKey === 'calculate_attribute_counts'
					? registeredAttributeCounts
					: defaultValue,
				queryKey === 'calculate_attribute_counts'
					? setCalculateAttributeCounts
					: setOtherQueryState,
			]
		);
		( useCollection as Mock ).mockReturnValue( {
			results: { attribute_counts: [] },
			isLoading: false,
		} );

		const { rerender } = renderHook( () =>
			useCollectionData( {
				queryAttribute,
				queryState,
				isEditor: false,
			} )
		);

		expect( setCalculateAttributeCounts ).toHaveBeenCalledWith( [
			queryAttribute,
		] );

		registeredAttributeCounts = [ queryAttribute ];
		collectionDataQueryState = {
			calculate_attribute_counts: registeredAttributeCounts,
		};
		rerender();

		act( () => {
			vi.advanceTimersByTime( 200 );
		} );

		expect( useCollection ).toHaveBeenLastCalledWith( {
			namespace: '/wc/store/v1',
			resourceName: 'products/collection-data',
			query: {
				...queryState,
				page: undefined,
				per_page: undefined,
				orderby: undefined,
				order: undefined,
				calculate_attribute_counts: [
					{
						taxonomy: 'pa_size',
						query_type: 'or',
					},
				],
			},
			shouldSelect: true,
		} );
	} );
} );
