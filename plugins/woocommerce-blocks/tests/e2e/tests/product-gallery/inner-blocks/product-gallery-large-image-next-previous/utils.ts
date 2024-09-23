/**
 * External dependencies
 */
import { Admin, Editor } from '@woocommerce/e2e-utils';

export const addBlock = async ( admin: Admin, editor: Editor ) => {
	await admin.visitSiteEditor( {
		postId: `woocommerce/woocommerce//single-product`,
		postType: 'wp_template',
		canvas: 'edit',
	} );

	await editor.insertBlock( {
		name: 'woocommerce/product-gallery',
	} );
};
