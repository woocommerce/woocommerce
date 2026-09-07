/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import React from 'react';

let mockSettings: Record< string, unknown > = {};

jest.mock( '../../../../utils/admin-settings', () => ( {
	getAdminSetting: jest.fn( () => mockSettings ),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '../../../utils/functions', () => ( {
	connectUrl: jest.fn( () => 'http://example.test/connect' ),
} ) );

/**
 * Internal dependencies
 */
import HeaderAccount from '../header-account';

function openMenu( page: string ) {
	render( <HeaderAccount page={ page } /> );
	fireEvent.click( screen.getByRole( 'button', { name: 'User options' } ) );
}

describe( 'HeaderAccount menu', () => {
	beforeEach( () => {
		mockSettings = {
			isConnected: false,
			userEmail: '',
			userAvatar: '',
		};
	} );

	it( 'offers connect and a link to the WooCommerce.com account when not connected in the marketplace', () => {
		openMenu( 'wc-addons' );

		expect(
			screen.getByRole( 'menuitem', { name: /Connect account/ } )
		).toHaveAttribute( 'href', 'http://example.test/connect' );
		expect(
			screen.getByRole( 'menuitem', {
				name: 'Your WooCommerce.com account',
			} )
		).toHaveAttribute( 'href', 'https://woocommerce.com/my-account/' );
		expect(
			document.querySelector( 'a[href*="my-dashboard"]' )
		).toBeNull();
	} );

	it( 'links the connected email to the WooCommerce.com account and hides the extra item', () => {
		mockSettings = {
			isConnected: true,
			userEmail: 'merchant@example.com',
			userAvatar: '',
		};
		openMenu( 'wc-addons' );

		expect(
			screen.getByRole( 'menuitem', { name: 'merchant@example.com' } )
		).toHaveAttribute( 'href', 'https://woocommerce.com/my-account/' );
		expect(
			screen.queryByRole( 'menuitem', {
				name: 'Your WooCommerce.com account',
			} )
		).toBeNull();
		expect(
			screen.getByRole( 'menuitem', { name: 'Disconnect account' } )
		).toBeInTheDocument();
	} );

	it( 'does not show the account link outside the marketplace when not connected', () => {
		openMenu( 'wc-admin' );

		expect(
			screen.getByRole( 'menuitem', { name: /Connect account/ } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'menuitem', {
				name: 'Your WooCommerce.com account',
			} )
		).toBeNull();
	} );
} );
