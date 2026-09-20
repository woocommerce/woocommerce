/**
 * External dependencies
 */
import { Locator } from '@playwright/test';
import {
	test as base,
	expect,
	wpCLI,
	getPostIdBySlug,
} from '@woocommerce/e2e-utils';

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

		test( 'submitting the first form adds only its own selection, and the second form keeps its own', async ( {
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

			// Each form now declares its own scope on the server (D5, D6), so
			// submitting the first form posts only its own selection.
			await expect(
				firstForm.getByRole( 'button', { name: '1 in cart' } )
			).toBeVisible();

			// The second form's own selection is untouched by the first
			// form's submit.
			await expect(
				secondForm
					.getByRole( 'radiogroup', { name: 'Color' } )
					.getByRole( 'radio', { name: 'Green', exact: true } )
			).toBeChecked();
			await expect(
				secondForm
					.getByRole( 'radiogroup', { name: 'Size' } )
					.getByRole( 'radio', { name: 'Medium', exact: true } )
			).toBeChecked();

			await frontendUtils.goToCart();
			await expect(
				page.getByLabel(
					'Quantity of Cross-form Variable Product in your cart.'
				)
			).toHaveValue( '1' );
			await expect( page.getByText( 'Color: Blue' ) ).toBeVisible();
			await expect( page.getByText( 'Size: Small' ) ).toBeVisible();
			await expect( page.getByText( 'Color: Green' ) ).toHaveCount( 0 );
			await expect( page.getByText( 'Size: Medium' ) ).toHaveCount( 0 );
		} );

		test( "a form's validation notice is cleared only by that form", async ( {
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
			// The notices region wraps its own form (it is the form's parent,
			// not a descendant), so each form's notice is scoped through the
			// region that wraps it, in the same document order as the forms.
			const noticeRegions = page.locator(
				'.wc-block-components-notices'
			);
			const noticeText =
				'Please select product attributes before adding to cart.';
			const firstFormNotice = noticeRegions
				.nth( 0 )
				.getByText( noticeText );
			const secondFormNotice = noticeRegions
				.nth( 1 )
				.getByText( noticeText );

			await firstForm
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();
			await expect( firstFormNotice ).toBeVisible();

			// Each form now owns its own notices context (D11), so the second
			// form's invalid submit adds its own notice without touching the
			// first form's.
			await secondForm
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();
			await expect( secondFormNotice ).toBeVisible();
			await expect( firstFormNotice ).toBeVisible();

			// Correcting and successfully submitting the second form does not
			// affect the first form's notice either — only the first form's
			// own submit can clear it.
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
			await expect( firstFormNotice ).toBeVisible();

			await firstForm
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Blue', exact: true } )
				.click();
			await firstForm
				.getByRole( 'radiogroup', { name: 'Size' } )
				.getByRole( 'radio', { name: 'Small', exact: true } )
				.click();
			await firstForm
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();

			// Only the first form's own corrected submit clears its notice.
			await expect( firstFormNotice ).toHaveCount( 0 );
		} );
	} );

	test.describe( 'Two Single Product blocks for one product', () => {
		test( "each Single Product block's own form drives that block's own price and gallery", async ( {
			page,
			admin,
			editor,
			pageObject,
		} ) => {
			const hoodieId = await getPostIdBySlug( 'hoodie' );

			await admin.createNewPost();

			await editor.insertBlock( {
				name: 'woocommerce/single-product',
				attributes: { productId: Number( hoodieId ) },
			} );
			await pageObject.updateAddToCartWithOptionsBlock();

			await editor.insertBlock( {
				name: 'woocommerce/single-product',
				attributes: { productId: Number( hoodieId ) },
			} );
			await pageObject.updateAddToCartWithOptionsBlock();

			const postId = await editor.publishPost();
			await page.goto( `/?p=${ postId }` );

			const blocks = page.locator(
				'[data-block-name="woocommerce/single-product"]'
			);
			const firstBlock = blocks.nth( 0 );
			const secondBlock = blocks.nth( 1 );

			const selectVariation = async (
				block: Locator,
				color: string,
				logo: string
			) => {
				await block
					.getByRole( 'radiogroup', { name: 'Color' } )
					.getByRole( 'radio', { name: color, exact: true } )
					.click();
				await block
					.getByRole( 'radiogroup', { name: 'Logo' } )
					.getByRole( 'radio', { name: logo, exact: true } )
					.click();
			};

			// The Single Product block's default template puts a Product
			// Gallery block beside the form (D5); reading the gallery's own
			// visible large image, scoped to one block, shows which
			// variation's image that block's own form selected.
			const galleryImageIds = ( block: Locator ) =>
				block
					.locator(
						'.wc-block-product-gallery-large-image__wrapper:not([hidden]) img[data-image-id]'
					)
					.evaluateAll( ( images ) =>
						images.map( ( image ) =>
							image.getAttribute( 'data-image-id' )
						)
					);

			await selectVariation( firstBlock, 'Blue', 'No' );

			await expect(
				firstBlock.locator( '.wp-block-woocommerce-product-price' )
			).toHaveText( '$45.00' );
			await expect( async () => {
				expect( await galleryImageIds( firstBlock ) ).toContain( '35' );
			} ).toPass();

			// The second block's own form is untouched by the first block's
			// selection: its price still shows the product's unselected
			// range.
			await expect(
				secondBlock.locator( '.wp-block-woocommerce-product-price' )
			).toHaveText( /\$42\.00 – \$45\.00.*/ );

			await selectVariation( secondBlock, 'Red', 'No' );

			await expect(
				secondBlock.locator( '.wp-block-woocommerce-product-price' )
			).toHaveText( /\$42\.00/ );
			await expect( async () => {
				expect( await galleryImageIds( secondBlock ) ).toContain(
					'34'
				);
			} ).toPass();

			// The first block's own price and gallery are unaffected by the
			// second block's later selection.
			await expect(
				firstBlock.locator( '.wp-block-woocommerce-product-price' )
			).toHaveText( '$45.00' );
			await expect( async () => {
				expect( await galleryImageIds( firstBlock ) ).toContain( '35' );
			} ).toPass();
		} );
	} );

	test.describe( 'Two Product Collection blocks each showing one product', () => {
		test.beforeEach( async () => {
			const variableResult = await wpCLI(
				`wc product create --user=1 --porcelain --name="Cross-form Collection Product" --slug="cross-form-collection-product" --type="variable" --attributes='${ JSON.stringify(
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

		test( "submitting the first collection tile's form adds only its own selection, and the second tile keeps its own", async ( {
			page,
			admin,
			editor,
			frontendUtils,
			productCollectionPageObject,
		} ) => {
			await admin.createNewPost();

			// A single collection cannot render the same product in two
			// tiles (its query returns each product once), so two
			// Product Collection blocks are used, one tile each.
			for ( let i = 0; i < 2; i++ ) {
				await productCollectionPageObject.insertProductCollection();
				await productCollectionPageObject.chooseCollectionInPost(
					'handPicked'
				);
				const productPicker = editor.canvas.locator(
					'.wc-block-editor-product-collection__product-picker'
				);
				await productPicker
					.getByLabel( 'Search for products to display' )
					.fill( 'Cross-form Collection Product' );
				await productPicker
					.getByRole( 'checkbox', {
						name: 'Cross-form Collection Product',
						exact: true,
					} )
					.click();
				await productPicker
					.locator( '.components-button.is-primary' )
					.click();
			}

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
				'$content = preg_replace("#<!--\\s*wp:woocommerce/product-button[^>]*/-->#", "<!-- wp:woocommerce/add-to-cart-with-options /-->", $post->post_content); ' +
				`wp_update_post(array("ID" => ${ postId }, "post_content" => $content));`;
			await wpCLI( `eval '${ swapProductButtonPhp }'` );

			await page.goto( `/?p=${ postId }` );

			const collections = page.locator(
				'[data-block-name="woocommerce/product-collection"]'
			);
			const firstCollection = collections.nth( 0 );
			const secondCollection = collections.nth( 1 );

			await firstCollection
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Blue', exact: true } )
				.click();
			await firstCollection
				.getByRole( 'radiogroup', { name: 'Size' } )
				.getByRole( 'radio', { name: 'Small', exact: true } )
				.click();

			await secondCollection
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Green', exact: true } )
				.click();
			await secondCollection
				.getByRole( 'radiogroup', { name: 'Size' } )
				.getByRole( 'radio', { name: 'Medium', exact: true } )
				.click();

			await firstCollection
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();
			await expect(
				firstCollection.getByRole( 'button', { name: '1 in cart' } )
			).toBeVisible();

			// The second collection's tile keeps its own selection.
			await expect(
				secondCollection
					.getByRole( 'radiogroup', { name: 'Color' } )
					.getByRole( 'radio', { name: 'Green', exact: true } )
			).toBeChecked();
			await expect(
				secondCollection
					.getByRole( 'radiogroup', { name: 'Size' } )
					.getByRole( 'radio', { name: 'Medium', exact: true } )
			).toBeChecked();

			await frontendUtils.goToCart();
			await expect(
				page.getByLabel(
					'Quantity of Cross-form Collection Product in your cart.'
				)
			).toHaveValue( '1' );
			await expect( page.getByText( 'Color: Blue' ) ).toBeVisible();
			await expect( page.getByText( 'Size: Small' ) ).toBeVisible();
			await expect( page.getByText( 'Color: Green' ) ).toHaveCount( 0 );
			await expect( page.getByText( 'Size: Medium' ) ).toHaveCount( 0 );
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

		test( "the shared child's quantity input id is unique per form, and clicking its label in the second form focuses the second form's input", async ( {
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

			// Each form's shared child now derives its ids from that form's
			// own page-unique scope name (D6), so no id repeats across the
			// two forms.
			expect( duplicatedIds ).toEqual( [] );

			const forms = page.locator(
				'[data-block-name="woocommerce/single-product"]'
			);
			const firstForm = forms.nth( 0 );
			const secondForm = forms.nth( 1 );

			await secondForm
				.getByText( sharedChildName, { exact: true } )
				.click();

			// A page-unique id per form means the label now resolves against
			// its own form's input.
			await expect( secondForm.locator( ':focus' ) ).toHaveCount( 1 );
			await expect( firstForm.locator( ':focus' ) ).toHaveCount( 0 );
		} );

		test( 'setting the shared child to different quantities in each grouped form adds each group its own quantity', async ( {
			page,
			admin,
			editor,
			frontendUtils,
			pageObject,
		} ) => {
			// An extra child left untouched in both groups, so an add that
			// only posts touched children is observable.
			const untouchedChildResult = await wpCLI(
				`wc product create --user=1 --porcelain --name="Cross-form Untouched Child" --slug="cross-form-untouched-child" --type="simple" --regular_price="1.00"`
			);
			const untouchedChildId = untouchedChildResult.stdout.match(
				/(\d+)\s*$/
			)?.[ 1 ] as string;
			await wpCLI(
				`wc product update ${ groupedAId } --user=1 --grouped_products='${ JSON.stringify(
					[ Number( sharedChildId ), Number( untouchedChildId ) ]
				) }'`
			);
			await wpCLI(
				`wc product update ${ groupedBId } --user=1 --grouped_products='${ JSON.stringify(
					[ Number( sharedChildId ), Number( untouchedChildId ) ]
				) }'`
			);

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

			const forms = page.locator(
				'[data-block-name="woocommerce/single-product"]'
			);
			const firstForm = forms.nth( 0 );
			const secondForm = forms.nth( 1 );

			await firstForm
				.getByLabel( `Increase quantity of ${ sharedChildName }` )
				.click();
			await firstForm
				.getByLabel( `Increase quantity of ${ sharedChildName }` )
				.click();
			await secondForm
				.getByLabel( `Increase quantity of ${ sharedChildName }` )
				.click();

			await firstForm
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();
			await expect(
				firstForm.getByRole( 'button', {
					name: 'Added to cart',
					exact: true,
				} )
			).toBeVisible();

			// The first form's own submit does not touch the second form's
			// still-untouched-by-a-submit quantity.
			await expect(
				secondForm.getByRole( 'spinbutton', {
					name: sharedChildName,
				} )
			).toHaveValue( '1' );

			await secondForm
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();
			await expect(
				secondForm.getByRole( 'button', {
					name: 'Added to cart',
					exact: true,
				} )
			).toBeVisible();

			// Each form's own scope kept its own quantity through both
			// submits.
			await expect(
				firstForm.getByRole( 'spinbutton', { name: sharedChildName } )
			).toHaveValue( '2' );
			await expect(
				secondForm.getByRole( 'spinbutton', {
					name: sharedChildName,
				} )
			).toHaveValue( '1' );

			await frontendUtils.goToCart();
			// The shared child is the same product either way it is added,
			// so its two group submits (quantity 2, then 1) land in the same
			// cart line, summing to 3.
			await expect(
				page.getByLabel(
					`Quantity of ${ sharedChildName } in your cart.`
				)
			).toHaveValue( '3' );
			// The untouched child, never given a quantity in either form, was
			// not added.
			await expect(
				page.getByText( 'Cross-form Untouched Child' )
			).toHaveCount( 0 );
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

		test( "each form's first-paint validity, out-of-stock message and quantity are its own, and ids stay stable across reloads", async ( {
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

				// Each form's quantity input is now seeded from its own
				// server-derived `initialQuantity` (D8): the fixture's own
				// minimum of 1, and T-Shirt's own minimum of 4.
				expect( quantityValues ).toEqual( [ '1', '4' ] );
			} finally {
				await noJsContext.close();
			}

			// Selecting the fixture's out-of-stock variation shows the
			// out-of-stock state in the fixture's own form only; T-Shirt's
			// form, for an unrelated product, is unaffected.
			await page.goto( `/?p=${ postId }` );
			const forms = page.locator( '.wp-block-add-to-cart-with-options' );
			const fixtureForm = forms.nth( 0 );
			const tShirtForm = forms.nth( 1 );
			await fixtureForm
				.getByRole( 'radiogroup', { name: 'Style' } )
				.getByRole( 'radio', { name: 'Unavailable', exact: true } )
				.click();
			await expect(
				fixtureForm.getByText( 'Out of stock' )
			).toBeVisible();
			await expect( tShirtForm.getByText( 'Out of stock' ) ).toHaveCount(
				0
			);

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

			// The server derives every id from the form's page-unique scope
			// name (D6), which is identical on every render, so the same
			// page yields the same ids on both loads.
			expect( secondLoadIds ).toEqual( firstLoadIds );
		} );
	} );
} );
