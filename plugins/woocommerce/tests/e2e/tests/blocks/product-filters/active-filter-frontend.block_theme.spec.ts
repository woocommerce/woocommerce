/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

// The post's Product Collection has `queryId: 1`, so its filters read
// `wcp-1-` prefixed URL params.
const withFilters = ( link: string, params: Record< string, string > ) => {
	const url = new URL( link );
	for ( const [ key, value ] of Object.entries( params ) ) {
		url.searchParams.set( `wcp-1-${ key }`, value );
	}
	return url.href;
};

test.describe( 'woocommerce/product-filter-active - Frontend', () => {
	let postLink: string;

	test.beforeEach( async ( { page, createPost } ) => {
		( { link: postLink } = await createPost( 'active-filters' ) );

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
		await page.goto( postLink );

		const chips = page.locator(
			'.wp-block-woocommerce-product-filter-active'
		);

		await expect( chips ).toHaveCount( 1 );
	} );

	test( 'With rating filters applied it shows the correct active filters', async ( {
		page,
	} ) => {
		await page.goto( withFilters( postLink, { rating_filter: '1,2,5' } ) );

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
			withFilters( postLink, {
				filter_stock_status: 'instock,onbackorder',
			} )
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
			withFilters( postLink, {
				filter_color: 'blue,gray',
				query_type_color: 'or',
			} )
		);

		await expect( page.getByText( 'Color: Blue' ).first() ).toBeVisible();
		await expect( page.getByText( 'Color: Gray' ).first() ).toBeVisible();
	} );

	test( 'With price filters applied it shows the correct active filters', async ( {
		page,
	} ) => {
		await page.goto(
			withFilters( postLink, { min_price: '17', max_price: '71' } )
		);

		await expect(
			page.getByText( 'Price: $17 - $71' ).first()
		).toBeVisible();
	} );
} );
