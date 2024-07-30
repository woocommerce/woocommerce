const { expect } = require( '@playwright/test' );

const closeChoosePatternModal = async ( { page } ) => {
	const closeModal = page.getByRole( 'button', {
		name: 'Close',
		exact: true,
	} );
	await page.addLocatorHandler( closeModal, async () => {
		await closeModal.click();
	} );
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

const openEditorSettings = async ( { page } ) => {
	// Open Settings sidebar if closed
	if ( await page.getByLabel( 'Editor Settings' ).isVisible() ) {
		console.log( 'Editor Settings is open, skipping action.' );
	} else {
		await page.getByLabel( 'Settings', { exact: true } ).click();
	}
};

const getCanvas = async ( page ) => {
	return page.frame( 'editor-canvas' ) || page;
};

const goToPageEditor = async ( { page } ) => {
	await page.goto( 'wp-admin/post-new.php?post_type=page' );
	await disableWelcomeModal( { page } );
	await page.waitForResponse(
		( response ) =>
			response.url().includes( '//page' ) && response.status() === 200
	);
};

const goToPostEditor = async ( { page } ) => {
	await page.goto( 'wp-admin/post-new.php' );
	await disableWelcomeModal( { page } );
	await page.waitForResponse(
		( response ) =>
			response.url().includes( '//single' ) && response.status() === 200
	);
};

const fillPageTitle = async ( page, title ) => {
	await ( await getCanvas( page ) ).getByLabel( 'Add title' ).click();
	await ( await getCanvas( page ) ).getByLabel( 'Add title' ).fill( title );
};

const insertBlock = async ( page, blockName, wpVersion = null ) => {
	await page
		.getByRole( 'button', {
			name: 'Toggle block inserter',
			expanded: false,
		} )
		.click();
	await page.getByPlaceholder( 'Search', { exact: true } ).fill( blockName );
	await page.getByRole( 'option', { name: blockName, exact: true } ).click();

	// In WP 6.6 'Toggle block inserter' button closes the inserter as expected,
	// but trying to immediately open it again will fail in Playwright, while manually it works.
	// We have tests that insert multiple blocks and fail because of this.
	// Using the new 'Close block inserter' button added in WP 6.6 works fine.
	if ( wpVersion && wpVersion <= 6.5 ) {
		await page
			.getByRole( 'button', {
				name: 'Toggle block inserter',
				expanded: true,
			} )
			.click();
	} else {
		await page
			.getByRole( 'button', {
				name: 'Close block inserter',
			} )
			.click();
	}
};

const insertBlockByShortcut = async ( page, blockName ) => {
	const canvas = await getCanvas( page );
	await canvas.getByRole( 'button', { name: 'Add default block' } ).click();
	await canvas
		.getByRole( 'document', {
			name: 'Empty block; start writing or type forward slash to choose a block',
		} )
		.pressSequentially( `/${ blockName }` );
	await expect(
		page.getByRole( 'option', { name: blockName, exact: true } )
	).toBeVisible();
	await page.getByRole( 'option', { name: blockName, exact: true } ).click();
	await expect(
		page.getByLabel( `Block: ${ blockName }` ).first()
	).toBeVisible();
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

const publishPage = async ( page, pageTitle ) => {
	await page
		.getByRole( 'button', { name: 'Publish', exact: true } )
		.dispatchEvent( 'click' );
	await page
		.getByRole( 'region', { name: 'Editor publish' } )
		.getByRole( 'button', { name: 'Publish', exact: true } )
		.click();
	await expect(
		page.getByText( `${ pageTitle } is now live.` )
	).toBeVisible();
};

module.exports = {
	closeChoosePatternModal,
	goToPageEditor,
	goToPostEditor,
	disableWelcomeModal,
	openEditorSettings,
	getCanvas,
	fillPageTitle,
	insertBlock,
	insertBlockByShortcut,
	transformIntoBlocks,
	publishPage,
};
