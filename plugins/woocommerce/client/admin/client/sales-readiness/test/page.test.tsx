/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import SalesReadiness from '../index';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

describe( 'SalesReadiness', () => {
	it( 'shows no spinner while the page loads its view', () => {
		( apiFetch as unknown as jest.Mock ).mockReturnValue(
			new Promise( () => undefined )
		);
		const { container } = render( <SalesReadiness /> );

		expect( container.querySelector( '.components-spinner' ) ).toBeNull();
		expect( container ).toHaveTextContent( 'AI shopping health' );
	} );
} );
