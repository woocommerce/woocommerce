const {
	test: baseTest,
} = require( '../../../../fixtures/block-editor-fixtures' );
const { expect } = require( '../../../../fixtures/fixtures' );
const { updateProduct } = require( '../../../../utils/product-block-editor' );

async function waitForAttributeList( page ) {
	// The list child is different in case there are no results versus when there already are some attributes, so we need to wait for either one to be visible.
	await Promise.race( [
		// in case there's at least one attribute
		// eslint-disable-next-line playwright/missing-playwright-await
		expect(
			page.getByRole( 'listbox' ).getByRole( 'option' ).first()
		).toBeVisible(),
		// in case there are no attributes
		// eslint-disable-next-line playwright/missing-playwright-await
		expect(
			page
				.getByRole( 'listbox' )
				.getByText( 'Nothing yet. Type to create.' )
		).toBeVisible(),
	] );
}

const test = baseTest.extend( {
	product: async ( { api }, use ) => {
		let product;

		await api
			.post( 'products', {
				name: `Product ${ Date.now() }`,
				type: 'simple',
			} )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		// Cleanup
		await api.delete( `products/${ product.id }`, { force: true } );
	},
	attributes: async ( { api }, use ) => {
		let attribute;
		let terms;

		await api
			.post( 'products/attributes', {
				name: `Color_${ Date.now() }`,
				slug: `pa_color_${ Date.now() }`,
			} )
			.then( ( response ) => {
				attribute = response.data;
			} );

		await api
			.post( `products/attributes/${ attribute.id }/terms/batch`, {
				create: [
					{
						name: 'red',
					},
					{
						name: 'blue',
					},
				],
			} )
			.then( ( response ) => {
				terms = response.data.create;
			} );

		await use( { attribute, terms } );

		// Cleanup
		await api.delete( `products/attributes/${ attribute.id }/terms/batch`, {
			delete: terms.map( ( term ) => term.id ),
		} );
		await api.delete( `products/attributes/${ attribute.id }` );
	},
	productWithAttributes: async ( { api, product, attributes }, use ) => {
		let updatedProduct;
		attributes.attribute.options = attributes.terms.map(
			( term ) => term.name
		);
		attributes.attribute.visible = true;
		await api
			.put( `products/${ product.id }`, {
				attributes: [ attributes.attribute ],
			} )
			.then( ( response ) => {
				updatedProduct = response.data;
			} );

		await use( updatedProduct );
	},
} );

