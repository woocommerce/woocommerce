/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';
import { createActor, toPromise, waitFor } from 'xstate5';

/**
 * Internal dependencies
 */
import {
	coreProfilerMachineActions,
	coreProfilerMachineActors,
	coreProfilerStateMachineDefinition,
} from '../index';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getQuery: jest.fn( () => ( {} ) ),
	updateQueryString: jest.fn(),
} ) );

const mockOnboardingSetting: { skipOnboardingFallbackUrl?: string } = {};
jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: jest.fn( ( name: string, fallback: unknown ) =>
		name === 'onboarding' ? mockOnboardingSetting : fallback
	),
} ) );

// Simulates REST for WooCommerce stores: actions and resolvers listed here reject, all others resolve.
const mockJetpackAuthResult = {
	success: true,
	url: 'https://jetpack.test/authorize',
};
const mockFailingRequests = new Set< string >();
const mockStoreCalls: string[] = [];
function mockWooStore( resolvedValues: Record< string, unknown > ) {
	return new Proxy(
		{},
		{
			get: ( _target, name: string ) => () => {
				mockStoreCalls.push( name );
				if (
					! mockFailingRequests.has( name ) &&
					! mockFailingRequests.has( '*' )
				) {
					return Promise.resolve( resolvedValues[ name ] );
				}
				// apiFetch rejects with a plain error object, not an Error.
				const request = Promise.reject( {
					code: 'rest_no_route',
					message: `${ name } failed`,
				} );
				// The profiler fires some writes without awaiting them; awaiting callers still see the rejection.
				request.catch( () => undefined );
				return request;
			},
		}
	);
}
jest.mock( '@wordpress/data', () => {
	const actual = jest.requireActual( '@wordpress/data' );
	const isWooStore = ( store: string | { name: string } ) =>
		( typeof store === 'string' ? store : store.name ).startsWith( 'wc/' );
	return {
		...actual,
		dispatch: ( store: { name: string } ) =>
			isWooStore( store ) ? mockWooStore( {} ) : actual.dispatch( store ),
		resolveSelect: ( store: { name: string } ) =>
			isWooStore( store )
				? mockWooStore( {
						getCountries: [],
						getFreeExtensions: [],
						getCoreProfilerCompletedSteps: {},
						getJetpackAuthUrl: mockJetpackAuthResult,
					} )
				: actual.resolveSelect( store ),
	};
} );

const FALLBACK_URL =
	'http://example.test/wp-admin/admin-post.php?action=woocommerce_skip_onboarding_setup';
const HOME_PATH = 'admin.php?page=wc-admin';

const startProfiler = () => {
	const actor = createActor(
		coreProfilerStateMachineDefinition.provide( {
			// @ts-expect-error Same action map CoreProfilerController provides.
			actions: coreProfilerMachineActions,
			actors: coreProfilerMachineActors,
			guards: {
				hasStepInUrl: ( _, params ) =>
					( getQuery() as { step?: string } ).step ===
					( params as { step: string } ).step,
			},
		} )
	);
	actor.start();
	return actor;
};

const skipToBusinessLocationAndSubmit = async (
	actor: ReturnType< typeof startProfiler >
) => {
	await waitFor( actor, ( snapshot ) =>
		snapshot.matches( { introOptIn: 'introOptIn' } )
	);
	actor.send( {
		type: 'INTRO_SKIPPED',
		payload: { optInDataSharing: false },
	} );
	await waitFor( actor, ( snapshot ) =>
		snapshot.matches( { skipGuidedSetup: 'skipFlowBusinessLocation' } )
	);
	actor.send( {
		type: 'BUSINESS_LOCATION_COMPLETED',
		payload: { storeLocation: 'DE:DE-BE' },
	} );
};

const waitForNavigation = async () => {
	// The skip flow shows its loader for two 1500 ms steps before leaving.
	for ( let i = 0; i < 40 && window.location.href === 'about:blank'; i++ ) {
		await jest.advanceTimersByTimeAsync( 100 );
	}
	return window.location.href;
};

