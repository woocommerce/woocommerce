/**
 * External dependencies
 */
import TestRenderer from 'react-test-renderer';
import _ from 'lodash';
import * as mockUtils from '@woocommerce/editor-components/utils';

/**
 * Internal dependencies
 */
import withSearchedProducts from '../with-searched-products';

jest.mock( '@woocommerce/block-settings', () => ( {
	IS_LARGE_CATALOG: true,
} ) );

// Mock the getProducts and isLargeCatalog values for tests.
mockUtils.getProducts = jest.fn().mockImplementation( () =>
	Promise.resolve( [
		{ id: 10, name: 'foo', parent: 0 },
		{ id: 20, name: 'bar', parent: 0 },
	] )
);

// Add a mock implementation of debounce for testing so we can spy on
// the onSearch call.
const debouncedCancel = jest.fn();
const debouncedAction = jest.fn();
_.debounce = ( onSearch ) => {
	const debounced = debouncedAction.mockImplementation( () => {
		onSearch();
	} );
	debounced.cancel = debouncedCancel;
	return debounced;
};

describe( 'withSearchedProducts Component', () => {
	const { getProducts } = mockUtils;
	afterEach( () => {
		debouncedCancel.mockClear();
		debouncedAction.mockClear();
		mockUtils.getProducts.mockClear();
	} );
	const TestComponent = withSearchedProducts(
		( { selected, products, isLoading, onSearch } ) => {
			return (
				<div
					products={ products }
					selected={ selected }
					isLoading={ isLoading }
					onSearch={ onSearch }
				/>
			);
		}
	);
	describe( 'lifecycle tests', () => {
		const selected = [ 10 ];
		const renderer = TestRenderer.create(
			<TestComponent selected={ selected } />
		);
		let props;
		it(
			'getProducts is called on mount with passed in selected ' +
				'values',
			() => {
				expect( getProducts ).toHaveBeenCalledWith( { selected } );
				expect( getProducts ).toHaveBeenCalledTimes( 1 );
			}
		);
		it( 'has expected values for props', () => {
			props = renderer.root.findByType( 'div' ).props;
			expect( props.selected ).toEqual( selected );
			expect( props.products ).toEqual( [
				{ id: 10, name: 'foo', parent: 0 },
				{ id: 20, name: 'bar', parent: 0 },
			] );
		} );
		it( 'debounce and getProducts is called on search event', () => {
			props = renderer.root.findByType( 'div' ).props;
			props.onSearch();
			expect( debouncedAction ).toHaveBeenCalled();
			expect( getProducts ).toHaveBeenCalledTimes( 1 );
		} );
		it( 'debounce is cancelled on unmount', () => {
			renderer.unmount();
			expect( debouncedCancel ).toHaveBeenCalled();
			expect( getProducts ).toHaveBeenCalledTimes( 0 );
		} );
	} );
} );
