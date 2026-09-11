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
const attributes = /** @type {Record<string, unknown>} */ ( {
	categoryId: 1,
} );

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

		it( 'reloads when inherited context changes and clears an incompatible taxonomy', async () => {
			await settle( () =>
				renderResult.rerender(
					<TestComponent
						attributes={ {} }
						context={ { termId: 42, taxonomy: 'product_cat' } }
					/>
				)
			);
			expect( mockUtils.getCategory ).toHaveBeenLastCalledWith( 42 );
			expect( lastProps.effectiveCategoryId ).toBe( 42 );

			await settle( () =>
				renderResult.rerender(
					<TestComponent
						attributes={ {} }
						context={ { termId: 43, taxonomy: 'product_cat' } }
					/>
				)
			);
			expect( mockUtils.getCategory ).toHaveBeenLastCalledWith( 43 );

			mockUtils.getCategory.mockClear();
			await settle( () =>
				renderResult.rerender(
					<TestComponent
						attributes={ {} }
						context={ { termId: 43, taxonomy: 'category' } }
					/>
				)
			);
			expect( mockUtils.getCategory ).not.toHaveBeenCalled();
			expect( lastProps.category ).toBeNull();
			expect( lastProps.effectiveCategoryId ).toBeUndefined();
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

		it.each( [
			{ termId: 42, taxonomy: 'product_cat' },
			{ termId: 42, termTaxonomy: 'product_cat', taxonomy: 'category' },
		] )( 'loads the category inherited from %j', async ( context ) => {
			const { getCategory } = mockUtils;
			await renderComponent( {
				attributes: {},
				context,
			} );

			expect( getCategory ).toHaveBeenLastCalledWith( 42 );
			expect( lastProps.category ).toEqual( {
				...mockCategory,
				id: 42,
			} );
		} );

		it( 'prefers an explicitly selected category over term context', async () => {
			const { getCategory } = mockUtils;
			await renderComponent( {
				attributes: { categoryId: 7 },
				context: { termId: 42, taxonomy: 'product_cat' },
			} );

			expect( getCategory ).toHaveBeenLastCalledWith( 7 );
		} );

		it( 'does not load a product category from a different taxonomy', async () => {
			mockUtils.getCategory.mockClear();
			await renderComponent( {
				attributes: {},
				context: { termId: 42, taxonomy: 'category' },
			} );
			expect( mockUtils.getCategory ).not.toHaveBeenCalled();
			expect( lastProps.category ).toBeNull();
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
