/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { fromCallback, fromPromise } from 'xstate5';
import type { AnyActorLogic } from 'xstate5';
import { recordEvent } from '@woocommerce/tracks';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { CoreProfilerController } from '../index';
import { pluginInstallerMachine } from '../services/installAndActivatePlugins';
import { exampleExtension } from './fixtures';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => ( {
	resolveSelect: jest.fn(),
	dispatch: jest.fn( () => ( {
		updateProfileItems: jest.fn().mockResolvedValue( undefined ),
	} ) ),
} ) );

jest.mock( '@wordpress/core-data', () => ( {
	store: { name: 'core' },
} ) );

jest.mock( '@woocommerce/data', () => ( {
	COUNTRIES_STORE_NAME: { name: 'countries' },
	onboardingStore: { name: 'onboarding' },
	pluginsStore: { name: 'plugins' },
	userStore: { name: 'user' },
	experimentalSettingOptionsStore: { name: 'settings' },
} ) );

jest.mock( '@woocommerce/navigation', () => ( {
	getQuery: jest.fn( () => ( {} ) ),
	updateQueryString: jest.fn(),
	getNewPath: jest.fn( () => '/' ),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: jest.fn( ( path: string ) => path ),
	getSetting: jest.fn( () => 'current' ),
} ) );

jest.mock( '@woocommerce/explat', () => ( {
	initializeExPlat: jest.fn(),
} ) );

jest.mock( '~/lib/init-remote-logging', () => ( {
	initRemoteLogging: jest.fn(),
} ) );

jest.mock( '~/xstate', () => ( {
	useXStateInspect: jest.fn( () => ( { xstateV5Inspector: undefined } ) ),
} ) );

jest.mock( '~/utils', () => ( {
	...jest.requireActual( '~/utils' ),
	useFullScreen: jest.fn(),
} ) );

jest.mock( '../services/country', () => ( {
	getCountryStateOptions: jest.fn( () => [
		{ key: 'US:CA', label: 'United States — California' },
	] ),
} ) );

jest.mock( '../pages/IntroOptIn', () => ( {
	IntroOptIn: jest.requireActual( './fixtures' ).IntroOptInDriver,
} ) );

jest.mock( '../pages/UserProfile', () => ( {
	UserProfile: jest.requireActual( './fixtures' ).UserProfileDriver,
} ) );

jest.mock( '../pages/BusinessInfo', () => ( {
	POSSIBLY_DEFAULT_STORE_NAMES:
		jest.requireActual( './fixtures' ).possibleDefaultStoreNames,
	BusinessInfo: jest.requireActual( './fixtures' ).BusinessInfoDriver,
} ) );

jest.mock( '../pages/BusinessLocation', () => ( {
	BusinessLocation: jest.requireActual( './fixtures' ).BusinessLocationDriver,
} ) );

jest.mock( '../pages/Plugins/Plugins', () => ( {
	Plugins: jest.requireActual( './fixtures' ).PluginsDriver,
} ) );

jest.mock( '../pages/Plugins/NoPermissions', () => ( {
	NoPermissionsError: jest.requireActual( './fixtures' ).NoPermissionsDriver,
} ) );

jest.mock( '../components/loader/Loader', () => ( {
	CoreProfilerLoader: jest.requireActual( './fixtures' ).LoaderDriver,
} ) );

jest.mock( '../components/profile-spinner/profile-spinner', () => ( {
	ProfileSpinner: jest.requireActual( './fixtures' ).SpinnerDriver,
} ) );

type ControllerProps = ComponentProps< typeof CoreProfilerController >;
type ServicesOverrides = Partial<
	Record< keyof ControllerProps[ 'servicesOverrides' ], AnyActorLogic >
>;

const mockRecordEvent = jest.mocked( recordEvent );
const mockGetQuery = jest.mocked( getQuery );

const resolvedActor = < T, >( output: T ) => fromPromise( async () => output );

const delayedActor = < T, >( output: T ) =>
	fromPromise(
		() =>
			new Promise< T >( ( resolve ) => {
				setTimeout( () => resolve( output ), 0 );
			} )
	);

const noopCallbackActor = fromCallback( () => {} );

