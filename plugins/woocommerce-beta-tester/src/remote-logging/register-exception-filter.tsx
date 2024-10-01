/* eslint-disable @typescript-eslint/ban-ts-comment */
/* eslint-disable @woocommerce/dependency-group */

/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';

import apiFetch from '@wordpress/api-fetch';
// @ts-ignore no types
import { dispatch } from '@wordpress/data';
/**
 * Internal dependencies
 */
import { API_NAMESPACE } from './';
// @ts-ignore no types
import { STORE_KEY as OPTIONS_STORE_NAME } from '../options/data/constants';

/**
 * Retrieves the options for simulating a WooCommerce JavaScript error.
 *
 * @return {Promise<Array|null>} The options if available, null otherwise.
 */
const getSimulateErrorOptions = async () => {
	try {
		const path = `${ API_NAMESPACE }/options?search=wc_beta_tester_simulate_woocommerce_js_error`;

		const options = await apiFetch<
			[
				{
					option_value: string;
					option_name: string;
					option_id: number;
				}
			]
		>( {
			path,
		} );
		return options && options.length > 0 ? options : null;
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Error retrieving simulate error options:', error );
		return null;
	}
};

/**
 * Deletes the option used for simulating WooCommerce JavaScript errors.
 */
const deleteSimulateErrorOption = async () => {
	await dispatch( OPTIONS_STORE_NAME ).deleteOption(
		'wc_beta_tester_simulate_woocommerce_js_error'
	);
};

/**
 * Adds a filter to throw an exception in the WooCommerce core context.
 */
const addCoreExceptionFilter = () => {
	addFilter( 'woocommerce_admin_pages_list', 'wc-beta-tester', () => {
		deleteSimulateErrorOption();

		throw new Error(
			'Test JS exception in WC Core context via WC Beta Tester'
		);
	} );
};

/**
 * Throws an exception specific to the WooCommerce Beta Tester context.
 */
const throwBetaTesterException = () => {
	throw new Error( 'Test JS exception from WooCommerce Beta Tester' );
};

/**
 * Registers an exception filter for simulating JavaScript errors in WooCommerce.
 * This function is used for testing purposes in the WooCommerce Beta Tester plugin.
 */
export const registerExceptionFilter = async () => {
	const options = await getSimulateErrorOptions();
	if ( ! options ) {
		return;
	}

	const context = options[ 0 ].option_value;
	if ( context === 'core' ) {
		addCoreExceptionFilter();
	} else {
		deleteSimulateErrorOption();
		throwBetaTesterException();
	}
};
