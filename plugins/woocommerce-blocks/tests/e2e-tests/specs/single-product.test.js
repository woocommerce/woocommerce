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

describe( 'Single Product Block', () => {
	beforeEach( async () => {
		await switchUserToAdmin();
		await createNewPost();
	} );

	it( 'can be created', async () => {
		await insertBlock( 'Single Product' );
		expect( await getEditedPostContent() ).toMatchSnapshot();
	} );

	it( 'can be inserted more than once', async () => {
		await insertBlock( 'Single Product' );
		expect( await getAllBlocks() ).toHaveLength( 1 );

		await insertBlock( 'Single Product' );
		expect( await getAllBlocks() ).toHaveLength( 2 );
	} );
} );
