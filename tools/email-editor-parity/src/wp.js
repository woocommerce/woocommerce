import { config } from '../config.js';

const authHeader =
	'Basic ' +
	Buffer.from( `${ config.username }:${ config.password }` ).toString(
		'base64'
	);

async function api( path, { method = 'GET', body } = {} ) {
	const res = await fetch( `${ config.baseUrl }/wp-json/${ path }`, {
		method,
		headers: {
			Authorization: authHeader,
			'Content-Type': 'application/json',
		},
		body: body ? JSON.stringify( body ) : undefined,
	} );
	if ( ! res.ok ) {
		const text = await res.text();
		throw new Error(
			`${ method } ${ path } -> ${ res.status }: ${ text.slice(
				0,
				300
			) }`
		);
	}
	return res.json();
}

export async function checkEnvironment() {
	try {
		await api( '' );
	} catch ( err ) {
		throw new Error(
			`Cannot reach ${ config.baseUrl }/wp-json/ — is the wp-env running?\n` +
				`Start it with: pnpm --filter=@woocommerce/plugin-woocommerce env:e2e:start\n(${ err.message })`
		);
	}
}

export async function enableEmailEditorFeature() {
	await api( 'e2e-options/update', {
		method: 'POST',
		body: {
			option_name: 'woocommerce_feature_block_email_editor_enabled',
			option_value: 'yes',
		},
	} );
}

export async function seedEmailPost( title, postContent ) {
	const res = await api( 'wc-email-test-helper/v1/seed-bulk', {
		method: 'POST',
		body: {
			seeds: [
				{
					post: {
						post_type: 'woo_email',
						post_status: 'publish',
						post_title: title,
						post_content: postContent,
					},
					meta: {},
				},
			],
		},
	} );
	const first = res?.results?.[ 0 ];
	if ( ! first?.post_id ) {
		throw new Error(
			`seedEmailPost failed: ${ first?.error ?? 'no post_id returned' }`
		);
	}
	return Number( first.post_id );
}

export async function getPreviewUrl( postId ) {
	const post = await api( `wp/v2/woo_email/${ postId }` );
	if ( ! post?.link ) {
		throw new Error( `No link for woo_email post ${ postId }` );
	}
	const sep = post.link.includes( '?' ) ? '&' : '?';
	return `${ post.link }${ sep }preview=true`;
}

export async function deleteEmailPost( postId ) {
	await api( `wp/v2/woo_email/${ postId }?force=true`, { method: 'DELETE' } );
}
