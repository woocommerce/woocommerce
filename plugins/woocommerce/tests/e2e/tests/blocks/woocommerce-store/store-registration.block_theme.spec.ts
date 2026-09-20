/**
 * External dependencies
 */
import { Page } from '@playwright/test';
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

// The exact acknowledgement string `@woocommerce/stores/woocommerce` passes
// to `store()`. A caller that already knows this string resolves the same
// registered store rather than being refused.
const storeConsent =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// The namespace the migration retired. A directive names a namespace either
// as `data-wp-interactive`'s own value, or as an explicit `<namespace>::`
// prefix on any other `data-wp-*` directive's value (the Interactivity API's
// own `nsPathRegExp`); a bare substring search would also flag the unrelated
// `woocommerce/products-by-attribute` block name.
const RETIRED_NAMESPACE = 'woocommerce/products';

type NamespaceAudit = {
	stateNamespaces: string[];
	directiveNamespaces: string[];
};

/**
 * Reads the namespaces the current page carries: every top-level key of the
 * serialized Interactivity API state (the
 * `wp-script-module-data-@wordpress/interactivity` JSON script tag), and
 * every namespace named by a `data-wp-*` directive in the rendered markup.
 */
async function auditNamespaces( page: Page ): Promise< NamespaceAudit > {
	return page.evaluate( () => {
		const stateScript = document.getElementById(
			'wp-script-module-data-@wordpress/interactivity'
		);
		let stateNamespaces: string[] = [];
		if ( stateScript?.textContent ) {
			try {
				const parsed = JSON.parse( stateScript.textContent ) as {
					state?: Record< string, unknown >;
				};
				stateNamespaces = Object.keys( parsed.state ?? {} );
			} catch {
				// No serialized state script on this page.
			}
		}

		const namespacedValue = /^([\w_/-]+)::/;
		const directiveNamespaces = new Set< string >();
		document.querySelectorAll( '*' ).forEach( ( element ) => {
			for ( const attribute of Array.from( element.attributes ) ) {
				if ( ! attribute.name.startsWith( 'data-wp-' ) ) {
					continue;
				}

				if ( attribute.name === 'data-wp-interactive' ) {
					try {
						const parsedValue = JSON.parse( attribute.value ) as {
							namespace?: string;
						};
						if ( typeof parsedValue?.namespace === 'string' ) {
							directiveNamespaces.add( parsedValue.namespace );
							continue;
						}
					} catch {
						// A bare namespace string, not JSON.
					}
					directiveNamespaces.add( attribute.value );
					continue;
				}

				const match = namespacedValue.exec( attribute.value );
				if ( match ) {
					directiveNamespaces.add( match[ 1 ] );
				}
			}
		} );

		return {
			stateNamespaces,
			directiveNamespaces: Array.from( directiveNamespaces ),
		};
	} );
}

type UnifiedStoreShape = {
	hasProducts: boolean;
	hasCart: boolean;
	hasProductScopes: boolean;
};

/**
 * From a script on the page — not from any WooCommerce block's own bundle —
 * calls `store( 'woocommerce', {}, { lock } )` with the same acknowledgement
 * string `@woocommerce/stores/woocommerce` uses, and reads `state.products`,
 * `state.cart` and `state.productScopes` back through the returned store.
 */
async function readUnifiedStoreState(
	page: Page,
	lock: string
): Promise< UnifiedStoreShape > {
	return page.evaluate( async ( consent ) => {
		const { store } = await import( '@wordpress/interactivity' );
		// Loading the module registers the unified store; a page that
		// carries no WooCommerce block importing it would leave
		// `@woocommerce/stores/woocommerce` absent from the import map
		// entirely, so this import failing is itself a defect to report.
		await import( '@woocommerce/stores/woocommerce' );
		const { state } = store( 'woocommerce', {}, { lock: consent } );

		const isObject = ( value: unknown ) =>
			typeof value === 'object' && value !== null;

		return {
			hasProducts: isObject( state.products ),
			hasCart: isObject( state.cart ),
			hasProductScopes: isObject( state.productScopes ),
		};
	}, lock );
}

