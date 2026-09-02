/**
 * External dependencies
 */
import { render, act } from '@testing-library/react';
import * as mockUtils from '@woocommerce/editor-components/utils';

/**
 * Internal dependencies
 */
import withProductVariations from '../with-product-variations';
import * as mockBaseUtils from '../../base/utils/errors';

jest.mock( '@woocommerce/editor-components/utils', () => ( {
	getProductVariationsWithTotal: jest.fn(),
} ) );

jest.mock( '../../base/utils/errors', () => ( {
	formatError: jest.fn(),
} ) );

const mockProducts = [
	{ id: 1, name: 'Hoodie', variations: [ { id: 3 }, { id: 4 } ] },
	{ id: 2, name: 'Backpack' },
];
const mockVariations = [
	{ id: 3, name: 'Blue' },
	{ id: 4, name: 'Red' },
];

// Capture the props the HOC injects into the wrapped component.
let lastProps;
const CapturedComponent = jest.fn( ( props ) => {
	lastProps = props;
	return null;
} );
const TestComponent = withProductVariations( CapturedComponent );

// Run an interaction and flush the async state updates it triggers inside
// `act`, so React's async fetch-then-setState work does not leak past the test.
const settle = async ( fn ) => {
	await act( async () => {
		fn();
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
	} );
};

