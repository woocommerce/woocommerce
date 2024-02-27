const updateProduct = async ( { page, expect } ) => {
	await page.getByRole( 'button', { name: 'Update' } ).click();
	// Verify product was updated
	await expect( page.getByLabel( 'Dismiss this notice' ) ).toContainText(
		'Product updated'
	);
};

module.exports = {
	updateProduct,
};
