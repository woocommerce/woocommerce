/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

import React from 'react';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { fieldExtensions } from './field';

jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
	sprintf: ( format: string, ...args: number[] ) =>
		format.replace( /%(\d)\$d/g, ( _, i ) =>
			String( args[ Number( i ) - 1 ] )
		),
} ) );

type EditProps = {
	data: Partial< ProductEntityRecord >;
	onChange: jest.Mock;
	field: { label: string };
};

const renderEdit = (
	data: Partial< ProductEntityRecord >,
	onChange = jest.fn()
) => {
	const Edit =
		fieldExtensions.Edit as unknown as React.ComponentType< EditProps >;
	return {
		...render(
			<Edit
				data={ data }
				onChange={ onChange }
				field={ { label: 'SEO title' } }
			/>
		),
		onChange,
	};
};

describe( 'seo_title field', () => {
	describe( 'getValue', () => {
		it( 'returns the seo_title from the item', () => {
			const result = fieldExtensions.getValue?.( {
				item: { seo_title: 'My SEO Title' } as ProductEntityRecord,
			} );
			expect( result ).toBe( 'My SEO Title' );
		} );
	} );

	describe( 'Edit', () => {
		it( 'shows character count with recommended max of 70', () => {
			renderEdit( { seo_title: 'Hello' } );
			expect(
				screen.getByText( '5 of 70 characters used' )
			).toBeInTheDocument();
		} );

		it( 'calls onChange with the seo_title key', () => {
			const { onChange } = renderEdit( { seo_title: '' } );
			const input = screen.getByLabelText( 'SEO title' );

			fireEvent.change( input, { target: { value: 'New title' } } );

			expect( onChange ).toHaveBeenCalledWith( {
				seo_title: 'New title',
			} );
		} );

		it( 'enforces maxLength of 70', () => {
			renderEdit( { seo_title: '' } );
			const input = screen.getByLabelText( 'SEO title' );
			expect( input ).toHaveAttribute( 'maxLength', '70' );
		} );

		it( 'handles undefined seo_title gracefully', () => {
			renderEdit( {} );
			expect(
				screen.getByText( '0 of 70 characters used' )
			).toBeInTheDocument();
		} );
	} );
} );
