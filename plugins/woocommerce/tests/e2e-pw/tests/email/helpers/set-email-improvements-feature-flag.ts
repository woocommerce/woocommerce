/**
 * External dependencies
 */
import { request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { setOption } from '../../../utils/options';

/**
 * Set the feature flag for email improvements feature.
 *
 * @param {string} baseURL The base URL.
 * @param {string} value   The value to set ('yes' or 'no').
 * @return {Promise<void>}
 */
export const setFeatureEmailImprovementsFlag = async (
	baseURL: string,
	value: string
) => {
	await setOption(
		request,
		baseURL,
		'woocommerce_feature_email_improvements_enabled',
		value
	);
};
