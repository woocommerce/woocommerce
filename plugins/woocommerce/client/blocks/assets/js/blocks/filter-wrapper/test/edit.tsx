/**
 * External dependencies
 */
import React from '@wordpress/element';
import { cleanup, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import FilterWrapperEdit from '../edit';
import RatingFilterEdit from '../../rating-filter/edit';
import StockFilterEdit from '../../stock-filter/edit';

jest.mock( '@wordpress/block-editor', () => ( {
	...jest.requireActual( '@wordpress/block-editor' ),
	useBlockProps: jest.fn( ( props = {} ) => props ),
	InspectorControls: jest.fn( ( { children } ) => <div>{ children }</div> ),
	InnerBlocks: jest.fn( ( { template } ) => (
		<section>
			<h3>{ template[ 0 ][ 1 ].content }</h3>
			<div
				data-testid="locked-filter-child"
				data-block-name={ template[ 1 ][ 0 ] }
				data-lock-remove={ String( template[ 1 ][ 1 ].lock.remove ) }
			/>
		</section>
	) ),
} ) );

jest.mock( '@wordpress/components', () => {
	const element = jest.requireActual( '@wordpress/element' );

	return {
		...jest.requireActual( '@wordpress/components' ),
		Disabled: jest.fn( ( { children } ) => <div>{ children }</div> ),
		Notice: jest.fn( ( { children } ) => <div>{ children }</div> ),
		PanelBody: jest.fn( ( { children } ) => <div>{ children }</div> ),
		ToggleControl: jest.fn( ( { label, checked, onChange } ) => (
			<label>
				<input
					type="checkbox"
					checked={ checked }
					onChange={ ( event ) => onChange( event.target.checked ) }
				/>
				{ label }
			</label>
		) ),
		withSpokenMessages: jest.fn( ( Component ) => Component ),
		__experimentalToggleGroupControl: jest.fn(
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
		__experimentalToggleGroupControlOption: jest.fn(
			( { label, onSelect, selectedValue, value } ) => (
				<label>
					<input
						type="radio"
						checked={ selectedValue === value }
						onChange={ () => onSelect( value ) }
					/>
					{ label }
				</label>
			)
		),
		__experimentalToolsPanel: jest.fn( ( { children } ) => (
			<div>{ children }</div>
		) ),
		__experimentalToolsPanelItem: jest.fn( ( { children } ) => (
			<div>{ children }</div>
		) ),
	};
} );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn( () => ( {
		removeBlock: jest.fn(),
		replaceBlock: jest.fn(),
		selectBlock: jest.fn(),
		updateBlockAttributes: jest.fn(),
	} ) ),
} ) );

jest.mock( '@woocommerce/editor-components/upgrade-downgrade-notice', () => ( {
	UpgradeDowngradeNotice: jest.fn( ( { children } ) => (
		<div>{ children }</div>
	) ),
} ) );

jest.mock( '@woocommerce/base-context/hooks', () => {
	const queryState = {};
	const queryValues: string[] = [];
	const setQueryState = jest.fn();
	const collectionData = {
		rating_counts: [
			{ rating: 1, count: 1 },
			{ rating: 5, count: 2 },
		],
		stock_status_counts: [
			{ status: 'instock', count: 2 },
			{ status: 'outofstock', count: 1 },
		],
	};

	return {
		...jest.requireActual( '@woocommerce/base-context/hooks' ),
		useCollectionData: jest.fn( () => ( {
			data: collectionData,
			isLoading: false,
		} ) ),
		useQueryStateByContext: jest.fn( () => [ queryState ] ),
		useQueryStateByKey: jest.fn( () => [ queryValues, setQueryState ] ),
	};
} );

jest.mock( '@woocommerce/settings', () => {
	const stockStatusOptions = {
		instock: 'In stock',
		outofstock: 'Out of stock',
	};

	return {
		...jest.requireActual( '@woocommerce/settings' ),
		getSetting: jest.fn( ( key, defaultValue ) => {
			if ( key === 'stockStatusOptions' ) {
				return stockStatusOptions;
			}
			return defaultValue;
		} ),
		getSettingWithCoercion: jest.fn( ( key, defaultValue ) =>
			key === 'hasFilterableProducts' ? true : defaultValue
		),
	};
} );

jest.mock( '@wordpress/a11y', () => ( {
	...jest.requireActual( '@wordpress/a11y' ),
	speak: jest.fn(),
} ) );

afterEach( () => {
	cleanup();
	jest.clearAllMocks();
	jest.restoreAllMocks();
} );

describe( 'legacy filter editor ownership', () => {
	it.each( [
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
			screen.getByRole( 'heading', { level: 3, name: row.heading } )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'locked-filter-child' ) ).toHaveAttribute(
			'data-block-name',
			`woocommerce/${ row.filterType }`
		);
		expect( screen.getByTestId( 'locked-filter-child' ) ).toHaveAttribute(
			'data-lock-remove',
			'true'
		);

		const innerBlocksProps = ( InnerBlocks as unknown as jest.Mock ).mock
			.calls[ 0 ][ 0 ];
		expect( innerBlocksProps.allowedBlocks ).toEqual( [ 'core/heading' ] );
		expect( innerBlocksProps.template ).toEqual( [
			[ 'core/heading', { content: row.heading, level: 3 } ],
			[
				`woocommerce/${ row.filterType }`,
				{ heading: '', lock: { remove: true } },
			],
		] );
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
			const setAttributes = jest.fn();
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
				screen.getByRole( 'radio', { name: 'Dropdown' } )
			);
			expect( setAttributes ).toHaveBeenCalledTimes( 1 );
			expect( setAttributes ).toHaveBeenCalledWith( {
				displayStyle: 'dropdown',
			} );

			rerender(
				renderEdit( { ...row.attributes, displayStyle: 'dropdown' } )
			);
			expect(
				screen.queryByRole( 'checkbox', { name: row.listOption } )
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
