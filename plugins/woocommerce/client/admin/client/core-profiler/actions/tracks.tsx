/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { CoreProfilerStateMachineContext } from '..';
import {
	UserProfileEvent,
	BusinessInfoEvent,
	PluginsLearnMoreLinkClickedEvent,
	PluginsInstallationCompletedWithErrorsEvent,
	PluginsInstallationCompletedEvent,
	PluginsInstallationRequestedEvent,
} from '../events';
import { POSSIBLY_DEFAULT_STORE_NAMES } from '../pages/BusinessInfo';
import {
	InstalledPlugin,
	PluginInstallError,
} from '../services/installAndActivatePlugins';
import { getPluginTrackKey, getTimeFrame } from '~/utils';
import { getPluginSlug } from '~/utils/plugins';
import { getCountryCode } from '~/dashboard/utils';

const SHIPPING_PLUGIN_SLUGS = new Set( [
	'woocommerce-shipping',
	'woocommerce-shipstation-integration',
	'packlink-pro-shipping',
] );

const isShippingPlugin = ( pluginKey: string ) =>
	SHIPPING_PLUGIN_SLUGS.has( getPluginSlug( pluginKey ) );

const getShippingPartnerTrackingBase = (
	context: CoreProfilerStateMachineContext
) => {
	const shippingPlugins = context.pluginsAvailable
		.filter( ( plugin ) => isShippingPlugin( plugin.key ) )
		.map( ( plugin ) => getPluginSlug( plugin.key ) )
		.join( ',' );

	return {
		context: 'core-profiler' as const,
		country: getCountryCode( context.businessInfo.location ) ?? '',
		plugins: shippingPlugins,
	};
};

const recordTracksStepViewed = ( _: unknown, params: { step: string } ) => {
	recordEvent( 'coreprofiler_step_view', {
		step: params.step,
		wc_version: getSetting( 'wcVersion' ),
	} );
};

const recordTracksStepSkipped = ( _: unknown, params: { step: string } ) => {
	recordEvent( `coreprofiler_${ params.step }_skip` );
};

const recordTracksIntroCompleted = () => {
	recordEvent( 'coreprofiler_step_complete', {
		step: 'intro_opt_in',
		wc_version: getSetting( 'wcVersion' ),
	} );
};

const recordSkipGuidedSetup = (
	_: unknown,
	{
		optInDataSharing,
	}: {
		optInDataSharing: boolean;
	}
) => {
	if ( ! optInDataSharing ) {
		return;
	}

	recordEvent( 'coreprofiler_skip_guided_setup', {
		wc_version: getSetting( 'wcVersion' ),
	} );
};

const recordTracksUserProfileCompleted = ( {
	event,
}: {
	event: Extract< UserProfileEvent, { type: 'USER_PROFILE_COMPLETED' } >;
} ) => {
	recordEvent( 'coreprofiler_step_complete', {
		step: 'user_profile',
		wc_version: getSetting( 'wcVersion' ),
	} );

	recordEvent( 'coreprofiler_user_profile', {
		business_choice: event.payload.userProfile.businessChoice,
		selling_online_answer: event.payload.userProfile.sellingOnlineAnswer,
		selling_platforms: event.payload.userProfile.sellingPlatforms
			? event.payload.userProfile.sellingPlatforms.join()
			: null,
	} );
};

const recordTracksSkipBusinessLocationCompleted = () => {
	recordEvent( 'coreprofiler_step_complete', {
		step: 'skip_business_location',
		wc_version: getSetting( 'wcVersion' ),
	} );
};

const recordTracksIsEmailChanged = ( {
	context,
	event,
}: {
	context: CoreProfilerStateMachineContext;
	event: Extract< BusinessInfoEvent, { type: 'BUSINESS_INFO_COMPLETED' } >;
} ) => {
	let emailSource, isEmailChanged;
	if ( context.onboardingProfile.store_email ) {
		emailSource = 'onboarding_profile_store_email'; // from previous entry
		isEmailChanged =
			event.payload.storeEmailAddress !==
			context.onboardingProfile.store_email;
	} else if ( context.currentUserEmail ) {
		emailSource = 'current_user_email'; // from currentUser
		isEmailChanged =
			event.payload.storeEmailAddress !== context.currentUserEmail;
	} else {
		emailSource = 'was_empty';
		isEmailChanged = event.payload.storeEmailAddress?.length > 0;
	}

	recordEvent( 'coreprofiler_email_marketing', {
		opt_in: event.payload.isOptInMarketing,
		email_field_prefilled_source: emailSource,
		email_field_modified: isEmailChanged,
	} );
};

const recordTracksBusinessInfoCompleted = ( {
	context,
	event,
}: {
	context: CoreProfilerStateMachineContext;
	event: Extract< BusinessInfoEvent, { type: 'BUSINESS_INFO_COMPLETED' } >;
} ) => {
	recordEvent( 'coreprofiler_step_complete', {
		step: 'business_info',
		wc_version: getSetting( 'wcVersion' ),
	} );

	recordEvent( 'coreprofiler_business_info', {
		business_name_filled:
			POSSIBLY_DEFAULT_STORE_NAMES.findIndex(
				( name ) => name === event.payload.storeName
			) === -1,
		industry: event.payload.industry,
		store_location_previously_set:
			context.onboardingProfile.is_store_country_set || false,
		geolocation_success: context.geolocatedLocation !== undefined,
		geolocation_overruled: event.payload.geolocationOverruled,
	} );
};

let shippingPartnerImpressionRecorded = false;

