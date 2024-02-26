/**
 * External dependencies
 */
import { RequestUtils as BaseRequestUtils } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { createPostFromTemplate } from './create-post-from-template';
import { deletePost } from './delete-post';

export class RequestUtils extends BaseRequestUtils {
	// The `setup` override is necessary only until
	// https://github.com/WordPress/gutenberg/pull/59362 is merged.
	static async setup( ...args: Parameters< typeof BaseRequestUtils.setup > ) {
		const { request, user, storageState, storageStatePath, baseURL } =
			await BaseRequestUtils.setup( ...args );

		// We need those checks to satisfy TypeScript.
		if ( ! storageState ) {
			throw new Error( 'Storage state is required' );
		}

		if ( ! storageStatePath ) {
			throw new Error( 'Storage state path is required' );
		}

		if ( ! baseURL ) {
			throw new Error( 'Base URL is required' );
		}

		return new this( request, {
			user,
			storageState,
			storageStatePath,
			baseURL,
		} );
	}

	/** @borrows createPostFromTemplate as this.createPostFromTemplate */
	createPostFromTemplate: typeof createPostFromTemplate =
		createPostFromTemplate.bind( this );
	/** @borrows deletePost as this.deletePost */
	deletePost: typeof deletePost = deletePost.bind( this );
}
