/**
 * External dependencies
 */
import { request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { setOption, deleteOption } from '../../../utils/options';

/**
 * Set the feature flag for email improvements feature.
 *
 * @param {string} baseURL The base URL.
 * @param {string} value   The value to set ('yes' or 'no').
 * @return {Promise<void>}
 */
export const setFeatureEmailImprovementsFlag = async ( baseURL, value ) => {
	await setOption(
		request,
		baseURL,
		'woocommerce_feature_email_improvements_enabled',
		value
	);
	// We need to delete the transient to prevent unwanted popups.
	await deleteOption(
		request,
		baseURL,
		'_transient_wc_settings_email_improvements_reverted'
	);
};
