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
 * @param baseURL The base URL.
 * @param value   The value to set ('yes' or 'no').
 */
export const setFeatureEmailImprovementsFlag = async (
	baseURL: string,
	value: string
): Promise< void > => {
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
