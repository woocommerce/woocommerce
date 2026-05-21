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
		throw new Error( 'images_count render not implemented' );
	}

	const Render = fieldExtensions.render as React.ComponentType< {
		item: Partial< ProductEntityRecord >;
	} >;

	return render( React.createElement( Render, { item } ) );
};

describe( 'images_count field', () => {
	it( 'renders the total number of images when present', () => {
		renderSummary( {
			images: [
				{ id: 1 } as ProductEntityRecord[ 'images' ][ number ],
				{ id: 2 } as ProductEntityRecord[ 'images' ][ number ],
				{ id: 3 } as ProductEntityRecord[ 'images' ][ number ],
			],
		} );

		expect( screen.getByText( '3' ) ).toBeInTheDocument();
	} );

	it( 'renders nothing when there are no images', () => {
		const { container } = renderSummary( { images: [] } );

		expect( container ).toBeEmptyDOMElement();
	} );
} );
