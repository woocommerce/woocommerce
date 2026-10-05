import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { recordPageView } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { _EmbedLayout as EmbedLayout } from '../embed';
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useSelect: vi.fn().mockReturnValue( {} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'EmbedLayout', () => {
	it( 'should call recordPageView with correct parameters', () => {
		window.history.pushState( {}, 'Page Title', '/url?search' );
		render( <EmbedLayout /> );
		expect( recordPageView ).toHaveBeenCalledWith( '/url?search', {
			is_embedded: true,
		} );
	} );
} );
