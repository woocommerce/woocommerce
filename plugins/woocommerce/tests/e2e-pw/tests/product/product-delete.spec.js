/**
 * Internal dependencies
 */
import { expect, test as baseTest } from '../../fixtures/fixtures';
import { getFakeProduct } from '../../utils/data';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { WC_ADMIN_API_PATH, WC_API_PATH } from '../../utils/api-client';

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	product: async ( { restApi }, use ) => {
		let product = getFakeProduct();

		await restApi
			.post( `${ WC_API_PATH }/products`, product )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		try {
			await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
				force: true,
			} );
		} catch ( error ) {
			// Only throw if the error is not a 404 (product not found).
			// This is expected if the product was already deleted by the test.
			if ( error.data?.data?.status !== 404 ) {
				throw error;
			}
		}
	},
	page: async ( { page, restApi }, use ) => {
		// Disable the task list reminder bar, it can interfere with the quick actions
		await restApi.post( `${ WC_ADMIN_API_PATH }/options`, {
			woocommerce_task_list_reminder_bar_hidden: 'yes',
		} );

		await use( page );
	},
} );

test( 'can delete a product from edit view', async ( { page, product } ) => {
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
		await expect( page.locator( `#post-${ product.id }` ) ).toBeVisible();

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
		await expect( page.locator( `#post-${ product.id }` ) ).toBeVisible();

		// Verify the product cannot be edited via direct URL
		await page.goto( `wp-admin/post.php?post=${ product.id }&action=edit` );
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
	restApi,
} ) => {
	// trash the product
	await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
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
		await page.goto( `wp-admin/post.php?post=${ product.id }&action=edit` );
		await expect(
			page.getByText(
				'You attempted to edit an item that does not exist. Perhaps it was deleted?'
			)
		).toBeVisible();
	} );
} );
