/**
 * External dependencies
 */
import {
	WC_ADMIN_API_PATH,
	WC_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,

	page: async ( { page, restApi }, use ) => {
		const initialTaskListState = await restApi.get(
			`${ WC_ADMIN_API_PATH }/options?options=woocommerce_task_list_hidden`
		);

		// Ensure task list is visible.
		await restApi.put( `${ WC_ADMIN_API_PATH }/options`, {
			woocommerce_task_list_hidden: 'no',
		} );

		await page.goto( 'wp-admin/admin.php?page=wc-admin' );

		await use( page );

		// Reset the task list to its initial state.
		await restApi.put(
			`${ WC_ADMIN_API_PATH }/options`,
			initialTaskListState.data
		);
	},

	nonSupportedWooPaymentsCountryPage: async ( { page, restApi }, use ) => {
		// Ensure store's base country location is a WooPayments non-supported country (e.g. AF).
		// Otherwise, the WooPayments task page logic or WooPayments redirects will kick in.
		const initialDefaultCountry = await restApi.get(
			`${ WC_API_PATH }/settings/general/woocommerce_default_country`
		);
		await restApi.put(
			`${ WC_API_PATH }/settings/general/woocommerce_default_country`,
			{
				value: 'AF',
			}
		);

		await use( page );

		// Reset the default country to its initial state.
		await restApi.put(
			`${ WC_API_PATH }/settings/general/woocommerce_default_country`,
			{
				value: initialDefaultCountry.data.value,
			}
		);
	},
} );

const SHIPPING_LABEL_PRINTING_STEP =
	/Enable shipping label printing and discounted rates/;
const shippingTaskTest = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
} );
const US_SHIPPING_PARTNER = {
	id: 'woocommerce-shipping',
	name: 'WooCommerce Shipping',
	slug: 'woocommerce-shipping',
	description: '',
	learn_more_link: 'https://woocommerce.com/products/shipping/',
	layout_column: {
		image: '',
		features: [],
	},
	available_layouts: [ 'column' ],
	is_visible: true,
};
const CHILE_SHIPPING_PARTNER = {
	id: 'envia',
	name: 'Envia',
	slug: '',
	description: '',
	learn_more_link:
		'https://woocommerce.com/products/envia-shipping-and-fulfillment/',
	layout_column: {
		image: '',
		features: [],
	},
	available_layouts: [ 'column' ],
	is_visible: true,
};

const mockShippingPartnerSuggestions = async ( page, shippingPartners ) => {
	await page.route(
		'**/wp-json/wc-admin/shipping-partner-suggestions*',
		( route ) =>
			route.fulfill( {
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify( shippingPartners ),
			} )
	);
};

const getMethodSettingsValues = ( settings = {} ) =>
	Object.entries( settings ).reduce( ( settingsValues, [ key, value ] ) => {
		if (
			value &&
			typeof value === 'object' &&
			Object.prototype.hasOwnProperty.call( value, 'value' )
		) {
			settingsValues[ key ] = value.value;
		}
		return settingsValues;
	}, {} );

const getNonDefaultShippingZones = async ( restApi ) => {
	const { data: zones } = await restApi.get(
		`${ WC_API_PATH }/shipping/zones`
	);
	const nonDefaultZones = zones.filter( ( zone ) => zone.id !== 0 );

	return Promise.all(
		nonDefaultZones.map( async ( zone ) => {
			const [ locationsResponse, methodsResponse ] = await Promise.all( [
				restApi.get(
					`${ WC_API_PATH }/shipping/zones/${ zone.id }/locations`
				),
				restApi.get(
					`${ WC_API_PATH }/shipping/zones/${ zone.id }/methods`
				),
			] );

			return {
				name: zone.name,
				order: zone.order,
				locations: locationsResponse.data.map( ( location ) => ( {
					code: location.code,
					type: location.type,
				} ) ),
				methods: methodsResponse.data.map( ( method ) => ( {
					method_id: method.method_id,
					enabled: method.enabled,
					order: method.order,
					settings: getMethodSettingsValues( method.settings ),
				} ) ),
			};
		} )
	);
};

const clearShippingZones = async ( restApi ) => {
	const { data: zones } = await restApi.get(
		`${ WC_API_PATH }/shipping/zones`
	);
	const deletableZones = zones.filter( ( zone ) => zone.id !== 0 );

	await Promise.all(
		deletableZones.map( ( zone ) =>
			restApi.delete( `${ WC_API_PATH }/shipping/zones/${ zone.id }`, {
				force: true,
			} )
		)
	);
};

const restoreShippingZones = async ( restApi, shippingZones ) => {
	await clearShippingZones( restApi );

	for ( const zone of shippingZones ) {
		const { data: createdZone } = await restApi.post(
			`${ WC_API_PATH }/shipping/zones`,
			{
				name: zone.name,
				order: zone.order,
			}
		);

		if ( zone.locations.length > 0 ) {
			await restApi.put(
				`${ WC_API_PATH }/shipping/zones/${ createdZone.id }/locations`,
				zone.locations
			);
		}

		for ( const method of zone.methods ) {
			const methodPayload = {
				method_id: method.method_id,
				enabled: method.enabled,
				order: method.order,
			};

			if ( Object.keys( method.settings ).length > 0 ) {
				methodPayload.settings = method.settings;
			}

			await restApi.post(
				`${ WC_API_PATH }/shipping/zones/${ createdZone.id }/methods`,
				methodPayload
			);
		}
	}
};

