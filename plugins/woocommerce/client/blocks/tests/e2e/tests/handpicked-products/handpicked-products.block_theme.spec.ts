/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	getDeprecatedBlockWarning,
	getProductIdBySlug,
	updateBlockAttributesBySlug,
} from '../../utils/deprecated-php-product-grid';

const blockData = {
	name: 'Hand-picked Products',
	slug: 'woocommerce/handpicked-products',
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

		const albumId = await getProductIdBySlug( page, 'woo-album' );
		await updateBlockAttributesBySlug( page, blockData.slug, {
			products: [ albumId ],
			editMode: false,
		} );

		await editor.publishAndVisitPost();
		const blockLocatorFrontend = await frontendUtils.getBlockByName(
			blockData.slug
		);
		await expect( blockLocatorFrontend ).toBeVisible();
		await expect( blockLocatorFrontend.getByText( 'Album' ) ).toBeVisible();
		await expect(
			blockLocatorFrontend.getByText( 'Add to Cart' )
		).toBeVisible();
	} );
} );
