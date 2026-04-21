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

jest.mock( '../utils/html', () => ( {
	convertHtmlToPlainText: ( text: string ) => text.replace( /<[^>]*>/g, '' ),
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
				field={ { label: 'SEO description' } }
			/>
		),
		onChange,
	};
};

describe( 'seo_description field', () => {
	describe( 'getValue', () => {
		it( 'returns the seo_description from the item', () => {
			const result = fieldExtensions.getValue?.( {
				item: {
					seo_description: 'My SEO Description',
				} as ProductEntityRecord,
			} );
			expect( result ).toBe( 'My SEO Description' );
		} );
	} );

	describe( 'Edit', () => {
		it( 'shows character count with recommended max of 156', () => {
			renderEdit( { seo_description: 'Hello' } );
			expect(
				screen.getByText( '5 of 156 characters used' )
			).toBeInTheDocument();
		} );

		it( 'calls onChange with the seo_description key', () => {
			const { onChange } = renderEdit( { seo_description: '' } );
			const textarea = screen.getByLabelText( 'SEO description' );

			fireEvent.change( textarea, {
				target: { value: 'New description' },
			} );

			expect( onChange ).toHaveBeenCalledWith( {
				seo_description: 'New description',
			} );
		} );

		it( 'enforces maxLength of 156', () => {
			renderEdit( { seo_description: '' } );
			const textarea = screen.getByLabelText( 'SEO description' );
			expect( textarea ).toHaveAttribute( 'maxLength', '156' );
		} );

		it( 'handles undefined seo_description gracefully', () => {
			renderEdit( {} );
			expect(
				screen.getByText( '0 of 156 characters used' )
			).toBeInTheDocument();
		} );
	} );
} );
