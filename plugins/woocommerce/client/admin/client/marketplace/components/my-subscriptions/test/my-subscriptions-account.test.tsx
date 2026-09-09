/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import React from 'react';
import apiFetch from '@wordpress/api-fetch';
import { speak } from '@wordpress/a11y';

let mockSettings: Record< string, unknown > = {};

jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: jest.fn( () => mockSettings ),
} ) );

jest.mock( '@wordpress/api-fetch', () =>
	jest.fn( () => Promise.resolve( {} ) )
);

jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );

jest.mock( '../../../utils/functions', () => ( {
	connectUrl: jest.fn( () => 'https://example.com/disconnect' ),
} ) );

jest.mock( '../../header-account/header-account-modal', () => ( {
	__esModule: true,
	default: ( { disconnectURL }: { disconnectURL: string } ) => (
		<div data-testid="disconnect-modal">{ disconnectURL }</div>
	),
} ) );

/**
 * Internal dependencies
 */
import MySubscriptionsAccount from '../my-subscriptions-account';

const apiFetchMock = apiFetch as unknown as jest.Mock;

describe( 'MySubscriptionsAccount', () => {
	beforeEach( () => {
		mockSettings = {
			isConnected: true,
			userEmail: 'merchant@example.com',
			dismissNoticeNonce: 'test-nonce',
			show_connected_account_notice: true,
		};
		apiFetchMock.mockClear();
		( speak as jest.Mock ).mockClear();
	} );

	it( 'renders nothing when the store is not connected', () => {
		mockSettings = { ...mockSettings, isConnected: false };
		const { container } = render( <MySubscriptionsAccount /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders nothing once the notice has been dismissed server-side', () => {
		mockSettings = {
			...mockSettings,
			show_connected_account_notice: false,
		};
		const { container } = render( <MySubscriptionsAccount /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'shows the connected email with view, inline disconnect and dismiss actions', () => {
		render( <MySubscriptionsAccount /> );

		expect(
			screen.getByRole( 'heading', {
				name: 'Connected to merchant@example.com',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'View account' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'disconnect your account' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Dismiss this notice' } )
		).toBeInTheDocument();
	} );

	it( 'puts the dismiss button first in the tab order', () => {
		const { container } = render( <MySubscriptionsAccount /> );

		const focusables = container.querySelectorAll( 'button, a' );

		expect( focusables[ 0 ] ).toHaveAccessibleName( 'Dismiss this notice' );
	} );

	it( 'opens the disconnect confirmation from the inline link', () => {
		render( <MySubscriptionsAccount /> );

		expect( screen.queryByTestId( 'disconnect-modal' ) ).toBeNull();

		fireEvent.click(
			screen.getByRole( 'button', { name: 'disconnect your account' } )
		);

		expect( screen.getByTestId( 'disconnect-modal' ) ).toHaveTextContent(
			'https://example.com/disconnect'
		);
	} );

	// Keep this test last: dismissal is remembered at module scope for the
	// rest of the page load, so later renders in this file would be empty.
	it( 'dismisses the notice, persists it, announces it and hands off focus', () => {
		const onDismiss = jest.fn();
		render( <MySubscriptionsAccount onDismiss={ onDismiss } /> );

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Dismiss this notice' } )
		);

		expect( screen.queryByRole( 'heading' ) ).toBeNull();
		expect( apiFetchMock ).toHaveBeenCalledWith( {
			path: '/wc-admin/notice/dismiss',
			method: 'POST',
			data: {
				notice_id: 'woo-connected-account-notice',
				dismiss_notice_nonce: 'test-nonce',
			},
		} );
		expect( speak ).toHaveBeenCalledWith(
			'Connected account notice dismissed.',
			'polite'
		);
		expect( onDismiss ).toHaveBeenCalledTimes( 1 );
	} );
} );
