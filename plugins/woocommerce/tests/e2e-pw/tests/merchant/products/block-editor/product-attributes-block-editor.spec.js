const {
	test: baseTest,
} = require( '../../../../fixtures/block-editor-fixtures' );
const { expect } = require( '../../../../fixtures/fixtures' );
const { updateProduct } = require( '../../../../utils/product-block-editor' );
const { clickOnTab } = require( '../../../../utils/simple-products' );
const attributesData = require( './fixtures/attributes' );
const {
	waitForGlobalAttributesLoaded,
} = require( './helpers/wait-for-global-attributes-loaded' );

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

test(
	'add local attribute (with terms) to the Product',
	{ tag: '@gutenberg' },
	async ( { page, product } ) => {
		await test.step( 'go to product editor -> Organization tab -> Click on `Add new`', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
			await clickOnTab( 'Organization', page );

			await page
				.getByRole( 'heading', { name: 'Attributes' } )
				.isVisible();

			await page.getByRole( 'button', { name: 'Add new' } ).click();

			await page
				.getByRole( 'heading', { name: 'Add variation options' } )
				.isVisible();

			await page.waitForLoadState( 'domcontentloaded' );

			// Confirm the Add button is disabled
			await expect(
				page.getByRole( 'button', { name: 'Add attributes' } )
			).toBeDisabled();
		} );

		await test.step( 'create local attributes with terms', async () => {
			/*
			 * attributeRowsLocator are the rows that contains
			 * the Attribute combobox and the Term FormTokenField.
			 */
			const attributeRowsLocator = page.locator(
				'.woocommerce-new-attribute-modal__table-row'
			);

			// First, check the app loads the attributes,
			await waitForGlobalAttributesLoaded( page );

			for ( const attribute of attributesData ) {
				const attributeRowLocator = attributeRowsLocator.last();

				const attributeComboboxLocator = attributeRowLocator
					.locator(
						'input[aria-describedby^="components-form-token-suggestions-howto-combobox-control"]'
					)
					.last();

				// Create new (local) product attribute.
				await attributeComboboxLocator.fill( attribute.name );
				await page
					.locator( `text=Create "${ attribute.name }"` )
					.click();

				const FormTokenFieldLocator = attributeRowLocator.locator(
					'td.woocommerce-new-attribute-modal__table-attribute-value-column'
				);

				// Term FormTokenField input locator
				const FormTokenFieldInputLocator =
					FormTokenFieldLocator.locator(
						'input[id^="components-form-token-input-"]'
					);

				// Add terms to the attribute.
				for ( const term of attribute.terms ) {
					await FormTokenFieldInputLocator.fill( term.name );
					await FormTokenFieldInputLocator.press( 'Enter' );
				}

				// Terms accepted, so the Add button should be enabled.
				await expect(
					page.getByRole( 'button', { name: 'Add attributes' } )
				).toBeEnabled();

				await page.getByLabel( 'Add another attribute' ).click();

				// Attribute no defined, so the Add button should be disabled.
				await expect(
					page.getByRole( 'button', { name: 'Add attributes' } )
				).toBeDisabled();
			}
		} );

		// Remove the last row, as it was added by the last click on "Add another attribute".
		await page
			.getByRole( 'button', { name: 'Remove attribute' } )
			.last()
			.click();

		await expect(
			page.getByRole( 'button', { name: 'Add attributes' } )
		).toBeEnabled();

		// Add the product attributes
		await page.getByRole( 'button', { name: 'Add attributes' } ).click();

		await test.step( 'verify attributes in product editor', async () => {
			// Locate the main attributes list element
			const attributesListLocator = page.locator(
				'[data-template-block-id="product-attributes-section"]'
			);
			await expect( attributesListLocator ).toBeVisible();

			const attributeRowsLocator =
				attributesListLocator.getByRole( 'listitem' );

			for ( const attribute of attributesData ) {
				const attributeRowLocator = attributeRowsLocator.filter( {
					has: page.getByText( attribute.name, { exact: true } ),
				} );
				await expect( attributeRowLocator ).toBeVisible();

				// UI only shows 3 terms, so we need to check if the term is visible.
				const shownTerms = attribute.terms.slice( 0, 3 ).entries();

				// Check if there are more terms than three.
				const moreThanThreeTerms = attribute.terms.length > 3;

				for ( const [ index, term ] of shownTerms ) {
					/*
					 * Disabling the eslint rule because the text
					 * is different when there are more than three terms.
					 */
					const termLabel =
						// eslint-disable-next-line playwright/no-conditional-in-test
						moreThanThreeTerms && index === 2
							? '+ 3 more'
							: term.name;
					// Pick the term element/locator
					const termLocator = attributeRowLocator
						.locator( `[aria-hidden="true"]` )
						.filter( {
							has: page.getByText( termLabel, {
								exact: true,
							} ),
						} );

					// Verify the term is visible
					await expect( termLocator ).toBeVisible();

					// Verify the term text
					await expect( termLocator ).toContainText( termLabel );
				}
			}
		} );

		await test.step( 'update the product', async () => {
			await updateProduct( { page, expect } );
		} );

		await test.step( 'verify the changes in the store frontend', async () => {
			// Verify attributes in store frontend
			await page.goto( product.permalink );

			// Verify attributes in store frontend
			for ( const attribute of attributesData ) {
				const item = page.getByRole( 'row' ).filter( {
					has: page.getByText( attribute.name, { exact: true } ),
				} );
				await expect( item ).toBeVisible();
				await expect( item ).toContainText( attribute.name );
			}
		} );
	}
);

