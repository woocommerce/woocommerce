const { test: baseTest, expect } = require( '../../fixtures/fixtures' );

baseTest.describe( 'Products > Delete Product', () => {
	const test = baseTest.extend( {
		storageState: process.env.ADMINSTATE,
		product: async ( { api }, use ) => {
			let product = {
				id: 0,
				name: `Product ${ Date.now() }`,
				type: 'simple',
				regular_price: '12.99',
			};

			await api.post( 'products', product ).then( ( response ) => {
				product = response.data;
			} );

			await use( product );

			// permanently delete the product if it still exists
			const r = await api.get( `products/${ product.id }` );
			if ( r.status !== 404 ) {
				await api.delete( `products/${ product.id }`, {
					force: true,
				} );
			}
		},
		page: async ( { page, wcAdminApi }, use ) => {
			// Disable the task list reminder bar, it can interfere with the quick actions
			await wcAdminApi.post( 'options', {
				woocommerce_task_list_reminder_bar_hidden: 'yes',
			} );

			await use( page );
		},
	} );

	test( 'can delete a product from edit view', async ( {
		page,
		product,
	} ) => {
		const editUrl = `wp-admin/post.php?post=${ product.id }&action=edit`;

		await test.step( 'Navigate to product edit page', async () => {
			await page.goto( editUrl );
		} );

		await test.step( 'Move product to trash', async () => {
			await page.getByRole( 'link', { name: 'Move to Trash' } ).click();
		} );

		await test.step( 'Verify product was trashed', async () => {
			// Verify displayed message
			await expect( page.locator( '#message' ).last() ).toContainText(
				'1 product moved to the Trash.'
			);

			// Verify the product is now in the trash
			await page.goto(
				`wp-admin/edit.php?post_status=trash&post_type=product`
			);
			await expect(
				page.locator( `#post-${ product.id }` )
			).toBeVisible();

			// Verify the product cannot be edited via direct URL
			await page.goto( editUrl );
			await expect(
				page.getByText(
					'You cannot edit this item because it is in the Trash. Please restore it and try again.'
				)
			).toBeVisible();
		} );
	} );

	test( 'can quick delete a product from product list', async ( {
		page,
		product,
	} ) => {
		await test.step( 'Navigate to products list page', async () => {
			await page.goto(
				`wp-admin/edit.php?post_type=product&s=${ product.name }`
			);
		} );

		await test.step( 'Move product to trash', async () => {
			// mouse over the product row to display the quick actions
			await page.locator( `#post-${ product.id }` ).hover();

			// move product to trash
			await page.locator( `#post-${ product.id } .submitdelete` ).click();
		} );

		await test.step( 'Verify product was trashed', async () => {
			// Verify displayed message
			await expect( page.locator( '#message' ).last() ).toContainText(
				'1 product moved to the Trash.'
			);

			// Verify the product is now in the trash
			await page.goto(
				`wp-admin/edit.php?post_status=trash&post_type=product`
			);
			await expect(
				page.locator( `#post-${ product.id }` )
			).toBeVisible();

			// Verify the product cannot be edited via direct URL
			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
			await expect(
				page.getByText(
					'You cannot edit this item because it is in the Trash. Please restore it and try again.'
				)
			).toBeVisible();
		} );
	} );

	test( 'can permanently delete a product from trash list', async ( {
		page,
		product,
		api,
	} ) => {
		// trash the product
		await api.delete( `products/${ product.id }`, {
			force: false,
		} );

		await test.step( 'Navigate to products trash list page', async () => {
			await page.goto(
				`wp-admin/edit.php?post_status=trash&post_type=product`
			);
		} );

		await test.step( 'Permanently delete the product', async () => {
			// mouse over the product row to display the quick actions
			await page.locator( `#post-${ product.id }` ).hover();

			// delete the product
			await page.locator( `#post-${ product.id } .submitdelete` ).click();
		} );

		await test.step( 'Verify product was permanently deleted', async () => {
			await expect( page.locator( '#message' ).last() ).toContainText(
				'1 product permanently deleted.'
			);

			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
			await expect(
				page.getByText(
					'You attempted to edit an item that does not exist. Perhaps it was deleted?'
				)
			).toBeVisible();
		} );
	} );
} );
