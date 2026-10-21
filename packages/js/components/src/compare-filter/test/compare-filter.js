/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { logged } from '@wordpress/deprecated';

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
	let warn;
	beforeEach( () => {
		// Reset the deprecation messages, so each test can assert its own warning.
		Object.keys( logged ).forEach( ( key ) => delete logged[ key ] );
		warn = jest.spyOn( console, 'warn' ).mockImplementation( () => {} );
		props = {
			path: '/foo/bar',
			param: 'product',
			getLabels() {
				return Promise.resolve( [] );
			},
			labels: {
				helpText: 'Select at least two to compare',
				title: 'Compare Things',
				update: 'Compare',
			},
			searchProps: {
				type: 'products',
				placeholder: 'Search for things to compare',
			},
		};
	} );
	it( 'should render the example from the storybook', () => {
		const path = '/story/woocommerce-admin-components-comparefilter--basic';

		expect( function () {
			render( <Basic path={ path } /> );
		} ).not.toThrow();
	} );

	it( 'should forward `searchProps` to the Search component', () => {
		props.searchProps = {
			type: 'custom',
			autocompleter: productAutocompleter,
			placeholder: 'Search for things to compare',
			showClearButton: true,
		};

		render( <CompareFilter { ...props } /> );

		// Check that Search component received the props, without checking its behavior/internals/implementation details.
		expect( Search ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				type: 'custom',
				autocompleter: productAutocompleter,
				placeholder: 'Search for things to compare',
				showClearButton: true,
			} ),
			expect.anything()
		);
		expect( warn ).not.toHaveBeenCalled();
	} );

	it( 'should keep control of the `selected` and `onChange` Search props', () => {
		const onChange = jest.fn();
		props.searchProps = {
			type: 'products',
			selected: [ { key: 1, label: 'Foo' } ],
			onChange,
		};

		render( <CompareFilter { ...props } /> );

		const [ searchProps ] = Search.mock.calls.slice( -1 )[ 0 ];
		expect( searchProps.selected ).toEqual( [] );
		expect( searchProps.onChange ).not.toBe( onChange );
	} );

	it( 'should still forward the deprecated `type`, `autocompleter`, and `labels.placeholder` props', () => {
		delete props.searchProps;
		props.type = 'custom';
		props.autocompleter = productAutocompleter;
		props.labels.placeholder = 'Search for things to compare';

		render( <CompareFilter { ...props } /> );

		expect( Search ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				type: 'custom',
				autocompleter: productAutocompleter,
				placeholder: 'Search for things to compare',
			} ),
			expect.anything()
		);
		expect( warn ).toHaveBeenCalledWith(
			expect.stringContaining( 'to CompareFilter' )
		);
	} );

	it( 'should prefer the deprecated props over `searchProps`', () => {
		props.type = 'custom';
		props.autocompleter = productAutocompleter;
		props.labels.placeholder = 'Changed placeholder';
		props.searchProps = {
			type: 'products',
			placeholder: 'Search for things to compare',
		};

		render( <CompareFilter { ...props } /> );

		expect( Search ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				type: 'custom',
				autocompleter: productAutocompleter,
				placeholder: 'Changed placeholder',
			} ),
			expect.anything()
		);
		expect( warn ).toHaveBeenCalled();
	} );
} );
