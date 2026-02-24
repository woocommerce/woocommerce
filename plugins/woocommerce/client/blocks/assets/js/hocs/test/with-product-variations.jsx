// We need to disable the following eslint check as it's only applicable
// to testing-library/react not `react-test-renderer` used here
/* eslint-disable testing-library/await-async-query */
/**
 * External dependencies
 */
import TestRenderer from 'react-test-renderer';
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
const TestComponent = withProductVariations( ( props ) => {
	return (
		<div
			data-error={ props.error }
			data-expandedProduct={ props.expandedProduct }
			data-isLoading={ props.isLoading }
			data-variations={ props.variations }
			data-variationsLoading={ props.variationsLoading }
			data-onLoadMoreVariations={ props.onLoadMoreVariations }
			data-totalVariations={ props.totalVariations }
		/>
	);
} );
const render = () => {
	return TestRenderer.create(
		<TestComponent
			error={ null }
			isLoading={ false }
			products={ mockProducts }
			selected={ [ 1 ] }
			showVariations={ true }
		/>
	);
};

describe( 'withProductVariations Component', () => {
	let renderer;
	afterEach( () => {
		mockUtils.getProductVariationsWithTotal.mockReset();
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

		it( 'getProductVariationsWithTotal is called on mount', () => {
			renderer = render();
			const { getProductVariationsWithTotal } = mockUtils;

			expect( getProductVariationsWithTotal ).toHaveBeenCalledWith( 1, {
				offset: 0,
			} );
			expect( getProductVariationsWithTotal ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'getProductVariationsWithTotal is called on component update', () => {
			renderer = TestRenderer.create(
				<TestComponent
					error={ null }
					isLoading={ false }
					products={ mockProducts }
				/>
			);
			const { getProductVariationsWithTotal } = mockUtils;

			expect( getProductVariationsWithTotal ).toHaveBeenCalledTimes( 0 );

			renderer.update(
				<TestComponent
					error={ null }
					isLoading={ false }
					products={ mockProducts }
					selected={ [ 1 ] }
					showVariations={ true }
				/>
			);

			expect( getProductVariationsWithTotal ).toHaveBeenCalledWith( 1, {
				offset: 0,
			} );
			expect( getProductVariationsWithTotal ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'getProductVariationsWithTotal is not called if selected product has no variations', () => {
			TestRenderer.create(
				<TestComponent
					error={ null }
					isLoading={ false }
					products={ mockProducts }
					selected={ [ 2 ] }
					showVariations={ true }
				/>
			);
			const { getProductVariationsWithTotal } = mockUtils;

			expect( getProductVariationsWithTotal ).toHaveBeenCalledTimes( 0 );
		} );

		it( 'getProductVariationsWithTotal is called if selected product is a variation', () => {
			TestRenderer.create(
				<TestComponent
					error={ null }
					isLoading={ false }
					products={ mockProducts }
					selected={ [ 3 ] }
					showVariations={ true }
				/>
			);
			const { getProductVariationsWithTotal } = mockUtils;

			expect( getProductVariationsWithTotal ).toHaveBeenCalledWith( 1, {
				offset: 0,
			} );
			expect( getProductVariationsWithTotal ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	describe( 'when the API returns variations data', () => {
		beforeEach( () => {
			mockUtils.getProductVariationsWithTotal.mockImplementation( () =>
				Promise.resolve( {
					variations: mockVariations,
					total: mockVariations.length,
				} )
			);
			renderer = render();
		} );

		it( 'sets the variations props', async () => {
			const props = renderer.root.findByType( 'div' ).props;
			const expectedVariations = {
				1: [
					{ id: 3, name: 'Blue', parent: 1 },
					{ id: 4, name: 'Red', parent: 1 },
				],
			};

			expect( props[ 'data-error' ] ).toBeNull();
			expect( props[ 'data-isLoading' ] ).toBe( false );
			expect( props[ 'data-variations' ] ).toEqual( expectedVariations );
		} );
	} );

	describe( 'when the API returns an error', () => {
		const error = { message: 'There was an error.' };
		const getProductVariationsWithTotalPromise = Promise.reject( error );
		const formattedError = { message: 'There was an error.', type: 'api' };

		beforeEach( () => {
			mockUtils.getProductVariationsWithTotal.mockImplementation(
				() => getProductVariationsWithTotalPromise
			);
			mockBaseUtils.formatError.mockImplementation(
				() => formattedError
			);
			renderer = render();
		} );

		test( 'sets the error prop', async () => {
			await TestRenderer.act( async () => {
				await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
			} );

			const { formatError } = mockBaseUtils;
			const props = renderer.root.findByType( 'div' ).props;

			expect( formatError ).toHaveBeenCalledWith( error );
			expect( formatError ).toHaveBeenCalledTimes( 1 );
			expect( props[ 'data-error' ] ).toEqual( formattedError );
			expect( props[ 'data-isLoading' ] ).toBe( false );
			expect( props[ 'data-variations' ] ).toEqual( { 1: null } );
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
			renderer = TestRenderer.create(
				<TestComponent
					error={ null }
					isLoading={ false }
					products={ productWithManyVariations }
					selected={ [ 1 ] }
					showVariations={ true }
				/>
			);

			await TestRenderer.act( async () => {
				await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
			} );

			const props = renderer.root.findByType( 'div' ).props;
			const { getProductVariationsWithTotal } = mockUtils;

			// Should have been called once with offset 0
			expect( getProductVariationsWithTotal ).toHaveBeenCalledWith( 1, {
				offset: 0,
			} );
			expect( getProductVariationsWithTotal ).toHaveBeenCalledTimes( 1 );

			// Should have first 25 variations
			expect( props[ 'data-variations' ][ 1 ] ).toHaveLength( 25 );
			expect( props[ 'data-variations' ][ 1 ][ 0 ] ).toEqual( {
				id: 1,
				name: 'Variation 1',
				parent: 1,
			} );
			expect( props[ 'data-variations' ][ 1 ][ 24 ] ).toEqual( {
				id: 25,
				name: 'Variation 25',
				parent: 1,
			} );

			// Should have total variations count
			expect( props[ 'data-totalVariations' ][ 1 ] ).toBe(
				totalVariations
			);

			// Should provide onLoadMoreVariations function
			expect( typeof props[ 'data-onLoadMoreVariations' ] ).toBe(
				'function'
			);
		} );

		it( 'loads the next 25 variations when onLoadMoreVariations is called', async () => {
			renderer = TestRenderer.create(
				<TestComponent
					error={ null }
					isLoading={ false }
					products={ productWithManyVariations }
					selected={ [ 1 ] }
					showVariations={ true }
				/>
			);

			// Wait for initial load
			await TestRenderer.act( async () => {
				await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
			} );

			let props = renderer.root.findByType( 'div' ).props;

			// Verify initial 25 variations are loaded
			expect( props[ 'data-variations' ][ 1 ] ).toHaveLength( 25 );

			// Call onLoadMoreVariations to load next batch
			await TestRenderer.act( async () => {
				props[ 'data-onLoadMoreVariations' ]();
				await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
			} );

			props = renderer.root.findByType( 'div' ).props;
			const { getProductVariationsWithTotal } = mockUtils;

			// Should have been called again with offset 25
			expect( getProductVariationsWithTotal ).toHaveBeenCalledWith( 1, {
				offset: 25,
			} );
			expect( getProductVariationsWithTotal ).toHaveBeenCalledTimes( 2 );

			// Should now have 50 variations (25 + 25)
			expect( props[ 'data-variations' ][ 1 ] ).toHaveLength( 50 );
			expect( props[ 'data-variations' ][ 1 ][ 25 ] ).toEqual( {
				id: 26,
				name: 'Variation 26',
				parent: 1,
			} );
			expect( props[ 'data-variations' ][ 1 ][ 49 ] ).toEqual( {
				id: 50,
				name: 'Variation 50',
				parent: 1,
			} );
		} );

		it( 'loads all variations when onLoadMoreVariations is called multiple times', async () => {
			renderer = TestRenderer.create(
				<TestComponent
					error={ null }
					isLoading={ false }
					products={ productWithManyVariations }
					selected={ [ 1 ] }
					showVariations={ true }
				/>
			);

			// Wait for initial load
			await TestRenderer.act( async () => {
				await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
			} );

			let props = renderer.root.findByType( 'div' ).props;

			// Load second batch
			await TestRenderer.act( async () => {
				props[ 'data-onLoadMoreVariations' ]();
				await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
			} );

			props = renderer.root.findByType( 'div' ).props;

			// Load third batch (final 10 variations)
			await TestRenderer.act( async () => {
				props[ 'data-onLoadMoreVariations' ]();
				await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
			} );

			props = renderer.root.findByType( 'div' ).props;
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
			expect( props[ 'data-variations' ][ 1 ] ).toHaveLength( 60 );
			expect( props[ 'data-variations' ][ 1 ][ 59 ] ).toEqual( {
				id: 60,
				name: 'Variation 60',
				parent: 1,
			} );
		} );
	} );
} );
