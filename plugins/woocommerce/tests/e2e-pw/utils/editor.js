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

const disableWelcomeModal = async ( { page } ) => {
	// Further info: https://github.com/woocommerce/woocommerce/pull/45856/
	await page.waitForLoadState( 'domcontentloaded' );

	const isWelcomeGuideActive = await page.evaluate( () =>
		wp.data.select( 'core/edit-post' ).isFeatureActive( 'welcomeGuide' )
	);

	if ( isWelcomeGuideActive ) {
		await page.evaluate( () =>
			wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'welcomeGuide' )
		);
	}
};

const goToPageEditor = async ( { page } ) => {
	await page.goto( 'wp-admin/post-new.php?post_type=page' );

	await disableWelcomeModal( { page } );
};

const goToPostEditor = async ( { page } ) => {
	await page.goto( 'wp-admin/post-new.php' );

	await disableWelcomeModal( { page } );
};

module.exports = {
	closeWelcomeModal,
	goToPageEditor,
	goToPostEditor,
	disableWelcomeModal,
};
