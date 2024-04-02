const { test: baseTest, expect } = require( '../../fixtures/fixtures' );

baseTest.describe( 'Products > Related products', () => {
	const test = baseTest.extend( {
		storageState: process.env.ADMINSTATE,
		products: async ( { api }, use ) => {
			const keys = [ 'main', 'linked1', 'linked2' ];
			const products = {};

			for ( const key of Object.values( keys ) ) {
				await api
					.post( 'products', {
						name: `${ key } ${ Date.now() }`,
						type: 'simple',
						regular_price: '12.99',
					} )
					.then( ( response ) => {
						products[ key ] = response.data;
					} );
			}

			await use( products );

			// Cleanup
			for ( const product of Object.values( products ) ) {
				await api.delete( `products/${ product.id }`, { force: true } );
			}
		},
	} );

	async function navigate( page, productId ) {
		await test.step( 'Navigate to product edit page', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ productId }&action=edit`
			);
		} );

		await test.step( 'go to Linked Products', async () => {
			await page.getByRole( 'link', { name: 'Linked Products' } ).click();
		} );
	}

	async function updateProduct( page ) {
		await test.step( 'update the product', async () => {
			// extra click somewhere in the page as a workaround for update button click not always working
			await page
				.getByRole( 'heading', { name: 'Edit product', exact: true } )
				.click();
			await page.getByRole( 'button', { name: 'Update' } ).click();
			await expect( page.getByText( 'Product updated.' ) ).toBeVisible();
		} );
	}

	test( 'add up-sells', async ( { page, products } ) => {
		await navigate( page, products.main.id );

		const upsellTextBoxLocator = page
			.locator( 'p' )
			.filter( { hasText: 'Upsells' } )
			.getByRole( 'textbox' );

		await test.step( 'add an up-sell by searching for product name', async () => {
			await upsellTextBoxLocator.click();
			await upsellTextBoxLocator.fill( products.linked1.name );
			await page.keyboard.press( 'Space' ); // This is needed to trigger the search
			await page
				.getByRole( 'option', { name: products.linked1.name } )
				.click();
			await expect(
				page.getByRole( 'listitem', {
					name: products.linked1.name,
				} )
			).toBeVisible();
		} );

		await test.step( 'add an up-sell by searching for product id', async () => {
			await upsellTextBoxLocator.click();
			await upsellTextBoxLocator.fill( `${ products.linked2.id }` );
			await page.keyboard.press( 'Space' ); // This is needed to trigger the search
			await page
				.getByRole( 'option', { name: products.linked2.name } )
				.click();
			await expect(
				page.getByRole( 'listitem', {
					name: products.linked2.name,
				} )
			).toBeVisible();
			await expect(
				page.getByRole( 'listitem', {
					name: products.linked1.name,
				} )
			).toBeVisible();
		} );

		await updateProduct( page );

		await test.step( 'verify the up-sell in the store frontend', async () => {
			await page.goto( products.main.permalink );

			await expect(
				page.locator( 'section.upsells' ).getByRole( 'heading', {
					name: products.linked1.name,
				} )
			).toBeVisible();
			await expect(
				page.locator( 'section.upsells' ).getByRole( 'heading', {
					name: products.linked2.name,
				} )
			).toBeVisible();
		} );
	} );

	test( 'remove up-sells', async ( { page, api, products } ) => {
		// Add up-sells
		await api.put( `products/${ products.main.id }`, {
			upsell_ids: [ products.linked1.id ],
		} );

		// Verify up-sells are present, so we can assert the opposite after removing them
		// This should prevent a possible false negative result
		await test.step( 'verify the up-sells in the store frontend', async () => {
			await page.goto( products.main.permalink );

			await expect(
				page.locator( 'section.upsells' ).getByRole( 'heading', {
					name: products.linked1.name,
				} )
			).toBeVisible();
		} );

		await navigate( page, products.main.id );

		await test.step( 'remove up-sells for a product', async () => {
			// Using backspace to remove the product because clicking the remove button is flaky
			await page
				.locator( 'p' )
				.filter( { hasText: 'Upsells' } )
				.getByRole( 'textbox' )
				.click();
			await page.keyboard.press( 'Backspace' );

			await expect(
				page.getByRole( 'listitem', { name: products.linked1.name } )
			).toBeHidden();
		} );

		await updateProduct( page );

		await test.step( 'verify the up-sells in the store frontend', async () => {
			await page.goto( products.main.permalink );

			await expect(
				page.locator( 'section.upsells' ).getByRole( 'heading', {
					name: products.linked1.name,
				} )
			).toBeHidden();
		} );
	} );

	test( 'add cross-sells', async ( { page, products } ) => {
		await navigate( page, products.main.id );

		const upsellTextBoxLocator = page
			.locator( 'p' )
			.filter( { hasText: 'Cross-sells' } )
			.getByRole( 'textbox' );

		await test.step( 'add a cross-sell by searching for product name', async () => {
			await upsellTextBoxLocator.click();
			await upsellTextBoxLocator.fill( products.linked1.name );
			await page.keyboard.press( 'Space' ); // This is needed to trigger the search
			await page
				.getByRole( 'option', { name: products.linked1.name } )
				.click();
			await expect(
				page.getByRole( 'listitem', {
					name: products.linked1.name,
				} )
			).toBeVisible();
		} );

		await test.step( 'add a cross-sell by searching for product id', async () => {
			await upsellTextBoxLocator.click();
			await upsellTextBoxLocator.fill( `${ products.linked2.id }` );
			await page.keyboard.press( 'Space' ); // This is needed to trigger the search
			await page
				.getByRole( 'option', { name: products.linked2.name } )
				.click();
			await expect(
				page.getByRole( 'listitem', {
					name: products.linked2.name,
				} )
			).toBeVisible();
			await expect(
				page.getByRole( 'listitem', {
					name: products.linked1.name,
				} )
			).toBeVisible();
		} );

		await updateProduct( page );

		await test.step( 'verify the cross-sell in the store frontend', async () => {
			await page.goto( products.main.permalink );

			// add to cart and view proceed to checkout
			await page
				.getByRole( 'button', { name: 'Add to cart', exact: true } )
				.click();
			await page.getByRole( 'link', { name: 'View cart' } ).click();

			// check for cross-sells
			const sectionLocator = page.locator( 'div' ).filter( {
				has: page.getByRole( 'heading', {
					name: 'You may be interested in',
				} ),
			} );

			await expect(
				sectionLocator.getByRole( 'heading', {
					name: products.linked1.name,
				} )
			).toBeVisible();
			await expect(
				sectionLocator.getByRole( 'heading', {
					name: products.linked2.name,
				} )
			).toBeVisible();
		} );
	} );

	test( 'remove cross-sells', async ( { page, api, products } ) => {
		// Add cross-sells
		await api.put( `products/${ products.main.id }`, {
			cross_sell_ids: [ products.linked1.id ],
		} );

		await navigate( page, products.main.id );

		await test.step( 'remove cross-sells for a product', async () => {
			// Using backspace to remove the product because clicking the remove button is flaky
			await page
				.locator( 'p' )
				.filter( { hasText: 'Cross-sells' } )
				.getByRole( 'textbox' )
				.click();
			await page.keyboard.press( 'Backspace' );

			await expect(
				page.getByRole( 'listitem', { name: products.linked1.name } )
			).toBeHidden();
		} );

		await updateProduct( page );

		await test.step( 'verify the cross-sells in the store frontend', async () => {
			await page.goto( products.main.permalink );

			// add to cart and view proceed to checkout
			await page
				.getByRole( 'button', { name: 'Add to cart', exact: true } )
				.click();
			await page.getByRole( 'link', { name: 'View cart' } ).click();

			// check for cross-sells
			await expect(
				page.getByText( products.linked1.name )
			).toBeHidden();
		} );
	} );
} );
