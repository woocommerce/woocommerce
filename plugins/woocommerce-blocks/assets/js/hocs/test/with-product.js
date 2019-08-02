/**
 * External dependencies
 */
import TestRenderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import withProduct from '../with-product';
import * as mockUtils from '../../components/utils';

// Mock the getProduct functions for tests.
jest.mock( '../../components/utils', () => ( {
	getProduct: jest.fn(),
} ) );

const mockProduct = { name: 'T-Shirt' };
const attributes = { productId: 1 };
const TestComponent = withProduct( ( props ) => {
	return <div
		error={ props.error }
		getProduct={ props.getProduct }
		isLoading={ props.isLoading }
		product={ props.product }
	/>;
} );
const render = () => {
	return TestRenderer.create(
		<TestComponent
			attributes={ attributes }
		/>
	);
};

describe( 'withProduct Component', () => {
	let renderer;
	afterEach( () => {
		mockUtils.getProduct.mockReset();
	} );

	describe( 'lifecycle events', () => {
		beforeEach( () => {
			mockUtils.getProduct.mockImplementation( () => Promise.resolve() );
			renderer = render();
		} );

		describe( 'test', () => {
			it( 'getProduct is called on mount with passed in product id', () => {
				const { getProduct } = mockUtils;

				expect( getProduct ).toHaveBeenCalledWith( attributes.productId );
				expect( getProduct ).toHaveBeenCalledTimes( 1 );
			} );
		} );

		describe( 'test', () => {
			it( 'getProduct is hooked to the prop', () => {
				const { getProduct } = mockUtils;
				const props = renderer.root.findByType( 'div' ).props;

				props.getProduct();

				expect( getProduct ).toHaveBeenCalledTimes( 2 );
			} );
		} );
	} );

	describe( 'when the API returns product data', () => {
		beforeEach( () => {
			mockUtils.getProduct.mockImplementation(
				( productId ) => Promise.resolve( { ...mockProduct, id: productId } )
			);
			renderer = render();
		} );

		it( 'sets the product props', () => {
			const props = renderer.root.findByType( 'div' ).props;

			expect( props.error ).toBeNull();
			expect( typeof props.getProduct ).toBe( 'function' );
			expect( props.isLoading ).toBe( false );
			expect( props.product ).toEqual( { ...mockProduct, id: attributes.productId } );
		} );
	} );

	describe( 'when the API returns an error', () => {
		beforeEach( () => {
			mockUtils.getProduct.mockImplementation(
				() => Promise.reject( { message: 'There was an error.' } )
			);
			renderer = render();
		} );

		it( 'sets the error prop', () => {
			const props = renderer.root.findByType( 'div' ).props;

			expect( props.error ).toEqual( { apiMessage: 'There was an error.' } );
			expect( typeof props.getProduct ).toBe( 'function' );
			expect( props.isLoading ).toBe( false );
			expect( props.product ).toBeNull();
		} );
	} );
} );
