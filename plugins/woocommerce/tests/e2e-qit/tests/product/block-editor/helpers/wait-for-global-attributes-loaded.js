/**
 * Waits for the global attributes to be loaded on the page.
 *
 * This function waits until the `hasFinishedResolution` selector
 * from the `wc/admin/products/attributes` store indicates that
 * the product attributes are no longer loading.
 *
 * @param {Object} page - The Playwright Page object.
 * @return {Promise<void>} A promise that resolves when the global attributes are loaded.
 */
export async function waitForGlobalAttributesLoaded( page ) {
	await page.waitForFunction( () => {
		const storeId = 'wc/admin/products/attributes';
		const attributeSortCriteria = { order_by: 'name' };

		const isLoadingAttributes = ! wp.data
			.select( storeId )
			.hasFinishedResolution( 'getProductAttributes', [
				attributeSortCriteria,
			] );

		return ! isLoadingAttributes;
	} );
}
