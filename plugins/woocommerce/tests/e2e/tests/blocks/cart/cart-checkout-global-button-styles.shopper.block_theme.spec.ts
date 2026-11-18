/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

test.describe( 'Cart and Checkout global button styles', () => {
	for ( const [ block, navigate ] of [
		[ 'cart', 'goToCart' ],
		[ 'checkout', 'goToCheckout' ],
	] as const ) {
		test( `${ block } buttons match core Button styles`, async ( {
			page,
			frontendUtils,
		} ) => {
			await frontendUtils.goToShop();
			await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
			await frontendUtils[ navigate ]();

			await page.getByRole( 'button', { name: 'Add coupons' } ).click();
			await page.getByLabel( 'Enter code' ).fill( 'test-global-styles' );

			await page.evaluate( () => {
				const wrapper = document.createElement( 'div' );
				wrapper.className = 'wp-block-button';
				const button = document.createElement( 'button' );
				button.className = 'wp-block-button__link wp-element-button';
				button.textContent = 'Reference button';
				wrapper.appendChild( button );
				document.querySelector( 'main' )?.appendChild( wrapper );
			} );

			const reference = page.getByRole( 'button', {
				name: 'Reference button',
			} );
			const buttons = page
				.locator( `.wp-block-woocommerce-${ block }` )
				.locator( '.wc-block-components-button.wp-element-button' );

			await expect( buttons ).toHaveCount( 2 );
			for ( const property of [
				'border-radius',
				'background-color',
				'color',
				'padding-top',
				'padding-inline-start',
				'font-family',
				'font-size',
			] ) {
				const expected = await reference.evaluate(
					( element, name ) =>
						getComputedStyle( element ).getPropertyValue( name ),
					property
				);
				for ( const button of await buttons.all() ) {
					await expect( button ).toHaveCSS( property, expected );
				}
			}

			await page.addStyleTag( {
				content: `
					:root :where(.wp-block-button .wp-block-button__link) {
						border-radius: 37px;
						padding: 17px 29px;
					}
					:root :where(.wp-block-button .wp-block-button__link):focus {
						outline: 4px solid rgb(0, 90, 20);
						box-shadow: none;
					}
				`,
			} );

			for ( const button of await buttons.all() ) {
				await expect( button ).toHaveCSS( 'border-radius', '37px' );
				await expect( button ).toHaveCSS( 'padding-top', '17px' );
				await expect( button ).toHaveCSS(
					'padding-inline-start',
					'29px'
				);
			}

			const applyButton = page.getByRole( 'button', { name: 'Apply' } );
			await applyButton.focus();
			await expect( applyButton ).toHaveCSS( 'outline-width', '4px' );
			await expect( applyButton ).toHaveCSS( 'box-shadow', 'none' );
		} );
	}
} );
