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

	test( 'Without any filters selected, active block should not be rendered', async ( {
		page,
	} ) => {
		await page.goto( '/shop' );

		const chips = page.locator(
			'.wp-block-woocommerce-product-filter-active'
		);

		await expect( chips ).toHaveCount( 1 );
	} );

	test( 'With rating filters applied it shows the correct active filters', async ( {
		page,
	} ) => {
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

	test( 'With stock filters applied it shows the correct active filters', async ( {
		page,
	} ) => {
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

	test( 'With attribute filters applied it shows the correct active filters', async ( {
		page,
	} ) => {
		await page.goto(
			`${ '/shop' }?filter_color=blue,gray&query_type_color=or`
		);

		await expect( page.getByText( 'Color: Blue' ).first() ).toBeVisible();
		await expect( page.getByText( 'Color: Gray' ).first() ).toBeVisible();
	} );

	for ( const [ min, max ] of [
		[ '17', '71' ],
		[ '17.5', '71.25' ],
	] ) {
		test( `Preserves price bounds ${ min } - ${ max } when removing another filter`, async ( {
			page,
		} ) => {
			await page.goto(
				`/shop?min_price=${ min }&max_price=${ max }&filter_stock_status=instock`
			);

			const priceChip = page.getByText( `Price: $${ min } - $${ max }`, {
				exact: true,
			} );
			await expect( priceChip ).toBeVisible();

			await page
				.getByRole( 'button', {
					name: 'Remove filter: Availability: In stock',
				} )
				.click();

			await expect( page ).toHaveURL( ( url ) => {
				return (
					url.searchParams.get( 'min_price' ) === min &&
					url.searchParams.get( 'max_price' ) === max &&
					! url.searchParams.has( 'filter_stock_status' )
				);
			} );
			await expect( priceChip ).toBeVisible();
		} );
	}
} );
