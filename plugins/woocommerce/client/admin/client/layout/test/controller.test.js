/**
 * External dependencies
 */
import * as navigation from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { updateLinkHref } from '../controller';

describe( 'updateLinkHref', () => {
	const timeExcludedScreens = [ 'stock', 'settings', 'customers' ];

	const REPORT_URL =
		'http://example.com/wp-admin/admin.php?page=wc-admin&path=/analytics/orders';
	const DASHBOARD_URL = 'http://example.com/wp-admin/admin.php?page=wc-admin';
	const REPORT_URL_TIME_EXCLUDED =
		'http://example.com/wp-admin/admin.php?page=wc-admin&path=/analytics/settings';
	const WOO_URL =
		'http://example.com/wp-admin/edit.php?post_type=shop_coupon';
	const WP_ADMIN_URL = 'http://example.com/wp-admin/edit-comments.php';

	const nextQuery = {
		fruit: 'apple',
		dish: 'cobbler',
	};

	beforeEach( () => {
		jest.restoreAllMocks();
	} );

	it( 'should update report urls', () => {
		const item = { href: REPORT_URL };
		updateLinkHref( item, nextQuery, timeExcludedScreens );
		const encodedPath = encodeURIComponent( '/analytics/orders' );

		expect( item.href ).toBe(
			`admin.php?page=wc-admin&path=${ encodedPath }&fruit=apple&dish=cobbler`
		);
	} );

	it( 'should update dashboard urls', () => {
		const item = { href: DASHBOARD_URL };
		updateLinkHref( item, nextQuery, timeExcludedScreens );

		expect( item.href ).toBe(
			'admin.php?page=wc-admin&fruit=apple&dish=cobbler'
		);
	} );

	it( 'should not add the nextQuery to a time excluded screen', () => {
		const item = { href: REPORT_URL_TIME_EXCLUDED };
		updateLinkHref( item, nextQuery, timeExcludedScreens );
		const encodedPath = encodeURIComponent( '/analytics/settings' );

		expect( item.href ).toBe(
			`admin.php?page=wc-admin&path=${ encodedPath }`
		);
	} );

	it( 'should not update WooCommerce urls', () => {
		const item = { href: WOO_URL };
		updateLinkHref( item, nextQuery, timeExcludedScreens );

		expect( item.href ).toBe( WOO_URL );
	} );

	it( 'should not update wp-admin urls', () => {
		const item = { href: WP_ADMIN_URL };
		updateLinkHref( item, nextQuery, timeExcludedScreens );

		expect( item.href ).toBe( WP_ADMIN_URL );
	} );

	it( 'should filter out undefined query values', () => {
		const item = { href: REPORT_URL };
		updateLinkHref(
			item,
			{ ...nextQuery, test: undefined, anotherParam: undefined },
			timeExcludedScreens
		);
		const encodedPath = encodeURIComponent( '/analytics/orders' );

		expect( item.href ).toBe(
			`admin.php?page=wc-admin&path=${ encodedPath }&fruit=apple&dish=cobbler`
		);
	} );

	it( 'should not prevent default when Command key is pressed', () => {
		const item = { href: REPORT_URL };
		const spyGetHistory = jest.spyOn( navigation, 'getHistory' );
		const event = {
			ctrlKey: false,
			metaKey: true,
			preventDefault: jest.fn(),
		};

		updateLinkHref( item, nextQuery, timeExcludedScreens );

		item.onclick( event );
		expect( spyGetHistory ).not.toHaveBeenCalled();
		expect( event.preventDefault ).not.toHaveBeenCalled();
	} );

	it( 'should not prevent default when Control key is pressed', () => {
		const item = { href: REPORT_URL };
		const spyGetHistory = jest.spyOn( navigation, 'getHistory' );
		const event = {
			ctrlKey: true,
			metaKey: false,
			preventDefault: jest.fn(),
		};

		updateLinkHref( item, nextQuery, timeExcludedScreens );

		item.onclick( event );
		expect( spyGetHistory ).not.toHaveBeenCalled();
		expect( event.preventDefault ).not.toHaveBeenCalled();
	} );

	it( 'should prevent default on normal clicks', () => {
		const item = { href: REPORT_URL };
		const spyGetHistory = jest.spyOn( navigation, 'getHistory' );
		const event = {
			ctrlKey: false,
			metaKey: false,
			preventDefault: jest.fn(),
		};

		updateLinkHref( item, nextQuery, timeExcludedScreens );

		item.onclick( event );
		expect( spyGetHistory ).toHaveBeenCalledTimes( 1 );
		expect( event.preventDefault ).toHaveBeenCalledTimes( 1 );
	} );
} );
