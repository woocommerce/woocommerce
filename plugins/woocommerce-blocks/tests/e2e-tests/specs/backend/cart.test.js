/**
 * External dependencies
 */
import {
	insertBlock,
	getEditedPostContent,
	getAllBlocks,
	createNewPost,
	switchUserToAdmin,
} from '@wordpress/e2e-test-utils';

describe( 'Cart Block', () => {
	beforeEach( async () => {
		await switchUserToAdmin();
		await createNewPost();
	} );

	it( 'can be created', async () => {
		await insertBlock( 'Cart' );
		expect( await getEditedPostContent() ).toMatchSnapshot();
	} );

	it( 'can only be inserted once', async () => {
		await insertBlock( 'Cart' );
		expect( await getAllBlocks() ).toHaveLength( 1 );

		await insertBlock( 'Cart' );
		expect( await getAllBlocks() ).toHaveLength( 1 );
	} );
} );
