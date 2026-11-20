/**
 * Wait for active classic requests and pending Cart and Checkout block updates.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<void>} Resolves when the tracked updates settle.
 */
module.exports = async function waitForCheckoutUpdates( page ) {
	await page.waitForFunction( () => {
		if ( window.jQuery?.active > 0 ) {
			return false;
		}
		const select = window.wp?.data?.select;
		if ( ! select ) {
			return true;
		}
		const cart = select( 'wc/store/cart' );
		if (
			cart &&
			( cart.isCustomerDataUpdating?.() ||
				cart.isShippingRateBeingSelected?.() ||
				cart.getItemsPendingQuantityUpdate?.()?.length ||
				cart.getItemsPendingDelete?.()?.length )
		) {
			return false;
		}
		return ! select( 'wc/store/checkout' )?.isCalculating?.();
	} );
};
