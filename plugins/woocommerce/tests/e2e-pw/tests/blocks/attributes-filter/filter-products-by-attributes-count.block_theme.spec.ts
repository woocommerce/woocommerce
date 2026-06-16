/**
 * External dependencies
 */
import { test as base, expect, PostCompiler } from '@woocommerce/e2e-utils';

const test = base.extend< {
	postCompiler: PostCompiler;
} >( {
	postCompiler: async ( { requestUtils }, use ) => {
		const post = await requestUtils.createPostFromFile(
			'filters-with-all-products'
		);

		await use( post );
	},
} );

test.describe( 'Filter by Attributes Block - with All products Block', () => {
	test( 'should show correct attrs count (color=blue|query_type_color=or)', async ( {
		page,
		postCompiler,
	} ) => {
		const post = await postCompiler.compile( {} );

		await page.goto(
			`${ post.link }?filter_color=blue&query_type_color=or`
		);

		const expectedValues = [ '4', '2', '3', '4', '1' ];

		await expect(
			page
				.locator( 'ul.wc-block-attribute-filter-list' )
				.first()
				.locator(
					'> li:not([class^="is-loading"]) .wc-filter-element-label-list-count > span:not([class^="screen-reader"])'
				)
		).toHaveText( expectedValues );
	} );

	test( 'should show correct attrs count (color=blue,gray|query_type_color=or)', async ( {
		page,
		postCompiler,
	} ) => {
		const post = await postCompiler.compile( {} );

		await page.goto(
			`${ post.link }?filter_color=blue,gray&query_type_color=or`
		);

		const expectedValues = [ '4', '2', '3', '4', '1' ];

		await expect(
			page
				.locator( 'ul.wc-block-attribute-filter-list' )
				.first()
				.locator(
					'> li:not([class^="is-loading"]) .wc-filter-element-label-list-count > span:not([class^="screen-reader"])'
				)
		).toHaveText( expectedValues );
	} );

	test( 'should show correct attrs count (color=blue|query_type_color=or|min_price=15|max_price=40)', async ( {
		page,
		postCompiler,
	} ) => {
		const post = await postCompiler.compile( {} );

		await page.goto(
			`${ post.link }?filter_color=blue&query_type_color=or&min_price=15&max_price=40`
		);

		const expectedValues = [ '2', '2', '2', '3', '1' ];

		await expect(
			page
				.locator( 'ul.wc-block-attribute-filter-list' )
				.first()
				.locator(
					'> li:not([class^="is-loading"]) .wc-filter-element-label-list-count > span:not([class^="screen-reader"])'
				)
		).toHaveText( expectedValues );
	} );
} );
