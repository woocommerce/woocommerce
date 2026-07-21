/**
 * External dependencies
 */
import { TemplateCompiler, test as base, expect } from '@woocommerce/e2e-utils';

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_active-filters'
		);
		await use( compiler );
	},
} );

test.describe( 'woocommerce/product-filter-active - Frontend', () => {
	test.beforeEach( async ( { page, templateCompiler } ) => {
		await templateCompiler.compile();

		await page.addInitScript( () => {
			// Mock the wc global variable.
			if ( typeof window.wc === 'undefined' ) {
				window.wc = {
					wcSettings: {
						getSetting() {
							return true;
						},
					},
				};
			}
		} );
	} );

	test( 'should show active filters for frontend query states', async ( {
		page,
	} ) => {
		await test.step( 'Without any filters selected, active block should not be rendered', async () => {
			await page.goto( '/shop' );

			const chips = page.locator(
				'.wp-block-woocommerce-product-filter-active'
			);

			await expect( chips ).toHaveCount( 1 );
		} );

		await test.step( 'With rating filters applied it shows the correct active filters', async () => {
			await page.goto( `${ '/shop' }?rating_filter=1,2,5` );

			await expect(
				page.getByText( 'Rating: Rated 1 out of 5' ).first()
			).toBeVisible();
			await expect(
				page.getByText( 'Rating: Rated 2 out of 5' ).first()
			).toBeVisible();
			await expect(
				page.getByText( 'Rating: Rated 5 out of 5' ).first()
			).toBeVisible();
		} );

		await test.step( 'With stock filters applied it shows the correct active filters', async () => {
			await page.goto(
				`${ '/shop' }?filter_stock_status=instock,onbackorder`
			);

			await expect(
				page.getByText( 'Availability: In stock' ).first()
			).toBeVisible();
			await expect(
				page.getByText( 'Availability: On backorder' ).first()
			).toBeVisible();
		} );

		await test.step( 'With attribute filters applied it shows the correct active filters', async () => {
			await page.goto(
				`${ '/shop' }?filter_color=blue,gray&query_type_color=or`
			);

			await expect(
				page.getByText( 'Color: Blue' ).first()
			).toBeVisible();
			await expect(
				page.getByText( 'Color: Gray' ).first()
			).toBeVisible();
		} );

		await test.step( 'With price filters applied it shows the correct active filters', async () => {
			await page.goto( `${ '/shop' }?min_price=17&max_price=71` );

			await expect(
				page.getByText( 'Price: $17 - $71' ).first()
			).toBeVisible();
		} );
	} );
} );
