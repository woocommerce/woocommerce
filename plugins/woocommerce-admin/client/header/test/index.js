/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { Header, getPageTitle } from '../index.js';

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSetting() {
		return 'Fake Site Title';
	},
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	...jest.requireActual( '@woocommerce/tracks' ),
	recordEvent: jest.fn(),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	useUserPreferences: () => ( {
		updateUserPreferences: () => {},
		// mock to disable the mobile app banner while testing this component
		android_app_banner_dismissed: 'yes',
	} ),
} ) );

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useSelect: jest.fn().mockReturnValue( {
			menuItems: [],
		} ),
	};
} );

global.window.wcNavigation = {};

const encodedBreadcrumb = [
	[ 'admin.php?page=wc-settings', 'Settings' ],
	'Accounts &amp; Privacy',
];

describe( 'Header', () => {
	beforeEach( () => {
		// Mock RAF to be synchronous for testing
		jest.spyOn( window, 'requestAnimationFrame' ).mockImplementation(
			( cb ) => {
				cb();
			}
		);

		// Mock user preferences to avoid testing the MobileAppBanner here

		// Disable the ActivityPanel so it isn't tested here
		window.wcAdminFeatures[ 'activity-panels' ] = false;
	} );

	afterEach( () => {
		window.requestAnimationFrame.mockRestore();
	} );

	it( 'should render decoded breadcrumb name', () => {
		const { queryByText } = render(
			<Header sections={ encodedBreadcrumb } isEmbedded={ true } />
		);
		expect( queryByText( 'Accounts &amp; Privacy' ) ).toBe( null );
		expect( queryByText( 'Accounts & Privacy' ) ).not.toBe( null );
	} );

	it( 'should only have the is-scrolled class if the page is scrolled', () => {
		const { container } = render(
			<Header sections={ encodedBreadcrumb } isEmbedded={ false } />
		);

		const topLevelElement = container.firstChild;
		expect( Object.values( topLevelElement.classList ) ).not.toContain(
			'is-scrolled'
		);
		Object.defineProperty( window, 'pageYOffset', {
			value: 200,
			writable: false,
		} );
		fireEvent.scroll( window, { target: { scrollY: 200 } } );
		expect( Object.values( topLevelElement.classList ) ).toContain(
			'is-scrolled'
		);
	} );

	it( 'correctly updates the document title to reflect the navigation state', () => {
		render(
			<Header sections={ encodedBreadcrumb } isEmbedded={ false } />
		);

		expect( document.title ).toBe(
			'Accounts & Privacy ‹ Settings ‹ Fake Site Title — WooCommerce'
		);
	} );
} );

describe( 'getPageTitle', () => {
	test( 'should get page title as the last item if section length is less than 3', () => {
		const sections = [ 'Payments' ];
		expect( getPageTitle( sections ) ).toBe( 'Payments' );
	} );

	test( "should get page title as the second item's second element if section length is 3 or more and second item has a second element", () => {
		const sections = [
			[ 'admin.php?page=wc-admin', 'WooCommerce' ],
			[ 'admin.php?page=wc-settings', 'Settings' ],
			'Payments',
		];
		expect( getPageTitle( sections ) ).toBe( 'Settings' );
	} );

	test( "should get page title as the last item if section length is 3 or more but second item doesn't have a second element", () => {
		const sections = [
			[ 'admin.php?page=wc-admin', 'WooCommerce' ],
			'Payments',
		];
		expect( getPageTitle( sections ) ).toBe( 'Payments' );
	} );
} );
