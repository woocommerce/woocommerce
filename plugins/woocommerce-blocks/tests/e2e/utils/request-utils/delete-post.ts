/**
 * Internal dependencies
 */
import type { RequestUtils } from './index';

export async function deletePost( this: RequestUtils, id: number ) {
	await this.rest( {
		method: 'DELETE',
		path: `/wp/v2/posts/${ id }`,
		params: {
			force: true,
		},
	} );
}
