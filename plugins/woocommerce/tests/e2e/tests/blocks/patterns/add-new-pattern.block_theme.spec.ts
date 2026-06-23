/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { addTestingBlocks, expectedTitles, expectedPrices } from './utils';

test.describe( 'Patterns in block theme', () => {
	test( 'Synced Pattern can be created with basic blocks', async ( {
		admin,
		editor,
	} ) => {
		await admin.createNewPattern( 'Woo Blocks Synced Pattern' );
		const { productTitles, productPrices } = await addTestingBlocks(
			editor
		);

		await expect( productTitles ).toHaveText( expectedTitles );
		await expect( productPrices ).toHaveText( expectedPrices );
	} );

	test( 'Unsynced Pattern can be created with basic blocks', async ( {
		admin,
		editor,
	} ) => {
		await admin.createNewPattern( 'Woo Blocks Unsynced Pattern', false );

		const { productTitles, productPrices } = await addTestingBlocks(
			editor
		);

		await expect( productTitles ).toHaveText( expectedTitles );
		await expect( productPrices ).toHaveText( expectedPrices );
	} );
} );
