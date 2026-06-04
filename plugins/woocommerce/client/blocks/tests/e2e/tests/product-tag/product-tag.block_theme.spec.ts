/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	getDeprecatedBlockWarning,
	getProductTagIdByName,
	updateBlockAttributesBySlug,
} from '../../utils/deprecated-php-product-grid';

const blockData = {
	name: 'Products by Tag',
	slug: 'woocommerce/product-tag',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( 'shows a deprecation warning in the editor and renders on the frontend', async ( {
		editor,
		admin,
		frontendUtils,
		page,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: blockData.slug } );
		const blockLocator = await editor.getBlockByName( blockData.slug );
		await expect(
			blockLocator.getByText( getDeprecatedBlockWarning( blockData.name ) )
		).toBeVisible();

		const tagId = await getProductTagIdByName( page, 'Recommended' );
		await updateBlockAttributesBySlug( page, blockData.slug, {
			tags: [ tagId ],
		} );

		await editor.publishAndVisitPost();
		const blockLocatorFrontend = await frontendUtils.getBlockByName(
			blockData.slug
		);
		await expect(
			blockLocatorFrontend.getByRole( 'listitem' )
		).toHaveCount( 2 );
	} );
} );
