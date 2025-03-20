/**
 * Internal dependencies
 */
import { test as baseTest, expect, tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { WC_API_PATH } from '../../utils/api-client';
import { checkCartContent } from '../../utils/cart';

const productData = {
	virtual: {
		name: `Virtual product ${ Date.now() }`,
		description: `Virtual product longer description`,
		shortDescription: `Virtual product short description`,
		regularPrice: '100.05',
		sku: `0_${ Date.now() }`,
		virtual: true,
		purchaseNote: 'Virtual product purchase note',
	},
	'non virtual': {
		name: `Simple product ${ Date.now() }`,
		description: `<b>Simple product HTML description.</b> <em>This should be italic.</em>`,
		shortDescription: `Simple product short description`,
		regularPrice: '100.05',
		sku: `1_${ Date.now() }`,
		shipping: {
			weight: '2',
			length: '20',
			width: '10',
			height: '30',
		},
		purchaseNote: 'Simple product purchase note',
	},
	downloadable: {
		name: `Downloadable product ${ Date.now() }`,
		regularPrice: '100.05',
		description: `Downloadable product longer description`,
		shortDescription: `Downloadable product short description`,
		sku: `2_${ Date.now() }`,
		purchaseNote: 'Downloadable product purchase note',
		fileName: 'e2e-product.zip',
	},
};

function removeHtmlTags( str ) {
	return str.replace( /<\/?[^>]+(>|$)/g, '' );
}

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	product: async ( { restApi }, use ) => {
		const product = {};
		await use( product );
		await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
			force: true,
		} );
	},

	category: async ( { restApi }, use ) => {
		let category = {};

		await restApi
			.post( `${ WC_API_PATH }/products/categories`, {
				name: `cat_${ Date.now() }`,
			} )
			.then( ( response ) => {
				category = response.data;
			} );

		await use( category );

		// Category cleanup
		await restApi.delete(
			`${ WC_API_PATH }/products/categories/${ category.id }`,
			{ force: true }
		);
	},
} );

