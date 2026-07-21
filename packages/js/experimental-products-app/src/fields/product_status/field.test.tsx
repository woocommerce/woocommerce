/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
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
		SelectControl: () => null,
	};
} );

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';
import { fieldExtensions } from './field';

const buildProduct = (
	overrides: Partial< ProductEntityRecord > = {}
): ProductEntityRecord =>
	( {
		id: 12,
		type: 'simple',
		status: 'publish',
		price: '12',
		images: [],
		categories: [],
		tags: [],
		...overrides,
	} ) as ProductEntityRecord;

const renderStatus = ( item: ProductEntityRecord ) => {
	if ( ! fieldExtensions.render ) {
		throw new Error( 'product_status render not implemented' );
	}

	const Render = fieldExtensions.render as React.ComponentType< {
		item: ProductEntityRecord;
	} >;

	return render( <Render item={ item } /> );
};

describe( 'product_status field', () => {
	it( 'renders product post statuses for products', () => {
		renderStatus( buildProduct( { status: 'draft' } ) );

		expect( screen.getByText( 'Draft' ) ).toBeInTheDocument();
		expect(
			fieldExtensions.getValue?.( {
				item: buildProduct( { status: 'draft' } ),
			} as never )
		).toBe( 'draft' );
	} );

	it( 'renders active status for variation rows in DataViews tables', () => {
		renderStatus(
			buildProduct( {
				parent_id: 12,
				type: 'variation',
				status: 'publish',
				price: '12',
			} )
		);

		expect( screen.getByText( 'Active' ) ).toBeInTheDocument();
		expect(
			fieldExtensions.getValue?.( {
				item: buildProduct( {
					parent_id: 12,
					type: 'variation',
					status: 'publish',
					price: '12',
				} ),
			} as never )
		).toBe( 'active' );
	} );

	it( 'renders inactive status for variation rows without a price', () => {
		renderStatus(
			buildProduct( {
				parent_id: 12,
				type: 'variation',
				status: 'publish',
				price: '',
			} )
		);

		expect( screen.getByText( 'Inactive' ) ).toBeInTheDocument();
		expect(
			fieldExtensions.getValue?.( {
				item: buildProduct( {
					parent_id: 12,
					type: 'variation',
					status: 'publish',
					price: '',
				} ),
			} as never )
		).toBe( 'inactive' );
	} );
} );
