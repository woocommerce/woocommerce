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
	updateBlockAttributesBySlug,
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
		await editor.insertBlock( { name: blockData.slug } );
		const blockLocator = await editor.getBlockByName( blockData.slug );
		await expect(
			blockLocator.getByText( getDeprecatedBlockWarning( blockData.name ) )
		).toBeVisible();

		const colorTerms = await getAllAttributeTerms( page, 'pa_color' );
		await updateBlockAttributesBySlug( page, blockData.slug, {
			attributes: colorTerms,
		} );

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
		await editor.insertBlock( { name: blockData.slug } );

		const colorTerms = await getAllAttributeTerms( page, 'pa_color' );
		await updateBlockAttributesBySlug( page, blockData.slug, {
			attributes: colorTerms,
		} );

		const sizeTerms = await getAllAttributeTerms( page, 'pa_size' );
		await updateBlockAttributesBySlug( page, blockData.slug, {
			attributes: sizeTerms,
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
