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
} );
