/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import type { DataFormControlProps } from '@wordpress/dataviews';
import React from 'react';

jest.mock( '@wordpress/ui', () => {
	const ReactActual = jest.requireActual( 'react' );

	return {
		Badge: ( {
			children,
			intent,
		}: {
			children?: React.ReactNode;
			intent?: string;
		} ) =>
			ReactActual.createElement(
				'span',
				{ 'data-intent': intent },
				children
			),
		SelectControl: ( {
			items,
			label,
			onValueChange,
			placeholder,
			value,
		}: {
			items: Array< { label: string; value: string } >;
			label: string;
			onValueChange: ( option: { value: string } ) => void;
			placeholder?: string;
			value?: { label: string; value: string };
		} ) =>
			ReactActual.createElement(
				'fieldset',
				null,
				ReactActual.createElement( 'legend', null, label ),
				ReactActual.createElement(
					'div',
					{ 'data-testid': 'selected-option' },
					value?.label ?? placeholder ?? ''
				),
				items.map( ( item ) =>
					ReactActual.createElement(
						'button',
						{
							key: item.value,
							onClick: () => onValueChange( item ),
							type: 'button',
						},
						item.label
					)
				)
			),
	};
} );

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';
import {
	fieldExtensions,
	getVariationActiveValue,
	isVariationActive,
} from './field';

const buildVariation = (
	overrides: Partial< ProductEntityRecord > = {}
): ProductEntityRecord =>
	( {
		id: 34,
		parent_id: 12,
		type: 'variation',
		status: 'publish',
		price: '12',
		images: [],
		categories: [],
		tags: [],
		...overrides,
	} ) as ProductEntityRecord;

describe( 'variation_active field', () => {
	it.each( [
		[ 'active', { status: 'publish', price: '12' }, true ],
		[ 'active with a zero price', { status: 'publish', price: '0' }, true ],
		[ 'inactive when private', { status: 'private', price: '12' }, false ],
		[ 'inactive without a price', { status: 'publish', price: '' }, false ],
	] as const )(
		'identifies an %s variation',
		( _label, overrides, expected ) => {
			expect( isVariationActive( buildVariation( overrides ) ) ).toBe(
				expected
			);
		}
	);

	it( 'returns active and inactive values for DataViews cells', () => {
		expect(
			fieldExtensions.getValue?.( {
				item: buildVariation( { status: 'publish', price: '12' } ),
			} as never )
		).toBe( 'active' );
		expect(
			fieldExtensions.getValue?.( {
				item: buildVariation( { status: 'publish', price: '' } ),
			} as never )
		).toBe( 'inactive' );
		expect(
			getVariationActiveValue(
				buildVariation( { status: 'private', price: '12' } )
			)
		).toBe( 'inactive' );
	} );

	it( 'renders active and inactive badges for the table', () => {
		if ( ! fieldExtensions.render ) {
			throw new Error( 'variation_active render not implemented' );
		}

		const Render = fieldExtensions.render as React.ComponentType< {
			item: ProductEntityRecord;
		} >;
		const { rerender } = render(
			<Render
				item={ buildVariation( { status: 'publish', price: '12' } ) }
			/>
		);

		expect( screen.getByText( 'Active' ) ).toBeInTheDocument();

		rerender(
			<Render
				item={ buildVariation( { status: 'publish', price: '' } ) }
			/>
		);

		expect( screen.getByText( 'Inactive' ) ).toBeInTheDocument();
	} );

	it( 'maps active and inactive edits to variation post statuses', () => {
		if ( ! fieldExtensions.Edit ) {
			throw new Error( 'variation_active edit not implemented' );
		}

		const onChange = jest.fn();
		const Edit = fieldExtensions.Edit as React.ComponentType<
			DataFormControlProps< ProductEntityRecord >
		>;

		render(
			<Edit
				data={ buildVariation( { status: 'publish', price: '' } ) }
				field={
					{
						...fieldExtensions,
						id: 'variation_active',
					} as DataFormControlProps< ProductEntityRecord >[ 'field' ]
				}
				onChange={ onChange }
			/>
		);

		fireEvent.click( screen.getByRole( 'button', { name: 'Active' } ) );
		expect( onChange ).toHaveBeenCalledWith( { status: 'publish' } );

		fireEvent.click( screen.getByRole( 'button', { name: 'Inactive' } ) );
		expect( onChange ).toHaveBeenCalledWith( { status: 'private' } );
	} );

	it( 'shows the mixed placeholder for bulk edits with different active values', () => {
		if ( ! fieldExtensions.Edit ) {
			throw new Error( 'variation_active edit not implemented' );
		}

		const Edit = fieldExtensions.Edit as React.ComponentType<
			DataFormControlProps< ProductEntityRecord >
		>;

		render(
			<Edit
				data={
					{
						...buildVariation(),
						variation_active: undefined,
					} as ProductEntityRecord & Record< string, unknown >
				}
				field={
					{
						...fieldExtensions,
						id: 'variation_active',
						placeholder: 'Mixed',
					} as DataFormControlProps< ProductEntityRecord >[ 'field' ]
				}
				onChange={ jest.fn() }
			/>
		);

		expect( screen.getByTestId( 'selected-option' ) ).toHaveTextContent(
			'Mixed'
		);
	} );
} );
