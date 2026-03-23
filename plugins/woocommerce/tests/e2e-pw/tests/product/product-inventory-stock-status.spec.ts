/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test as baseTest, expect, tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	product: async ( { restApi }, use ) => {
		let product;

		await restApi
			.post( `${ WC_API_PATH }/products`, {
				name: `Stock status test product ${ Date.now() }`,
				type: 'simple',
				regular_price: '10.00',
			} )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
			force: true,
		} );
	},
} );

test(
	'stock status dropdown has consistent height with other selects on WP 7.0+',
	{ tag: [ tags.GUTENBERG ] },
	async ( { page, product } ) => {
		await test.step(
			'navigate to the product Inventory tab',
			async () => {
				await page.goto(
					`wp-admin/post.php?post=${ product.id }&action=edit`
				);

				await page
					.getByRole( 'link' )
					.filter( { hasText: 'Inventory' } )
					.click();
			}
		);

		await test.step(
			'verify stock status select height and alignment',
			async () => {
				const stockStatusSelect = page.locator(
					'select#_stock_status'
				);
				await expect( stockStatusSelect ).toBeVisible();

				// On WP 7.0+ with the wc-wp-version-gte-70 body class,
				// the select should have height: 40px and line-height: 2
				// to match the WP 7.0 native form element sizing.
				const hasWp70Class = await page.evaluate( () =>
					document.body.classList.contains(
						'wc-wp-version-gte-70'
					)
				);

				if ( hasWp70Class ) {
					await expect( stockStatusSelect ).toHaveCSS(
						'height',
						'40px'
					);
				}

				// Regardless of WP version, verify the select is visible
				// and has a reasonable height (not collapsed or oversized).
				const height = await stockStatusSelect.evaluate(
					( el ) => el.getBoundingClientRect().height
				);
				expect( height ).toBeGreaterThanOrEqual( 28 );
				expect( height ).toBeLessThanOrEqual( 44 );
			}
		);
	}
);
