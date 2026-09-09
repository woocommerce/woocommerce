/**
 * Internal dependencies
 */
import { expect, test, tags, request } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { setFeatureFlag, resetFeatureFlags } from '../../utils/features';
import { setOption } from '../../utils/options';
import { wpCLI } from '../../utils/cli';

const compatibilityFailureFragments = [
	'private api',
	'privateapi',
	'wp private apis',
	'core/rich-text',
	'does not exist on window.wp',
];

const isCompatibilityFailure = ( message: string ): boolean => {
	const normalizedMessage = message
		.toLowerCase()
		.replaceAll( '-', ' ' )
		.replaceAll( '_', ' ' );

	return compatibilityFailureFragments.some( ( fragment ) =>
		normalizedMessage.includes( fragment )
	);
};

const recordCompatibilityFailure = (
	failures: string[],
	message: string
): void => {
	if ( isCompatibilityFailure( message ) ) {
		failures.push( message );
	}
};

const getUpdatedWeightUnit = ( originalUnit: string ): string =>
	originalUnit === 'kg' ? 'g' : 'kg';

const getBaseURL = ( baseURL: string | undefined ): string => {
	if ( ! baseURL ) {
		throw new Error( 'Expected baseURL to be configured.' );
	}

	return baseURL;
};

const getPostedFormValue = (
	postData: string | null,
	name: string
): string | null => {
	if ( ! postData ) {
		return null;
	}

	const urlEncodedMarker = `${ name }=`;
	const urlEncodedIndex = postData.indexOf( urlEncodedMarker );
	if ( urlEncodedIndex !== -1 ) {
		const start = urlEncodedIndex + urlEncodedMarker.length;
		const end = postData.indexOf( '&', start );
		return decodeURIComponent(
			end === -1 ? postData.slice( start ) : postData.slice( start, end )
		);
	}

	const multipartName = `name="${ name }"`;
	const nameIndex = postData.indexOf( multipartName );
	if ( nameIndex === -1 ) {
		return null;
	}

	const valueStart = postData.indexOf( '\r\n\r\n', nameIndex );
	if ( valueStart === -1 ) {
		return null;
	}

	const valueEnd = postData.indexOf( '\r\n', valueStart + 4 );
	if ( valueEnd === -1 ) {
		return null;
	}

	return postData.slice( valueStart + 4, valueEnd );
};