const defaultServicesOverrides: ServicesOverrides = {
	preFetchGetPlugins: resolvedActor( [] ),
	getAllowTrackingOption: resolvedActor( 'yes' ),
	getStoreNameOption: resolvedActor( '' ),
	getStoreCountryOption: resolvedActor( 'US:CA' ),
	getCountries: resolvedActor( [
		{ code: 'US', name: 'United States', states: [] },
	] ),
	getGeolocation: resolvedActor( undefined ),
	getOnboardingProfileItems: resolvedActor( {} ),
	getCurrentUserEmail: resolvedActor( 'merchant@example.test' ),
	getCurrentUser: resolvedActor( {
		capabilities: { install_plugins: true },
	} ),
	// Let the current-user actor populate capabilities before the plugins
	// permission guard runs when the plugins actor completes.
	getPlugins: delayedActor( [ exampleExtension ] ),
	getJetpackIsConnected: resolvedActor( true ),
	browserPopstateHandler: noopCallbackActor,
	updateBusinessInfo: resolvedActor( undefined ),
	updateTrackingOption: resolvedActor( undefined ),
	updateOnboardingProfileOption: resolvedActor( undefined ),
	updateProfilerCompletedSteps: resolvedActor( undefined ),
	getCoreProfilerCompletedSteps: resolvedActor( {} ),
	skipFlowUpdateBusinessLocation: resolvedActor( undefined ),
};

const actionOverrides = {
	preFetchIsJetpackConnected: () => undefined,
	preFetchJetpackAuthUrl: () => undefined,
	updateQueryStep: () => undefined,
	redirectToWooHome: () => undefined,
	redirectToJetpackAuthPage: () => undefined,
	reloadPage: () => undefined,
} as ControllerProps[ 'actionOverrides' ];

// Runs the real installer machine with only the network call replaced.
const installerWith = ( installPlugin: AnyActorLogic ) =>
	pluginInstallerMachine.provide( { actors: { installPlugin } } );

// XState promise actors settle after RTL's synchronous act scope completes,
// so flush one macrotask inside the same scope.
const actAndFlush = async ( callback: () => unknown ) => {
	await act( async () => {
		await callback();
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
	} );
};

const renderController = ( servicesOverrides: ServicesOverrides = {} ) =>
	actAndFlush( () =>
		render(
			<CoreProfilerController
				actionOverrides={ actionOverrides }
				servicesOverrides={
					{
						...defaultServicesOverrides,
						...servicesOverrides,
					} as ControllerProps[ 'servicesOverrides' ]
				}
			/>
		)
	);

const clickButton = async ( name: string ) => {
	const button = await screen.findByRole( 'button', { name } );
	await actAndFlush( () => userEvent.click( button ) );
};

const expectEventOrder = ( expectedEvents: string[] ) => {
	const recordedEvents = mockRecordEvent.mock.calls.map(
		( [ name, properties ] ) => {
			if (
				( name === 'coreprofiler_step_view' ||
					name === 'coreprofiler_step_complete' ) &&
				properties?.step
			) {
				return `${ name }:${ properties.step }`;
			}

			return name;
		}
	);
	let previousIndex = -1;

	expectedEvents.forEach( ( event ) => {
		expect( recordedEvents.slice( previousIndex + 1 ) ).toContain( event );
		previousIndex = recordedEvents.indexOf( event, previousIndex + 1 );
	} );
};

const completeStandardProfile = async () => {
	await clickButton( 'Complete intro' );
	await clickButton( 'Complete user profile' );
	await clickButton( 'Complete business info' );
};

