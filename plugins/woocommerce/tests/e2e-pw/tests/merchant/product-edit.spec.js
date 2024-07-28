const { test: baseTest, expect } = require( '../../fixtures/fixtures' );

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
	products: async ( { api }, use ) => {
		const products = [];

		// Create two simple products for testing
		for ( let i = 0; i < 2; i++ ) {
			await api
				.post( 'products', {
					id: 0,
					name: `Product ${ i }_${ Date.now() }`,
					type: 'simple',
					regular_price: `${ 12.99 + i }`,
					manage_stock: true,
					stock_quantity: 10 + i,
					stock_status: 'instock',
				} )
				.then( ( response ) => {
					products.push( response.data );
				} );
		}

		await use( products );

		// Cleanup
		for ( const product of products ) {
			await api.delete( `products/${ product.id }`, { force: true } );
		}
	},
} );

test.describe(
	'Product Editing',
	{ tag: [ '@gutenberg', '@services' ] },
	() => {
		test( 'can edit a product and save the changes', async ( {
			page,
			products,
		} ) => {
			await page.goto(
				`wp-admin/post.php?post=${ products[ 0 ].id }&action=edit`
			);

			const newProduct = {
				name: `Product ${ Date.now() }`,
				description: `This product is pretty awesome ${ Date.now() }`,
				regularPrice: '100.05',
				salePrice: '99.05',
			};

			// Edit product name
			await page.getByLabel( 'Product name' ).fill( newProduct.name );

			// Switch to text mode and edit product description
			await page.locator( '#content-html' ).click();
			await page
				.locator( '.wp-editor-area' )
				.first()
				.fill( newProduct.description );

			// Edit product prices
			await page
				.getByLabel( 'Regular price ($)' )
				.fill( newProduct.regularPrice );
			await page
				.getByLabel( 'Sale price ($)' )
				.fill( newProduct.salePrice );

			// Publish the updated product
			await page.getByRole( 'button', { name: 'Update' } ).click();

			// Verify the changes
			await expect( page.getByLabel( 'Product name' ) ).toHaveValue(
				newProduct.name
			);
			await expect(
				page.locator( '.wp-editor-area' ).first()
			).toContainText( newProduct.description );
			await expect( page.getByLabel( 'Regular price ($)' ) ).toHaveValue(
				newProduct.regularPrice
			);
			await expect( page.getByLabel( 'Sale price ($)' ) ).toHaveValue(
				newProduct.salePrice
			);
		} );

		test( 'can bulk edit product prices', async ( { page, products } ) => {
			await page.goto( `wp-admin/edit.php?post_type=product` );

			const regularPriceIncrease = 10;
			const salePriceDecrease = 10;

			// Select all products for bulk editing
			for ( const product of products ) {
				await page.getByLabel( `Select ${ product.name }` ).click();
			}

			// Initiate bulk edit
			await page
				.locator( '#bulk-action-selector-top' )
				.selectOption( 'Edit' );
			await page.locator( '#doaction' ).click();

			await expect(
				await page.locator( '#bulk-titles-list li' ).count()
			).toEqual( products.length );

			// Update regular price by a percentage
			await page
				.locator( 'select[name="change_regular_price"]' )
				.selectOption(
					'Increase existing price by (fixed amount or %):'
				);
			await page
				.getByPlaceholder( 'Enter price ($)' )
				.fill( `${ regularPriceIncrease }%` );

			// Update sale price to a percentage of the regular price
			await page
				.locator( 'select[name="change_sale_price"]' )
				.selectOption(
					'Set to regular price decreased by (fixed amount or %):'
				);
			await page
				.getByPlaceholder( 'Enter sale price ($)' )
				.fill( `${ salePriceDecrease }%` );

			// Save the updates
			await page.getByRole( 'button', { name: 'Update' } ).click();

			// Verify the price updates on the product pages
			for ( const product of products ) {
				await page.goto( `product/${ product.slug }` );

				const expectedRegularPrice = (
					product.regular_price *
					( 1 + regularPriceIncrease / 100 )
				).toFixed( 2 );
				const expectedSalePrice = (
					expectedRegularPrice *
					( 1 - salePriceDecrease / 100 )
				).toFixed( 2 );

				await expect
					.soft(
						await page
							.locator( 'del' )
							.getByText( `$${ expectedRegularPrice }` )
							.count()
					)
					.toBeGreaterThan( 0 );
				await expect
					.soft(
						await page
							.locator( 'ins' )
							.getByText( `$${ expectedSalePrice }` )
							.count()
					)
					.toBeGreaterThan( 0 );
			}
		} );

		test('can bulk edit product stock', async ({ page, products }) => {
			await page.goto(`wp-admin/edit.php?post_type=product`);
		  
			const stockQtyIncrease = 10;
		  
			// Select product for bulk editing
			for (const product of products) {
			  await page.getByLabel(`Select ${product.name}`).click();
			}
		  
			// Click on bulk edit
			await page.locator('#bulk-action-selector-top').selectOption('Edit');
			await page.locator('#doaction').click();
		  
			await expect(await page.locator('#bulk-titles-list li').count()).toEqual(products.length);
		  
			// Update stock quantity
			await page.locator('select[name="change_stock"]').selectOption('Increase existing stock by:');
			await page.getByPlaceholder('Stock qty').fill(`${stockQtyIncrease}`);
		  
			// Enable stock management in bulk edit
			await page.locator('select[name="_manage_stock"]').selectOption({ label: 'Yes' });
		  
			// Update stock status to 'In stock'
			await page.locator('select[name="_stock_status"]').first().selectOption('instock');
		  
			// Save the updates
			await page.getByRole('button', { name: 'Update' }).click();
		  
			// Verify the stock quantity and status updates on the product pages
			for (const product of products) {
			  await page.goto(`product/${product.slug}`);
		  
			  const expectedStockQty = product.stock_quantity + stockQtyIncrease;
		  
			  // Verify updated stock quantity
			  await expect.soft(page.getByText(`${expectedStockQty} in stock`)).toBeVisible();
			}
		  });						  

		test( 'can bulk edit product dimensions', async ( {
			page,
			products,
		} ) => {
			await page.goto( `wp-admin/edit.php?post_type=product` );

			const weightDimension = 3;
			const lengthDimension = 20;
			const widthDimension = 20;
			const heightDimension = 6;

			// Select all products for bulk editing
			for ( const product of products ) {
				await page.getByLabel( `Select ${ product.name }` ).click();
			}

			// Initiate bulk edit
			await page
				.locator( '#bulk-action-selector-top' )
				.selectOption( 'Edit' );
			await page.locator( '#doaction' ).click();

			await expect(
				await page.locator( '#bulk-titles-list li' ).count()
			).toEqual( products.length );

			// Update weight
			await page
				.locator( 'select[name="change_weight"]' )
				.selectOption( 'Change to:' );
			await page
				.getByPlaceholder( '0 (kg)' )
				.fill( `${ weightDimension }` );

			// Update dimensions
			await page
				.locator( 'select[name="change_dimensions"]' )
				.selectOption( 'Change to:' );
			await page
				.getByPlaceholder( 'Length (cm)' )
				.fill( `${ lengthDimension }` );
			await page
				.getByPlaceholder( 'Width (cm)' )
				.fill( `${ widthDimension }` );
			await page
				.getByPlaceholder( 'Height (cm)' )
				.fill( `${ heightDimension }` );

			// Save the updates
			await page.getByRole( 'button', { name: 'Update' } ).click();

			// Verify the weight and dimensions updates on the product pages
			for ( const product of products ) {
				await page.goto( `product/${ product.slug }` );

				await expect(
					page
						.locator(
							'tr.woocommerce-product-attributes-item--weight td'
						)
						.first()
				).toContainText( `${ weightDimension } kg` );

				await expect(
					page.locator(
						'tr.woocommerce-product-attributes-item--dimensions td.woocommerce-product-attributes-item__value'
					)
				).toContainText(
					`${ lengthDimension } × ${ widthDimension } × ${ heightDimension } cm`
				);
			}
		} );
	}
);
