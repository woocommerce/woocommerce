/**
 * External dependencies
 */
import { createMachine, assign } from 'xstate';
import { useMachine } from '@xstate/react';
import { useEffect } from '@wordpress/element';
import { ExtensionList } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { IntroOptIn } from './pages/IntroOptIn';
import { UserProfile } from './pages/UserProfile';
import { BusinessInfo } from './pages/BusinessInfo';
import { BusinessLocation } from './pages/BusinessLocation';

/** Uncomment below to display xstate inspector during development */
// import { inspect } from '@xstate/inspect';
// inspect( {
// 	// url: 'https://stately.ai/viz?inspect', // (default)
// 	iframe: false, // open in new window
// } );

// TODO: Typescript support can be improved, but for now lets write the types ourselves
// https://stately.ai/blog/introducing-typescript-typegen-for-xstate

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
	userProfile: { foo: { bar: 'qux' }; skipped: false } | { skipped: true };
	geolocatedLocation: {
		location: string;
	};
	extensionsAvailable: ExtensionList[ 'plugins' ] | [];
	extensionsSelected: string[]; // extension slugs
	businessInfo: { foo?: { bar: 'qux' }; location: string };
};

const Extensions = ( {
	context,
	sendEvent,
}: {
	context: CoreProfilerStateMachineContext;
	sendEvent: ( payload: ExtensionsEvent ) => void;
} ) => {
	return (
		// TOOD: we need to fetch the extensions list from the API as part of initializing the profiler
		<>
			<div>Extensions</div>
			<div>{ context.extensionsAvailable }</div>
			<button
				onClick={ () =>
					sendEvent( {
						type: 'EXTENSIONS_COMPLETED',
						payload: {
							extensionsSelected: [ 'woocommerce-payments' ],
						},
					} )
				}
			>
				Next
			</button>
		</>
	);
};

const coreProfilerStateMachineDefinition = createMachine( {
	id: 'coreProfiler',
	initial: 'initializing',
	context: {
		// these are safe default values if for some reason the steps fail to complete correctly
		// actual defaults displayed to the user should be handled in the steps themselves
		optInDataSharing: false,
		userProfile: { skipped: true },
		geolocatedLocation: { location: 'US:CA' },
		businessInfo: { location: 'US:CA' },
		extensionsAvailable: [],
		extensionsSelected: [],
	} as CoreProfilerStateMachineContext,
	states: {
		initializing: {
			on: {
				INITIALIZATION_COMPLETE: 'introOptIn',
			},
			invoke: [
				{
					src: 'initializeProfiler',
				},
			],
			meta: {
				progress: 0,
			},
		},
		introOptIn: {
			on: {
				INTRO_COMPLETED: {
					target: 'userProfile',
					actions: [
						assign( {
							optInDataSharing: (
								_context,
								event: IntroOptInEvent
							) => event.payload.optInDataSharing, // sets context.optInDataSharing to the payload of the event
						} ),
					],
				},
				INTRO_SKIPPED: {
					// if the user skips the intro, we set the optInDataSharing to false and go to the Business Location page
					target: 'skipFlowBusinessLocation',
					actions: [
						assign( {
							optInDataSharing: (
								context,
								event: IntroOptInEvent
							) => event.payload.optInDataSharing, // sets context.optInDataSharing to the payload of the event, which is always false
						} ),
					],
				},
			},
			meta: {
				progress: 20,
				component: IntroOptIn,
			},
		},
		userProfile: {
			on: {
				USER_PROFILE_COMPLETED: {
					target: 'preBusinessInfo',
					actions: [
						assign( {
							userProfile: ( context, event: UserProfileEvent ) =>
								event.payload.userProfile, // sets context.userProfile to the payload of the event
						} ),
					],
				},
				USER_PROFILE_SKIPPED: {
					target: 'preBusinessInfo',
					actions: [
						assign( {
							userProfile: ( context, event: UserProfileEvent ) =>
								event.payload.userProfile, // assign context.userProfile to the payload of the event
						} ),
					],
				},
			},
			meta: {
				progress: 40,
				component: UserProfile,
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
		skipFlowBusinessLocation: {
			on: {
				BUSINESS_LOCATION_COMPLETED: {
					target: 'settingUpStore',
					actions: [
						assign( {
							businessInfo: (
								_context,
								event: BusinessLocationEvent
							) => event.payload.businessInfo, // assign context.businessInfo to the payload of the event
						} ),
					],
				},
			},
			meta: {
				progress: 80,
				component: BusinessLocation,
			},
		},
		preExtensions: {
			always: [
				// immediately transition to extensions without any events as long as extensions fetching parallel has completed
				{
					target: 'extensions',
					cond: () => true, // TODO: use a custom function to check on the parallel state using meta when we implement that. https://xstate.js.org/docs/guides/guards.html#guards-condition-functions
				},
			],
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

const CoreProfilerController = ( {} ) => {
	const [ state, send ] = useMachine(
		coreProfilerStateMachineDefinition.withConfig( {
			services: {
				initializeProfiler: () => ( sendBack ) => {
					// TODO: placeholder to simulate initialization time
					const interval = setInterval( () => {
						sendBack( {
							type: 'INITIALIZATION_COMPLETE',
						} );
					}, 1000 );

					return () => {
						clearInterval( interval );
					};
				},
			},
		} ),
		{ devTools: true }
	);

	const currentNodeMeta = state.meta[ `coreProfiler.${ state.value }` ]
		? state.meta[ `coreProfiler.${ state.value }` ]
		: undefined;
	const navigationProgress = currentNodeMeta?.progress; // This value is defined in each state node's meta tag, we can assume it is 0-100
	const CurrentComponent =
		currentNodeMeta?.component ?? ( () => <div>Insert Spinner</div> ); // If no component is defined for the state then its a loading state

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
			<div>Core Profiler Placeholder</div>
			<div>{ `${ navigationProgress }% complete` }</div>
			<div
				className={ `woocommerce-profile-wizard__container woocommerce-profile-wizard__step-${ state.value }` }
			>
				<div>{ `Current State: ${ state.value }` }</div>
				<div>
					{ `Context values: ${ JSON.stringify(
						state.context,
						null,
						2
					) }` }
				</div>
				<div>
					{
						<CurrentComponent
							sendEvent={ send }
							context={ state.context }
						/>
					}
				</div>
			</div>
		</>
	);
};
export default CoreProfilerController;
