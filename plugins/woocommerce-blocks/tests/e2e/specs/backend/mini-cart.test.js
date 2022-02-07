/**
 * Internal dependencies
 */
import {
	openWidgetsEditorBlockInserter,
	closeModalIfExists,
	openWidgetEditor,
	searchForBlock,
	isBlockInsertedInWidgetsArea,
} from '../../utils.js';

const block = {
	name: 'Mini Cart',
	slug: 'woocommerce/mini-cart',
	class: '.wc-block-mini-cart',
};

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 3 ) {
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `skipping ${ block.name } tests`, () => {} );
}

const removeDismissedCompatibilityNoticesFromLocalStorage = async () => {
	await page.evaluate( () => {
		localStorage.removeItem( 'wc-blocks_dismissed_compatibility_notices' );
	} );
};

const addBlockToWidgetsArea = async () => {
	await closeModalIfExists();
	await openWidgetsEditorBlockInserter();
	await searchForBlock( block.name );
	const miniCartButton = await page.$x(
		`//button//span[text()='${ block.name }']`
	);

	await miniCartButton[ 0 ].click();
};

describe( `${ block.name } Block`, () => {
	describe( 'in widget editor', () => {
		beforeAll( async () => {
			await removeDismissedCompatibilityNoticesFromLocalStorage();
		} );

		beforeEach( async () => {
			await openWidgetEditor();
		} );

		it( 'can be inserted in widget area', async () => {
			await addBlockToWidgetsArea();
			expect( await isBlockInsertedInWidgetsArea( block.slug ) ).toBe(
				true
			);
		} );

		it( 'the compatibility notice appears', async () => {
			await addBlockToWidgetsArea();
			const compatibilityNoticeTitle = await page.$x(
				`//h1[contains(text(), 'Compatibility notice')]`
			);
			expect( compatibilityNoticeTitle.length ).toBe( 1 );
		} );

		it( "after the compatibility notice is dismissed, it doesn't appear again", async () => {
			await page.evaluate( () => {
				localStorage.setItem(
					'wc-blocks_dismissed_compatibility_notices',
					'["mini-cart"]'
				);
			} );
			await addBlockToWidgetsArea();
			const compatibilityNoticeTitle = await page.$x(
				`//h1[contains(text(), 'Compatibility notice')]`
			);
			expect( compatibilityNoticeTitle.length ).toBe( 0 );
		} );

		it( 'can only be inserted once', async () => {
			await addBlockToWidgetsArea();
			const miniCartButton = await page.$x(
				`//button[@aria-disabled]//span[text()='${ block.name }']`
			);

			expect( miniCartButton ).toHaveLength( 1 );
		} );
	} );

	// @todo Add tests for the Mini Cart block in FSE editor
	// describe( 'in FSE editor', () => {} );
} );
