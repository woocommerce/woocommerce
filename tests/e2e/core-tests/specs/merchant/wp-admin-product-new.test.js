/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	merchant,
	clickTab,
	uiUnblocked,
	evalAndClick,
	setCheckbox,
} = require( '@woocommerce/e2e-utils' );
const {
	waitAndClick,
	waitForSelector,
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

const simpleProductName = config.get( 'products.simple.name' );
const simpleProductPrice = config.has('products.simple.price') ? config.get('products.simple.price') : '9.99';

const verifyPublishAndTrash = async () => {
	// Wait for auto save
	await page.waitFor( 2000 );

	// Publish product
	await expect( page ).toClick( '#publish' );
	await page.waitForSelector( '.updated.notice', { text: 'Product published.' } );

	// Verify
	await expect( page ).toMatchElement( '.updated.notice', { text: 'Product published.' } );
	await page.waitForSelector( 'a', { text: 'Move to Trash' } );

	// Trash product
	await expect( page ).toClick( 'a', { text: 'Move to Trash' } );
	await page.waitForSelector( '.updated.notice', { text: '1 product moved to the Trash.' } );

	// Verify
	await expect( page ).toMatchElement( '.updated.notice', { text: '1 product moved to the Trash.' } );
};

const openNewProductAndVerify = async () => {
	// Go to "add product" page
	await merchant.openNewProduct();

	// Make sure we're on the add product page
	await expect(page.title()).resolves.toMatch('Add new product');
}

const runAddSimpleProductTest = () => {
	describe('Add New Simple Product Page', () => {
		beforeAll(async () => {
			await merchant.login();
		});

		it('can create simple virtual product titled "Simple Product" with regular price $9.99', async () => {
			await openNewProductAndVerify();

			// Set product data
			await expect(page).toFill('#title', simpleProductName);
			await expect(page).toClick('#_virtual');
			await clickTab('General');
			await expect(page).toFill('#_regular_price', simpleProductPrice);

			// Publish product, verify that it was published. Trash product, verify that it was trashed.
			await verifyPublishAndTrash(
				'#publish',
				'.updated.notice',
				'Product published.',
				'Move to Trash',
				'1 product moved to the Trash.'
			);
		});
	});
};

const runAddVariableProductTest = () => {
	describe('Add New Variable Product Page', () => {
		it('can create product with variations', async () => {
			await openNewProductAndVerify();

			// Set product data
			await expect(page).toFill('#title', 'Variable Product with Three Variations');
			await expect(page).toSelect('#product-type', 'Variable product');

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

			await waitAndClick( page, '.variations_tab a' );
			await waitForSelector(
				page,
				'select[name="attribute_attr-1[0]"]',
				{
					visible: true,
					timeout: 5000
				}
			);

			// Verify that variations were created
			await Promise.all([
				expect(page).toMatchElement('select[name="attribute_attr-1[0]"]', {text: 'val1'}),
				expect(page).toMatchElement('select[name="attribute_attr-2[0]"]', {text: 'val1'}),
				expect(page).toMatchElement('select[name="attribute_attr-3[0]"]', {text: 'val1'}),

				expect(page).toMatchElement('select[name="attribute_attr-1[1]"]', {text: 'val1'}),
				expect(page).toMatchElement('select[name="attribute_attr-2[1]"]', {text: 'val1'}),
				expect(page).toMatchElement('select[name="attribute_attr-3[1]"]', {text: 'val2'}),

				expect(page).toMatchElement('select[name="attribute_attr-1[2]"]', {text: 'val1'}),
				expect(page).toMatchElement('select[name="attribute_attr-2[2]"]', {text: 'val2'}),
				expect(page).toMatchElement('select[name="attribute_attr-3[2]"]', {text: 'val1'}),

				expect(page).toMatchElement('select[name="attribute_attr-1[3]"]', {text: 'val1'}),
				expect(page).toMatchElement('select[name="attribute_attr-2[3]"]', {text: 'val2'}),
				expect(page).toMatchElement('select[name="attribute_attr-3[3]"]', {text: 'val2'}),

				expect(page).toMatchElement('select[name="attribute_attr-1[4]"]', {text: 'val2'}),
				expect(page).toMatchElement('select[name="attribute_attr-2[4]"]', {text: 'val1'}),
				expect(page).toMatchElement('select[name="attribute_attr-3[4]"]', {text: 'val1'}),

				expect(page).toMatchElement('select[name="attribute_attr-1[5]"]', {text: 'val2'}),
				expect(page).toMatchElement('select[name="attribute_attr-2[5]"]', {text: 'val1'}),
				expect(page).toMatchElement('select[name="attribute_attr-3[5]"]', {text: 'val2'}),

				expect(page).toMatchElement('select[name="attribute_attr-1[6]"]', {text: 'val2'}),
				expect(page).toMatchElement('select[name="attribute_attr-2[6]"]', {text: 'val2'}),
				expect(page).toMatchElement('select[name="attribute_attr-3[6]"]', {text: 'val1'}),

				expect(page).toMatchElement('select[name="attribute_attr-1[7]"]', {text: 'val2'}),
				expect(page).toMatchElement('select[name="attribute_attr-2[7]"]', {text: 'val2'}),
				expect(page).toMatchElement('select[name="attribute_attr-3[7]"]', {text: 'val2'}),
			]);

			/*
			Puppeteer seems unable to find the individual variation fields in headless mode on MacOS
			This section of the test runs fine in both Travis and non-headless mode on Mac
			Disabling temporarily to allow the test to be re-enabled without local testing headache
			await waitAndClick( page, '.variations-pagenav .expand_all');
			await page.waitFor( 2000 );
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
			/**/

			// Publish product, verify that it was published. Trash product, verify that it was trashed.
			await verifyPublishAndTrash(
				'#publish',
				'.notice',
				'Product published.',
				'1 product moved to the Trash.'
			);
		});
	});
};

module.exports = {
	runAddSimpleProductTest,
	runAddVariableProductTest,
};
