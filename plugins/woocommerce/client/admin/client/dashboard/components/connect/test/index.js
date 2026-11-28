/**
 * External dependencies
 */
import { render, fireEvent, waitFor } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	createReduxStore,
	createRegistry,
	RegistryProvider,
} from '@wordpress/data';
import { onboardingStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import ConnectedConnect, { Connect } from '../index.js';

describe( 'Rendering', () => {
	it( 'should render an abort button when the abort handler is provided', async () => {
		const { container } = render(
			<Connect
				createNotice={ () => {} }
				onAbort={ () => {} }
				requestJetpackAuthUrl={ jest.fn() }
			/>
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

const createTestRegistry = () => {
	const registry = createRegistry();
	registry.register( onboardingStore );
	// The component sends its notices here; the test only needs the store to exist.
	registry.register(
		createReduxStore( 'core/notices', {
			reducer: ( state = [] ) => state,
			actions: {
				createNotice: () => ( { type: 'CREATE_NOTICE' } ),
			},
		} )
	);
	return registry;
};

describe( 'Retry with the onboarding store', () => {
	it( 'sends a new forced request on every click, not the cached result', async () => {
		const forcedPaths = [];
		apiFetch.setFetchHandler( async ( { path } ) => {
			if ( path.includes( 'force=true' ) ) {
				forcedPaths.push( path );
			}
			return {
				success: false,
				errors: [ 'Held back after an earlier failure.' ],
				url: 'https://jetpack.test/authorize',
			};
		} );
		const { findByText } = render(
			<RegistryProvider value={ createTestRegistry() }>
				<ConnectedConnect redirectUrl="https://store.test/wp-admin/" />
			</RegistryProvider>
		);
		const retry = await findByText( 'Retry' );

		fireEvent.click( retry );
		await waitFor( () => expect( forcedPaths ).toHaveLength( 1 ) );
		await waitFor( () => expect( retry ).not.toHaveClass( 'is-busy' ) );
		fireEvent.click( retry );

		await waitFor( () => expect( forcedPaths ).toHaveLength( 2 ) );
	} );

	it( 'offers Retry when a held-back registration reports no message', async () => {
		// After a 5xx failure the connection package keeps no message, so the response has an empty error.
		apiFetch.setFetchHandler( async () => ( {
			success: false,
			errors: [ '' ],
			url: 'https://jetpack.test/authorize?scope',
		} ) );
		const { findByText, queryByText } = render(
			<RegistryProvider value={ createTestRegistry() }>
				<ConnectedConnect redirectUrl="https://store.test/wp-admin/" />
			</RegistryProvider>
		);

		expect( await findByText( 'Retry' ) ).toBeInTheDocument();
		expect( queryByText( 'Connect' ) ).not.toBeInTheDocument();
	} );
} );
