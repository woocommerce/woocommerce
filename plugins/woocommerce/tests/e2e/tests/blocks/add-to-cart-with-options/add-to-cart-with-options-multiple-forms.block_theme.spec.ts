/**
 * External dependencies
 */
import { test as base, expect, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from './add-to-cart-with-options.page';
import ProductCollectionPage from '../product-collection/product-collection.page';

const test = base.extend< {
	pageObject: AddToCartWithOptionsPage;
	productCollectionPageObject: ProductCollectionPage;
} >( {
	pageObject: async ( { page, admin, editor }, use ) => {
		const pageObject = new AddToCartWithOptionsPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
	productCollectionPageObject: async ( { page, admin, editor }, use ) => {
		const pageObject = new ProductCollectionPage( { page, admin, editor } );
		await use( pageObject );
	},
} );

test.describe( 'Add to Cart + Options Block: Multiple forms on one page', () => {
	test.describe( 'Two forms directly in the Single Product Template', () => {
		test.beforeEach( async () => {
			const variableResult = await wpCLI(
				`wc product create --user=1 --porcelain --name="Cross-form Variable Product" --slug="cross-form-variable-product" --type="variable" --attributes='${ JSON.stringify(
					[
						{
							name: 'Color',
							options: [ 'Blue', 'Green' ],
							visible: true,
							variation: true,
						},
						{
							name: 'Size',
							options: [ 'Small', 'Medium' ],
							visible: true,
							variation: true,
						},
					]
				) }'`
			);
			const variableProductId = variableResult.stdout.match(
				/(\d+)\s*$/
			)?.[ 1 ] as string;

			await wpCLI(
				`wc product_variation create ${ variableProductId } --user=1 --porcelain --regular_price="10.00" --attributes='${ JSON.stringify(
					[
						{ name: 'Color', option: 'Blue' },
						{ name: 'Size', option: 'Small' },
					]
				) }'`
			);
			await wpCLI(
				`wc product_variation create ${ variableProductId } --user=1 --porcelain --regular_price="12.00" --attributes='${ JSON.stringify(
					[
						{ name: 'Color', option: 'Green' },
						{ name: 'Size', option: 'Medium' },
					]
				) }'`
			);
		} );

		test( 'submitting the first form can add the variation selected in the second', async ( {
			page,
			editor,
			pageObject,
			frontendUtils,
		} ) => {
			await pageObject.updateSingleProductTemplate();

			const addToCartWithOptionsBlock = await editor.getBlockByName(
				pageObject.BLOCK_SLUG
			);
			await editor.selectBlocks( addToCartWithOptionsBlock );
			await editor.clickBlockOptionsMenuItem( 'Duplicate' );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await frontendUtils.emptyCart();
			await page.goto( '/product/cross-form-variable-product/' );

			const forms = page.locator( '.wp-block-add-to-cart-with-options' );
			await expect( forms ).toHaveCount( 2 );
			const firstForm = forms.nth( 0 );
			const secondForm = forms.nth( 1 );

			await firstForm
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Blue', exact: true } )
				.click();
			await firstForm
				.getByRole( 'radiogroup', { name: 'Size' } )
				.getByRole( 'radio', { name: 'Small', exact: true } )
				.click();

			await secondForm
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Green', exact: true } )
				.click();
			await secondForm
				.getByRole( 'radiogroup', { name: 'Size' } )
				.getByRole( 'radio', { name: 'Medium', exact: true } )
				.click();

			await firstForm
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();

			// Today, both forms share one page-wide `variationId` (set by
			// whichever form's variation selector ran last), while each
			// form's posted attributes stay its own. The first form ends up
			// posting a variation id that does not match its own Blue/Small
			// attributes, and the request is rejected outright rather than
			// adding either variation.
			await expect(
				page.getByText(
					'Invalid value posted for Color. Allowed values: Blue, Green'
				)
			).toBeVisible();

			await frontendUtils.goToCart();
			await expect(
				page.getByText( 'Your cart is currently empty!' )
			).toBeVisible();
		} );

		test( "submitting the second form invalid duplicates the first form's validation notice instead of clearing it", async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.updateSingleProductTemplate();

			const addToCartWithOptionsBlock = await editor.getBlockByName(
				pageObject.BLOCK_SLUG
			);
			await editor.selectBlocks( addToCartWithOptionsBlock );
			await editor.clickBlockOptionsMenuItem( 'Duplicate' );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( '/product/cross-form-variable-product/' );

			const forms = page.locator( '.wp-block-add-to-cart-with-options' );
			const firstForm = forms.nth( 0 );
			const secondForm = forms.nth( 1 );
			const missingAttributesNotice = page.getByText(
				'Please select product attributes before adding to cart.'
			);

			await firstForm
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();
			await expect( missingAttributesNotice ).toHaveCount( 1 );

			// Today, submitting the second form invalid tries to clear
			// "its" notices by id, but reads the notice list from its own
			// (empty) local context instead of the page-wide tracking array
			// the ids came from, so the removal silently fails. The second
			// form then adds its own copy of the same message: two identical
			// notices end up on the page instead of one being replaced.
			await secondForm
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();
			await expect( missingAttributesNotice ).toHaveCount( 2 );

			// Correcting and successfully submitting the second form does not
			// clear any notice either — the success path never touches
			// `noticeIds` at all. Both copies of the first form's notice
			// (never corrected) are still shown.
			await secondForm
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Green', exact: true } )
				.click();
			await secondForm
				.getByRole( 'radiogroup', { name: 'Size' } )
				.getByRole( 'radio', { name: 'Medium', exact: true } )
				.click();
			await secondForm
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();
			await expect( page.getByText( '1 in cart' ) ).toBeVisible();
			await expect( missingAttributesNotice ).toHaveCount( 2 );
		} );
	} );

	test.describe( 'Two forms sharing a grouped child', () => {
		let groupedAId: string;
		let groupedBId: string;
		let sharedChildId: string;
		let sharedChildName: string;

		test.beforeEach( async () => {
			sharedChildName = 'Cross-form Shared Child';
			const childResult = await wpCLI(
				`wc product create --user=1 --porcelain --name="${ sharedChildName }" --slug="cross-form-shared-child" --type="simple" --regular_price="3.00"`
			);
			sharedChildId = childResult.stdout.match(
				/(\d+)\s*$/
			)?.[ 1 ] as string;

			const groupedAResult = await wpCLI(
				`wc product create --user=1 --porcelain --name="Cross-form Grouped A" --slug="cross-form-grouped-a" --type="grouped" --grouped_products='${ JSON.stringify(
					[ Number( sharedChildId ) ]
				) }'`
			);
			groupedAId = groupedAResult.stdout.match(
				/(\d+)\s*$/
			)?.[ 1 ] as string;

			const groupedBResult = await wpCLI(
				`wc product create --user=1 --porcelain --name="Cross-form Grouped B" --slug="cross-form-grouped-b" --type="grouped" --grouped_products='${ JSON.stringify(
					[ Number( sharedChildId ) ]
				) }'`
			);
			groupedBId = groupedBResult.stdout.match(
				/(\d+)\s*$/
			)?.[ 1 ] as string;
		} );

		test( "the shared child's quantity input id is duplicated, and clicking its label in the second form focuses the first form's input", async ( {
			page,
			admin,
			editor,
			pageObject,
		} ) => {
			await admin.createNewPost();

			await editor.insertBlock( {
				name: 'woocommerce/single-product',
				attributes: { productId: Number( groupedAId ) },
			} );
			await pageObject.updateAddToCartWithOptionsBlock();

			await editor.insertBlock( {
				name: 'woocommerce/single-product',
				attributes: { productId: Number( groupedBId ) },
			} );
			await pageObject.updateAddToCartWithOptionsBlock();

			const postId = await editor.publishPost();
			await page.goto( `/?p=${ postId }` );

			const ids = await page
				.locator( '[id]' )
				.evaluateAll( ( elements ) =>
					elements.map( ( element ) => element.id )
				);
			const occurrences = new Map< string, number >();
			for ( const id of ids ) {
				occurrences.set( id, ( occurrences.get( id ) ?? 0 ) + 1 );
			}
			const duplicatedIds = [ ...occurrences.entries() ].filter(
				( [ , count ] ) => count > 1
			);

			// Today, the shared child renders `id="quantity_<childId>"` (and a
			// matching `for`) in every form that includes it, so the page ends
			// up with the same id twice.
			expect( duplicatedIds.length ).toBeGreaterThan( 0 );
			expect( duplicatedIds.map( ( [ id ] ) => id ) ).toContain(
				`quantity_${ sharedChildId }`
			);

			const forms = page.locator(
				'[data-block-name="woocommerce/single-product"]'
			);
			const firstForm = forms.nth( 0 );
			const secondForm = forms.nth( 1 );

			await secondForm
				.getByText( sharedChildName, { exact: true } )
				.click();

			// A browser resolves `label[for]` against the first element in the
			// document with that id, so clicking the second form's label
			// focuses the first form's input instead of its own.
			await expect( secondForm.locator( ':focus' ) ).toHaveCount( 0 );
			await expect( firstForm.locator( ':focus' ) ).toHaveCount( 1 );
		} );
	} );

	test.describe( 'First paint of two forms sharing a Product Collection', () => {
		test.beforeEach( async ( { requestUtils } ) => {
			// Gives T-Shirt a minimum purchase quantity of 4 (its default is
			// 1), so the two fixture products have different minimums.
			await requestUtils.activatePlugin(
				'woocommerce-blocks-test-quantity-constraints'
			);

			const variableResult = await wpCLI(
				`wc product create --user=1 --porcelain --name="Cross-form First Paint Fixture" --slug="cross-form-first-paint-fixture" --sku="cross-form-first-paint-fixture" --type="variable" --attributes='${ JSON.stringify(
					[
						{
							name: 'Style',
							options: [ 'Available', 'Unavailable' ],
							visible: true,
							variation: true,
						},
					]
				) }'`
			);
			const variableProductId =
				variableResult.stdout.match( /(\d+)\s*$/ )?.[ 1 ];

			await wpCLI(
				`wc product_variation create ${ variableProductId } --user=1 --porcelain --regular_price="10.00" --attributes='${ JSON.stringify(
					[ { name: 'Style', option: 'Available' } ]
				) }'`
			);
			await wpCLI(
				`wc product_variation create ${ variableProductId } --user=1 --porcelain --regular_price="10.00" --manage_stock=true --in_stock=false --attributes='${ JSON.stringify(
					[ { name: 'Style', option: 'Unavailable' } ]
				) }'`
			);
		} );

		test( "the two forms' first-paint validity, out-of-stock message and quantity are page-wide instead of each form's own, and ids stay stable across reloads", async ( {
			page,
			browser,
			editor,
			admin,
			productCollectionPageObject,
		} ) => {
			await admin.createNewPost();
			await productCollectionPageObject.insertProductCollection();
			await productCollectionPageObject.chooseCollectionInPost(
				'handPicked'
			);

			const productPicker = editor.canvas.locator(
				'.wc-block-editor-product-collection__product-picker'
			);
			// T-Shirt is picked last, so it is the last form rendered.
			await productPicker
				.getByRole( 'checkbox', {
					name: 'Cross-form First Paint Fixture (cross-form-first-paint-fixture)',
				} )
				.click();
			await productPicker
				.getByRole( 'checkbox', { name: 'T-Shirt (woo-tshirt)' } )
				.click();
			await productPicker
				.locator( '.components-button.is-primary' )
				.click();

			const postId = await editor.publishPost();

			// The block editor's Product Template does not allow inserting
			// Add to Cart with Options there (it is designed for a single
			// product's page, not a loop: `canInsertBlockType` returns
			// false), so the Product Button block is published as usual and
			// then swapped for Add to Cart with Options directly in the
			// saved post content. The frontend renderer does not enforce
			// that editor-only restriction.
			const swapProductButtonPhp =
				`$post = get_post(${ postId }); ` +
				'$content = preg_replace("#<!--\\s*wp:woocommerce/product-button[^>]*/-->#", "<!-- wp:woocommerce/add-to-cart-with-options /-->", $post->post_content, 1); ' +
				`wp_update_post(array("ID" => ${ postId }, "post_content" => $content));`;
			await wpCLI( `eval '${ swapProductButtonPhp }'` );

			const noJsContext = await browser.newContext( {
				javaScriptEnabled: false,
			} );
			try {
				const noJsPage = await noJsContext.newPage();
				await noJsPage.goto( `/?p=${ postId }` );

				const forms = noJsPage.locator(
					'.wp-block-add-to-cart-with-options'
				);
				await expect( forms ).toHaveCount( 2 );

				const quantityInputs = forms.locator(
					'input[name="quantity"]'
				);
				const quantityValues = await quantityInputs.evaluateAll(
					( inputs: HTMLInputElement[] ) =>
						inputs.map( ( input ) => input.value )
				);

				// Today, both forms bind their quantity input's value to the
				// same page-wide `state.inputQuantity`, seeded by whichever
				// form rendered last: the T-Shirt fixture's minimum of 4,
				// not the first form's own minimum of 1.
				expect( quantityValues ).toEqual( [ '4', '4' ] );

				const submitButtons = forms.locator( 'button[type="submit"]' );
				const submitButtonsHidden = await submitButtons.evaluateAll(
					( buttons: HTMLButtonElement[] ) =>
						buttons.map( ( button ) => button.hidden )
				);

				// Today, both forms' submit buttons bind their `hidden`
				// attribute to the same page-wide `state.allowsAddingToCart`.
				// Both end up hidden on first paint, even though the T-Shirt
				// fixture (a simple, in-stock, purchasable product) should
				// always allow adding to cart on its own.
				expect( submitButtonsHidden ).toEqual( [ true, true ] );
			} finally {
				await noJsContext.close();
			}

			// With JavaScript enabled, the ids rendered on two separate,
			// full page loads are compared.
			await page.goto( `/?p=${ postId }` );
			const firstLoadIds = await page
				.locator( '[id]' )
				.evaluateAll( ( elements ) =>
					elements.map( ( element ) => element.id )
				);

			await page.goto( `/?p=${ postId }` );
			const secondLoadIds = await page
				.locator( '[id]' )
				.evaluateAll( ( elements ) =>
					elements.map( ( element ) => element.id )
				);

			// Today the two collections are not identical: the variation
			// selector's attribute label id and both forms' quantity input
			// ids are generated fresh on every render, so a client-side
			// navigation that swaps this markup back in would not find the
			// same ids it had before leaving.
			expect( secondLoadIds ).not.toEqual( firstLoadIds );
		} );
	} );
} );
