/**
 * External dependencies
 */
import { render, act } from '@testing-library/react';
import * as mockUtils from '@woocommerce/editor-components/utils';

/**
 * Internal dependencies
 */
import withProduct from '../with-product';
import * as mockBaseUtils from '../../base/utils/errors';

jest.mock( '@woocommerce/editor-components/utils', () => ( {
	getProduct: jest.fn(),
} ) );

jest.mock( '../../base/utils/errors', () => ( {
	formatError: jest.fn(),
} ) );

const mockProduct = { name: 'T-Shirt' };
const attributes = { productId: 1 };

// Capture the props the HOC injects into the wrapped component.
let lastProps;
const CapturedComponent = jest.fn( ( props ) => {
	lastProps = props;
	return null;
} );
const TestComponent = withProduct( CapturedComponent );

// Run an interaction and flush the async state updates it triggers inside
// `act`, so React's async fetch-then-setState work does not leak past the test.
const settle = async ( fn ) => {
	await act( async () => {
		fn();
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
	} );
};

describe( 'withProduct Component', () => {
	let renderResult;
	const renderComponent = ( props = { attributes } ) =>
		settle( () => {
			renderResult = render( <TestComponent { ...props } /> );
		} );

	afterEach( () => {
		mockUtils.getProduct.mockReset();
		CapturedComponent.mockClear();
		lastProps = undefined;
	} );

	describe( 'lifecycle events', () => {
		beforeEach( async () => {
			mockUtils.getProduct.mockImplementation( () => Promise.resolve() );
			await renderComponent();
		} );

		it( 'getProduct is called on mount with passed in product id', () => {
			const { getProduct } = mockUtils;

			expect( getProduct ).toHaveBeenCalledWith( attributes.productId );
			expect( getProduct ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'getProduct is called on component update', async () => {
			const { getProduct } = mockUtils;
			const newAttributes = { ...attributes, productId: 2 };
			await settle( () =>
				renderResult.rerender(
					<TestComponent attributes={ newAttributes } />
				)
			);

			expect( getProduct ).toHaveBeenNthCalledWith(
				2,
				newAttributes.productId
			);
			expect( getProduct ).toHaveBeenCalledTimes( 2 );
		} );

		it( 'getProduct is hooked to the prop', async () => {
			const { getProduct } = mockUtils;

			await settle( () => lastProps.getProduct() );

			expect( getProduct ).toHaveBeenCalledTimes( 2 );
		} );
	} );

	describe( 'when the API returns product data', () => {
		beforeEach( async () => {
			mockUtils.getProduct.mockImplementation( ( productId ) =>
				Promise.resolve( { ...mockProduct, id: productId } )
			);
			await renderComponent();
		} );

		it( 'sets the product props', () => {
			expect( lastProps.error ).toBeNull();
			expect( typeof lastProps.getProduct ).toBe( 'function' );
			expect( lastProps.isLoading ).toBe( false );
			expect( lastProps.product ).toEqual( {
				...mockProduct,
				id: attributes.productId,
			} );
		} );
	} );

	describe( 'when the API returns an error', () => {
		const error = { message: 'There was an error.' };
		const formattedError = { message: 'There was an error.', type: 'api' };

		beforeEach( async () => {
			mockUtils.getProduct.mockImplementation( () =>
				Promise.reject( error )
			);
			mockBaseUtils.formatError.mockImplementation( () => formattedError );
			await renderComponent();
		} );

		test( 'sets the error prop', () => {
			const { formatError } = mockBaseUtils;

			expect( formatError ).toHaveBeenCalledWith( error );
			expect( formatError ).toHaveBeenCalledTimes( 1 );
			expect( lastProps.error ).toEqual( formattedError );
			expect( typeof lastProps.getProduct ).toBe( 'function' );
			expect( lastProps.isLoading ).toBe( false );
			expect( lastProps.product ).toBeNull();
		} );
	} );
} );
