/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	getAllAttributeTerms,
	getDeprecatedBlockWarning,
} from '../../utils/deprecated-php-product-grid';

const blockData = {
	name: 'Products by Attribute',
	slug: 'woocommerce/products-by-attribute',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( 'shows a deprecation warning in the editor and renders on the frontend', async ( {
		editor,
		admin,
		frontendUtils,
		page,
	} ) => {
		await admin.createNewPost();

		const colorTerms = await getAllAttributeTerms( page, 'pa_color' );
		await editor.insertBlock( {
			name: blockData.slug,
			attributes: {
				attributes: colorTerms,
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
		).toHaveCount( 9 );
	} );

	test( 'can filter products by attribute on the frontend', async ( {
		editor,
		admin,
		frontendUtils,
		page,
	} ) => {
		await admin.createNewPost();
		const sizeTerms = await getAllAttributeTerms( page, 'pa_size' );
		await editor.insertBlock( {
			name: blockData.slug,
			attributes: {
				attributes: sizeTerms,
			},
		} );

		await editor.publishAndVisitPost();
		const blockLocatorFrontend = await frontendUtils.getBlockByName(
			blockData.slug
		);
		await expect(
			blockLocatorFrontend.getByRole( 'listitem' )
		).toHaveCount( 1 );
	} );
} );
