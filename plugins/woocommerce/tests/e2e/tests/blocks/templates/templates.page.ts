/**
 * External dependencies
 */
import { Editor, Admin } from '@woocommerce/e2e-utils';

class TemplatesPage {
	private admin: Admin;
	private editor: Editor;

	constructor( { admin, editor }: { admin: Admin; editor: Editor } ) {
		this.admin = admin;
		this.editor = editor;
	}

	async addParagraphToTemplate( templateSlug: string, content: string ) {
		await this.admin.visitSiteEditor( {
			postId: templateSlug,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await this.editor.insertBlock( {
			name: 'core/paragraph',
			attributes: {
				content,
			},
		} );

		await this.editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );
	}
}

export default TemplatesPage;
