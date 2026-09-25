/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';
import { logged } from '@wordpress/deprecated';
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { Basic } from '../stories/filter-picker.story';
import FilterPicker from '../index';
import Search from '../../search';
import productAutocompleter from '../../search/autocompleters/product';
// Due to Jest implementation we cannot mock it only for specific tests.
// If your test requires non-mocked Search, move them to another test file.
jest.mock( '../../search', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );
jest.mock( '../../search/autocompleters/product', () => ( {
	__esModule: true,
	default: { name: 'products' },
} ) );
jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	updateQueryString: jest.fn(),
} ) );

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
		it( 'should render a filter without `labels`', async () => {
			delete config.filters[ 1 ].settings.labels;

			openDropdown(
				render( <FilterPicker path="/foo/bar" config={ config } /> )
			);

			expect( getLastSearchProps() ).toMatchObject( {
				type: 'products',
				placeholder: 'Type to search for a product',
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

	describe( 'search selection query updates', () => {
		const advancedFilters = {
			filters: {
				product: {
					rules: [ { value: 'includes' }, { value: 'excludes' } ],
				},
			},
		};

		const productFilter = {
			component: 'Search',
			label: 'Single product',
			value: 'single_product',
			settings: {
				type: 'products',
				param: 'products',
				getLabels: () =>
					Promise.resolve( [ { key: 999, label: 'Old product' } ] ),
				labels: {
					placeholder: 'Type to search for a product',
					button: 'Single product',
				},
			},
		};

		const variationFilter = {
			component: 'Search',
			label: 'Single variation',
			value: 'single_variation',
			settings: {
				type: 'variations',
				param: 'variations',
				getLabels: () =>
					Promise.resolve( [ { key: 999, label: 'Old variation' } ] ),
				labels: {
					placeholder: 'Type to search for a variation',
					button: 'Single variation',
				},
			},
		};

		beforeEach( () => {
			jest.clearAllMocks();
			Search.mockImplementation( ( { onChange, selected, type } ) => {
				const choice =
					type === 'products'
						? { key: 101, label: 'Product 101' }
						: { key: 202, label: 'Variation 202' };
				return (
					<div>
						{ selected.map( ( tag ) => (
							<span key={ tag.key }>{ tag.label }</span>
						) ) }
						<button onClick={ () => onChange( [ choice ] ) }>
							Choose { choice.label }
						</button>
					</div>
				);
			} );
		} );

		it( 'selects a product, preserves static parameters, and clears stale filters', async () => {
			const onFilterSelect = jest.fn();
			const query = {
				period: 'last_month',
				compare: 'previous_year',
				filter: 'single_product',
				products: 999,
				variations: 404,
				product_includes: 303,
				product_excludes: 505,
			};
			const config = {
				label: 'Show',
				staticParams: [ 'period', 'compare' ],
				param: 'filter',
				showFilters: () => true,
				filters: [
					{ label: 'All products', value: 'all' },
					productFilter,
					variationFilter,
				],
			};

			render(
				<FilterPicker
					advancedFilters={ advancedFilters }
					config={ config }
					onFilterSelect={ onFilterSelect }
					path="/analytics/products"
					query={ query }
				/>
			);

			const persistedButton = await screen.findByRole( 'button', {
				name: /Old product.*Single product/,
			} );
			await userEvent.click( persistedButton );
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Choose Product 101' } )
			);

			// `product_excludes` is deliberately absent: an advanced filter that
			// does not allow multiples is reset through its first matching rule
			// only, so `includes` is cleared and `excludes` is left as it was.
			const expectedUpdate = {
				filter: 'single_product',
				products: 101,
				period: 'last_month',
				compare: 'previous_year',
				variations: undefined,
				product_includes: undefined,
			};
			expect( updateQueryString ).toHaveBeenCalledTimes( 1 );
			// Read the recorded call back and compare it strictly.
			// toHaveBeenCalledWith equates a key set to undefined with an
			// absent one, and the cleared filter params are the behavior
			// under test: dropping them would leave the assertion green.
			expect( updateQueryString.mock.calls[ 0 ] ).toStrictEqual( [
				expectedUpdate,
				'/analytics/products',
				query,
			] );
			expect( onFilterSelect ).toHaveBeenCalledTimes( 1 );
			expect( onFilterSelect.mock.calls[ 0 ] ).toStrictEqual( [
				expectedUpdate,
			] );
			expect(
				screen.getByRole( 'button', {
					name: /Product 101.*Single product/,
				} )
			).toBeInTheDocument();
		} );

		it( 'selects a variation and preserves its product and date scope', async () => {
			const onFilterSelect = jest.fn();
			const query = {
				period: 'last_month',
				compare: 'previous_year',
				filter: 'single_product',
				products: 101,
				'filter-variations': 'single_variation',
				variations: 999,
			};
			const config = {
				label: 'Variation',
				staticParams: [ 'filter', 'products', 'period', 'compare' ],
				param: 'filter-variations',
				showFilters: () => true,
				filters: [
					{ label: 'All variations', value: 'all' },
					productFilter,
					variationFilter,
				],
			};

			render(
				<FilterPicker
					advancedFilters={ { filters: {} } }
					config={ config }
					onFilterSelect={ onFilterSelect }
					path="/analytics/products"
					query={ query }
				/>
			);

			const persistedButton = await screen.findByRole( 'button', {
				name: /Old variation.*Single variation/,
			} );
			await userEvent.click( persistedButton );
			await userEvent.click(
				screen.getByRole( 'button', {
					name: 'Choose Variation 202',
				} )
			);

			const expectedUpdate = {
				'filter-variations': 'single_variation',
				variations: 202,
				filter: 'single_product',
				products: 101,
				period: 'last_month',
				compare: 'previous_year',
			};
			expect( updateQueryString ).toHaveBeenCalledTimes( 1 );
			expect( updateQueryString.mock.calls[ 0 ] ).toStrictEqual( [
				expectedUpdate,
				'/analytics/products',
				query,
			] );
			expect( onFilterSelect ).toHaveBeenCalledTimes( 1 );
			expect( onFilterSelect.mock.calls[ 0 ] ).toStrictEqual( [
				expectedUpdate,
			] );
			expect(
				screen.getByRole( 'button', {
					name: /Variation 202.*Single variation/,
				} )
			).toBeInTheDocument();
		} );
	} );
} );
