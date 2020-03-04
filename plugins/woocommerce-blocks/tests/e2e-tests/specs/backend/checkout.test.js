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

describe( 'Checkout Block', () => {
	beforeEach( async () => {
		await switchUserToAdmin();
		await createNewPost();
	} );

	it( 'can be created', async () => {
		await insertBlock( 'Checkout' );
		expect( await getEditedPostContent() ).toMatchSnapshot();
	} );

	it( 'can only be inserted once', async () => {
		await insertBlock( 'Checkout' );
		expect( await getAllBlocks() ).toHaveLength( 1 );

		await insertBlock( 'Checkout' );
		expect( await getAllBlocks() ).toHaveLength( 1 );
	} );
} );
