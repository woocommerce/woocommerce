/**
 * Internal dependencies
 */
import ApiClient, { WC_ADMIN_API_PATH } from './api-client';

/**
 * Update the onboarding profile using a call to the wc-admin API.
 *
 * @param {Object} data the data to send to the onboarding/profile endpoint
 * @return {Promise<void>} the value for the skipped field in the onboarding profile
 */

export async function updateOnboardingProfile( data ) {
	const apiClient = ApiClient.getInstance();
	const path = `${ WC_ADMIN_API_PATH }/onboarding/profile`;

	const updateResponse = await apiClient.put( path, data );

	if ( updateResponse.statusCode !== 200 ) {
		console.error(
			'Failed to update onboarding profile:',
			updateResponse.statusCode
		);
	}

	const newProfileResponse = await apiClient.get( path );

	return newProfileResponse.statusCode === 200
		? newProfileResponse.data
		: null;
}

/**
 * Skip the onboarding wizard using a call to the wc-admin API.
 *
 * @return {Promise<void>} the value for the skipped field in the onboarding profile
 */

export async function skipOnboardingWizard() {
	const profile = await updateOnboardingProfile( {
		skipped: true,
	} );
	return profile?.skipped;
}
