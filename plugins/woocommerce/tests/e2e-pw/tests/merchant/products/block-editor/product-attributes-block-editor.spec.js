const { test: baseTest } = require( './block-editor-fixtures' );
const { expect } = require( '../../../../fixtures' );
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
		attributes.attribute.options = attributes.terms.map(
			( term ) => term.name
		);
		await api.put( `products/${ product.id }`, {
			attributes: [ attributes.attribute ],
		} );

		await use( product );
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
		await updateProduct( { page, expect } );
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

test( 'can add existing attributes', async ( {
	page,
	product,
	attributes,
} ) => {
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
			await page.getByRole( 'option', { name: term.name } ).click();
		}

		// Add attributes
		await page.getByRole( 'button', { name: 'Add attributes' } ).click();
	} );

	await test.step( 'update the product', async () => {
		await updateProduct( { page, expect } );
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

test( 'can update product attributes', async ( {
	page,
	productWithAttributes,
	attributes,
} ) => {
	await test.step( "update product's attribute value", async () => {
		await page.reload();

		await page.getByRole( 'button', { name: 'Edit' } ).click();
		await page
			.getByLabel( `Remove ${ attributes.terms[ 0 ].name }` )
			.click();

		await page.getByLabel( 'Edit attribute' ).click();

		//todo: verify the attributes in the product editor
	} );

	await test.step( 'update the product', async () => {
		await updateProduct( { page, expect } );
	} );

	await test.step( 'verify the change in product editor', async () => {
		// Verify attributes in product editor
		//todo: verify the attributes in the product editor
	} );

	await test.step( 'verify the changes in the store frontend', async () => {
		// Verify attributes in store frontend
		await page.goto( productWithAttributes.permalink );

		// Verify attributes in store frontend
		//todo: verify the attributes in the store frontend
	} );
} );