describe( 'Core Profiler skip flow when REST requests fail', () => {
	const originalLocation = window.location;
	let consoleError: jest.SpyInstance;

	// Each simulated request rejects with "<request> failed", so this pins which failure was logged.
	const expectLoggedFailure = ( request: string ) =>
		expect( consoleError ).toHaveBeenCalledWith(
			'WooCommerce Core Profiler:',
			expect.objectContaining( { message: `${ request } failed` } )
		);

	beforeEach( () => {
		jest.useFakeTimers();
		jest.clearAllMocks();
		// Recovered errors are logged on purpose; keep them out of the test output.
		consoleError = jest
			.spyOn( console, 'error' )
			.mockImplementation( () => undefined );
		mockFailingRequests.clear();
		mockStoreCalls.length = 0;
		mockOnboardingSetting.skipOnboardingFallbackUrl = FALLBACK_URL;
		// @ts-expect-error Replace jsdom's location so navigation can be observed.
		delete window.location;
		// @ts-expect-error Minimal location stub.
		window.location = { href: 'about:blank' };
	} );

	afterEach( () => {
		jest.useRealTimers();
		jest.restoreAllMocks();
		// @ts-expect-error Restore jsdom's location.
		window.location = originalLocation;
	} );

	it( 'keeps the profiler usable when every REST request fails', async () => {
		mockFailingRequests.add( '*' );
		const actor = startProfiler();

		await skipToBusinessLocationAndSubmit( actor );

		expect( await waitForNavigation() ).toBe(
			`${ FALLBACK_URL }&store_location_not_saved=1`
		);
		expect( actor.getSnapshot().status ).toBe( 'active' );
		expect( mockStoreCalls ).not.toContain( 'coreProfilerCompleted' );
		expect( consoleError ).toHaveBeenCalledWith(
			'WooCommerce Core Profiler:',
			expect.objectContaining( {
				cause: expect.objectContaining( { code: 'rest_no_route' } ),
			} )
		);
		// The plugins prefetch has no error handler of its own, so only the root handler logs it.
		expectLoggedFailure( 'getFreeExtensions' );
		actor.stop();
	} );

	it( 'leaves the store location flag off the fallback page when only the skipped flag fails', async () => {
		mockFailingRequests.add( 'updateProfileItems' );
		const actor = startProfiler();

		await skipToBusinessLocationAndSubmit( actor );

		expect( await waitForNavigation() ).toBe( FALLBACK_URL );
		actor.stop();
	} );

	it( 'leaves through Home when the skipped flag saves but location and currency fail', async () => {
		mockFailingRequests.add( 'saveSetting' );
		mockFailingRequests.add( 'updateStoreCurrencyAndMeasurementUnits' );
		const actor = startProfiler();

		await skipToBusinessLocationAndSubmit( actor );

		expect( await waitForNavigation() ).toBe(
			`${ HOME_PATH }&store_location_not_saved=1`
		);
		expect( mockStoreCalls ).toEqual(
			expect.arrayContaining( [
				'updateProfileItems',
				'coreProfilerCompleted',
			] )
		);
		expectLoggedFailure( 'saveSetting' );
		expectLoggedFailure( 'updateStoreCurrencyAndMeasurementUnits' );
		actor.stop();
	} );

	it( 'leaves through Home when Coming Soon setup fails', async () => {
		mockFailingRequests.add( 'coreProfilerCompleted' );
		const actor = startProfiler();

		await skipToBusinessLocationAndSubmit( actor );

		expect( await waitForNavigation() ).toBe( HOME_PATH );
		expectLoggedFailure( 'coreProfilerCompleted' );
		actor.stop();
	} );

	it( 'falls back to Home when no fallback URL was preloaded', async () => {
		mockFailingRequests.add( 'updateProfileItems' );
		delete mockOnboardingSetting.skipOnboardingFallbackUrl;
		const actor = startProfiler();

		await skipToBusinessLocationAndSubmit( actor );

		expect( await waitForNavigation() ).toBe( HOME_PATH );
		expect( mockStoreCalls ).not.toContain( 'coreProfilerCompleted' );
		actor.stop();
	} );

	it( 'does not use the fallback when every request succeeds', async () => {
		const actor = startProfiler();

		await skipToBusinessLocationAndSubmit( actor );

		expect( await waitForNavigation() ).toBe( HOME_PATH );
		expect( mockStoreCalls ).toContain( 'coreProfilerCompleted' );
		expect( consoleError ).not.toHaveBeenCalled();
		actor.stop();
	} );

	it( 'still fetches the Jetpack auth URL when Coming Soon setup fails', async () => {
		mockFailingRequests.add( 'coreProfilerCompleted' );
		const actor = createActor(
			coreProfilerMachineActors.exitToJetpackAuth
		).start();

		await expect( toPromise( actor ) ).resolves.toEqual(
			mockJetpackAuthResult
		);
		expect( mockStoreCalls ).toEqual( [
			'coreProfilerCompleted',
			'getJetpackAuthUrl',
		] );
		expectLoggedFailure( 'coreProfilerCompleted' );
	} );
} );
