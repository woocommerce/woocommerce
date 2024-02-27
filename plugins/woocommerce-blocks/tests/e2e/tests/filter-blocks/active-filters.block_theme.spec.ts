/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import { Post } from '@wordpress/e2e-test-utils-playwright/build-types/request-utils/posts';
import path from 'path';

const TEMPLATE_PATH = path.join( __dirname, './active-filters.handlebars' );

const test = base.extend< {
	defaultBlockPost: Post;
} >( {
	defaultBlockPost: async ( { requestUtils }, use ) => {
		const testingPost = await requestUtils.createPostFromTemplate(
			{ title: 'Active Filters Block' },
			TEMPLATE_PATH,
			{}
		);

		await use( testingPost );
		await requestUtils.deletePost( testingPost.id );
	},
} );

test.describe( 'Product Filter: Active Filters Block', async () => {
	test.describe( 'frontend', () => {
		test( 'Without any filters selected, only a wrapper block is rendered', async ( {
			page,
			defaultBlockPost,
		} ) => {
			const locator = page.locator(
				'.wp-block-woocommerce-product-filter'
			);

			await expect( locator ).toHaveCount( 1 );

			const html = await locator.innerHTML();
			expect( html.trim() ).toBe( '' );
		} );

		test( 'With rating filters applied it shows the correct active filters', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto( `${ defaultBlockPost.link }?rating_filter=1,2,5` );

			const hasTitle =
				( await page.locator( 'text=Rating:' ).count() ) === 1;

			expect( hasTitle ).toBe( true );

			for ( const text of [
				'Rated 1 out of 5',
				'Rated 2 out of 5',
				'Rated 5 out of 5',
			] ) {
				const hasFilter =
					( await page.locator( `text=${ text }` ).count() ) === 1;
				expect( hasFilter ).toBe( true );
			}
		} );

		test( 'With stock filters applied it shows the correct active filters', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto(
				`${ defaultBlockPost.link }?filter_stock_status=instock,onbackorder`
			);

			const hasTitle =
				( await page.locator( 'text=Stock Status:' ).count() ) === 1;

			expect( hasTitle ).toBe( true );

			for ( const text of [ 'In stock', 'On backorder' ] ) {
				const hasFilter =
					( await page.locator( `text=${ text }` ).count() ) === 1;
				expect( hasFilter ).toBe( true );
			}
		} );

		test( 'With attribute filters applied it shows the correct active filters', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto(
				`${ defaultBlockPost.link }?filter_color=blue,gray&query_type_color=or`
			);

			const hasTitle =
				( await page.locator( 'text=Color:' ).count() ) === 1;

			expect( hasTitle ).toBe( true );

			for ( const text of [ 'Blue', 'Gray' ] ) {
				const hasFilter =
					( await page.locator( `text=${ text }` ).count() ) === 1;
				expect( hasFilter ).toBe( true );
			}
		} );

		test( 'With price filters applied it shows the correct active filters', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto(
				`${ defaultBlockPost.link }?min_price=17&max_price=71`
			);

			const hasTitle =
				( await page.locator( 'text=Price:' ).count() ) === 1;

			expect( hasTitle ).toBe( true );

			const hasFilter =
				( await page.locator( `text=Between $17 and $71` ).count() ) ===
				1;
			expect( hasFilter ).toBe( true );
		} );
	} );
} );
