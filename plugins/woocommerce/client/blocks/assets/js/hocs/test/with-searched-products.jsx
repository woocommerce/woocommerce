/**
 * External dependencies
 */
import { render, act } from '@testing-library/react';
import * as mockUtils from '@woocommerce/editor-components/utils';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Internal dependencies
 */
import withSearchedProducts from '../with-searched-products';

// Add a mock implementation of debounce for testing so we can spy on the onSearch call.
jest.mock( 'use-debounce', () => {
	return {
		useDebouncedCallback: jest
			.fn()
			.mockImplementation(
				( search ) => () => mockUtils.getProducts( search )
			),
	};
} );

jest.mock( '@woocommerce/block-settings', () => ( {
	__esModule: true,
	blocksConfig: {
		productCount: 101,
	},
} ) );

// Mock the getProducts values for tests.
mockUtils.getProducts = jest.fn().mockImplementation( () =>
	Promise.resolve( [
		{ id: 10, name: 'foo', parent: 0 },
		{ id: 20, name: 'bar', parent: 0 },
	] )
);

// Capture the props the HOC injects into the wrapped component.
let lastProps;
const CapturedComponent = jest.fn( ( props ) => {
	lastProps = props;
	return null;
} );
const TestComponent = withSearchedProducts( CapturedComponent );

// Run an interaction and flush the async state updates it triggers inside
// `act`, so React's async fetch-then-setState work does not leak past the test.
const settle = async ( fn ) => {
	await act( async () => {
		fn();
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
	} );
};

describe( 'withSearchedProducts Component', () => {
	const { getProducts } = mockUtils;
	afterEach( () => {
		useDebouncedCallback.mockClear();
		mockUtils.getProducts.mockClear();
		CapturedComponent.mockClear();
		lastProps = undefined;
	} );

	describe( 'lifecycle tests', () => {
		const selected = [ 10 ];

		beforeEach( async () => {
			await settle( () =>
				render( <TestComponent selected={ selected } /> )
			);
		} );

		it( 'has expected values for props', () => {
			expect( lastProps.selected ).toEqual( selected );
			expect( lastProps.products ).toEqual( [
				{ id: 10, name: 'foo', parent: 0 },
				{ id: 20, name: 'bar', parent: 0 },
			] );
		} );

		it( 'debounce and getProducts is called on search event', async () => {
			// Ignore the getProducts call triggered on mount so the assertion
			// measures only the call made in response to the search event.
			getProducts.mockClear();

			await settle( () => lastProps.onSearch() );

			expect( useDebouncedCallback ).toHaveBeenCalled();
			expect( getProducts ).toHaveBeenCalledTimes( 1 );
		} );
	} );
} );
