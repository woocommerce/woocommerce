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
 * @param baseURL The base URL.
 * @param value   The value to set ('yes' or 'no').
 */
export const setEmailEditorFeatureFlag = async (
	baseURL: string,
	value: string
): Promise< void > => {
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
 * @param baseURL The base URL.
 */
export const enableEmailEditor = async ( baseURL: string ): Promise< void > =>
	setEmailEditorFeatureFlag( baseURL, 'yes' );

/**
 * Disable the email editor feature.
 *
 * @param baseURL The base URL.
 */
export const disableEmailEditor = async ( baseURL: string ): Promise< void > =>
	setEmailEditorFeatureFlag( baseURL, 'no' );

/**
 * Delete an email post.
 *
 * @param baseURL The base URL.
 * @param pageId  The page ID.
 */
export const deleteEmailPost = async (
	baseURL: string,
	pageId: string
): Promise< void > => {
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
 * @param baseURL The base URL.
 * @param emailId The transactional email ID.
 */
export const resetWCTransactionalEmail = async (
	baseURL: string,
	emailId: string
): Promise< void > => {
	await deleteOption( request, baseURL, `woocommerce_${ emailId }_settings` );
};
