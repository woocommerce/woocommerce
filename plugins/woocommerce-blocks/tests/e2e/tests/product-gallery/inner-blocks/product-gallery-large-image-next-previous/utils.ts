/**
 * External dependencies
 */
import { EditorUtils, WC_TEMPLATES_SLUG } from '@woocommerce/e2e-utils';
import { Admin, Editor } from '@wordpress/e2e-test-utils-playwright';

export const addBlock = async (
	admin: Admin,
	editor: Editor,
	editorUtils: EditorUtils
) => {
	await admin.visitSiteEditor( {
		postId: `${ WC_TEMPLATES_SLUG }//single-product`,
		postType: 'wp_template',
	} );
	await editorUtils.enterEditMode();

	await editor.insertBlock( {
		name: 'woocommerce/product-gallery',
	} );
};
