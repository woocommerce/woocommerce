const { expect } = require( '@playwright/test' );

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

const getCanvas = async ( page ) => {
	return page.frame( 'editor-canvas' ) || page;
};

const goToPageEditor = async ( { page } ) => {
	await page.goto( 'wp-admin/post-new.php?post_type=page' );

	await disableWelcomeModal( { page } );
};

const goToPostEditor = async ( { page } ) => {
	await page.goto( 'wp-admin/post-new.php' );

	await disableWelcomeModal( { page } );
};

const fillPageTitle = async ( page, title ) => {
	await ( await getCanvas( page ) )
		.getByRole( 'textbox', { name: 'Add title' } )
		.fill( title );
};

const insertBlock = async ( page, blockName ) => {
	const canvas = await getCanvas( page );
	// Click the title to activate the block inserter.
	await canvas.getByRole( 'textbox', { name: 'Add title' } ).click();
	await canvas.getByLabel( 'Add block' ).click();
	await page.getByPlaceholder( 'Search', { exact: true } ).fill( blockName );
	await page.getByRole( 'option', { name: blockName, exact: true } ).click();
};

const transformIntoBlocks = async ( page ) => {
	const canvas = await getCanvas( page );

	await expect(
		canvas.locator(
			'.wp-block-woocommerce-classic-shortcode__placeholder-copy'
		)
	).toBeVisible();
	await canvas
		.getByRole( 'button' )
		.filter( { hasText: 'Transform into blocks' } )
		.click();

	await expect( page.getByLabel( 'Dismiss this notice' ) ).toContainText(
		'Classic shortcode transformed to blocks.'
	);
};

module.exports = {
	closeWelcomeModal,
	goToPageEditor,
	goToPostEditor,
	disableWelcomeModal,
	getCanvas,
	fillPageTitle,
	insertBlock,
	transformIntoBlocks,
};
