/**
 * External dependencies
 */
import { Editor, Admin } from '@woocommerce/e2e-utils';
import { Page } from '@playwright/test';
import { saveAndActivateTemplate } from './constants';

class TemplatesPage {
	private admin: Admin;
	private editor: Editor;
	private page: Page;

	constructor( {
		admin,
		editor,
		page,
	}: {
		admin: Admin;
		editor: Editor;
		page: Page;
	} ) {
		this.admin = admin;
		this.editor = editor;
		this.page = page;
	}

	async addParagraphToTemplate( templateSlug: string, content: string ) {
		await this.admin.visitSiteEditor( {
			postId: templateSlug,
			postType: 'wp_registered_template',
			canvas: 'edit',
		} );

		await this.editor.insertBlock( {
			name: 'core/paragraph',
			attributes: {
				content,
			},
		} );

		await saveAndActivateTemplate( {
			editor: this.editor,
			page: this.page,
		} );
	}
}

export default TemplatesPage;
