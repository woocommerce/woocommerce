/**
 * External dependencies
 */
const { faker } = require( '@faker-js/faker' );

/**
 * Internal dependencies
 */
import { test as baseTest, expect, tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { WC_API_PATH } from '../../utils/api-client';
import { getFakeProduct } from '../../utils/data';

async function saveProductChanges( page ) {
	await page
		.locator( '#publishing-action' )
		.getByRole( 'button', { name: 'Update' } )
		.click();
	await expect(
		page
			.locator( 'div.notice-success > p' )
			.filter( { hasText: 'Product updated' } )
	).toBeVisible();
}

async function saveBulkProductChanges( page ) {
	await page.getByRole( 'button', { name: 'Update' } ).click();
	await expect(
		page
			.locator( 'div.notice > p' )
			.filter( { hasText: /(\d+) product(s)? updated/ } )
	).toBeVisible();
}

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	products: async ( { restApi }, use ) => {
		const products = [];

		for ( let i = 0; i < 2; i++ ) {
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					id: 0,
					name: faker.commerce.productName(),
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

		for ( const product of products ) {
			await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
				force: true,
			} );
		}
	},
} );

test( 'can edit a product and save the changes', async ( {
	page,
	products,
} ) => {
	await page.goto(
		`wp-admin/post.php?post=${ products[ 0 ].id }&action=edit`
	);

	const newProduct = getFakeProduct();
	newProduct.sale_price = newProduct.regular_price - 1;

	await test.step( 'edit the product name', async () => {
		await page.getByLabel( 'Product name' ).fill( newProduct.name );
	} );

	await test.step( 'edit the product description', async () => {
		await page.locator( '#content-html' ).click(); // text mode to work around iframe
		await page
			.locator( '.wp-editor-area' )
			.first()
			.fill( newProduct.description );
	} );

	await test.step( 'edit the product price', async () => {
		await page
			.getByLabel( 'Regular price ($)' )
			.fill( newProduct.regular_price.toString() );
		await page
			.getByLabel( 'Sale price ($)' )
			.fill( newProduct.sale_price.toString() );
	} );

	await test.step( 'publish the updated product', async () => {
		await saveProductChanges( page );
	} );

	await test.step( 'verify the changes', async () => {
		await expect( page.getByLabel( 'Product name' ) ).toHaveValue(
			newProduct.name
		);
		await expect( page.locator( '.wp-editor-area' ).first() ).toContainText(
			newProduct.description
		);
		await expect( page.getByLabel( 'Regular price ($)' ) ).toHaveValue(
			newProduct.regular_price.toString()
		);
		await expect( page.getByLabel( 'Sale price ($)' ) ).toHaveValue(
			newProduct.sale_price.toString()
		);
	} );
} );

test( 'can bulk edit products', async ( { page, products } ) => {
	await page.goto( `wp-admin/edit.php?post_type=product` );

	const regularPriceIncrease = 10;
	const salePriceDecrease = 10;
	const stockQtyIncrease = 10;

	await test.step( 'select and bulk edit the products', async () => {
		for ( const product of products ) {
			await page.getByLabel( `Select ${ product.name }` ).click();
		}

		await page
			.locator( '#bulk-action-selector-top' )
			.selectOption( 'Edit' );
		await page.locator( '#doaction' ).click();

		await expect(
			await page.locator( '#bulk-titles-list li' ).count()
		).toEqual( products.length );
	} );

	await test.step( 'update the regular price', async () => {
		await page
			.locator( 'select[name="change_regular_price"]' )
			.selectOption( 'Increase existing price by (fixed amount or %):' );
		await page
			.getByPlaceholder( 'Enter price ($)' )
			.fill( `${ regularPriceIncrease }%` );
	} );

	await test.step( 'update the sale price', async () => {
		await page
			.locator( 'select[name="change_sale_price"]' )
			.selectOption(
				'Set to regular price decreased by (fixed amount or %):'
			);
		await page
			.getByPlaceholder( 'Enter sale price ($)' )
			.fill( `${ salePriceDecrease }%` );
	} );

	await test.step( 'update the stock quantity', async () => {
		await page
			.locator( 'select[name="change_stock"]' )
			.selectOption( 'Increase existing stock by:' );
		await page
			.getByPlaceholder( 'Stock qty' )
			.fill( `${ stockQtyIncrease }` );
	} );

	await test.step( 'save the updates', async () => {
		await saveBulkProductChanges( page );
	} );

	await test.step( 'verify the changes', async () => {
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
			const expectedStockQty = product.stock_quantity + stockQtyIncrease;

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
			await expect
				.soft( page.getByText( `${ expectedStockQty } in stock` ) )
				.toBeVisible();
		}
	} );
} );

