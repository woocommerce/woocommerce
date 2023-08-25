/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { recordPageView } from '@woocommerce/tracks';
import { createHigherOrderComponent, pure } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { updateLinkHref } from '../controller';
import { EmbedLayout } from '../index';

jest.mock( '@woocommerce/customer-effort-score', () => ( {
	CustomerEffortScoreModalContainer: jest.fn().mockReturnValue( <div></div> ),
	triggerExitPageCesSurvey: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );
	return {
		...Object.keys( originalModule ).reduce( ( mocked, key ) => {
			try {
				mocked[ key ] = originalModule[ key ];
			} catch ( e ) {
				mocked[ key ] = jest.fn();
			}
			return mocked;
		}, {} ),
		useSelect: jest.fn().mockReturnValue( {} ),
		withSelect: jest.fn().mockReturnValue( ( mapSelectToProps ) =>
			createHigherOrderComponent(
				( WrappedComponent ) =>
					pure( ( ownProps ) => {
						const mapSelect = ( select, registry ) =>
							mapSelectToProps( select, ownProps, registry );
						return (
							<WrappedComponent
								{ ...ownProps }
								{ ...mapSelect( jest.fn(), {} ) }
							/>
						);
					} ),
				'withSelect'
			)
		),
	};
} );

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
} );

describe( 'Layout', () => {
	it.skip( 'should call recordPageView with correct parameters', () => {
		window.history.pushState( {}, 'Page Title', '/url?search' );
		render( <EmbedLayout /> );
		expect( recordPageView ).toHaveBeenCalledWith( '/url?search', {
			has_navigation: true,
			is_embedded: true,
		} );
	} );
} );
