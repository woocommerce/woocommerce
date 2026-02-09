// We need to disable the following eslint check as it's only applicable
// to testing-library/react not `react-test-renderer` used here
/* eslint-disable testing-library/await-async-query */
/**
 * External dependencies
 */
import TestRenderer, { act } from 'react-test-renderer';
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

describe( 'withSearchedCategories Component', () => {
	afterEach( () => {
		mockUtils.getCategories.mockClear();
	} );
	const TestComponent = withSearchedCategories(
		( { selected, categories, isLoading, onSearch } ) => {
			return (
				<div
					data-categories={ categories }
					data-selected={ selected }
					data-isLoading={ isLoading }
					data-onSearch={ onSearch }
				/>
			);
		}
	);
	describe( 'lifecycle tests', () => {
		const selected = [ 10 ];
		let props, renderer;

		act( () => {
			renderer = TestRenderer.create(
				<TestComponent selected={ selected } />
			);
		} );

		it( 'has expected values for props', () => {
			props = renderer.root.findByType( 'div' ).props;
			expect( props[ 'data-selected' ] ).toEqual( selected );
			expect( props[ 'data-categories' ] ).toEqual( [
				{ id: 1, name: 'Clothing' },
				{ id: 2, name: 'Food' },
			] );
		} );
	} );
} );
