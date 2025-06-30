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

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn().mockImplementation( ( callback ) => {
		const selector = {
			getActivePlugins: jest.fn().mockReturnValue( [] ),
			isJetpackConnected: jest.fn().mockReturnValue( false ),
			getInstalledPlugins: jest.fn().mockReturnValue( [] ),
			isResolving: jest.fn().mockReturnValue( false ),
			hasFinishedResolution: jest.fn().mockReturnValue( true ),
			getCurrentUser: jest.fn().mockReturnValue( {
				currentUserCan: jest.fn().mockReturnValue( true ),
			} ),
			getOption: jest.fn().mockReturnValue( 'wc-admin' ),
			getNotices: jest.fn().mockReturnValue( [] ),
			getNotes: jest.fn().mockReturnValue( [] ),
			hasStartedResolution: jest.fn().mockReturnValue( true ),
		};
		return callback( () => selector );
	} ),
} ) );

jest.mock( '@woocommerce/data', () => {
	const originalModule = jest.requireActual( '@woocommerce/data' );
	return {
		...originalModule,
		useUser: jest.fn().mockReturnValue( { currentUserCan: () => true } ),
	};
} );

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

describe( 'Layout', () => {
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
				jest.runOnlyPendingTimers();
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
