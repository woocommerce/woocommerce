/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

test.describe( 'Filter by Attributes Block - with All products Block', () => {
	test( 'should show correct attrs count (color=blue|query_type_color=or)', async ( {
		page,
	} ) => {
		await page.goto(
			'/active-filters-block/?filter_color=blue&query_type_color=or',
			{
				waitUntil: 'commit',
			}
		);

		// Check if the page has loaded successfully.
		await expect( page.getByText( 'Active Filters block' ) ).toBeVisible();

		const expectedValues = [ '4', '0', '2', '2', '0' ];

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
	} ) => {
		await page.goto(
			'/active-filters-block/?filter_color=blue,gray&query_type_color=or',
			{
				waitUntil: 'commit',
			}
		);

		// Check if the page has loaded successfully.
		await expect( page.getByText( 'Active Filters block' ) ).toBeVisible();

		const expectedValues = [ '4', '3', '2', '2', '0' ];

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
	} ) => {
		await page.goto(
			'/active-filters-block/?filter_color=blue&query_type_color=or&min_price=15&max_price=40',
			{
				waitUntil: 'commit',
			}
		);

		// Check if the page has loaded successfully.
		await expect( page.getByText( 'Active Filters block' ) ).toBeVisible();

		const expectedValues = [ '2', '0', '1', '1', '0' ];

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
