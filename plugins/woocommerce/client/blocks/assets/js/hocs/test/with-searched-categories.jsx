/**
 * External dependencies
 */
import { render, act } from '@testing-library/react';
import * as mockUtils from '@woocommerce/editor-components/utils';

/**
 * Internal dependencies
 */
import withSearchedCategories from '../with-searched-categories';

// Mock the getCategories values for tests.
mockUtils.getCategories = jest.fn().mockImplementation( () =>
	Promise.resolve( [
		{ id: 1, name: 'Clothing' },
		{ id: 2, name: 'Food' },
	] )
);

// Capture the props the HOC injects into the wrapped component.
let lastProps;
const CapturedComponent = jest.fn( ( props ) => {
	lastProps = props;
	return null;
} );
const TestComponent = withSearchedCategories( CapturedComponent );

// Run an interaction and flush the async state updates it triggers inside
// `act`, so React's async fetch-then-setState work does not leak past the test.
const settle = async ( fn ) => {
	await act( async () => {
		fn();
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
	} );
};

describe( 'withSearchedCategories Component', () => {
	afterEach( () => {
		mockUtils.getCategories.mockClear();
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
			expect( lastProps.categories ).toEqual( [
				{ id: 1, name: 'Clothing' },
				{ id: 2, name: 'Food' },
			] );
		} );
	} );
} );
