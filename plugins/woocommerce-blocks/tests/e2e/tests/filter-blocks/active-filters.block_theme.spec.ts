/**
 * External dependencies
 */
import { TemplateCompiler, test as base, expect } from '@woocommerce/e2e-utils';

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_acvitve-filters'
		);
		await use( compiler );
	},
} );

test.describe( 'Product Filter: Active Filters Block', () => {
	test.describe( 'frontend', () => {
		test( 'Without any filters selected, only a wrapper block is rendered', async ( {
			page,
			templateCompiler,
		} ) => {
			await templateCompiler.compile();

			await page.goto( '/shop' );

			const locator = page.locator(
				'.wp-block-woocommerce-product-filter'
			);

			await expect( locator ).toHaveCount( 1 );

			const html = await locator.innerHTML();
			expect( html.trim() ).toBe( '' );
		} );

		test( 'With rating filters applied it shows the correct active filters', async ( {
			page,
			templateCompiler,
		} ) => {
			await templateCompiler.compile();

			await page.goto( `${ '/shop' }?rating_filter=1,2,5` );

			await expect( page.getByText( 'Rating:' ) ).toBeVisible();
			await expect( page.getByText( 'Rated 1 out of 5' ) ).toBeVisible();
			await expect( page.getByText( 'Rated 2 out of 5' ) ).toBeVisible();
			await expect( page.getByText( 'Rated 5 out of 5' ) ).toBeVisible();
		} );

		test( 'With stock filters applied it shows the correct active filters', async ( {
			page,
			templateCompiler,
		} ) => {
			await templateCompiler.compile();

			await page.goto(
				`${ '/shop' }?filter_stock_status=instock,onbackorder`
			);

			await expect( page.getByText( 'Stock Status:' ) ).toBeVisible();
			await expect( page.getByText( 'In stock' ) ).toBeVisible();
			await expect( page.getByText( 'On backorder' ) ).toBeVisible();
		} );

		test( 'With attribute filters applied it shows the correct active filters', async ( {
			page,
			templateCompiler,
		} ) => {
			await templateCompiler.compile();

			await page.goto(
				`${ '/shop' }?filter_color=blue,gray&query_type_color=or`
			);

			await expect( page.getByText( 'Color:' ) ).toBeVisible();
			await expect( page.getByText( 'Blue' ) ).toBeVisible();
			await expect( page.getByText( 'Gray' ) ).toBeVisible();
		} );

		test( 'With price filters applied it shows the correct active filters', async ( {
			page,
			templateCompiler,
		} ) => {
			await templateCompiler.compile();

			await page.goto( `${ '/shop' }?min_price=17&max_price=71` );

			await expect( page.getByText( 'Price:' ) ).toBeVisible();
			await expect(
				page.getByText( 'Between $17 and $71' )
			).toBeVisible();
		} );
	} );
} );
