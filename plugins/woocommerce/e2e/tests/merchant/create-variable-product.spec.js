const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const variableProductName = 'Variable Product with Three Variations';
const manualVariableProduct = 'Manual Variable Product';
const variationOnePrice = '9.99';
const variationTwoPrice = '11.99';
const variationThreePrice = '20.00';
const productWeight = '200';
const productLength = '10';
const productWidth = '20';
const productHeight = '15';
const defaultAttributes = [ 'val2', 'val1', 'val2' ];
const stockAmount = '100';
const lowStockAmount = '10';

test.describe( 'Add New Variable Product Page', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// delete products
		await api.get( 'products' ).then( ( response ) => {
			const products = response.data;
			for ( const product of products ) {
				if (
					product.name === variableProductName ||
					product.name === manualVariableProduct
				) {
					api.delete( `products/${ product.id }`, {
						force: true,
					} ).then( () => {
						// nothing to do here.
					} );
				}
			}
		} );
	} );

	// tests build upon one another, so running one in the middle will fail.
	test( 'can create product, attributes and variations', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=product' );
		await page.fill( '#title', variableProductName );
		await page.selectOption( '#product-type', 'variable', { force: true } );

		await page.click( 'a[href="#product_attributes"]' );

		// add 3 attributes
		for ( let i = 0; i < 3; i++ ) {
			await page.click( 'button.add_attribute' );
			await page.fill(
				`input[name="attribute_names[${ i }]"]`,
				`attr #${ i + 1 }`
			);
			await page.fill(
				`textarea[name="attribute_values[${ i }]"]`,
				'val1 | val2'
			);
			await page.click( `input[name="attribute_variation[${ i }]"]` );
		}
		await page.click( 'text=Save attributes' );

		// create variations from all attributes
		await page.click( 'a[href="#variable_product_options"]' );
		await page.selectOption( '#field_to_edit', 'link_all_variations', {
			force: true,
		} );
		page.on( 'dialog', ( dialog ) => dialog.accept() );
		await page.click( 'a.do_variation_action' );

		await page.waitForLoadState( 'networkidle' );

		// add variation attributes
		for ( let i = 0; i < 8; i++ ) {
			const val1 = 'val1';
			const val2 = 'val2';
			const attr3 = !! ( i % 2 ); // 0-1,4-5 / 2-3,6-7
			const attr2 = i % 4 > 1; // 0-3 / 4-7
			const attr1 = i > 3;
			await expect(
				page.locator( `select[name="attribute_attr-1[${ i }]"]` )
			).toHaveValue( attr1 ? val2 : val1 );
			await expect(
				page.locator( `select[name="attribute_attr-2[${ i }]"]` )
			).toHaveValue( attr2 ? val2 : val1 );
			await expect(
				page.locator( `select[name="attribute_attr-3[${ i }]"]` )
			).toHaveValue( attr3 ? val2 : val1 );
		}

		await page.locator( '#save-post' ).click();
	} );

	test( 'can set the variation attributes, bulk edit variations', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/edit.php?post_type=product' );
		await page.locator( `a:has-text("${ variableProductName }")` ).click();
		await page.click( 'a[href="#variable_product_options"]' );

		// set the variation attributes
		await page.click( 'div.variations-pagenav > span > a.expand_all' );
		await page.check( 'input[name="variable_is_virtual[0]"]' );
		await page.fill(
			'input[name="variable_regular_price[0]"]',
			variationOnePrice
		);
		await page.check( 'input[name="variable_is_virtual[1]"]' );
		await page.fill(
			'input[name="variable_regular_price[1]"]',
			variationTwoPrice
		);
		await page.check( 'input[name="variable_manage_stock[2]"]' );
		await page.fill(
			'input[name="variable_regular_price[2]"]',
			variationThreePrice
		);
		await page.fill( 'input[name="variable_weight[2]"]', productWeight );
		await page.fill( 'input[name="variable_length[2]"]', productLength );
		await page.fill( 'input[name="variable_width[2]"]', productWidth );
		await page.fill( 'input[name="variable_height[2]"]', productHeight );
		await page.click( 'button.save-variation-changes' );

		// bulk-edit variations
		await page.click( 'div.variations-pagenav > span > a.expand_all' );
		for ( let i = 0; i < 8; i++ ) {
			const checkBox = page.locator(
				`input[name="variable_is_downloadable[${ i }]"]`
			);
			await expect( checkBox ).not.toBeChecked();
		}
		await page.selectOption( '#field_to_edit', 'toggle_downloadable', {
			force: true,
		} );
		await page.click( 'a.do_variation_action' );
		await page.click( 'div.variations-pagenav > span > a.expand_all' );
		for ( let i = 0; i < 8; i++ ) {
			const checkBox = page.locator(
				`input[name="variable_is_downloadable[${ i }]"]`
			);
			await expect( checkBox ).toBeChecked();
		}

		await page.locator( '#save-post' ).click();
	} );

	test( 'can delete all variations', async ( { page } ) => {
		await page.goto( 'wp-admin/edit.php?post_type=product' );
		await page.locator( `a:has-text("${ variableProductName }")` ).click();
		await page.click( 'a[href="#variable_product_options"]' );
		page.on( 'dialog', ( dialog ) => dialog.accept() );
		await Promise.all( [
			page.waitForResponse(
				( resp ) =>
					resp.url().includes( 'admin-ajax.php' ) &&
					resp.status() === 200
			),
			page.selectOption( '#field_to_edit', 'delete_all', {
				force: true,
			} ),
		] );

		// delete all variations
		await page.click( 'a.do_variation_action' );
		await page.waitForSelector( '.woocommerce_variation', {
			state: 'detached',
		} );
		const variationsCount = await page.$$( '.woocommerce_variation' );
		await expect( variationsCount ).toHaveLength( 0 );
	} );

	test( 'can manually add a variation', async ( { page } ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=product' );
		await page.fill( '#title', manualVariableProduct );
		await page.selectOption( '#product-type', 'variable', { force: true } );
		await page.click( 'a[href="#product_attributes"]' );
		// add 3 attributes
		for ( let i = 0; i < 3; i++ ) {
			await page.click( 'button.add_attribute' );
			await page.fill(
				`input[name="attribute_names[${ i }]"]`,
				`attr #${ i + 1 }`
			);
			await page.fill(
				`textarea[name="attribute_values[${ i }]"]`,
				'val1 | val2'
			);
			await page.click( `input[name="attribute_variation[${ i }]"]` );
		}
		await page.click( 'text=Save attributes' );
		await page.click( 'a[href="#variable_product_options"]' );

		// manually adds a variation
		await page.selectOption( '#field_to_edit', 'add_variation', {
			force: true,
		} );
		await page.click( 'a.do_variation_action' );
		for ( let i = 0; i < defaultAttributes.length; i++ ) {
			await page.selectOption(
				`select[name="attribute_attr-${ i + 1 }[0]"]`,
				defaultAttributes[ i ]
			);
		}
		await page.click( 'button.save-variation-changes' );
		for ( let i = 0; i < defaultAttributes.length; i++ ) {
			await expect(
				page.locator(
					`select[name="attribute_attr-${
						i + 1
					}[0]"] > option[selected]`
				)
			).toHaveText( defaultAttributes[ i ] );
		}

		await page.locator( '#save-post' ).click();
	} );

	test( 'can manage stock at variation level', async ( { page } ) => {
		await page.goto( 'wp-admin/edit.php?post_type=product' );
		await page
			.locator( `a:has-text("${ manualVariableProduct }")` )
			.click();
		await page.click( 'a[href="#variable_product_options"]' );

		// manage stock at variation level
		await page.click( 'div.variations-pagenav > span > a.expand_all' );
		await page.check( 'input.checkbox.variable_manage_stock' );
		await page.fill( 'input#variable_regular_price_0', variationOnePrice );
		await expect(
			page.locator( 'p.variable_stock_status' )
		).not.toBeVisible();
		await page.fill( 'input#variable_stock0', stockAmount );
		await page.selectOption( '#variable_backorders0', 'notify', {
			force: true,
		} );
		await page.fill( 'input#variable_low_stock_amount0', lowStockAmount );
		await page.click( 'button.save-variation-changes' );
		await page.click( 'div.variations-pagenav > span > a.expand_all' );
		await expect( page.locator( '#variable_stock0' ) ).toHaveValue(
			stockAmount
		);
		await expect(
			page.locator( '#variable_low_stock_amount0' )
		).toHaveValue( lowStockAmount );
		await expect(
			page.locator( '#variable_backorders0 > option[selected]' )
		).toHaveText( 'Allow, but notify customer' );
	} );

	test( 'can set variation defaults', async ( { page } ) => {
		await page.goto( 'wp-admin/edit.php?post_type=product' );
		await page
			.locator( `a:has-text("${ manualVariableProduct }")` )
			.click();
		await page.click( 'a[href="#variable_product_options"]' );

		// set variation defaults
		for ( let i = 0; i < defaultAttributes.length; i++ ) {
			await page.selectOption(
				`select[name="default_attribute_attr-${ i + 1 }"]`,
				defaultAttributes[ i ]
			);
		}
		await page.click( 'button.save-variation-changes', { force: true } );
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
	} );

	test( 'can remove a variation', async ( { page } ) => {
		await page.goto( 'wp-admin/edit.php?post_type=product' );
		await page
			.locator( `a:has-text("${ manualVariableProduct }")` )
			.click();
		await page.click( 'a[href="#variable_product_options"]' );

		// remove a variation
		page.on( 'dialog', ( dialog ) => dialog.accept() );
		await page.click( '.remove_variation.delete', { force: true } );
		await page.click( '.remove_variation.delete' ); // have to do this twice to get the link to appear
		await expect( page.locator( '.woocommerce_variation' ) ).toHaveCount(
			0
		);
	} );
} );
