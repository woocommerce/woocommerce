const { test: baseTest } = require( './block-editor-fixtures' );
const { expect } = require( '../../../../fixtures' );

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
				name: 'Color',
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

		await use( attribute, terms );

		// Cleanup
		await api.delete( `products/attributes/${ attribute.id }/terms/batch`, {
			delete: terms.map( ( term ) => term.id ),
		} );
		await api.delete( `products/attributes/${ attribute.id }` );
	},
	page: async ( { page, product }, use ) => {
		await test.step( 'go to product editor, inventory tab', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
			await page.getByRole( 'button', { name: 'Organization' } ).click();
		} );

		await use( page );
	},
} );

test( 'can create product attributes', async ( { page, product } ) => {
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

	await test.step( 'add a new attribute', async () => {
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

	await test.step( 'update the product', async () => {
		await page.getByRole( 'button', { name: 'Update' } ).click();
		// Verify product was updated
		await expect( page.getByLabel( 'Dismiss this notice' ) ).toContainText(
			'Product updated'
		);
	} );

	await test.step( 'verify the change in product editor', async () => {
		// Verify attributes in product editor
		//todo: verify the attributes in the product editor
	} );

	await test.step( 'verify the changes in the store frontend', async () => {
		// Verify attributes in store frontend
		await page.goto( product.permalink );

		// Verify attributes in store frontend
		//todo: verify the attributes in the store frontend
	} );
} );
