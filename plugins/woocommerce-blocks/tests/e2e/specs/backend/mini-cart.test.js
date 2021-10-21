/**
 * External dependencies
 */
import { switchUserToAdmin, getAllBlocks } from '@wordpress/e2e-test-utils';
import { visitBlockPage } from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import { insertBlockDontWaitForInsertClose } from '../../utils.js';

const block = {
	name: 'Mini Cart',
	slug: 'woocommerce/mini-cart',
	class: '.wc-block-mini-cart',
};

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 3 ) {
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `skipping ${ block.name } tests`, () => {} );
}

describe( `${ block.name } Block`, () => {
	describe( `before compatibility notice is dismissed`, () => {
		beforeAll( async () => {
			// make sure CartCheckoutCompatibilityNotice will appear
			await page.evaluate( () => {
				localStorage.removeItem(
					'wc-blocks_dismissed_compatibility_notices'
				);
			} );
			await visitBlockPage( `${ block.name } Block` );
		} );

		it( 'shows compatibility notice', async () => {
			const compatibilityNoticeTitle = await page.$x(
				`//h1[contains(text(), 'Compatibility notice')]`
			);
			expect( compatibilityNoticeTitle.length ).toBe( 1 );
		} );
	} );

	describe( 'after compatibility notice is dismissed', () => {
		beforeAll( async () => {
			await page.evaluate( () => {
				localStorage.setItem(
					'wc-blocks_dismissed_compatibility_notices',
					'["mini-cart"]'
				);
			} );
			await switchUserToAdmin();
			await visitBlockPage( `${ block.name } Block` );
		} );

		afterAll( async () => {
			await page.evaluate( () => {
				localStorage.removeItem(
					'wc-blocks_dismissed_compatibility_notices'
				);
			} );
		} );
		it( 'can only be inserted once', async () => {
			await insertBlockDontWaitForInsertClose( block.name );
			expect( await getAllBlocks() ).toHaveLength( 1 );
		} );

		it( 'renders without crashing', async () => {
			await expect( page ).toRenderBlock( block );
		} );
	} );
} );
