/**
 * External dependencies
 */
import TestRenderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import withProducts from '../with-products';
import * as mockUtils from '../utils';
import * as mockBaseUtils from '../../utils/errors';

jest.mock( '../utils', () => ( {
	getProducts: jest.fn(),
} ) );

jest.mock( '../../utils/errors', () => ( {
	formatError: jest.fn(),
} ) );

const mockProducts = [ { id: 10, name: 'foo' }, { id: 20, name: 'bar' } ];
const defaultArgs = {
	orderby: 'menu_order',
	order: 'asc',
	per_page: 9,
	page: 1,
};
const TestComponent = withProducts( ( props ) => {
	return (
		<div
			error={ props.error }
			getProducts={ props.getProducts }
			appendReviews={ props.appendReviews }
			onChangeArgs={ props.onChangeArgs }
			isLoading={ props.isLoading }
			products={ props.products }
			totalProducts={ props.totalProducts }
		/>
	);
} );
const render = () => {
	return TestRenderer.create(
		<TestComponent
			attributes={ {
				columns: 3,
				rows: 3,
			} }
			currentPage={ 1 }
			sortValue="menu_order"
			productId={ 1 }
			productsToDisplay={ 2 }
		/>
	);
};

describe( 'withProducts Component', () => {
	let renderer;
	afterEach( () => {
		mockUtils.getProducts.mockReset();
	} );

	describe( 'lifecycle events', () => {
		beforeEach( () => {
			mockUtils.getProducts
				.mockImplementationOnce( () =>
					Promise.resolve( {
						products: mockProducts.slice( 0, 2 ),
						totalProducts: mockProducts.length,
					} )
				)
				.mockImplementationOnce( () =>
					Promise.resolve( {
						products: mockProducts.slice( 2, 3 ),
						totalProducts: mockProducts.length,
					} )
				);
			renderer = render();
		} );

		it( 'getProducts is called on mount', () => {
			const { getProducts } = mockUtils;

			expect( getProducts ).toHaveBeenCalledWith( defaultArgs );
			expect( getProducts ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	describe( 'when the API returns product data', () => {
		beforeEach( () => {
			mockUtils.getProducts.mockImplementation( () =>
				Promise.resolve( {
					products: mockProducts,
					totalProducts: mockProducts.length,
				} )
			);
			renderer = render();
		} );

		it( 'sets products based on API response', () => {
			const props = renderer.root.findByType( 'div' ).props;

			expect( props.error ).toBeNull();
			expect( props.isLoading ).toBe( false );
			expect( props.products ).toEqual( mockProducts );
			expect( props.totalProducts ).toEqual( mockProducts.length );
		} );
	} );

	describe( 'when the API returns an error', () => {
		const error = { message: 'There was an error.' };
		const getProductsPromise = Promise.reject( error );
		const formattedError = { message: 'There was an error.', type: 'api' };

		beforeEach( () => {
			mockUtils.getProducts.mockImplementation(
				() => getProductsPromise
			);
			mockBaseUtils.formatError.mockImplementation(
				() => formattedError
			);
			renderer = render();
		} );

		it( 'sets the error prop', ( done ) => {
			const { formatError } = mockBaseUtils;
			getProductsPromise.catch( () => {
				const props = renderer.root.findByType( 'div' ).props;

				expect( formatError ).toHaveBeenCalledWith( error );
				expect( formatError ).toHaveBeenCalledTimes( 1 );
				expect( props.error ).toEqual( formattedError );
				expect( props.isLoading ).toBe( false );
				expect( props.products ).toEqual( [] );
				expect( props.totalProducts ).toEqual( 0 );

				done();
			} );
		} );
	} );
} );
