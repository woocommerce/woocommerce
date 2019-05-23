/** @format */
/**
 * WooCommerce dependencies
 */
import { stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { updateLinkHref } from '../controller';

describe( 'updateLinkHref', () => {
	const timeExcludedScreens = [ 'devdocs', 'stock', 'settings', 'customers' ];

	const REPORT_URL =
		'http://example.com/wp-admin/admin.php?page=wc-admin#/analytics/orders?period=today&compare=previous_year';
	const DASHBOARD_URL =
		'http://example.com/wp-admin/admin.php?page=wc-admin#/?period=week&compare=previous_year';
	const DASHBOARD_URL_NO_HASH = 'http://example.com/wp-admin/admin.php?page=wc-admin';
	const WOO_URL = 'http://example.com/wp-admin/edit.php?post_type=shop_coupon';
	const WP_ADMIN_URL = 'http://example.com/wp-admin/edit-comments.php';

	const nextQuery = stringifyQuery( {
		fruit: 'apple',
		dish: 'cobbler',
	} );

	it( 'should update report urls', () => {
		const item = { href: REPORT_URL };
		updateLinkHref( item, nextQuery, timeExcludedScreens );

		expect( item.href ).toBe(
			'http://example.com/wp-admin/admin.php?page=wc-admin#/analytics/orders?fruit=apple&dish=cobbler'
		);
	} );

	it( 'should update dashboard urls', () => {
		const item = { href: DASHBOARD_URL };
		updateLinkHref( item, nextQuery, timeExcludedScreens );

		expect( item.href ).toBe(
			'http://example.com/wp-admin/admin.php?page=wc-admin#/?fruit=apple&dish=cobbler'
		);
	} );

	it( 'should update dashboard urls with no hash', () => {
		const item = { href: DASHBOARD_URL_NO_HASH };
		updateLinkHref( item, nextQuery, timeExcludedScreens );

		expect( item.href ).toBe(
			'http://example.com/wp-admin/admin.php?page=wc-admin#/?fruit=apple&dish=cobbler'
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
