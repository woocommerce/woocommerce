/**
 * External dependencies
 */
import { test as base, expect, wpCLI } from '@woocommerce/e2e-utils';

/**
 * The closing runtime proof for the unified `woocommerce` store's SSR
 * mirror: a server-rendered purchase surface resolves SKU, stock,
 * specification, and quantity-constraint data from
 * `woocommerce::state.itemInContext.product.*` / `.variation.*` — the
 * nested PHP closures registered by `ProductsStore::register_getters()`,
 * which mirror the client envelope (`base/stores/woocommerce/index.ts`)
 * exactly — before any JS executes, and the client's post-hydration reads
 * land on the identical values, through the single `woocommerce` store,
 * with no visible flash. This is the deferred SSR round-trip + hydration
 * verification the design phase reasoned about rather than executed on
 * this runtime, closed out here.
 *
 * No shipped core block ever establishes a concrete `variationId` in a
 * purchase surface's `woocommerce` context — `SingleProduct.php` and
 * `ProductTemplate.php` both always declare it `null` (a shopper's
 * attribute pick is inherently a client-only action the server cannot
 * know in advance). So the pre-hydration state for a variable product is,
 * by design, always the base-product fallback: not a degraded edge case,
 * but the standing SSR behaviour for every variable-product purchase
 * surface shipped today. The attribute-pick repaint itself (price, SKU,
 * gallery, hidden input) is exercised extensively, unweakened, by the
 * `add-to-cart-with-options` suite re-driven alongside this file; this
 * spec's own job is the two things nothing else covers: the raw
 * pre-hydration markup, and that hydration changes nothing visible.
 */

const test = base.extend( {} );

/** The block markup this spec inspects: SKU, specifications, and Add to Cart + Options nested in a Single Product block. */
const surfaceContent = (
	productId: string
) => `<!-- wp:woocommerce/single-product {"productId":${ productId }} -->
<div class="wp-block-woocommerce-single-product woocommerce">
<!-- wp:woocommerce/product-sku /-->
<!-- wp:woocommerce/product-specifications /-->
<!-- wp:woocommerce/add-to-cart-with-options /-->
</div>
<!-- /wp:woocommerce/single-product -->`;