const updateStoreLocationForShippingTask = async (
	restApi,
	{ defaultCountry, storeAddress }
) => {
	const [
		initialShippingDefaultsOption,
		initialMarketplaceSuggestionsSetting,
		initialOnboardingProfile,
		initialDefaultCountry,
		initialStoreAddress,
		initialShippingZones,
	] = await Promise.all( [
		restApi.get(
			`${ WC_ADMIN_API_PATH }/options?options=woocommerce_admin_created_default_shipping_zones`
		),
		restApi.get(
			`${ WC_API_PATH }/settings/advanced/woocommerce_show_marketplace_suggestions`
		),
		restApi.get( `${ WC_ADMIN_API_PATH }/onboarding/profile` ),
		restApi.get(
			`${ WC_API_PATH }/settings/general/woocommerce_default_country`
		),
		restApi.get(
			`${ WC_API_PATH }/settings/general/woocommerce_store_address`
		),
		getNonDefaultShippingZones( restApi ),
	] );

	await clearShippingZones( restApi );
	await restApi.put( `${ WC_ADMIN_API_PATH }/options`, {
		woocommerce_admin_created_default_shipping_zones: 'no',
	} );
	await restApi.put(
		`${ WC_API_PATH }/settings/advanced/woocommerce_show_marketplace_suggestions`,
		{
			value: 'no',
		}
	);

	await restApi.put( `${ WC_ADMIN_API_PATH }/onboarding/profile`, {
		skipped: true,
	} );

	await restApi.put(
		`${ WC_API_PATH }/settings/general/woocommerce_default_country`,
		{
			value: defaultCountry,
		}
	);

	if ( typeof storeAddress === 'string' ) {
		await restApi.put(
			`${ WC_API_PATH }/settings/general/woocommerce_store_address`,
			{
				value: storeAddress,
			}
		);
	}

	return {
		defaultCountry: initialDefaultCountry.data.value,
		storeAddress: initialStoreAddress.data.value,
		shippingDefaultsOption: initialShippingDefaultsOption.data,
		marketplaceSuggestionsSetting:
			initialMarketplaceSuggestionsSetting.data.value,
		onboardingProfileSkipped: initialOnboardingProfile.data.skipped,
		shippingZones: initialShippingZones,
	};
};

const resetStoreLocationForShippingTask = async ( restApi, initialValues ) => {
	await restoreShippingZones( restApi, initialValues.shippingZones );

	await restApi.put(
		`${ WC_API_PATH }/settings/general/woocommerce_default_country`,
		{
			value: initialValues.defaultCountry,
		}
	);
	await restApi.put(
		`${ WC_API_PATH }/settings/general/woocommerce_store_address`,
		{
			value: initialValues.storeAddress,
		}
	);
	await restApi.put(
		`${ WC_ADMIN_API_PATH }/options`,
		initialValues.shippingDefaultsOption
	);
	await restApi.put(
		`${ WC_API_PATH }/settings/advanced/woocommerce_show_marketplace_suggestions`,
		{
			value: initialValues.marketplaceSuggestionsSetting,
		}
	);

	if ( typeof initialValues.onboardingProfileSkipped === 'boolean' ) {
		await restApi.put( `${ WC_ADMIN_API_PATH }/onboarding/profile`, {
			skipped: initialValues.onboardingProfileSkipped,
		} );
	}
};

const openShippingLabelPrintingStep = async ( page ) => {
	await page.goto( 'wp-admin/admin.php?page=wc-admin&task=shipping' );

	const skipGuidedSetupButton = page.getByRole( 'button', {
		name: 'Skip guided setup',
	} );
	if ( ( await skipGuidedSetupButton.count() ) > 0 ) {
		await skipGuidedSetupButton.first().click();
		await page.goto( 'wp-admin/admin.php?page=wc-admin&task=shipping' );
	}

	await expect
		.poll(
			() =>
				page
					.getByText( SHIPPING_LABEL_PRINTING_STEP, {
						exact: false,
					} )
					.count(),
			{ timeout: 15000 }
		)
		.toBeGreaterThan( 0 );

	const reviewShippingOptionsButton = page.getByRole( 'button', {
		name: /Review your shipping options/,
	} );
	if ( ( await reviewShippingOptionsButton.count() ) > 0 ) {
		await reviewShippingOptionsButton.click();
	}

	const saveShippingOptionsButton = page.getByRole( 'button', {
		name: /Save shipping options|Continue|Complete task/,
	} );
	await expect( saveShippingOptionsButton ).toBeVisible();
	await saveShippingOptionsButton.click();

	await expect(
		page.getByText( SHIPPING_LABEL_PRINTING_STEP, {
			exact: false,
		} )
	).toBeVisible();
};

