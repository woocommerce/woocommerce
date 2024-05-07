/* eslint-disable playwright/no-conditional-in-test */
const { test } = require( '../../../../fixtures/block-editor-fixtures' );
const { expect } = require( '@playwright/test' );

const { clickOnTab } = require( '../../../../utils/simple-products' );
const {
	disableVariableProductBlockTour,
} = require( '../../../../utils/product-block-editor' );

const { variableProducts: utils } = require( '../../../../utils' );

const {
	createVariableProduct,
	deleteProductsAddedByTests,
	showVariableProductTour,
	productAttributes,
} = utils;

const NEW_EDITOR_ADD_PRODUCT_URL =
	'wp-admin/admin.php?page=wc-admin&path=%2Fadd-product&tab=variations';

const isTrackingSupposedToBeEnabled = !! process.env.ENABLE_TRACKING;

const productData = {
	name: `Variable product Name ${ new Date().getTime().toString() }`,
	summary: 'This is a product summary',
};

const attributesData = {
	name: 'Size',
	options: [ 'Small', 'Medium', 'Large' ],
};

const tabs = [
	{
		name: 'General',
		noteText:
			"This product has options, such as size or color. You can manage each variation's images, downloads, and other details individually.",
	},
	{
		name: 'Pricing',
		noteText:
			"This product has options, such as size or color. You can now manage each variation's price and other details individually.",
	},
	{
		name: 'Inventory',
		noteText:
			"This product has options, such as size or color. You can now manage each variation's inventory and other details individually.",
	},
	{
		name: 'Shipping',
		noteText:
			"This product has options, such as size or color. You can now manage each variation's shipping settings and other details individually.",
	},
];

let productId_editVariations, productId_deleteVariations;

