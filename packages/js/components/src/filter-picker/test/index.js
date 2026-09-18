/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';
import { logged } from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import { Basic } from '../stories/filter-picker.story';
import FilterPicker from '../index';
import Search from '../../search';
import productAutocompleter from '../../search/autocompleters/product';
// Due to Jest implementation we cannot mock it only for specific tests.
// If your test requires non-mocked Search, move them to another test file.
jest.mock( '../../search' );

describe( 'FilterPicker', () => {
	it( 'should render the example from the storybook', async () => {
		// Jest and its JSDOM does not allow making extensive use of searchParams used by Basic example.
		const path = '/story/woocommerce-admin-components-filterpicker--basic';

		expect( function () {
			render( <Basic path={ path } /> );
		} ).not.toThrow();
	} );
	describe( "when a config is given with a filter with `component: 'Search'`", () => {
		let config;
		let warn;
		const openDropdown = ( { queryAllByRole } ) => {
			// The main dropdown does not have its role defined, so we need to dig deeper into actual internals.
			userEvent.click( queryAllByRole( 'button' )[ 0 ] );
		};
		const getLastSearchProps = () =>
			Search.mock.calls.slice( -1 )[ 0 ][ 0 ];

		beforeEach( () => {
			// Reset the deprecation messages, so each test can assert its own warning.
			Object.keys( logged ).forEach( ( key ) => delete logged[ key ] );
			warn = jest.spyOn( console, 'warn' ).mockImplementation( () => {} );
			config = {
				label: 'Show',
				staticParams: [],
				param: 'product_filter',
				showFilters: () => true,
				filters: [
					{ label: 'All Products', value: 'all' },
					{
						component: 'Search',
						value: 'select_product',
						chartMode: 'item-comparison',
						path: 'select_product',
						settings: {
							param: 'products',
							labels: {
								button: 'Single Product',
							},
							searchProps: {
								type: 'products',
								placeholder: 'Type to search for a product',
							},
						},
					},
				],
			};
		} );

		it( 'should render the label without a trailing colon', () => {
			const { container } = render(
				<FilterPicker path="/foo/bar" config={ config } />
			);

			const label = container.querySelector(
				'.woocommerce-filters-label'
			);
			expect( label.textContent ).toBe( 'Show' );
		} );

		it( 'should render the Search component', async () => {
			const path = '/foo/bar';

			const { queryAllByRole } = render(
				<FilterPicker path={ path } config={ config } />
			);

			// Emulate filter dropdown being opened.
			// The main dropdown does not have its role defined, so we need to dig deeper into actual internals.
			userEvent.click( queryAllByRole( 'button' )[ 0 ] );

			// Check that the given component was rendered, without checking its behavior/internals/implementation details.
			//
			// In vanilla HTML, we would check
			// expect( filterPicker.querySelector('woo-search') ).to.be.not.null();
			// expect( filterPicker.querySelector('woo-search') ).to.be.an.instanceof( Search );
			//
			// Following will check if it was rendered, not neceserily being visible now.
			expect( Search ).toHaveBeenCalled();
		} );
		it( 'should forward `searchProps` to the Search component', async () => {
			config.filters[ 1 ].settings.searchProps = {
				type: 'custom',
				autocompleter: productAutocompleter,
				placeholder: 'Type to search for a product',
				showClearButton: true,
			};

			openDropdown(
				render( <FilterPicker path="/foo/bar" config={ config } /> )
			);

			// Check that Search was rendered with the given props, without checking its behavior/internals/implementation details.
			expect( getLastSearchProps() ).toMatchObject( {
				type: 'custom',
				autocompleter: productAutocompleter,
				placeholder: 'Type to search for a product',
				showClearButton: true,
			} );
			expect( warn ).not.toHaveBeenCalled();
		} );
		it( 'should keep its own Search props and merge `className`', async () => {
			config.filters[ 1 ].settings.searchProps = {
				type: 'products',
				className: 'my-search',
				inlineTags: false,
				staticResults: false,
			};

			openDropdown(
				render( <FilterPicker path="/foo/bar" config={ config } /> )
			);

			expect( getLastSearchProps() ).toMatchObject( {
				className: 'woocommerce-filters-filter__search my-search',
				inlineTags: true,
				staticResults: true,
			} );
		} );
		it( 'should still forward the deprecated `type`, `autocompleter`, and `labels.placeholder` settings', async () => {
			const settings = config.filters[ 1 ].settings;
			delete settings.searchProps;
			settings.type = 'custom';
			settings.autocompleter = productAutocompleter;
			settings.labels.placeholder = 'Type to search for a product';

			openDropdown(
				render( <FilterPicker path="/foo/bar" config={ config } /> )
			);

			expect( getLastSearchProps() ).toMatchObject( {
				type: 'custom',
				autocompleter: productAutocompleter,
				placeholder: 'Type to search for a product',
			} );
			expect( warn ).toHaveBeenCalledWith(
				expect.stringContaining( 'FilterPicker filter' )
			);
		} );
		it( 'should prefer the deprecated settings over `searchProps`', async () => {
			// An extension that changes the settings of a core filter the old way.
			const settings = config.filters[ 1 ].settings;
			settings.type = 'custom';
			settings.autocompleter = productAutocompleter;
			settings.labels.placeholder = 'Changed placeholder';

			openDropdown(
				render( <FilterPicker path="/foo/bar" config={ config } /> )
			);

			expect( getLastSearchProps() ).toMatchObject( {
				type: 'custom',
				autocompleter: productAutocompleter,
				placeholder: 'Changed placeholder',
			} );
			expect( warn ).toHaveBeenCalled();
		} );
	} );
	describe( 'getAllFilterParams', () => {
		const query = { product_filter: 'select_product' };
		const config = {
			label: 'Show',
			staticParams: [],
			param: 'product_filter',
			showFilters: () => true,
			filters: [
				{
					label: 'Single Product',
					value: 'select_product',
					chartMode: 'item-comparison',
					subFilters: [
						{
							component: 'Search',
							value: 'single_product',
							chartMode: 'item-comparison',
							path: [ 'select_product' ],
							settings: {
								param: 'param_1',
								getLabels: () => {},
								searchProps: { type: 'products' },
							},
						},
					],
				},
				{
					label: 'Comparison',
					value: 'compare-products',
					chartMode: 'item-comparison',
					settings: {
						param: 'param_2',
						getLabels: () => {},
						onClick: () => {},
						searchProps: { type: 'products' },
					},
				},
			],
		};

		it( 'should return an array', () => {
			const filterPicker = new FilterPicker( { config, query } );
			const allParams = filterPicker.getAllFilterParams();

			expect( allParams ).toHaveLength( 2 );
			expect( allParams.includes( 'param_1' ) ).toBeTruthy();
			expect( allParams.includes( 'param_2' ) ).toBeTruthy();
		} );
	} );
} );
