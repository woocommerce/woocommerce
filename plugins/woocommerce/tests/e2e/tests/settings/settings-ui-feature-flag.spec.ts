/**
 * Internal dependencies
 */
import { expect, test, tags, request } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { setFeatureFlag, resetFeatureFlags } from '../../utils/features';
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

const getBaseURL = ( baseURL: string | undefined ): string => {
	if ( ! baseURL ) {
		throw new Error( 'Expected baseURL to be configured.' );
	}

	return baseURL;
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
		await wpCLI(
			'wp plugin deactivate settings-ui-component-registration --skip-plugins'
		);
	} );

	test( 'loads the private DataForm runtime without compatibility failures', async ( {
		page,
	} ) => {
		const compatibilityFailures: string[] = [];
		const recordCompatibilityFailure = ( message: string ) => {
			if ( isCompatibilityFailure( message ) ) {
				compatibilityFailures.push( message );
			}
		};

		page.on( 'pageerror', ( error ) => {
			recordCompatibilityFailure( error.message );
		} );
		page.on( 'console', ( message ) => {
			recordCompatibilityFailure( message.text() );
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
		const updatedUnit = originalUnit === 'kg' ? 'g' : 'kg';
		const saveButton = settingsUI.getByRole( 'button', { name: 'Save' } );

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

		await settingsUI
			.getByLabel( 'Weight unit' )
			.selectOption( originalUnit );
		await settingsUI.getByRole( 'button', { name: 'Save' } ).click();
		await expect(
			page.getByText( 'Your settings have been saved.' )
		).toBeVisible();
		await expect( settingsUI.getByLabel( 'Weight unit' ) ).toHaveValue(
			originalUnit
		);
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
