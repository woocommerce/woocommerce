/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { fromCallback, fromPromise } from 'xstate5';
import { recordEvent } from '@woocommerce/tracks';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { CoreProfilerController } from '../index';
import { exampleExtension } from './fixtures';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => ( {
	resolveSelect: jest.fn(),
	dispatch: jest.fn( () => ( {
		updateProfileItems: jest.fn().mockResolvedValue( undefined ),
		updateStoreCurrencyAndMeasurementUnits: jest
			.fn()
			.mockResolvedValue( undefined ),
		saveSetting: jest.fn().mockResolvedValue( undefined ),
		saveEntityRecord: jest.fn().mockResolvedValue( undefined ),
		invalidateResolutionForStoreSelector: jest.fn(),
		coreProfilerCompleted: jest.fn().mockResolvedValue( undefined ),
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
	getPluginTrackKey: jest.fn( ( key: string ) => key ),
	getTimeFrame: jest.fn( () => 'under_10_seconds' ),
	useFullScreen: jest.fn(),
} ) );

jest.mock( '~/utils/plugins', () => ( {
	getPluginSlug: jest.fn( ( key: string ) => key ),
} ) );

jest.mock( '~/dashboard/utils', () => ( {
	getCountryCode: jest.fn(
		( location?: string ) => location?.split( ':' )[ 0 ]
	),
} ) );

jest.mock( '../services/country', () => ( {
	getCountryStateOptions: jest.fn( () => [
		{ key: 'US:CA', label: 'United States — California' },
	] ),
} ) );

jest.mock( '../services/installAndActivatePlugins', () => {
	const { fromCallback: createCallbackActor } =
		jest.requireActual( 'xstate5' );

	return {
		pluginInstallerMachine: createCallbackActor( () => () => undefined ),
	};
} );

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

const resolvedActor = < T, >( output: T ) => fromPromise( async () => output );

const delayedActor = < T, >( output: T ) =>
	fromPromise(
		() =>
			new Promise< T >( ( resolve ) => {
				setTimeout( () => resolve( output ), 0 );
			} )
	);

const createServiceOverrides = (): ControllerProps[ 'servicesOverrides' ] =>
	( {
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
		browserPopstateHandler: fromCallback( () => () => undefined ),
		updateBusinessInfo: resolvedActor( undefined ),
		updateTrackingOption: resolvedActor( undefined ),
		updateOnboardingProfileOption: resolvedActor( undefined ),
		updateProfilerCompletedSteps: resolvedActor( undefined ),
		getCoreProfilerCompletedSteps: resolvedActor( {} ),
		skipFlowUpdateBusinessLocation: resolvedActor( undefined ),
		pluginInstallerMachine: fromCallback( () => () => undefined ),
		exitToWooHome: resolvedActor( undefined ),
	} ) as ControllerProps[ 'servicesOverrides' ];

const createActionOverrides = (): ControllerProps[ 'actionOverrides' ] =>
	( {
		preFetchIsJetpackConnected: jest.fn(),
		preFetchJetpackAuthUrl: jest.fn(),
		updateQueryStep: jest.fn(),
		redirectToWooHome: jest.fn(),
		redirectToJetpackAuthPage: jest.fn(),
		reloadPage: jest.fn(),
	} ) as ControllerProps[ 'actionOverrides' ];

const renderController = async (
	actionOverrides: ControllerProps[ 'actionOverrides' ] = {},
	servicesOverrides: ControllerProps[ 'servicesOverrides' ] = {}
) => {
	let rendered: ReturnType< typeof render >;

	// XState promise actors settle after RTL's synchronous render act completes.
	// eslint-disable-next-line testing-library/no-unnecessary-act
	await act( async () => {
		rendered = render(
			<CoreProfilerController
				actionOverrides={ {
					...createActionOverrides(),
					...actionOverrides,
				} }
				servicesOverrides={ {
					...createServiceOverrides(),
					...servicesOverrides,
				} }
			/>
		);
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
	} );

	return rendered!;
};

const expectEventOrder = ( expectedEvents: string[] ) => {
	const recordedEvents = ( recordEvent as jest.Mock ).mock.calls.map(
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
		const index = recordedEvents.indexOf( event, previousIndex + 1 );
		expect( index ).toBeGreaterThan( previousIndex );
		previousIndex = index;
	} );
};

const clickAndFlushActors = async (
	button: Parameters< typeof userEvent.click >[ 0 ]
) => {
	// The user event starts XState promise actors that need the same act scope.
	// eslint-disable-next-line testing-library/no-unnecessary-act
	await act( async () => {
		await userEvent.click( button );
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
	} );
};

const completeStandardProfile = async () => {
	await clickAndFlushActors(
		await screen.findByRole( 'button', { name: 'Complete intro' } )
	);
	await clickAndFlushActors(
		await screen.findByRole( 'button', {
			name: 'Complete user profile',
		} )
	);
	await clickAndFlushActors(
		await screen.findByRole( 'button', {
			name: 'Complete business info',
		} )
	);
};

describe( 'Core Profiler Tracks flows', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		( getQuery as jest.Mock ).mockReturnValue( {} );
	} );

	it( 'records the guided-setup skip flow', async () => {
		await renderController();

		await clickAndFlushActors(
			await screen.findByRole( 'button', {
				name: 'Skip guided setup',
			} )
		);

		await clickAndFlushActors(
			await screen.findByRole( 'button', {
				name: 'Complete business location',
			} )
		);

		await waitFor( () =>
			expect( recordEvent ).toHaveBeenCalledWith(
				'coreprofiler_step_complete',
				expect.objectContaining( {
					step: 'skip_business_location',
				} )
			)
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_step_view',
			expect.objectContaining( {
				step: 'skip_business_location',
			} )
		);
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
		const { unmount } = await renderController();

		await clickAndFlushActors(
			await screen.findByRole( 'button', { name: 'Complete intro' } )
		);
		await clickAndFlushActors(
			await screen.findByRole( 'button', {
				name: 'Skip user profile',
			} )
		);
		await clickAndFlushActors(
			await screen.findByRole( 'button', {
				name: 'Skip business info',
			} )
		);
		await clickAndFlushActors(
			await screen.findByRole( 'button', { name: 'Skip extensions' } )
		);

		await waitFor( () =>
			expect( recordEvent ).toHaveBeenCalledWith(
				'coreprofiler_plugins_skip'
			)
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_step_complete',
			expect.objectContaining( { step: 'intro_opt_in' } )
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_step_view',
			expect.objectContaining( { step: 'user_profile' } )
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_step_view',
			expect.objectContaining( { step: 'business_info' } )
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_step_view',
			expect.objectContaining( { step: 'plugins' } )
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

		unmount();
	} );

	it( 'records completed profile and extension installation flows', async () => {
		const successfulInstaller = fromCallback( ( { sendBack } ) => {
			sendBack( {
				type: 'PLUGINS_INSTALLATION_COMPLETED',
				payload: {
					installationCompletedResult: {
						installedPlugins: [
							{
								plugin: 'example-extension',
								installTime: 1000,
							},
						],
						totalTime: 1000,
					},
				},
			} );

			return () => undefined;
		} );

		await renderController( {}, {
			pluginInstallerMachine: successfulInstaller,
		} as ControllerProps[ 'servicesOverrides' ] );

		await completeStandardProfile();
		await clickAndFlushActors(
			await screen.findByRole( 'button', {
				name: 'Learn more about extension',
			} )
		);
		await clickAndFlushActors(
			await screen.findByRole( 'button', { name: 'Install extension' } )
		);

		await waitFor( () =>
			expect( recordEvent ).toHaveBeenCalledWith(
				'coreprofiler_store_extensions_installed_and_activated',
				expect.objectContaining( {
					success: true,
					installed_extensions: [ 'example-extension' ],
				} )
			)
		);
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
				extension: 'example-extension',
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

		await clickAndFlushActors(
			await screen.findByRole( 'button', {
				name: 'Continue without extensions',
			} )
		);

		await waitFor( () =>
			expect( recordEvent ).toHaveBeenCalledWith(
				'coreprofiler_plugins_skip'
			)
		);
		expect(
			( recordEvent as jest.Mock ).mock.calls.filter(
				( [ eventName ] ) => eventName === 'coreprofiler_plugins_skip'
			)
		).toHaveLength( 1 );
		expect( recordEvent ).not.toHaveBeenCalledWith(
			'coreprofiler_store_extensions_continue',
			expect.anything()
		);
	} );

	it( 'records when extension-installation permission is missing', async () => {
		( getQuery as jest.Mock ).mockReturnValue( { step: 'plugins' } );

		await renderController( {}, {
			getCurrentUser: resolvedActor( {
				capabilities: { install_plugins: false },
			} ),
		} as ControllerProps[ 'servicesOverrides' ] );

		await screen.findByText( 'No installation permission' );

		await waitFor( () =>
			expect( recordEvent ).toHaveBeenCalledWith(
				'coreprofiler_store_extensions_no_permission_error'
			)
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_step_view',
			expect.objectContaining( { step: 'plugins' } )
		);

		expectEventOrder( [
			'coreprofiler_step_view:plugins',
			'coreprofiler_store_extensions_no_permission_error',
		] );
	} );

	it( 'records a failed extension installation', async () => {
		const failedInstaller = fromCallback( ( { sendBack } ) => {
			sendBack( {
				type: 'PLUGINS_INSTALLATION_COMPLETED_WITH_ERRORS',
				payload: {
					errors: [
						{
							plugin: 'example-extension',
							error: 'Installation failed',
							errorDetails: {
								data: {
									code: 'install_error',
									data: { status: 500 },
								},
							},
						},
					],
				},
			} );

			return () => undefined;
		} );

		await renderController( {}, {
			pluginInstallerMachine: failedInstaller,
		} as ControllerProps[ 'servicesOverrides' ] );

		await completeStandardProfile();
		await clickAndFlushActors(
			await screen.findByRole( 'button', { name: 'Install extension' } )
		);

		await waitFor( () =>
			expect( recordEvent ).toHaveBeenCalledWith(
				'coreprofiler_store_extension_installed_and_activated',
				{
					success: false,
					extension: 'example-extension',
					error_message: 'Installation failed',
				}
			)
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'coreprofiler_store_extensions_installed_and_activated',
			{
				success: false,
				failed_extensions: [ 'example-extension' ],
			}
		);

		expectEventOrder( [
			'coreprofiler_store_extensions_continue',
			'coreprofiler_store_extensions_installed_and_activated',
			'coreprofiler_store_extension_installed_and_activated',
		] );
	} );
} );
