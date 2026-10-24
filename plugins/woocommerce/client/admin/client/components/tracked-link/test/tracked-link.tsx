/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { TrackedLink } from '../tracked-link';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

const mockLocation = { href: '' };
Object.defineProperty( window, 'location', {
	value: mockLocation,
	writable: true,
} );

// Stop jsdom from following the link when Link leaves a modified click to the browser.
const preventNavigation = ( event: Event ) => event.preventDefault();

const renderLink = ( props = {} ) =>
	render(
		<TrackedLink
			message="Visit {{Link}}the Marketplace{{/Link}}"
			eventName="test_link_click"
			eventProperties={ { source: 'test' } }
			targetUrl="admin.php?page=wc-admin&path=/extensions"
			{ ...props }
		/>
	);

describe( 'TrackedLink', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockLocation.href = '';
		document.addEventListener( 'click', preventNavigation );
	} );

	afterEach( () => {
		document.removeEventListener( 'click', preventNavigation );
	} );

	it( 'records the event once and navigates on a plain click', () => {
		renderLink();

		fireEvent.click( screen.getByText( 'the Marketplace' ) );

		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith( 'test_link_click', {
			source: 'test',
		} );
		expect( mockLocation.href ).toBe(
			'admin.php?page=wc-admin&path=/extensions'
		);
	} );

	it( 'records the event once on a Cmd-click and leaves the navigation to the browser', () => {
		renderLink();

		fireEvent.click( screen.getByText( 'the Marketplace' ), {
			metaKey: true,
		} );

		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( mockLocation.href ).toBe( '' );
	} );

	it( 'calls onClickCallback instead of recording the event, also on a Cmd-click', () => {
		const onClickCallback = jest.fn();
		renderLink( { onClickCallback } );

		fireEvent.click( screen.getByText( 'the Marketplace' ), {
			metaKey: true,
		} );

		expect( onClickCallback ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).not.toHaveBeenCalled();
	} );
} );
