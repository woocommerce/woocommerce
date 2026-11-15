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

jest.mock( '../../use-query-state' );
jest.mock( '../../../providers/query-state-context' );
jest.mock( '../use-collection' );

describe( 'useCollectionData', () => {
	afterEach( () => {
		jest.useRealTimers();
		jest.clearAllMocks();
	} );

	test( 'passes active attribute and price filters to the collection-data request', () => {
		jest.useFakeTimers();

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
		const setCalculateAttributeCounts = jest.fn();
		const setCollectionDataQueryState = jest.fn();
		const setOtherQueryState = jest.fn();

		( useQueryStateContext as jest.Mock ).mockReturnValue( 'page' );
		( useQueryStateByContext as jest.Mock ).mockImplementation( () => [
			collectionDataQueryState,
			setCollectionDataQueryState,
		] );
		( useQueryStateByKey as jest.Mock ).mockImplementation(
			( queryKey, defaultValue ) => [
				queryKey === 'calculate_attribute_counts'
					? registeredAttributeCounts
					: defaultValue,
				queryKey === 'calculate_attribute_counts'
					? setCalculateAttributeCounts
					: setOtherQueryState,
			]
		);
		( useCollection as jest.Mock ).mockReturnValue( {
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
			jest.advanceTimersByTime( 200 );
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
