const closeWelcomeModal = async ( { page, timeout = 5000 } ) => {
	// Close welcome popup if prompted
	try {
		await page.getByLabel( 'Close', { exact: true } ).click( { timeout } );
	} catch ( error ) {
		// Welcome modal wasn't present, skipping action.
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
