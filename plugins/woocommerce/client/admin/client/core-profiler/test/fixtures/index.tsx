/**
 * External dependencies
 */
import type { Extension } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import type { CoreProfilerPageComponent } from '../../index';

type DriverProps = Pick< CoreProfilerPageComponent, 'sendEvent' >;

export const possibleDefaultStoreNames = [
	undefined,
	'woocommerce',
	'Site Title',
	'',
];

export const exampleExtension: Extension = {
	key: 'example-extension',
	name: 'Example Extension',
	label: 'Example Extension',
	description: 'Example extension for controller flow tests.',
	image_url: '',
	manage_url: '',
	is_activated: false,
	is_built_by_wc: false,
	is_visible: true,
	requires_jpc: false,
};

export const IntroOptInDriver = ( { sendEvent }: DriverProps ) => (
	<div>
		<button
			onClick={ () =>
				sendEvent( {
					type: 'INTRO_COMPLETED',
					payload: { optInDataSharing: true },
				} )
			}
		>
			Complete intro
		</button>
		<button
			onClick={ () =>
				sendEvent( {
					type: 'INTRO_SKIPPED',
					payload: { optInDataSharing: true },
				} )
			}
		>
			Skip guided setup
		</button>
	</div>
);

export const UserProfileDriver = ( { sendEvent }: DriverProps ) => (
	<div>
		<button
			onClick={ () =>
				sendEvent( {
					type: 'USER_PROFILE_COMPLETED',
					payload: {
						userProfile: {
							businessChoice: 'im_just_starting_my_business',
							sellingOnlineAnswer: null,
							sellingPlatforms: null,
						},
					},
				} )
			}
		>
			Complete user profile
		</button>
		<button
			onClick={ () =>
				sendEvent( {
					type: 'USER_PROFILE_SKIPPED',
					payload: { userProfile: { skipped: true } },
				} )
			}
		>
			Skip user profile
		</button>
	</div>
);

export const BusinessInfoDriver = ( { sendEvent }: DriverProps ) => (
	<div>
		<button
			onClick={ () =>
				sendEvent( {
					type: 'BUSINESS_INFO_COMPLETED',
					payload: {
						storeName: 'Example Store',
						industry: 'other',
						storeLocation: 'US:CA',
						geolocationOverruled: false,
						isOptInMarketing: true,
						storeEmailAddress: 'merchant@example.test',
					},
				} )
			}
		>
			Complete business info
		</button>
		<button
			onClick={ () => sendEvent( { type: 'SKIP_BUSINESS_INFO_STEP' } ) }
		>
			Skip business info
		</button>
	</div>
);

export const BusinessLocationDriver = ( { sendEvent }: DriverProps ) => (
	<button
		onClick={ () =>
			sendEvent( {
				type: 'BUSINESS_LOCATION_COMPLETED',
				payload: { storeLocation: 'US:CA' },
			} )
		}
	>
		Complete business location
	</button>
);

export const PluginsDriver = ( { sendEvent }: DriverProps ) => (
	<div>
		<button onClick={ () => sendEvent( { type: 'PLUGINS_PAGE_SKIPPED' } ) }>
			Skip extensions
		</button>
		<button
			onClick={ () =>
				sendEvent( {
					type: 'PLUGINS_PAGE_COMPLETED_WITHOUT_SELECTING_PLUGINS',
				} )
			}
		>
			Continue without extensions
		</button>
		<button
			onClick={ () =>
				sendEvent( {
					type: 'PLUGINS_LEARN_MORE_LINK_CLICKED',
					payload: {
						plugin: 'example-extension',
						learnMoreLink: 'https://example.test/extension',
					},
				} )
			}
		>
			Learn more about extension
		</button>
		<button
			onClick={ () =>
				sendEvent( {
					type: 'PLUGINS_INSTALLATION_REQUESTED',
					payload: {
						pluginsShown: [ 'example-extension' ],
						pluginsSelected: [ 'example-extension' ],
						pluginsUnselected: [],
					},
				} )
			}
		>
			Install extension
		</button>
	</div>
);

export const NoPermissionsDriver = () => <div>No installation permission</div>;

export const LoaderDriver = () => null;

export const SpinnerDriver = () => null;