test.describe( 'Variations tab', () => {
	test.describe( 'Create variable product', () => {
		test.beforeAll( async ( { browser } ) => {
			productId_editVariations = await createVariableProduct(
				productAttributes
			);
			productId_deleteVariations = await createVariableProduct(
				productAttributes
			);
			await showVariableProductTour( browser, false );
		} );

		test.afterAll( async () => {
			await deleteProductsAddedByTests();
		} );
		test.skip(
			isTrackingSupposedToBeEnabled,
			'The block product editor is not being tested'
		);

		test( 'can create a variation option and publish the product', async ( {
			page,
		} ) => {
			await test.step( 'Load new product editor, disable tour', async () => {
				await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
				await disableVariableProductBlockTour( { page } );
			} );

			await test.step( 'Click on General tab, enter product name and summary', async () => {
				await clickOnTab( 'General', page );
				await page
					.getByPlaceholder( 'e.g. 12 oz Coffee Mug' )
					.fill( productData.name );
				await page
					.locator(
						'[data-template-block-id="basic-details"] .components-summary-control'
					)
					.last()
					.fill( productData.summary );
			} );

			await test.step( 'Click on Variations tab, add a new attribute', async () => {
				await clickOnTab( 'Variations', page );
				await page
					.getByRole( 'heading', { name: 'Variation options' } )
					.isVisible();

				await page
					.locator( '.woocommerce-attribute-field' )
					.getByRole( 'button', {
						name: 'Add sizes',
					} )
					.click();
			} );

			await test.step( 'Add attribute options', async () => {
				await page
					.getByRole( 'heading', { name: 'Add variation options' } )
					.isVisible();

				await page.waitForLoadState( 'domcontentloaded' );

				await page.locator( 'text=Create "Size"' ).click();

				const attributeColumn = page.getByPlaceholder(
					'Search or create attribute'
				);

				await expect( attributeColumn ).toHaveValue( 'Size' );

				for ( const option of attributesData.options ) {
					await page
						.locator(
							'.woocommerce-new-attribute-modal__table-attribute-value-column .woocommerce-experimental-select-control__input'
						)
						.fill( option );

					await page.locator( `text=Create "${ option }"` ).click();

					await expect(
						page.locator( '.woocommerce-attribute-term-field' )
					).toContainText( option );
				}

				await page
					.locator( '.woocommerce-new-attribute-modal__buttons' )
					.getByRole( 'button', {
						name: 'Add',
					} )
					.click();
			} );

			await test.step( 'Add prices to variations', async () => {
				await expect(
					page.getByText(
						'3 variations do not have prices. Variations that do not have prices will not be visible to customers.Set prices'
					)
				).toBeVisible();

				await page.on( 'dialog', ( dialog ) => dialog.accept( '50' ) );

				await page
					.getByRole( 'button', { name: 'Set prices' } )
					.click();

				await expect(
					page.getByText( '$50.00' ).nth( 2 )
				).toBeVisible();

				await expect(
					page.getByLabel( 'Dismiss this notice' )
				).toContainText( '3 variations updated.' );

				await expect(
					page.getByRole( 'button', { name: 'Select all (3)' } )
				).toBeVisible();
			} );

			await test.step( 'Publish the product', async () => {
				await page
					.locator( '.woocommerce-product-header__actions' )
					.getByRole( 'button', {
						name: 'Publish',
					} )
					.click();

				const element = page.locator(
					'div.components-snackbar__content'
				);
				if ( Array.isArray( element ) ) {
					await expect( await element[ 0 ].innerText() ).toMatch(
						`${ attributesData.options.length } variations updated.`
					);
					await expect( await element[ 1 ].innerText() ).toMatch(
						/Product published/
					);
				}
			} );
		} );

		test( 'can edit a variation', async ( { page } ) => {
			await page.goto(
				`/wp-admin/admin.php?page=wc-admin&path=/product/${ productId_editVariations }`
			);

			await clickOnTab( 'Variations', page );

			await page
				.getByRole( 'button', {
					name: 'Generate from options',
				} )
				.click();

			await clickOnTab( 'Variations', page );

			await page
				.locator( '.woocommerce-product-variations__table-body > div' )
				.first()
				.getByText( 'Edit' )
				.click();

			await page
				.locator( '.woocommerce-product-tabs' )
				.getByRole( 'button', { name: 'Pricing' } )
				.click();

			await page
				.getByLabel( 'Regular price', { exact: true } )
				.fill( '100' );

			await page
				.locator( '.woocommerce-product-tabs' )
				.getByRole( 'button', { name: 'Inventory' } )
				.click();

			await page
				.locator( '#inspector-input-control-2' )
				.fill( `product-sku-${ new Date().getTime().toString() }` );

			await page
				.locator( '.woocommerce-product-header__actions' )
				.getByRole( 'button', {
					name: 'Update',
				} )
				.click();
			const element = page.locator( 'div.components-snackbar__content' );
			await expect( await element.innerText() ).toMatch(
				/Product updated/
			);

			await page
				.locator( '.woocommerce-product-header__back-tooltip-wrapper' )
				.getByRole( 'button', {
					name: 'Main product',
				} )
				.click();

			await expect(
				page
					.locator(
						'.woocommerce-product-variations__table-body > div'
					)
					.first()
			).toBeVisible();
		} );

		test( 'can delete a variation', async ( { page } ) => {
			await page.goto(
				`/wp-admin/admin.php?page=wc-admin&path=/product/${ productId_deleteVariations }`
			);

			await clickOnTab( 'Variations', page );

			await page
				.locator(
					'.woocommerce-variations-table-error-or-empty-state__actions'
				)
				.getByRole( 'button', { name: 'Generate from options' } )
				.click();

			await page
				.locator( '.woocommerce-product-variations__table-body > div' )
				.first()
				.locator( 'button[aria-label="Actions"]' )
				.click();

			await page.locator( 'text=Delete' ).click( { timeout: 3000 } );

			const element = page.locator( 'div.components-snackbar__content' );
			await expect( await element.innerText() ).toMatch(
				'1 variation deleted.'
			);

			await expect(
				await page
					.locator(
						'.woocommerce-product-variations__table-body > div'
					)
					.count()
			).toEqual( 5 );
		} );

		test( 'can see variations warning and click the CTA', async ( {
			page,
		} ) => {
			await page.goto(
				`/wp-admin/admin.php?page=wc-admin&path=/product/${ productId_deleteVariations }`
			);

			for ( const tab of tabs ) {
				const { name: tabName, noteText } = tab;
				await clickOnTab( tabName, page );

				const notices = page.locator(
					'p.woocommerce-product-notice__content'
				);

				const noticeCount = await notices.count();

				for ( let i = 0; i < noticeCount; i++ ) {
					const notice = notices.nth( i );
					if ( await notice.isVisible() ) {
						await expect( notice ).toHaveText( noteText );
					}
				}

				await page
					.locator( '.woocommerce-product-notice__content' )
					.getByRole( 'button', { name: 'Go to Variations' } )
					.click();

				await expect(
					page.getByRole( 'heading', {
						name: 'Variation options',
					} )
				).toBeVisible();
			}
		} );

		test( 'can see single variation warning and click the CTA', async ( {
			page,
		} ) => {
			await page.goto(
				`/wp-admin/admin.php?page=wc-admin&path=/product/${ productId_deleteVariations }&tab=variations`
			);

			await expect(
				page.getByText(
					'variations do not have prices. Variations that do not have prices will not be visible to customers.Set prices'
				)
			).toBeVisible();

			await page
				.getByRole( 'link', { name: 'Edit', exact: true } )
				.first()
				.click();

			const notices = page.getByText(
				'You’re editing details specific to this variation.'
			);

			const noticeCount = await notices.count();

			const noteText =
				'You’re editing details specific to this variation.';

			for ( let i = 0; i < noticeCount; i++ ) {
				const notice = notices.nth( i );
				if ( await notice.isVisible() ) {
					await expect( notice ).toHaveText( noteText );
				}
			}

			await page
				.locator( '.woocommerce-product-notice__content > a' )
				.first()
				.click();

			await expect(
				page.getByRole( 'heading', {
					name: 'Variation options',
				} )
			).toBeVisible();
		} );
	} );
} );
