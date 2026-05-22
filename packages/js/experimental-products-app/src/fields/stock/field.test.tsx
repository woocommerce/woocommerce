/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import React from 'react';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';
import { fieldExtensions } from './field';

const renderStock = ( item: Partial< ProductEntityRecord > ) => {
	if ( ! fieldExtensions.render ) {
		throw new Error( 'stock render not implemented' );
	}

	const Render = fieldExtensions.render as React.ComponentType< {
		item: Partial< ProductEntityRecord >;
	} >;

	return render( React.createElement( Render, { item } ) );
};

describe( 'stock field render', () => {
	it( 'renders "In stock" when in stock without quantity management', () => {
		renderStock( { stock_status: 'instock', manage_stock: false } );
		expect( screen.getByText( 'In stock' ) ).toBeInTheDocument();
	} );

	it( 'renders quantity when in stock with manage_stock and a valid qty', () => {
		renderStock( {
			stock_status: 'instock',
			manage_stock: true,
			stock_quantity: 5,
		} );
		expect( screen.getByText( '5 in stock' ) ).toBeInTheDocument();
	} );

	it( 'renders singular form for qty of 1', () => {
		renderStock( {
			stock_status: 'instock',
			manage_stock: true,
			stock_quantity: 1,
		} );
		expect( screen.getByText( '1 in stock' ) ).toBeInTheDocument();
	} );

	it( 'renders "In stock" when manage_stock is true but qty is 0', () => {
		renderStock( {
			stock_status: 'instock',
			manage_stock: true,
			stock_quantity: 0,
		} );
		expect( screen.getByText( 'In stock' ) ).toBeInTheDocument();
	} );

	it( 'renders "In stock" when qty is NaN', () => {
		renderStock( {
			stock_status: 'instock',
			manage_stock: true,
			stock_quantity: NaN,
		} );
		expect( screen.getByText( 'In stock' ) ).toBeInTheDocument();
	} );

	it( 'renders "In stock" when qty is Infinity', () => {
		renderStock( {
			stock_status: 'instock',
			manage_stock: true,
			stock_quantity: Infinity,
		} );
		expect( screen.getByText( 'In stock' ) ).toBeInTheDocument();
	} );

	it( 'renders "Out of stock"', () => {
		renderStock( { stock_status: 'outofstock' } );
		expect( screen.getByText( 'Out of stock' ) ).toBeInTheDocument();
	} );

	it( 'renders "On backorder"', () => {
		renderStock( { stock_status: 'onbackorder' } );
		expect( screen.getByText( 'On backorder' ) ).toBeInTheDocument();
	} );

	it( 'renders nothing for an invalid stock status', () => {
		const { container } = renderStock( {
			stock_status: 'unknown' as ProductEntityRecord[ 'stock_status' ],
		} );
		expect( container ).toBeEmptyDOMElement();
	} );
} );
