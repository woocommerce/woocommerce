import fs from 'fs';
import path from 'path';
import type { Page } from '@playwright/test';
import { readFile } from '../utils.js';
import { expect } from '@wordpress/e2e-test-utils-playwright';

type PerfUtilsConstructorProps = {
	page: Page;
};

export class PerfUtils {
	page: Page;

	constructor( { page }: PerfUtilsConstructorProps ) {
		this.page = page;
	}

	/**
	 * Returns the locator for the editor canvas element. This supports both the
	 * legacy and the iframed canvas which is necessary when comparing
	 * performance against older versions.
	 *
	 * @return Locator for the editor canvas element.
	 */
	async getCanvas() {
		const canvasLocator = this.page.locator(
			'.wp-block-post-content, iframe[name=editor-canvas]'
		);

		const isFramed = await canvasLocator.evaluate(
			( node ) => node.tagName === 'IFRAME'
		);

		if ( isFramed ) {
			return canvasLocator.frameLocator( ':scope' );
		}

		return canvasLocator;
	}

	/**
	 * Saves the post as a draft and returns its ID.
	 *
	 * @return URL of the saved draft.
	 */
	async saveDraft() {
		await this.page.getByRole( 'button', { name: 'Save draft' } ).click();

		const postId = await this.page.waitForFunction( () => {
			return new URL( window.location.href ).searchParams.get( 'post' )
		})

		return postId;
	}

	/**
	 * Disables the editor autosave function. This can be used in order to avoid
	 * the autosave feature from interfering with the performance tests.
	 */
	async disableAutosave() {
		await this.page.waitForFunction( () => window?.wp?.data );

		await this.page.evaluate( () => {
			return window.wp.data
				.dispatch( 'core/editor' )
				.updateEditorSettings( {
					autosaveInterval: 100000000000,
					localAutosaveInterval: 100000000000,
				} );
		} );
	}

	/**
	 * Loads blocks into the editor canvas from an HTML asset.
	 *
	 * @param filepath Path to the HTML fixture.
	 */
	async loadBlocksFromHtml( filename: string ) {
		const assetPath = path.join( process.env.ASSETS_PATH!, filename );
		if ( ! fs.existsSync( assetPath ) ) {
			throw new Error( `File not found: ${ assetPath }` );
		}

		await this.page.waitForFunction(
			() => window?.wp?.blocks && window?.wp?.data
		);

		return await this.page.evaluate( ( html: string ) => {
			const { parse } = window.wp.blocks;
			const { dispatch } = window.wp.data;
			const blocks = parse( html );

			blocks.forEach( ( block: any ) => {
				if ( block.name === 'core/image' ) {
					delete block.attributes.id;
					delete block.attributes.url;
				}
			} );

			dispatch( 'core/block-editor' ).resetBlocks( blocks );
		}, readFile( assetPath ) );
	}
}
