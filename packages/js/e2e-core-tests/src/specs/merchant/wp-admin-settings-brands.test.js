/**
 * Internal dependencies
 */
const { merchant } = require( '@woocommerce/e2e-utils' );

/**
 * Go to the Brands page.
 *
 * This will visit the Products page first, and then click on the Brands link.
 * This is to workaround the hover menu for now.
 */
const goToBrandsPage = async () => {
	await page
		.locator( '#menu-posts-product' )
		.getByRole( 'link', { name: 'Products', exact: true } )
		.click();
	await page.getByRole( 'link', { name: 'Brands', exact: true } ).click();

	// Wait for the Brands page to load.
	// This is needed so that checking for existing brands would work.
	await page.waitForLoadState( 'networkidle' );
};

const createBrandIfNotExist = async (
	name,
	slug,
	parentBrand,
	description,
	thumbnailFileName
) => {
	// Create "WooCommerce" brand if it does not exist.
	const cellVisible = await page
		.locator( '#posts-filter' )
		.getByRole( 'cell', { name: slug, exact: true } )
		.isVisible();

	if ( cellVisible ) {
		return;
	}

	await page.getByRole( 'textbox', { name: 'Name' } ).click();
	await page.getByRole( 'textbox', { name: 'Name' } ).fill( name );
	await page.getByRole( 'textbox', { name: 'Slug' } ).click();
	await page.getByRole( 'textbox', { name: 'Slug' } ).fill( slug );

	await page
		.getByRole( 'combobox', { name: 'Parent Brand' } )
		.selectOption( { label: parentBrand } );

	await page.getByRole( 'textbox', { name: 'Description' } ).click();
	await page
		.getByRole( 'textbox', { name: 'Description' } )
		.fill( description );
	await page.getByRole( 'button', { name: 'Upload/Add image' } ).click();
	await page.getByRole( 'tab', { name: 'Media Library' } ).click();
	await page.getByRole( 'checkbox', { name: thumbnailFileName } ).click();
	await page.getByRole( 'button', { name: 'Use image' } ).click();
	await page.getByRole( 'button', { name: 'Add New Brand' } ).click();

	// We should see an "Item added." notice message at the top of the page.
	await expect(
		page.locator( '#ajax-response' ).getByText( 'Item added.' )
	).toBeVisible();

	// We should see the newly created brand in the Brands table.
	await expect(
		page
			.locator( '#posts-filter' )
			.getByRole( 'cell', { name: slug, exact: true } )
	).toHaveCount( 1 );
};

const runAddProductBrandsTest = () => {
	describe( 'Merchant can add product brands', () => {
		beforeAll( async () => {
			await merchant.login();
		} );

		it( 'can add product brands', async () => {
			await goToBrandsPage();

			await createBrandIfNotExist(
				'WooCommerce',
				'woocommerce',
				'None',
				'All things WooCommerce!',
				'album-1.jpg'
			);

			// Create child brand under the "WooCommerce" parent brand.
			await createBrandIfNotExist(
				'WooCommerce Apparels',
				'woocommerce-apparels',
				'WooCommerce',
				'Cool WooCommerce clothings!',
				'single-1.jpg'
			);

			// Create a dummy child brand called "WooCommerce Dummy" under the "WooCommerce" parent brand.
			await createBrandIfNotExist(
				'WooCommerce Dummy',
				'woocommerce-dummy',
				'WooCommerce',
				'Dummy WooCommerce brand!',
				'single-1.jpg'
			);
		} );
	} );
};

// eslint-disable-next-line jest/no-export
module.exports = runAddProductBrandsTest;
