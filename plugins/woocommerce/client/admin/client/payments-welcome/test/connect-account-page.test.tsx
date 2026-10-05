import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

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
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useDispatch: vi.fn(),
		useSelect: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/tracks', () => {
	const mock = {
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '~/utils/admin-settings', () => {
	const mock = {
		getAdminSetting: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/element', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/element' ) ),
		useState: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../apms', () => {
	const mock = vi.fn().mockReturnValue( null );
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '../banner', () => {
	const mock = vi.fn().mockImplementation( ( { handleSetup } ) => (
		<div>
			<button onClick={ handleSetup }>Handle Setup Button</button>
		</div>
	) );
	return {
		default: mock,
		...mock,
	};
} );
describe( 'Connect Account Page', () => {
	const setupMocks = () => {
		( useDispatch as Mock ).mockReturnValue( {
			updateOptions: vi.fn(),
			installAndActivatePlugins: vi.fn(),
		} );
		( useState as Mock )
			.mockImplementationOnce( () => [ false, vi.fn() ] ) // isSubmitted state
			.mockImplementationOnce( () => [ '', vi.fn() ] ) // errorMessage state
			.mockImplementationOnce( () => [ new Set(), vi.fn() ] ); // enabledApms state
		( useSelect as Mock ).mockReturnValue( {
			isJetpackConnected: true,
			connectUrl: '',
		} );
		( getAdminSetting as Mock ).mockReturnValue( {
			id: 'incentiveId',
		} );
	};
	beforeEach( () => {
		vi.clearAllMocks();
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
