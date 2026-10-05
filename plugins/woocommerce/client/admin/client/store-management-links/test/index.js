import { describe, expect, it, vi } from 'vitest';
vi.mock( '@woocommerce/tracks', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/tracks' ) ),
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: () => 'https://fake-site-url.com',
} ) );
vi.mock( '@woocommerce/settings', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/settings' ) ),
		getSetting: vi.fn( () => 'https://fake-site-url.com' ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { render, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	StoreManagementLinks,
	getLinkTypeAndHref,
	getItemsByCategory,
	generateExtensionLinks,
} from '..';
describe( 'getLinkTypeAndHref', () => {
	it( 'generates the correct link for wc-admin links', () => {
		const result = getLinkTypeAndHref( {
			type: 'wc-admin',
			path: 'foo/bar',
		} );
		expect( result.linkType ).toEqual( 'wc-admin' );
		expect( result.href ).toEqual(
			'admin.php?page=wc-admin&path=%2Ffoo/bar'
		);
	} );
	it( 'generates the correct link for wp-admin links', () => {
		const result = getLinkTypeAndHref( {
			type: 'wp-admin',
			path: '/foo/bar',
		} );
		expect( result.linkType ).toEqual( 'wp-admin' );
		expect( result.href ).toEqual( '/foo/bar' );
	} );
	it( 'generates the correct link for wc-settings links', () => {
		const result = getLinkTypeAndHref( {
			type: 'wc-settings',
			tab: 'foo',
		} );
		expect( result.linkType ).toEqual( 'wp-admin' );
		expect( result.href ).toEqual( 'admin.php?page=wc-settings&tab=foo' );
	} );
	it( 'generates the an external link if there is no provided type', () => {
		const result = getLinkTypeAndHref( {
			href: 'http://example.com',
		} );
		expect( result.linkType ).toEqual( 'external' );
		expect( result.href ).toEqual( 'http://example.com' );
	} );
} );
describe( 'StoreManagementLinks', () => {
	it( 'records a track when a link is clicked', async () => {
		const { queryByText } = render( <StoreManagementLinks /> );
		const linkDetails = getItemsByCategory( 'fakeUrl' )[ 0 ].items[ 0 ];
		await act( async () => {
			userEvent.click( queryByText( linkDetails.title ) );
		} );
		expect( recordEvent ).toHaveBeenCalledWith( 'home_quick_links_click', {
			task_name: linkDetails.listItemTag,
		} );
	} );
} );
describe( 'generateExtensionLinks', () => {
	it( 'filters out external links', () => {
		expect(
			generateExtensionLinks( [
				{
					href: 'https://example.com',
					title: 'external link',
					icon: <div>hi</div>,
				},
			] )
		).toEqual( [] );
	} );
	it( 'generates a valid link for relative links', () => {
		const validFullUrl = {
			href: 'http://localhost/foo/bar',
			title: 'external link',
			icon: <div>hi</div>,
		};
		const validRelativeUrl = {
			href: '/foo/bar',
			title: 'external link',
			icon: <div>hi</div>,
		};
		expect( generateExtensionLinks( [ validFullUrl ] ) ).toEqual( [
			{
				icon: validFullUrl.icon,
				link: {
					href: validFullUrl.href,
					linkType: 'wp-admin',
				},
				title: validFullUrl.title,
				listItemTag: 'quick-links-extension-link',
			},
		] );
		expect( generateExtensionLinks( [ validRelativeUrl ] ) ).toEqual( [
			{
				icon: validRelativeUrl.icon,
				link: {
					href: validRelativeUrl.href,
					linkType: 'wp-admin',
				},
				title: validRelativeUrl.title,
				listItemTag: 'quick-links-extension-link',
			},
		] );
	} );
} );
