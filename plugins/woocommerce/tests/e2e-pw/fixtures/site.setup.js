/**
 * Internal dependencies
 */
import { test as setup } from './fixtures';
import { ADMIN_STATE_PATH } from '../playwright.config';
import { setComingSoon } from '../utils/coming-soon';
import { skipOnboardingWizard } from '../utils/onboarding';
import { setNewPaymentsSettingsPage } from '../utils/payments-settings';

setup.use( { storageState: ADMIN_STATE_PATH } );

setup( 'configure HPOS', async ( { api } ) => {
	const { DISABLE_HPOS } = process.env;
	console.log( `DISABLE_HPOS: ${ DISABLE_HPOS }` );

	if ( DISABLE_HPOS ) {
		const hposSettingRetries = 5;
		const value = DISABLE_HPOS === '1' ? 'no' : 'yes';
		let hposConfigured = false;

		for ( let i = 0; i < hposSettingRetries; i++ ) {
			try {
				console.log(
					`Trying to switch ${
						value === 'yes' ? 'on' : 'off'
					} HPOS...`
				);
				const response = await api.post(
					'settings/advanced/woocommerce_custom_orders_table_enabled',
					{ value }
				);
				if ( response.data.value === value ) {
					console.log(
						`HPOS Switched ${
							value === 'yes' ? 'on' : 'off'
						} successfully`
					);
					hposConfigured = true;
					break;
				}
			} catch ( e ) {
				console.log(
					`HPOS setup failed. Retrying... ${ i }/${ hposSettingRetries }`
				);
				console.log( e );
			}
		}

		if ( ! hposConfigured ) {
			console.error(
				'Cannot proceed e2e test, HPOS configuration failed. Please check if the correct DISABLE_HPOS value was used and the test site has been setup correctly.'
			);
			process.exit( 1 );
		}
	}

	const response = await api.get(
		'settings/advanced/woocommerce_custom_orders_table_enabled'
	);
	const dataValue = response.data.value;
	const enabledOption = response.data.options[ dataValue ];
	console.log(
		`HPOS configuration (woocommerce_custom_orders_table_enabled): ${ dataValue } - ${ enabledOption }`
	);
} );

//todo to remove, see https://github.com/woocommerce/woocommerce/issues/50758
setup( 'convert Cart and Checkout pages to shortcode', async ( { wpApi } ) => {
	// List all pages
	const response_list = await wpApi.get(
		'./wp-json/wp/v2/pages?slug=cart,checkout',
		{
			data: {
				_fields: [ 'id', 'slug' ],
			},
			failOnStatusCode: true,
		}
	);

	const list = await response_list.json();

	// Find the cart and checkout pages
	const cart = list.find( ( page ) => page.slug === 'cart' );
	const checkout = list.find( ( page ) => page.slug === 'checkout' );

	// Convert their contents to shortcodes
	await wpApi.put( `./wp-json/wp/v2/pages/${ cart.id }`, {
		data: {
			content: {
				raw: '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->',
			},
		},
		failOnStatusCode: true,
	} );

	await wpApi.put( `./wp-json/wp/v2/pages/${ checkout.id }`, {
		data: {
			content: {
				raw: '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
			},
		},
		failOnStatusCode: true,
	} );
} );

setup( 'disable coming soon', async ( { baseURL } ) => {
	await setComingSoon( { baseURL, enabled: 'no' } );
} );

setup( 'disable onboarding wizard', async () => {
	await skipOnboardingWizard();
} );

setup( 'disable new payments settings page', async ( { baseURL } ) => {
	await setNewPaymentsSettingsPage( { baseURL, enabled: 'no' } );
} );

setup( 'determine if multisite', async ( { api } ) => {
	const response = await api.get( 'system_status' );
	const { environment } = response.data;

	if ( environment.wp_multisite === false ) {
		delete process.env.IS_MULTISITE;
	} else {
		process.env.IS_MULTISITE = environment.wp_multisite;
		console.log( `IS_MULTISITE: ${ process.env.IS_MULTISITE }` );
	}
} );

setup( 'general settings', async ( { api } ) => {
	await api.post( 'settings/general/batch', {
		update: [
			{ id: 'woocommerce_allowed_countries', value: 'all' },
			{ id: 'woocommerce_currency', value: 'USD' },
			{ id: 'woocommerce_price_thousand_sep', value: ',' },
			{ id: 'woocommerce_price_decimal_sep', value: '.' },
			{ id: 'woocommerce_price_num_decimals', value: '2' },
			{ id: 'woocommerce_store_address', value: 'addr 1' },
			{ id: 'woocommerce_store_city', value: 'San Francisco' },
			{ id: 'woocommerce_default_country', value: 'US:CA' },
			{ id: 'woocommerce_store_postcode', value: '94107' },
		],
	} );
} );
