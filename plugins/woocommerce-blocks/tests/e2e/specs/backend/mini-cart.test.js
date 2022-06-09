/**
 * External dependencies
 */
import {
	insertBlock,
	canvas,
	searchForBlock as searchForFSEBlock,
} from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import {
	openWidgetsEditorBlockInserter,
	closeModalIfExists,
	openWidgetEditor,
	searchForBlock,
	isBlockInsertedInWidgetsArea,
	goToSiteEditor,
	useTheme,
	waitForCanvas,
	addBlockToFSEArea,
} from '../../utils.js';

const block = {
	name: 'Mini Cart',
	slug: 'woocommerce/mini-cart',
	class: '.wc-block-mini-cart',
	selectors: {
		insertButton: "//button//span[text()='Mini Cart']",
		insertButtonDisabled:
			"//button[@aria-disabled]//span[text()='Mini Cart']",
		compatibilityNoticeTitle:
			"//h1[contains(text(), 'Compatibility notice')]",
	},
};

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 3 ) {
	// eslint-disable-next-line jest/no-focused-tests, jest/expect-expect
	test.only( `skipping ${ block.name } tests`, () => {} );
}

const removeDismissedCompatibilityNoticesFromLocalStorage = async () => {
	await page.evaluate( () => {
		window.localStorage.removeItem(
			'wc-blocks_dismissed_compatibility_notices'
		);
	} );
};

const addBlockToWidgetsArea = async () => {
	await closeModalIfExists();
	await openWidgetsEditorBlockInserter();
	await searchForBlock( block.name );
	const miniCartButton = await page.$x( block.selectors.insertButton );
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
				block.selectors.compatibilityNoticeTitle
			);
			expect( compatibilityNoticeTitle.length ).toBe( 1 );
		} );

		it( "after the compatibility notice is dismissed, it doesn't appear again", async () => {
			await page.evaluate( () => {
				window.localStorage.setItem(
					'wc-blocks_dismissed_compatibility_notices',
					'["mini-cart"]'
				);
			} );
			await addBlockToWidgetsArea();
			const compatibilityNoticeTitle = await page.$x(
				block.selectors.compatibilityNoticeTitle
			);
			expect( compatibilityNoticeTitle.length ).toBe( 0 );
		} );

		it( 'can only be inserted once', async () => {
			await addBlockToWidgetsArea();
			const miniCartButton = await page.$x(
				block.selectors.insertButtonDisabled
			);

			expect( miniCartButton ).toHaveLength( 1 );
		} );
	} );

	describe( 'in FSE editor', () => {
		useTheme( 'emptytheme' );

		beforeEach( async () => {
			// TODO: Update to always use site-editor.php once WordPress 6.0 is released and fix is verified.
			await goToSiteEditor(
				process.env.GUTENBERG_EDITOR_CONTEXT || 'core'
			);
			await removeDismissedCompatibilityNoticesFromLocalStorage();
			await waitForCanvas();
		} );

		it( 'can be inserted in FSE area', async () => {
			await insertBlock( block.name );
			await expect( canvas() ).toMatchElement( block.class );
		} );

		it( 'the compatibility notice appears', async () => {
			await addBlockToFSEArea( block.name );
			const compatibilityNoticeTitle = await page.$x(
				block.selectors.compatibilityNoticeTitle
			);
			expect( compatibilityNoticeTitle.length ).toBe( 1 );
		} );

		it( "after the compatibility notice is dismissed, it doesn't appear again", async () => {
			await page.evaluate( () => {
				window.localStorage.setItem(
					'wc-blocks_dismissed_compatibility_notices',
					'["mini-cart"]'
				);
			} );
			await addBlockToFSEArea( block.name );
			const compatibilityNoticeTitle = await page.$x(
				block.selectors.compatibilityNoticeTitle
			);
			expect( compatibilityNoticeTitle.length ).toBe( 0 );
		} );

		it( 'can only be inserted once', async () => {
			await insertBlock( block.name );
			await searchForFSEBlock( block.name );
			const miniCartButton = await page.$x(
				block.selectors.insertButtonDisabled
			);
			expect( miniCartButton ).toHaveLength( 1 );
		} );
	} );
} );
