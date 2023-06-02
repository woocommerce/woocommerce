/* eslint-disable xstate/no-inline-implementation */
/**
 * External dependencies
 */
import { createMachine, assign, DoneInvokeEvent, actions, spawn } from 'xstate';
import { useMachine } from '@xstate/react';
import { useEffect, useMemo } from '@wordpress/element';
import { resolveSelect, dispatch } from '@wordpress/data';
import {
	ExtensionList,
	OPTIONS_STORE_NAME,
	COUNTRIES_STORE_NAME,
	Country,
	ONBOARDING_STORE_NAME,
	Extension,
	GeolocationResponse,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { getSetting } from '@woocommerce/settings';
import { initializeExPlat } from '@woocommerce/explat';
import { CountryStateOption } from '@woocommerce/onboarding';

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
import {
	BusinessInfo,
	IndustryChoice,
	POSSIBLY_DEFAULT_STORE_NAMES,
} from './pages/BusinessInfo';
import { BusinessLocation } from './pages/BusinessLocation';
import { getCountryStateOptions } from './services/country';
import { Loader } from './pages/Loader';
import { Extensions } from './pages/Extensions';
import { ProfileSpinner } from './components/profile-spinner/profile-spinner';
import { Plugins } from './pages/Plugins';
import { getPluginTrackKey, getTimeFrame } from '~/utils';
import { ProfileSpinner } from './components/profile-spinner/profile-spinner';


import './style.scss';
import {
	InstallationCompletedResult,
	InstallAndActivatePlugins,
	InstalledPlugin,
	PluginInstallError,
} from './services/installAndActivatePlugins';

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
		storeName?: string;
		industry?: IndustryChoice;
		storeLocation: CountryStateOption[ 'key' ];
		geolocationOverruled: boolean;
	};
};

