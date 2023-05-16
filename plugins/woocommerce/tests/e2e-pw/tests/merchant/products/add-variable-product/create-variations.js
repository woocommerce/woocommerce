const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const defaultAttributes = [ 'val2', 'val1', 'val2' ];

let productId_bulkEdit, fixedVariationIds;

test.describe( 'Update variations', () => {
	test( 'can manage stock levels, set variation defaults and remove a variation', async ( {
		page,
	} ) => {
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
