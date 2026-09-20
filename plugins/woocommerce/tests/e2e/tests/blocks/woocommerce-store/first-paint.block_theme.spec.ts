/**
 * External dependencies
 */
import { test as base, expect, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from '../add-to-cart-with-options/add-to-cart-with-options.page';
import ProductCollectionPage from '../product-collection/product-collection.page';

const test = base.extend< {
	pageObject: AddToCartWithOptionsPage;
	productCollectionPageObject: ProductCollectionPage;
} >( {
	pageObject: async ( { page, admin, editor }, use ) => {
		await use( new AddToCartWithOptionsPage( { page, admin, editor } ) );
	},
	productCollectionPageObject: async ( { page, admin, editor }, use ) => {
		await use( new ProductCollectionPage( { page, admin, editor } ) );
	},
} );

/**
 * Every value in R4's inventory is already correct in the server HTML on the
 * base branch; this spec proves the migration to the unified `woocommerce`
 * store does not change that. Each flow loads a page in a browser context
 * with JavaScript disabled, so only what the server rendered is observed.
 */
test.describe( 'First paint with JavaScript disabled', () => {
	test( "a variable product's page renders its own SKU, price, stock, quantity constraints, hidden variation id and variation description on the server, and a second product on the same template renders its own", async ( {
		browser,
		editor,
		pageObject,
	} ) => {
		const outOfStock = await wpCLI(
			`wc product create --user=1 --porcelain --name="First Paint Simple Fixture" --slug="first-paint-simple-fixture" --sku="first-paint-simple-sku" --regular_price="19.99" --in_stock=false`
		);
		expect( outOfStock.stdout.match( /(\d+)\s*$/ )?.[ 1 ] ).toBeTruthy();

		const variableResult = await wpCLI(
			`wc product create --user=1 --porcelain --name="First Paint Variable Fixture" --slug="first-paint-variable-fixture" --sku="first-paint-variable-sku" --type="variable" --manage_stock=true --stock_quantity=8 --backorders=no --attributes='${ JSON.stringify(
				[
					{
						name: 'Style',
						options: [ 'Classic', 'Limited' ],
						visible: true,
						variation: true,
					},
				]
			) }'`
		);
		const variableId = variableResult.stdout.match( /(\d+)\s*$/ )?.[ 1 ];

		await wpCLI(
			`wc product_variation create ${ variableId } --user=1 --porcelain --regular_price="24.00" --sku="first-paint-variable-classic-sku" --description="Classic variation description" --attributes='${ JSON.stringify(
				[ { name: 'Style', option: 'Classic' } ]
			) }'`
		);
		await wpCLI(
			`wc product_variation create ${ variableId } --user=1 --porcelain --regular_price="29.00" --sku="first-paint-variable-limited-sku" --manage_stock=true --in_stock=false --description="Limited variation description" --attributes='${ JSON.stringify(
				[ { name: 'Style', option: 'Limited' } ]
			) }'`
		);

		// This applies to the whole Single Product Template, so both
		// products below are rendered by the same, single template.
		await pageObject.updateSingleProductTemplate();
		await expect(
			editor.canvas
				.getByLabel( `Block: ${ pageObject.BLOCK_NAME }` )
				.first()
		).toBeVisible();
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		const noJsContext = await browser.newContext( {
			javaScriptEnabled: false,
		} );
		try {
			const noJsPage = await noJsContext.newPage();

			await test.step( "the simple product's own SKU, price and stock indicator resolve", async () => {
				await noJsPage.goto( '/product/first-paint-simple-fixture/' );

				await expect(
					noJsPage.locator( '.wc-block-components-product-sku .sku' )
				).toHaveText( 'first-paint-simple-sku' );
				await expect(
					noJsPage.locator(
						'[data-block-name="woocommerce/product-price"]'
					)
				).toContainText( '19.99' );
				await expect(
					noJsPage.locator(
						'.wc-block-components-product-stock-indicator'
					)
				).toContainText( 'Out of stock' );
			} );

			await test.step( "the variable product's own SKU, quantity constraints, hidden variation id and hidden variation description resolve, distinct from the simple product's", async () => {
				await noJsPage.goto( '/product/first-paint-variable-fixture/' );

				await expect(
					noJsPage.locator( '.wc-block-components-product-sku .sku' )
				).toHaveText( 'first-paint-variable-sku' );

				const quantityInput = noJsPage.locator(
					'.wp-block-add-to-cart-with-options-quantity-selector input[name="quantity"]'
				);
				await expect( quantityInput ).toHaveAttribute( 'min', '1' );
				await expect( quantityInput ).toHaveAttribute( 'max', '8' );
				await expect( quantityInput ).toHaveAttribute( 'step', '1' );

				// No variation is selected on first paint, so the hidden
				// `variation_id` input's server-resolved value is empty:
				// the same state the base branch renders.
				const variationIdInput = noJsPage.locator(
					'input[name="variation_id"]'
				);
				await expect( variationIdInput ).toHaveCount( 1 );
				expect(
					await variationIdInput.getAttribute( 'value' )
				).toBeNull();

				// With no variation selected, the Variation Description
				// block stays hidden and empty, exactly as it does before
				// any script runs on the base branch.
				const variationDescription = noJsPage.locator(
					'.wp-block-woocommerce-add-to-cart-with-options-variation-description'
				);
				await expect( variationDescription ).toBeHidden();
				await expect( variationDescription ).toBeEmpty();
			} );
		} finally {
			await noJsContext.close();
		}
	} );

	test( "a grouped product's Single Product block renders each child's own selector and the product's specifications on the server", async ( {
		browser,
		editor,
		admin,
		pageObject,
	} ) => {
		const childA = await wpCLI(
			`wc product create --user=1 --porcelain --name="First Paint Grouped Child A" --slug="first-paint-grouped-child-a" --sku="first-paint-grouped-child-a-sku" --regular_price="7.00"`
		);
		const childAId = childA.stdout.match( /(\d+)\s*$/ )?.[ 1 ];
		const childB = await wpCLI(
			`wc product create --user=1 --porcelain --name="First Paint Grouped Child B" --slug="first-paint-grouped-child-b" --sku="first-paint-grouped-child-b-sku" --regular_price="9.00"`
		);
		const childBId = childB.stdout.match( /(\d+)\s*$/ )?.[ 1 ];

		await wpCLI(
			`wc product create --user=1 --porcelain --name="First Paint Grouped Fixture" --slug="first-paint-grouped-fixture" --sku="first-paint-grouped-fixture-sku" --type="grouped" --weight="1.5" --grouped_products='${ JSON.stringify(
				[ Number( childAId ), Number( childBId ) ]
			) }'`
		);

		// The grouped product is embedded in a regular post, which is not
		// itself a product: the Single Product block has to resolve its
		// own bound product rather than fall back to any page-wide state.
		await admin.createNewPost();
		await editor.insertBlock( { name: 'woocommerce/single-product' } );
		const singleProductBlock = await editor.getBlockByName(
			'woocommerce/single-product'
		);
		await singleProductBlock
			.locator(
				'input[type="radio"][value="first-paint-grouped-fixture"]'
			)
			.nth( 0 )
			.click();
		await singleProductBlock.getByText( 'Done' ).click();

		await pageObject.updateAddToCartWithOptionsBlock();

		// Insert next to the Product Price block: the Single Product
		// block's own inner blocks area is a fixed template, so the new
		// block goes inside the column that holds Price, alongside it.
		const productPriceBlock = await editor.getBlockByName(
			'woocommerce/product-price'
		);
		const priceParentClientId =
			( await productPriceBlock.getAttribute( 'data-block' ) ) ?? '';
		const priceParentRootClientId =
			( await editor.getBlockRootClientId( priceParentClientId ) ) ?? '';
		await editor.selectBlocks( productPriceBlock );
		await editor.insertBlock(
			{ name: 'woocommerce/product-specifications' },
			{ clientId: priceParentRootClientId }
		);
		await expect(
			editor.canvas.getByLabel( 'Block: Product Specifications' )
		).toBeVisible();

		const postId = await editor.publishPost();

		const noJsContext = await browser.newContext( {
			javaScriptEnabled: false,
		} );
		try {
			const noJsPage = await noJsContext.newPage();
			await noJsPage.goto( `/?p=${ postId }` );

			const childAInput = noJsPage.locator(
				`input[name="quantity[${ childAId }]"]`
			);
			const childBInput = noJsPage.locator(
				`input[name="quantity[${ childBId }]"]`
			);
			await expect( childAInput ).toHaveCount( 1 );
			await expect( childBInput ).toHaveCount( 1 );

			const childAInputId = await childAInput.getAttribute( 'id' );
			const childBInputId = await childBInput.getAttribute( 'id' );
			expect( childAInputId ).not.toBeNull();
			expect( childBInputId ).not.toBeNull();
			expect( childAInputId ).not.toEqual( childBInputId );

			// Each row's label references its own child's input, and shows
			// its own child's name, not the other row's.
			await expect(
				noJsPage.locator( `label[for="${ childAInputId }"]` )
			).toHaveText( 'First Paint Grouped Child A' );
			await expect(
				noJsPage.locator( `label[for="${ childBInputId }"]` )
			).toHaveText( 'First Paint Grouped Child B' );

			await expect(
				noJsPage.locator(
					'.wp-block-woocommerce-product-specifications'
				)
			).toContainText( '1.5' );
		} finally {
			await noJsContext.close();
		}
	} );

	test( "a Product Collection's Product Template and the legacy Products block each resolve every product's own SKU and price on the server", async ( {
		browser,
		editor,
		admin,
		productCollectionPageObject,
	} ) => {
		const productA = await wpCLI(
			`wc product create --user=1 --porcelain --name="First Paint PT Fixture A" --slug="first-paint-pt-fixture-a" --sku="first-paint-pt-a-sku" --regular_price="11.11"`
		);
		const productAId = Number(
			productA.stdout.match( /(\d+)\s*$/ )?.[ 1 ]
		);
		const productB = await wpCLI(
			`wc product create --user=1 --porcelain --name="First Paint PT Fixture B" --slug="first-paint-pt-fixture-b" --sku="first-paint-pt-b-sku" --regular_price="22.22"`
		);
		const productBId = Number(
			productB.stdout.match( /(\d+)\s*$/ )?.[ 1 ]
		);

		let productTemplatePostId: number;

		await test.step( 'Product Collection', async () => {
			await admin.createNewPost();
			await productCollectionPageObject.insertProductCollection();
			await productCollectionPageObject.chooseCollectionInPost(
				'handPicked'
			);

			const productPicker = editor.canvas.locator(
				'.wc-block-editor-product-collection__product-picker'
			);
			await productPicker
				.getByRole( 'checkbox', {
					name: 'First Paint PT Fixture A (first-paint-pt-a-sku)',
				} )
				.click();
			await productPicker
				.getByRole( 'checkbox', {
					name: 'First Paint PT Fixture B (first-paint-pt-b-sku)',
				} )
				.click();
			await productPicker
				.locator( '.components-button.is-primary' )
				.click();

			await productCollectionPageObject.insertBlockInProductCollection( {
				name: 'woocommerce/product-sku',
				attributes: {},
			} );

			productTemplatePostId = await editor.publishPost();
		} );

		let legacyProductsPostId: number;

		await test.step( 'the legacy Products (Deprecated) block', async () => {
			await admin.createNewPost();
			await editor.insertBlock( {
				name: 'core/query',
				attributes: {
					namespace: 'woocommerce/product-query',
					query: {
						inherit: false,
						postType: 'product',
						perPage: 2,
						include: [ productAId, productBId ],
					},
				},
				innerBlocks: [
					{
						name: 'core/post-template',
						attributes: {
							__woocommerceNamespace:
								'woocommerce/product-query/product-template',
						},
						innerBlocks: [
							{ name: 'woocommerce/product-image' },
							{
								name: 'core/post-title',
								attributes: {
									__woocommerceNamespace:
										'woocommerce/product-query/product-title',
								},
							},
							{ name: 'woocommerce/product-sku' },
							{ name: 'woocommerce/product-price' },
						],
					},
					{ name: 'core/query-pagination' },
					{ name: 'core/query-no-results' },
				],
			} );

			legacyProductsPostId = await editor.publishPost();
		} );

		const noJsContext = await browser.newContext( {
			javaScriptEnabled: false,
		} );
		try {
			const noJsPage = await noJsContext.newPage();

			await test.step( "Product Template resolves each tile's own product", async () => {
				await noJsPage.goto( `/?p=${ productTemplatePostId }` );

				const items = noJsPage.locator(
					'.wc-block-product-template .wc-block-product'
				);
				await expect( items ).toHaveCount( 2 );

				await expect(
					items
						.filter( { hasText: 'First Paint PT Fixture A' } )
						.locator( '.wc-block-components-product-sku .sku' )
				).toHaveText( 'first-paint-pt-a-sku' );
				await expect(
					items
						.filter( { hasText: 'First Paint PT Fixture B' } )
						.locator( '.wc-block-components-product-sku .sku' )
				).toHaveText( 'first-paint-pt-b-sku' );
			} );

			await test.step( "the legacy Products block resolves each item's own product", async () => {
				await noJsPage.goto( `/?p=${ legacyProductsPostId }` );

				await expect(
					noJsPage
						.locator( `li.post-${ productAId }` )
						.locator( '.wc-block-components-product-sku .sku' )
				).toHaveText( 'first-paint-pt-a-sku' );
				await expect(
					noJsPage
						.locator( `li.post-${ productBId }` )
						.locator( '.wc-block-components-product-sku .sku' )
				).toHaveText( 'first-paint-pt-b-sku' );
				await expect(
					noJsPage
						.locator( `li.post-${ productAId }` )
						.locator(
							'[data-block-name="woocommerce/product-price"]'
						)
				).toContainText( '11.11' );
				await expect(
					noJsPage
						.locator( `li.post-${ productBId }` )
						.locator(
							'[data-block-name="woocommerce/product-price"]'
						)
				).toContainText( '22.22' );
			} );
		} finally {
			await noJsContext.close();
		}
	} );

	test( 'Product Button shows the server\'s "N in cart" label before any script runs', async ( {
		page,
		browser,
		frontendUtils,
	} ) => {
		const productName = 'First Paint Product Button Fixture';
		await wpCLI(
			`wc product create --user=1 --porcelain --name="${ productName }" --slug="first-paint-product-button-fixture" --regular_price="9.99"`
		);

		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( productName );
		await frontendUtils.addToCart( productName );

		const currentUrl = page.url();
		const cookies = await page.context().cookies();
		const noJsContext = await browser.newContext( {
			javaScriptEnabled: false,
		} );
		try {
			await noJsContext.addCookies( cookies );
			const noJsPage = await noJsContext.newPage();
			await noJsPage.goto( currentUrl );

			await expect(
				noJsPage.getByText( '2 in cart', { exact: true } )
			).toBeVisible();
		} finally {
			await noJsContext.close();
		}
	} );

	test( 'Mini-Cart shows rows, badge count, subtotal, tax label and button label from the server before any script runs', async ( {
		page,
		browser,
		frontendUtils,
		requestUtils,
	} ) => {
		await frontendUtils.emptyCart();

		await wpCLI(
			`wc product create --user=1 --porcelain --name="First Paint Mini Cart Simple" --slug="first-paint-mini-cart-simple" --regular_price="15.00"`
		);
		const variableResult = await wpCLI(
			`wc product create --user=1 --porcelain --name="First Paint Mini Cart Variable" --slug="first-paint-mini-cart-variable" --type="variable" --attributes='${ JSON.stringify(
				[
					{
						name: 'Size',
						options: [ 'Small' ],
						visible: true,
						variation: true,
					},
				]
			) }'`
		);
		const variableId = variableResult.stdout.match( /(\d+)\s*$/ )?.[ 1 ];
		await wpCLI(
			`wc product_variation create ${ variableId } --user=1 --porcelain --regular_price="25.00" --attributes='${ JSON.stringify(
				[ { name: 'Size', option: 'Small' } ]
			) }'`
		);

		const { id: taxRateId } = await requestUtils.rest< { id: number } >( {
			method: 'POST',
			path: 'wc/v3/taxes',
			data: {
				rate: '20',
				name: 'First paint mini-cart tax rate',
				class: 'standard',
			},
		} );

		try {
			await requestUtils.rest( {
				method: 'PUT',
				path: 'wc/v3/settings/general/woocommerce_calc_taxes',
				data: { value: 'yes' },
			} );

			await frontendUtils.goToShop();
			await frontendUtils.addToCart( 'First Paint Mini Cart Simple' );

			await page.goto( '/product/first-paint-mini-cart-variable/' );
			await page
				.locator( 'select[name="attribute_size"]' )
				.selectOption( 'Small' );
			// The classic variable-product form is a plain HTML form (no
			// AJAX): submitting it reloads the page, which the store notice
			// assertion below waits out.
			await page.getByRole( 'button', { name: 'Add to cart' } ).click();
			await expect(
				page.getByText( /has been added to your cart/ )
			).toBeVisible();

			const cookies = await page.context().cookies();
			const noJsContext = await browser.newContext( {
				javaScriptEnabled: false,
			} );
			try {
				await noJsContext.addCookies( cookies );
				const noJsPage = await noJsContext.newPage();
				// `/mini-cart/` renders the Mini-Cart with its price and tax
				// label always shown (`hasHiddenPrice: false`), unlike the
				// header instance, which hides them by default.
				await noJsPage.goto( '/mini-cart/' );

				// The drawer this Mini-Cart instance renders into is closed
				// by default (opening it is a client-side action), so its
				// contents are hidden rather than absent. `toContainText`
				// and `toHaveText` read the server-rendered text regardless
				// of that CSS visibility; `toBeVisible` is reserved for the
				// badge, which is shown on the closed button itself.
				const miniCart = noJsPage
					.getByTestId( 'mini-cart' )
					.locator( '.wc-block-mini-cart' )
					.first();

				await expect( miniCart ).toContainText(
					'First Paint Mini Cart Simple'
				);
				await expect( miniCart ).toContainText(
					'First Paint Mini Cart Variable'
				);

				const badge = miniCart.locator( '.wc-block-mini-cart__badge' );
				await expect( badge ).toBeVisible();
				await expect( badge ).toHaveText( '2' );

				await expect(
					miniCart.locator( '.wc-block-mini-cart__amount' )
				).toContainText( '$' );
				await expect(
					noJsPage.locator(
						'.wc-block-mini-cart__footer-subtotal .wc-block-components-totals-item__value'
					)
				).toContainText( '$' );

				await expect(
					miniCart.locator( '.wc-block-mini-cart__tax-label' )
				).not.toBeEmpty();

				const button = miniCart.locator(
					'.wc-block-mini-cart__button'
				);
				await expect( button ).toHaveAttribute(
					'aria-label',
					/Number of items in the cart: 2/
				);
			} finally {
				await noJsContext.close();
			}
		} finally {
			try {
				await requestUtils.rest( {
					method: 'PUT',
					path: 'wc/v3/settings/general/woocommerce_calc_taxes',
					data: { value: 'yes' },
				} );
			} finally {
				await requestUtils.rest( {
					method: 'DELETE',
					path: `wc/v3/taxes/${ taxRateId }`,
					params: { force: true },
				} );
			}
		}
	} );

	test( "Mini-Cart shows the server's count when a woocommerce_cart_contents_count filter is installed", async ( {
		page,
		browser,
		frontendUtils,
		requestUtils,
	} ) => {
		await frontendUtils.emptyCart();
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-cart-contents-count-filter'
		);

		const productName = 'First Paint Filtered Count Fixture';
		await wpCLI(
			`wc product create --user=1 --porcelain --name="${ productName }" --slug="first-paint-filtered-count-fixture" --regular_price="5.00"`
		);

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( productName );

		const cookies = await page.context().cookies();
		const noJsContext = await browser.newContext( {
			javaScriptEnabled: false,
		} );
		try {
			await noJsContext.addCookies( cookies );
			const noJsPage = await noJsContext.newPage();
			await noJsPage.goto( '/mini-cart/' );

			const badge = noJsPage
				.locator( '.wc-block-mini-cart__badge' )
				.first();
			await expect( badge ).toHaveText( '999' );
		} finally {
			await noJsContext.close();
		}
	} );

	test( 'Mini-Cart shows a server cart error notice in the drawer before any script runs', async ( {
		page,
		browser,
		frontendUtils,
	} ) => {
		const productName = 'First Paint Stock Drop Fixture';
		const result = await wpCLI(
			`wc product create --user=1 --porcelain --name="${ productName }" --slug="first-paint-stock-drop-fixture" --regular_price="10.00" --manage_stock=true --stock_quantity=1`
		);
		const productId = result.stdout.match( /(\d+)\s*$/ )?.[ 1 ];

		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( productName );

		// Drop stock below the cart's quantity while it's already in the
		// cart, producing a server-side cart error on the next load.
		await wpCLI(
			`wc product update ${ productId } --stock_quantity=0 --in_stock=false --user=1`
		);

		const cookies = await page.context().cookies();
		const noJsContext = await browser.newContext( {
			javaScriptEnabled: false,
		} );
		try {
			await noJsContext.addCookies( cookies );
			const noJsPage = await noJsContext.newPage();
			await noJsPage.goto( '/mini-cart/' );

			const notice = noJsPage
				.locator( '.wc-block-components-notice-banner' )
				.first();
			await expect( notice ).toBeVisible();
		} finally {
			await noJsContext.close();
		}
	} );

	test( 'a page with a non-empty cart is marked no-cache', async ( {
		browser,
	} ) => {
		const productName = 'First Paint No-Cache Fixture';
		await wpCLI(
			`wc product create --user=1 --porcelain --name="${ productName }" --slug="first-paint-no-cache-fixture" --regular_price="8.00"`
		);

		const guestContext = await browser.newContext();
		try {
			const guestPage = await guestContext.newPage();
			await guestPage.goto( '/shop/' );

			const addedToCartResponse = guestPage.waitForResponse(
				( response ) => response.url().includes( 'wc/store/v1/batch' )
			);
			await guestPage
				.getByLabel( `Add to cart: “${ productName }”` )
				.click();
			await addedToCartResponse;

			const response = await guestPage.goto( '/shop/' );
			const cacheControl = response?.headers()[ 'cache-control' ] ?? '';
			expect( cacheControl ).toContain( 'no-cache' );
		} finally {
			await guestContext.close();
		}
	} );
} );
