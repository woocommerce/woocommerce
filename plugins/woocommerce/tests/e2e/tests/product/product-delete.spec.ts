/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, test as baseTest } from '../../fixtures/fixtures';
import { getFakeProduct } from '../../utils/data';
import { ADMIN_STATE_PATH } from '../../playwright.config';

/**
 * Clicks the hover-revealed Trash / Delete Permanently row action for a product
 * in the WP list table.
 *
 * The matched product row can render near the bottom of the list table, where
 * the revealed action link ends up below the fold or obscured by the table
 * footer, so a plain click is intercepted and times out. Scrolling the row into
 * view before hovering keeps the action inside the viewport and clickable.
 */
async function deleteProductViaRowAction( page: Page, productId: number ) {
	const row = page.locator( `#post-${ productId }` );

	// Bring the row (and its always-present row-actions) fully into view before
	// hovering, so the Trash link isn't outside the viewport or under the footer.
	await row.scrollIntoViewIfNeeded();

	// Mouse over the product row to reveal the quick actions.
	await row.hover();

	// Trigger the delete row action.
	await row.locator( '.submitdelete' ).click();
}

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
			if ( error.status !== 404 ) {
				throw error;
			}
		}
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
		await deleteProductViaRowAction( page, product.id );
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
		await deleteProductViaRowAction( page, product.id );
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
