/**
 * @jest-environment node
 */

jest.mock( '@wordpress/data-controls', () => ( {
	apiFetch: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => ( {
	controls: {
		dispatch: jest.fn(),
		select: jest.fn(),
	},
} ) );

/**
 * External dependencies
 */
import { controls } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	installJetpackAndConnect,
	connectToJetpackWithFailureRedirect,
} from '../actions';
import { STORE_NAME } from '../constants';

// Tests run faster in node env, and we just need access to the window global for this test
global.window = {
	location: {
		href: '',
	} as Location,
} as Window & typeof globalThis;

function getAdminLink( path: string ) {
	return path;
}

describe( 'installJetPackAndConnect', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'installs jetpack, then activates it', () => {
		const installer = installJetpackAndConnect( () => '', getAdminLink );

		// Run to first yield
		installer.next();

		// Run the install
		installer.next();

		expect( controls.dispatch ).toHaveBeenCalledWith(
			STORE_NAME,
			'installPlugins',
			[ 'jetpack' ]
		);

		// Run the activate
		installer.next();

		expect( controls.dispatch ).toHaveBeenCalledWith(
			STORE_NAME,
			'activatePlugins',
			[ 'jetpack' ]
		);
	} );

	it( 'calls the passed error handler if an exception is thrown into the generator', () => {
		const errorHandler = jest.fn();
		const installer = installJetpackAndConnect(
			errorHandler,
			getAdminLink
		);

		// Run to first yield
		installer.next();

		// Throw error into generator
		installer.throw( new Error( 'Failed!' ) );

		expect( errorHandler ).toHaveBeenCalledWith( 'Failed!' );
	} );

	it( 'redirects to the connect url if there are no errors', () => {
		const installer = installJetpackAndConnect( jest.fn(), getAdminLink );

		// Run to yield any errors from getJetpackConnectUrl
		installer.next();
		installer.next();
		installer.next();
		installer.next( 'https://example.com' );
		installer.next();

		expect( global.window.location.href ).toBe( 'https://example.com' );
	} );
} );

describe( 'connectToJetpack', () => {
	it( 'redirects to the failure url if there is an error', () => {
		const connect = connectToJetpackWithFailureRedirect(
			'https://example.com/failure',
			jest.fn(),
			getAdminLink
		);

		connect.next();
		connect.throw( new Error( 'Failed' ) );
		connect.next();

		expect( global.window.location.href ).toBe(
			'https://example.com/failure'
		);
	} );

	it( 'redirects to the jetpack url if there is no error', () => {
		const connect = connectToJetpackWithFailureRedirect(
			'https://example.com/failure',
			jest.fn(),
			getAdminLink
		);

		connect.next();
		connect.next( 'https://example.com/success' );
		connect.next();
		connect.next();

		expect( global.window.location.href ).toBe(
			'https://example.com/success'
		);
	} );

	it( 'calls the passed error handler if an exception is thrown into the generator', () => {
		const errorHandler = jest.fn();
		const connect = connectToJetpackWithFailureRedirect(
			'',
			errorHandler,
			getAdminLink
		);

		// Run to first yield
		connect.next();

		// Throw error into generator
		connect.throw( new Error( 'Failed!' ) );

		expect( errorHandler ).toHaveBeenCalledWith( 'Failed!' );
	} );
} );
