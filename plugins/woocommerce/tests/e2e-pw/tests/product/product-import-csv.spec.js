/**
 * External dependencies
 */
import path from 'path';
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test, expect, tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

const filePath = path.resolve( 'tests/e2e-pw/test-data/sample_products.csv' );
const filePathOverride = path.resolve(
	'tests/e2e-pw/test-data/sample_products_override.csv'
);

const productIds = [];
const categoryIds = [];
const attributeIds = [];

const productNames = [
	'Imported V-Neck T-Shirt',
	'Imported Hoodie',
	'Imported Hoodie with Logo',
	'Imported T-Shirt',
	'Imported Beanie',
	'Imported Belt',
	'Imported Cap',
	'Imported Sunglasses',
	'Imported Hoodie with Pocket',
	'Imported Hoodie with Zipper',
	'Imported Long Sleeve Tee',
	'Imported Polo',
	'Imported Album',
	'Imported Single',
	'Imported T-Shirt with Logo',
	'Imported Beanie with Logo',
	'Imported Logo Collection',
	'Imported WordPress Pennant',
];
const productNamesOverride = [
	'Imported V-Neck T-Shirt Override',
	'Imported Hoodie Override',
	'Imported Hoodie with Logo Override',
	'Imported T-Shirt Override',
	'Imported Beanie Override',
	'Imported Belt Override',
	'Imported Cap Override',
	'Imported Sunglasses Override',
	'Imported Hoodie with Pocket Override',
	'Imported Hoodie with Zipper Override',
	'Imported Long Sleeve Tee Override',
	'Imported Polo Override',
	'Imported Album Override',
	'Imported Single Override',
	'Imported T-Shirt with Logo Override',
	'Imported Beanie with Logo Override',
	'Imported Logo Collection Override',
	'Imported WordPress Pennant Override',
];
const productPricesOverride = [
	'$111.05',
	'$118.00',
	'$145.00',
	'$120.00',
	'$118.00',
	'$118.00',
	'$13.00',
	'$12.00',
	'$115.00',
	'$120.00',
	'$125.00',
	'$145.00',
	'$145.00',
	'$135.00',
	'$190.00',
	'$118.00',
	'$116.00',
	'$165.00',
	'$155.00',
	'$120.00',
	'$118.00',
	'$118.00',
	'$145.00',
	'$142.00',
	'$145.00',
	'$115.00',
	'$120.00',
];
const productCategories = [
	'Clothing',
	'Hoodies',
	'Tshirts',
	'Accessories',
	'Music',
	'Decor',
];
const productAttributes = [ 'Color', 'Size' ];

const errorMessage = 'File is empty. Please upload something more substantial.';

