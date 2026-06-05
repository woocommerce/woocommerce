/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { getDeprecatedBlockWarning } from '../../utils/deprecated-php-product-grid';

const blockData = {
	name: 'Newest Products',
	slug: 'woocommerce/product-new',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( 'shows a deprecation warning in the editor and renders on the frontend', async ( {
		editor,
		admin,
		frontendUtils,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: blockData.slug } );
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
} );
