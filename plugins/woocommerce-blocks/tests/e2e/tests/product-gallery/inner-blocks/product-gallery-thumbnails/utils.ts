/**
 * External dependencies
 */
import { Admin, Editor } from '@wordpress/e2e-test-utils-playwright';

// Define a utility function to add the "woocommerce/product-gallery" block to the editor
export const addBlock = async ( admin: Admin, editor: Editor ) => {
	// Visit the site editor for the specific product page
	await admin.visitSiteEditor( {
		postId: `woocommerce/woocommerce//single-product`,
		postType: 'wp_template',
		canvas: 'edit',
	} );

	// Insert the "woocommerce/product-gallery" block
	await editor.insertBlock( {
		name: 'woocommerce/product-gallery',
	} );
};
