/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import {
	CoreProfilerStateMachineContext,
	UserProfileEvent,
	BusinessInfoEvent,
} from '../../';
import { POSSIBLY_DEFAULT_STORE_NAMES } from '../../pages/BusinessInfo';

const recordTracksStepViewed = (
	_ctx: unknown,
	_evt: unknown,
	{ action }: { action: unknown }
) => {
	const { step } = action as { step: string };
	recordEvent( 'storeprofiler_step_view', {
		step,
		wc_version: getSetting( 'wcVersion' ),
	} );
};

const recordTracksStepSkipped = (
	_ctx: unknown,
	_evt: unknown,
	{ action }: { action: unknown }
) => {
	const { step } = action as { step: string };
	recordEvent( `storeprofiler_${ step }_skip` );
};

const recordTracksIntroCompleted = () => {
	recordEvent( 'storeprofiler_step_complete', {
		step: 'store_details',
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

const recordTracksSkipBusinessLocationCompleted = () => {
	recordEvent( 'storeprofiler_step_complete', {
		step: 'skip_business_location',
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

export default {
	recordTracksStepViewed,
	recordTracksStepSkipped,
	recordTracksIntroCompleted,
	recordTracksUserProfileCompleted,
	recordTracksSkipBusinessLocationCompleted,
	recordTracksBusinessInfoCompleted,
};
