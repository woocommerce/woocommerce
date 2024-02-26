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
		const baseRequestUtils = await BaseRequestUtils.setup( ...args );
		baseRequestUtils.constructor = this;

		return baseRequestUtils;
	}

	/** @borrows createPostFromTemplate as this.createPostFromTemplate */
	createPostFromTemplate: typeof createPostFromTemplate =
		createPostFromTemplate.bind( this );
	/** @borrows deletePost as this.deletePost */
	deletePost: typeof deletePost = deletePost.bind( this );
}
