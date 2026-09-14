/**
 * External dependencies
 */
import { createClient } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { admin } from '../../../../test-data/data';
import playwrightConfig from '../../../../playwright.config';
import {
	META_KEYS,
	TEST_HELPER_API_BASE,
	type Status,
} from './classifications';

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
		`${ TEST_HELPER_API_BASE }/reset-post/${ encodeURIComponent(
			emailId
		) }`,
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
		`${ TEST_HELPER_API_BASE }/canonical-hash/${ encodeURIComponent(
			emailId
		) }?mode=${ mode }`
	);
	const body = res?.data;
	if ( ! body?.hash ) {
		throw new Error(
			`Failed to resolve hash for ${ emailId } mode=${ mode }`
		);
	}
	return String( body.hash );
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

/**
 * Create a woo_email post directly via the seed-bulk endpoint, bypassing the
 * WCTransactionalEmailPostsGenerator. The created post has no entry in the
 * options-table mapping used by WCTransactionalEmailPostsManager, so the
 * backfill and divergence-sweep pipelines cannot resolve its email_id and
 * will skip it entirely.
 *
 * Use this for scenarios that need a woo_email post for an email type that is
 * NOT in the sync registry (e.g. a third-party email that is registered as a
 * WC_Email subclass but is not enrolled in the block-editor transactional
 * emails list).
 */
export async function seedWooEmailPostDirect(
	seed: Pick< WooEmailSeed, 'postContent' | 'stripStampMeta' >
): Promise< number > {
	const meta: Record< string, unknown > = {};

	if ( seed.stripStampMeta ) {
		meta[ META_KEYS.STATUS ] = null;
		meta[ META_KEYS.SOURCE_HASH ] = null;
		meta[ META_KEYS.SOURCE_VERSION ] = null;
		meta[ META_KEYS.LAST_SYNCED_AT ] = null;
		meta[ META_KEYS.BACKFILLED ] = null;
	}

	const postData: Record< string, unknown > = {
		post_type: 'woo_email',
		post_status: 'publish',
	};
	if ( seed.postContent !== undefined ) {
		postData.post_content = seed.postContent;
	}

	const client = apiClient();
	const res = await client.post( `${ TEST_HELPER_API_BASE }/seed-bulk`, {
		seeds: [
			{
				post: postData,
				meta,
			},
		],
	} );

	const results: Array< { post_id?: number; error?: string } > =
		res?.data?.results ?? [];
	const first = results[ 0 ];
	if ( ! first?.post_id ) {
		throw new Error(
			`seedWooEmailPostDirect: failed to create post — ${
				first?.error ?? 'no post_id returned'
			}`
		);
	}
	return Number( first.post_id );
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

export type ApplyChoice = {
	path: ( number | string )[];
	decision: 'keep_yours' | 'use_core';
};

export type ApplyResult = {
	merged_content: string;
	revision_id: string;
	version_to: string;
	status: string;
	structural_skipped: boolean;
	aliases_migrated: string[];
};

/**
 * Call the /apply endpoint for a woo_email post using basic-auth credentials,
 * bypassing the cookie+nonce requirement of the WP REST API for authenticated
 * cookie sessions. `choices` defaults to [] (keep all merchant edits, apply
 * only core additions).
 */
export async function applyWooEmailTemplate(
	postId: number,
	choices: ApplyChoice[] = []
): Promise< ApplyResult > {
	const client = apiClient();
	const res = await client.post(
		`woocommerce-email-editor/v1/emails/${ postId }/apply`,
		{ choices } as Record< string, unknown >
	);
	if ( ! res?.data?.status ) {
		throw new Error(
			`applyWooEmailTemplate: unexpected response for post ${ postId }: ${ JSON.stringify(
				res?.data
			) }`
		);
	}
	return res.data as ApplyResult;
}

export type ResetResult = {
	content: string;
	version: string | null;
	source_hash: string | null;
	synced_at: string | null;
	/** The post-reset sync status (e.g. "in_sync") for sync-enabled emails, or null otherwise. */
	status: string | null;
};

/**
 * Call the /reset endpoint for a woo_email post using basic-auth credentials,
 * bypassing the cookie+nonce requirement of the WP REST API for authenticated
 * cookie sessions. Resets the post content to the canonical WooCommerce template.
 *
 * Note: unlike applyWooEmailTemplate whose `status` field is "applied", the
 * reset endpoint returns the post-reset sync status (e.g. "in_sync") in the
 * `status` field.
 */
export async function resetWooEmailTemplate(
	postId: number
): Promise< ResetResult > {
	const client = apiClient();
	const res = await client.post(
		`woocommerce-email-editor/v1/emails/${ postId }/reset`,
		{}
	);
	if ( res?.data?.content === undefined ) {
		throw new Error(
			`resetWooEmailTemplate: unexpected response for post ${ postId }: ${ JSON.stringify(
				res?.data
			) }`
		);
	}
	return res.data as ResetResult;
}
