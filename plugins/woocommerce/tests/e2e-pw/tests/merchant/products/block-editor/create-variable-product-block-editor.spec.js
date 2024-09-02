/* eslint-disable playwright/no-conditional-in-test */
const { test } = require( '../../../../fixtures/block-editor-fixtures' );
const { expect } = require( '@playwright/test' );

const { clickOnTab } = require( '../../../../utils/simple-products' );
const {
	disableVariableProductBlockTour,
} = require( '../../../../utils/product-block-editor' );

const { variableProducts: utils } = require( '../../../../utils' );
const attributes = require( './fixtures/attributes' );
const tabs = require( './data/tabs' );
const {
	waitForGlobalAttributesLoaded,
} = require( './helpers/wait-for-global-attributes-loaded' );

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

const sizeAttribute = attributes.find(
	( attribute ) => attribute.name === 'Size'
);

const termsLength = sizeAttribute.terms.length;

let productId_editVariations,
	productId_deleteVariations,
	productId_singleVariation;

test.describe( 'Variations tab', { tag: '@gutenberg' }, () => {
	test.describe( 'Create variable products', () => {
		test.beforeAll( async ( { browser } ) => {
			productId_editVariations = await createVariableProduct(
				productAttributes
			);
			productId_deleteVariations = await createVariableProduct(
				productAttributes
			);
			productId_singleVariation = await createVariableProduct(
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
						name: 'Add options',
					} )
					.click();
			} );

			let newAttrData;
			await test.step( 'Create global attribute', async () => {
				await page
					.getByRole( 'heading', { name: 'Add variation options' } )
					.isVisible();

				await page.waitForLoadState( 'domcontentloaded' );

				/*
				 * Check the app loads the attributes,
				 * based on the Spinner visibility.
				 */
				await waitForGlobalAttributesLoaded( page );

				// Attribute combobox input
				const attributeInputLocator = page
					.getByRole( 'dialog' )
					.getByRole( 'combobox' )
					.first();

				await attributeInputLocator.fill( sizeAttribute.name );

				await page.locator( 'text=Create "Size"' ).click();

				// Wait for the create-attribute async request to finish
				const newAttrResponse = await page.waitForResponse(
					( response ) =>
						response
							.url()
							.includes(
								`wp-json/wc/v3/products/attributes?name=${ sizeAttribute.name }&generate_slug=true`
							) && response.status() === 201
				);

				newAttrData = await newAttrResponse.json();
			} );

			await test.step( 'Add new terms to the attribute', async () => {
				const FormTokenFieldLocator = page.locator(
					'td.woocommerce-new-attribute-modal__table-attribute-value-column'
				);

				const FormTokenFieldInputLocator =
					FormTokenFieldLocator.locator(
						'input[id^="components-form-token-input-"]'
					);

				for ( const term of sizeAttribute.terms ) {
					// Fill the input field with the option
					await FormTokenFieldInputLocator.fill( term.name );
					await FormTokenFieldInputLocator.press( 'Enter' );

					/*
					 * Check the new option is added to the list,
					 * by checking the last aria-hidden
					 */
					const newAriaHiddenTokenLocator =
						FormTokenFieldLocator.locator(
							'span.components-form-token-field__token-text > span[aria-hidden="true"]'
						).last();

					await expect( newAriaHiddenTokenLocator ).toHaveText(
						term.name
					);

					/*
					 * Wait for the async POST request
					 * that creates the new attribute term to finish.
					 */
					await page.waitForResponse( ( response ) => {
						const urlToMatch = `/wp-json/wc/v3/products/attributes/${ newAttrData.id }/terms?name=${ term.name }&slug=${ term.slug }&_locale=user`;

						return (
							response
								.url()
								.includes( encodeURI( urlToMatch ) ) &&
							response.status() === 201
						);
					} );
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
						`${ termsLength } variations do not have prices. Variations that do not have prices will not be visible to customers.Set prices`
					)
				).toBeVisible();

				page.on( 'dialog', ( dialog ) => dialog.accept( '50' ) );

				await page
					.getByRole( 'button', { name: 'Set prices' } )
					.click();

				await expect( page.getByText( '50' ).nth( 2 ) ).toBeVisible();

				await expect(
					page.getByLabel( 'Dismiss this notice' )
				).toContainText( `${ termsLength } variations updated.` );

				await expect(
					page.getByRole( 'button', {
						name: `Select all (${ termsLength })`,
					} )
				).toBeVisible();
			} );

			await test.step( 'Publish the product', async () => {
				await page
					.locator( '.woocommerce-product-header__actions' )
					.getByRole( 'button', {
						name: 'Publish',
					} )
					.click();

				const snackbarLocator = page.locator(
					'div.components-snackbar__content'
				);

				// Wait for the snackbar to appear
				await snackbarLocator.waitFor( {
					state: 'visible',
					timeout: 20000,
				} );

				// Verify that the first message is the expected one
				await expect( snackbarLocator.nth( 0 ) ).toHaveText(
					`${ sizeAttribute.terms.length } variations updated.`
				);

				// Verify that the second message is the expected one
				await expect( snackbarLocator.nth( 1 ) ).toHaveText(
					/Product published/
				);
			} );
		} );

		test( 'can edit a variation', async ( { page } ) => {
			await page.goto(
				`/wp-admin/admin.php?page=wc-admin&path=/product/${ productId_editVariations }`
			);

			await disableVariableProductBlockTour( { page } );

			await clickOnTab( 'Variations', page );

			await page
				.getByRole( 'button', { name: 'Generate from options' } )
				.click();

			const getVariationsResponsePromise = page.waitForResponse(
				( response ) =>
					response
						.url()
						.includes(
							`/wp-json/wc/v3/products/${ productId_editVariations }/variations`
						) && response.status() === 200
			);

			await clickOnTab( 'Variations', page );

			await getVariationsResponsePromise;

			// Wait for response only to avoid flaky filling regular price below in Inventory tab
			const waitResponse = page.waitForResponse(
				( response ) =>
					response
						.url()
						.includes(
							'wp-json/wc-admin/options?options=woocommerce_dimension_unit'
						) && response.status() === 200
			);

			await page
				.locator( '.woocommerce-product-variations__table-body > div' )
				.first()
				.getByText( 'Edit' )
				.click();

			await page
				.locator( '.woocommerce-product-tabs' )
				.getByRole( 'tab', { name: 'General' } )
				.click();

			await page
				.getByLabel( 'Regular price', { exact: true } )
				.waitFor( { state: 'visible' } );

			await waitResponse;

			await page.getByLabel( 'Regular price', { exact: true } ).click();

			await page
				.getByLabel( 'Regular price', { exact: true } )
				.fill( '100' );

			await page
				.locator( '.woocommerce-product-tabs' )
				.getByRole( 'tab', { name: 'Inventory' } )
				.click();

			await page
				.locator( '[name="woocommerce-product-sku"]' )
				.fill( `product-sku-${ new Date().getTime().toString() }` );

			await page
				.locator( '.woocommerce-product-header__actions' )
				.getByRole( 'button', {
					name: 'Update',
				} )
				.click();
			const element = page.locator( 'div.components-snackbar__content' );
			await expect( await element.innerText() ).toMatch(
				/Product updated./
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

			const getVariationsResponsePromise = page.waitForResponse(
				( response ) =>
					response
						.url()
						.includes(
							`/wp-json/wc/v3/products/${ productId_deleteVariations }/variations`
						) && response.status() === 200
			);

			await clickOnTab( 'Variations', page );

			await getVariationsResponsePromise;

			await page
				.getByRole( 'button', { name: 'Generate from options' } )
				.click();

			await getVariationsResponsePromise;

			await page.getByLabel( 'Actions', { exact: true } ).first().click();

			await page.getByLabel( 'Delete variation' ).click();

			await expect(
				page
					.getByLabel( 'Dismiss this notice' )
					.getByText( '1 variation deleted.' )
			).toBeVisible();

			await page.waitForSelector(
				'div.woocommerce-product-variations-pagination__info',
				{ timeout: 20000 }
			); // test was timing out before page loaded

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
				`/wp-admin/admin.php?page=wc-admin&path=/product/${ productId_singleVariation }&tab=variations`
			);

			await page
				.getByRole( 'button', { name: 'Generate from options' } )
				.click();

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
