/**
 * External dependencies
 */
import { request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { setOption } from '../../../utils/options';

/**
 * Set the feature flag for PayPal Standard feature.
 *
 * @param {string} baseURL    The base URL.
 * @param {string} shouldLoad The value to set ('yes' or 'no').
 * @return {Promise<void>}
 */
export const setPaypalStandardFeatureFlag = async ( baseURL, shouldLoad ) => {
	const value = {
		_should_load: shouldLoad,
	};
	await setOption(
		request,
		baseURL,
		'woocommerce_paypal_settings',
		value
	);
};
