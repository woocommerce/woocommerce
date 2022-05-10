const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const path = require( 'path' );
const filePath = path.resolve( 'e2e/test-data/sample_products.csv' );
const filePathOverride = path.resolve(
	'e2e/test-data/sample_products_override.csv'
);

const productIds = [];

const productNames = [
	'V-Neck T-Shirt',
	'Hoodie',
	'Hoodie with Logo',
	'T-Shirt',
	'Beanie',
	'Belt',
	'Cap',
	'Sunglasses',
	'Hoodie with Pocket',
	'Hoodie with Zipper',
	'Long Sleeve Tee',
	'Polo',
	'Album',
	'Single',
	'T-Shirt with Logo',
	'Beanie with Logo',
	'Logo Collection',
	'WordPress Pennant',
];
const productNamesOverride = [
	'V-Neck T-Shirt Override',
	'Hoodie Override',
	'Hoodie with Logo Override',
	'T-Shirt Override',
	'Beanie Override',
	'Belt Override',
	'Cap Override',
	'Sunglasses Override',
	'Hoodie with Pocket Override',
	'Hoodie with Zipper Override',
	'Long Sleeve Tee Override',
	'Polo Override',
	'Album Override',
	'Single Override',
	'T-Shirt with Logo Override',
	'Beanie with Logo Override',
	'Logo Collection Override',
	'WordPress Pennant Override',
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
const errorMessage =
	'Invalid file type. The importer supports CSV and TXT file formats.';

test.describe( 'Import Products from a CSV file', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// get a list of all products
		await api.get( 'products?per_page=20' ).then( ( response ) => {
			for ( let i = 0; i < response.data.length; i++ ) {
				// if the product is one we imported, add it to the array
				for ( let j = 0; j < productNamesOverride.length; j++ ) {
					if (
						response.data[ i ].name === productNamesOverride[ j ]
					) {
						productIds.push( response.data[ i ].id );
					}
				}
			}
		} );
		// batch delete all products in the array
		await api.post( 'products/batch', { delete: [ ...productIds ] } );
	} );

	test( 'should show error message if you go without providing CSV file', async ( {
		page,
	} ) => {
		await page.goto(
			'wp-admin/edit.php?post_type=product&page=product_importer'
		);

		// verify the error message if you go without providing CSV file
		await page.click( 'button[value="Continue"]' );
		await expect( page.locator( 'div.error' ) ).toContainText(
			errorMessage
		);
	} );

	test( 'can upload the CSV file and import products', async ( { page } ) => {
		await page.goto(
			'wp-admin/edit.php?post_type=product&page=product_importer'
		);

		// Select the CSV file and upload it
		const [ fileChooser ] = await Promise.all( [
			page.waitForEvent( 'filechooser' ),
			page.click( '#upload' ),
		] );
		await fileChooser.setFiles( filePath );
		await page.click( 'button[value="Continue"]' );

		// Click on run the importer
		await page.click( 'button[value="Run the importer"]' );

		// Confirm that the import is done
		await expect(
			page.locator( '.woocommerce-importer-done' )
		).toContainText( 'Import complete!', { timeout: 120000 } );

		// View the products
		await page.click( 'text=View products' );

		// Compare imported products to what's expected
		await page.waitForSelector( 'a.row-title' );
		const productTitles = await page.$$eval( 'a.row-title', ( elements ) =>
			elements.map( ( item ) => item.innerHTML )
		);

		await expect( productTitles.sort() ).toEqual( productNames.sort() );
	} );

	test( 'can override the existing products via CSV import', async ( {
		page,
	} ) => {
		await page.goto(
			'wp-admin/edit.php?post_type=product&page=product_importer'
		);

		// Put the CSV Override products file, set checkbox and proceed further
		const [ fileChooser ] = await Promise.all( [
			page.waitForEvent( 'filechooser' ),
			page.click( '#upload' ),
		] );
		await fileChooser.setFiles( filePathOverride );
		await page.click( '#woocommerce-importer-update-existing' );
		await page.click( 'button[value="Continue"]' );

		// Click on run the importer
		await page.click( 'button[value="Run the importer"]' );

		// Confirm that the import is done
		await expect(
			page.locator( '.woocommerce-importer-done' )
		).toContainText( 'Import complete!', { timeout: 120000 } );

		// View the products
		await page.click( 'text=View products' );

		// Compare imported products to what's expected
		await page.waitForSelector( 'a.row-title' );
		const productTitles = await page.$$eval( 'a.row-title', ( elements ) =>
			elements.map( ( item ) => item.innerHTML )
		);

		await expect( productTitles.sort() ).toEqual(
			productNamesOverride.sort()
		);

		// Compare product prices to what's expected
		await page.waitForSelector( 'td.price.column-price' );
		const productPrices = await page.$$eval( '.amount', ( elements ) =>
			elements.map( ( item ) => item.innerText )
		);

		await expect( productPrices.sort() ).toEqual(
			productPricesOverride.sort()
		);
	} );
} );
