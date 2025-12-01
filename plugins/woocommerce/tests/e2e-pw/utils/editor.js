const { getCanvas } = require( '@woocommerce/e2e-utils-playwright/src' );

const fillPageTitle = async ( page, title ) => {
	// Close the Block Inserter if it's open.
	// Since Gutenberg 19.9 it is expanded by default.
	if (
		await page
			.getByRole( 'button', {
				name: /Toggle block inserter|Block Inserter/,
				expanded: true,
			} )
			.isVisible()
	) {
		await page.getByLabel( 'Close Block Inserter' ).click();
	}

	const canvas = await getCanvas( page );
	// Gutenberg (since 19.9) uses the "Block: Title" label.
	const block_title = canvas.getByLabel( /Add title|Block: Title/ );
	await block_title.click();
	await block_title.fill( title );
};

module.exports = {
	fillPageTitle,
};
