/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Basic } from '../stories/compare-filter.story';
import { CompareFilter } from '../index';
import Search from '../../search';
import productAutocompleter from '../../search/autocompleters/product';
// Due to Jest implementation we cannot mock it only for specific tests.
// If your test requires non-mocked Search, move them to another test file.
jest.mock( '../../search' );
Search.mockName( 'Search' );

describe( 'CompareFilter', () => {
	let props;
	beforeEach( () => {
		props = {
			path: '/foo/bar',
			type: 'products',
			param: 'product',
			getLabels() {
				return Promise.resolve( [] );
			},
			labels: {
				helpText: 'Select at least two to compare',
				placeholder: 'Search for things to compare',
				title: 'Compare Things',
				update: 'Compare',
			},
		};
	} );
	it( 'should render the example from the storybook', () => {
		const path = '/story/woocommerce-admin-components-comparefilter--basic';

		expect( function () {
			render( <Basic path={ path } /> );
		} ).not.toThrow();
	} );

	it( 'should forward the `type` prop the Search component', () => {
		props.type = 'custom';

		render( <CompareFilter { ...props } /> );

		// Check that Search component received the prop, without checking its behavior/internals/implementation details.
		expect( Search ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				type: 'custom',
			} ),
			expect.anything()
		);
	} );
	it( 'should forward the `autocompleter` prop the Search component', () => {
		props.autocompleter = productAutocompleter;

		render( <CompareFilter { ...props } /> );

		// Check that Search component received the prop, without checking its behavior/internals/implementation details.
		expect( Search ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				autocompleter: productAutocompleter,
			} ),
			expect.anything()
		);
	} );
} );
