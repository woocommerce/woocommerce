/**
 * Cart store module id E2E Tests
 *
 * The `woocommerce` iAPI store is served as the script module
 * `@woocommerce/stores/woocommerce`. These tests load a page that renders the
 * Mini-Cart block and check the module id the page resolves and the store it
 * registers.
 */

/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-utils';

const test = base.extend( {} );

test.describe( 'Cart store module id', () => {
	test( 'the Mini-Cart page loads the woocommerce store from @woocommerce/stores/woocommerce', async ( {
		page,
		frontendUtils,
	} ) => {
		// The block theme header renders the Mini-Cart block on the shop page.
		await frontendUtils.goToShop();

		const moduleReferences = await page.evaluate( () => {
			const importMapText =
				document.querySelector( 'script[type="importmap"]' )
					?.textContent ?? '{}';
			const importMapIds = Object.keys(
				JSON.parse( importMapText ).imports ?? {}
			);
			const preloadUrls = Array.from(
				document.querySelectorAll< HTMLLinkElement >(
					'link[rel="modulepreload"]'
				)
			).map( ( link ) => link.href );

			return { importMapIds, preloadUrls };
		} );

		const namesStore =
			moduleReferences.importMapIds.includes(
				'@woocommerce/stores/woocommerce'
			) ||
			moduleReferences.preloadUrls.some( ( url ) =>
				/\/@woocommerce\/stores\/woocommerce\.js(\?|$)/.test( url )
			);
		expect( namesStore ).toBe( true );

		const namesRemovedId = [
			...moduleReferences.importMapIds,
			...moduleReferences.preloadUrls,
		].some( ( reference ) =>
			reference.includes( '@woocommerce/stores/woocommerce/cart' )
		);
		expect( namesRemovedId ).toBe( false );

		const storeShape = await page.evaluate( async () => {
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			const { state, actions } = store(
				'woocommerce',
				{},
				{ lock: unlockKey }
			) as {
				state: Record< string, unknown >;
				actions: Record< string, unknown >;
			};

			return {
				stateKeys: [
					'cart',
					'restUrl',
					'nonce',
					'findItemInCart',
				].filter( ( key ) => key in state ),
				actionKeys: [
					'removeCartItem',
					'addCartItem',
					'batchAddCartItems',
					'refreshCartItems',
					'waitForIdle',
					'showNoticeError',
					'updateNotices',
				].filter( ( key ) => typeof actions[ key ] === 'function' ),
			};
		} );

		expect( storeShape.stateKeys ).toEqual( [
			'cart',
			'restUrl',
			'nonce',
			'findItemInCart',
		] );
		expect( storeShape.actionKeys ).toEqual( [
			'removeCartItem',
			'addCartItem',
			'batchAddCartItems',
			'refreshCartItems',
			'waitForIdle',
			'showNoticeError',
			'updateNotices',
		] );
	} );
} );
