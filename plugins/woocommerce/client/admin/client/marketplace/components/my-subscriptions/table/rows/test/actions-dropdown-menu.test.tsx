/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import React from 'react';

jest.mock( '../../../../../../utils/admin-settings', () => ( {
	ADMIN_URL: 'https://store.test/wp-admin/',
	getAdminSetting: jest.fn( () => ( {
		reviewURL:
			'https://store.test/wp-admin/admin.php?page=wc-addons&section=helper&wc-helper-review=1&wc-helper-nonce=abc',
	} ) ),
} ) );

/**
 * Internal dependencies
 */
import { getAdminSetting } from '../../../../../../utils/admin-settings';
import ActionsDropdownMenu from '../actions-dropdown-menu';
import { Subscription } from '../../../types';

function openMenu( subscription: Partial< Subscription > ) {
	render(
		<ActionsDropdownMenu
			subscription={
				{
					product_key: 'test-key',
					is_shared: false,
					...subscription,
				} as Subscription
			}
		/>
	);
	fireEvent.click( screen.getByRole( 'button', { name: 'Actions' } ) );
}

describe( 'ActionsDropdownMenu', () => {
	it.each( [
		[
			'shows the review item when WooCommerce.com allows it',
			{ can_review: true },
			true,
		],
		[ 'hides the review item without can_review', {}, false ],
		[
			'hides the review item on rows without a product key',
			{ can_review: true, product_key: '' },
			false,
		],
	] )( '%s', ( _, subscription, expected ) => {
		openMenu( subscription );

		expect(
			screen.queryByRole( 'menuitem', {
				name: 'Leave a review',
			} ) !== null
		).toBe( expected );
	} );

	it( 'opens the review route for the row in a new tab', () => {
		const open = jest.spyOn( window, 'open' ).mockImplementation();
		openMenu( { can_review: true } );

		fireEvent.click(
			screen.getByRole( 'menuitem', {
				name: 'Leave a review',
			} )
		);

		const url = new URL( open.mock.calls[ 0 ][ 0 ] as string );
		expect( url.searchParams.get( 'wc-helper-review' ) ).toBe( '1' );
		expect( url.searchParams.get( 'wc-helper-product-key' ) ).toBe(
			'test-key'
		);
		expect( open.mock.calls[ 0 ].slice( 1 ) ).toEqual( [
			'_blank',
			'noopener',
		] );
		open.mockRestore();
	} );

	it( 'hides the review item when the page has no review route', () => {
		( getAdminSetting as jest.Mock ).mockReturnValueOnce( {} );
		openMenu( { can_review: true } );

		expect(
			screen.queryByRole( 'menuitem', {
				name: 'Leave a review',
			} )
		).toBeNull();
	} );
} );