test(
	'can restore regular price when bulk editing products',
	{ tag: [ tags.SERVICES ] },
	async ( { page, products } ) => {
		await page.goto( `wp-admin/edit.php?post_type=product` );

		const salePriceDecrease = 10;

		await test.step( 'select and bulk edit the products', async () => {
			for ( const product of products ) {
				await page.getByLabel( `Select ${ product.name }` ).click();
			}

			await page
				.locator( '#bulk-action-selector-top' )
				.selectOption( 'Edit' );
			await page.locator( '#doaction' ).click();

			await expect(
				await page.locator( '#bulk-titles-list li' ).count()
			).toEqual( products.length );
		} );

		await test.step( 'update the sale price', async () => {
			await page
				.locator( 'select[name="change_sale_price"]' )
				.selectOption(
					'Set to regular price decreased by (fixed amount or %):'
				);
			await page
				.getByPlaceholder( 'Enter sale price ($)' )
				.fill( `${ salePriceDecrease }%` );
		} );

		await test.step( 'save the updates', async () => {
			await saveBulkProductChanges( page );
		} );

		await test.step( 'verify the changes', async () => {
			for ( const product of products ) {
				await page.goto( `product/${ product.slug }` );

				const expectedRegularPrice = product.regular_price;

				const expectedSalePrice = (
					expectedRegularPrice *
					( 1 - salePriceDecrease / 100 )
				).toFixed( 2 );

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

		await test.step( 'Update products leaving the "Sale > Change to" empty', async () => {
			await page.goto( `wp-admin/edit.php?post_type=product` );

			for ( const product of products ) {
				await page.getByLabel( `Select ${ product.name }` ).click();
			}

			await page
				.locator( '#bulk-action-selector-top' )
				.selectOption( 'Edit' );
			await page.locator( '#doaction' ).click();

			await page
				.locator( 'select[name="change_sale_price"]' )
				.selectOption( 'Change to:' );

			await saveBulkProductChanges( page );
		} );

		await test.step( 'Verify products have their regular price again', async () => {
			for ( const product of products ) {
				await page.goto( `product/${ product.slug }` );

				const expectedRegularPrice = product.regular_price;

				await expect
					.soft( await page.locator( 'ins' ).count() )
					.toBe( 0 );

				await expect
					.soft( page.locator( 'bdi' ).first() )
					.toContainText( expectedRegularPrice );
			}
		} );
	}
);

test(
	'can decrease the sale price if the product was not previously in sale when bulk editing products',
	{ tag: [ tags.SERVICES ] },
	async ( { page, products } ) => {
		await page.goto( `wp-admin/edit.php?post_type=product` );

		const salePriceDecrease = 10;

		await test.step( 'Update products with the "Sale > Decrease existing sale price" option', async () => {
			await page.goto( `wp-admin/edit.php?post_type=product` );

			for ( const product of products ) {
				await page.getByLabel( `Select ${ product.name }` ).click();
			}

			await page
				.locator( '#bulk-action-selector-top' )
				.selectOption( 'Edit' );
			await page.locator( '#doaction' ).click();

			await page
				.locator( 'select[name="change_sale_price"]' )
				.selectOption(
					'Decrease existing sale price by (fixed amount or %):'
				);
			await page
				.getByPlaceholder( 'Enter sale price ($)' )
				.fill( `${ salePriceDecrease }%` );

			await saveBulkProductChanges( page );
		} );

		await test.step( 'Verify products have a sale price', async () => {
			for ( const product of products ) {
				await page.goto( `product/${ product.slug }` );

				const expectedSalePrice = (
					product.regular_price *
					( 1 - salePriceDecrease / 100 )
				).toFixed( 2 );

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
	}
);

test(
	'increasing the sale price from 0 does not change the sale price when bulk editing products',
	{ tag: [ tags.SERVICES ] },
	async ( { page, restApi, products } ) => {
		let product;
		await restApi
			.post( `${ WC_API_PATH }/products`, {
				id: 0,
				name: faker.commerce.productName(),
				type: 'simple',
				regular_price: '100',
				sale_price: '0',
				manage_stock: true,
				stock_quantity: 10,
				stock_status: 'instock',
			} )
			.then( ( response ) => {
				product = response.data;
				// For cleanup: products from this list are deleted in the fixture
				products.push( product );
			} );

		const salePriceIncrease = 10;

		await test.step( 'Update products with the "Sale > Increase existing sale price" option', async () => {
			await page.goto( `wp-admin/edit.php?post_type=product` );

			await page.getByLabel( `Select ${ product.name }` ).click();

			await page
				.locator( '#bulk-action-selector-top' )
				.selectOption( 'Edit' );
			await page.locator( '#doaction' ).click();

			await page
				.locator( 'select[name="change_sale_price"]' )
				.selectOption(
					'Increase existing sale price by (fixed amount or %):'
				);

			await page
				.getByPlaceholder( 'Enter sale price ($)' )
				.fill( `${ salePriceIncrease }%` );

			await saveBulkProductChanges( page );
		} );

		await test.step( 'Verify products have a sale price', async () => {
			await page.goto( `product/${ product.slug }` );

			const expectedSalePrice = '$0.00';

			await expect
				.soft(
					await page
						.locator( 'ins' )
						.getByText( expectedSalePrice )
						.count()
				)
				.toBeGreaterThan( 0 );
		} );
	}
);
