/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
	clickTab,
	uiUnblocked,
	evalAndClick,
	setCheckbox,
	setBrowserViewport,
	verifyAndPublish,
	waitForSelector,
	waitForSelectorWithoutThrow
} = require( '@woocommerce/e2e-utils' );
const {
	waitAndClick,
} = require( '@woocommerce/e2e-environment' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );
const config = require( 'config' );

const VirtualProductName = 'Virtual Product Name';
const NonVirtualProductName = 'Non-Virtual Product Name';
const simpleProductPrice = config.has('products.simple.price') ? config.get('products.simple.price') : '9.99';
const defaultAttributes = [ 'val2', 'val1', 'val2' ];

const openNewProductAndVerify = async () => {
	// Go to "add product" page
	await merchant.openNewProduct();

	// Make sure we're on the add product page
	await expect(page.title()).resolves.toMatch('Add new product');
}

// mytodo: const selectActionVariations = async()=>{}

const runAddSimpleProductTest = () => {
	describe('Add New Simple Product Page', () => {
		beforeAll(async () => {
			await merchant.login();
		});

		it('can create simple virtual product and add it to the cart', async () => {

			// @todo: remove this once https://github.com/woocommerce/woocommerce/issues/31337 has been addressed
			await setBrowserViewport( {
				width: 970,
				height: 700,
			} );

			await openNewProductAndVerify();

			// Set product data and publish the product
			await expect(page).toFill('#title', VirtualProductName);
			await expect(page).toClick('#_virtual');
			await clickTab('General');
			await expect(page).toFill('#_regular_price', simpleProductPrice);
			await verifyAndPublish();

			await merchant.logout();
		});

		it('can have a shopper add the simple virtual product to the cart', async () => {
			// See product in the shop and add it to the cart
			await shopper.goToShop();
			await shopper.addToCartFromShopPage(VirtualProductName);
			await shopper.goToCart();
			await shopper.productIsInCart(VirtualProductName);

			// Assert that the page does not contain shipping calculation button
			await expect(page).not.toMatchElement('a.shipping-calculator-button');

			// Remove product from cart
			await shopper.removeFromCart(VirtualProductName);
		});

		it('can create simple non-virtual product and add it to the cart', async () => {
			// @todo: remove this once https://github.com/woocommerce/woocommerce/issues/31337 has been addressed
			await setBrowserViewport( {
				width: 960,
				height: 700,
			} );

			await merchant.login();
			await openNewProductAndVerify();

			// Set product data and publish the product
			await expect(page).toFill('#title', NonVirtualProductName);
			await clickTab('General');
			await expect(page).toFill('#_regular_price', simpleProductPrice);
			await verifyAndPublish();

			await merchant.logout();
		});

		it('can have a shopper add the simple non-virtual product to the cart', async () => {
			// See product in the shop and add it to the cart
			await shopper.goToShop();

			await page.reload({ waitUntil: ['networkidle0', 'domcontentloaded'] });

			await shopper.addToCartFromShopPage(NonVirtualProductName);
			await shopper.goToCart();
			await shopper.productIsInCart(NonVirtualProductName);

			// Assert that the page does contain shipping calculation button
			await page.waitForSelector('a.shipping-calculator-button');
			await expect(page).toMatchElement('a.shipping-calculator-button');

			// Remove product from cart
			await shopper.removeFromCart(NonVirtualProductName);
		});
	});
};

