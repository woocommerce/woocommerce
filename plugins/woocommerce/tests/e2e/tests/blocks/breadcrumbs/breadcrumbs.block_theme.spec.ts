/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

const blockData = {
	slug: 'woocommerce/breadcrumbs',
	name: 'Store Breadcrumbs',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( 'block can be inserted in the Site Editor', async ( {
		admin,
		requestUtils,
		editor,
	} ) => {
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug: `breadcrumbs-test-${ test.info().repeatEachIndex }`,
			title: `Breadcrumbs Test ${ test.info().repeatEachIndex }`,
			content: 'placeholder',
		} );

		try {
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

			await expect( block ).toHaveText(
				'Breadcrumbs / Navigation / Path'
			);
		} finally {
			await requestUtils.revertTemplate( 'wp_template', template.id );
		}
	} );
} );