export type BusinessLocationEvent = {
	type: 'BUSINESS_LOCATION_COMPLETED';
	payload: {
		storeLocation: CountryStateOption[ 'key' ];
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
	industry: Array< IndustryChoice >;
	selling_online_answer: SellingOnlineAnswer | null;
	selling_platforms: SellingPlatform[] | null;
	skip?: boolean;
	is_store_country_set: boolean | null;
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

export type CoreProfilerStateMachineContext = {
	optInDataSharing: boolean;
	userProfile: {
		businessChoice?: BusinessChoice;
		sellingOnlineAnswer?: SellingOnlineAnswer | null;
		sellingPlatforms?: SellingPlatform[] | null;
		skipped?: boolean;
	};
	pluginsAvailable: ExtensionList[ 'plugins' ] | [];
	pluginsSelected: string[]; // extension slugs
	pluginsInstallationErrors: PluginInstallError[];
	geolocatedLocation: GeolocationResponse | undefined;
	businessInfo: {
		storeName?: string | undefined;
		industry?: string | undefined;
		location?: string;
	};
	countries: CountryStateOption[];
	loader: {
		progress?: number;
		className?: string;
		useStages?: string;
		stageIndex?: number;
	};
	onboardingProfile: OnboardingProfile;
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

const getStoreNameOption = async () =>
	resolveSelect( OPTIONS_STORE_NAME ).getOption( 'blogname' );

const handleStoreNameOption = assign( {
	businessInfo: (
		context: CoreProfilerStateMachineContext,
		event: DoneInvokeEvent< string | undefined >
	) => {
		return {
			...context.businessInfo,
			storeName: POSSIBLY_DEFAULT_STORE_NAMES.includes( event.data ) // if its empty or the default, show empty to the user
				? undefined
				: event.data,
		};
	},
} );

const getStoreCountryOption = async () =>
	resolveSelect( OPTIONS_STORE_NAME ).getOption(
		'woocommerce_default_country'
	);

const handleStoreCountryOption = assign( {
	businessInfo: (
		context: CoreProfilerStateMachineContext,
		event: DoneInvokeEvent< string | undefined >
	) => {
		return {
			...context.businessInfo,
			location: event.data,
		};
	},
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

const preFetchOptions = assign( {
	spawnPrefetchOptionsRef: ( _ctx, _evt, { action } ) => {
		spawn(
			Promise.all( [
				// @ts-expect-error -- not sure its possible to type this yet, maybe in xstate v5
				action.options.map( ( optionName: string ) =>
					resolveSelect( OPTIONS_STORE_NAME ).getOption( optionName )
				),
			] ),
			'core-profiler-prefetch-options'
		);
	},
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

const assignOnboardingProfile = assign( {
	onboardingProfile: (
		_context,
		event: DoneInvokeEvent< OnboardingProfile | undefined >
	) => event.data,
} );

const getGeolocation = async ( context: CoreProfilerStateMachineContext ) => {
	if ( context.optInDataSharing ) {
		return resolveSelect( COUNTRIES_STORE_NAME ).geolocate();
	}
	return undefined;
};

const preFetchGeolocation = assign( {
	spawnGeolocationRef: ( context: CoreProfilerStateMachineContext ) =>
		spawn(
			getGeolocation( context ),
			'core-profiler-prefetch-geolocation'
		),
} );

const handleGeolocation = assign( {
	geolocatedLocation: (
		_context,
		event: DoneInvokeEvent< GeolocationResponse >
	) => {
		return event.data;
	},
} );

const redirectToWooHome = () => {
	/**
	 * @todo replace with navigateTo
	 */
	window.location.href = '/wp-admin/admin.php?page=wc-admin';
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

const recordTracksBusinessInfoViewed = () => {
	recordEvent( 'storeprofiler_step_view', {
		step: 'business_info',
		wc_version: getSetting( 'wcVersion' ),
	} );
};

const recordTracksBusinessInfoCompleted = (
	_context: CoreProfilerStateMachineContext,
	event: Extract< BusinessInfoEvent, { type: 'BUSINESS_INFO_COMPLETED' } >
) => {
	recordEvent( 'storeprofiler_step_complete', {
		step: 'business_info',
		wc_version: getSetting( 'wcVersion' ),
	} );

	recordEvent( 'storeprofiler_business_info', {
		business_name_filled:
			POSSIBLY_DEFAULT_STORE_NAMES.findIndex(
				( name ) => name === event.payload.storeName
			) === -1,
		industry: event.payload.industry,
		store_location_previously_set:
			_context.onboardingProfile.is_store_country_set || false,
		geolocation_success: _context.geolocatedLocation !== undefined,
		geolocation_overruled: event.payload.geolocationOverruled,
	} );
};

const recordTracksSkipBusinessLocationViewed = () => {
	recordEvent( 'storeprofiler_step_view', {
		step: 'skip_business_location',
		wc_version: getSetting( 'wcVersion' ),
	} );
};

const recordTracksSkipBusinessLocationCompleted = () => {
	recordEvent( 'storeprofiler_step_complete', {
		step: 'skip_business_location',
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

// TODO: move the data references over to the context.onboardingProfile object which stores the entire woocommerce_onboarding_profile contents
const updateOnboardingProfileOption = (
	context: CoreProfilerStateMachineContext
) => {
	const { businessChoice, sellingOnlineAnswer, sellingPlatforms } =
		context.userProfile;

	return dispatch( OPTIONS_STORE_NAME ).updateOptions( {
		woocommerce_onboarding_profile: {
			...context.onboardingProfile,
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

const updateBusinessInfo = async (
	_ctx: CoreProfilerStateMachineContext,
	event: BusinessInfoEvent
) => {
	const refreshedOnboardingProfile = ( await resolveSelect(
		OPTIONS_STORE_NAME
	).getOption( 'woocommerce_onboarding_profile' ) ) as OnboardingProfile;
	return dispatch( OPTIONS_STORE_NAME ).updateOptions( {
		blogname: event.payload.storeName,
		woocommerce_default_country: event.payload.storeLocation,
		woocommerce_onboarding_profile: {
			...refreshedOnboardingProfile,
			is_store_country_set: true,
			industry: [ event.payload.industry ],
		},
	} );
};

const persistBusinessInfo = assign( {
	persistBusinessInfoRef: (
		_ctx: CoreProfilerStateMachineContext,
		event: BusinessInfoEvent
	) =>
		spawn(
			updateBusinessInfo( _ctx, event ),
			'core-profiler-update-business-info'
		),
} );

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
		extensionsBundles.find(
			( bundle ) => bundle.key === 'obw/core-profiler'
		)?.plugins || []
	);
};

const handlePlugins = assign( {
	pluginsAvailable: ( _context, event: DoneInvokeEvent< Extension[] > ) =>
		event.data,
} );

export const preFetchActions = {
	preFetchGetPlugins,
	preFetchGetCountries,
	preFetchGeolocation,
	preFetchOptions,
};

export const recordTracksActions = {
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
	recordTracksBusinessInfoViewed,
	recordTracksBusinessInfoCompleted,
};

const coreProfilerMachineActions = {
	...preFetchActions,
	...recordTracksActions,
	handlePlugins,
	updateTrackingOption,
	handleTrackingOption,
	handleGeolocation,
	handleStoreNameOption,
	handleStoreCountryOption,
	assignOptInDataSharing,
	handleCountries,
	handleOnboardingProfileOption,
	assignOnboardingProfile,
	persistBusinessInfo,
	redirectToWooHome,
};

const coreProfilerMachineServices = {
	getAllowTrackingOption,
	getStoreNameOption,
	getStoreCountryOption,
	getCountries,
	getGeolocation,
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
		geolocatedLocation: undefined,
		businessInfo: {
			storeName: undefined,
			industry: undefined,
			storeCountryPreviouslySet: false,
			location: 'US:CA',
		},
		countries: [] as CountryStateOption[],
		pluginsAvailable: [],
		pluginsInstallationErrors: [],
		pluginsSelected: [],
		loader: {},
		onboardingProfile: {} as OnboardingProfile,
	} as CoreProfilerStateMachineContext,
	states: {
		initializing: {
			entry: [
				// these prefetch tasks are spawned actors in the background and do not block progression of the state machine
				'preFetchGetPlugins',
				'preFetchGetCountries',
				{
					type: 'preFetchOptions',
					options: [
						'blogname',
						'woocommerce_onboarding_profile',
						'woocommerce_default_country',
					],
				},
			],
			type: 'parallel',
			states: {
				// if we have any other init tasks to do in parallel, add them as a parallel state here.
				// this blocks the introOptIn UI from loading keep that in mind when adding new tasks here
				trackingOption: {
					initial: 'fetching',
					states: {
						fetching: {
							invoke: {
								src: 'getAllowTrackingOption',
								onDone: [
									{
										actions: [ 'handleTrackingOption' ],
										target: 'done',
									},
								],
								onError: {
									target: 'done', // leave it as initialised default on error
								},
							},
						},
						done: {
							type: 'final',
						},
					},
				},
			},
			onDone: {
				target: 'introOptIn',
				// TODO: at this point, we can handle the URL path param if any and jump to the correct page
			},
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
						actions: [
							'handleOnboardingProfileOption',
							'assignOnboardingProfile',
						],
						target: 'userProfile',
					},
				],
				onError: {
					target: 'userProfile',
				},
			},
		},
		userProfile: {
			entry: [ 'recordTracksUserProfileViewed', 'preFetchGeolocation' ],
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
			type: 'parallel',
			states: {
				geolocation: {
					initial: 'checkDataOptIn',
					states: {
						checkDataOptIn: {
							// if the user has opted out of data sharing, we skip the geolocation step
							always: [
								{
									cond: ( context ) =>
										context.optInDataSharing,
									target: 'fetching',
								},
								{
									target: 'done',
								},
							],
						},
						fetching: {
							invoke: {
								src: 'getGeolocation',
								onDone: {
									target: 'done',
									actions: 'handleGeolocation',
								},
								// onError TODO: handle error
							},
						},
						done: {
							type: 'final',
						},
					},
				},
				storeCountryOption: {
					initial: 'fetching',
					states: {
						fetching: {
							invoke: {
								src: 'getStoreCountryOption',
								onDone: [
									{
										actions: [ 'handleStoreCountryOption' ],
										target: 'done',
									},
								],
								onError: {
									target: 'done',
								},
							},
						},
						done: {
							type: 'final',
						},
					},
				},
				storeNameOption: {
					initial: 'fetching',
					states: {
						fetching: {
							invoke: {
								src: 'getStoreNameOption',
								onDone: [
									{
										actions: [ 'handleStoreNameOption' ],
										target: 'done',
									},
								],
								onError: {
									target: 'done', // leave it as initialised default on error
								},
							},
						},
						done: {
							type: 'final',
						},
					},
				},
				countries: {
					initial: 'fetching',
					states: {
						fetching: {
							invoke: {
								src: 'getCountries',
								onDone: {
									target: 'done',
									actions: 'handleCountries',
								},
							},
						},
						done: {
							type: 'final',
						},
					},
				},
			},
			// onDone is reached when child parallel states are all at their final states
			onDone: {
				target: 'businessInfo',
			},
			meta: {
				progress: 50,
			},
		},
		businessInfo: {
			entry: [ 'recordTracksBusinessInfoViewed' ],
			on: {
				BUSINESS_INFO_COMPLETED: {
					target: 'prePlugins',
					actions: [
						'persistBusinessInfo',
						'recordTracksBusinessInfoCompleted',
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
							) => {
								return {
									..._context.businessInfo,
									location: event.payload.storeLocation,
								};
							},
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
								context.businessInfo.location as string
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
				onDone: [
					{
						target: 'pluginsSkipped',
						cond: ( context, event ) => event.data.length === 0,
					},
					{ target: 'plugins', actions: 'handlePlugins' },
				],
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
						completed: true,
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
						business_extensions:
							event.payload.installationCompletedResult.installedPlugins.map(
								( extension: InstalledPlugin ) =>
									extension.plugin
							),
						completed: true,
					} );
				},
				onDone: {
					actions: 'redirectToWooHome',
				},
			},
			meta: {
				component: Loader,
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

								let stageIndex = 0;

								if ( progress > 30 ) {
									stageIndex = 1;
								} else if ( progress > 60 ) {
									stageIndex = 2;
								}

								return {
									useStages: 'plugins',
									progress,
									stageIndex,
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
								installed_extensions:
									installationCompletedResult.installedPlugins.map(
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

	const [ state, send ] = useMachine( augmentedStateMachine, {
		devTools: process.env.NODE_ENV === 'development',
	} );
	const stateValue =
		typeof state.value === 'object'
			? Object.keys( state.value )[ 0 ]
			: state.value;
	const currentNodeMeta = state.meta[ `coreProfiler.${ stateValue }` ]
		? state.meta[ `coreProfiler.${ stateValue }` ]
		: undefined;
	const navigationProgress = currentNodeMeta?.progress; // This value is defined in each state node's meta tag, we can assume it is 0-100
	const CurrentComponent =
		currentNodeMeta?.component ?? ( () => ( <ProfileSpinner /> ) ); // If no component is defined for the state then its a loading state

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
