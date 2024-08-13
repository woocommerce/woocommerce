/**
 * Internal dependencies
 */
const { merchant } = require( '@woocommerce/e2e-utils' );

const runAddProductBrandsTest = () => {
	describe( 'Merchant can add product brands', () => {
		beforeAll( async () => {
			await merchant.login();
		} );

		it( 'can add sproduct brands', async () => {
			await page
				.locator("#menu-posts-product")
				.getByRole("link", { name: "Products", exact: true })
				.click();
			await page.getByRole("link", { name: "Brands", exact: true }).click();

			// Wait for the Brands page to load.
			// This is needed so that checking for existing brands would work.
			await page.waitForLoadState("networkidle");
			
		} );
	} );
};

// eslint-disable-next-line jest/no-export
module.exports = runAddProductBrandsTest;
