/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	getDeprecatedBlockWarning,
	getProductCategoryIdByName,
} from '../../utils/deprecated-php-product-grid';

const blockData = {
	name: 'Products by Category',
	slug: 'woocommerce/product-category',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( 'shows a deprecation warning in the editor and renders on the frontend', async ( {
		editor,
		admin,
		frontendUtils,
		page,
	} ) => {
		await admin.createNewPost();
		const categoryId = await getProductCategoryIdByName(
			page,
			'Accessories'
		);
		await editor.insertBlock( {
			name: blockData.slug,
			attributes: {
				categories: [ categoryId ],
			},
		} );
		const blockLocator = await editor.getBlockByName( blockData.slug );
		await expect(
			blockLocator.getByText(
				getDeprecatedBlockWarning( blockData.name )
			)
		).toBeVisible();

		await editor.publishAndVisitPost();
		const blockLocatorFrontend = await frontendUtils.getBlockByName(
			blockData.slug
		);
		await expect(
			blockLocatorFrontend.getByRole( 'listitem' )
		).toHaveCount( 5 );
	} );
} );
