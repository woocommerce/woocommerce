const { test: baseTest, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

baseTest.describe( 'Products > Related products', () => {
	baseTest.use( { storageState: process.env.ADMINSTATE } );

	const test = baseTest.extend( {
		api: async ( { baseURL }, use ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );

			await use( api );
		},
		products: async ( { page, api }, use ) => {
			const productTypes = [ 'simple', 'upsell1', 'upsell2' ];
			const products = {};

			for ( const type of Object.values( productTypes ) ) {
				await api
					.post( 'products', {
						name: `Product ${ type } ${ Date.now() }`,
						type: 'simple',
					} )
					.then( ( response ) => {
						products[ type ] = response.data;
					} );
			}

			await test.step( 'Navigate to product edit page', async () => {
				await page.goto(
					`wp-admin/post.php?post=${ products.simple.id }&action=edit`
				);
			} );

			await test.step( 'go to Linked Products', async () => {
				await page
					.getByRole( 'link', { name: 'Linked Products' } )
					.click();
			} );

			await use( products );

			// Cleanup
			for ( const product of Object.values( products ) ) {
				await api.delete( `products/${ product.id }`, { force: true } );
			}
		},
	} );

	test( 'add up-sells', async ( { page, products } ) => {
		const upsellTextBoxLocator = page
			.locator( 'p' )
			.filter( { hasText: 'Upsells' } )
			.getByRole( 'textbox' );

		await test.step( 'add an up-sell by searching for product name', async () => {
			await upsellTextBoxLocator.click();
			await upsellTextBoxLocator.fill( products.upsell1.name );
			await page.keyboard.press( 'Space' ); // This is needed to trigger the search
			await page
				.getByRole( 'option', { name: products.upsell1.name } )
				.click();
			await expect(
				page.getByRole( 'listitem', { name: products.upsell1.name } )
			).toBeVisible();
		} );

		await test.step( 'add an up-sell by searching for product id', async () => {
			await upsellTextBoxLocator.click();
			await upsellTextBoxLocator.fill( `${ products.upsell2.id }` );
			await page.keyboard.press( 'Space' ); // This is needed to trigger the search
			await page
				.getByRole( 'option', { name: products.upsell2.name } )
				.click();
			await expect(
				page.getByRole( 'listitem', { name: products.upsell2.name } )
			).toBeVisible();
			await expect(
				page.getByRole( 'listitem', { name: products.upsell1.name } )
			).toBeVisible();
		} );

		await test.step( 'publish the updated product', async () => {
			await page.getByRole( 'button', { name: 'Update' } ).click();
		} );

		await test.step( 'verify the up-sell in the store frontend', async () => {
			await page.goto( products.simple.permalink );
			const sectionLocator = page.locator( 'section' ).filter( {
				has: page.getByRole( 'heading', {
					name: 'You may also like',
				} ),
			} );

			await expect(
				sectionLocator.getByRole( 'heading', {
					name: products.upsell1.name,
				} )
			).toBeVisible();
			await expect(
				sectionLocator.getByRole( 'heading', {
					name: products.upsell2.name,
				} )
			).toBeVisible();
		} );
	} );

	test( 'add cross-sells', async ( { page, products } ) => {
		await test.step( 'Navigate to product edit page', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ products.simple.id }&action=edit`
			);
		} );
	} );
} );
