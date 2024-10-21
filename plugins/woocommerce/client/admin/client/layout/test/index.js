/**
 * External dependencies
 */
import { render, screen, act } from '@testing-library/react';
import { addFilter, removeFilter } from '@wordpress/hooks';
import { recordPageView } from '@woocommerce/tracks';
import * as navigation from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { updateLinkHref, PAGES_FILTER } from '../controller';
import { EmbedLayout, PageLayout } from '../index';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn().mockReturnValue( {} ),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	useUser: jest.fn().mockReturnValue( { currentUserCan: () => true } ),
} ) );

jest.mock( '@woocommerce/customer-effort-score', () => ( {
	CustomerEffortScoreModalContainer: () => null,
	triggerExitPageCesSurvey: jest.fn(),
} ) );

jest.mock( '@woocommerce/components', () => ( {
	...jest.requireActual( '@woocommerce/components' ),
	Spinner: jest.fn( () => <div>spinner</div> ),
} ) );

jest.mock( '~/activity-panel', () => null );

jest.mock( '~/utils/admin-settings', () => {
	const adminSetting = jest.requireActual( '~/utils/admin-settings' );
	return {
		...adminSetting,
		getAdminSetting: jest.fn().mockImplementation( ( name, ...args ) => {
			if ( name === 'woocommerceTranslation' ) {
				return 'WooCommerce';
			}
			return adminSetting.getAdminSetting( name, ...args );
		} ),
	};
} );

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getHistory: jest.fn(),
} ) );

const mockedGetHistory = navigation.getHistory;

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

describe( 'EmbedLayout', () => {
	it( 'should call recordPageView with correct parameters', () => {
		window.history.pushState( {}, 'Page Title', '/url?search' );
		render( <EmbedLayout /> );
		expect( recordPageView ).toHaveBeenCalledWith( '/url?search', {
			is_embedded: true,
		} );
	} );
} );

describe( 'PageLayout', () => {
	beforeEach( () => {
		jest.spyOn( window, 'wpNavMenuClassChange' ).mockImplementation(
			jest.fn()
		);
		jest.useFakeTimers();
		jest.clearAllMocks();
	} );

	afterEach( () => {
		jest.useRealTimers();
		jest.clearAllTimers();
	} );

	function mockPath( pathname ) {
		const historyMock = {
			listen: jest.fn().mockImplementation( () => jest.fn() ),
			location: { pathname },
		};
		mockedGetHistory.mockReturnValue( historyMock );
	}

	it( 'should call recordPageView with correct parameters', () => {
		mockPath( '/analytics/overview' );
		render( <PageLayout /> );
		expect( recordPageView ).toHaveBeenCalledWith( 'analytics_overview', {
			jetpack_active: false,
			jetpack_connected: false,
			jetpack_installed: false,
		} );
	} );

	describe( 'NoMatch', () => {
		const message = 'Sorry, you are not allowed to access this page.';

		it( 'should render a loading spinner first and then the error message after the delay', () => {
			mockPath( '/incorrect-path' );
			render( <PageLayout /> );

			expect( screen.getByText( 'spinner' ) ).toBeInTheDocument();
			expect( screen.queryByText( message ) ).not.toBeInTheDocument();

			act( () => {
				jest.runOnlyPendingTimers();
			} );

			expect( screen.queryByText( 'spinner' ) ).not.toBeInTheDocument();
			expect( screen.getByText( message ) ).toBeInTheDocument();
		} );

		it( 'should render the page added after the initial filter has been run, not show the error message', () => {
			const namespace = `woocommerce/woocommerce/test_${ PAGES_FILTER }`;
			const path = '/test/greeting';

			mockPath( path );
			render( <PageLayout /> );

			expect( screen.getByText( 'spinner' ) ).toBeInTheDocument();
			expect( screen.queryByText( message ) ).not.toBeInTheDocument();
			expect(
				screen.queryByRole( 'button', { name: 'Greet' } )
			).not.toBeInTheDocument();

			act( () => {
				addFilter( PAGES_FILTER, namespace, ( pages ) => {
					return [
						...pages,
						{
							breadcrumbs: [ 'Greeting' ],
							container: () => <button>Greet</button>,
							path,
						},
					];
				} );
			} );

			expect( screen.queryByText( 'spinner' ) ).not.toBeInTheDocument();
			expect( screen.queryByText( message ) ).not.toBeInTheDocument();
			expect(
				screen.getByRole( 'button', { name: 'Greet' } )
			).toBeInTheDocument();

			// Clean up the filter as filters are working globally.
			removeFilter( PAGES_FILTER, namespace );
		} );
	} );
} );
