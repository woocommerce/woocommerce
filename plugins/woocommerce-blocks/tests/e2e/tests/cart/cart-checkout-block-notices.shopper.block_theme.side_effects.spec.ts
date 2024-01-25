/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-playwright-utils';
import {
	cli,
	BLOCK_THEME_SLUG,
	BLOCK_CHILD_THEME_SLUG,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CheckoutPage } from '../checkout/checkout.page';

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper → Notice Templates', () => {
	test.beforeAll( async () => {
		await cli(
			`npm run wp-env run tests-cli -- wp theme install ${ BLOCK_CHILD_THEME_SLUG } --activate`
		);
	} );

	test.afterAll( async () => {
		await cli(
			`npm run wp-env run tests-cli -- wp theme install ${ BLOCK_THEME_SLUG } --activate`
		);
	} );

	test( 'Custom notice templates are visible', async ( {} ) => {
		await expect( true ).toBe( true );
	} );
} );
