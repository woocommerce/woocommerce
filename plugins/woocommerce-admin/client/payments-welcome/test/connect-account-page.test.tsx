/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import ConnectAccountPage from '..';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

describe( 'Connect Account Page', () => {
	it( 'should fire custom page_view track when viewing', async () => {
		render( <ConnectAccountPage /> );

		expect( recordEvent ).toHaveBeenCalledWith( 'page_view', {
			path: 'payments_connect_core_test',
			incentive_id: undefined,
		} );
	} );
} );
