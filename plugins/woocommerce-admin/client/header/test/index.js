jest.mock( '@woocommerce/wc-admin-settings', () => ( {
	...jest.requireActual( '@woocommerce/wc-admin-settings' ),
	getSetting() {
		return 'Fake Site Title';
	},
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	...jest.requireActual( '@woocommerce/tracks' ),
	recordEvent: jest.fn(),
} ) );

/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { Header } from '../index.js';

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
		expect( topLevelElement.classList ).not.toContain( 'is-scrolled' );
		fireEvent.scroll( window, { target: { scrollY: 200 } } );
		expect( topLevelElement.classList ).toContain( 'is-scrolled' );
	} );

	it( 'correctly updates the document title to reflect the navigation state', () => {
		render(
			<Header sections={ encodedBreadcrumb } isEmbedded={ false } />
		);

		expect( document.title ).toBe(
			'Accounts & Privacy ‹ Settings ‹ Fake Site Title — WooCommerce'
		);
	} );

	it( 'tracks link clicks with recordEvent', () => {
		const { queryByRole } = render(
			<Header sections={ encodedBreadcrumb } isEmbedded={ false } />
		);

		const firstLink = queryByRole( 'link' );
		fireEvent.click( firstLink );

		expect( recordEvent ).toBeCalledWith( 'navbar_breadcrumb_click', {
			href: firstLink.getAttribute( 'href' ),
			text: firstLink.innerText,
		} );
	} );
} );
