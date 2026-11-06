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
		requestUtils,
	} ) => {
		const patternName = `Woo Blocks Synced Pattern ${
			test.info().repeatEachIndex
		}`;

		try {
			await admin.createNewPattern( patternName );
			const { productTitles, productPrices } =
				await addTestingBlocks( editor );

			await expect( productTitles ).toHaveText( expectedTitles );
			await expect( productPrices ).toHaveText( expectedPrices );
		} finally {
			const patterns: { id: number; title: { raw: string } }[] =
				await requestUtils.rest( {
					path: '/wp/v2/blocks',
					params: {
						search: patternName,
						status: 'any',
						context: 'edit',
						per_page: 100,
					},
				} );
			for ( const { id } of patterns.filter(
				( { title } ) => title.raw === patternName
			) ) {
				await requestUtils.rest( {
					method: 'DELETE',
					path: `/wp/v2/blocks/${ id }`,
					params: { force: true },
				} );
			}
		}
	} );
} );