test(
	'Can hide the task list',
	{ tag: [ tags.NOT_E2E ] },
	async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-admin' );
		await test.step( 'Load the WC Admin page.', async () => {
			await expect(
				page.getByRole( 'button', { name: 'Customize your store' } )
			).toBeVisible();
			await expect( page.getByText( 'Store management' ) ).toBeHidden();
		} );

		await test.step( 'Hide the task list', async () => {
			await page
				.getByRole( 'button', { name: 'Task List Options' } )
				.first()
				.click();
			await page
				.getByRole( 'button', { name: 'Hide setup list' } )
				.click();
			await expect(
				page.getByRole( 'heading', {
					name: 'Customize your store',
				} )
			).toBeHidden();
			await expect( page.getByText( 'Store management' ) ).toBeVisible();
		} );
	}
);

test(
	'Payments task list item links to Payments settings page',
	{ tag: [ tags.NOT_E2E ] },
	/**
	 * @param {{ nonSupportedWooPaymentsCountryPage: import('@playwright/test').Page }} page
	 */
	async ( { nonSupportedWooPaymentsCountryPage } ) => {
		await nonSupportedWooPaymentsCountryPage.goto(
			'wp-admin/admin.php?page=wc-admin'
		);
		await nonSupportedWooPaymentsCountryPage
			.locator( '.woocommerce-task-list__item' )
			.filter( { hasText: 'Set up payments' } )
			.click();

		await expect(
			nonSupportedWooPaymentsCountryPage.locator(
				'.woocommerce-layout__header-wrapper > h1'
			)
		).toHaveText( 'Settings' );
	}
);

test( 'Can connect to WooCommerce.com', async ( { page } ) => {
	await page.goto( 'wp-admin/admin.php?page=wc-admin' );
	await test.step( 'Go to WC Home and make sure the total sales is visible', async () => {
		await page
			.getByRole( 'menuitem', { name: 'Total sales' } )
			.waitFor( { state: 'visible', timeout: 30000 } );
	} );

	await test.step( 'Go to the extensions tab and connect store', async () => {
		const connectButton = page.getByRole( 'link', {
			name: 'Connect',
		} );

		// Set up response waiter BEFORE navigation to avoid race condition
		const waitForSubscriptionsResponse = page.waitForResponse(
			( response ) =>
				response
					.url()
					.includes( '/wp-json/wc/v3/marketplace/subscriptions' ) &&
				response.status() === 200
		);

		await page.goto(
			'wp-admin/admin.php?page=wc-admin&tab=my-subscriptions&path=%2Fextensions'
		);

		await expect(
			page.getByText(
				'Hundreds of vetted products and services. Unlimited potential.'
			)
		).toBeVisible( { timeout: 30000 } );
		await expect(
			page.getByRole( 'button', { name: 'My Subscriptions' } )
		).toBeVisible();
		await expect( connectButton ).toBeVisible();

		// Wait for the API response before checking button attributes
		await waitForSubscriptionsResponse;

		await expect( connectButton ).toHaveAttribute(
			'href',
			/my-subscriptions/
		);
		await connectButton.click();
	} );

	await test.step( 'Check that we are sent to wp.com', async () => {
		// Use polling assertion for URL check since page.url() is not auto-retrying
		await expect
			.poll( () => page.url(), { timeout: 30000 } )
			.toContain( 'wordpress.com/log-in' );
		await expect(
			page.getByRole( 'heading', {
				name: 'Log in to Woo with WordPress.com',
			} )
		).toBeVisible( { timeout: 30000 } );
	} );
} );

shippingTaskTest(
	'Shipping task shows install CTA for US stores',
	async ( { page, restApi } ) => {
		const initialValues = await updateStoreLocationForShippingTask(
			restApi,
			{
				defaultCountry: 'US:CA',
				storeAddress: '',
			}
		);

		try {
			await mockShippingPartnerSuggestions( page, [
				US_SHIPPING_PARTNER,
			] );
			await openShippingLabelPrintingStep( page );

			await expect(
				page.getByRole( 'button', { name: 'Install and enable' } )
			).toBeVisible();
		} finally {
			await resetStoreLocationForShippingTask( restApi, initialValues );
		}
	}
);

shippingTaskTest(
	'Shipping task shows download CTA for Chile stores',
	async ( { page, restApi } ) => {
		const initialValues = await updateStoreLocationForShippingTask(
			restApi,
			{
				defaultCountry: 'CL:CL-RM',
			}
		);

		try {
			await mockShippingPartnerSuggestions( page, [
				CHILE_SHIPPING_PARTNER,
			] );
			await openShippingLabelPrintingStep( page );

			await expect(
				page.getByRole( 'button', { name: 'Download' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'button', { name: 'Install and enable' } )
			).toHaveCount( 0 );
		} finally {
			await resetStoreLocationForShippingTask( restApi, initialValues );
		}
	}
);
