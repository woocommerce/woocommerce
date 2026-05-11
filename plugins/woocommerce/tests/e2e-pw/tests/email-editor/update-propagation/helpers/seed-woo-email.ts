/**
 * External dependencies
 */
import { createClient } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { admin } from '../../../../test-data/data';
import playwrightConfig from '../../../../playwright.config';
import { META_KEYS, TEST_HELPER_API_BASE, type Status } from './classifications';

export type WooEmailSeed = {
	emailId: string;
	postContent?: string;
	storedSourceHash?: string | 'AUTO_OLD' | 'AUTO_CURRENT';
	status?: Status | null;
	version?: string | null;
	postDateGmt?: string;
	postModifiedGmt?: string;
	stripStampMeta?: boolean;
};

const baseURL = playwrightConfig.use?.baseURL ?? '';

function apiClient() {
	return createClient( baseURL, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} );
}

export async function resetWooEmailPost( emailId: string ): Promise< number > {
	const client = apiClient();
	const res = await client.post(
		`${ TEST_HELPER_API_BASE }/reset-post/${ emailId }`,
		{}
	);
	const body = res?.data;
	if ( ! body?.post_id ) {
		throw new Error(
			`resetWooEmailPost: missing post_id in response for ${ emailId }`
		);
	}
	return Number( body.post_id );
}

export async function seedWooEmailPost(
	seed: WooEmailSeed
): Promise< number > {
	const postId = await resetWooEmailPost( seed.emailId );

	const meta: Record< string, unknown > = {};

	if ( seed.stripStampMeta ) {
		meta[ META_KEYS.STATUS ] = null;
		meta[ META_KEYS.SOURCE_HASH ] = null;
		meta[ META_KEYS.SOURCE_VERSION ] = null;
		meta[ META_KEYS.LAST_SYNCED_AT ] = null;
		meta[ META_KEYS.BACKFILLED ] = null;
	} else {
		if ( seed.status !== undefined ) {
			meta[ META_KEYS.STATUS ] = seed.status;
		}
		if ( seed.storedSourceHash !== undefined ) {
			meta[ META_KEYS.SOURCE_HASH ] = await resolveHash(
				seed.emailId,
				seed.storedSourceHash
			);
		}
		if ( seed.version !== undefined ) {
			meta[ META_KEYS.SOURCE_VERSION ] = seed.version;
		}
	}

	const postUpdate: Record< string, unknown > = {};
	if ( seed.postContent !== undefined ) {
		postUpdate.post_content = seed.postContent;
	}
	if ( seed.postDateGmt !== undefined ) {
		postUpdate.post_date_gmt = seed.postDateGmt;
	}
	if ( seed.postModifiedGmt !== undefined ) {
		postUpdate.post_modified_gmt = seed.postModifiedGmt;
	}

	const client = apiClient();
	await client.post( `${ TEST_HELPER_API_BASE }/seed-meta/${ postId }`, {
		meta,
		post: postUpdate,
	} );

	return postId;
}

async function resolveHash(
	emailId: string,
	hashSpec: string | 'AUTO_OLD' | 'AUTO_CURRENT'
): Promise< string > {
	if ( hashSpec !== 'AUTO_OLD' && hashSpec !== 'AUTO_CURRENT' ) {
		return hashSpec;
	}
	const client = apiClient();
	const mode = hashSpec === 'AUTO_OLD' ? 'old' : 'current';
	const res = await client.get(
		`${ TEST_HELPER_API_BASE }/canonical-hash/${ emailId }?mode=${ mode }`
	);
	const body = res?.data;
	if ( ! body?.hash ) {
		throw new Error(
			`Failed to resolve hash for ${ emailId } mode=${ mode }`
		);
	}
	return String( body.hash );
}

export async function getWooEmailMeta(
	postId: number
): Promise< Record< string, string[] > > {
	const client = apiClient();
	const res = await client.get(
		`${ TEST_HELPER_API_BASE }/seed-meta/${ postId }`
	);
	return ( res?.data?.meta ?? {} ) as Record< string, string[] >;
}

export async function getWooEmailPostContent(
	postId: number
): Promise< string > {
	const client = apiClient();
	const res = await client.get(
		`${ TEST_HELPER_API_BASE }/post-content/${ postId }`
	);
	return String( res?.data?.post_content ?? '' );
}
