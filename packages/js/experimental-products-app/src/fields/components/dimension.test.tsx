/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import type { DataFormControlProps } from '@wordpress/dataviews';
import React from 'react';

jest.mock( '@wordpress/core-data', () => ( {
	useEntityRecord: jest.fn( () => ( {
		record: {
			values: {
				woocommerce_dimension_unit: 'cm',
			},
		},
		isResolving: false,
	} ) ),
} ) );

jest.mock( '@wordpress/ui', () => {
	const ReactActual = jest.requireActual( 'react' );

	return {
		InputControl: ( {
			label,
			onChange,
			placeholder,
			suffix,
			value,
		}: {
			label: string;
			onChange: React.ChangeEventHandler< HTMLInputElement >;
			placeholder?: string;
			suffix?: React.ReactNode;
			value?: string;
		} ) =>
			ReactActual.createElement(
				'label',
				null,
				label,
				ReactActual.createElement( 'input', {
					'aria-label': label,
					onChange,
					placeholder,
					value: value ?? '',
				} ),
				suffix
			),
		InputLayout: {
			Slot: ( { children }: { children?: React.ReactNode } ) =>
				ReactActual.createElement( 'span', null, children ),
		},
	};
} );

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { createDimensionField } from './dimension';

describe( 'dimension field', () => {
	const renderEdit = (
		data: ProductEntityRecord,
		onChange = jest.fn(),
		placeholder?: string
	) => {
		const fieldExtensions = createDimensionField( 'length' );

		if ( ! fieldExtensions.Edit ) {
			throw new Error( 'dimension edit not implemented' );
		}

		const Edit = fieldExtensions.Edit as React.ComponentType<
			DataFormControlProps< ProductEntityRecord >
		>;

		const view = render(
			<Edit
				data={ data }
				field={
					{
						...fieldExtensions,
						id: 'length',
						label: 'Length',
						placeholder,
					} as DataFormControlProps< ProductEntityRecord >[ 'field' ]
				}
				onChange={ onChange }
			/>
		);

		return {
			...view,
			onChange,
		};
	};

	it( 'renders an empty input when dimensions are missing', () => {
		const { onChange } = renderEdit( {
			id: 12,
			name: 'Beanie',
		} as ProductEntityRecord );
		const input = screen.getByLabelText( 'Length' );

		expect( input ).toHaveValue( '' );

		fireEvent.change( input, {
			target: {
				value: '12',
			},
		} );

		expect( onChange ).toHaveBeenCalledWith( {
			dimensions: {
				length: '12',
			},
		} );
	} );

	it( 'emits only the edited dimension', () => {
		const { onChange } = renderEdit( {
			id: 12,
			name: 'Beanie',
			dimensions: {
				length: '12',
				width: '',
				height: '',
			},
		} as ProductEntityRecord );
		const input = screen.getByLabelText( 'Length' );

		fireEvent.change( input, {
			target: {
				value: '15',
			},
		} );

		expect( onChange ).toHaveBeenCalledWith( {
			dimensions: {
				length: '15',
			},
		} );
	} );

	it( 'renders the mixed placeholder when provided', () => {
		renderEdit(
			{
				id: 12,
				name: 'Beanie',
				dimensions: {
					length: '',
					width: '',
					height: '',
				},
			} as ProductEntityRecord,
			jest.fn(),
			'Mixed'
		);

		expect( screen.getByLabelText( 'Length' ) ).toHaveAttribute(
			'placeholder',
			'Mixed'
		);
	} );
} );
