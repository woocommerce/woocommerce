const closeWelcomeModal = async ( { page } ) => {
	if ( await page.getByLabel( 'Welcome to the block editor' ).isVisible() ) {
		await page.getByLabel( 'Close', { exact: true } ).click();
	} else {
		console.log( "Welcome modal wasn't present, skipping action." );
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
