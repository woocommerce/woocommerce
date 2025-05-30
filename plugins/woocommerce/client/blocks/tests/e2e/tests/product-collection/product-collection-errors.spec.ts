/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

const test = base.extend< {
	category: { name: string; id: number };
	product: { name: string; id: number };
	pageSetup: boolean;
	pageId: number;
} >( {
	category: async ( { page }, use ) => {
		const name = `e2e-test-cat-${ Date.now() }`;
		let categoryId: number | null = null;

		try {
			await page.goto(
				'wp-admin/edit-tags.php?taxonomy=product_cat&post_type=product'
			);
			await page.locator( '#tag-name' ).fill( name );
			await page
				.getByRole( 'button', { name: 'Add new category' } )
				.click();

			const row = page
				.locator( 'table.wp-list-table td.name a.row-title' )
				.filter( { hasText: name } );
			await expect( row ).toBeVisible();

			const href = await row.getAttribute( 'href' );
			const idMatch = href?.match( /tag_ID=(\d+)/ );
			categoryId = idMatch ? parseInt( idMatch[ 1 ], 10 ) : null;

			if ( ! categoryId ) {
				throw new Error(
					`Could not extract category ID for: ${ name }`
				);
			}

			await use( { name, id: categoryId } );
		} finally {
			if ( categoryId ) {
				try {
					await page.goto(
						'wp-admin/edit-tags.php?taxonomy=product_cat&post_type=product'
					);
					const deleteLink = page.locator(
						`tr[id="tag-${ categoryId }"] .row-actions .delete a`
					);
					if ( await deleteLink.isVisible() ) {
						await deleteLink.scrollIntoViewIfNeeded();
						await deleteLink.click();
					}
				} catch ( error ) {
					throw new Error(
						`Failed to cleanup category ${ categoryId }: ${ error }`
					);
				}
			}
		}
	},

	product: async ( { page, category }, use ) => {
		const productName = `Out of Stock Product ${ Date.now() }`;
		const { id: categoryId } = category;
		let productId: number | null = null;

		try {
			await page.goto( '/wp-admin/post-new.php?post_type=product', {
				waitUntil: 'domcontentloaded',
			} );

			// Wait for the to be rendered
			await page
				.locator( '#woocommerce-product-data' )
				.waitFor( { timeout: 10000 } );

			await page.getByLabel( 'Product name' ).fill( productName );

			// Wait for the Regular price input to exist before filling
			await page
				.locator( '#_regular_price' )
				.waitFor( { timeout: 10000 } );
			await page.fill( '#_regular_price', '29.99' );

			await page.getByRole( 'link', { name: 'Inventory' } ).click();

			const manageStock = page.locator( '#_manage_stock' );
			if ( await manageStock.isChecked() ) {
				await manageStock.uncheck();
			}

			const outOfStockRadio = page.getByRole( 'radio', {
				name: 'Out of stock',
			} );
			await outOfStockRadio.waitFor( { timeout: 5000 } );
			await outOfStockRadio.check();
			await expect( outOfStockRadio ).toBeChecked();

			const categoryCheckbox = page.locator(
				`input[name="tax_input[product_cat][]"][value="${ categoryId }"]`
			);
			await categoryCheckbox.check();

			await page.locator( 'input#publish' ).click();

			// After publishing, wait for WooCommerce's "Product published." notice
			const successNotice = page.locator( '#message.notice-success' );
			await expect( successNotice ).toBeVisible( { timeout: 20000 } );

			// (optional) Get product ID from URL safely after publish is complete
			const currentUrl = page.url();
			const productIdMatch = currentUrl.match( /post=(\d+)/ );
			if ( productIdMatch ) {
				productId = parseInt( productIdMatch[ 1 ], 10 );
			}

			await use( { name: productName, id: productId! } );
		} finally {
			if ( productId ) {
				try {
					await page.goto(
						`/wp-admin/post.php?post=${ productId }&action=edit`
					);
					const moveToTrashLink = page.getByRole( 'link', {
						name: 'Move to Trash',
					} );
					if ( await moveToTrashLink.isVisible() ) {
						await moveToTrashLink.click();
					}
				} catch ( error ) {}
			}
		}
	},

	pageSetup: async ( { request, category }, use ) => {
		let pageId: number | null = null;

		try {
			const res = await request.post( '/wp/v2/pages', {
				data: {
					title: 'Product Collection Test',
					content: `<!-- wp:woocommerce/product-collection {"categories":["${ category.id }"]} /-->`,
					status: 'publish',
					slug: 'product-collection-test',
				},
			} );

			expect( res.status() ).toBe( 201 );
			const data = await res.json();
			pageId = data.id;

			await use( true );
		} finally {
			if ( pageId ) {
				try {
					await request.delete(
						`/wp/v2/pages/${ pageId }?force=true`
					);
				} catch ( error ) {}
			}
		}
	},
} );

test( 'displays error notice when adding out-of-stock product from Product Collection block', async ( {
	page,
	product,
	pageSetup,
} ) => {
	expect( pageSetup ).toBeTruthy();

	await test.step( 'Go to the test page with Product Collection block', async () => {
		await page.goto( '/product-collection-test' );
		await expect( page.getByText( product.name ) ).toBeVisible();
	} );

	await test.step( 'Try adding the out-of-stock product to cart', async () => {
		const addToCartBtn = page
			.getByRole( 'button', { name: /add to cart/i } )
			.first();
		await addToCartBtn.click();
	} );

	await test.step( 'Expect error notice to appear', async () => {
		const errorNotice = page.locator(
			'.wc-block-components-notice-banner.is-error'
		);
		await expect( errorNotice ).toBeVisible( { timeout: 5000 } );
		await expect( errorNotice ).toContainText(
			/out of stock|cannot be added/i
		);
	} );
} );