function expectNoRetiredNamespace( audit: NamespaceAudit ) {
	expect( audit.stateNamespaces ).not.toContain( RETIRED_NAMESPACE );
	expect( audit.directiveNamespaces ).not.toContain( RETIRED_NAMESPACE );
	expect( audit.stateNamespaces ).toContain( 'woocommerce' );
}

function expectUnifiedStoreReachable( storeState: UnifiedStoreShape ) {
	expect( storeState.hasProducts ).toBe( true );
	expect( storeState.hasCart ).toBe( true );
	expect( storeState.hasProductScopes ).toBe( true );
}

/**
 * The migration in T3 to T18 folded `woocommerce/products` and
 * `woocommerce/cart` into the one `woocommerce` Interactivity API store. This
 * spec proves the retired namespace is gone from the fixture set's serialized
 * state and directives, that `state.products`, `state.cart` and
 * `state.productScopes` all resolve under `woocommerce` on those pages, and
 * that a script holding WooCommerce's own acknowledgement string can still
 * reach the store. A hit here is a defect in T3, T4, T5 or T18, not in this
 * spec.
 */
test.describe( 'Unified `woocommerce` store registration', () => {
	test( 'no page serializes the retired woocommerce/products namespace, and a script with the acknowledgement string reaches state.products, state.cart and state.productScopes', async ( {
		page,
		editor,
		pageObject,
		admin,
		productCollectionPageObject,
	} ) => {
		await test.step( 'a Single Product Template page with Add to Cart with Options', async () => {
			const product = await wpCLI(
				`wc product create --user=1 --porcelain --name="Store Registration Simple Fixture" --slug="store-registration-simple-fixture" --sku="store-registration-simple-sku" --regular_price="15.00"`
			);
			expect( product.stdout.match( /(\d+)\s*$/ )?.[ 1 ] ).toBeTruthy();

			await pageObject.updateSingleProductTemplate();
			await expect(
				editor.canvas
					.getByLabel( `Block: ${ pageObject.BLOCK_NAME }` )
					.first()
			).toBeVisible();
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( '/product/store-registration-simple-fixture/' );

			expectNoRetiredNamespace( await auditNamespaces( page ) );
			expectUnifiedStoreReachable(
				await readUnifiedStoreState( page, storeConsent )
			);
		} );

		let productCollectionPostId: number;

		await test.step( 'a Product Collection page with an Add to Cart button', async () => {
			const productA = await wpCLI(
				`wc product create --user=1 --porcelain --name="Store Registration Collection Fixture A" --slug="store-registration-collection-fixture-a" --sku="store-registration-collection-a-sku" --regular_price="9.00"`
			);
			const productB = await wpCLI(
				`wc product create --user=1 --porcelain --name="Store Registration Collection Fixture B" --slug="store-registration-collection-fixture-b" --sku="store-registration-collection-b-sku" --regular_price="11.00"`
			);
			expect( productA.stdout.match( /(\d+)\s*$/ )?.[ 1 ] ).toBeTruthy();
			expect( productB.stdout.match( /(\d+)\s*$/ )?.[ 1 ] ).toBeTruthy();

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
					name: 'Store Registration Collection Fixture A (store-registration-collection-a-sku)',
				} )
				.click();
			await productPicker
				.getByRole( 'checkbox', {
					name: 'Store Registration Collection Fixture B (store-registration-collection-b-sku)',
				} )
				.click();
			await productPicker
				.locator( '.components-button.is-primary' )
				.click();

			await productCollectionPageObject.insertBlockInProductCollection( {
				name: 'woocommerce/product-button',
				attributes: {},
			} );

			productCollectionPostId = await editor.publishPost();

			await page.goto( `/?p=${ productCollectionPostId }` );

			expectNoRetiredNamespace( await auditNamespaces( page ) );
			expectUnifiedStoreReachable(
				await readUnifiedStoreState( page, storeConsent )
			);
		} );

		await test.step( 'the Mini-Cart page', async () => {
			await page.goto( '/mini-cart/' );

			expectNoRetiredNamespace( await auditNamespaces( page ) );
			expectUnifiedStoreReachable(
				await readUnifiedStoreState( page, storeConsent )
			);
		} );
	} );
} );
