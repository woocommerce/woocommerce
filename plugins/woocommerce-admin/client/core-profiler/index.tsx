/* eslint-disable xstate/no-inline-implementation */
/**
 * External dependencies
 */
import {
	createMachine,
	ActionMeta,
	assign,
	DoneInvokeEvent,
	actions,
	spawn,
	AnyEventObject,
	BaseActionObject,
	Sender,
} from 'xstate';
import { useMachine, useSelector } from '@xstate/react';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { resolveSelect, dispatch } from '@wordpress/data';
import {
	updateQueryString,
	getQuery,
	getNewPath,
} from '@woocommerce/navigation';
import {
	ExtensionList,
	OPTIONS_STORE_NAME,
	COUNTRIES_STORE_NAME,
	Country,
	ONBOARDING_STORE_NAME,
	Extension,
	GeolocationResponse,
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
	USER_STORE_NAME,
	WCUser,
} from '@woocommerce/data';
import { initializeExPlat } from '@woocommerce/explat';
import { CountryStateOption } from '@woocommerce/onboarding';
import { getAdminLink } from '@woocommerce/settings';
import CurrencyFactory from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import { findComponentMeta } from '~/utils/xstate/find-component';
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
import { CoreProfilerLoader } from './components/loader/Loader';
import { Plugins } from './pages/Plugins';
import { getPluginSlug, useFullScreen } from '~/utils';
import './style.scss';
import {
	InstallationCompletedResult,
	InstalledPlugin,
	PluginInstallError,
	pluginInstallerMachine,
} from './services/installAndActivatePlugins';
import { ProfileSpinner } from './components/profile-spinner/profile-spinner';
import recordTracksActions from './actions/tracks';
import { ComponentMeta } from './types';
import { getCountryCode } from '~/dashboard/utils';
import { getAdminSetting } from '~/utils/admin-settings';

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
		isOptInMarketing: boolean;
		storeEmailAddress: string;
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
		pluginsShown: string[];
		pluginsSelected: string[];
		pluginsUnselected: string[];
	};
};

export type PluginsLearnMoreLinkClicked = {
	type: 'PLUGINS_LEARN_MORE_LINK_CLICKED';
	payload: {
		plugin: string;
		learnMoreLink: string;
	};
};

export type CoreProfilerPageComponent = ( props: {
	navigationProgress: number | undefined;
	sendEvent: Sender< AnyEventObject >;
	context: CoreProfilerStateMachineContext;
} ) => React.ReactElement | null;

export type OnboardingProfile = {
	business_choice: BusinessChoice;
	industry: Array< IndustryChoice >;
	selling_online_answer: SellingOnlineAnswer | null;
	selling_platforms: SellingPlatform[] | null;
	skip?: boolean;
	is_store_country_set: boolean | null;
	store_email?: string;
	is_agree_marketing?: boolean;
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
	jetpackAuthUrl?: string;
	persistBusinessInfoRef?: ReturnType< typeof spawn >;
	spawnUpdateOnboardingProfileOptionRef?: ReturnType< typeof spawn >;
	spawnGeolocationRef?: ReturnType< typeof spawn >;
	currentUserEmail: string | undefined;
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
			() => resolveSelect( COUNTRIES_STORE_NAME ).getCountries(),
			'core-profiler-prefetch-countries'
		),
} );

