/* eslint-disable xstate/no-inline-implementation */
/**
 * External dependencies
 */
import { createMachine, assign, DoneInvokeEvent, actions, spawn } from 'xstate';
import { useMachine } from '@xstate/react';
import { useEffect, useMemo } from '@wordpress/element';
import { resolveSelect, dispatch } from '@wordpress/data';
import { navigateTo, getNewPath } from '@woocommerce/navigation';
import {
	ExtensionList,
	OPTIONS_STORE_NAME,
	COUNTRIES_STORE_NAME,
	Country,
	ONBOARDING_STORE_NAME,
	Extension,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { getSetting } from '@woocommerce/settings';
import { initializeExPlat } from '@woocommerce/explat';

/**
 * Internal dependencies
 */
import { IntroOptIn } from './pages/IntroOptIn';
import {
	UserProfile,
	BusinessChoice,
	SellingOnlineAnswer,
	SellingPlatform,
} from './pages/UserProfile';
import { BusinessInfo } from './pages/BusinessInfo';
import { BusinessLocation } from './pages/BusinessLocation';
import { getCountryStateOptions } from './services/country';
import { Loader } from './pages/Loader';
import { Plugins } from './pages/Plugins';
import { getPluginTrackKey, getTimeFrame } from '~/utils';
import './style.scss';
import {
	InstallationCompletedResult,
	InstallAndActivatePlugins,
	InstalledPlugin,
	PluginInstallError,
} from './services/installAndActivatePlugins';

// TODO: Typescript support can be improved, but for now lets write the types ourselves
// https://stately.ai/blog/introducing-typescript-typegen-for-xstate

export type InitializationCompleteEvent = {
	type: 'INITIALIZATION_COMPLETE';
	payload: { optInDataSharing: boolean };
};

export type IntroOptInEvent =
	| { type: 'INTRO_COMPLETED'; payload: { optInDataSharing: boolean } } // can be true or false depending on whether the user opted in or not
	| { type: 'INTRO_SKIPPED'; payload: { optInDataSharing: false } }; // always false for now

export type UserProfileEvent =
	| {
			type: 'USER_PROFILE_COMPLETED';
			payload: {
				userProfile: CoreProfilerStateMachineContext[ 'userProfile' ];
			}; // TODO: fill in the types for this when developing this page
	  }
	| {
			type: 'USER_PROFILE_SKIPPED';
			payload: { userProfile: { skipped: true } };
	  };

export type BusinessInfoEvent = {
	type: 'BUSINESS_INFO_COMPLETED';
	payload: {
		businessInfo: CoreProfilerStateMachineContext[ 'businessInfo' ];
	};
};

export type BusinessLocationEvent = {
	type: 'BUSINESS_LOCATION_COMPLETED';
	payload: {
		businessInfo: CoreProfilerStateMachineContext[ 'businessInfo' ];
	};
};

export type PluginsInstallationRequestedEvent = {
	type: 'PLUGINS_INSTALLATION_REQUESTED';
	payload: {
		plugins: CoreProfilerStateMachineContext[ 'pluginsSelected' ];
	};
};

// TODO: add types as we develop the pages
export type OnboardingProfile = {
	business_choice: BusinessChoice;
	selling_online_answer: SellingOnlineAnswer | null;
	selling_platforms: SellingPlatform[] | null;
	skip?: boolean;
};

export type PluginsPageSkippedEvent = {
	type: 'PLUGINS_PAGE_SKIPPED';
};

export type PluginInstalledAndActivatedEvent = {
	type: 'PLUGIN_INSTALLED_AND_ACTIVATED';
	payload: {
		pluginsCount: number;
		installedPluginIndex: number;
	};
};
export type PluginsInstallationCompletedEvent = {
	type: 'PLUGINS_INSTALLATION_COMPLETED';
	payload: {
		installationCompletedResult: InstallationCompletedResult;
	};
};

export type PluginsInstallationCompletedWithErrorsEvent = {
	type: 'PLUGINS_INSTALLATION_COMPLETED_WITH_ERRORS';
	payload: {
		errors: PluginInstallError[];
	};
};

export type ExtensionErrors = { plugin: string; error: string }[];
export type CoreProfilerStateMachineContext = {
	optInDataSharing: boolean;
	userProfile: {
		businessChoice?: BusinessChoice;
		sellingOnlineAnswer?: SellingOnlineAnswer | null;
		sellingPlatforms?: SellingPlatform[] | null;
		skipped?: boolean;
	};
	geolocatedLocation: {
		location: string;
	};
	pluginsAvailable: ExtensionList[ 'plugins' ] | [  ];
	pluginsSelected: string[]; // extension slugs
	pluginsInstallationErrors: ExtensionErrors;
	businessInfo: { foo?: { bar: 'qux' }; location: string };
	countries: { [ key: string ]: string };
	loader: {
		progress?: number;
		className?: string;
		useStages?: string;
		stageIndex?: number;
	};
};

const getAllowTrackingOption = async () =>
	resolveSelect( OPTIONS_STORE_NAME ).getOption(
		'woocommerce_allow_tracking'
	);

const handleTrackingOption = assign( {
	optInDataSharing: (
		_context,
		event: DoneInvokeEvent< 'no' | 'yes' | undefined >
	) => event.data !== 'no',
} );

/**
 * Prefetch it so that @wp/data caches it and there won't be a loading delay when its used
 */
const preFetchGetCountries = assign( {
	spawnGetCountriesRef: () =>
		spawn(
			resolveSelect( COUNTRIES_STORE_NAME ).getCountries(),
			'core-profiler-prefetch-countries'
		),
} );

const getCountries = async () =>
	resolveSelect( COUNTRIES_STORE_NAME ).getCountries();

const handleCountries = assign( {
	countries: ( _context, event: DoneInvokeEvent< Country[] > ) => {
		return getCountryStateOptions( event.data );
	},
} );

const getOnboardingProfileOption = async () =>
	resolveSelect( OPTIONS_STORE_NAME ).getOption(
		'woocommerce_onboarding_profile'
	);

const handleOnboardingProfileOption = assign( {
	userProfile: (
		_context,
		event: DoneInvokeEvent< OnboardingProfile | undefined >
	) => {
		if ( ! event.data ) {
			return {};
		}

		const {
			business_choice: businessChoice,
			selling_online_answer: sellingOnlineAnswer,
			selling_platforms: sellingPlatforms,
			...rest
		} = event.data;

		return {
			...rest,
			businessChoice,
			sellingOnlineAnswer,
			sellingPlatforms,
		};
	},
} );

const redirectToWooHome = () => {
	navigateTo( { url: getNewPath( {}, '/', {} ) } );
};

const recordTracksIntroCompleted = () => {
	recordEvent( 'storeprofiler_step_complete', {
		step: 'store_details',
		wc_version: getSetting( 'wcVersion' ),
	} );
};

const recordTracksIntroSkipped = () => {
	recordEvent( 'storeprofiler_store_details_skip' );
};

const recordTracksIntroViewed = () => {
	recordEvent( 'storeprofiler_step_view', {
		step: 'store_details',
		wc_version: getSetting( 'wcVersion' ),
	} );
};

const recordTracksUserProfileViewed = () => {
	recordEvent( 'storeprofiler_step_view', {
		step: 'user_profile',
		wc_version: getSetting( 'wcVersion' ),
	} );
};

const recordTracksPluginsViewed = () => {
	recordEvent( 'storeprofiler_step_view', {
		step: 'plugins',
		wc_version: getSetting( 'wcVersion' ),
	} );
};

const recordTracksUserProfileCompleted = (
	_context: CoreProfilerStateMachineContext,
	event: Extract< UserProfileEvent, { type: 'USER_PROFILE_COMPLETED' } >
) => {
	recordEvent( 'storeprofiler_step_complete', {
		step: 'user_profile',
		wc_version: getSetting( 'wcVersion' ),
	} );

	recordEvent( 'storeprofiler_user_profile', {
		business_choice: event.payload.userProfile.businessChoice,
		selling_online_answer: event.payload.userProfile.sellingOnlineAnswer,
		selling_platforms: event.payload.userProfile.sellingPlatforms
			? event.payload.userProfile.sellingPlatforms.join()
			: null,
	} );
};

const recordTracksUserProfileSkipped = () => {
	recordEvent( 'storeprofiler_user_profile_skip' );
};

const recordTracksPluginsSkipped = () => {
	recordEvent( 'storeprofiler_plugins_skip' );
};

const recordTracksSkipBusinessLocationViewed = () => {
	recordEvent( 'storeprofiler_step_view', {
		step: 'skip_business_location',
		wc_version: getSetting( 'wcVersion' ),
	} );
};

const recordTracksSkipBusinessLocationCompleted = () => {
	recordEvent( 'storeprofiler_step_complete', {
		step: 'skp_business_location',
		wc_version: getSetting( 'wcVersion' ),
	} );
};

const updateTrackingOption = (
	_context: CoreProfilerStateMachineContext,
	event: IntroOptInEvent
) => {
	if (
		event.payload.optInDataSharing &&
		typeof window.wcTracks.enable === 'function'
	) {
		window.wcTracks.enable( () => {
			initializeExPlat();
		} );
	} else if ( ! event.payload.optInDataSharing ) {
		window.wcTracks.isEnabled = false;
	}

	const trackingValue = event.payload.optInDataSharing ? 'yes' : 'no';
	dispatch( OPTIONS_STORE_NAME ).updateOptions( {
		woocommerce_allow_tracking: trackingValue,
	} );
};

const updateOnboardingProfileOption = (
	context: CoreProfilerStateMachineContext
) => {
	const {
		businessChoice,
		sellingOnlineAnswer,
		sellingPlatforms,
		...rest
	} = context.userProfile;

	return dispatch( OPTIONS_STORE_NAME ).updateOptions( {
		woocommerce_onboarding_profile: {
			...rest,
			business_choice: businessChoice,
			selling_online_answer: sellingOnlineAnswer,
			selling_platforms: sellingPlatforms,
		},
	} );
};

const updateBusinessLocation = ( countryAndState: string ) => {
	return dispatch( OPTIONS_STORE_NAME ).updateOptions( {
		woocommerce_default_country: countryAndState,
	} );
};

const promiseDelay = ( milliseconds: number ) => {
	return new Promise( ( resolve ) => {
		setTimeout( resolve, milliseconds );
	} );
};

/**
 * Assigns the optInDataSharing value from the event payload to the context
 */
const assignOptInDataSharing = assign( {
	optInDataSharing: ( _context, event: IntroOptInEvent ) =>
		event.payload.optInDataSharing,
} );

/**
 * Prefetch it so that @wp/data caches it and there won't be a loading delay when its used
 */
const preFetchGetPlugins = assign( {
	extensionsRef: () =>
		spawn(
			resolveSelect( ONBOARDING_STORE_NAME ).getFreeExtensions(),
			'core-profiler-prefetch-extensions'
		),
} );

const getPlugins = async () => {
	dispatch( ONBOARDING_STORE_NAME ).invalidateResolution(
		'getFreeExtensions'
	);
	const extensionsBundles = await resolveSelect(
		ONBOARDING_STORE_NAME
	).getFreeExtensions();
	return (
		extensionsBundles.find( ( bundle ) => bundle.key === 'obw/grow' )
			?.plugins || []
	);
};

const handlePlugins = assign( {
	pluginsAvailable: ( _context, event: DoneInvokeEvent< Extension[] > ) =>
		event.data,
} );

const coreProfilerMachineActions = {
	updateTrackingOption,
	preFetchGetPlugins,
	preFetchGetCountries,
	handleTrackingOption,
	handlePlugins,
	recordTracksIntroCompleted,
	recordTracksIntroSkipped,
	recordTracksIntroViewed,
	recordTracksUserProfileCompleted,
	recordTracksUserProfileSkipped,
	recordTracksUserProfileViewed,
	recordTracksPluginsViewed,
	recordTracksPluginsSkipped,
	recordTracksSkipBusinessLocationViewed,
	recordTracksSkipBusinessLocationCompleted,
	assignOptInDataSharing,
	handleCountries,
	handleOnboardingProfileOption,
	redirectToWooHome,
};

const coreProfilerMachineServices = {
	getAllowTrackingOption,
	getCountries,
	getOnboardingProfileOption,
	getPlugins,
};
export const coreProfilerStateMachineDefinition = createMachine( {
	id: 'coreProfiler',
	initial: 'initializing',
	predictableActionArguments: true, // recommended setting: https://xstate.js.org/docs/guides/actions.html
	context: {
		// these are safe default values if for some reason the steps fail to complete correctly
		// actual defaults displayed to the user should be handled in the steps themselves
		optInDataSharing: false,
		userProfile: { skipped: true },
		geolocatedLocation: { location: 'US:CA' },
		businessInfo: { location: 'US:CA' },
		pluginsAvailable: [],
		pluginsSelected: [],
		pluginsInstallationErrors: [],
		countries: {},
		loader: {},
	} as CoreProfilerStateMachineContext,
	states: {
		initializing: {
			on: {
				INITIALIZATION_COMPLETE: {
					target: 'introOptIn',
				},
			},
			entry: [ 'preFetchGetPlugins', 'preFetchGetCountries' ],
			invoke: [
				{
					src: 'getAllowTrackingOption',
					// eslint-disable-next-line xstate/no-ondone-outside-compound-state -- The invoke.onDone property refers to the invocation (invoke.src) being done, not the onDone property on a state node.
					onDone: [
						{
							actions: [ 'handleTrackingOption' ],
							target: 'introOptIn',
						},
					],
					onError: {
						target: 'introOptIn', // leave it as initialised default on error
					},
				},
			],
			meta: {
				progress: 0,
			},
		},
		introOptIn: {
			on: {
				INTRO_COMPLETED: {
					target: 'preUserProfile',
					actions: [
						'assignOptInDataSharing',
						'updateTrackingOption',
					],
				},
				INTRO_SKIPPED: {
					// if the user skips the intro, we set the optInDataSharing to false and go to the Business Location page
					target: 'preSkipFlowBusinessLocation',
					actions: [
						'assignOptInDataSharing',
						'updateTrackingOption',
					],
				},
			},
			entry: [ 'recordTracksIntroViewed' ],
			exit: actions.choose( [
				{
					cond: ( _context, event ) =>
						event.type === 'INTRO_COMPLETED',
					actions: 'recordTracksIntroCompleted',
				},
				{
					cond: ( _context, event ) => event.type === 'INTRO_SKIPPED',
					actions: 'recordTracksIntroSkipped',
				},
			] ),
			meta: {
				progress: 20,
				component: IntroOptIn,
			},
		},
		preUserProfile: {
			invoke: {
				src: 'getOnboardingProfileOption',
				onDone: [
					{
						actions: [ 'handleOnboardingProfileOption' ],
						target: 'userProfile',
					},
				],
				onError: {
					target: 'userProfile',
				},
			},
		},
		userProfile: {
			entry: [ 'recordTracksUserProfileViewed' ],
			on: {
				USER_PROFILE_COMPLETED: {
					target: 'postUserProfile',
					actions: [
						assign( {
							userProfile: ( context, event: UserProfileEvent ) =>
								event.payload.userProfile, // sets context.userProfile to the payload of the event
						} ),
					],
				},
				USER_PROFILE_SKIPPED: {
					target: 'postUserProfile',
					actions: [
						assign( {
							userProfile: ( context, event: UserProfileEvent ) =>
								event.payload.userProfile, // assign context.userProfile to the payload of the event
						} ),
					],
				},
			},
			exit: actions.choose( [
				{
					cond: ( _context, event ) =>
						event.type === 'USER_PROFILE_COMPLETED',
					actions: 'recordTracksUserProfileCompleted',
				},
				{
					cond: ( _context, event ) =>
						event.type === 'USER_PROFILE_SKIPPED',
					actions: 'recordTracksUserProfileSkipped',
				},
			] ),
			meta: {
				progress: 40,
				component: UserProfile,
			},
		},
		postUserProfile: {
			invoke: {
				src: ( context ) => {
					return updateOnboardingProfileOption( context );
				},
				onDone: {
					target: 'preBusinessInfo',
				},
				onError: {
					target: 'preBusinessInfo',
				},
			},
		},
		preBusinessInfo: {
			always: [
				// immediately transition to businessInfo without any events as long as geolocation parallel has completed
				{
					target: 'businessInfo',
					cond: () => true, // TODO: use a custom function to check on the parallel state using meta when we implement that. https://xstate.js.org/docs/guides/guards.html#guards-condition-functions
				},
			],
			meta: {
				progress: 50,
			},
		},
		businessInfo: {
			on: {
				BUSINESS_INFO_COMPLETED: {
					target: 'prePlugins',
					actions: [
						assign( {
							businessInfo: (
								_context,
								event: BusinessInfoEvent
							) => event.payload.businessInfo, // assign context.businessInfo to the payload of the event
						} ),
					],
				},
			},
			meta: {
				progress: 60,
				component: BusinessInfo,
			},
		},
		preSkipFlowBusinessLocation: {
			invoke: {
				src: 'getCountries',
				onDone: [
					{
						actions: [ 'handleCountries' ],
						target: 'skipFlowBusinessLocation',
					},
				],
				onError: {
					target: 'skipFlowBusinessLocation',
				},
			},
		},
		skipFlowBusinessLocation: {
			on: {
				BUSINESS_LOCATION_COMPLETED: {
					target: 'postSkipFlowBusinessLocation',
					actions: [
						assign( {
							businessInfo: (
								_context,
								event: BusinessLocationEvent
							) => event.payload.businessInfo, // assign context.businessInfo to the payload of the event
						} ),
						'recordTracksSkipBusinessLocationCompleted',
					],
				},
			},
			entry: [ 'recordTracksSkipBusinessLocationViewed' ],
			meta: {
				progress: 80,
				component: BusinessLocation,
			},
		},
		postSkipFlowBusinessLocation: {
			initial: 'updateBusinessLocation',
			states: {
				updateBusinessLocation: {
					entry: assign( {
						loader: {
							progress: 10,
						},
					} ),
					invoke: {
						src: ( context ) => {
							return updateBusinessLocation(
								context.businessInfo.location
							);
						},
						onDone: {
							target: 'progress20',
						},
					},
				},
				// Although we don't need to wait 3 seconds for the following states
				// We will dispaly 20% and 80% progress for 1.5 seconds each
				// for the sake of user experience.
				progress20: {
					entry: assign( {
						loader: {
							progress: 20,
						},
					} ),
					invoke: {
						src: () => {
							return promiseDelay( 1500 );
						},
						onDone: {
							target: 'progress80',
						},
					},
				},
				progress80: {
					entry: assign( {
						loader: {
							progress: 80,
						},
					} ),
					invoke: {
						src: () => {
							return promiseDelay( 1500 );
						},
						onDone: {
							actions: [ 'redirectToWooHome' ],
						},
					},
				},
			},
			meta: {
				component: Loader,
			},
		},
		prePlugins: {
			invoke: {
				src: 'getPlugins',
				onDone: [ { target: 'plugins', actions: 'handlePlugins' } ],
			},
			// add exit action to filter the extensions using a custom function here and assign it to context.extensionsAvailable
			exit: assign( {
				pluginsAvailable: ( context ) => {
					return context.pluginsAvailable.filter( () => true );
				}, // TODO : define an extensible filter function here
			} ),
			meta: {
				progress: 70,
			},
		},
		pluginsSkipped: {
			entry: assign( {
				loader: {
					progress: 80,
				},
			} ),
			invoke: {
				src: () => {
					dispatch( ONBOARDING_STORE_NAME ).updateProfileItems( {
						plugins_page_skipped: true,
					} );
					return promiseDelay( 3000 );
				},
				onDone: {
					actions: [ 'redirectToWooHome' ],
				},
			},
			meta: {
				component: Loader,
			},
		},
		plugins: {
			entry: [ 'recordTracksPluginsViewed' ],
			on: {
				PLUGINS_PAGE_SKIPPED: {
					actions: [ 'recordTracksPluginsSkipped' ],
					target: 'pluginsSkipped',
				},
				PLUGINS_INSTALLATION_REQUESTED: {
					target: 'installPlugins',
					actions: [
						assign( {
							pluginsSelected: (
								_context,
								event: PluginsInstallationRequestedEvent
							) => event.payload.plugins,
						} ),
					],
				},
			},
			meta: {
				progress: 80,
				component: Plugins,
			},
		},
		postPluginInstallation: {
			invoke: {
				src: async ( _context, event ) => {
					return await dispatch(
						ONBOARDING_STORE_NAME
					).updateProfileItems( {
						business_extensions: event.payload.installationCompletedResult.installedPlugins.map(
							( extension: InstalledPlugin ) => extension.plugin
						),
					} );
				},
				onDone: {
					actions: 'redirectToWooHome',
				},
			},
			meta: {
				progress: 100,
			},
		},
		installPlugins: {
			on: {
				PLUGIN_INSTALLED_AND_ACTIVATED: {
					actions: [
						assign( {
							loader: (
								_context,
								event: PluginInstalledAndActivatedEvent
							) => {
								const progress = Math.round(
									( event.payload.installedPluginIndex /
										event.payload.pluginsCount ) *
										100
								);

								return {
									useStages: 'plugins',
									progress,
									stageIndex:
										progress > 30
											? progress > 60
												? 2
												: 1
											: 0,
								};
							},
						} ),
					],
				},
				PLUGINS_INSTALLATION_COMPLETED_WITH_ERRORS: {
					target: 'prePlugins',
					actions: [
						assign( {
							pluginsInstallationErrors: ( _context, event ) =>
								event.payload.errors,
						} ),
						( _context, event ) => {
							recordEvent(
								'storeprofiler_store_extensions_installed_and_activated',
								{
									success: false,
									failed_extensions: event.payload.errors.map(
										( error: PluginInstallError ) =>
											getPluginTrackKey( error.plugin )
									),
								}
							);
						},
					],
				},
				PLUGINS_INSTALLATION_COMPLETED: {
					target: 'postPluginInstallation',
					actions: [
						( _context, event ) => {
							const installationCompletedResult =
								event.payload.installationCompletedResult;

							const trackData: {
								success: boolean;
								installed_extensions: string[];
								total_time: string;
								[ key: string ]:
									| number
									| boolean
									| string
									| string[];
							} = {
								success: true,
								installed_extensions: installationCompletedResult.installedPlugins.map(
									( installedPlugin: InstalledPlugin ) =>
										getPluginTrackKey(
											installedPlugin.plugin
										)
								),
								total_time: getTimeFrame(
									installationCompletedResult.totalTime
								),
							};

							for ( const installedPlugin of installationCompletedResult.installedPlugins ) {
								trackData[
									'install_time_' +
										getPluginTrackKey(
											installedPlugin.plugin
										)
								] = getTimeFrame( installedPlugin.installTime );
							}

							recordEvent(
								'storeprofiler_store_extensions_installed_and_activated',
								trackData
							);
						},
					],
				},
			},
			entry: [
				assign( {
					loader: {
						progress: 10,
						useStages: 'plugins',
					},
				} ),
			],
			invoke: {
				src: InstallAndActivatePlugins,
			},
			meta: {
				component: Loader,
			},
		},
		settingUpStore: {},
	},
} );

export const CoreProfilerController = ( {
	actionOverrides,
	servicesOverrides,
}: {
	actionOverrides: Partial< typeof coreProfilerMachineActions >;
	servicesOverrides: Partial< typeof coreProfilerMachineServices >;
} ) => {
	const augmentedStateMachine = useMemo( () => {
		// When adding extensibility, this is the place to manipulate the state machine definition.
		return coreProfilerStateMachineDefinition.withConfig( {
			actions: {
				...coreProfilerMachineActions,
				...actionOverrides,
			},
			services: {
				...coreProfilerMachineServices,
				...servicesOverrides,
			},
		} );
	}, [ actionOverrides, servicesOverrides ] );

	const [ state, send ] = useMachine( augmentedStateMachine );
	const stateValue =
		typeof state.value === 'object'
			? Object.keys( state.value )[ 0 ]
			: state.value;
	const currentNodeMeta = state.meta[ `coreProfiler.${ stateValue }` ]
		? state.meta[ `coreProfiler.${ stateValue }` ]
		: undefined;
	const navigationProgress = currentNodeMeta?.progress; // This value is defined in each state node's meta tag, we can assume it is 0-100
	const CurrentComponent =
		currentNodeMeta?.component ??
		( () => (
			<div data-testid="core-profiler-loading-screen">Insert Spinner</div>
		) ); // If no component is defined for the state then its a loading state

	useEffect( () => {
		document.body.classList.remove( 'woocommerce-admin-is-loading' );
		document.body.classList.add( 'woocommerce-profile-wizard__body' );
		document.body.classList.add( 'woocommerce-admin-full-screen' );
		document.body.classList.add( 'is-wp-toolbar-disabled' );
		return () => {
			document.body.classList.remove(
				'woocommerce-profile-wizard__body'
			);
			document.body.classList.remove( 'woocommerce-admin-full-screen' );
			document.body.classList.remove( 'is-wp-toolbar-disabled' );
		};
	} );

	return (
		<>
			<div
				className={ `woocommerce-profile-wizard__container woocommerce-profile-wizard__step-${ state.value }` }
			>
				{
					<CurrentComponent
						navigationProgress={ navigationProgress }
						sendEvent={ send }
						context={ state.context }
					/>
				}
			</div>
		</>
	);
};
export default CoreProfilerController;
