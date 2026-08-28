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
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	installJetpackAndConnect,
	connectToJetpackWithFailureRedirect,
	installPlugins,
	activatePlugins,
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

/**
 * Drives a redux-routine generator to completion the way the runtime would:
 * the `apiFetch` yield resolves to (or throws) `apiResult`, yielded generators
 * are run in turn, every other yield resolves to undefined. Returns the error
 * the generator threw, or null when it completed.
 */
function runUntilThrow(
	generator: Generator< unknown, unknown, unknown >,
	apiResult: unknown,
	apiThrows = false
): Error | null {
	const drive = ( gen: Generator< unknown, unknown, unknown > ) => {
		let step = gen.next();
		while ( ! step.done ) {
			const value = step.value as
				| { type?: string; next?: unknown }
				| undefined;
			if ( value?.type === 'API_FETCH' ) {
				step = apiThrows
					? gen.throw( apiResult )
					: gen.next( apiResult );
			} else if ( typeof value?.next === 'function' ) {
				drive( value as Generator< unknown, unknown, unknown > );
				step = gen.next();
			} else {
				step = gen.next();
			}
		}
	};

	try {
		drive( generator );
		return null;
	} catch ( e ) {
		return e as Error;
	}
}

describe( 'installPlugins error message', () => {
	beforeEach( () => {
		( apiFetch as jest.Mock ).mockReset();
		( apiFetch as jest.Mock ).mockImplementation( () => ( {
			type: 'API_FETCH',
		} ) );
	} );

	it( 'frames a single server reason as one sentence, naming the plugin once', () => {
		const error = runUntilThrow(
			installPlugins( [ 'visa-acceptance-solutions' ] ),
			{
				data: { installed: [], results: {} },
				errors: {
					errors: {
						'visa-acceptance-solutions': [
							'The package could not be installed. The PHP version on your server is 8.1.34, however the uploaded plugin requires 8.2.0.',
						],
					},
				},
				success: false,
				message: '',
			}
		);

		expect( error?.message ).toBe(
			'Could not install visa-acceptance-solutions. The package could not be installed. The PHP version on your server is 8.1.34, however the uploaded plugin requires 8.2.0.'
		);
		expect( error?.message ).not.toContain( 'plugin, ' );
	} );

	it( 'frames a permission error without repeating the plugin name', () => {
		const error = runUntilThrow(
			installPlugins( [ 'woocommerce-payments' ] ),
			{
				code: 'woocommerce_rest_cannot_update',
				message: 'Sorry',
				data: { status: 403 },
			},
			true
		);

		expect( error?.message ).toBe(
			'Could not install woocommerce-payments. You do not have permissions to manage plugins. Please contact your site administrator.'
		);
	} );

	it( 'frames a connection error message', () => {
		const error = runUntilThrow(
			installPlugins( [ 'woocommerce-payments' ] ),
			new Error( 'Failed to fetch' ),
			true
		);

		expect( error?.message ).toBe(
			'Could not install woocommerce-payments. Failed to fetch'
		);
	} );

	it( 'attributes each reason to its plugin in the plural frame', () => {
		const error = runUntilThrow( installPlugins( [ 'a', 'b' ] ), {
			data: { installed: [], results: {} },
			errors: { errors: { a: [ 'Reason A.' ], b: [ 'Reason B.' ] } },
			success: false,
			message: '',
		} );

		// The reasons no longer carry their own slug, so the frame has to supply it.
		expect( error?.message ).toBe(
			'Could not install the following plugins: a, b. a: Reason A. \nb: Reason B.'
		);
	} );

	it( 'names only the plugins that actually failed', () => {
		const error = runUntilThrow( installPlugins( [ 'a', 'b' ] ), {
			data: { installed: [ 'a' ], results: {} },
			errors: { errors: { b: [ 'Reason B.' ] } },
			success: false,
			message: '',
		} );

		expect( error?.message ).toBe( 'Could not install b. Reason B.' );
	} );

	it( 'tolerates message lists the endpoint returned as bare strings', () => {
		// The old formatting stringified the whole object, so a non-array value never threw.
		const error = runUntilThrow( installPlugins( [ 'a', 'b' ] ), {
			data: { installed: [], results: {} },
			errors: { errors: { a: 'Reason A.', b: 'Reason B.' } },
			success: false,
			message: '',
		} );

		expect( error?.message ).toBe(
			'Could not install the following plugins: a, b. a: Reason A. \nb: Reason B.'
		);
	} );

	it( 'drops values that are not messages and keeps the ones that are', () => {
		const error = runUntilThrow( installPlugins( [ 'a', 'b' ] ), {
			data: { installed: [], results: {} },
			errors: {
				errors: { a: { inner: 'not a message' }, b: [ 'Reason B.' ] },
			},
			success: false,
			message: '',
		} );

		expect( error?.message ).toBe( 'Could not install b. Reason B.' );
	} );

	it( 'falls back to the raw payload when nothing readable remains', () => {
		const error = runUntilThrow( installPlugins( [ 'a' ] ), {
			data: { installed: [], results: {} },
			errors: { errors: { a: [ null ] } },
			success: false,
			message: '',
		} );

		expect( error?.message ).toBe( 'Could not install a. {"a":[null]}' );
	} );

	it( 'keeps a failure for a plugin the server added to the request', () => {
		// woocommerce_admin_plugins_pre_install can add plugins server-side; their failures
		// come back in the same payload and must not vanish from the notice.
		const error = runUntilThrow( installPlugins( [ 'a' ] ), {
			data: { installed: [], results: {} },
			errors: { errors: { a: [ 'Reason A.' ], added: [ 'Reason B.' ] } },
			success: false,
			message: '',
		} );

		expect( error?.message ).toBe(
			'Could not install the following plugins: a, added. a: Reason A. \nadded: Reason B.'
		);
	} );

	it( 'exposes the unframed reason on the error', () => {
		const error = runUntilThrow( installPlugins( [ 'a' ] ), {
			data: { installed: [], results: {} },
			errors: { errors: { a: [ 'Reason A.' ] } },
			success: false,
			message: '',
		} );

		expect( ( error as { reason?: string } )?.reason ).toBe( 'Reason A.' );
	} );

	it( 'reports the step that failed on the error', () => {
		const error = runUntilThrow( installPlugins( [ 'a' ] ), {
			data: { installed: [], results: {} },
			errors: { errors: { a: [ 'Reason A.' ] } },
			success: false,
			message: '',
		} );

		expect( ( error as { actionType?: string } )?.actionType ).toBe(
			'install'
		);
	} );
} );

describe( 'activatePlugins error message', () => {
	beforeEach( () => {
		( apiFetch as jest.Mock ).mockReset();
		( apiFetch as jest.Mock ).mockImplementation( () => ( {
			type: 'API_FETCH',
		} ) );
	} );

	it( 'reports activate as the failed step so callers do not infer it from stale status', () => {
		const error = runUntilThrow( activatePlugins( [ 'a' ] ), {
			data: { activated: [], active: [] },
			errors: { errors: { a: [ 'Reason A.' ] } },
			success: false,
			message: '',
		} );

		expect( error?.message ).toBe( 'Could not activate a. Reason A.' );
		expect( ( error as { actionType?: string } )?.actionType ).toBe(
			'activate'
		);
	} );
} );
