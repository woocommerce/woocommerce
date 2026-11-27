/**
 * External dependencies
 */
import { render, fireEvent, waitFor } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Connect } from '../index.js';

describe( 'Rendering', () => {
	it( 'should render an abort button when the abort handler is provided', async () => {
		const { container } = render(
			<Connect createNotice={ () => {} } onAbort={ () => {} } />
		);

		const buttons = container.querySelectorAll( 'button' );

		expect( buttons.length ).toBe( 2 );
		expect( buttons[ 1 ].textContent ).toBe( 'Abort' );
	} );
} );

describe( 'Retry', () => {
	const originalLocation = window.location;

	beforeEach( () => {
		// jsdom cannot navigate, so the test replaces location with a plain object it can read back.
		delete window.location;
		window.location = {
			href: 'https://store.test/wp-admin/',
			reload: jest.fn(),
		};
	} );

	afterEach( () => {
		window.location = originalLocation;
	} );

	it( 'requests a forced authorization URL and redirects when it succeeds', async () => {
		const requestJetpackAuthUrl = jest.fn().mockResolvedValue( {
			success: true,
			url: 'https://jetpack.test/authorize',
		} );
		const onConnect = jest.fn();
		const { getByText } = render(
			<Connect
				createNotice={ jest.fn() }
				error="Held back after an earlier failure."
				from="woocommerce-services"
				onConnect={ onConnect }
				redirectUrl="https://store.test/wp-admin/admin.php?page=wc-admin"
				requestJetpackAuthUrl={ requestJetpackAuthUrl }
			/>
		);

		fireEvent.click( getByText( 'Retry' ) );

		await waitFor( () =>
			expect( window.location ).toBe( 'https://jetpack.test/authorize' )
		);
		// A forced request skips the connection package's backoff; the URL fetched on render stays unforced.
		expect( requestJetpackAuthUrl ).toHaveBeenCalledWith( {
			redirectUrl: 'https://store.test/wp-admin/admin.php?page=wc-admin',
			from: 'woocommerce-services',
			force: true,
		} );
		expect( onConnect ).toHaveBeenCalled();
		expect( window.location.reload ).toBeUndefined();
	} );

	it( 'shows the returned error and stays on the page when the forced request fails', async () => {
		const createNotice = jest.fn();
		const requestJetpackAuthUrl = jest.fn().mockResolvedValue( {
			success: false,
			errors: [ 'The site could not be registered.' ],
			url: 'https://jetpack.test/authorize',
		} );
		const { getByText } = render(
			<Connect
				createNotice={ createNotice }
				error="Held back after an earlier failure."
				requestJetpackAuthUrl={ requestJetpackAuthUrl }
			/>
		);

		fireEvent.click( getByText( 'Retry' ) );

		await waitFor( () =>
			expect( createNotice ).toHaveBeenCalledWith(
				'error',
				'The site could not be registered.'
			)
		);
		expect( window.location ).toEqual(
			expect.objectContaining( { href: 'https://store.test/wp-admin/' } )
		);
	} );
} );
