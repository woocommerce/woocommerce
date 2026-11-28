/**
 * External dependencies
 */
import { render, fireEvent, screen } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import ConnectAccountPage from '..';
import { getAdminSetting } from '~/utils/admin-settings';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn(),
	useSelect: jest.fn(),
} ) );
jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );
jest.mock( '~/utils/admin-settings', () => ( { getAdminSetting: jest.fn() } ) );
jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( '@wordpress/element' ),
	useState: jest.fn(),
} ) );
jest.mock( '../apms', () => jest.fn().mockReturnValue( null ) );
jest.mock( '../banner', () =>
	jest.fn().mockImplementation( ( { handleSetup } ) => (
		<div>
			<button onClick={ handleSetup }>Handle Setup Button</button>
		</div>
	) )
);

describe( 'Connect Account Page', () => {
	const setupMocks = () => {
		( useDispatch as jest.Mock ).mockReturnValue( {
			updateOptions: jest.fn(),
			installAndActivatePlugins: jest.fn(),
		} );
		( useState as jest.Mock )
			.mockImplementationOnce( () => [ false, jest.fn() ] ) // isSubmitted state
			.mockImplementationOnce( () => [ '', jest.fn() ] ) // errorMessage state
			.mockImplementationOnce( () => [ new Set(), jest.fn() ] ); // enabledApms state
		( useSelect as jest.Mock ).mockReturnValue( {
			isJetpackConnected: true,
			connectUrl: '',
		} );
		( getAdminSetting as jest.Mock ).mockReturnValue( {
			id: 'incentiveId',
		} );
	};

	beforeEach( () => {
		jest.clearAllMocks();
		setupMocks();
	} );

	it( 'should fire custom page_view track when viewing', async () => {
		render( <ConnectAccountPage /> );
		expect( recordEvent ).toHaveBeenCalledWith( 'page_view', {
			path: 'payments_connect_core_test',
			incentive_id: 'incentiveId',
			source: 'wcadmin',
		} );
	} );

	it( 'should trigger wcpay_connect_account_clicked event when clicking connect', async () => {
		render( <ConnectAccountPage /> );
		fireEvent.click( screen.getByText( 'Handle Setup Button' ) );
		expect( recordEvent ).toHaveBeenNthCalledWith(
			2,
			'wcpay_connect_account_clicked',
			{
				wpcom_connection: 'Yes',
				incentive_id: 'incentiveId',
				path: 'payments_connect_core_test',
				source: 'wcadmin',
			}
		);
	} );
} );
