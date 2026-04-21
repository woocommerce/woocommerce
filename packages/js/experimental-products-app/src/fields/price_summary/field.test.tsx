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

const renderSummary = ( item: Partial< ProductEntityRecord > ) => {
	if ( ! fieldExtensions.render ) {
		throw new Error( 'price_summary render not implemented' );
	}

	const Render = fieldExtensions.render as React.ComponentType< {
		item: Partial< ProductEntityRecord >;
	} >;

	return render( React.createElement( Render, { item } ) );
};

describe( 'price_summary field', () => {
	beforeEach( () => {
		document.documentElement.lang = 'en-US';
	} );

	it( 'shows the sale price and label when the product is on sale', () => {
		renderSummary( {
			on_sale: true,
			regular_price: '150',
			sale_price: '100',
		} );

		expect( screen.getByText( '$100.00 · On sale' ) ).toBeInTheDocument();
	} );

	it( 'falls back to the regular price when the product is not on sale', () => {
		renderSummary( {
			on_sale: false,
			regular_price: '99',
		} );

		expect( screen.getByText( '$99.00' ) ).toBeInTheDocument();
		expect( screen.queryByText( /On sale/i ) ).not.toBeInTheDocument();
	} );

	it( 'renders nothing when no price data is available', () => {
		const { container } = renderSummary( {} );

		expect( container ).toBeEmptyDOMElement();
	} );
} );