test( 'can create and add attributes', async ( { page, product } ) => {
	const newAttributes = [
		{
			name: `pa_0_${ Date.now() }`,
			value: `value_0_${ Date.now() }`,
		},
		{
			name: `pa_1_${ Date.now() }`,
			value: `value_1_${ Date.now() }`,
		},
	];

	await test.step( 'go to product editor, Organization tab', async () => {
		await page.goto( `wp-admin/post.php?post=${ product.id }&action=edit` );
		await page.getByRole( 'button', { name: 'Organization' } ).click();
	} );

	await test.step( 'add new attributes', async () => {
		await page.getByRole( 'button', { name: 'Add new' } ).click();

		// Add attributes that do not exist
		await page.getByPlaceholder( 'Search or create attribute' ).click();

		// Unless we wait for the list to be visible, the attribute name will be filled too soon and the test will fail.
		await waitForAttributeList( page );

		await page
			.getByPlaceholder( 'Search or create attribute' )
			.fill( newAttributes[ 0 ].name );
		await page.getByText( `Create "${ newAttributes[ 0 ].name }"` ).click();

		await page.getByPlaceholder( 'Search or create value' ).click();
		await page
			.getByPlaceholder( 'Search or create value' )
			.fill( newAttributes[ 0 ].value );
		await page
			.getByText( `Create "${ newAttributes[ 0 ].value }"` )
			.click();

		// Add another attribute
		await page.getByLabel( 'Add another attribute' ).click();
		await page
			.getByPlaceholder( 'Search or create attribute' )
			.nth( 1 )
			.click();

		await waitForAttributeList( page );

		await page
			.getByPlaceholder( 'Search or create attribute' )
			.nth( 1 )
			.fill( newAttributes[ 1 ].name );
		await page.getByText( `Create "${ newAttributes[ 1 ].name }"` ).click();

		await page.getByPlaceholder( 'Search or create value' ).click();
		await page
			.getByPlaceholder( 'Search or create value' )
			.fill( newAttributes[ 1 ].value );
		await page
			.getByText( `Create "${ newAttributes[ 1 ].value }"` )
			.click();

		// Add attributes
		await page.getByRole( 'button', { name: 'Add attributes' } ).click();
	} );

	await test.step( 'verify attributes in product editor', async () => {
		// Verify new attributes in product editor
		for ( const attribute of newAttributes ) {
			const item = page.getByRole( 'listitem' ).filter( {
				has: page.getByText( attribute.name, { exact: true } ),
			} );
			await expect( item ).toBeVisible();
			await expect( item ).toContainText( attribute.value );
		}
	} );

	await test.step( 'update the product', async () => {
		await updateProduct( { page, expect } );
	} );

	await test.step( 'verify attributes in product editor after product update', async () => {
		// Verify attributes in product editor
		for ( const attribute of newAttributes ) {
			const item = page.getByRole( 'listitem' ).filter( {
				has: page.getByText( attribute.name, { exact: true } ),
			} );
			await expect( item ).toBeVisible();
			await expect( item ).toContainText( attribute.value );
		}
	} );

	await test.step( 'verify the changes in the store frontend', async () => {
		// Verify attributes in store frontend
		await page.goto( product.permalink );

		// Verify attributes in store frontend
		for ( const attribute of newAttributes ) {
			const item = page.getByRole( 'row' ).filter( {
				has: page.getByText( attribute.name, { exact: true } ),
			} );
			await expect( item ).toBeVisible();
			await expect( item ).toContainText( attribute.value );
		}
	} );
} );

test( 'can add existing attributes', async ( {
	page,
	product,
	attributes,
} ) => {
	await test.step( 'go to product editor, Organization tab', async () => {
		await page.goto( `wp-admin/post.php?post=${ product.id }&action=edit` );
		await page.getByRole( 'button', { name: 'Organization' } ).click();
	} );

	await test.step( 'add an existing attribute', async () => {
		await page.getByRole( 'button', { name: 'Add new' } ).click();

		// Add attributes that do not exist
		await page.getByPlaceholder( 'Search or create attribute' ).click();

		// Unless we wait for the list to be visible, the attribute name will be filled too soon and the test will fail.
		await waitForAttributeList( page );

		await page
			.getByPlaceholder( 'Search or create attribute' )
			.fill( attributes.attribute.name );
		await page
			.getByRole( 'option', { name: attributes.attribute.name } )
			.first()
			.click();

		await page.getByPlaceholder( 'Search or create value' ).click();

		for ( const term of attributes.terms ) {
			await page.getByLabel( term.name, { exact: true } ).check();
		}

		await page.keyboard.press( 'Escape' );

		// Add attributes
		await page.getByRole( 'button', { name: 'Add attributes' } ).click();
	} );

	const attributeRowLocator = page.getByRole( 'listitem' ).filter( {
		has: page.getByText( attributes.attribute.name, { exact: true } ),
	} );

	await test.step( 'verify attributes in product editor', async () => {
		await expect( attributeRowLocator ).toBeVisible();
		for ( const term of attributes.terms ) {
			await expect( attributeRowLocator ).toContainText( term.name );
		}
	} );

	await test.step( 'update the product', async () => {
		await updateProduct( { page, expect } );
	} );

	await test.step( 'verify attributes in product editor after product update', async () => {
		await expect( attributeRowLocator ).toBeVisible();
		for ( const term of attributes.terms ) {
			await expect( attributeRowLocator ).toContainText( term.name );
		}
	} );

	await test.step( 'verify the changes in the store frontend', async () => {
		await page.goto( product.permalink );

		const item = page.getByRole( 'row' ).filter( {
			has: page.getByText( attributes.attribute.name, { exact: true } ),
		} );
		await expect( item ).toBeVisible();
		for ( const term of attributes.terms ) {
			await expect( item ).toContainText( term.name );
		}
	} );
} );

