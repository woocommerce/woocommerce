import { afterEach, describe, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import React from '@wordpress/element';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import FilterWrapperEdit from '../edit';
import PriceFilterEdit from '../../price-filter/edit';
import RatingFilterEdit from '../../rating-filter/edit';
import StockFilterEdit from '../../stock-filter/edit';
import { textContentMatcher } from '../../../../../tests/utils/find-by-text';
vi.mock( '@wordpress/block-editor', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/block-editor' ) ),
		useBlockProps: vi.fn( ( props = {} ) => props ),
		InspectorControls: vi.fn( ( { children } ) => <div>{ children }</div> ),
		InnerBlocks: vi.fn( ( { template } ) => (
			<section>
				<h3>{ template[ 0 ][ 1 ].content }</h3>
				<div
					data-testid="locked-filter-child"
					data-block-name={ template[ 1 ][ 0 ] }
					data-lock-remove={ String(
						template[ 1 ][ 1 ].lock.remove
					) }
				/>
			</section>
		) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/components', async () => {
	const element = await vi.importActual( '@wordpress/element' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		...( await vi.importActual( '@wordpress/components' ) ),
		Disabled: vi.fn( ( { children } ) => <div>{ children }</div> ),
		Notice: vi.fn( ( { children } ) => <div>{ children }</div> ),
		PanelBody: vi.fn( ( { children } ) => <div>{ children }</div> ),
		ToggleControl: vi.fn( ( { label, checked, onChange } ) => (
			<span>
				<input
					type="checkbox"
					aria-label={ label }
					checked={ checked }
					onChange={ ( event ) => onChange( event.target.checked ) }
				/>
				{ label }
			</span>
		) ),
		withSpokenMessages: vi.fn( ( Component ) => Component ),
		__experimentalToggleGroupControl: vi.fn(
			( { children, label, onChange, value } ) => (
				<fieldset>
					<legend>{ label }</legend>
					{ element.Children.map( children, ( child ) =>
						element.cloneElement( child, {
							onSelect: onChange,
							selectedValue: value,
						} )
					) }
				</fieldset>
			)
		),
		__experimentalToggleGroupControlOption: vi.fn(
			( { label, onSelect, selectedValue, value } ) => (
				<span>
					<input
						type="radio"
						aria-label={ label }
						checked={ selectedValue === value }
						onChange={ () => onSelect( value ) }
					/>
					{ label }
				</span>
			)
		),
		__experimentalToolsPanel: vi.fn( ( { children } ) => (
			<div>{ children }</div>
		) ),
		__experimentalToolsPanelItem: vi.fn( ( { children } ) => (
			<div>{ children }</div>
		) ),
	} );
} );
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useDispatch: vi.fn( () => ( {
			removeBlock: vi.fn(),
			replaceBlock: vi.fn(),
			selectBlock: vi.fn(),
			updateBlockAttributes: vi.fn(),
		} ) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/editor-components/upgrade-downgrade-notice', () => {
	const mock = {
		UpgradeDowngradeNotice: vi.fn( ( { children } ) => (
			<div>{ children }</div>
		) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/block-settings', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/block-settings' ) ),
		blocksConfig: {
			productCount: 1,
		},
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/base-context/hooks', async () => {
	const queryState = {};
	const queryValues: string[] = [];
	const setQueryState = vi.fn();
	const collectionData = {
		rating_counts: [
			{
				rating: 1,
				count: 1,
			},
			{
				rating: 5,
				count: 2,
			},
		],
		stock_status_counts: [
			{
				status: 'instock',
				count: 2,
			},
			{
				status: 'outofstock',
				count: 1,
			},
		],
		price_range: {
			min_price: '100',
			max_price: '5000',
			currency_code: 'USD',
			currency_symbol: '$',
			currency_thousand_separator: ',',
			currency_decimal_separator: '.',
			currency_minor_unit: 2,
			currency_prefix: '$',
			currency_suffix: '',
		},
	};
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		...( await vi.importActual( '@woocommerce/base-context/hooks' ) ),
		useCollectionData: vi.fn( () => ( {
			data: collectionData,
			isLoading: false,
		} ) ),
		useQueryStateByContext: vi.fn( () => [ queryState ] ),
		useQueryStateByKey: vi.fn( () => [ queryValues, setQueryState ] ),
	} );
} );
vi.mock( '@woocommerce/settings', async () => {
	const stockStatusOptions = {
		instock: 'In stock',
		outofstock: 'Out of stock',
	};
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		...( await vi.importActual( '@woocommerce/settings' ) ),
		getSetting: vi.fn( ( key, defaultValue ) => {
			if ( key === 'stockStatusOptions' ) {
				return stockStatusOptions;
			}
			return defaultValue;
		} ),
		getSettingWithCoercion: vi.fn( ( key, defaultValue ) =>
			key === 'hasFilterableProducts' ? true : defaultValue
		),
	} );
} );
vi.mock( '@wordpress/a11y', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/a11y' ) ),
		speak: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
afterEach( () => {
	vi.clearAllMocks();
	vi.restoreAllMocks();
} );
describe( 'legacy filter editor ownership', () => {
	it.each( [
		{
			filterType: 'price-filter',
			heading: 'Filter by price',
		},
		{
			filterType: 'rating-filter',
			heading: 'Filter by rating',
		},
		{
			filterType: 'stock-filter',
			heading: 'Filter by stock status',
		},
	] )( 'seeds the $filterType wrapper template', ( row ) => {
		const WrapperEdit = FilterWrapperEdit as unknown as React.ComponentType<
			Record< string, unknown >
		>;
		render(
			<WrapperEdit
				attributes={ {
					filterType: row.filterType,
					heading: row.heading,
				} }
				clientId="wrapper-client-id"
			/>
		);
		expect(
			screen.getByRole( 'heading', {
				level: 3,
				name: row.heading,
			} )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'locked-filter-child' ) ).toHaveAttribute(
			'data-block-name',
			`woocommerce/${ row.filterType }`
		);
		expect( screen.getByTestId( 'locked-filter-child' ) ).toHaveAttribute(
			'data-lock-remove',
			'true'
		);
		const innerBlocksProps = ( InnerBlocks as unknown as Mock ).mock
			.calls[ 0 ][ 0 ];
		expect( innerBlocksProps.allowedBlocks ).toEqual( [ 'core/heading' ] );
		expect( innerBlocksProps.template ).toEqual( [
			[
				'core/heading',
				{
					content: row.heading,
					level: 3,
				},
			],
			[
				`woocommerce/${ row.filterType }`,
				{
					heading: '',
					lock: {
						remove: true,
					},
				},
			],
		] );
	} );
	it( 'maps Price display and Apply controls to preview behavior', async () => {
		const user = userEvent.setup();
		const setAttributes = vi.fn();
		const attributes = {
			heading: '',
			headingLevel: 3,
			showInputFields: true,
			inlineInput: false,
			showFilterButton: false,
		};
		const PriceEdit = PriceFilterEdit as unknown as React.ComponentType<
			Record< string, unknown >
		>;
		const renderEdit = ( editAttributes: Record< string, unknown > ) => (
			<PriceEdit
				attributes={ editAttributes }
				clientId="filter-client-id"
				setAttributes={ setAttributes }
			/>
		);
		const { rerender } = render( renderEdit( attributes ) );
		expect(
			await screen.findByRole( 'textbox', {
				name: 'Filter products by minimum price',
			} )
		).toBeVisible();
		expect(
			screen.getByRole( 'textbox', {
				name: 'Filter products by maximum price',
			} )
		).toBeVisible();
		expect(
			screen.queryByRole( 'button', {
				name: 'Apply price filter',
			} )
		).not.toBeInTheDocument();
		await user.click(
			screen.getByRole( 'radio', {
				name: 'Text',
			} )
		);
		expect( setAttributes ).toHaveBeenCalledTimes( 1 );
		expect( setAttributes ).toHaveBeenCalledWith( {
			showInputFields: false,
		} );
		rerender(
			renderEdit( {
				...attributes,
				showInputFields: false,
			} )
		);
		expect(
			screen.queryByRole( 'textbox', {
				name: 'Filter products by minimum price',
			} )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'textbox', {
				name: 'Filter products by maximum price',
			} )
		).not.toBeInTheDocument();
		expect( screen.getByText( textContentMatcher( '$1' ) ) ).toBeVisible();
		expect( screen.getByText( textContentMatcher( '$50' ) ) ).toBeVisible();
		setAttributes.mockClear();
		await user.click(
			screen.getByRole( 'checkbox', {
				name: "Show 'Apply filters' button",
			} )
		);
		expect( setAttributes ).toHaveBeenCalledTimes( 1 );
		expect( setAttributes ).toHaveBeenCalledWith( {
			showFilterButton: true,
		} );
		rerender(
			renderEdit( {
				...attributes,
				showInputFields: false,
				showFilterButton: true,
			} )
		);
		expect(
			await screen.findByRole( 'button', {
				name: 'Apply price filter',
			} )
		).toBeVisible();
	} );
	it.each( [
		{
			Edit: RatingFilterEdit,
			attributes: {
				displayStyle: 'list',
				isPreview: false,
				selectType: 'multiple',
				showCounts: false,
				showFilterButton: false,
			},
			listOption: 'Rated 1 out of 5',
		},
		{
			Edit: StockFilterEdit,
			attributes: {
				displayStyle: 'list',
				heading: '',
				headingLevel: 3,
				isPreview: false,
				selectType: 'multiple',
				showCounts: false,
				showFilterButton: false,
			},
			listOption: 'In stock',
		},
	] )(
		'maps $listOption display and Apply controls to preview behavior',
		async ( row ) => {
			const user = userEvent.setup();
			const setAttributes = vi.fn();
			const Edit = row.Edit as unknown as React.ComponentType<
				Record< string, unknown >
			>;
			const renderEdit = ( attributes: Record< string, unknown > ) => (
				<Edit
					attributes={ attributes }
					clientId="filter-client-id"
					setAttributes={ setAttributes }
				/>
			);
			const { rerender } = render( renderEdit( row.attributes ) );
			expect(
				await screen.findByRole( 'checkbox', {
					name: row.listOption,
				} )
			).toBeVisible();
			expect(
				screen.queryByRole( 'button', {
					name: /^Apply (rating|stock) filter$/,
				} )
			).not.toBeInTheDocument();
			await user.click(
				screen.getByRole( 'radio', {
					name: 'Dropdown',
				} )
			);
			expect( setAttributes ).toHaveBeenCalledTimes( 1 );
			expect( setAttributes ).toHaveBeenCalledWith( {
				displayStyle: 'dropdown',
			} );
			rerender(
				renderEdit( {
					...row.attributes,
					displayStyle: 'dropdown',
				} )
			);
			expect(
				screen.queryByRole( 'checkbox', {
					name: row.listOption,
				} )
			).not.toBeInTheDocument();
			expect( await screen.findByRole( 'combobox' ) ).toBeVisible();
			setAttributes.mockClear();
			await user.click(
				screen.getByRole( 'checkbox', {
					name: "Show 'Apply filters' button",
				} )
			);
			expect( setAttributes ).toHaveBeenCalledTimes( 1 );
			expect( setAttributes ).toHaveBeenCalledWith( {
				showFilterButton: true,
			} );
			rerender(
				renderEdit( {
					...row.attributes,
					displayStyle: 'dropdown',
					showFilterButton: true,
				} )
			);
			expect(
				await screen.findByRole( 'button', {
					name: /^Apply (rating|stock) filter$/,
				} )
			).toBeVisible();
		}
	);
} );
