/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { EditorUtils, FrontendUtils } from '@woocommerce/e2e-utils';
import { Editor } from '@wordpress/e2e-test-utils-playwright';

const selectors = {
	editor: {},
};

export class ProductFiltersPage {
	editor: Editor;
	page: Page;
	frontendUtils: FrontendUtils;
	editorUtils: EditorUtils;
	constructor( {
		editor,
		page,
		frontendUtils,
		editorUtils,
	}: {
		editor: Editor;
		page: Page;
		frontendUtils: FrontendUtils;
		editorUtils: EditorUtils;
	} ) {
		this.editor = editor;
		this.page = page;
		this.frontendUtils = frontendUtils;
		this.editorUtils = editorUtils;
	}

	async addProductFiltersBlock( { cleanContent = true } ) {
		if ( cleanContent ) {
			await this.editor.setContent( '' );
		}
		await this.editor.insertBlock( {
			name: 'woocommerce/product-filters',
		} );
	}

	async getBlock( { page }: { page: 'frontend' | 'editor' } ) {
		const blockName = 'woocommerce/product-filters';
		if ( page === 'frontend' ) {
			return (
				await this.frontendUtils.getBlockByName( blockName )
			 ).filter( {
				has: this.page.locator( ':visible' ),
			} );
		}
		return this.editorUtils.getBlockByName( blockName );
	}
}