describe( 'Core Profiler Tracks flows', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockGetQuery.mockReturnValue( {} );
	} );

	it( 'records the guided-setup skip flow', async () => {
		await renderController();

		await clickButton( 'Skip guided setup' );
		await clickButton( 'Complete business location' );

		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_skip_guided_setup',
			expect.objectContaining( { wc_version: 'current' } )
		);
		expectEventOrder( [
			'coreprofiler_step_view:skip_business_location',
			'coreprofiler_skip_guided_setup',
			'coreprofiler_step_complete:skip_business_location',
		] );
	} );

	it( 'records the standard skipped steps', async () => {
		await renderController();

		await clickButton( 'Complete intro' );
		await clickButton( 'Skip user profile' );
		await clickButton( 'Skip business info' );
		await clickButton( 'Skip extensions' );

		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_plugins_skip'
		);
		expectEventOrder( [
			'coreprofiler_step_complete:intro_opt_in',
			'coreprofiler_step_view:user_profile',
			'coreprofiler_user_profile_skip',
			'coreprofiler_step_view:business_info',
			'coreprofiler_business_info_skip',
			'coreprofiler_step_view:plugins',
			'coreprofiler_plugins_skip',
		] );
	} );

	it( 'records completed profile and extension installation flows', async () => {
		await renderController( {
			pluginInstallerMachine: installerWith(
				resolvedActor( {
					data: { install_time: { 'example-extension': 1000 } },
				} )
			),
		} );

		await completeStandardProfile();
		await clickButton( 'Learn more about extension' );
		await clickButton( 'Install extension' );

		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_user_profile',
			expect.objectContaining( {
				business_choice: 'im_just_starting_my_business',
				selling_online_answer: null,
				selling_platforms: null,
			} )
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_business_info',
			expect.objectContaining( {
				business_name_filled: true,
				industry: 'other',
				geolocation_success: false,
				geolocation_overruled: false,
			} )
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_email_marketing',
			expect.objectContaining( {
				opt_in: true,
				email_field_prefilled_source: 'current_user_email',
				email_field_modified: false,
			} )
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_plugins_learn_more_link_clicked',
			{
				plugin: 'example-extension',
				link: 'https://example.test/extension',
			}
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_store_extensions_continue',
			{
				shown: [ 'example-extension' ],
				selected: [ 'example-extension' ],
				unselected: [],
			}
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_store_extension_installed_and_activated',
			expect.objectContaining( {
				success: true,
				extension: 'example_extension',
			} )
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_store_extensions_installed_and_activated',
			expect.objectContaining( {
				success: true,
				installed_extensions: [ 'example_extension' ],
			} )
		);

		expectEventOrder( [
			'coreprofiler_step_complete:intro_opt_in',
			'coreprofiler_step_view:user_profile',
			'coreprofiler_step_complete:user_profile',
			'coreprofiler_user_profile',
			'coreprofiler_step_view:business_info',
			'coreprofiler_step_complete:business_info',
			'coreprofiler_business_info',
			'coreprofiler_email_marketing',
			'coreprofiler_step_view:plugins',
			'coreprofiler_plugins_learn_more_link_clicked',
			'coreprofiler_store_extensions_continue',
			'coreprofiler_store_extension_installed_and_activated',
			'coreprofiler_store_extensions_installed_and_activated',
		] );
	} );

	it( 'records the extensions step as skipped when none are selected', async () => {
		await renderController();
		await completeStandardProfile();

		await clickButton( 'Continue without extensions' );

		expect(
			mockRecordEvent.mock.calls.filter(
				( [ eventName ] ) => eventName === 'coreprofiler_plugins_skip'
			)
		).toHaveLength( 1 );
		expect( recordEvent ).not.toHaveBeenCalledWith(
			'coreprofiler_store_extensions_continue',
			expect.anything()
		);
	} );

	it( 'records when extension-installation permission is missing', async () => {
		mockGetQuery.mockReturnValue( { step: 'plugins' } );

		await renderController( {
			getCurrentUser: resolvedActor( {
				capabilities: { install_plugins: false },
			} ),
		} );

		await screen.findByText( 'No installation permission' );

		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_store_extensions_no_permission_error'
		);
		expectEventOrder( [
			'coreprofiler_step_view:plugins',
			'coreprofiler_store_extensions_no_permission_error',
		] );
	} );

	it( 'records a failed extension installation', async () => {
		await renderController( {
			pluginInstallerMachine: installerWith(
				fromPromise( async () => {
					throw new Error( 'Installation failed' );
				} )
			),
		} );

		await completeStandardProfile();
		await clickButton( 'Install extension' );

		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_store_extension_installed_and_activated',
			{
				success: false,
				extension: 'example_extension',
				error_message: 'Installation failed',
			}
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_store_extensions_installed_and_activated',
			{
				success: false,
				failed_extensions: [ 'example_extension' ],
			}
		);

		expectEventOrder( [
			'coreprofiler_store_extensions_continue',
			'coreprofiler_store_extensions_installed_and_activated',
			'coreprofiler_store_extension_installed_and_activated',
		] );
	} );
} );
