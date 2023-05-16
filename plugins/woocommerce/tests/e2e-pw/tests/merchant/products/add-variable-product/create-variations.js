const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const defaultAttributes = [ 'val2', 'val1', 'val2' ];
const stockAmount = '100';
const lowStockAmount = '10';

let productId_indivEdit, fixedVariationIds;

test.describe( 'Update variations', () => {
	

	test( 'can bulk edit variations', async ( { page } ) => {
		await test.step( 'Go to the "Edit product" page.', async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ productId_indivEdit }&action=edit`
			);
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page.click( 'a[href="#variable_product_options"]' );
		} );

		await test.step(
			'Select the \'Toggle "Downloadable"\' bulk action.',
			async () => {
				await page.selectOption(
					'#field_to_edit',
					'toggle_downloadable'
				);
			}
		);

		await test.step( 'Expand all variations.', async () => {
			await page.click(
				'#variable_product_options .toolbar-top a.expand_all'
			);
		} );

		await test.step(
			'Expect all "Downloadable" checkboxes to be checked.',
			async () => {
				const checkBoxes = page.locator(
					'input[name^="variable_is_downloadable"]'
				);
				const count = await checkBoxes.count();

				for ( let i = 0; i < count; i++ ) {
					await expect( checkBoxes.nth( i ) ).toBeChecked();
				}
			}
		);
	} );

	test( 'can delete all variations', async ( { page } ) => {
		await test.step( 'Go to the "Edit product" page.', async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ productId_indivEdit }&action=edit`
			);
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page.click( 'a[href="#variable_product_options"]' );
		} );

		await test.step(
			'Select the bulk action "Delete all variations".',
			async () => {
				page.on( 'dialog', ( dialog ) => dialog.accept() );
				await page.selectOption( '#field_to_edit', 'delete_all' );
			}
		);

		await test.step(
			'Expect that there are no more variations.',
			async () => {
				await expect(
					page.locator( '.woocommerce_variation' )
				).toHaveCount( 0 );
			}
		);
	} );

	test( 'can manage stock levels, set variation defaults and remove a variation', async ( {
		page,
	} ) => {
		// manage stock at variation level
		await page.click( 'a[href="#variable_product_options"]' );
		await page.waitForLoadState( 'networkidle' );
		await page.click(
			'#variable_product_options .toolbar-top a.expand_all'
		);
		await page.check( 'input.checkbox.variable_manage_stock' );

		const firstVariationContainer = await page
			.locator( '.woocommerce_variations  .woocommerce_variation' )
			.first();

		await firstVariationContainer
			.getByPlaceholder( 'Variation price (required)' )
			.fill( variationOnePrice );
		await expect(
			page.locator( 'p.variable_stock_status' )
		).not.toBeVisible();
		await firstVariationContainer
			.getByLabel( 'Stock quantity' )
			.nth( 1 )
			.fill( stockAmount );
		await page.selectOption( '#variable_backorders0', 'notify', {
			force: true,
		} );
		await firstVariationContainer
			.getByPlaceholder( 'Store-wide threshold (2)' )
			.fill( lowStockAmount );
		await page.click( 'button.save-variation-changes' );
		await page.click(
			'#variable_product_options .toolbar-top a.expand_all'
		);
		await expect( page.locator( '#variable_stock0' ) ).toHaveValue(
			stockAmount
		);
		await expect(
			page.locator( '#variable_low_stock_amount0' )
		).toHaveValue( lowStockAmount );
		await expect(
			page.locator( '#variable_backorders0 > option[selected]' )
		).toHaveText( 'Allow, but notify customer' );

		// set variation defaults
		await page.click( 'a[href="#variable_product_options"]' );

		// set variation defaults
		for ( let i = 0; i < defaultAttributes.length; i++ ) {
			await page.selectOption(
				`select[name="default_attribute_attr-${ i + 1 }"]`,
				defaultAttributes[ i ]
			);
		}
		await page.click( 'button.save-variation-changes' );
		await page.waitForSelector( 'input#variable_low_stock_amount0', {
			state: 'hidden',
		} );
		for ( let i = 0; i < defaultAttributes.length; i++ ) {
			await expect(
				page.locator(
					`select[name="default_attribute_attr-${ i + 1 }"]`
				)
			).toHaveValue( `${ defaultAttributes[ i ] }` );
		}

		// remove a variation
		await page.click( 'a[href="#variable_product_options"]' );

		page.on( 'dialog', ( dialog ) => dialog.accept() );
		await page.hover( '.woocommerce_variation' );
		await page.click( '.remove_variation.delete' );
		await expect( page.locator( '.woocommerce_variation' ) ).toHaveCount(
			0
		);
	} );
} );
