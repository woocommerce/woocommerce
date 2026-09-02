/**
 * External dependencies
 */
import { expect, test as base, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CheckoutPage } from '../checkout/checkout.page';
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

type ShippingTopology = {
	defaultRate?: boolean;
	gbRate?: boolean;
	pickup?: boolean;
	requiresAddress: boolean;
};

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( { page } );
		await use( pageObject );
	},
} );

const configureShippingTopology = async ( topology: ShippingTopology ) => {
	const encodedTopology = Buffer.from(
		JSON.stringify( {
			defaultRate: false,
			gbRate: false,
			pickup: false,
			...topology,
		} )
	).toString( 'base64' );

	await wpCLI( `eval '
		$config = json_decode( base64_decode( "${ encodedTopology }" ), true );

		foreach ( WC_Shipping_Zones::get_zones() as $zone_data ) {
			$zone = new WC_Shipping_Zone( $zone_data["zone_id"] );
			$zone->delete( true );
		}

		$default_zone = WC_Shipping_Zones::get_zone( 0 );
		foreach ( $default_zone->get_shipping_methods() as $method ) {
			$default_zone->delete_shipping_method( $method->instance_id );
		}

		update_option( "woocommerce_flat_rate_settings", array( "enabled" => "no" ) );
		update_option( "woocommerce_free_shipping_settings", array( "enabled" => "no" ) );
		update_option( "woocommerce_local_pickup_settings", array( "enabled" => "no" ) );
		update_option( "woocommerce_default_customer_address", "" );
		update_option( "woocommerce_shipping_cost_requires_address", $config["requiresAddress"] ? "yes" : "no" );

		if ( $config["defaultRate"] ) {
			$instance_id = $default_zone->add_shipping_method( "flat_rate" );
			update_option(
				"woocommerce_flat_rate_{$instance_id}_settings",
				array( "enabled" => "yes", "title" => "Home delivery", "tax_status" => "none", "cost" => "10" )
			);
		}

		if ( $config["gbRate"] ) {
			$gb_zone = new WC_Shipping_Zone();
			$gb_zone->set_zone_name( "United Kingdom" );
			$gb_zone->set_zone_locations( array( (object) array( "code" => "GB", "type" => "country" ) ) );
			$gb_zone->save();
			$instance_id = $gb_zone->add_shipping_method( "flat_rate" );
			update_option(
				"woocommerce_flat_rate_{$instance_id}_settings",
				array( "enabled" => "yes", "title" => "UK delivery", "tax_status" => "none", "cost" => "15" )
			);
		}

		update_option(
			"woocommerce_pickup_location_settings",
			array( "enabled" => $config["pickup"] ? "yes" : "no", "title" => "Pickup", "tax_status" => "none", "cost" => "" )
		);
		update_option(
			"pickup_location_pickup_locations",
			$config["pickup"]
				? array(
					array(
						"name" => "Automattic, Inc.",
						"address" => array(
							"address_1" => "60 29th Street, Suite 343",
							"city" => "San Francisco",
							"postcode" => "94110",
							"state" => "CA",
							"country" => "US"
						),
						"details" => "American entity",
						"enabled" => true
					)
				)
				: array()
		);

		WC_Cache_Helper::get_transient_version( "shipping", true );
		delete_transient( "wc_shipping_method_count" );
		WC()->shipping()->reset_shipping();
	'` );
};

test.describe( 'Merchant → Shipping', () => {
	test( 'Merchant can hide shipping costs before address is entered', async ( {
		page,
		shippingUtils,
	} ) => {
		await configureShippingTopology( { requiresAddress: false } );
		await shippingUtils.openShippingSettings();

		const hideShippingCosts = page.getByLabel(
			'Hide shipping costs until an address is entered'
		);
		await expect( hideShippingCosts ).not.toBeChecked();
		await hideShippingCosts.check();
		await shippingUtils.saveShippingSettings();
		await page.reload();

		await expect( hideShippingCosts ).toBeChecked();
	} );
} );

test.describe( 'Shopper → Shipping', () => {
	test( 'Shopper can switch between shipping and local pickup while preserving the effective selected rate', async ( {
		frontendUtils,
		page,
	} ) => {
		await configureShippingTopology( {
			defaultRate: true,
			pickup: true,
			requiresAddress: false,
		} );

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCheckout();

		const ship = page.getByRole( 'radio', { name: 'Ship', exact: true } );
		const pickup = page.getByRole( 'radio', {
			name: 'Pickup',
			exact: true,
		} );
		const deliveryRate = page.getByRole( 'radio', {
			name: /Home delivery/,
		} );
		const pickupRate = page.getByRole( 'radio', {
			name: /Automattic, Inc\./,
		} );

		await expect( ship ).toBeChecked();
		await expect( pickup ).not.toBeChecked();
		await expect( deliveryRate ).toBeChecked();

		await pickup.click();
		await expect( pickup ).toBeChecked();
		await expect( ship ).not.toBeChecked();
		await expect( pickupRate ).toBeChecked();
		await expect( deliveryRate ).toBeHidden();

		await ship.click();
		await expect( ship ).toBeChecked();
		await expect( pickup ).not.toBeChecked();
		await expect( deliveryRate ).toBeChecked();
	} );

	test( 'Shopper sees no matching rates until a complete address reveals the zone rate', async ( {
		checkoutPageObject,
		frontendUtils,
		page,
	} ) => {
		await configureShippingTopology( {
			gbRate: true,
			requiresAddress: true,
		} );

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCheckout();

		await expect(
			page.getByText(
				/Enter a shipping address to view shipping options/
			)
		).toBeVisible();

		await checkoutPageObject.fillInCheckoutWithTestData();
		await checkoutPageObject.waitForCheckoutToFinishUpdating();
		await expect(
			page
				.getByLabel( 'Checkout' )
				.getByText(
					'No shipping options are available for this address. Please verify the address is correct or try a different address.'
				)
		).toBeVisible();

		await checkoutPageObject.fillInCheckoutWithTestData( {
			country: 'United Kingdom (UK)',
			countryKey: 'GB',
			city: 'London',
			state: '',
			postcode: 'EC4M 9AF',
		} );

		const shippingAddress = page.getByRole( 'group', {
			name: 'Shipping address',
		} );
		await expect(
			shippingAddress.getByLabel( 'Country/Region' )
		).toHaveValue( 'GB' );
		await expect(
			shippingAddress.locator( '#shipping-postcode' )
		).toHaveValue( 'EC4M 9AF' );
		await checkoutPageObject.waitForCheckoutToFinishUpdating();

		await expect(
			page.getByRole( 'radio', { name: /UK delivery/ } )
		).toBeChecked();
	} );
} );
