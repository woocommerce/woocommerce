/**
 * External dependencies
 */
import { createClient } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { admin } from '../../../../test-data/data';
import playwrightConfig from '../../../../playwright.config';
import { TEST_HELPER_API_BASE } from './classifications';

const baseURL = playwrightConfig.use?.baseURL ?? '';

function apiClient() {
	return createClient( baseURL, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} );
}

export async function listSyncEnabledEmails(): Promise< string[] > {
	const client = apiClient();
	const res = await client.get(
		`${ TEST_HELPER_API_BASE }/sync-enabled-emails`
	);
	return ( res?.data?.email_ids ?? [] ) as string[];
}

export type MerchantEditedBlocks = {
	post_id: number;
	base_blocks: number;
	post_blocks: number;
	edited: Array< {
		index: number;
		block: string;
		text: string;
		attrs_changed: boolean;
		markup_changed: boolean;
	} >;
};

/**
 * Run the change summary's own "did the merchant edit this block?" rule on a
 * post, against the base render stored on it.
 */
export async function getMerchantEditedBlocks(
	postId: number
): Promise< MerchantEditedBlocks > {
	const client = apiClient();
	const res = await client.get(
		`${ TEST_HELPER_API_BASE }/merchant-edited-blocks/${ postId }`
	);
	return res?.data as MerchantEditedBlocks;
}
