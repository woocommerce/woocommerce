import { beforeEach, describe, expect, it, vi } from 'vitest';
let { mockSettings } = vi.hoisted( () => {
	const mockSettings = {};
	return {
		mockSettings,
	};
} );

/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import React from 'react';
vi.mock( '../../../../utils/admin-settings', () => {
	const mock = {
		ADMIN_URL: '',
		getAdminSetting: vi.fn( () => mockSettings ),
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
vi.mock( '../../../utils/functions', () => {
	const mock = {
		connectUrl: vi.fn( () => 'http://example.test/connect' ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

/**
 * Internal dependencies
 */
import HeaderAccount from '../header-account';
function openMenu( page: string ) {
	render( <HeaderAccount page={ page } /> );
	fireEvent.click(
		screen.getByRole( 'button', {
			name: 'User options',
		} )
	);
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
			screen.getByRole( 'menuitem', {
				name: /Connect account/,
			} )
		).toHaveAttribute( 'href', 'http://example.test/connect' );
		const accountItem = screen.getByRole( 'menuitem', {
			name: /Your WooCommerce\.com account\s*\(opens in a new tab\)/,
		} );
		expect( accountItem ).toHaveAttribute(
			'href',
			'https://woocommerce.com/my-account/'
		);
		expect( accountItem ).toHaveAttribute( 'target', '_blank' );
		expect( accountItem ).toHaveAttribute( 'rel', 'noopener noreferrer' );
		expect(
			screen.getByRole( 'menuitem', {
				name: /Connect account/,
			} )
		).not.toHaveAttribute( 'target' );
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
		const emailItem = screen.getByRole( 'menuitem', {
			name: /merchant@example\.com\s*\(opens in a new tab\)/,
		} );
		expect( emailItem ).toHaveAttribute(
			'href',
			'https://woocommerce.com/my-account/'
		);
		expect( emailItem ).toHaveAttribute( 'target', '_blank' );
		expect( emailItem ).toHaveAttribute( 'rel', 'noopener noreferrer' );
		expect(
			screen.queryByRole( 'menuitem', {
				name: /Your WooCommerce.com account/,
			} )
		).toBeNull();
		expect(
			screen.getByRole( 'menuitem', {
				name: 'Disconnect account',
			} )
		).toBeInTheDocument();
	} );
	it( 'does not show the account link outside the marketplace when not connected', () => {
		openMenu( 'wc-admin' );
		expect(
			screen.getByRole( 'menuitem', {
				name: /Connect account/,
			} )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'menuitem', {
				name: /Your WooCommerce.com account/,
			} )
		).toBeNull();
	} );
} );
