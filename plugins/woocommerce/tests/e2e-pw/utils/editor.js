const { test } = require( '@playwright/test' );

const closeWelcomeModal = async ( { page } ) => {
	const welcomeModalVisible =
		await test.step( 'Check if the Welcome modal appeared', async () => {
			return await page
				.getByRole( 'heading', {
					name: 'Welcome to the block editor',
				} )
				.isVisible();
		} );

	if ( welcomeModalVisible ) {
		await test.step( 'Welcome modal appeared. Close it.', async () => {
			await page
				.getByRole( 'document' )
				.getByRole( 'button', { name: 'Close' } )
				.click();
		} );
	} else {
		await test.step( 'Welcome modal did not appear.', async () => {
			// do nothing.
		} );
	}
};

const goToPageEditor = async ( { page } ) => {
	await page.goto( 'wp-admin/post-new.php?post_type=page' );

	await closeWelcomeModal( { page } );
};

const goToPostEditor = async ( { page } ) => {
	await page.goto( 'wp-admin/post-new.php' );

	await closeWelcomeModal( { page } );
};

module.exports = {
	closeWelcomeModal,
	goToPageEditor,
	goToPostEditor,
};
