/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { Editor, FrontendUtils, RequestUtils } from '@woocommerce/e2e-utils';
import { BlockRepresentation } from '@wordpress/e2e-test-utils-playwright/build-types/editor/insert-block';

export class AccordionPage {
	editor: Editor;
	page: Page;
	frontendUtils: FrontendUtils;
	requestUtils: RequestUtils;
	constructor( {
		editor,
		page,
		frontendUtils,
		requestUtils,
	}: {
		editor: Editor;
		page: Page;
		frontendUtils: FrontendUtils;
		requestUtils: RequestUtils;
	} ) {
		this.editor = editor;
		this.page = page;
		this.frontendUtils = frontendUtils;
		this.editor = editor;
		this.requestUtils = requestUtils;
	}

	async insertNestedPanelBlock(
		index: number,
		blockRepresentation: BlockRepresentation
	) {
		const parentBlock = (
			await this.editor.getBlockByName( 'woocommerce/accordion-panel' )
		 ).nth( index ?? 0 );
		const clientId =
			( await parentBlock.getAttribute( 'data-block' ) ) ?? '';
		const parentClientId =
			( await this.editor.getBlockRootClientId( clientId ) ) ?? '';

		await this.editor.selectBlocks( parentBlock );
		await this.editor.insertBlock( blockRepresentation, {
			clientId: parentClientId,
		} );
	}

	async insertAccordion() {
		const parentBlock = await this.editor.getBlockByName(
			'woocommerce/accordion-group'
		);
		await this.editor.selectBlocks( parentBlock );
		await this.editor.canvas.getByLabel( 'Add Accordion' ).click();
	}

	async setAccordionTitleAndContent(
		index: number,
		title: string,
		content: string
	) {
		const blockLocator = await this.editor.getBlockByName(
			'woocommerce/accordion-group'
		);
		await blockLocator
			.getByLabel( 'Accordion title' )
			.nth( index )
			.fill( title );
		await this.insertNestedPanelBlock( index, {
			name: 'core/paragraph',
		} );
		await this.editor.canvas
			.getByRole( 'document', { name: 'Empty block' } )
			.fill( content );
	}

	async insertAccordionGroup(
		accordionData: {
			title: string;
			content: string;
		}[]
	) {
		await this.editor.insertBlock( {
			name: 'woocommerce/accordion-group',
		} );
		for ( let index = 0; index < accordionData.length; index++ ) {
			const data = accordionData[ index ];
			const accordionCount = await (
				await this.editor.getBlockByName( 'woocommerce/accordion-item' )
			 ).count();
			if ( index >= accordionCount ) {
				await this.insertAccordion();
			}
			await this.setAccordionTitleAndContent(
				index,
				data.title,
				data.content
			);
		}
	}
}
