/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { Editor, FrontendUtils } from '@woocommerce/e2e-utils';

export class ProductFiltersPage {
	editor: Editor;
	page: Page;
	frontendUtils: FrontendUtils;
	constructor( {
		editor,
		page,
		frontendUtils,
	}: {
		editor: Editor;
		page: Page;
		frontendUtils: FrontendUtils;
	} ) {
		this.editor = editor;
		this.page = page;
		this.frontendUtils = frontendUtils;
		this.editor = editor;
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
		return this.editor.getBlockByName( blockName );
	}
}