// Test skipped because an issue with the options not always loading makes it flaky.
// See https://github.com/woocommerce/woocommerce/issues/44925
test.skip( 'can update product attributes', async ( {
	page,
	productWithAttributes,
} ) => {
	const attribute = productWithAttributes.attributes[ 0 ];

	await test.step( 'go to product editor, Organization tab', async () => {
		await page.goto(
			`wp-admin/post.php?post=${ productWithAttributes.id }&action=edit`
		);
		await page.getByRole( 'button', { name: 'Organization' } ).click();

		// Sometimes the attribute's terms take a while to load, and we need to reload and retry.
		// See https://github.com/woocommerce/woocommerce/issues/44925
		await expect(
			async () => {
				await page.reload();
				await page.getByRole( 'button', { name: 'Edit' } ).click();
				await expect(
					page.getByLabel( `Remove ${ attribute.options[ 0 ] }` )
				).toBeVisible( { timeout: 2000 } );
			},
			{
				message: "wait for the attribute's terms to load",
			}
		).toPass();
	} );

	await test.step( "update product's attribute terms", async () => {
		await page.getByLabel( `Remove ${ attribute.options[ 0 ] }` ).click();

		await page.getByLabel( 'Edit attribute' ).click();
	} );

	let attributeRowLocator = page.getByRole( 'listitem' ).filter( {
		has: page.getByText( attribute.name, {
			exact: true,
		} ),
	} );

	await test.step( 'verify attributes in product editor', async () => {
		await expect(
			attributeRowLocator,
			'The attribute item is still displayed'
		).toBeVisible();
		await expect(
			attributeRowLocator,
			'The remaining term is still displayed'
		).toContainText( attribute.options[ 1 ] );
		await expect(
			attributeRowLocator,
			'The removed term is not displayed'
		).not.toContainText( attribute.options[ 0 ] );
	} );

	await test.step( 'update the product', async () => {
		await updateProduct( { page, expect } );
	} );

	await test.step( 'verify attributes in product editor after product update', async () => {
		await expect(
			attributeRowLocator,
			'The attribute item is still displayed'
		).toBeVisible();
		await expect(
			attributeRowLocator,
			'The remaining term is still displayed'
		).toContainText( attribute.options[ 1 ] );
		await expect(
			attributeRowLocator,
			'The removed term is not displayed'
		).not.toContainText( attribute.options[ 0 ] );
	} );

	await test.step( 'verify the changes in the store frontend', async () => {
		await page.goto( productWithAttributes.permalink );

		attributeRowLocator = page.getByRole( 'row' ).filter( {
			has: page.getByText( attribute.name, { exact: true } ),
		} );
		await expect( attributeRowLocator ).toBeVisible();
		await expect(
			attributeRowLocator,
			'The remaining term is still displayed'
		).toContainText( attribute.options[ 1 ] );
		await expect(
			attributeRowLocator,
			'The removed term is not displayed'
		).not.toContainText( attribute.options[ 0 ] );
	} );
} );

test( 'can remove product attributes', async ( {
	page,
	productWithAttributes,
	attributes,
} ) => {
	await test.step( 'go to product editor, Organization tab', async () => {
		await page.goto(
			`wp-admin/post.php?post=${ productWithAttributes.id }&action=edit`
		);
		await page.getByRole( 'button', { name: 'Organization' } ).click();
	} );

	const attributeItemLocator = page.getByRole( 'listitem' ).filter( {
		has: page.getByText( attributes.attribute.name, { exact: true } ),
	} );
	page.on( 'dialog', ( dialog ) => dialog.accept() );

	await test.step( "remove product's attribute", async () => {
		await attributeItemLocator.getByLabel( 'Remove' ).click();
	} );

	await test.step( 'verify the change in product editor', async () => {
		await expect( attributeItemLocator ).toBeHidden();
	} );

	await test.step( 'update the product', async () => {
		await updateProduct( { page, expect } );
	} );

	await test.step( 'verify the change in product editor after update', async () => {
		await expect( attributeItemLocator ).toBeHidden();
	} );
} );
