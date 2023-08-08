/**
 * External dependencies
 */

import { Page } from '@playwright/test';

/**
 * Internal dependencies
 */

export class FrontendUtils {
	page: Page;
	constructor( page: Page ) {
		this.page = page;
	}

	async getBlockByName( name: string ) {
		return this.page.locator( `[data-block-name="${ name }"]` );
	}

	async getBlockByClassWithParent( blockClass: string, parentName: string ) {
		const parentBlock = await this.getBlockByName( parentName );
		if ( ! parentBlock ) {
			throw new Error( `Parent block "${ parentName }" not found.` );
		}
		const block = parentBlock.locator( `.${ blockClass }` );
		return block;
	}

	async addToCart() {
		await this.page.click( 'text=Add to cart' );
	}

	async goToShop() {
		await this.page.goto( '/shop', {
			waitUntil: 'commit',
		} );
	}

	async isBlockEarlierThan< T >(
		containerBlock: T,
		firstBlock: string,
		secondBlock: string
	) {
		const container =
			containerBlock instanceof Function
				? await containerBlock()
				: containerBlock;

		if ( ! container ) {
			throw new Error( 'Container block not found.' );
		}

		const childBlocks = container.locator( '[data-block-name]' );

		let firstBlockIndex = -1;
		let secondBlockIndex = -1;

		for ( let i = 0; i < ( await childBlocks.count() ); i++ ) {
			const blockName = await childBlocks
				.nth( i )
				.getAttribute( 'data-block-name' );

			if ( blockName === firstBlock ) {
				firstBlockIndex = i;
			}

			if ( blockName === secondBlock ) {
				secondBlockIndex = i;
			}

			if ( firstBlockIndex !== -1 && secondBlockIndex !== -1 ) {
				break;
			}
		}

		if ( firstBlockIndex === -1 || secondBlockIndex === -1 ) {
			throw new Error( 'Both blocks must exist within the editor' );
		}

		return firstBlockIndex < secondBlockIndex;
	}
}
