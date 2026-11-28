/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { recordPageView } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { _EmbedLayout as EmbedLayout } from '../embed';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn().mockReturnValue( {} ),
} ) );

describe( 'EmbedLayout', () => {
	it( 'should call recordPageView with correct parameters', () => {
		window.history.pushState( {}, 'Page Title', '/url?search' );
		render( <EmbedLayout /> );
		expect( recordPageView ).toHaveBeenCalledWith( '/url?search', {
			is_embedded: true,
		} );
	} );
} );