const preFetchOptions = assign( {
	spawnPrefetchOptionsRef: ( _context, _event, { action } ) => {
		spawn(
			() =>
				Promise.all( [
					// @ts-expect-error -- not sure its possible to type this yet, maybe in xstate v5
					action.options.map( ( optionName: string ) =>
						resolveSelect( OPTIONS_STORE_NAME ).getOption(
							optionName
						)
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

const getCurrentUserEmail = async () => {
	const currentUser: WCUser< 'email' > = await resolveSelect(
		USER_STORE_NAME
	).getCurrentUser();
	return currentUser?.email;
};

const assignCurrentUserEmail = assign( {
	currentUserEmail: (
		_context,
		event: DoneInvokeEvent< string | undefined >
	) => {
		if (
			event.data &&
			event.data.length > 0 &&
			event.data !== 'wordpress@example.com' // wordpress default prefilled email address
		) {
			return event.data;
		}
		return undefined;
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
			() => getGeolocation( context ),
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
	window.location.href = getNewPath( {}, '/', {} );
};

const redirectToJetpackAuthPage = (
	_context: CoreProfilerStateMachineContext,
	event: { data: { url: string } }
) => {
	const url = new URL( event.data.url );
	url.searchParams.set( 'installed_ext_success', '1' );
	const selectedPlugin = _context.pluginsSelected.find(
		( plugin ) => plugin === 'jetpack' || plugin === 'jetpack-boost'
	);

	if ( selectedPlugin ) {
		const pluginName =
			selectedPlugin === 'jetpack' ? 'jetpack-ai' : 'jetpack-boost';
		url.searchParams.set( 'plugin_name', pluginName );
	}

	window.location.href = url.toString();
};

const updateTrackingOption = async (
	context: CoreProfilerStateMachineContext
) => {
	await new Promise< void >( ( resolve ) => {
		if (
			context.optInDataSharing &&
			typeof window.wcTracks.enable === 'function'
		) {
			window.wcTracks.enable( () => {
				initializeExPlat();
				resolve(); // resolve the promise only after explat is enabled by the callback
			} );
		} else {
			if ( ! context.optInDataSharing ) {
				window.wcTracks.isEnabled = false;
			}
			resolve();
		}
	} );

	const trackingValue = context.optInDataSharing ? 'yes' : 'no';
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

const spawnUpdateOnboardingProfileOption = assign( {
	spawnUpdateOnboardingProfileOptionRef: (
		context: CoreProfilerStateMachineContext
	) =>
		spawn(
			() => updateOnboardingProfileOption( context ),
			'update-onboarding-profile'
		),
} );

const updateBusinessLocation = ( countryAndState: string ) => {
	return dispatch( OPTIONS_STORE_NAME ).updateOptions( {
		woocommerce_default_country: countryAndState,
	} );
};

const updateStoreCurrency = async ( countryAndState: string ) => {
	const { general: settings = {} } = await resolveSelect(
		SETTINGS_STORE_NAME
	).getSettings( 'general' );

	const countryCode = getCountryCode( countryAndState ) as string;
	const { currencySymbols = {}, localeInfo = {} } = getAdminSetting(
		'onboarding',
		{}
	);
	const currencySettings = CurrencyFactory().getDataForCountry(
		countryCode,
		localeInfo,
		currencySymbols
	) as {
		code: string;
		symbolPosition: string;
		thousandSeparator: string;
		decimalSeparator: string;
		precision: string;
	};

	if ( Object.keys( currencySettings ).length === 0 ) {
		return;
	}

	return dispatch( SETTINGS_STORE_NAME ).updateAndPersistSettingsForGroup(
		'general',
		{
			general: {
				...settings,
				woocommerce_currency: currencySettings.code,
				woocommerce_currency_pos: currencySettings.symbolPosition,
				woocommerce_price_thousand_sep:
					currencySettings.thousandSeparator,
				woocommerce_price_decimal_sep:
					currencySettings.decimalSeparator,
				woocommerce_price_num_decimals: currencySettings.precision,
			},
		}
	);
};

const assignStoreLocation = assign( {
	businessInfo: (
		context: CoreProfilerStateMachineContext,
		event: BusinessLocationEvent
	) => {
		return {
			...context.businessInfo,
			location: event.payload.storeLocation,
		};
	},
} );

const assignUserProfile = assign( {
	userProfile: ( context, event: UserProfileEvent ) =>
		event.payload.userProfile, // sets context.userProfile to the payload of the event
} );

const updateBusinessInfo = async (
	_context: CoreProfilerStateMachineContext,
	event: AnyEventObject
) => {
	const refreshedOnboardingProfile = ( await resolveSelect(
		OPTIONS_STORE_NAME
	).getOption( 'woocommerce_onboarding_profile' ) ) as OnboardingProfile;

	await updateStoreCurrency( event.payload.storeLocation );

	return dispatch( OPTIONS_STORE_NAME ).updateOptions( {
		blogname: event.payload.storeName,
		woocommerce_default_country: event.payload.storeLocation,
		woocommerce_onboarding_profile: {
			...refreshedOnboardingProfile,
			is_store_country_set: true,
			industry: [ event.payload.industry ],
			is_agree_marketing: event.payload.isOptInMarketing,
			store_email:
				event.payload.storeEmailAddress.length > 0
					? event.payload.storeEmailAddress
					: null,
		},
	} );
};

const persistBusinessInfo = assign( {
	persistBusinessInfoRef: (
		context: CoreProfilerStateMachineContext,
		event: BusinessInfoEvent
	) =>
		spawn(
			() => updateBusinessInfo( context, event ),
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

const preFetchIsJetpackConnected = assign( {
	isJetpackConnectedRef: () =>
		spawn(
			() => resolveSelect( PLUGINS_STORE_NAME ).isJetpackConnected(),
			'core-profiler-prefetch-is-jetpack-connected'
		),
} );

const preFetchJetpackAuthUrl = assign( {
	jetpackAuthUrlRef: () =>
		spawn(
			() =>
				resolveSelect( ONBOARDING_STORE_NAME ).getJetpackAuthUrl( {
					redirectUrl: getAdminLink( 'admin.php?page=wc-admin' ),
					from: 'woocommerce-core-profiler',
				} ),
			'core-profiler-prefetch-jetpack-auth-url'
		),
} );

/**
 * Prefetch it so that @wp/data caches it and there won't be a loading delay when its used
 */
const preFetchGetPlugins = assign( {
	extensionsRef: () =>
		spawn(
			() => resolveSelect( ONBOARDING_STORE_NAME ).getFreeExtensions(),
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

/** Special callback that is used to trigger a navigation event if the user uses the browser's back or foward buttons */
const browserPopstateHandler = () => ( sendBack: Sender< AnyEventObject > ) => {
	const popstateHandler = () => {
		sendBack( 'EXTERNAL_URL_UPDATE' );
	};
	window.addEventListener( 'popstate', popstateHandler );
	return () => {
		window.removeEventListener( 'popstate', popstateHandler );
	};
};

const handlePlugins = assign< CoreProfilerStateMachineContext >( {
	pluginsAvailable: ( _context, event ) =>
		( event as DoneInvokeEvent< Extension[] > ).data,
} );

export type CoreProfilerMachineAssign = (
	ctx: CoreProfilerStateMachineContext,
	evt: AnyEventObject,
	{
		action: { step },
	}: ActionMeta<
		CoreProfilerStateMachineContext,
		AnyEventObject,
		BaseActionObject
	>
) => void;

const updateQueryStep: CoreProfilerMachineAssign = (
	_context,
	_evt,
	{ action }
) => {
	const { step } = getQuery() as { step: string };
	// only update the query string if it has changed
	if ( action.step !== step ) {
		updateQueryString( { step: action.step } );
	}
};

const assignPluginsSelected = assign( {
	pluginsSelected: ( _context, event: PluginsInstallationRequestedEvent ) => {
		return event.payload.pluginsSelected.map( getPluginSlug );
	},
} );

export const preFetchActions = {
	preFetchGetPlugins,
	preFetchGetCountries,
	preFetchGeolocation,
	preFetchOptions,
	preFetchIsJetpackConnected,
	preFetchJetpackAuthUrl,
};

const coreProfilerMachineActions = {
	...preFetchActions,
	...recordTracksActions,
	handlePlugins,
	updateQueryStep,
	handleTrackingOption,
	handleGeolocation,
	handleStoreNameOption,
	handleStoreCountryOption,
	assignOptInDataSharing,
	assignStoreLocation,
	assignPluginsSelected,
	assignUserProfile,
	handleCountries,
	handleOnboardingProfileOption,
	assignOnboardingProfile,
	assignCurrentUserEmail,
	persistBusinessInfo,
	spawnUpdateOnboardingProfileOption,
	redirectToWooHome,
	redirectToJetpackAuthPage,
};

const coreProfilerMachineServices = {
	getAllowTrackingOption,
	getStoreNameOption,
	getStoreCountryOption,
	getCountries,
	getGeolocation,
	getOnboardingProfileOption,
	getCurrentUserEmail,
	getPlugins,
	browserPopstateHandler,
	updateBusinessInfo,
	updateTrackingOption,
};
export const coreProfilerStateMachineDefinition = createMachine( {
	id: 'coreProfiler',
	initial: 'navigate',
	predictableActionArguments: true, // recommended setting: https://xstate.js.org/docs/guides/actions.html
	invoke: {
		src: 'browserPopstateHandler',
	},
	on: {
		EXTERNAL_URL_UPDATE: {
			target: 'navigate',
		},
	},
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
		jetpackAuthUrl: undefined,
		currentUserEmail: undefined,
	} as CoreProfilerStateMachineContext,
	states: {
		navigate: {
			always: [
				/**
				 * The 'navigate' state forwards the progress to whichever step is
				 *  specified in the query string. If no step is specified, it will
				 *  default to introOptIn.
				 *
				 *  Each top level state must be responsible for populating their own
				 *  context data dependencies, as it is possible that they are the
				 *  first state to be loaded due to the navigation jump.
				 *
				 *  Each top level state must also be responsible for updating the
				 *  query string to reflect their own state, using the 'updateQueryStep'
				 *  action.
				 */
				{
					target: '#introOptIn',
					cond: {
						type: 'hasStepInUrl',
						step: 'intro-opt-in',
					},
				},
				{
					target: '#userProfile',
					cond: { type: 'hasStepInUrl', step: 'user-profile' },
				},
				{
					target: '#businessInfo',
					cond: { type: 'hasStepInUrl', step: 'business-info' },
				},
				{
					target: '#plugins',
					cond: { type: 'hasStepInUrl', step: 'plugins' },
				},
				{
					target: '#skipGuidedSetup',
					cond: { type: 'hasStepInUrl', step: 'skip-guided-setup' },
				},
				{
					target: 'introOptIn',
				},
			],
		},
		introOptIn: {
			id: 'introOptIn',
			initial: 'preIntroOptIn',
			states: {
				preIntroOptIn: {
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
												actions: [
													'handleTrackingOption',
												],
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
					},
					meta: {
						progress: 0,
					},
				},
				introOptIn: {
					on: {
						INTRO_COMPLETED: {
							target: 'postIntroOptIn',
							actions: [ 'assignOptInDataSharing' ],
						},
						INTRO_SKIPPED: {
							// if the user skips the intro, we set the optInDataSharing to false and go to the Business Location page
							target: '#skipGuidedSetup',
							actions: [
								'assignOptInDataSharing',
								'updateTrackingOption',
							],
						},
					},
					meta: {
						progress: 20,
						component: IntroOptIn,
					},
				},
				postIntroOptIn: {
					invoke: {
						src: 'updateTrackingOption',
						onDone: {
							actions: [ 'recordTracksIntroCompleted' ],
							target: '#userProfile',
						},
					},
				},
			},
		},
		userProfile: {
			id: 'userProfile',
			initial: 'preUserProfile',
			states: {
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
					meta: {
						progress: 40,
						component: UserProfile,
					},
					entry: [
						{
							type: 'recordTracksStepViewed',
							step: 'user_profile',
						},
						{ type: 'updateQueryStep', step: 'user-profile' },
						'preFetchGeolocation',
					],
					on: {
						USER_PROFILE_COMPLETED: {
							target: 'postUserProfile',
							actions: [ 'assignUserProfile' ],
						},
						USER_PROFILE_SKIPPED: {
							target: 'postUserProfile',
							actions: [ 'assignUserProfile' ],
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
							actions: [
								{
									type: 'recordTracksStepSkipped',
									step: 'user_profile',
								},
							],
						},
					] ),
				},
				postUserProfile: {
					entry: [ 'spawnUpdateOnboardingProfileOption' ],
					always: {
						target: '#businessInfo',
					},
				},
			},
		},
		businessInfo: {
			id: 'businessInfo',
			initial: 'preBusinessInfo',
			entry: [ { type: 'updateQueryStep', step: 'business-info' } ],
			states: {
				preBusinessInfo: {
					type: 'parallel',
					states: {
						geolocation: {
							initial: 'checkDataOptIn',
							states: {
								checkDataOptIn: {
									invoke: {
										src: 'getAllowTrackingOption',
										onDone: [
											{
												actions: [
													'handleTrackingOption',
												],
												target: 'fetching',
											},
										],
										onError: {
											target: 'done', // leave it as initialised default on error
										},
									},
								},
								fetching: {
									invoke: {
										src: 'getGeolocation',
										onDone: {
											target: 'done',
											actions: 'handleGeolocation',
										},
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
						storeCountryOption: {
							initial: 'fetching',
							states: {
								fetching: {
									invoke: {
										src: 'getStoreCountryOption',
										onDone: [
											{
												actions: [
													'handleStoreCountryOption',
												],
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
						onboardingProfileOption: {
							initial: 'fetching',
							states: {
								fetching: {
									invoke: {
										src: 'getOnboardingProfileOption',
										onDone: [
											{
												actions: [
													'assignOnboardingProfile',
												],
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
												actions: [
													'handleStoreNameOption',
												],
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
						currentUserEmail: {
							initial: 'fetching',
							states: {
								fetching: {
									invoke: {
										src: 'getCurrentUserEmail',
										onDone: {
											target: 'done',
											actions: [
												'assignCurrentUserEmail',
											],
										},
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
					},
					// onDone is reached when child parallel states fo fetching are resolved (reached final states)
					onDone: {
						target: 'businessInfo',
					},
				},
				businessInfo: {
					meta: {
						progress: 60,
						component: BusinessInfo,
					},
					entry: [
						{
							type: 'recordTracksStepViewed',
							step: 'business_info',
						},
					],
					on: {
						BUSINESS_INFO_COMPLETED: {
							target: 'postBusinessInfo',
							actions: [
								'recordTracksBusinessInfoCompleted',
								'recordTracksIsEmailChanged',
							],
						},
					},
				},
				postBusinessInfo: {
					invoke: {
						src: 'updateBusinessInfo',
						onDone: {
							target: '#plugins',
						},
						onError: {
							target: '#plugins',
						},
					},
				},
			},
		},
		skipGuidedSetup: {
			id: 'skipGuidedSetup',
			initial: 'preSkipFlowBusinessLocation',
			entry: [ { type: 'updateQueryStep', step: 'skip-guided-setup' } ],
			states: {
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
								'assignStoreLocation',
								'recordTracksSkipBusinessLocationCompleted',
							],
						},
					},
					entry: [
						{
							type: 'recordTracksStepViewed',
							step: 'skip_business_location',
						},
					],
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
									useStages: 'skippedGuidedSetup',
								},
							} ),
							invoke: {
								src: ( context ) => {
									const skipped = dispatch(
										ONBOARDING_STORE_NAME
									).updateProfileItems( {
										skipped: true,
									} );
									const businessLocation =
										updateBusinessLocation(
											context.businessInfo
												.location as string
										);
									const currencyUpdate = updateStoreCurrency(
										context.businessInfo.location as string
									);

									return Promise.all( [
										skipped,
										businessLocation,
										currencyUpdate,
									] );
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
									useStages: 'skippedGuidedSetup',
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
									useStages: 'skippedGuidedSetup',
									stageIndex: 1,
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
						component: CoreProfilerLoader,
					},
				},
			},
		},
		plugins: {
			id: 'plugins',
			initial: 'prePlugins',
			states: {
				prePlugins: {
					invoke: {
						src: 'getPlugins',
						onDone: [
							{
								target: 'pluginsSkipped',
								cond: ( _context, event ) => {
									// Skip the plugins page
									// When there is 0 plugin returned from the server
									// Or all the plugins are activated already.
									return event.data?.every(
										( plugin: Extension ) =>
											plugin.is_activated
									);
								},
							},
							{ target: 'plugins', actions: 'handlePlugins' },
						],
					},
					// add exit action to filter the extensions using a custom function here and assign it to context.extensionsAvailable
					exit: assign( {
						pluginsAvailable: ( context ) => {
							return context.pluginsAvailable.filter(
								() => true
							);
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
							dispatch(
								ONBOARDING_STORE_NAME
							).updateProfileItems( {
								is_plugins_page_skipped: true,
								completed: true,
							} );
							return promiseDelay( 3000 );
						},
						onDone: [
							{
								target: 'isJetpackConnected',
								cond: 'hasJetpackSelected',
							},
							{ actions: [ 'redirectToWooHome' ] },
						],
					},
					meta: {
						component: CoreProfilerLoader,
					},
				},
				plugins: {
					entry: [
						{ type: 'recordTracksStepViewed', step: 'plugins' },
						{ type: 'updateQueryStep', step: 'plugins' },
					],
					on: {
						PLUGINS_PAGE_SKIPPED: {
							actions: [
								{
									type: 'recordTracksStepSkipped',
									step: 'plugins',
								},
							],
							target: 'pluginsSkipped',
						},
						PLUGINS_LEARN_MORE_LINK_CLICKED: {
							actions: [
								{
									type: 'recordTracksPluginsLearnMoreLinkClicked',
									step: 'plugins',
								},
							],
						},
						PLUGINS_INSTALLATION_REQUESTED: {
							target: 'installPlugins',
							actions: [
								'assignPluginsSelected',
								'recordTracksPluginsInstallationRequest',
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
						onDone: [
							{
								target: 'isJetpackConnected',
								cond: 'hasJetpackSelected',
							},
							{ actions: 'redirectToWooHome' },
						],
					},
					meta: {
						component: CoreProfilerLoader,
						progress: 100,
					},
				},
				isJetpackConnected: {
					invoke: {
						src: async () => {
							return await resolveSelect(
								PLUGINS_STORE_NAME
							).isJetpackConnected();
						},
						onDone: [
							{
								target: 'sendToJetpackAuthPage',
								cond: ( _context, event ) => {
									return ! event.data;
								},
							},
							{ actions: 'redirectToWooHome' },
						],
					},
					meta: {
						component: CoreProfilerLoader,
						progress: 100,
					},
				},
				sendToJetpackAuthPage: {
					invoke: {
						src: async () =>
							await resolveSelect(
								ONBOARDING_STORE_NAME
							).getJetpackAuthUrl( {
								redirectUrl: getAdminLink(
									'admin.php?page=wc-admin'
								),
								from: 'woocommerce-core-profiler',
							} ),
						onDone: {
							actions: actions.choose( [
								{
									cond: ( _context, event ) =>
										event.data.success === true,
									actions: 'redirectToJetpackAuthPage',
								},
								{
									cond: ( _context, event ) =>
										event.data.success === false,
									actions: 'redirectToWooHome',
								},
							] ),
						},
					},
					meta: {
						component: CoreProfilerLoader,
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
											( event.payload
												.installedPluginIndex /
												event.payload.pluginsCount ) *
												100
										);

										let stageIndex = 0;

										if ( progress > 60 ) {
											stageIndex = 2;
										} else if ( progress > 30 ) {
											stageIndex = 1;
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
									pluginsInstallationErrors: (
										_context,
										event
									) => event.payload.errors,
								} ),
								{
									type: 'recordFailedPluginInstallations',
								},
							],
						},
						PLUGINS_INSTALLATION_COMPLETED: {
							target: 'postPluginInstallation',
							actions: [
								{
									type: 'recordSuccessfulPluginInstallation',
								},
							],
						},
					},
					entry: actions.choose( [
						{
							cond: 'hasJetpackSelected',
							actions: [
								assign( {
									loader: {
										progress: 10,
										useStages: 'plugins',
									},
								} ),
								'preFetchIsJetpackConnected',
								'preFetchJetpackAuthUrl',
							],
						},
						{
							actions: [
								assign( {
									loader: {
										progress: 10,
										useStages: 'plugins',
									},
								} ),
							],
						},
					] ),
					invoke: {
						src: pluginInstallerMachine,
						data: ( context ) => {
							return {
								selectedPlugins: context.pluginsSelected,
								pluginsAvailable: context.pluginsAvailable,
							};
						},
					},
					meta: {
						component: CoreProfilerLoader,
					},
				},
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
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore -- there seems to be a flaky error here - it fails sometimes and then not on recompile, will need to investigate further.
			actions: {
				...coreProfilerMachineActions,
				...actionOverrides,
			},
			services: {
				...coreProfilerMachineServices,
				...servicesOverrides,
			},
			guards: {
				hasStepInUrl: ( _ctx, _evt, { cond }: { cond: unknown } ) => {
					const { step = undefined } = getQuery() as { step: string };
					return (
						step === ( cond as { step: string | undefined } ).step
					);
				},
				hasJetpackSelected: ( context ) => {
					return (
						context.pluginsSelected.find(
							( plugin ) =>
								plugin === 'jetpack' ||
								plugin === 'jetpack-boost'
						) !== undefined ||
						context.pluginsAvailable.find(
							( plugin: Extension ) =>
								( plugin.key === 'jetpack' ||
									plugin.key === 'jetpack-boost' ) &&
								plugin.is_activated
						) !== undefined
					);
				},
			},
		} );
	}, [ actionOverrides, servicesOverrides ] );

	const [ state, send, service ] = useMachine( augmentedStateMachine, {
		devTools: process.env.NODE_ENV === 'development',
	} );

	// eslint-disable-next-line react-hooks/exhaustive-deps -- false positive due to function name match, this isn't from react std lib
	const currentNodeMeta = useSelector( service, ( currentState ) =>
		findComponentMeta< ComponentMeta >( currentState?.meta ?? undefined )
	);

	const navigationProgress = currentNodeMeta?.progress;

	const [ CurrentComponent, setCurrentComponent ] =
		useState< CoreProfilerPageComponent | null >( null );
	useEffect( () => {
		if ( currentNodeMeta?.component ) {
			setCurrentComponent( () => currentNodeMeta?.component );
		}
	}, [ CurrentComponent, currentNodeMeta?.component ] );

	const currentNodeCssLabel =
		state.value instanceof Object
			? Object.keys( state.value )[ 0 ]
			: state.value;

	useFullScreen( [ 'woocommerce-profile-wizard__body' ] );

	return (
		<>
			<div
				className={ `woocommerce-profile-wizard__container woocommerce-profile-wizard__step-${ currentNodeCssLabel }` }
			>
				{ CurrentComponent ? (
					<CurrentComponent
						navigationProgress={ navigationProgress }
						sendEvent={ send }
						context={ state.context }
					/>
				) : (
					<ProfileSpinner />
				) }
			</div>
		</>
	);
};
export default CoreProfilerController;
