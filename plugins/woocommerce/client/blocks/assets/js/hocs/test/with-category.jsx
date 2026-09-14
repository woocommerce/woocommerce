/**
 * External dependencies
 */
import { render, act } from '@testing-library/react';
import * as mockUtils from '@woocommerce/editor-components/utils';

/**
 * Internal dependencies
 */
import withCategory from '../with-category';
import * as mockBaseUtils from '../../base/utils/errors';

jest.mock( '@woocommerce/editor-components/utils', () => ( {
	getCategory: jest.fn(),
} ) );

jest.mock( '../../base/utils/errors', () => ( {
	formatError: jest.fn(),
} ) );

const mockCategory = { name: 'Clothing' };
const attributes = { categoryId: 1 };

// Capture the props the HOC injects into the wrapped component.
let lastProps;
const CapturedComponent = jest.fn( ( props ) => {
	lastProps = props;
	return null;
} );
const TestComponent = withCategory( CapturedComponent );

// Run an interaction and flush the async state updates it triggers inside
// `act`, so React's async fetch-then-setState work does not leak past the test.
const settle = async ( fn ) => {
	await act( async () => {
		fn();
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
	} );
};

describe( 'withCategory Component', () => {
	let renderResult;
	const renderComponent = ( props = { attributes } ) =>
		settle( () => {
			renderResult = render( <TestComponent { ...props } /> );
		} );

	afterEach( () => {
		mockUtils.getCategory.mockReset();
		CapturedComponent.mockClear();
		lastProps = undefined;
	} );

	describe( 'lifecycle events', () => {
		beforeEach( async () => {
			mockUtils.getCategory.mockImplementation( () => Promise.resolve() );
			await renderComponent();
		} );

		it( 'getCategory is called on mount with passed in category id', () => {
			const { getCategory } = mockUtils;

			expect( getCategory ).toHaveBeenCalledWith( attributes.categoryId );
			expect( getCategory ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'getCategory is called on component update', async () => {
			const { getCategory } = mockUtils;
			const newAttributes = { ...attributes, categoryId: 2 };
			await settle( () =>
				renderResult.rerender(
					<TestComponent attributes={ newAttributes } />
				)
			);

			expect( getCategory ).toHaveBeenNthCalledWith(
				2,
				newAttributes.categoryId
			);
			expect( getCategory ).toHaveBeenCalledTimes( 2 );
		} );

		it( 'getCategory is hooked to the prop', async () => {
			const { getCategory } = mockUtils;

			await settle( () => lastProps.getCategory() );

			expect( getCategory ).toHaveBeenCalledTimes( 2 );
		} );
	} );

	describe( 'when the API returns category data', () => {
		beforeEach( async () => {
			mockUtils.getCategory.mockImplementation( ( categoryId ) =>
				Promise.resolve( { ...mockCategory, id: categoryId } )
			);
			await renderComponent();
		} );

		it( 'sets the category props', () => {
			expect( lastProps.error ).toBeNull();
			expect( typeof lastProps.getCategory ).toBe( 'function' );
			expect( lastProps.isLoading ).toBe( false );
			expect( lastProps.category ).toEqual( {
				...mockCategory,
				id: attributes.categoryId,
			} );
		} );
	} );

	describe( 'when the API returns an error', () => {
		const error = { message: 'There was an error.' };
		const formattedError = { message: 'There was an error.', type: 'api' };

		beforeEach( async () => {
			mockUtils.getCategory.mockImplementation( () =>
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
			expect( typeof lastProps.getCategory ).toBe( 'function' );
			expect( lastProps.isLoading ).toBe( false );
			expect( lastProps.category ).toBeNull();
		} );
	} );
} );
