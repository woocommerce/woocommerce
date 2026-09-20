/**
 * External dependencies
 */
import { test, expect, wpCLI } from '@woocommerce/e2e-utils';

/**
 * `wc_interactivity_api_load_product`,
 * `wc_interactivity_api_load_purchasable_child_products` and
 * `wc_interactivity_api_load_variations` are global functions third-party
 * code can call directly, outside of any WooCommerce block. This spec
 * proves each one still has the name and the signature it has on the base
 * branch, by calling all three from a fixture plugin
 * (`woocommerce-blocks-test-loader-functions`) the same way the blocks that
 * ship in this codebase do — same argument list, same consent statement —
 * and checking the data they return lands where the unified `woocommerce`
 * store reads it. A signature change (a renamed, added or reordered
 * parameter) would fatal the fixture's call, leaving the page without the
 * markup this test asserts on.
 */
test.describe( 'Global Interactivity API loader functions', () => {
	test( 'load a simple product, a grouped product’s purchasable children and a variable product’s variations into state.products and state.productVariations', async ( {
		browser,
		requestUtils,
	} ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-loader-functions'
		);

		const simpleResult = await wpCLI(
			`wc product create --user=1 --porcelain --name="Loader Functions Simple Fixture" --slug="loader-functions-simple-fixture" --sku="loader-functions-simple-sku" --regular_price="12.00"`
		);
		const simpleId = simpleResult.stdout.match( /(\d+)\s*$/ )?.[ 1 ];
		expect( simpleId ).toBeTruthy();

		const childAResult = await wpCLI(
			`wc product create --user=1 --porcelain --name="Loader Functions Grouped Child A" --slug="loader-functions-grouped-child-a" --sku="loader-functions-grouped-child-a-sku" --regular_price="7.00"`
		);
		const childAId = childAResult.stdout.match( /(\d+)\s*$/ )?.[ 1 ];
		expect( childAId ).toBeTruthy();

		const childBResult = await wpCLI(
			`wc product create --user=1 --porcelain --name="Loader Functions Grouped Child B" --slug="loader-functions-grouped-child-b" --sku="loader-functions-grouped-child-b-sku" --regular_price="9.00"`
		);
		const childBId = childBResult.stdout.match( /(\d+)\s*$/ )?.[ 1 ];
		expect( childBId ).toBeTruthy();

		const groupedResult = await wpCLI(
			`wc product create --user=1 --porcelain --name="Loader Functions Grouped Fixture" --slug="loader-functions-grouped-fixture" --type="grouped" --grouped_products='${ JSON.stringify(
				[ Number( childAId ), Number( childBId ) ]
			) }'`
		);
		const groupedId = groupedResult.stdout.match( /(\d+)\s*$/ )?.[ 1 ];
		expect( groupedId ).toBeTruthy();

		const variableResult = await wpCLI(
			`wc product create --user=1 --porcelain --name="Loader Functions Variable Fixture" --slug="loader-functions-variable-fixture" --type="variable" --attributes='${ JSON.stringify(
				[
					{
						name: 'Style',
						options: [ 'Classic' ],
						visible: true,
						variation: true,
					},
				]
			) }'`
		);
		const variableId = variableResult.stdout.match( /(\d+)\s*$/ )?.[ 1 ];
		expect( variableId ).toBeTruthy();

		const variationResult = await wpCLI(
			`wc product_variation create ${ variableId } --user=1 --porcelain --regular_price="24.00" --sku="loader-functions-variation-sku" --attributes='${ JSON.stringify(
				[ { name: 'Style', option: 'Classic' } ]
			) }'`
		);
		const variationId = variationResult.stdout.match( /(\d+)\s*$/ )?.[ 1 ];
		expect( variationId ).toBeTruthy();

		const post = await requestUtils.createPost( {
			title: 'Loader functions fixture',
			status: 'publish',
			date_gmt: new Date().toISOString(),
			content: `[wc_iapi_loader_functions_test simple_id="${ simpleId }" grouped_id="${ groupedId }" variable_id="${ variableId }"]`,
		} );

		const noJsContext = await browser.newContext( {
			javaScriptEnabled: false,
		} );
		try {
			const noJsPage = await noJsContext.newPage();
			await noJsPage.goto( post.link );

			await test.step( 'wc_interactivity_api_load_product loads the simple product into state.products[ id ]', async () => {
				await expect(
					noJsPage.getByTestId( 'loader-simple-return-sku' )
				).toHaveText( 'loader-functions-simple-sku' );
				await expect(
					noJsPage.getByTestId( 'loader-simple-state-sku' )
				).toHaveText( 'loader-functions-simple-sku' );
			} );

			await test.step( 'wc_interactivity_api_load_purchasable_child_products loads the grouped product’s children into state.products[ id ]', async () => {
				await expect(
					noJsPage.getByTestId(
						`loader-grouped-child-return-sku-${ childAId }`
					)
				).toHaveText( 'loader-functions-grouped-child-a-sku' );
				await expect(
					noJsPage.getByTestId(
						`loader-grouped-child-state-sku-${ childAId }`
					)
				).toHaveText( 'loader-functions-grouped-child-a-sku' );
				await expect(
					noJsPage.getByTestId(
						`loader-grouped-child-return-sku-${ childBId }`
					)
				).toHaveText( 'loader-functions-grouped-child-b-sku' );
				await expect(
					noJsPage.getByTestId(
						`loader-grouped-child-state-sku-${ childBId }`
					)
				).toHaveText( 'loader-functions-grouped-child-b-sku' );
			} );

			await test.step( 'wc_interactivity_api_load_variations loads the variable product’s variation into state.productVariations[ id ]', async () => {
				await expect(
					noJsPage.getByTestId(
						`loader-variation-return-sku-${ variationId }`
					)
				).toHaveText( 'loader-functions-variation-sku' );
				await expect(
					noJsPage.getByTestId(
						`loader-variation-state-sku-${ variationId }`
					)
				).toHaveText( 'loader-functions-variation-sku' );
			} );
		} finally {
			await noJsContext.close();
		}
	} );
} );
