import { afterEach, describe, expect, it, vi, type Mock } from 'vitest';

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
vi.mock( '@wordpress/block-editor', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/block-editor' ) ),
		useBlockProps: vi.fn( ( props = {} ) => props ),
		BlockControls: vi.fn( ( { children } ) => <div>{ children }</div> ),
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
		withSpokenMessages: vi.fn( ( Component ) => Component ),
		// The WordPress control scheduler is browser-owned; these adapters retain
		// only the semantic radio/checkbox boundary for the real Edit callbacks.
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
	} );
} );
vi.mock( '@woocommerce/base-context/hooks', async () => {
	const attributeTerms = [
		{
			id: 11,
			name: 'Small',
			slug: 'small',
		},
		{
			id: 12,
			name: 'Medium',
			slug: 'medium',
		},
		{
			id: 13,
			name: 'Large',
			slug: 'large',
		},
	];
	const collectionData = {
		price_range: null,
		attribute_counts: [
			{
				term: 11,
				count: 1,
			},
			{
				term: 12,
				count: 1,
			},
			{
				term: 13,
				count: 1,
			},
		],
		rating_counts: null,
		stock_status_counts: null,
	};
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		...( await vi.importActual( '@woocommerce/base-context/hooks' ) ),
		useCollection: vi.fn( () => ( {
			results: attributeTerms,
			isLoading: false,
		} ) ),
		useCollectionData: vi.fn( () => ( {
			data: collectionData,
			isLoading: false,
		} ) ),
		useQueryStateByContext: vi.fn( () => [ {} ] ),
		useQueryStateByKey: vi.fn( () => [ [], vi.fn() ] ),
	} );
} );
vi.mock( '@woocommerce/settings', async () => {
	const attributes = [
		{
			attribute_id: '1',
			attribute_name: 'size',
			attribute_label: 'Size',
			attribute_orderby: 'menu_order',
		},
	];
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		...( await vi.importActual( '@woocommerce/settings' ) ),
		getSetting: vi.fn( ( key, defaultValue ) =>
			key === 'attributes' ? attributes : defaultValue
		),
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
		const innerBlocksProps = ( InnerBlocks as unknown as Mock ).mock
			.calls[ 0 ][ 0 ];
		expect( innerBlocksProps.allowedBlocks ).toEqual( [ 'core/heading' ] );
		expect( innerBlocksProps.template ).toEqual( [
			[
				'core/heading',
				{
					content: 'Filter by attribute',
					level: 3,
				},
			],
			[
				'woocommerce/attribute-filter',
				{
					heading: '',
					lock: {
						remove: true,
					},
				},
			],
		] );
	} );
	it( 'maps Attribute display and Apply controls to preview behavior', async () => {
		const user = userEvent.setup();
		const setAttributes = vi.fn();
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
				await screen.findByRole( 'checkbox', {
					name,
				} )
			).toBeVisible();
		}
		expect(
			screen.queryByRole( 'button', {
				name: /apply attribute filter/i,
			} )
		).not.toBeInTheDocument();

		// The @wordpress/element state update falls outside userEvent's act boundary.
		// eslint-disable-next-line testing-library/no-unnecessary-act
		await act( async () => {
			await user.click(
				screen.getByRole( 'radio', {
					name: 'Dropdown',
				} )
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
