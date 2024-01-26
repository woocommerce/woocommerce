const { test: baseTest, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

baseTest.describe( 'Products > Add Simple Product', () => {
	baseTest.use( { storageState: process.env.ADMINSTATE } );

	const category = 'e2e test products';
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
			description: `Simple product longer description`,
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
		},
	};

	const test = baseTest.extend( {
		api: async ( { baseURL }, use ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );

			// Create the category
			await api.post( 'products/categories', {
				name: category,
			} );

			await use( api );

			// Cleanup
			for ( const product of Object.values( productData ) ) {
				await api.delete( `products/${ product.id }`, { force: true } );
			}
		},
	} );

	for ( const productType of Object.keys( productData ) ) {
		test( `can create a simple ${ productType } product`, async ( {
			page,
		} ) => {
			const product = productData[ productType ];

			await test.step( 'navigate to products page', async () => {
				await page.goto( '/wp-admin/edit.php?post_type=product' );
			} );

			await test.step( 'add new product', async () => {
				await page
					.getByRole( 'link', { name: 'Add New' } )
					.last()
					.click();
			} );

			await test.step( 'fill in common product details', async () => {
				// Product name
				await page.getByLabel( 'Product name' ).fill( product.name );

				// Product description
				await page
					.locator( '.wp-editor-area' )
					.first()
					.fill( product.description );
				await page
					.locator( '.wp-editor-area' )
					.nth( 1 )
					.fill( product.shortDescription );

				// Product price
				await page
					.getByLabel( 'Regular price ($)' )
					.fill( product.regularPrice );
				await page.getByText( 'Inventory' ).click();

				// Inventory information
				await page
					.getByLabel( 'SKU', { exact: true } )
					.fill( product.sku );

				// Advanced information
				await page.getByText( 'Advanced' ).click();
				await page
					.getByLabel( 'Purchase note' )
					.fill( product.purchaseNote );
				await page.keyboard.press( 'Enter' );

				// Categories
				await page.getByRole( 'checkbox', { name: category } ).check();
				await expect(
					page.getByRole( 'checkbox', { name: category } )
				).toBeChecked();

				await expect(
					page
						.locator( '#product_cat-all' )
						.getByText( 'e2e test products' )
				).toBeVisible();

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
			if ( product.shipping ) {
				await test.step( 'add shipping details', async () => {
					await page
						.getByRole( 'link', { name: 'Shipping' } )
						.click();
					await page
						.getByPlaceholder( '0' )
						.fill( product.shipping.weight );
					await page
						.getByPlaceholder( 'Length' )
						.fill( product.shipping.length );
					await page
						.getByPlaceholder( 'Width' )
						.fill( product.shipping.width );
					await page
						.getByPlaceholder( 'Height' )
						.fill( product.shipping.height );
				} );
			}

			// eslint-disable-next-line playwright/no-conditional-in-test
			if ( product.virtual ) {
				await test.step( 'add virtual product details', async () => {
					await page.getByLabel( 'Virtual' ).check();
					await expect( page.getByLabel( 'Virtual' ) ).toBeChecked();
				} );
			}

			// eslint-disable-next-line playwright/no-conditional-in-test
			if ( product.downloadable ) {
				await test.step( 'add downloadable product details', async () => {
					await page.getByLabel( 'Downloadable' ).check();
					await expect(
						page.getByLabel( 'Downloadable' )
					).toBeChecked();
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

				product.id = page.url().match( /(?<=post=)\d+/ );
				expect( product.id ).toBeDefined();
			} );

			await test.step( 'verify the saved product in frontend', async () => {
				const permalink = await page
					.locator( '#sample-permalink a' )
					.innerText();

				await page.goto( permalink );

				// Verify product name
				await expect
					.soft( page.getByRole( 'heading', { name: product.name } ) )
					.toBeVisible();

				// Verify price
				await expect
					.soft( page.getByText( product.regularPrice ).first() )
					.toBeVisible();

				// Verify description
				await expect
					.soft( page.getByText( product.shortDescription ) )
					.toBeVisible();
				await expect
					.soft( page.getByText( product.description ) )
					.toBeVisible();
				await expect
					.soft( page.getByText( `SKU: ${ product.sku }` ) )
					.toBeVisible();

				// Verify category
				await expect
					.soft(
						page
							.getByText( 'Category' )
							.getByRole( 'link', { name: category } )
					)
					.toBeVisible();

				// Verify tags
				await expect
					.soft(
						page.getByRole( 'link', { name: 'e2e', exact: true } )
					)
					.toBeVisible();
				await expect
					.soft(
						page.getByRole( 'link', {
							name: 'test products',
							exact: true,
						} )
					)
					.toBeVisible();
			} );

			await test.step( 'shopper can add the product to cart', async () => {
				// logout admin user
				await page.context().clearCookies();
				await page.reload();

				await page
					.getByRole( 'button', { name: 'Add to cart' } )
					.click();
				await page.getByRole( 'link', { name: 'View cart' } ).click();

				await expect(
					page.getByRole( 'link' ).filter( { hasText: product.name } )
				).toBeVisible();

				await page
					.getByRole( 'link', { name: 'Proceed to checkout' } )
					.click();

				await expect(
					page.getByRole( 'cell' ).filter( { hasText: product.name } )
				).toBeVisible();
			} );
		} );
	}
} );
