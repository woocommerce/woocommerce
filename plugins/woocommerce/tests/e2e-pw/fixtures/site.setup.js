/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test as setup } from './fixtures';
import { setComingSoon } from '../utils/coming-soon';
import { skipOnboardingWizard } from '../utils/onboarding';

setup( 'setup site', async ( { baseURL, restApi } ) => {
	await setup.step( 'configure HPOS', async () => {
		const { DISABLE_HPOS } = process.env;
		console.log( `DISABLE_HPOS: ${ DISABLE_HPOS }` );

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
				const response = await restApi.post(
					`${ WC_API_PATH }/settings/advanced/woocommerce_custom_orders_table_enabled`,
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

		const response = await restApi.get(
			`${ WC_API_PATH }/settings/advanced/woocommerce_custom_orders_table_enabled`
		);
		const dataValue = response.data.value;
		const enabledOption = response.data.options[ dataValue ];
		console.log(
			`HPOS configuration (woocommerce_custom_orders_table_enabled): ${ dataValue } - ${ enabledOption }`
		);
	} );

	await setup.step( 'disable coming soon', async () => {
		await setComingSoon( { baseURL, enabled: 'no' } );
	} );

	await setup.step( 'disable onboarding wizard', async () => {
		await skipOnboardingWizard();
	} );

	await setup.step( 'determine if multisite', async () => {
		const response = await restApi.get( `${ WC_API_PATH }/system_status` );
		const { environment } = response.data;

		if ( environment.wp_multisite === false ) {
			delete process.env.IS_MULTISITE;
		} else {
			process.env.IS_MULTISITE = environment.wp_multisite;
			console.log( `IS_MULTISITE: ${ process.env.IS_MULTISITE }` );
		}
	} );

	await setup.step( 'general settings', async () => {
		await restApi.post( `${ WC_API_PATH }/settings/general/batch`, {
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
} );