const runAddVariableProductTest = () => {
	describe('Add New Variable Product Page', () => {
		beforeAll(async () => {
			await merchant.login();
		});

		it('can create product with variations', async () => {
			await openNewProductAndVerify();

			// Set product data
			await expect(page).toFill('#title', 'Variable Product with Three Variations');
			await expect(page).toSelect('#product-type', 'Variable product');
		});

		it('can create set variable product attributes', async () => {

			// Create attributes for variations
			await waitAndClick( page, '.attribute_tab a' );
			await expect( page ).toSelect( 'select[name="attribute_taxonomy"]', 'Custom product attribute' );

			for ( let i = 0; i < 3; i++ ) {
				await expect(page).toClick( 'button.add_attribute', {text: 'Add'} );
				// Wait for attribute form to load
				await uiUnblocked();

				await page.focus(`input[name="attribute_names[${i}]"]`);
				await expect(page).toFill(`input[name="attribute_names[${i}]"]`, 'attr #' + (i + 1));
				await expect(page).toFill(`textarea[name="attribute_values[${i}]"]`, 'val1 | val2');
				await waitAndClick( page, `input[name="attribute_variation[${i}]"]`);
			}

			await expect(page).toClick( 'button', {text: 'Save attributes'});

			// Wait for attribute form to save (triggers 2 UI blocks)
			await uiUnblocked();
			await uiUnblocked();
		});

		it('can create variable product variations', async () => {
			// Create variations from attributes
			await waitForSelector( page, '.variations_tab' );
			await waitAndClick( page, '.variations_tab a' );
			await waitForSelector( page, 'select.variation_actions:not(:disabled)');
			await page.focus('select.variation_actions');
			await expect(page).toSelect('select.variation_actions', 'Create variations from all attributes');

			// headless: false doesn't require this
			const firstDialog = await expect(page).toDisplayDialog(async () => {
				await evalAndClick( 'a.do_variation_action' );
			});

			await expect(firstDialog.message()).toMatch('Are you sure you want to link all variations?');

			// Set some variation data
			await uiUnblocked();
			await uiUnblocked();
		});

		it('can add variation attributes', async () => {
			await waitAndClick( page, '.variations_tab a' );
			await uiUnblocked();
			await waitForSelector(
				page,
				'select[name="attribute_attr-1[0]"]',
				{
					visible: true,
					timeout: 5000
				}
			);

			// Verify that variations were created
			for ( let index = 0; index < 8; index++ ) {
				const val1 = { text: 'val1' };
				const val2 = { text: 'val2' };

				// odd / even
				let attr3 = !! ( index % 2 );
				// 0-1,4-5 / 2-3,6-7
				let attr2 = ( index % 4 ) > 1;
				// 0-3 / 4-7
				let attr1 = ( index > 3 );

				await expect( page ).toMatchElement( `select[name="attribute_attr-1[${index}]"]`, attr1 ? val2 : val1 );
				await expect( page ).toMatchElement( `select[name="attribute_attr-2[${index}]"]`, attr2 ? val2 : val1 );
				await expect( page ).toMatchElement( `select[name="attribute_attr-3[${index}]"]`, attr3 ? val2 : val1 );
			}

			await waitAndClick( page, '.variations-pagenav .expand_all');
			await waitForSelectorWithoutThrow( 'input[name="variable_is_virtual[0]"]', 5 );
			await setCheckbox('input[name="variable_is_virtual[0]"]');
			await expect(page).toFill('input[name="variable_regular_price[0]"]', '9.99');

			await setCheckbox('input[name="variable_is_virtual[1]"]');
			await expect(page).toFill('input[name="variable_regular_price[1]"]', '11.99');

			await setCheckbox('input[name="variable_manage_stock[2]"]');
			await expect(page).toFill('input[name="variable_regular_price[2]"]', '20');
			await expect(page).toFill('input[name="variable_weight[2]"]', '200');
			await expect(page).toFill('input[name="variable_length[2]"]', '10');
			await expect(page).toFill('input[name="variable_width[2]"]', '20');
			await expect(page).toFill('input[name="variable_height[2]"]', '15');

			await page.focus('button.save-variation-changes');
			await expect(page).toClick('button.save-variation-changes', {text: 'Save changes'});
		});

		it( 'can bulk-edit variations', async () => {
			// mytodo
		} );

		it( 'can delete all variations', async () => {
			// Select "Delete all variations" from the actions menu.
			await expect( page ).toSelect(
				'select.variation_actions',
				'Delete all variations'
			);
			const firstDialog = await expect( page ).toDisplayDialog(
				async () => {
					await evalAndClick( 'a.do_variation_action' );
				}
			);

			// Verify that confirmation dialog shows the correct message.
			await expect( firstDialog.message() ).toMatch(
				'Are you sure you want to delete all variations? This cannot be undone.'
			);
			await uiUnblocked();

			// Verify that no variations were displayed.
			const variationsCount = await page.$$( '.woocommerce_variation' );
			expect( variationsCount ).toHaveLength( 0 );
		} );

		it( 'can manually add a variation', async () => {
			// Select "Add variation" from the actions menu.
			await expect( page ).toSelect(
				'select.variation_actions',
				'Add variation'
			);
			await expect( page ).toClick( 'a.do_variation_action' );
			await uiUnblocked();

			// Set attribute values.
			for ( let i = 0; i < defaultAttributes.length; i++ ) {
				await expect( page ).toSelect(
					`select[name="attribute_attr-${ i + 1 }[0]"]`,
					defaultAttributes[ i ]
				);
			}
			await expect( page ).toClick( 'button.save-variation-changes', {
				text: 'Save changes',
			} );

			// Wait for attribute form to save (triggers 2 UI blocks)
			await uiUnblocked();
			await uiUnblocked();

			// Verify that attribute values were saved.
			for ( let i = 0; i < defaultAttributes.length; i++ ) {
				await expect( page ).toMatchElement(
					`select[name="attribute_attr-${
						i + 1
					}[0]"] option[selected]`,
					{
						text: defaultAttributes[ i ],
					}
				);
			}
		} );

		it( 'can manage stock at variation level', async () => {
			// Expand the variation
			await expect( page ).toClick(
				'.toolbar-top .variations-pagenav .expand_all'
			);

			// Enable "Manage stock?" option.
			await setCheckbox( 'input.checkbox.variable_manage_stock' );

			// Input a regular price
			await expect( page ).toFill(
				'input#variable_regular_price_0',
				simpleProductPrice
			);

			// Verify that the "Stock status" dropdown menu disappears.
			await expect( page ).toMatchElement( 'p.variable_stock_status', {
				visible: false,
			} );

			// Fill out the "Stock quantity", "Allow backorders?", and "Low stock threshold" fields.
			await expect( page ).toFill( 'input#variable_stock0', '100' );
			await expect( page ).toSelect(
				'select#variable_backorders0',
				'Allow, but notify customer'
			);
			await expect( page ).toFill(
				'input#variable_low_stock_amount0',
				'10'
			);

			// Save changes.
			await expect( page ).toClick( 'button.save-variation-changes', {
				text: 'Save changes',
			} );
			await uiUnblocked();

			// Verify that field values specific to stock management were saved.
			// Expand the variation
			await expect( page ).toClick(
				'.toolbar-top .variations-pagenav .expand_all'
			);

			// Verify 'Stock quantity'.
			const actualStockQty = await page.$eval(
				'input#variable_stock0',
				( stockQtyInput ) => stockQtyInput.value
			);
			expect( actualStockQty ).toEqual( '100' );

			// Verify 'Allow backorders?'.
			await expect( page ).toMatchElement(
				'select#variable_backorders0 option[selected]',
				'Allow, but notify customer'
			);

			// Verify 'Low stock threshold'.
			const actualLowStockThresh = await page.$eval(
				'input#variable_low_stock_amount0',
				( lowStockThreshInput ) => lowStockThreshInput.value
			);
			expect( actualLowStockThresh ).toEqual( '10' );
		} );

		it( 'can set variation defaults', async () => {
			// Get the product permalink
			const permalink = await page.$eval(
				'#sample-permalink a',
				( a ) => a.href
			);

			// Set default attribute values
			for ( let i = 0; i < defaultAttributes.length; i++ ) {
				await expect( page ).toSelect(
					`select[name="default_attribute_attr-${ i + 1 }"]`,
					defaultAttributes[ i ]
				);
			}

			// Save changes.
			await expect( page ).toClick( 'button.save-variation-changes', {
				text: 'Save changes',
			} );
			await uiUnblocked();

			// Navigate to the product URL
			await page.goto( permalink, { waitUntil: 'networkidle0' } );

			// Verify that default values per attribute were selected.
			for ( let i = 0; i < defaultAttributes.length; i++ ) {
				await expect( page ).toMatchElement(
					`select#attr-${ i + 1 } option[selected]`,
					defaultAttributes[ i ]
				);
			}

			// Navigate back to Product page
			await page.goBack( { waitUntil: 'networkidle0' } );
		} );

		it( 'can remove a variation', async () => {
			// mytodo
		} );
	});
};

module.exports = {
	runAddSimpleProductTest,
	runAddVariableProductTest,
};