test(
	'can add existing attributes',
	{ tag: '@gutenberg' },
	async ( { page, product, attributes } ) => {
		await test.step( 'go to product editor, Organization tab', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
			await page.getByRole( 'tab', { name: 'Organization' } ).click();
		} );

		await test.step( 'add an existing attribute', async () => {
			await page.getByRole( 'button', { name: 'Add new' } ).click();

			await page.waitForLoadState( 'domcontentloaded' );

			await page
				.locator( '.woocommerce-attributes-combobox input' )
				.click();

			// Unless we wait for the list to be visible, the attribute name will be filled too soon and the test will fail.
			await waitForAttributeList( page );

			await page
				.locator( '.woocommerce-attributes-combobox input' )
				.fill( attributes.attribute.name );
			await page
				.getByRole( 'option', { name: attributes.attribute.name } )
				.first()
				.click();

			await page.getByPlaceholder( 'Search or create value' ).click();

			for ( const term of attributes.terms ) {
				await page.getByText( term.name, { exact: true } ).click();
			}

			await page.keyboard.press( 'Escape' );

			// Add attributes
			await page
				.getByRole( 'button', { name: 'Add attributes' } )
				.click();
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
				has: page.getByText( attributes.attribute.name, {
					exact: true,
				} ),
			} );
			await expect( item ).toBeVisible();
			for ( const term of attributes.terms ) {
				await expect( item ).toContainText( term.name );
			}
		} );
	}
);

test(
	'can update product attributes',
	{ tag: '@gutenberg' },
	async ( { page, productWithAttributes } ) => {
		const attribute = productWithAttributes.attributes[ 0 ];

		await test.step( 'go to product editor, Organization tab', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ productWithAttributes.id }&action=edit`
			);
			await page.getByRole( 'tab', { name: 'Organization' } ).click();

			// Sometimes the attribute's terms take a while to load, and we need to reload and retry.
			// See https://github.com/woocommerce/woocommerce/issues/44925
			await expect(
				async () => {
					await page.reload();
					await page.getByRole( 'button', { name: 'Edit' } ).click();
					await expect(
						page.locator(
							`button[aria-label="Remove ${ attribute.options[ 0 ] }"]`
						)
					).toBeVisible( { timeout: 2000 } );
				},
				{
					message: "wait for the attribute's terms to load",
				}
			).toPass();
		} );

		await test.step( "update product's attribute terms", async () => {
			await page
				.getByLabel( `Remove ${ attribute.options[ 0 ] }` )
				.click();

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
	}
);

test(
	'can remove product attributes',
	{ tag: '@gutenberg' },
	async ( { page, productWithAttributes, attributes } ) => {
		await test.step( 'go to product editor, Organization tab', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ productWithAttributes.id }&action=edit`
			);
			const getAttributesResponsePromise = page.waitForResponse(
				( response ) =>
					response.url().includes( '/terms?attribute_id=' ) &&
					response.status() === 200
			);
			await page.getByRole( 'tab', { name: 'Organization' } ).click();
			await getAttributesResponsePromise;
			await page
				.getByLabel( 'Block: Product attributes' )
				.waitFor( { state: 'visible' } );
			await page
				.getByLabel( 'Block: Product attributes' )
				.scrollIntoViewIfNeeded();
		} );

		const attributeItemLocator = page.getByRole( 'listitem' ).filter( {
			has: page.getByText( attributes.attribute.name, { exact: true } ),
		} );
		page.on( 'dialog', ( dialog ) => dialog.accept() );

		await test.step( "remove product's attribute", async () => {
			await attributeItemLocator
				.getByLabel( 'Remove' )
				.click( { delay: 1000 } );
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
	}
);
