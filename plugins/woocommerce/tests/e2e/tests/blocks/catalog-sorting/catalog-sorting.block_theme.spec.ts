/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

const blockData = {
	name: 'Catalog Sorting',
	slug: 'woocommerce/catalog-sorting',
	class: '.wc-block-catalog-sorting',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( 'block can be inserted in the Site Editor', async ( {
		admin,
		requestUtils,
		cleanup,
		editor,
	} ) => {
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug: `catalog-sorting-test-${ test.info().repeatEachIndex }`,
			title: `Catalog Sorting Test ${ test.info().repeatEachIndex }`,
			content: 'placeholder',
		} );
		cleanup( () =>
			requestUtils.revertTemplate( 'wp_template', template.id )
		);

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await expect(
			editor.getCustomHtmlBlockContentLocator( 'placeholder' )
		).toBeVisible();

		await editor.insertBlock( {
			name: blockData.slug,
		} );

		const block = await editor.getBlockByName( blockData.slug );
		await expect( block ).toHaveText( 'Default sorting' );
	} );
} );
