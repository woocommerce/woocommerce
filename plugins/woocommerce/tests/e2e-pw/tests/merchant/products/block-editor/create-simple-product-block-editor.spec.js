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
	shipping: {
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
			isTrackingSupposedToBeEnabled,
			'The block product editor is not being tested'
		);

		test( 'can create a simple product', async ( { page } ) => {
			await test.step( 'add new product', async () => {
				await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
			} );

			await test.step( 'add product name, description, and summary', async () => {
				await clickOnTab( 'General', page );
				await page
					.getByPlaceholder( 'e.g. 12 oz Coffee Mug' )
					.fill( productData.name );

			await page
				.locator(
					'[data-template-block-id="product-description__content"] > p'
				)
				.fill( productData.descriptionSimple );

			await page.getByText( 'Full editor' ).click();

			const wordPressVersion = await getInstalledWordPressVersion();
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

			await test.step( 'add shipping details', async () => {
				await clickOnTab( 'Shipping', page );
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

				// Verify shipping dimensions
				await expect(
					page.getByText( `Weight ${ productData.shipping.weight }` )
				).toBeVisible();
				await expect(
					page.getByText(
						`Dimensions ${ productData.shipping.length } × ${ productData.shipping.width } × ${ productData.shipping.height }`
					)
				).toBeVisible();
			} );
		} );

		test( 'can not create a product with duplicated SKU', async ( {
			page,
		} ) => {
			await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
			await clickOnTab( 'General', page );
			await page
				.locator( '//input[@placeholder="e.g. 12 oz Coffee Mug"]' )
				.fill( productData.name );
			await page
				.locator(
					'[data-template-block-id="basic-details"] .components-summary-control'
				)
				.fill( productData.summary );

			await page
				.locator(
					'[id^="wp-block-woocommerce-product-regular-price-field"]'
				)
				.first()
				.fill( productData.productPrice );
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
