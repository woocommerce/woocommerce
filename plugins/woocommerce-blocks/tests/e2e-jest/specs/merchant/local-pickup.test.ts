/**
 * External dependencies
 */
import { switchUserToAdmin, visitAdminPage } from '@wordpress/e2e-test-utils';
import { findLabelWithText } from '@woocommerce/blocks-test-utils';
import WooCommerceRestApi from '@woocommerce/woocommerce-rest-api';
import { default as axios } from 'axios';

/**
 * Internal dependencies
 */
import { merchant } from '../../../utils';

const setDefaults = async () => {
	await merchant.enableLocalPickup();
	await merchant.removeCostForLocalPickup();
};

const clearLocations = async () => {
	const editButtons = await page.$$(
		'.pickup-locations tbody tr .button-link-edit'
	);

	for ( const button of editButtons ) {
		await button.click();
		await expect( page ).toMatchElement( '.components-modal__content' );
		await expect( page ).toClick( '.components-modal__content button', {
			text: 'Delete location',
		} );
		await expect( page ).not.toMatchElement( '.components-modal__content' );
	}
};

/**
 * Sets the WC Cart and Checkout page IDs to the IDs of the pages with the given slugs.
 */
const setCartCheckoutPages = async ( {
	cartSlug,
	checkoutSlug,
}: {
	cartSlug: string;
	checkoutSlug: string;
} ) => {
	const WPAPI = `${ process.env.WORDPRESS_BASE_URL }/wp-json/wp/v2/pages`;
	const response = await axios.get( `${ WPAPI }?per_page=100` );
	const pages = response.data;
	const cartBlock = pages.find( ( page ) => page.slug === cartSlug );
	const checkoutBlock = pages.find( ( page ) => page.slug === checkoutSlug );
	const WooCommerce = new WooCommerceRestApi( {
		url: `${ process.env.WORDPRESS_BASE_URL }/`,
		consumerKey: 'consumer_key', // Your consumer key
		consumerSecret: 'consumer_secret', // Your consumer secret
		version: 'wc/v3',
		axiosConfig: {
			auth: {
				username: process.env.WORDPRESS_LOGIN,
				password: process.env.WORDPRESS_PASSWORD,
			},
		},
	} );
	const fixture = [
		{
			id: 'woocommerce_cart_page_id',
			value: cartBlock.id.toString() || '',
		},
		{
			id: 'woocommerce_checkout_page_id',
			value: checkoutBlock.id.toString() || '',
		},
	];

	await WooCommerce.post( 'settings/advanced/batch', {
		update: fixture,
	} );
};
describe( `Local Pickup Settings`, () => {
	beforeAll( async () => {
		await switchUserToAdmin();
		await merchant.goToLocalPickupSettingsPage();
		await setDefaults();
		await clearLocations();
		await merchant.saveLocalPickupSettingsPageWithRefresh();
	} );

	afterAll( async () => {
		await switchUserToAdmin();
		await merchant.goToLocalPickupSettingsPage();
		await setDefaults();
		await clearLocations();
		await merchant.saveLocalPickupSettingsPageWithRefresh();
	} );

	beforeEach( async () => {
		await switchUserToAdmin();
		await merchant.goToLocalPickupSettingsPage();
	} );

	it( 'renders without crashing', async () => {
		await expect( page ).toMatchElement( '#local-pickup-settings' );
	} );

	describe( 'Core Settings', () => {
		afterAll( async () => {
			await setCartCheckoutPages( {
				cartSlug: 'cart-block',
				checkoutSlug: 'checkout-block',
			} );
		} );
		it( 'shows the correct shipping options depending on whether Local Pickup is enabled', async () => {
			await merchant.disableLocalPickup();
			await visitAdminPage(
				'admin.php',
				'page=wc-settings&tab=shipping&section=options'
			);
			const hideShippingLabel = await page.$x(
				'//label[contains(., "Hide shipping costs until an address is entered")]'
			);
			await expect( hideShippingLabel ).toHaveLength( 1 );

			await merchant.enableLocalPickup();
			await visitAdminPage(
				'admin.php',
				'page=wc-settings&tab=shipping&section=options'
			);
			const modifiedHideShippingLabel = await page.$x(
				'//label[contains(., "Hide shipping costs until an address is entered (Not available when using WooCommerce Blocks Local Pickup)")]'
			);
			await expect( modifiedHideShippingLabel ).toHaveLength( 1 );
		} );
	} );

	describe( 'Global Settings', () => {
		it( 'allows toggling of enabled on', async () => {
			const initialChecked = await page.$eval(
				'input[name="local_pickup_enabled"]',
				( el ) => ( el as HTMLInputElement ).checked
			);
			const toggleLabel = await findLabelWithText(
				'Enable local pickup'
			);
			await toggleLabel.click();
			await merchant.saveLocalPickupSettingsPageWithRefresh();

			expect(
				await page.$eval(
					'input[name="local_pickup_enabled"]',
					( el ) => ( el as HTMLInputElement ).checked
				)
			).not.toBe( initialChecked );
		} );

		it( 'allows the title to be changed', async () => {
			await expect( page ).toFill(
				'input[name="local_pickup_title"]',
				'Local Pickup Test'
			);

			await merchant.saveLocalPickupSettingsPageWithRefresh();

			expect(
				await page.$eval(
					'input[name="local_pickup_title"]',
					( el ) => el.value
				)
			).toBe( 'Local Pickup Test' );
		} );

		it( 'add price field toggles settings', async () => {
			const selector = `input[name="local_pickup_cost"]`; // Cost field.
			const toggleLabel = await findLabelWithText(
				'Add a price for customers who choose local pickup'
			);
			await expect( toggleLabel ).toToggleElement( selector );
		} );

		it( 'cost and tax status can be set', async () => {
			const toggleLabel = await findLabelWithText(
				'Add a price for customers who choose local pickup'
			);
			const initialChecked = await page.$eval(
				'#inspector-checkbox-control-1',
				( el ) => ( el as HTMLInputElement ).checked
			);

			if ( ! initialChecked ) {
				await toggleLabel.click();
			}

			await expect( page ).toFill(
				'input[name="local_pickup_cost"]',
				'20'
			);
			await page.select(
				'select[name="local_pickup_tax_status"]',
				'none'
			);

			await merchant.saveLocalPickupSettingsPageWithRefresh();

			const refreshChecked = await page.$eval(
				'#inspector-checkbox-control-1',
				( el ) => ( el as HTMLInputElement ).checked
			);
			const costValue = await page.$eval(
				'input[name="local_pickup_cost"]',
				( el ) => el.value
			);
			const taxValue = await page.$eval(
				'select[name="local_pickup_tax_status"]',
				( el ) => el.value
			);

			expect( refreshChecked ).toBe( true );
			expect( costValue ).toBe( '20' );
			expect( taxValue ).toBe( 'none' );
		} );
	} );

	describe( 'Location Settings', () => {
		it( 'can add a new location', async () => {
			await expect( page ).toClick( 'button', {
				text: 'Add pickup location',
			} );
			await expect( page ).toMatchElement( '.components-modal__content' );
			await expect( page ).toFill(
				'.components-modal__content input[name="location_name"]',
				'New Location Name'
			);
			await expect( page ).toFill(
				'.components-modal__content input[name="location_address"]',
				'New Address 123'
			);
			await expect( page ).toFill(
				'.components-modal__content input[name="location_city"]',
				'New City'
			);
			// We merged the Country and State fields into the same field in the local pickup modal.
			await page.select(
				'.components-modal__content select[name="location_country_state"]',
				'United Kingdom (UK)'
			);
			await expect( page ).toFill(
				'.components-modal__content input[name="location_postcode"]',
				'N3W 123'
			);
			await expect( page ).toFill(
				'.components-modal__content input[name="pickup_details"]',
				'These are the pickup details for the new location.'
			);

			await expect( page ).toClick( '.components-modal__content button', {
				text: 'Done',
			} );

			await merchant.saveLocalPickupSettingsPageWithRefresh();

			await expect( page ).toMatchElement(
				'.pickup-locations tbody tr td',
				{
					text: 'New Location Name',
				}
			);
		} );

		it( 'can edit a location', async () => {
			await expect( page ).toClick( '.pickup-locations button', {
				text: 'Edit',
			} );
			await expect( page ).toMatchElement( '.components-modal__content' );
			await expect( page ).toClick( '.components-modal__content button', {
				text: 'Done',
			} );
		} );

		it( 'can delete a location', async () => {
			await expect( page ).toClick( '.pickup-locations button', {
				text: 'Edit',
			} );
			await expect( page ).toMatchElement( '.components-modal__content' );
			await expect( page ).toClick( '.components-modal__content button', {
				text: 'Delete location',
			} );

			await merchant.saveLocalPickupSettingsPageWithRefresh();
			await expect( page ).not.toMatchElement(
				'.pickup-locations tbody tr td',
				{
					text: 'New Location Name',
				}
			);
		} );
	} );
} );