const recordShippingPartnerImpression = ( {
	context,
}: {
	context: CoreProfilerStateMachineContext;
} ) => {
	if ( shippingPartnerImpressionRecorded ) {
		return;
	}

	const trackingBase = getShippingPartnerTrackingBase( context );

	if ( trackingBase.plugins.length > 0 ) {
		shippingPartnerImpressionRecorded = true;
		recordEvent( 'shipping_partner_impression', trackingBase );
	}
};

const recordTracksPluginsInstallationRequest = ( {
	context,
	event,
}: {
	context: CoreProfilerStateMachineContext;
	event: Extract<
		PluginsInstallationRequestedEvent,
		{ type: 'PLUGINS_INSTALLATION_REQUESTED' }
	>;
} ) => {
	recordEvent( 'coreprofiler_store_extensions_continue', {
		shown: event.payload.pluginsShown || [],
		selected: event.payload.pluginsSelected || [],
		unselected: event.payload.pluginsUnselected || [],
	} );

	const trackingBase = getShippingPartnerTrackingBase( context );
	const selectedShippingPlugins = (
		event.payload.pluginsSelected || []
	).filter( ( slug ) => SHIPPING_PLUGIN_SLUGS.has( getPluginSlug( slug ) ) );

	selectedShippingPlugins.forEach( ( pluginSlug ) => {
		recordEvent( 'shipping_partner_click', {
			...trackingBase,
			selected_plugin: getPluginSlug( pluginSlug ),
		} );
	} );
};

const recordTracksPluginsLearnMoreLinkClicked = (
	{ event }: { event: PluginsLearnMoreLinkClickedEvent },
	params: { step: string }
) => {
	recordEvent( `coreprofiler_${ params.step }_learn_more_link_clicked`, {
		plugin: event.payload.plugin,
		link: event.payload.learnMoreLink,
	} );
};

const recordTracksPluginsInstallationNoPermissionError = () =>
	recordEvent( 'coreprofiler_store_extensions_no_permission_error' );

const recordFailedPluginInstallations = ( {
	context,
	event,
}: {
	context: CoreProfilerStateMachineContext;
	event: PluginsInstallationCompletedWithErrorsEvent;
} ) => {
	const failedExtensions = event.payload.errors.map(
		( error: PluginInstallError ) => getPluginTrackKey( error.plugin )
	);

	recordEvent( 'coreprofiler_store_extensions_installed_and_activated', {
		success: false,
		failed_extensions: failedExtensions,
	} );

	const trackingBase = getShippingPartnerTrackingBase( context );

	event.payload.errors.forEach( ( error: PluginInstallError ) => {
		recordEvent( 'coreprofiler_store_extension_installed_and_activated', {
			success: false,
			extension: getPluginTrackKey( error.plugin ),
			error_message: error.error,
		} );

		if ( isShippingPlugin( error.plugin ) ) {
			recordEvent( 'shipping_partner_install', {
				...trackingBase,
				selected_plugin: getPluginSlug( error.plugin ),
				success: false,
			} );
		}
	} );
};

const recordSuccessfulPluginInstallation = ( {
	context,
	event,
}: {
	context: CoreProfilerStateMachineContext;
	event: PluginsInstallationCompletedEvent;
} ) => {
	const installationCompletedResult =
		event.payload.installationCompletedResult;

	const trackData: {
		success: boolean;
		installed_extensions: string[];
		total_time: string;
		[ key: string ]: number | boolean | string | string[];
	} = {
		success: true,
		installed_extensions: installationCompletedResult.installedPlugins.map(
			( installedPlugin: InstalledPlugin ) =>
				getPluginTrackKey( installedPlugin.plugin )
		),
		total_time: getTimeFrame( installationCompletedResult.totalTime ),
	};

	const trackingBase = getShippingPartnerTrackingBase( context );

	for ( const installedPlugin of installationCompletedResult.installedPlugins ) {
		const pluginKey = getPluginTrackKey( installedPlugin.plugin );
		const installTime = getTimeFrame( installedPlugin.installTime );
		trackData[ 'install_time_' + pluginKey ] = installTime;

		recordEvent( 'coreprofiler_store_extension_installed_and_activated', {
			success: true,
			extension: pluginKey,
			install_time: installTime,
		} );

		if ( isShippingPlugin( installedPlugin.plugin ) ) {
			const slug = getPluginSlug( installedPlugin.plugin );
			recordEvent( 'shipping_partner_install', {
				...trackingBase,
				selected_plugin: slug,
				success: true,
			} );
			recordEvent( 'shipping_partner_activate', {
				...trackingBase,
				selected_plugin: slug,
				success: true,
			} );
		}
	}

	recordEvent(
		'coreprofiler_store_extensions_installed_and_activated',
		trackData
	);
};

export const resetShippingPartnerImpressionFlag = () => {
	shippingPartnerImpressionRecorded = false;
};

export default {
	recordTracksStepViewed,
	recordTracksStepSkipped,
	recordTracksIntroCompleted,
	recordSkipGuidedSetup,
	recordTracksUserProfileCompleted,
	recordTracksSkipBusinessLocationCompleted,
	recordTracksBusinessInfoCompleted,
	recordTracksPluginsLearnMoreLinkClicked,
	recordFailedPluginInstallations,
	recordTracksPluginsInstallationNoPermissionError,
	recordSuccessfulPluginInstallation,
	recordTracksPluginsInstallationRequest,
	recordTracksIsEmailChanged,
	recordShippingPartnerImpression,
};
