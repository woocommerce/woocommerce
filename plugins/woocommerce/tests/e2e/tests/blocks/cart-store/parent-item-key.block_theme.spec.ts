/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import { test as base, expect, guestFile, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from '../add-to-cart-with-options/add-to-cart-with-options.page';
import {
	CART_LINE_IDENTITY_PLUGIN,
	PRODUCT_X,
	productButton,
	seedDeclaredChildLine,
} from './utils';

type StoreApiCartItem = {
	id: number;
	key: string;
	parent_item_key: string | null;
};

/** Reads the current session cart through the browser's Store API session. */
const readCartItems = async ( page: Page ): Promise< StoreApiCartItem[] > => {
	const cart = await page.evaluate( async () => {
		const response = await fetch( '/wp-json/wc/store/v1/cart' );
		return {
			ok: response.ok,
			items: ( await response.json() ).items,
		};
	} );
	expect( cart.ok ).toBe( true );
	return cart.items as StoreApiCartItem[];
};

/**
 * Runs a WP-CLI `post list --format=ids` query that must match exactly one post
 * and returns its ID. The ID is read from the last non-empty output line, so
 * numbers printed by the wp-env wrapper can't be mistaken for it.
 */
const getSinglePostId = async ( command: string ): Promise< number > => {
	const result = await wpCLI( command );
	const lastLine =
		result.stdout
			.split( '\n' )
			.map( ( line ) => line.trim() )
			.filter( Boolean )
			.pop() ?? '';
	const postId = Number( lastLine );
	expect( Number.isInteger( postId ) && postId > 0 ).toBe( true );
	return postId;
};

/** Resolves a sample product by its WordPress post slug. */
const getProductIdBySlug = ( slug: string ): Promise< number > =>
	getSinglePostId(
		`post list --post_type=product --field=ID --name="${ slug }" --format=ids`
	);

/** Adds Cap through the legacy URL and returns its session cart line. */
const addCapToCart = async ( page: Page ) => {
	const capId = await getProductIdBySlug( 'cap' );
	await page.goto( `/?add-to-cart=${ capId }` );
	const capLine = ( await readCartItems( page ) ).find(
		( item ) => item.id === capId
	);
	expect( capLine ).toBeDefined();
	return {
		capId,
		parentItemKey: ( capLine as StoreApiCartItem ).key,
	};
};

/** Seeds Beanie as Cap's child and verifies the declared cart relationship. */
const seedBeanieAsCapChild = async ( page: Page ) => {
	const { capId, parentItemKey } = await addCapToCart( page );
	await seedDeclaredChildLine( page, PRODUCT_X.id, parentItemKey );

	const items = await readCartItems( page );
	expect( items ).toHaveLength( 2 );
	expect( items.find( ( item ) => item.id === capId )?.key ).toBe(
		parentItemKey
	);
	expect(
		items.find( ( item ) => item.id === PRODUCT_X.id )?.parent_item_key
	).toBe( parentItemKey );

	return { capId };
};

/** Reads the shop's server-rendered ProductButton without executing its scripts. */
const readServerRenderedProductButtonText = async (
	page: Page,
	productId: number
) => {
	const response = await page.request.get( '/shop/' );
	expect( response.ok() ).toBe( true );
	const html = await response.text();
	const buttonText = await page.evaluate(
		( { pageHtml, id } ) => {
			const documentWithoutScripts = new DOMParser().parseFromString(
				pageHtml,
				'text/html'
			);
			const button = documentWithoutScripts
				.querySelector( `li.post-${ id }` )
				?.querySelector( 'button' );
			return button?.textContent?.trim() ?? null;
		},
		{ pageHtml: html, id: productId }
	);
	expect( buttonText ).not.toBeNull();
	return buttonText;
};

/** Confirms the hydrated ProductButton responds to a cart mutation. */
const expectHydratedProductButton = async (
	page: Page,
	productId: number,
	initialText: string,
	updatedText: string
) => {
	const button = productButton( page, productId );
	await expect( button ).toBeVisible();
	await expect( button ).toHaveText( initialText );

	const addResponsePromise = page.waitForResponse(
		( response ) =>
			response.url().includes( '/wc/store/v1/batch' ) &&
			response.request().method() === 'POST'
	);
	await button.click();
	const addResponse = await addResponsePromise;
	expect( addResponse.ok() ).toBe( true );
	await expect( button ).toHaveText( updatedText );
};

const test = base.extend< {
	addToCartWithOptionsPage: AddToCartWithOptionsPage;
} >( {
	addToCartWithOptionsPage: async (
		{ page, admin, editor },
		provideFixture
	) => {
		await provideFixture(
			new AddToCartWithOptionsPage( { page, admin, editor } )
		);
	},
} );

test.describe( 'ProductButton in-cart count', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin( CART_LINE_IDENTITY_PLUGIN );
	} );

	test( 'does not count a child cart line in the add to cart button', async ( {
		page,
		frontendUtils,
		addToCartWithOptionsPage,
	} ) => {
		await addToCartWithOptionsPage.createPostWithProductBlock(
			'hoodie',
			'hoodie-blue-yes'
		);
		const postUrl = page.url();

		await frontendUtils.emptyCart();

		const variationId = await getSinglePostId(
			'post list --post_type=product_variation --field=ID --name="Hoodie - Blue, Yes" --format=ids'
		);

		const capId = await getProductIdBySlug( 'cap' );
		await page.goto( `/?add-to-cart=${ capId }` );
		const parentCartItems = await readCartItems( page );
		expect( parentCartItems ).toHaveLength( 1 );
		const parentLine = parentCartItems.find(
			( item ) => item.id === capId
		);
		expect( parentLine ).toBeDefined();
		const parentItemKey = ( parentLine as StoreApiCartItem ).key;

		await seedDeclaredChildLine( page, variationId, parentItemKey );
		const cartItems = await readCartItems( page );
		expect( cartItems ).toHaveLength( 2 );
		expect(
			cartItems.find( ( item ) => item.id === variationId )
				?.parent_item_key
		).toBe( parentItemKey );
		expect(
			cartItems.find( ( item ) => item.key === parentItemKey )
				?.parent_item_key
		).toBeNull();

		// Reads the server-rendered HTML without running scripts.
		const readServerRenderedButtonText = async () => {
			const response = await page.request.get( postUrl );
			const button = ( await response.text() ).match(
				/<button\b[^>]*class="[^"]*\bsingle_add_to_cart_button\b[^"]*"[^>]*>([\s\S]*?)<\/button>/
			);
			expect( button ).not.toBeNull();

			return button?.[ 1 ]?.replace( /<[^>]*>/g, '' ).trim();
		};

		const addToCartButton = page
			.locator( '.wp-block-add-to-cart-with-options' )
			.locator( '.single_add_to_cart_button' );

		expect( await readServerRenderedButtonText() ).toBe( 'Add to cart' );
		await page.goto( postUrl );
		// The server renders the button hidden and the client store reveals it, so
		// waiting for it to be visible makes the next text check read the hydrated count.
		await expect( addToCartButton ).toBeVisible();
		await expect( addToCartButton ).toHaveText( 'Add to cart' );

		await addToCartButton.click();
		await expect( addToCartButton ).toHaveText( '1 in cart' );

		expect( await readServerRenderedButtonText() ).toBe( '1 in cart' );
		await page.reload();
		await expect( addToCartButton ).toBeVisible();
		await expect( addToCartButton ).toHaveText( '1 in cart' );
	} );

	test.describe( 'as a guest', () => {
		test.use( { storageState: guestFile } );

		test( 'counts a child whose declared parent is not in the cart', async ( {
			page,
		} ) => {
			await seedDeclaredChildLine(
				page,
				PRODUCT_X.id,
				'not-a-cart-line-key'
			);

			expect(
				await readServerRenderedProductButtonText( page, PRODUCT_X.id )
			).toBe( '1 in cart' );

			await page.goto( '/shop/' );
			await expectHydratedProductButton(
				page,
				PRODUCT_X.id,
				'1 in cart',
				'2 in cart'
			);
		} );

		test( 'counts a child after its parent is removed from the Mini-Cart', async ( {
			page,
			miniCartUtils,
		} ) => {
			const { capId } = await seedBeanieAsCapChild( page );

			await page.goto( '/shop/' );
			const button = productButton( page, PRODUCT_X.id );
			await expect( button ).toBeVisible();
			await expect( button ).toHaveText( 'Add to cart' );

			let mainFrameNavigations = 0;
			page.on( 'framenavigated', ( frame ) => {
				if ( frame === page.mainFrame() ) {
					mainFrameNavigations++;
				}
			} );
			const shopUrl = page.url();

			await miniCartUtils.openMiniCart();
			const removeResponsePromise = page.waitForResponse(
				( response ) =>
					response.url().includes( '/wc/store/v1/batch' ) &&
					response.request().method() === 'POST'
			);
			await page
				.getByRole( 'dialog' )
				.getByRole( 'button', { name: 'Remove Cap from cart' } )
				.click();
			const removeResponse = await removeResponsePromise;
			expect( removeResponse.ok() ).toBe( true );
			const items = await readCartItems( page );
			expect(
				items.find( ( item ) => item.id === capId )
			).toBeUndefined();
			expect(
				items.find( ( item ) => item.id === PRODUCT_X.id )
					?.parent_item_key
			).toBeNull();
			await expect( button ).toHaveText( '1 in cart' );
			expect( page.url() ).toBe( shopUrl );
			expect( mainFrameNavigations ).toBe( 0 );
		} );

		test( 'counts a child when its declared parent product is drafted', async ( {
			page,
		} ) => {
			const { capId } = await seedBeanieAsCapChild( page );

			try {
				await wpCLI( `post update ${ capId } --post_status=draft` );

				const items = await readCartItems( page );
				expect(
					items.find( ( item ) => item.id === capId )
				).toBeUndefined();
				const childLine = items.find(
					( item ) => item.id === PRODUCT_X.id
				);
				expect( childLine ).toBeDefined();
				expect( childLine?.parent_item_key ).toBeNull();

				expect(
					await readServerRenderedProductButtonText(
						page,
						PRODUCT_X.id
					)
				).toBe( '1 in cart' );

				await page.goto( '/shop/' );
				await expectHydratedProductButton(
					page,
					PRODUCT_X.id,
					'1 in cart',
					'2 in cart'
				);
			} finally {
				await wpCLI( `post update ${ capId } --post_status=publish` );
			}
		} );
	} );
} );
