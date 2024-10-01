const { test } = require( '../../../../fixtures/block-editor-fixtures' );
const { expect } = require( '@playwright/test' );

const { clickOnTab } = require( '../../../../utils/simple-products' );
const {
	getInstalledWordPressVersion,
} = require( '../../../../utils/wordpress' );
const { insertBlock } = require( '../../../../utils/editor' );

const NEW_EDITOR_ADD_PRODUCT_URL =
	'wp-admin/admin.php?page=wc-admin&path=%2Fadd-product';

const isTrackingSupposedToBeEnabled = !! process.env.ENABLE_TRACKING;

const productData = {
	name: `Simple product Name ${ new Date().getTime().toString() }`,
	summary: 'This is a product summary',
	descriptionTitle: 'Product Description title',
	descriptionParagraph: 'This is a product description',
	descriptionSimple: 'This is a product simple description',
	productPrice: '100',
	salePrice: '90',
	customFields: [
		{ name: `custom-field_${ Date.now() }`, value: 'custom_1' },
	],
	sku: `sku_${ Date.now() }`,
	gtin: `${ Date.now() }`,
	shipping: {
		shippingClassName: `shipping-class_${ Date.now() }`,
		weight: '2',
		length: '20',
		width: '10',
		height: '30',
	},
};

test.describe.configure( { mode: 'serial' } );

