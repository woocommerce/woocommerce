/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import path from 'path';

/**
 * Internal dependencies
 */
import { createPostFromTemplate } from '../../utils/create-dynamic-content';

const TEMPLATE_PATH = path.join( __dirname, 'stock-status.handlebars' );

test.describe( 'Product Filter: Stock Status Block', async () => {
	test( 'By default it renders a checkbox list with the available stock statuses', async ( {
		page,
	} ) => {
		const { url, deletePost } = await createPostFromTemplate(
			'Product Filter: Stock Status Block',
			TEMPLATE_PATH,
			{}
		);

		await page.goto( url );

		const stockStatuses = page.locator(
			'.wc-block-components-checkbox__label'
		);

		await expect( stockStatuses ).toHaveCount( 2 );

		await expect( stockStatuses.nth( 0 ) ).toHaveText( 'In stock' );
		await expect( stockStatuses.nth( 1 ) ).toHaveText( 'Out of stock' );

		await deletePost();
	} );
} );
