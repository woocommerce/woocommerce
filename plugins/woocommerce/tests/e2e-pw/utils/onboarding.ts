/**
 * External dependencies
 */
import e2eUtils from '@woocommerce/e2e-utils-playwright';

const { createClient, WC_ADMIN_API_PATH } = e2eUtils;

/**
 * Internal dependencies
 */
import { admin } from '../test-data/data';
import playwrightConfig from '../playwright.config';
import type { RestApiClient } from '../fixtures/fixtures';

/**
 * Onboarding profile data for updates.
 */
interface OnboardingProfileData {
	skipped?: boolean;
	completed?: boolean;
	industry?: string[];
	product_types?: string[];
	product_count?: string;
	selling_venues?: string;
	revenue?: string;
	other_platform?: string;
	other_platform_name?: string;
	business_extensions?: string[];
	theme?: string;
	wccom_connected?: boolean;
	setup_client?: boolean;
	is_agree_marketing?: boolean;
	store_email?: string;
	[ key: string ]: unknown;
}

/**
 * Onboarding profile response from API.
 */
interface OnboardingProfile {
	skipped: boolean;
	completed: boolean;
	industry: string[];
	product_types: string[];
	product_count: string;
	selling_venues: string;
	revenue: string;
	other_platform: string;
	other_platform_name: string;
	business_extensions: string[];
	theme: string;
	wccom_connected: boolean;
	setup_client: boolean;
	is_agree_marketing: boolean;
	store_email: string;
}

/**
 * Update the onboarding profile using a call to the wc-admin API.
 *
 * @param data - The data to send to the onboarding/profile endpoint
 * @return The updated onboarding profile or null if failed
 */
export async function updateOnboardingProfile(
	data: OnboardingProfileData
): Promise< OnboardingProfile | null > {
	const apiClient = createClient( playwrightConfig.use?.baseURL as string, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} ) as RestApiClient;
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
		? ( newProfileResponse.data as OnboardingProfile )
		: null;
}

/**
 * Skip the onboarding wizard using a call to the wc-admin API.
 *
 * @return The value for the skipped field in the onboarding profile
 */
export async function skipOnboardingWizard(): Promise< boolean | undefined > {
	const profile = await updateOnboardingProfile( {
		skipped: true,
	} );
	return profile?.skipped;
}
