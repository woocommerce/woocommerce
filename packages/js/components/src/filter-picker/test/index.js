/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

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
		beforeEach( () => {
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
							type: 'products',
							param: 'products',
							labels: {
								placeholder: 'Type to search for a product',
								button: 'Single Product',
							},
						},
					},
				],
			};
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
		it( "for a `'custom'` type should forward autocompleter config the Search component", async () => {
			const path = '/foo/bar';

			const customFilterSettings = config.filters[ 1 ].settings;
			customFilterSettings.type = 'custom';
			customFilterSettings.autocompleter = productAutocompleter;

			const { queryAllByRole } = render(
				<FilterPicker path={ path } config={ config } />
			);

			// Emulate filter dropdown being opened.
			// The main dropdown does not have its role defined, so we need to dig deeper into actual internals.
			userEvent.click( queryAllByRole( 'button' )[ 0 ] );

			// Check that the given component was rendered, without checking its behavior/internals/implementation details.
			//
			// In vanilla HTML, we would check
			// expect( filterPicker.querySelector('woo-search') ).to.have.a.property( 'autocompleter', autocompleter );
			//
			// Following will check if it was rendered with given props, not neceserily being visible now.
			const lastCallArgs = Search.mock.calls.slice( -1 )[ 0 ];
			expect( lastCallArgs[ 0 ] ).toHaveProperty(
				'autocompleter',
				productAutocompleter
			);
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
								type: 'products',
								param: 'param_1',
								getLabels: () => {},
							},
						},
					],
				},
				{
					label: 'Comparison',
					value: 'compare-products',
					chartMode: 'item-comparison',
					settings: {
						type: 'products',
						param: 'param_2',
						getLabels: () => {},
						onClick: () => {},
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
