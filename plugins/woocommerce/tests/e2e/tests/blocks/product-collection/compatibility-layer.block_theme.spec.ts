/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage from './product-collection.page';

const test = base.extend< { pageObject: ProductCollectionPage } >( {
	pageObject: async ( { page, admin, editor }, use ) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Product Collection: Compatibility Layer', () => {
	test.beforeEach( async ( { pageObject, requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-product-collection-compatibility-layer'
		);
		await pageObject.goToProductCatalogFrontend();
	} );

	test( 'renders global compatibility hooks around the inherited collection and loop', async ( {
		page,
		pageObject,
	} ) => {
		const globalHooks = [
			'woocommerce_before_main_content',
			'woocommerce_before_shop_loop',
			'woocommerce_after_shop_loop',
			'woocommerce_after_main_content',
		];

		for ( const hookName of globalHooks ) {
			const hook = pageObject.locateByTestId( hookName );
			await expect( hook ).toHaveCount( 1 );
			await expect( hook ).toHaveText( `Hook: ${ hookName }` );
		}

		await expect(
			page.locator( '.wp-block-woocommerce-product-collection' )
		).toHaveCount( 1 );
		await expect( pageObject.productTemplate ).toHaveCount( 1 );
		await expect( pageObject.products.first() ).toBeVisible();

		const structureSelector = [
			'[data-testid="woocommerce_before_main_content"]',
			'.wp-block-woocommerce-product-collection',
			'[data-testid="woocommerce_before_shop_loop"]',
			'.wc-block-product-template',
			'[data-testid="woocommerce_after_shop_loop"]',
			'[data-testid="woocommerce_after_main_content"]',
		].join( ', ' );
		const structure = await page
			.locator( structureSelector )
			.evaluateAll( ( nodes ) =>
				nodes.map( ( node ) => {
					const testId = node.getAttribute( 'data-testid' );
					if ( testId ) {
						return testId;
					}

					return node.classList.contains(
						'wp-block-woocommerce-product-collection'
					)
						? 'product-collection'
						: 'product-template';
				} )
			);

		expect( structure ).toEqual( [
			'woocommerce_before_main_content',
			'product-collection',
			'woocommerce_before_shop_loop',
			'product-template',
			'woocommerce_after_shop_loop',
			'woocommerce_after_main_content',
		] );
	} );

	test( 'renders compatibility hooks in order for every product', async ( {
		pageObject,
	} ) => {
		await expect( pageObject.products.first() ).toBeVisible();
		const productCount = await pageObject.products.count();
		expect( productCount ).toBeGreaterThan( 0 );

		const itemHooks = [
			'woocommerce_before_shop_loop_item',
			'woocommerce_before_shop_loop_item_title',
			'woocommerce_shop_loop_item_title',
			'woocommerce_after_shop_loop_item_title',
			'woocommerce_after_shop_loop_item',
		];

		for ( const hookName of itemHooks ) {
			const hooks = pageObject.locateByTestId( hookName );
			await expect( hooks ).toHaveCount( productCount );
			await expect( hooks ).toHaveText(
				Array( productCount ).fill( `Hook: ${ hookName }` )
			);
		}

		const productSequences = await pageObject.products.evaluateAll(
			( products ) =>
				products.map( ( product ) =>
					Array.from(
						product.querySelectorAll(
							'[data-testid="woocommerce_before_shop_loop_item"], [data-testid="woocommerce_before_shop_loop_item_title"], .wp-block-post-title, [data-testid="woocommerce_shop_loop_item_title"], [data-testid="woocommerce_after_shop_loop_item_title"], [data-testid="woocommerce_after_shop_loop_item"]'
						)
					).map(
						( node ) =>
							node.getAttribute( 'data-testid' ) ??
							'product-title'
					)
				)
		);
		const expectedSequence = [
			'woocommerce_before_shop_loop_item',
			'woocommerce_before_shop_loop_item_title',
			'product-title',
			'woocommerce_shop_loop_item_title',
			'woocommerce_after_shop_loop_item_title',
			'woocommerce_after_shop_loop_item',
		];

		expect( productSequences ).toEqual(
			Array.from( { length: productCount }, () => expectedSequence )
		);
	} );
} );