describe( 'withProductVariations Component', () => {
	let renderResult;
	const renderComponent = ( props ) =>
		settle( () => {
			renderResult = render(
				<TestComponent
					error={ null }
					isLoading={ false }
					products={ mockProducts }
					selected={ [ 1 ] }
					showVariations={ true }
					{ ...props }
				/>
			);
		} );

	afterEach( () => {
		mockUtils.getProductVariationsWithTotal.mockReset();
		CapturedComponent.mockClear();
		lastProps = undefined;
	} );

	describe( 'lifecycle events', () => {
		beforeEach( () => {
			mockUtils.getProductVariationsWithTotal.mockImplementation( () =>
				Promise.resolve( {
					variations: mockVariations,
					total: mockVariations.length,
				} )
			);
		} );

		it( 'getProductVariationsWithTotal is called on mount', async () => {
			await renderComponent();
			const { getProductVariationsWithTotal } = mockUtils;

			expect( getProductVariationsWithTotal ).toHaveBeenCalledWith( 1, {
				offset: 0,
			} );
			expect( getProductVariationsWithTotal ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'getProductVariationsWithTotal is called on component update', async () => {
			await renderComponent( {
				selected: undefined,
				showVariations: undefined,
			} );
			const { getProductVariationsWithTotal } = mockUtils;

			expect( getProductVariationsWithTotal ).toHaveBeenCalledTimes( 0 );

			await settle( () =>
				renderResult.rerender(
					<TestComponent
						error={ null }
						isLoading={ false }
						products={ mockProducts }
						selected={ [ 1 ] }
						showVariations={ true }
					/>
				)
			);

			expect( getProductVariationsWithTotal ).toHaveBeenCalledWith( 1, {
				offset: 0,
			} );
			expect( getProductVariationsWithTotal ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'getProductVariationsWithTotal is not called if selected product has no variations', async () => {
			await renderComponent( { selected: [ 2 ] } );
			const { getProductVariationsWithTotal } = mockUtils;

			expect( getProductVariationsWithTotal ).toHaveBeenCalledTimes( 0 );
		} );

		it( 'getProductVariationsWithTotal is called if selected product is a variation', async () => {
			await renderComponent( { selected: [ 3 ] } );
			const { getProductVariationsWithTotal } = mockUtils;

			expect( getProductVariationsWithTotal ).toHaveBeenCalledWith( 1, {
				offset: 0,
			} );
			expect( getProductVariationsWithTotal ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	describe( 'when the API returns variations data', () => {
		beforeEach( async () => {
			mockUtils.getProductVariationsWithTotal.mockImplementation( () =>
				Promise.resolve( {
					variations: mockVariations,
					total: mockVariations.length,
				} )
			);
			await renderComponent();
		} );

		it( 'sets the variations props', () => {
			const expectedVariations = {
				1: [
					{ id: 3, name: 'Blue', parent: 1 },
					{ id: 4, name: 'Red', parent: 1 },
				],
			};

			expect( lastProps.error ).toBeNull();
			expect( lastProps.isLoading ).toBe( false );
			expect( lastProps.variations ).toEqual( expectedVariations );
		} );
	} );

	describe( 'when the API returns an error', () => {
		const error = { message: 'There was an error.' };
		const formattedError = { message: 'There was an error.', type: 'api' };

		beforeEach( async () => {
			mockUtils.getProductVariationsWithTotal.mockImplementation( () =>
				Promise.reject( error )
			);
			mockBaseUtils.formatError.mockImplementation(
				() => formattedError
			);
			await renderComponent();
		} );

		test( 'sets the error prop', () => {
			const { formatError } = mockBaseUtils;

			expect( formatError ).toHaveBeenCalledWith( error );
			expect( formatError ).toHaveBeenCalledTimes( 1 );
			expect( lastProps.error ).toEqual( formattedError );
			expect( lastProps.isLoading ).toBe( false );
			expect( lastProps.variations ).toEqual( { 1: null } );
		} );
	} );

	describe( 'when a product has more than 25 variations', () => {
		const totalVariations = 60;
		const mockManyVariations = Array.from(
			{ length: totalVariations },
			( _, i ) => ( {
				id: i + 1,
				name: `Variation ${ i + 1 }`,
			} )
		);

		const productWithManyVariations = [
			{
				id: 1,
				name: 'Hoodie',
				variations: mockManyVariations.map( ( v ) => ( { id: v.id } ) ),
			},
		];

		beforeEach( () => {
			mockUtils.getProductVariationsWithTotal.mockImplementation(
				( productId, { offset = 0 } ) => {
					const start = offset;
					const end = Math.min( start + 25, totalVariations );
					const variations = mockManyVariations.slice( start, end );

					return Promise.resolve( {
						variations,
						total: totalVariations,
					} );
				}
			);
		} );

		it( 'loads the first 25 variations by default and provides onLoadMoreVariations', async () => {
			await renderComponent( { products: productWithManyVariations } );

			const { getProductVariationsWithTotal } = mockUtils;

			// Should have been called once with offset 0
			expect( getProductVariationsWithTotal ).toHaveBeenCalledWith( 1, {
				offset: 0,
			} );
			expect( getProductVariationsWithTotal ).toHaveBeenCalledTimes( 1 );

			// Should have first 25 variations
			expect( lastProps.variations[ 1 ] ).toHaveLength( 25 );
			expect( lastProps.variations[ 1 ][ 0 ] ).toEqual( {
				id: 1,
				name: 'Variation 1',
				parent: 1,
			} );
			expect( lastProps.variations[ 1 ][ 24 ] ).toEqual( {
				id: 25,
				name: 'Variation 25',
				parent: 1,
			} );

			// Should have total variations count
			expect( lastProps.totalVariations[ 1 ] ).toBe( totalVariations );

			// Should provide onLoadMoreVariations function
			expect( typeof lastProps.onLoadMoreVariations ).toBe( 'function' );
		} );

		it( 'loads the next 25 variations when onLoadMoreVariations is called', async () => {
			await renderComponent( { products: productWithManyVariations } );

			// Verify initial 25 variations are loaded
			expect( lastProps.variations[ 1 ] ).toHaveLength( 25 );

			// Call onLoadMoreVariations to load next batch
			await settle( () => lastProps.onLoadMoreVariations() );

			const { getProductVariationsWithTotal } = mockUtils;

			// Should have been called again with offset 25
			expect( getProductVariationsWithTotal ).toHaveBeenCalledWith( 1, {
				offset: 25,
			} );
			expect( getProductVariationsWithTotal ).toHaveBeenCalledTimes( 2 );

			// Should now have 50 variations (25 + 25)
			expect( lastProps.variations[ 1 ] ).toHaveLength( 50 );
			expect( lastProps.variations[ 1 ][ 25 ] ).toEqual( {
				id: 26,
				name: 'Variation 26',
				parent: 1,
			} );
			expect( lastProps.variations[ 1 ][ 49 ] ).toEqual( {
				id: 50,
				name: 'Variation 50',
				parent: 1,
			} );
		} );

		it( 'loads all variations when onLoadMoreVariations is called multiple times', async () => {
			await renderComponent( { products: productWithManyVariations } );

			// Load second batch
			await settle( () => lastProps.onLoadMoreVariations() );

			// Load third batch (final 10 variations)
			await settle( () => lastProps.onLoadMoreVariations() );

			const { getProductVariationsWithTotal } = mockUtils;

			// Should have been called 3 times total
			expect( getProductVariationsWithTotal ).toHaveBeenCalledTimes( 3 );
			expect( getProductVariationsWithTotal ).toHaveBeenNthCalledWith(
				3,
				1,
				{
					offset: 50,
				}
			);

			// Should now have all 60 variations
			expect( lastProps.variations[ 1 ] ).toHaveLength( 60 );
			expect( lastProps.variations[ 1 ][ 59 ] ).toEqual( {
				id: 60,
				name: 'Variation 60',
				parent: 1,
			} );
		} );
	} );
} );
