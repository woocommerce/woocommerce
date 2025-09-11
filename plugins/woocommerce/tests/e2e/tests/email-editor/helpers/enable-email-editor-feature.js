/**
 * External dependencies
 */
import { request } from '@playwright/test';
import { createClient, WP_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { setOption, deleteOption } from '../../../utils/options';
import { admin } from '../../../test-data/data';

/**
 * Set the feature flag for email improvements feature.
 *
 * @param {string} baseURL The base URL.
 * @param {string} value   The value to set ('yes' or 'no').
 * @return {Promise<void>}
 */
export const setEmailEditorFeatureFlag = async ( baseURL, value ) => {
	await setOption(
		request,
		baseURL,
		'woocommerce_feature_block_email_editor_enabled',
		value
	);
};

/**
 * Enable the email editor feature.
 *
 * @param {string} baseURL The base URL.
 * @return {Promise<void>}
 */
export const enableEmailEditor = async ( baseURL ) =>
	setEmailEditorFeatureFlag( baseURL, 'yes' );

/**
 * Disable the email editor feature.
 *
 * @param {string} baseURL The base URL.
 * @return {Promise<void>}
 */
export const disableEmailEditor = async ( baseURL ) =>
	setEmailEditorFeatureFlag( baseURL, 'no' );

/**
 * Delete an email post.
 *
 * @param {string} baseURL The base URL.
 * @param {string} pageId  The page ID.
 * @return {Promise<void>}
 */
export const deleteEmailPost = async ( baseURL, pageId ) => {
	console.log( 'Deleting email post', { pageId } );

	const apiClient = createClient( baseURL, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} );
	await apiClient.delete(
		`${ WP_API_PATH }/woo_email/${ pageId }?force=true`
	);

	// clear the transient. It will force post regeneration.
	await deleteOption(
		request,
		baseURL,
		'_transient_wc_email_editor_initial_templates_generated'
	);
};

/**
 * Reset the WC_Email email settings.
 *
 * This will reset the email by deleting the option saved in the DB retuning it back to the default state.
 *
 * @param {string} baseURL The base URL.
 * @param {string} emailId The transactional email ID.
 * @return {Promise<void>}
 */
export const resetWCTransactionalEmail = async ( baseURL, emailId ) =>
	deleteOption( request, baseURL, `woocommerce_${ emailId }_settings` );
