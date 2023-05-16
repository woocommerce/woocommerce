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
import { Extensions } from './pages/Extensions';

import './style.scss';

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

export type ExtensionsEvent = {
	type: 'EXTENSIONS_COMPLETED';
	payload: {
		extensionsSelected: CoreProfilerStateMachineContext[ 'extensionsSelected' ];
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
	geolocatedLocation: {
		location: string;
	};
	extensionsAvailable: ExtensionList[ 'plugins' ] | [];
	extensionsSelected: string[]; // extension slugs
	businessInfo: { foo?: { bar: 'qux' }; location: string };
	countries: { [ key: string ]: string };
	loader: {
		progress?: number;
		className?: string;
		useStages?: string;
		stageIndex?: number;
	};
};

// TODO: add more types here as we develop the pages
export type OnboardingProfile = {
	business_choice: BusinessChoice;
	selling_online_answer: SellingOnlineAnswer | null;
	selling_platforms: SellingPlatform[] | null;
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
	const { businessChoice, sellingOnlineAnswer, sellingPlatforms, ...rest } =
		context.userProfile;

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
const preFetchGetExtensions = assign( {
	extensionsRef: () =>
		spawn(
			resolveSelect( ONBOARDING_STORE_NAME ).getFreeExtensions(),
			'core-profiler-prefetch-extensions'
		),
} );

const getExtensions = async () => {
	const extensionsBundles = await resolveSelect(
		ONBOARDING_STORE_NAME
	).getFreeExtensions();
	return (
		extensionsBundles.find( ( bundle ) => bundle.key === 'obw/grow' )
			?.plugins || []
	);
};

const handleExtensions = assign( {
	extensionsAvailable: ( _context, event: DoneInvokeEvent< Extension[] > ) =>
		event.data,
} );

const coreProfilerMachineActions = {
	updateTrackingOption,
	preFetchGetExtensions,
	preFetchGetCountries,
	handleTrackingOption,
	handleExtensions,
	recordTracksIntroCompleted,
	recordTracksIntroSkipped,
	recordTracksIntroViewed,
	recordTracksUserProfileCompleted,
	recordTracksUserProfileSkipped,
	recordTracksUserProfileViewed,
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
	getExtensions,
	getOnboardingProfileOption,
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
		extensionsAvailable: [],
		extensionsSelected: [],
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
			entry: [ 'preFetchGetExtensions', 'preFetchGetCountries' ],
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
					target: 'preExtensions',
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
		preExtensions: {
			invoke: {
				src: 'getExtensions',
				onDone: [
					{ target: 'extensions', actions: 'handleExtensions' },
				],
			},
			// add exit action to filter the extensions using a custom function here and assign it to context.extensionsAvailable
			exit: assign( {
				extensionsAvailable: ( context ) => {
					return context.extensionsAvailable.filter( () => true );
				}, // TODO : define an extensible filter function here
			} ),
			meta: {
				progress: 70,
			},
		},
		extensions: {
			on: {
				EXTENSIONS_COMPLETED: {
					target: 'settingUpStore',
				},
			},
			meta: {
				progress: 80,
				component: Extensions,
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