for ( const productType of Object.keys( productData ) ) {
	test(
		`can create a simple ${ productType } product`,
		{ tag: [ tags.GUTENBERG ] },
		async ( { page, category, product } ) => {
			await test.step( 'add new product', async () => {
				await page.goto( 'wp-admin/post-new.php?post_type=product' );
			} );

			await test.step( 'add product name and description', async () => {
				// Product name
				await page
					.getByLabel( 'Product name' )
					.fill( productData[ productType ].name );

				// Product description
				await page.locator( '#content-html' ).click(); // text mode to avoid the iframe
				await page
					.locator( '.wp-editor-area' )
					.first()
					.fill( productData[ productType ].description );
				await page.locator( '#excerpt-html' ).click(); // text mode to avoid the iframe
				await page
					.locator( '.wp-editor-area' )
					.nth( 1 )
					.fill( productData[ productType ].shortDescription );
			} );

			await test.step( 'add product price and inventory information', async () => {
				// Product price
				await page
					.getByLabel( 'Regular price ($)' )
					.fill( productData[ productType ].regularPrice );
				await page
					.getByRole( 'link' )
					.filter( { hasText: 'Inventory' } )
					.click();

				// Inventory information
				await page
					.getByLabel( 'SKU', { exact: true } )
					.fill( productData[ productType ].sku );
			} );

			await test.step( 'add product attributes', async () => {
				// Product attributes
				const attributeName = 'attribute name';
				await page
					.locator( '#woocommerce-product-data' )
					.getByRole( 'link', { name: 'Attributes' } )
					.click();
				await page
					.getByPlaceholder( 'e.g. length or weight' )
					.fill( attributeName );
				await page
					.getByPlaceholder( 'Enter some descriptive text.' )
					.fill( 'some attribute value' );
				await page.keyboard.press( 'Enter' );
				await page
					.getByRole( 'button', { name: 'Save attributes' } )
					.click();
				await expect(
					page.getByRole( 'heading', {
						name: `Remove ${ attributeName }`,
					} )
				).toBeVisible();
			} );

			await test.step( 'add product advanced information', async () => {
				// Advanced information
				await page
					.getByRole( 'link' )
					.filter( { hasText: 'Advanced' } )
					.first()
					.click();
				await page
					.getByLabel( 'Purchase note' )
					.fill( productData[ productType ].purchaseNote );
				await page.keyboard.press( 'Enter' );
			} );

			await test.step( 'add product categories', async () => {
				await page.getByText( category.name ).first().check();

				await expect(
					page
						.locator( '#product_cat-all' )
						.getByText( category.name )
				).toBeVisible();
			} );

			await test.step( 'add product tags', async () => {
				// Tags
				await page
					.getByLabel( 'Add new tag' )
					.fill( 'e2e,test products' );
				await page
					.getByRole( 'button', { name: 'Add', exact: true } )
					.click();
				await expect(
					page.locator( '#tagsdiv-product_tag li' ).getByText( 'e2e' )
				).toBeVisible();
				await expect(
					page
						.locator( '#tagsdiv-product_tag li' )
						.getByText( 'test products' )
				).toBeVisible();
			} );

			// eslint-disable-next-line playwright/no-conditional-in-test
			if ( productData[ productType ].shipping ) {
				await test.step( 'add shipping details', async () => {
					await page
						.getByRole( 'link', { name: 'Shipping' } )
						.click();
					await expect(
						page.getByText( 'Shipping class', { exact: true } )
					).toBeVisible();
					await page
						.locator('#_weight')
						.fill( productData[ productType ].shipping.weight );
					await page
						.getByPlaceholder( 'Length', { exact: true } )
						.fill( productData[ productType ].shipping.length );
					await page
						.getByPlaceholder( 'Width' )
						.fill( productData[ productType ].shipping.width );
					await page
						.getByPlaceholder( 'Height' )
						.fill( productData[ productType ].shipping.height );
				} );
			}

			// eslint-disable-next-line playwright/no-conditional-in-test
			if ( productData[ productType ].virtual ) {
				await test.step( 'add virtual product details', async () => {
					await page.getByRole('checkbox', { name: 'Virtual' }).check();
					await expect(page.getByRole('checkbox', { name: 'Virtual' })).toBeChecked();
				} );
			}

			// eslint-disable-next-line playwright/no-conditional-in-test
			if ( productData[ productType ].downloadable ) {
				await test.step( 'add downloadable product details', async () => {
					await page.getByLabel( 'Downloadable' ).check();
					await expect(
						page.getByLabel( 'Downloadable' )
					).toBeChecked();

					// Add a download link
					await page.getByRole( 'link', { name: 'General' } ).click();
					await page
						.getByRole( 'link', { name: 'Add File' } )
						.click();
					await page
						.getByPlaceholder( 'File name' )
						.fill( productData[ productType ].fileName );
					await page
						.getByPlaceholder( 'http://' )
						.fill(
							`https://example.com/${ productData[ productType ].fileName }`
						);
					await page.getByPlaceholder( 'Never' ).fill( '365' );
				} );
			}

			await test.step( 'publish the product', async () => {
				await page
					.getByRole( 'button', { name: 'Publish', exact: true } )
					.click();

				await expect(
					page
						.locator( 'div.notice-success > p' )
						.filter( { hasText: 'Product published.' } )
				).toBeVisible();

				product.id = page.url().match( /(?<=post=)\d+/ )[ 0 ];
				expect( product.id ).toBeDefined();
			} );

			await test.step( 'verify the saved product in frontend', async () => {
				const permalink = await page
					.locator( '#sample-permalink a' )
					.innerText();

				await page.goto( permalink );

				// Verify product name
				await expect(
					page.getByRole( 'heading', {
						name: productData[ productType ].name,
					} )
				).toBeVisible();

				// Verify price
				await expect(
					page
						.getByText( productData[ productType ].regularPrice )
						.first()
				).toBeVisible();

				// Verify description
				await expect(
					page
						.getByText(
							productData[ productType ].shortDescription
						)
						.first()
				).toBeVisible();
				await expect(
					page
						.getByText(
							removeHtmlTags(
								productData[ productType ].description
							)
						)
						.first()
				).toBeVisible();
				await expect(
					page.getByText( `SKU: ${ productData[ productType ].sku }` )
				).toBeVisible();

				// Verify category
				await expect(
					page.getByText( `Category: ${ category.name }` )
				).toBeVisible();

				// Verify tags
				await expect(
					page.getByRole( 'link', { name: 'e2e', exact: true } )
				).toBeVisible();
				await expect(
					page.getByRole( 'link', {
						name: 'test products',
						exact: true,
					} )
				).toBeVisible();
			} );

			await test.step( 'shopper can add the product to cart', async () => {
				// logout admin user
				await page.context().clearCookies();
				await page.reload();

				await page
					.getByRole( 'button', { name: 'Add to cart' } )
					.click();
				await page.getByRole( 'link', { name: 'View cart' } ).click();

				await checkCartContent(
					false,
					page,
					[
						{
							data: {
								name: productData[ productType ].name,
								price: productData[ productType ].regularPrice,
							},
							qty: 1,
						},
					],
					0
				);
			} );
		}
	);
}