test.describe( 'Settings UI feature flag', { tag: [ tags.NOT_E2E ] }, () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeAll( async ( { baseURL } ) => {
		const url = getBaseURL( baseURL );

		await wpCLI(
			'wp plugin activate settings-ui-component-registration --skip-plugins'
		);
		await setFeatureFlag( request, url, 'settings-ui', true );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const url = getBaseURL( baseURL );

		await resetFeatureFlags( request, url );
		await setOption( request, url, 'woocommerce_enable_reviews', 'yes' );
		await setOption( request, url, 'woocommerce_manage_stock', 'yes' );
		await setOption( request, url, 'woocommerce_hold_stock_minutes', '60' );
		await setOption(
			request,
			url,
			'woocommerce_notify_low_stock_amount',
			'2'
		);
		await wpCLI(
			'wp plugin deactivate settings-ui-component-registration --skip-plugins'
		);
	} );

	test( 'loads the private DataForm runtime without compatibility failures', async ( {
		page,
	} ) => {
		const compatibilityFailures: string[] = [];

		page.on( 'pageerror', ( error ) => {
			recordCompatibilityFailure( compatibilityFailures, error.message );
		} );
		page.on( 'console', ( message ) => {
			recordCompatibilityFailure( compatibilityFailures, message.text() );
		} );

		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=products' );
		await expect( page.locator( '[data-wc-settings-ui]' ) ).toBeVisible();
		await expect(
			page.locator( 'link[href*="/settings-ui/style.css"]' )
		).toHaveCount( 1 );

		const settingsUI = page.locator( '[data-wc-settings-ui]' );
		await expect( settingsUI.locator( '.wc-settings-ui' ) ).toHaveCSS(
			'gap',
			'24px'
		);
		const dataForm = settingsUI
			.locator( '.dataforms-layouts__wrapper' )
			.first();
		await expect( dataForm ).toBeVisible();
		await expect(
			settingsUI.locator( '.wc-settings-ui__section-card' )
		).toHaveCount( 0 );

		const weightUnit = settingsUI.getByLabel( 'Weight unit' );
		expect( [ 'kg', 'g', 'lbs', 'oz' ] ).toContain(
			await weightUnit.inputValue()
		);
		await expect( weightUnit.locator( 'option' ) ).toHaveText( [
			'kg',
			'g',
			'lbs',
			'oz',
		] );

		const dataFormGlobalType = await page.evaluate( () => {
			const browserWindow = window as typeof window & {
				wc?: { settingsUi?: { DataForm?: unknown } };
			};

			return typeof browserWindow.wc?.settingsUi?.DataForm;
		} );
		expect( dataFormGlobalType ).toBe( 'undefined' );

		expect( compatibilityFailures ).toEqual( [] );
	} );

	test( 'saves a DataForm edit through the form post round-trip', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=products' );
		const settingsUI = page.locator( '[data-wc-settings-ui]' );
		await expect( settingsUI ).toBeVisible();

		const weightUnit = settingsUI.getByLabel( 'Weight unit' );
		const originalUnit = await weightUnit.inputValue();
		const updatedUnit = getUpdatedWeightUnit( originalUnit );
		const saveButton = settingsUI.getByRole( 'button', { name: 'Save' } );

		try {
			await expect( saveButton ).toBeDisabled();
			await weightUnit.selectOption( updatedUnit );
			await expect( saveButton ).toBeEnabled();
			await saveButton.click();

			await expect(
				page.getByText( 'Your settings have been saved.' )
			).toBeVisible();
			await page.reload();
			await expect( settingsUI.getByLabel( 'Weight unit' ) ).toHaveValue(
				updatedUnit
			);
		} finally {
			// The save persists a site-wide option, so restore it even when an
			// assertion fails. Plugins and themes are skipped because booting
			// the extensions earlier specs install can exhaust the CLI
			// container's memory before the command runs.
			await wpCLI(
				`wp option update woocommerce_weight_unit ${ originalUnit } --skip-plugins --skip-themes`
			);
		}
	} );

	test( 'toggles dependent fields with a visibility rule', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=products' );
		const settingsUI = page.locator( '[data-wc-settings-ui]' );
		await expect( settingsUI ).toBeVisible();

		const enableReviews = settingsUI.getByLabel( 'Enable product reviews' );
		const verifiedOwnerLabel = settingsUI.getByLabel(
			'Show "verified owner" label on customer reviews'
		);
		const verifiedOwnersOnly = settingsUI.getByLabel(
			'Reviews can only be left by "verified owners"'
		);
		const saveButton = settingsUI.getByRole( 'button', { name: 'Save' } );

		await expect( enableReviews ).toBeChecked();
		await expect( verifiedOwnerLabel ).toBeVisible();
		await expect( verifiedOwnersOnly ).toBeVisible();

		await enableReviews.uncheck();
		await expect( verifiedOwnerLabel ).toHaveCount( 0 );
		await expect( verifiedOwnersOnly ).toHaveCount( 0 );
		await expect( saveButton ).toBeEnabled();

		await enableReviews.check();
		await expect( verifiedOwnerLabel ).toBeVisible();
		await expect( verifiedOwnersOnly ).toBeVisible();
		// Restoring the original value leaves nothing to save.
		await expect( saveButton ).toBeDisabled();
	} );

	test( 'submits an untouched inventory value through classic sanitization', async ( {
		baseURL,
		page,
	} ) => {
		const url = getBaseURL( baseURL );
		await setFeatureFlag( request, url, 'settings-ui', true );
		await setOption( request, url, 'woocommerce_manage_stock', 'yes' );
		await setOption( request, url, 'woocommerce_hold_stock_minutes', '60' );
		await setOption(
			request,
			url,
			'woocommerce_notify_low_stock_amount',
			'02'
		);

		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=products&section=inventory'
		);

		const editedHoldStock = page.getByRole( 'spinbutton', {
			name: 'Hold stock (minutes)',
		} );
		const preservedLowStock = page.getByRole( 'spinbutton', {
			name: 'Low stock threshold',
		} );
		const lowStockFormValue = page.locator(
			'input[type="hidden"][name="woocommerce_notify_low_stock_amount"]'
		);

		const saveButton = page.getByRole( 'button', {
			name: 'Save',
			exact: true,
		} );

		await expect(
			page.locator( '[data-wc-settings-ui="1"]' )
		).toBeVisible();
		await expect( preservedLowStock ).toHaveValue( '2' );
		await expect( lowStockFormValue ).toHaveValue( '02' );
		await editedHoldStock.fill( '61' );
		await editedHoldStock.blur();
		await expect( saveButton ).toBeEnabled();
		await expect( lowStockFormValue ).toHaveValue( '02' );

		const saveRequestPromise = page.waitForRequest( ( httpRequest ) => {
			return (
				httpRequest.method() === 'POST' &&
				httpRequest.url().includes( 'page=wc-settings' )
			);
		} );
		await saveButton.click();
		const saveRequest = await saveRequestPromise;
		expect(
			getPostedFormValue(
				saveRequest.postData(),
				'woocommerce_notify_low_stock_amount'
			)
		).toBe( '02' );

		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect( preservedLowStock ).toBeVisible();
		await expect( preservedLowStock ).toHaveValue( '2' );
		await expect( editedHoldStock ).toHaveValue( '61' );

		const [ holdStockOption, lowStockOption ] = await Promise.all( [
			wpCLI(
				'wp option get woocommerce_hold_stock_minutes --skip-plugins'
			),
			wpCLI(
				'wp option get woocommerce_notify_low_stock_amount --skip-plugins'
			),
		] );
		expect( holdStockOption.stdout.trim() ).toBe( '61' );
		expect( lowStockOption.stdout.trim() ).toBe( '2' );
	} );

	test( 'loads a declared component registration before mounting settings', async ( {
		page,
	} ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=products&section=settings_ui_component_registered'
		);

		await expect(
			page.locator( '[data-wc-settings-ui="1"]' )
		).toBeVisible();
		await expect(
			page.getByTestId( 'settings-ui-registered-component' )
		).toContainText( 'Registered settings UI component' );

		const registeredInput = page.getByLabel( 'Registered component value' );
		await expect( registeredInput ).toHaveValue( 'Initial value' );
		await registeredInput.fill( 'Updated value' );
		await expect(
			page.locator(
				'input[name="settings_ui_component_registered_value"]'
			)
		).toHaveValue( 'Updated value' );
	} );

	test( 'fails closed when an executed script omits its component registration', async ( {
		page,
	} ) => {
		const settingsUrl =
			'wp-admin/admin.php?page=wc-settings&tab=products&section=settings_ui_component_missing&preserved=yes';

		await page.goto( settingsUrl );

		await expect( page.getByRole( 'textbox' ) ).toHaveCount( 0 );
		await expect( page.locator( '.woocommerce-save-button' ) ).toHaveCount(
			0
		);
		const classicAction = page.getByRole( 'link', {
			name: 'Use classic settings',
		} );
		await expect( classicAction ).toBeVisible();
		expect(
			await page.evaluate(
				() =>
					(
						window as unknown as {
							wcSettingsUIComponentTest?: {
								missingRegistrationScriptExecuted?: boolean;
							};
						}
					 ).wcSettingsUIComponentTest
						?.missingRegistrationScriptExecuted
			)
		).toBe( true );

		await classicAction.click();
		await expect( page ).toHaveURL( ( url ) => {
			const params = url.searchParams;

			return (
				params.get( 'wc_settings_ui' ) === 'classic' &&
				params.get( 'tab' ) === 'products' &&
				params.get( 'section' ) === 'settings_ui_component_missing' &&
				params.get( 'preserved' ) === 'yes'
			);
		} );
		await expect(
			page.locator( '#settings_ui_component_missing_value' )
		).toBeVisible();
		await expect( page.locator( '[data-wc-settings-ui]' ) ).toHaveCount(
			0
		);
		await expect(
			page.getByRole( 'button', { name: 'Save changes' } )
		).toBeVisible();
	} );
} );
