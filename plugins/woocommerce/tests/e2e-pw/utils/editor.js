const closeWelcomeModal = async ( { page } ) => {
	// Close welcome popup if prompted
	try {
		await page
			.getByLabel( 'Close', { exact: true } )
			.click( { timeout: 5000 } );
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

const disableWelcomeModal = async ( { page } ) => {
	const isWelcomeGuideActive = await page.evaluate( () =>
		wp.data.select( 'core/edit-post' ).isFeatureActive( 'welcomeGuide' )
	);

	if ( isWelcomeGuideActive ) {
		await page.evaluate( () =>
			wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'welcomeGuide' )
		);
	}
};

module.exports = {
	closeWelcomeModal,
	goToPageEditor,
	goToPostEditor,
	disableWelcomeModal,
};