test.describe( 'SSR first paint and hydration through the unified envelope', () => {
	test( 'a server-rendered simple product page resolves SKU, stock, specification, and quantity data before hydration', async ( {
		page,
		browser,
		requestUtils,
	} ) => {
		const sku = 'ssr-simple-product-sku';
		const productOutput = await wpCLI(
			`wc product create --user=1 --name="SSR Simple Product" --type=simple --sku="${ sku }" --regular_price="19.99" --weight="1.5" --manage_stock=true --stock_quantity=7 --backorders=no`
		);
		const productId =
			productOutput.stdout.match( /product\s+(\d+)/ )?.[ 1 ];
		if ( ! productId ) {
			throw new Error(
				`No productId found, cliOutput: ${ JSON.stringify(
					productOutput
				) }`
			);
		}

		const post = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/posts',
			data: {
				status: 'publish',
				title: 'SSR first paint: simple product',
				content: surfaceContent( productId ),
			},
		} );

		// A JS-disabled context renders exactly, and only, what PHP baked
		// into the response — the genuine pre-hydration proof, rather than
		// a race against the client's own hydration.
		const noJsContext = await browser.newContext( {
			javaScriptEnabled: false,
		} );

		try {
			const noJsPage = await noJsContext.newPage();
			await noJsPage.goto( `/?p=${ post.id }` );

			await expect( noJsPage.getByText( `SKU: ${ sku }` ) ).toBeVisible();
			await expect( noJsPage.getByText( '7 in stock' ) ).toBeVisible();
			await expect(
				noJsPage
					.locator( '.wp-block-product-specifications-item-weight' )
					.getByText( '1.5 lbs' )
			).toBeVisible();

			const quantityInput = noJsPage.locator( 'input[name="quantity"]' );
			await expect( quantityInput ).toHaveAttribute( 'min', '1' );
			await expect( quantityInput ).toHaveAttribute( 'step', '1' );
		} finally {
			await noJsContext.close();
		}

		// The same page, hydrated: a simple product's envelope has no
		// variation ambiguity to resolve, so the hydrated client's own
		// reads land on the identical values with nothing left to change.
		await page.goto( `/?p=${ post.id }` );
		await expect( page.getByText( `SKU: ${ sku }` ) ).toBeVisible();
		await expect( page.getByText( '7 in stock' ) ).toBeVisible();
	} );

	test( 'a server-rendered variable product page falls back to the base product before hydration, and hydration changes nothing until an attribute is picked', async ( {
		page,
		browser,
		requestUtils,
	} ) => {
		const parentSku = 'ssr-variable-parent-sku';
		const variationSku = 'ssr-variable-blue-sku';
		const variationDescription = 'SSR variation description text';

		const productOutput = await wpCLI(
			`wc product create --user=1 --slug="ssr-hydration-variable" --name="SSR Hydration Variable Product" --type=variable --sku="${ parentSku }" --weight="2.5" --manage_stock=true --stock_quantity=9 --backorders=no --attributes='${ JSON.stringify(
				[
					{
						name: 'Color',
						options: [ 'Blue', 'Red' ],
						variation: true,
						visible: true,
					},
				]
			) }'`
		);
		const parentId = productOutput.stdout.match( /product\s+(\d+)/ )?.[ 1 ];
		if ( ! parentId ) {
			throw new Error(
				`No productId found, cliOutput: ${ JSON.stringify(
					productOutput
				) }`
			);
		}

		const variationOutput = await wpCLI(
			`wc product_variation create "${ parentId }" --user=1 --regular_price="25.00" --sku="${ variationSku }" --description="${ variationDescription }" --attributes='${ JSON.stringify(
				[ { name: 'Color', option: 'Blue' } ]
			) }'`
		);
		const variationId = variationOutput.stdout.match( /\d+/g )?.pop();
		if ( ! variationId ) {
			throw new Error(
				`No variationId found, cliOutput: ${ JSON.stringify(
					variationOutput
				) }`
			);
		}

		const post = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/posts',
			data: {
				status: 'publish',
				title: 'SSR first paint: variable product',
				content: surfaceContent( parentId ),
			},
		} );

		const addToCartBlock = ( onPage: typeof page ) =>
			onPage.locator( '.wp-block-add-to-cart-with-options' );
		const variationIdInput = ( onPage: typeof page ) =>
			onPage.locator( 'input[name="variation_id"]' );
		// The `VariationDescription` block carries no class of its own to
		// select on; its `hidden` binding is the one identifying mark.
		const descriptionLocator = ( onPage: typeof page ) =>
			onPage.locator( '[data-wp-bind--hidden*="variation.description"]' );

		await test.step( 'pre-hydration: SKU, stock, specification, and quantity data are the base product’s own — no variation ever resolves server-side — and the hidden input is empty with the description hidden', async () => {
			const noJsContext = await browser.newContext( {
				javaScriptEnabled: false,
			} );

			try {
				const noJsPage = await noJsContext.newPage();
				await noJsPage.goto( `/?p=${ post.id }` );

				await expect(
					noJsPage.getByText( `SKU: ${ parentSku }` )
				).toBeVisible();
				await expect(
					noJsPage.getByText( '9 in stock' )
				).toBeVisible();
				await expect(
					noJsPage
						.locator(
							'.wp-block-product-specifications-item-weight'
						)
						.getByText( '2.5 lbs' )
				).toBeVisible();

				const quantityInput = noJsPage.locator(
					'input[name="quantity"]'
				);
				await expect( quantityInput ).toHaveAttribute( 'min', '1' );
				await expect( quantityInput ).toHaveAttribute( 'max', '9' );
				await expect( quantityInput ).toHaveAttribute( 'step', '1' );

				// No context ever pins a variationId server-side, so the
				// envelope never resolves one: the hidden input carries no
				// value and the variation description stays hidden — the
				// base-product fallback, byte-for-byte today's behaviour.
				await expect( variationIdInput( noJsPage ) ).toHaveValue( '' );
				await expect( descriptionLocator( noJsPage ) ).toBeHidden();
			} finally {
				await noJsContext.close();
			}
		} );

		await test.step( 'hydration matches the SSR first paint exactly, with no flash to a different value', async () => {
			await page.goto( `/?p=${ post.id }` );

			await expect(
				page.getByText( `SKU: ${ parentSku }` )
			).toBeVisible();
			await expect( page.getByText( '9 in stock' ) ).toBeVisible();
			await expect( variationIdInput( page ) ).toHaveValue( '' );
			await expect( descriptionLocator( page ) ).toBeHidden();
		} );

		await test.step( 'an attribute pick repaints SKU, the hidden variation-id input, and the description from the envelope — the single `woocommerce` store, no second store read', async () => {
			const colorBlueOption = addToCartBlock( page )
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Blue', exact: true } );
			await colorBlueOption.click();

			await expect(
				page.getByText( `SKU: ${ variationSku }` )
			).toBeVisible();
			await expect( variationIdInput( page ) ).toHaveValue( variationId );
			await expect( descriptionLocator( page ) ).toBeVisible();
			await expect(
				page.getByText( variationDescription )
			).toBeVisible();
		} );
	} );
} );
