/**
 * External dependencies
 */
import {
	addAProductToCart,
	WC_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, test as baseTest } from '../../fixtures/fixtures';
import { getFakeProduct } from '../../utils/data';
import { setFilterValue } from '../../utils/filters';
import {
	createClassicCheckoutPage,
	CLASSIC_CHECKOUT_PAGE,
} from '../../utils/pages';

const test = baseTest.extend( {
	product: async ( { restApi }, use ) => {
		let product;

		await restApi
			.post( `${ WC_API_PATH }/products`, getFakeProduct( { dec: 0 } ) )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
			force: true,
		} );
	},
} );

test( 'Shortcode checkout shows or hides the State field for the selected country', async ( {
	page,
	product,
} ) => {
	await createClassicCheckoutPage();
	await page.context().clearCookies();
	await addAProductToCart( page, product.id, 1 );
	// Give Andorra an empty state list, as an extension can. The filter replaces
	// every list, which leaves Cyprus and Lithuania as they are: neither has one.
	await setFilterValue( page, 'woocommerce_states', { AD: [] } );
	await page.goto( CLASSIC_CHECKOUT_PAGE.slug );

	const country = page.locator( '#billing_country' );
	const stateRow = page.locator( '#billing_state_field' );

	// Cyprus has no state list and its locale hides State.
	await country.selectOption( 'CY' );
	await expect( stateRow ).toBeHidden();

	// Lithuania has no state list and a required State.
	await country.selectOption( 'LT' );
	await expect( stateRow ).toBeVisible();
	await expect( stateRow ).toContainClass( 'validate-required' );

	// A locale that hides State still hides it.
	await country.selectOption( 'CY' );
	await expect( stateRow ).toBeHidden();

	// An empty state list keeps State hidden even though the locale shows it.
	await country.selectOption( 'AD' );
	await expect( stateRow ).toBeHidden();
} );
