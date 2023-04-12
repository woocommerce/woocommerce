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

async function resetVariableProductTour( baseURL, browser ) {
	// Go to the product page, so that the `window.wp.data` module is available
	const page = await browser.newPage( { baseURL: baseURL } );
	await page.goto( 'wp-admin/post-new.php?post_type=product' );

	// Get the current user's ID and user preferences
	const { id: userId, woocommerce_meta } = await page.evaluate( () => {
		return window.wp.data.select( 'core' ).getCurrentUser();
	} );

	// Reset the variable product tour preference, so that it will be shown again
	const updatedWooCommerceMeta = {
		...woocommerce_meta,
		variable_product_tour_shown: '',
	};

	// Save the updated user preferences
	await page.evaluate(
		async ( { userId, updatedWooCommerceMeta } ) => {
			await window.wp.data.dispatch( 'core' ).saveUser( {
				id: userId,
				woocommerce_meta: updatedWooCommerceMeta,
			} );
		},
		{ userId, updatedWooCommerceMeta }
	);
}

test.describe( 'Add New Variable Product Page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.afterAll( async ( { baseURL, browser } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );

		const varProducts = await api
			.get( 'products', { per_page: 100, search: variableProductName } )
			.then( ( response ) => response.data );

		const manualProducts = await api
			.get( 'products', { per_page: 100, search: manualVariableProduct } )
			.then( ( response ) => response.data );

		const ids = varProducts
			.map( ( { id } ) => id )
			.concat( manualProducts.map( ( { id } ) => id ) );

		await api.post( 'products/batch', { delete: ids } );

		await resetVariableProductTour( baseURL, browser );
	} );

	test( 'shows the variable product tour', async ( { page } ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=product' );
		await page.selectOption( '#product-type', 'variable', { force: true } );

		// because of the way that the tour is dynamically positioned,
		// Playwright can't automatically scroll the button into view,
		// so we will manually scroll the attributes tab into view,
		// which will cause the tour to be scrolled into view as well
		await page
			.locator( '.attribute_tab' )
			.getByRole( 'link', { name: 'Attributes' } )
			.scrollIntoViewIfNeeded();

		// dismiss the variable product tour
		await page
			.getByRole( 'button', { name: 'Got it' } )
			.click( { force: true } );

		// wait for the tour's dismissal to be saved
		await page.waitForResponse(
			( response ) =>
				response.url().includes( '/users/' ) &&
				response.status() === 200
		);
	} );

	test( 'can create product, attributes and variations, edit variations and delete variations', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=product' );
		await page.fill( '#title', variableProductName );
		await page.selectOption( '#product-type', 'variable', { force: true } );

		await page.click( 'a[href="#product_attributes"]' );

		// add 3 attributes
		for ( let i = 0; i < 3; i++ ) {
			if ( i > 0 ) {
				await page.click( 'button.add_attribute' );
			}
			await page.waitForSelector(
				`input[name="attribute_names[${ i }]"]`
			);

			await page
				.locator( `input[name="attribute_names[${ i }]"]` )
				.first()
				.type( `attr #${ i + 1 }` );
			await page
				.locator( `textarea[name="attribute_values[${ i }]"]` )
				.first()
				.type( 'val1 | val2' );
		}
		await page.click( 'text=Save attributes' );
		// wait for the attributes to be saved
		await page.waitForResponse(
			( response ) =>
				response.url().includes( '/post.php?post=' ) &&
				response.status() === 200
		);

		// Save before going to the Variations tab to prevent variations from all attributes to be automatically created
		await page.locator( '#save-post' ).click();
		await expect(
			page.getByText( 'Product draft updated. ' )
		).toBeVisible();

		page.on( 'dialog', ( dialog ) => dialog.accept() );

		// manually create variations from all attributes
		await page.click( 'a[href="#variable_product_options"]' );

		await page.click( 'button.generate_variations' );

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
		await expect( page.locator( '#message.notice-success' ) ).toContainText(
			'Product draft updated.'
		);

		// set variation attributes and bulk edit variations
		await page.click( 'a[href="#variable_product_options"]' );

		// set the variation attributes
		await page.click(
			'#variable_product_options .toolbar-top a.expand_all'
		);
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
		await page.keyboard.press( 'ArrowUp' );
		await page.click( 'button.save-variation-changes' );

		// bulk-edit variations
		await page.click(
			'#variable_product_options .toolbar-top a.expand_all'
		);
		for ( let i = 0; i < 4; i++ ) {
			const checkBox = page.locator(
				`input[name="variable_is_downloadable[${ i }]"]`
			);
			await expect( checkBox ).not.toBeChecked();
		}
		await page.selectOption( '#field_to_edit', 'toggle_downloadable', {
			force: true,
		} );
		await page.click(
			'#variable_product_options .toolbar-top a.expand_all'
		);
		for ( let i = 0; i < 4; i++ ) {
			const checkBox = await page.locator(
				`input[name="variable_is_downloadable[${ i }]"]`
			);
			await expect( checkBox ).toBeChecked();
		}

		await page.locator( '#save-post' ).click();

		// delete all variations
		await page.click( 'a[href="#variable_product_options"]' );
		await page.waitForLoadState( 'networkidle' );
		await page.selectOption( '#field_to_edit', 'delete_all' );
		await page.waitForSelector( '.woocommerce_variation', {
			state: 'detached',
		} );
		const variationsCount = await page.$$( '.woocommerce_variation' );
		await expect( variationsCount ).toHaveLength( 0 );
	} );

	test( 'can manually add a variation, manage stock levels, set variation defaults and remove a variation', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=product' );
		await page.fill( '#title', manualVariableProduct );
		await page.selectOption( '#product-type', 'variable', { force: true } );
		await page.click( 'a[href="#product_attributes"]' );
		// add 3 attributes
		for ( let i = 0; i < 3; i++ ) {
			if ( i > 0 ) {
				await page.click( 'button.add_attribute' );
			}
			await page.waitForSelector(
				`input[name="attribute_names[${ i }]"]`
			);

			await page
				.locator( `input[name="attribute_names[${ i }]"]` )
				.first()
				.type( `attr #${ i + 1 }` );
			await page
				.locator( `textarea[name="attribute_values[${ i }]"]` )
				.first()
				.type( 'val1 | val2' );
		}
		await page.click( 'text=Save attributes' );
		// wait for the attributes to be saved
		await page.waitForResponse(
			( response ) =>
				response.url().includes( '/post.php?post=' ) &&
				response.status() === 200
		);

		// Save before going to the Variations tab to prevent variations from all attributes to be automatically created
		await page.locator( '#save-post' ).click();
		await expect(
			page.getByText( 'Product draft updated. ' )
		).toBeVisible();
		await page.click( '.updated.notice .notice-dismiss' );

		// manually adds a variation
		await page.click( 'a[href="#variable_product_options"]' );
		await page.click( 'button.add_variation_manually' );
		await expect( page.locator( '.variation-needs-update' ) ).toBeVisible();
		for ( let i = 0; i < defaultAttributes.length; i++ ) {
			await page.selectOption(
				`.variation-needs-update h3 select >> nth=${ i }`,
				defaultAttributes[ i ]
			);
		}
		await page.click( 'button.save-variation-changes' );
		for ( let i = 0; i < defaultAttributes.length; i++ ) {
			await expect(
				page
					.locator( '.woocommerce_variation' )
					.first()
					.locator( 'select' )
					.nth( i )
					.locator( 'option[selected]' )
			).toHaveText( defaultAttributes[ i ] );
		}

		await page.locator( '#save-post' ).click();
		await expect(
			page.getByText( 'Product draft updated. ' )
		).toBeVisible();

		// manage stock at variation level
		await page.click( 'a[href="#variable_product_options"]' );
		await page.waitForLoadState( 'networkidle' );
		await page.click(
			'#variable_product_options .toolbar-top a.expand_all'
		);
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

		// remove a variation
		page.on( 'dialog', ( dialog ) => dialog.accept() );
		await page.hover( '.woocommerce_variation' );
		await page.click( '.remove_variation.delete' );
		await expect( page.locator( '.woocommerce_variation' ) ).toHaveCount(
			0
		);
	} );
} );
