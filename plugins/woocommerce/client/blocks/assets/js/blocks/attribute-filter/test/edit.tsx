/**
 * External dependencies
 */
import React, { useState } from '@wordpress/element';
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import FilterWrapperEdit from '../../filter-wrapper/edit';
import AttributeFilterBlock from '../block';
import AttributeFilterEdit from '../edit';

jest.mock( '@wordpress/block-editor', () => ( {
	...jest.requireActual( '@wordpress/block-editor' ),
	useBlockProps: jest.fn( ( props = {} ) => props ),
	BlockControls: jest.fn( ( { children } ) => <div>{ children }</div> ),
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
			<label htmlFor={ String( label ) }>
				<input
					id={ String( label ) }
					type="checkbox"
					checked={ checked }
					onChange={ ( event ) => onChange( event.target.checked ) }
				/>
				{ label }
			</label>
		) ),
		withSpokenMessages: jest.fn( ( Component ) => Component ),
		// The WordPress control scheduler is browser-owned; these adapters retain
		// only the semantic radio/checkbox boundary for the real Edit callbacks.
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
				<label htmlFor={ value }>
					<input
						id={ value }
						type="radio"
						checked={ selectedValue === value }
						onChange={ () => onSelect( value ) }
					/>
					{ label }
				</label>
			)
		),
	};
} );

jest.mock( '@woocommerce/base-context/hooks', () => {
	const attributeTerms = [
		{ id: 11, name: 'Small', slug: 'small' },
		{ id: 12, name: 'Medium', slug: 'medium' },
		{ id: 13, name: 'Large', slug: 'large' },
	];
	const collectionData = {
		price_range: null,
		attribute_counts: [
			{ term: 11, count: 1 },
			{ term: 12, count: 1 },
			{ term: 13, count: 1 },
		],
		rating_counts: null,
		stock_status_counts: null,
	};

	return {
		...jest.requireActual( '@woocommerce/base-context/hooks' ),
		useCollection: jest.fn( () => ( {
			results: attributeTerms,
			isLoading: false,
		} ) ),
		useCollectionData: jest.fn( () => ( {
			data: collectionData,
			isLoading: false,
		} ) ),
		useQueryStateByContext: jest.fn( () => [ {} ] ),
		useQueryStateByKey: jest.fn( () => [ [], jest.fn() ] ),
	};
} );

jest.mock( '@woocommerce/settings', () => {
	const attributes = [
		{
			attribute_id: '1',
			attribute_name: 'size',
			attribute_label: 'Size',
			attribute_orderby: 'menu_order',
		},
	];

	return {
		...jest.requireActual( '@woocommerce/settings' ),
		getSetting: jest.fn( ( key, defaultValue ) =>
			key === 'attributes' ? attributes : defaultValue
		),
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
	jest.clearAllMocks();
	jest.restoreAllMocks();
} );

describe( 'Attribute Filter editor ownership', () => {
	it( 'seeds the attribute-filter wrapper template', () => {
		const WrapperEdit = FilterWrapperEdit as unknown as React.ComponentType<
			Record< string, unknown >
		>;

		render(
			<WrapperEdit
				attributes={ {
					filterType: 'attribute-filter',
					heading: 'Filter by attribute',
				} }
				clientId="wrapper-client-id"
			/>
		);

		expect(
			screen.getByRole( 'heading', {
				level: 3,
				name: 'Filter by attribute',
			} )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'locked-filter-child' ) ).toHaveAttribute(
			'data-block-name',
			'woocommerce/attribute-filter'
		);
		expect( screen.getByTestId( 'locked-filter-child' ) ).toHaveAttribute(
			'data-lock-remove',
			'true'
		);

		const innerBlocksProps = ( InnerBlocks as unknown as jest.Mock ).mock
			.calls[ 0 ][ 0 ];
		expect( innerBlocksProps.allowedBlocks ).toEqual( [ 'core/heading' ] );
		expect( innerBlocksProps.template ).toEqual( [
			[ 'core/heading', { content: 'Filter by attribute', level: 3 } ],
			[
				'woocommerce/attribute-filter',
				{ heading: '', lock: { remove: true } },
			],
		] );
	} );

	it( 'maps Attribute display and Apply controls to preview behavior', async () => {
		const user = userEvent.setup();
		const setAttributes = jest.fn();
		const initialAttributes: React.ComponentProps<
			typeof AttributeFilterBlock
		>[ 'attributes' ] = {
			attributeId: 1,
			displayStyle: 'list',
			heading: '',
			headingLevel: 3,
			isPreview: false,
			queryType: 'or',
			selectType: 'multiple',
			showCounts: false,
			showFilterButton: false,
		};
		const Edit = AttributeFilterEdit as unknown as React.ComponentType<
			Record< string, unknown >
		>;
		const StatefulAttributeFilter = () => {
			const [ attributes, setCurrentAttributes ] =
				useState( initialAttributes );
			const updateAttributes = (
				updates: Partial< typeof attributes >
			) => {
				setAttributes( updates );
				setCurrentAttributes( ( currentAttributes ) => ( {
					...currentAttributes,
					...updates,
				} ) );
			};

			return (
				<Edit
					attributes={ attributes }
					clientId="attribute-filter-client-id"
					setAttributes={ updateAttributes }
				/>
			);
		};

		render( <StatefulAttributeFilter /> );

		for ( const name of [ 'Small', 'Medium', 'Large' ] ) {
			expect(
				await screen.findByRole( 'checkbox', { name } )
			).toBeVisible();
		}
		expect(
			screen.queryByRole( 'button', { name: /apply attribute filter/i } )
		).not.toBeInTheDocument();

		// The @wordpress/element state update falls outside userEvent's act boundary.
		// eslint-disable-next-line testing-library/no-unnecessary-act
		await act( async () => {
			await user.click(
				screen.getByRole( 'radio', { name: 'Dropdown' } )
			);
		} );
		expect( setAttributes ).toHaveBeenCalledTimes( 1 );
		expect( setAttributes ).toHaveBeenCalledWith( {
			displayStyle: 'dropdown',
		} );
		expect( await screen.findByRole( 'combobox' ) ).toBeVisible();

		setAttributes.mockClear();
		// The @wordpress/element state update falls outside userEvent's act boundary.
		// eslint-disable-next-line testing-library/no-unnecessary-act
		await act( async () => {
			await user.click(
				screen.getByRole( 'checkbox', {
					name: "Show 'Apply filters' button",
				} )
			);
		} );
		expect( setAttributes ).toHaveBeenCalledTimes( 1 );
		expect( setAttributes ).toHaveBeenCalledWith( {
			showFilterButton: true,
		} );
		expect(
			await screen.findByRole( 'button', {
				name: /apply attribute filter/i,
			} )
		).toBeVisible();
	} );
} );
