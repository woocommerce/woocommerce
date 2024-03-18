const updateProduct = async ( { page, expect } ) => {
	await page.getByRole( 'button', { name: 'Update' } ).click();
	// Verify product was updated
	await expect( page.getByLabel( 'Dismiss this notice' ) ).toContainText(
		'Product updated'
	);
};

const disableVariableProductBlockTour = async ( { page } ) => {
	await page.waitForLoadState();
	await page.waitForFunction( () => window?.wp?.data );

	// Get the current user data
	const { id: userId, woocommerce_meta } = await page.evaluate( () => {
		return window.wp.data.select( 'core' ).getCurrentUser();
	} );

	// Disable the variable product block tour
	const updatedWooCommerceMeta = {
		...woocommerce_meta,
		variable_product_block_tour_shown: '"yes"',
	};

	// Push the updated user data
	await page.evaluate(
		// eslint-disable-next-line @typescript-eslint/no-shadow
		async ( { userId, updatedWooCommerceMeta } ) => {
			await window.wp.data.dispatch( 'core' ).saveUser( {
				id: userId,
				woocommerce_meta: updatedWooCommerceMeta,
			} );
		},
		{ userId, updatedWooCommerceMeta }
	);

	await page.reload();
};

module.exports = {
	updateProduct,
	disableVariableProductBlockTour,
};
