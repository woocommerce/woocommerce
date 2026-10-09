/**
 * External dependencies
 */
import { createClient } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { admin } from '../test-data/data';
import playwrightConfig from '../playwright.config';

let api: ReturnType< typeof createClient > | null = null;

function getApi() {
	if ( ! api ) {
		api = createClient( playwrightConfig.use.baseURL, {
			type: 'basic',
			username: admin.username,
			password: admin.password,
		} );
	}
	return api;
}

const cache = new Map< string, { id: number; source_url: string } >();

/**
 * Resolve a media library item by its slug.
 *
 * The `image-01/02/03` images are imported into the media library during site
 * setup (see `bin/test-env-setup.sh`). Returns the attachment so callers can use
 * its `id` (e.g. product images) or `source_url` (e.g. downloadable files, which
 * must live within the approved uploads directory) instead of relying on an
 * external URL.
 *
 * @param {string} slug The attachment slug, e.g. `image-01`.
 * @return The resolved attachment with `id` and `source_url`.
 */
export const getMediaBySlug = async (
	slug: string
): Promise< { id: number; source_url: string } > => {
	if ( cache.has( slug ) ) {
		return cache.get( slug )!;
	}
	const response = await getApi().get<
		Array< { id: number; source_url: string } >
	>( 'wp/v2/media', { slug, per_page: 1 } );
	const media = response.data?.[ 0 ];
	if ( ! media ) {
		throw new Error( `Media library image not found: ${ slug }` );
	}
	cache.set( slug, media );
	return media;
};