test.describe( 'General tab', { tag: '@gutenberg' }, () => {
	test.describe( 'Simple product form', () => {
		test( 'renders each block without error', async ( { page } ) => {
			await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
			await clickOnTab( 'General', page );
			await page.getByPlaceholder( 'e.g. 12 oz Coffee Mug' ).isVisible();

			await expect( page.locator( '.block-editor-warning' ) ).toHaveCount(
				0
			);
		} );
	} );

	test.describe( 'Create product', () => {
		let productId;

		test.skip(
			// skip(condition, description) can be used at runtime to skip a test
			// eslint-disable-next-line jest/valid-title
			isTrackingSupposedToBeEnabled,
			'The block product editor is not being tested'
		);

		test(
			'can create a simple product',
			{ tag: '@skip-on-default-pressable' },
			async ( { page } ) => {
				await test.step( 'add new product', async () => {
					await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
				} );

				await test.step( 'add product name', async () => {
					await clickOnTab( 'General', page );
					await page
						.getByPlaceholder( 'e.g. 12 oz Coffee Mug' )
						// Have to use pressSequentially in order for the SKU to be auto-updated
						// before we move to the SKU field and attempt to fill it in; otherwise,
						// the SKU field can sometimes end up getting auto-updated after we have filled it in,
						// wiping out the value we entered.
						.pressSequentially( productData.name );
				} );

				await test.step( 'add simple product description', async () => {
					const descriptionSimpleParagraph = page.locator(
						'[data-template-block-id="product-description__content"] > p'
					);

					await descriptionSimpleParagraph.fill(
						productData.descriptionSimple
					);
				} );

				await test.step( 'add full product description', async () => {
					// Helps to ensure that block toolbar appears, by letting the editor
					// know that the user is done typing.
					await page.keyboard.press( 'Escape' );

					await page.getByText( 'Full editor' ).click();

					const wordPressVersion =
						await getInstalledWordPressVersion();
					await insertBlock( page, 'Heading', wordPressVersion );

					const editorCanvasLocator = page.frameLocator(
						'iframe[name="editor-canvas"]'
					);

					await editorCanvasLocator
						.locator( '[data-title="Heading"]' )
						.fill( productData.descriptionTitle );

					await editorCanvasLocator
						.locator( '[data-title="Heading"]' )
						.blur();

					await insertBlock( page, 'Paragraph', wordPressVersion );

					await editorCanvasLocator
						.locator( '[data-title="Paragraph"]' )
						.last()
						.fill( productData.descriptionParagraph );

					await page.getByRole( 'button', { name: 'Done' } ).click();
				} );

				await test.step( 'verify full product description', async () => {
					const previewContainerIframe = page
						.locator( '.block-editor-block-preview__container' )
						.frameLocator( 'iframe[title="Editor canvas"]' );

					const descriptionTitle = previewContainerIframe.locator(
						'[data-title="Heading"]'
					);
					const descriptionInitialParagraph = previewContainerIframe
						.locator( '[data-title="Paragraph"]' )
						.first();
					const descriptionSecondParagraph = previewContainerIframe
						.locator( '[data-title="Paragraph"]' )
						.last();

					await expect( descriptionTitle ).toHaveText(
						productData.descriptionTitle
					);
					await expect( descriptionInitialParagraph ).toHaveText(
						productData.descriptionSimple
					);
					await expect( descriptionSecondParagraph ).toHaveText(
						productData.descriptionParagraph
					);

					await descriptionTitle.click();

					await expect(
						page.getByText( 'Edit in full editor' )
					).toBeVisible();
				} );

				await test.step( 'add product summary', async () => {
					await page
						.locator(
							'[data-template-block-id="basic-details"] .components-summary-control'
						)
						.last()
						.fill( productData.summary );

					// Blur the summary field to hide the toolbar before clicking on the regular price field.
					await page
						.locator(
							'[data-template-block-id="basic-details"] .components-summary-control'
						)
						.last()
						.blur();
				} );

				await test.step( 'add product price', async () => {
					const regularPrice = page
						.locator( 'input[name="regular_price"]' )
						.first();
					await regularPrice.waitFor( { state: 'visible' } );
					await regularPrice.click();
					await regularPrice.fill( productData.productPrice );

					const salePrice = page
						.locator( 'input[name="sale_price"]' )
						.first();
					await salePrice.waitFor( { state: 'visible' } );
					await salePrice.click();
					await salePrice.fill( productData.salePrice );
				} );

				await test.step( 'add custom fields', async () => {
					await clickOnTab( 'Organization', page );

					const customFieldsAddNewButton = page
						.getByLabel(
							'Block: Product custom fields toggle control'
						)
						.getByRole( 'button', { name: 'Add new' } );

					// When re-running the test without resetting the env,
					// the custom fields toggle might be already checked,
					// so we need to check if the "Add new" button is already visible.
					//
					// eslint-disable-next-line playwright/no-conditional-in-test
					if ( ! ( await customFieldsAddNewButton.isVisible() ) ) {
						// Toggle the "Show custom fields" so that the "Add new" button is visible

						const customFieldsToggle = page.getByRole( 'checkbox', {
							name: 'Show custom fields',
						} );

						await customFieldsToggle.scrollIntoViewIfNeeded();

						// click() is used instead of check() because
						// Playwright sometimes has issues with custom checkboxes:
						// - https://github.com/microsoft/playwright/issues/13470
						// - https://github.com/microsoft/playwright/issues/20893
						// - https://github.com/microsoft/playwright/issues/27016
						//
						// eslint-disable-next-line playwright/no-conditional-in-test
						if ( ! ( await customFieldsToggle.isChecked() ) ) {
							await customFieldsToggle.click();
						}

						await customFieldsToggle.isEnabled();
					}

					await expect( customFieldsAddNewButton ).toBeVisible();

					await customFieldsAddNewButton.click();

					// Add custom fields modal
					const modal = page.locator(
						'.woocommerce-product-custom-fields__create-modal'
					);

					await expect(
						modal.getByText( 'Add custom fields' )
					).toBeVisible();

					const nameInput = modal.getByLabel( 'Name' );
					// Have to use pressSequentially in order to get the dropdown to show up and be able to select the option
					await nameInput.pressSequentially(
						productData.customFields[ 0 ].name
					);

					await expect(
						modal.getByRole(
							'option',
							productData.customFields[ 0 ].name
						)
					).toBeVisible();

					await nameInput.press( 'Enter' );

					const valueInput = modal.getByLabel( 'Value' );
					await valueInput.fill(
						productData.customFields[ 0 ].value
					);

					await modal
						.getByRole( 'button', { name: 'Add', exact: true } )
						.click();

					await expect(
						modal.getByText( 'Add custom fields' )
					).toBeHidden();

					await expect(
						page.getByText( productData.customFields[ 0 ].name )
					).toBeVisible();
					await expect(
						page.getByText( productData.customFields[ 0 ].value )
					).toBeVisible();
				} );

				await test.step( 'add inventory details', async () => {
					await clickOnTab( 'Inventory', page );

					await page
						.getByLabel( 'SKU (Stock Keeping Unit)' )
						.fill( productData.sku );
					await page
						.getByLabel( 'GTIN, UPC, EAN, or ISBN' )
						.fill( productData.gtin );
				} );

				await test.step( 'add shipping details', async () => {
					await clickOnTab( 'Shipping', page );

					// Shipping class
					await page
						.getByLabel( 'Shipping class', { exact: true } )
						//.locator( 'select[name="shipping_class"]' )
						.selectOption( 'Add new shipping class' );

					// New shipping class modal
					const modal = page.locator(
						'.woocommerce-add-new-shipping-class-modal'
					);

					await expect(
						modal.getByText( 'New shipping class' )
					).toBeVisible();

					await modal
						.getByLabel( 'Name (Required)' )
						.fill( productData.shipping.shippingClassName );

					await modal.getByText( 'Add' ).click();

					await expect(
						modal.getByText( 'New shipping class' )
					).toBeHidden();

					await expect(
						page.getByLabel( 'Shipping class', { exact: true } )
					).toHaveValue( productData.shipping.shippingClassName );

					// Shipping dimensions
					await page
						.getByLabel( 'Width A' )
						.fill( productData.shipping.width );
					await page
						.getByLabel( 'Length B' )
						.fill( productData.shipping.length );
					await page
						.getByLabel( 'Height C' )
						.fill( productData.shipping.height );
					await page
						.getByLabel( 'Weight' )
						.fill( productData.shipping.weight );
				} );

				await test.step( 'publish the product', async () => {
					await page
						.locator( '.woocommerce-product-header__actions' )
						.getByRole( 'button', {
							name: 'Publish',
						} )
						.click();

					await expect(
						page.getByLabel( 'Dismiss this notice' )
					).toContainText( 'Product published' );

					const title = page.locator(
						'.woocommerce-product-header__title'
					);

					// Save product ID
					const productIdRegex = /product%2F(\d+)/;
					const url = page.url();
					const productIdMatch = productIdRegex.exec( url );
					// This isn't really a conditional branch in the test;
					// just making sure we don't blow up if the regex doesn't match
					// (it will be caught in the expect below).
					// eslint-disable-next-line playwright/no-conditional-in-test
					productId = productIdMatch ? productIdMatch[ 1 ] : null;

					expect( productId ).toBeDefined();
					await expect( title ).toHaveText( productData.name );
				} );

				// Note for future refactoring: It would be good to reuse the verification step
				// from product-create-simple.spec.js, as both tests are just verifying that the
				// product was created correctly by looking at the front end.
				await test.step( 'verify the saved product in frontend', async () => {
					const permalink = await page
						.locator( '.product-details-section__product-link a' )
						.getAttribute( 'href' );

					await page.goto( permalink );

					// Verify product name
					await expect(
						page.getByRole( 'heading', {
							name: productData.name,
						} )
					).toBeVisible();

					// Verify price
					await expect(
						page.getByText( productData.productPrice ).first()
					).toBeVisible();
					await expect(
						page.getByText( productData.salePrice ).first()
					).toBeVisible();

					// Verify summary
					await expect(
						page.getByText( productData.summary )
					).toBeVisible();

					// Verify description
					await page
						.getByRole( 'tab', { name: 'Description' } )
						.click();

					await expect(
						page.getByText( productData.descriptionTitle )
					).toBeVisible();
					await expect(
						page.getByText( productData.descriptionSimple )
					).toBeVisible();
					await expect(
						page.getByText( productData.descriptionParagraph )
					).toBeVisible();

					// Verify inventory details
					await expect(
						page.getByText( `SKU: ${ productData.sku }` )
					).toBeVisible();
					// Note: GTIN is not displayed in the front end in the theme used in the test

					// Note: Shipping class is not displayed in the front end in the theme used in the test

					// Verify shipping dimensions
					await page
						.getByRole( 'tab', { name: 'Additional information' } )
						.click();

					await expect(
						page.getByText(
							`Weight ${ productData.shipping.weight }`
						)
					).toBeVisible();
					await expect(
						page.getByText(
							`Dimensions ${ productData.shipping.length } × ${ productData.shipping.width } × ${ productData.shipping.height }`
						)
					).toBeVisible();
				} );
			}
		);

		test( 'can not create a product with duplicated SKU', async ( {
			page,
		} ) => {
			await test.step( 'add new product', async () => {
				await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
			} );

			await test.step( 'add product name', async () => {
				await clickOnTab( 'General', page );
				await page
					.locator( '//input[@placeholder="e.g. 12 oz Coffee Mug"]' )
					// Have to use pressSequentially in order for the SKU to be auto-updated
					// before we move to the SKU field and attempt to fill it in; otherwise,
					// the SKU field can sometimes end up getting auto-updated after we have filled it in,
					// wiping out the value we entered.
					.pressSequentially( productData.name );
			} );

			await test.step( 'add product price', async () => {
				const regularPrice = page
					.locator( 'input[name="regular_price"]' )
					.first();
				await regularPrice.waitFor( { state: 'visible' } );
				await regularPrice.click();
				await regularPrice.fill( productData.productPrice );
			} );

			await test.step( 'add inventory details', async () => {
				await clickOnTab( 'Inventory', page );

				await page
					.getByLabel( 'SKU (Stock Keeping Unit)' )
					.fill( productData.sku );
			} );

			await test.step( 'publish the product', async () => {
				await page
					.locator( '.woocommerce-product-header__actions' )
					.getByRole( 'button', {
						name: 'Publish',
					} )
					.click();

				await expect(
					page.locator( '.components-snackbar__content' )
				).toContainText( 'Invalid or duplicated SKU.' );
			} );
		} );

		// Note for future refactoring: It would be good to reuse the verification step
		// from product-create-simple.spec.js, as both tests are just verifying that the
		// product that was created can be added to the cart in the front end.
		test( 'can a shopper add the simple product to the cart', async ( {
			page,
		} ) => {
			await page.goto( `/?post_type=product&p=${ productId }` );

			await page.locator( 'button[name="add-to-cart"]' ).click();
			await page.getByRole( 'link', { name: 'View cart' } ).click();
			await expect(
				page.locator( 'td[data-title=Product]' ).first()
			).toContainText( productData.name );
			await page
				.locator( `a.remove[data-product_id='${ productId }']` )
				.click();
			await expect(
				page.locator( `a.remove[data-product_id='${ productId }']` )
			).toBeHidden();
		} );
	} );
} );
