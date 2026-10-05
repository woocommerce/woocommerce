import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

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
import { PAGES_FILTER } from '../controller';
import { _Layout as Layout } from '../index';
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useSelect: vi.fn().mockImplementation( ( callback ) => {
			const selector = {
				getActivePlugins: vi.fn().mockReturnValue( [] ),
				isJetpackConnected: vi.fn().mockReturnValue( false ),
				getInstalledPlugins: vi.fn().mockReturnValue( [] ),
				isResolving: vi.fn().mockReturnValue( false ),
				hasFinishedResolution: vi.fn().mockReturnValue( true ),
				getCurrentUser: vi.fn().mockReturnValue( {
					currentUserCan: vi.fn().mockReturnValue( true ),
				} ),
				getOption: vi.fn().mockReturnValue( 'wc-admin' ),
				getNotices: vi.fn().mockReturnValue( [] ),
				getNotes: vi.fn().mockReturnValue( [] ),
				hasStartedResolution: vi.fn().mockReturnValue( true ),
			};
			return callback( () => selector );
		} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/data', async () => {
	const originalModule = await vi.importActual( '@woocommerce/data' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		...originalModule,
		useUser: vi.fn().mockReturnValue( {
			currentUserCan: () => true,
		} ),
		useUserPreferences: vi.fn().mockReturnValue( {} ),
	} );
} );
vi.mock( '@woocommerce/customer-effort-score', () => {
	const mock = {
		CustomerEffortScoreModalContainer: () => null,
		triggerExitPageCesSurvey: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/components', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/components' ) ),
		Spinner: vi.fn( () => <div>spinner</div> ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '~/activity-panel', () => {
	const mock = null;
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '~/utils/admin-settings', async () => {
	const adminSetting = await vi.importActual( '~/utils/admin-settings' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		...adminSetting,
		getAdminSetting: vi.fn().mockImplementation( ( name, ...args ) => {
			if ( name === 'woocommerceTranslation' ) {
				return 'WooCommerce';
			}
			return adminSetting.getAdminSetting( name, ...args );
		} ),
	} );
} );
vi.mock( '@woocommerce/navigation', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/navigation' ) ),
		getHistory: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const mockedGetHistory = navigation.getHistory;
describe( 'Layout', () => {
	beforeEach( () => {
		vi.spyOn( window, 'wpNavMenuClassChange' ).mockImplementation(
			vi.fn()
		);
		vi.useFakeTimers();
		vi.clearAllMocks();
	} );
	afterEach( () => {
		vi.useRealTimers();
		vi.clearAllTimers();
	} );
	function mockPath( pathname ) {
		const historyMock = {
			listen: vi.fn().mockImplementation( () => vi.fn() ),
			location: {
				pathname,
			},
		};
		mockedGetHistory.mockReturnValue( historyMock );
	}
	it( 'should call recordPageView with correct parameters', () => {
		mockPath( '/analytics/overview' );
		render( <Layout /> );
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
			render( <Layout /> );
			expect( screen.getByText( 'spinner' ) ).toBeInTheDocument();
			expect( screen.queryByText( message ) ).not.toBeInTheDocument();
			act( () => {
				vi.runOnlyPendingTimers();
			} );
			expect( screen.queryByText( 'spinner' ) ).not.toBeInTheDocument();
			expect( screen.getByText( message ) ).toBeInTheDocument();
		} );
		it( 'should render the page added after the initial filter has been run, not show the error message', () => {
			const namespace = `woocommerce/woocommerce/test_${ PAGES_FILTER }`;
			const path = '/test/greeting';
			mockPath( path );
			render( <Layout /> );
			expect( screen.getByText( 'spinner' ) ).toBeInTheDocument();
			expect( screen.queryByText( message ) ).not.toBeInTheDocument();
			expect(
				screen.queryByRole( 'button', {
					name: 'Greet',
				} )
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
				screen.getByRole( 'button', {
					name: 'Greet',
				} )
			).toBeInTheDocument();

			// Clean up the filter as filters are working globally.
			removeFilter( PAGES_FILTER, namespace );
		} );
	} );
} );