test.describe( 'Import Products from a CSV file', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.afterAll( async ( { restApi } ) => {
		// get a list of all products
		await restApi
			.get( `${ WC_API_PATH }/products?per_page=50` )
			.then( ( response ) => {
				for ( let i = 0; i < response.data.length; i++ ) {
					// if the product is one we imported, add it to the array
					for ( let j = 0; j < productNamesOverride.length; j++ ) {
						if (
							response.data[ i ].name ===
							productNamesOverride[ j ]
						) {
							productIds.push( response.data[ i ].id );
						}
					}
				}
			} );
		// batch delete all products in the array
		await restApi.post( `${ WC_API_PATH }/products/batch`, {
			delete: [ ...productIds ],
		} );
		// get a list of all product categories
		await restApi
			.get( `${ WC_API_PATH }/products/categories` )
			.then( ( response ) => {
				for ( let i = 0; i < response.data.length; i++ ) {
					// if the product category is one that was created, add it to the array
					for ( let j = 0; j < productCategories.length; j++ ) {
						if (
							response.data[ i ].name === productCategories[ j ]
						) {
							categoryIds.push( response.data[ i ].id );
						}
					}
				}
			} );
		// batch delete all categories in the array
		await restApi.post( `${ WC_API_PATH }/products/categories/batch`, {
			delete: [ ...categoryIds ],
		} );
		// get a list of all product attributes
		await restApi
			.get( `${ WC_API_PATH }/products/attributes` )
			.then( ( response ) => {
				for ( let i = 0; i < response.data.length; i++ ) {
					// if the product attribute is one that was created, add it to the array
					for ( let j = 0; j < productAttributes.length; j++ ) {
						if (
							response.data[ i ].name === productAttributes[ j ]
						) {
							attributeIds.push( response.data[ i ].id );
						}
					}
				}
			} );
		// batch delete attributes in the array
		await restApi.post( `${ WC_API_PATH }/products/attributes/batch`, {
			delete: [ ...attributeIds ],
		} );
	} );

	test(
		'should show error message if you go without providing CSV file',
		{ tag: [ tags.NOT_E2E, tags.NON_CRITICAL ] },
		async ( { page } ) => {
			await page.goto(
				'wp-admin/edit.php?post_type=product&page=product_importer'
			);

			// verify the error message if you go without providing CSV file
			await page.locator( 'button[value="Continue"]' ).click();
			await expect( page.locator( 'div.error.inline' ) ).toContainText(
				errorMessage
			);
		}
	);

	test(
		'can upload the CSV file and import products',
		{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
		async ( { page } ) => {
			await page.goto(
				'wp-admin/edit.php?post_type=product&page=product_importer'
			);

			// Select the CSV file and upload it
			const [ fileChooser ] = await Promise.all( [
				page.waitForEvent( 'filechooser' ),
				page.locator( '#upload' ).click(),
			] );
			await fileChooser.setFiles( filePath );
			await page.locator( 'button[value="Continue"]' ).click();

			// Click on run the importer
			await page.locator( 'button[value="Run the importer"]' ).click();

			// Confirm that the import is done
			await expect(
				page.locator( '.woocommerce-importer-done' )
			).toContainText( 'Import complete!', { timeout: 120000 } );

			// View the products
			await page.locator( 'text=View products' ).click();

			// Search for "import" to narrow the results to just the products we imported
			await page.locator( '#post-search-input' ).fill( 'Imported' );
			await page.locator( '#search-submit' ).click();

			// Wait for search results to load completely
			await page.waitForSelector( 'a.row-title', { timeout: 30000 } );

			// Compare imported products to what's expected
			await expect( page.locator( 'a.row-title' ) ).toHaveCount(
				productNames.length,
				{ timeout: 30000 }
			);

			const productTitles = await page
				.locator( 'a.row-title' )
				.allTextContents();

			expect( productTitles.sort() ).toEqual( productNames.sort() );
		}
	);

	test(
		'can override the existing products via CSV import',
		{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
		async ( { page } ) => {
			await page.goto(
				'wp-admin/edit.php?post_type=product&page=product_importer'
			);

			// Put the CSV Override products file, set checkbox and proceed further
			const [ fileChooser ] = await Promise.all( [
				page.waitForEvent( 'filechooser' ),
				page.locator( '#upload' ).click(),
			] );
			await fileChooser.setFiles( filePathOverride );
			await page
				.locator( '#woocommerce-importer-update-existing' )
				.click();
			await page.locator( 'button[value="Continue"]' ).click();

			// Click on run the importer
			await page.locator( 'button[value="Run the importer"]' ).click();

			// Confirm that the import is done
			await expect(
				page.locator( '.woocommerce-importer-done' )
			).toContainText( 'Import complete!', { timeout: 120000 } ); // import can take a while

			// View the products
			await page.locator( 'text=View products' ).click();

			// Search for "import" to narrow the results to just the products we imported
			await page.locator( '#post-search-input' ).fill( 'Imported' );
			await page.locator( '#search-submit' ).click();

			await expect( page.locator( 'a.row-title' ) ).toHaveCount(
				productNamesOverride.length
			);
			const productTitles = await page
				.locator( 'a.row-title' )
				.allInnerTexts();

			expect( productTitles.sort() ).toEqual(
				productNamesOverride.sort()
			);

			// Compare product prices to what's expected
			await expect( page.locator( '.amount' ) ).toHaveCount(
				productPricesOverride.length
			);
			const productPrices = await page
				.locator( '.amount' )
				.allInnerTexts();

			expect( productPrices.sort() ).toStrictEqual(
				productPricesOverride.sort()
			);
		}
	);
} );
