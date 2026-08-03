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

const getBaseURL = ( baseURL: string | undefined ): string => {
	if ( ! baseURL ) {
		throw new Error( 'Expected baseURL to be configured.' );
	}

	return baseURL;
};

test.describe( 'Settings UI feature flag', { tag: tags.NOT_E2E }, () => {
	test.describe.configure( { mode: 'serial' } );
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeAll( async () => {
		await wpCLI(
			'ln -sfn /var/www/html/wp-content/plugins/woocommerce/tests/e2e/test-plugins/settings-ui-component-registration /var/www/html/wp-content/plugins/settings-ui-component-registration'
		);
		await wpCLI( 'wp plugin activate settings-ui-component-registration' );
	} );

	test.beforeEach( async ( { baseURL } ) => {
		const url = getBaseURL( baseURL );

		await setFeatureFlag( request, url, 'settings-ui', false );
		await setOption( request, url, 'woocommerce_enable_reviews', 'yes' );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const url = getBaseURL( baseURL );

		await resetFeatureFlags( request, url );
		await setOption( request, url, 'woocommerce_enable_reviews', 'yes' );
		await wpCLI(
			'wp plugin deactivate settings-ui-component-registration'
		);
		await wpCLI(
			'unlink /var/www/html/wp-content/plugins/settings-ui-component-registration'
		);
	} );

	test( 'does not mount the settings UI when the feature flag is disabled', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=products' );

		await expect(
			page.locator( '#woocommerce_enable_reviews' )
		).toBeVisible();
		await expect( page.locator( '[data-wc-settings-ui]' ) ).toHaveCount(
			0
		);
		await page.locator( '#woocommerce_enable_reviews' ).uncheck();
		await page.getByRole( 'button', { name: 'Save changes' } ).click();

		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect(
			page.locator( '#woocommerce_enable_reviews' )
		).not.toBeChecked();
	} );

	test( 'loads the private DataForm runtime without compatibility failures', async ( {
		page,
		baseURL,
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

		await setFeatureFlag(
			request,
			getBaseURL( baseURL ),
			'settings-ui',
			true
		);
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
		const sectionCard = settingsUI
			.locator( '.wc-settings-ui__section-card' )
			.first();
		await expect( sectionCard ).toHaveCSS( 'display', 'flex' );
		await expect( sectionCard ).toHaveCSS( 'border-top-width', '1px' );
		await expect( sectionCard ).toHaveCSS( 'border-radius', '8px' );
		await expect( sectionCard ).toHaveCSS(
			'background-color',
			'rgb(255, 255, 255)'
		);
		await expect(
			sectionCard.locator( '.wc-settings-ui__section-header' )
		).toHaveCSS( 'padding', '24px' );
		await expect(
			sectionCard.locator( '.wc-settings-ui__section-fields' )
		).toHaveCSS( 'padding', '0px 24px 24px' );

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

	test( 'loads a declared component registration before mounting settings', async ( {
		baseURL,
		page,
	} ) => {
		const url = getBaseURL( baseURL );
		await setFeatureFlag( request, url, 'settings-ui', true );

		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=products&section=settings_ui_component_registered'
		);

		await expect(
			page.locator( '[data-wc-settings-ui="1"]' )
		).toBeVisible();
		await expect(
			page.getByTestId( 'settings-ui-registered-component' )
		).toContainText( 'Registered settings UI component' );
		await expect
			.poll( () =>
				page.evaluate(
					() =>
						(
							window as unknown as {
								wcSettingsUIComponentTest?: {
									registeredScriptExecuted?: boolean;
								};
							}
						 ).wcSettingsUIComponentTest?.registeredScriptExecuted
				)
			)
			.toBe( true );
	} );

	test( 'uses classic output when a declared script handle is not registered', async ( {
		baseURL,
		page,
	} ) => {
		const url = getBaseURL( baseURL );
		await setFeatureFlag( request, url, 'settings-ui', true );

		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=products&section=settings_ui_component_unregistered'
		);

		await expect(
			page.locator( '#settings_ui_component_unregistered_value' )
		).toBeVisible();
		await expect( page.locator( '[data-wc-settings-ui]' ) ).toHaveCount(
			0
		);
		await expect(
			page.getByRole( 'button', { name: 'Save changes' } )
		).toBeVisible();
	} );

	test( 'fails closed when an executed script omits its component registration', async ( {
		baseURL,
		page,
	} ) => {
		const url = getBaseURL( baseURL );
		await setFeatureFlag( request, url, 'settings-ui', true );
		const settingsUrl =
			'wp-admin/admin.php?page=wc-settings&tab=products&section=settings_ui_component_missing&preserved=yes';

		await page.goto( settingsUrl );

		await expect
			.poll( () =>
				page.evaluate(
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
			)
			.toBe( true );
		await expect( page.getByRole( 'textbox' ) ).toHaveCount( 0 );
		await expect( page.locator( '.woocommerce-save-button' ) ).toHaveCount(
			0
		);
		const classicAction = page.getByRole( 'link', {
			name: 'Use classic settings',
		} );
		await expect( classicAction ).toBeVisible();

		const classicHref = await classicAction.getAttribute( 'href' );
		expect( classicHref ).not.toBeNull();
		const classicUrl = new URL( classicHref as string );
		expect( classicUrl.searchParams.getAll( 'wc_settings_ui' ) ).toEqual( [
			'classic',
		] );
		expect( classicUrl.searchParams.get( 'page' ) ).toBe( 'wc-settings' );
		expect( classicUrl.searchParams.get( 'tab' ) ).toBe( 'products' );
		expect( classicUrl.searchParams.get( 'section' ) ).toBe(
			'settings_ui_component_missing'
		);
		expect( classicUrl.searchParams.get( 'preserved' ) ).toBe( 'yes' );

		await classicAction.click();
		await expect( page ).toHaveURL( /wc_settings_ui=classic/ );
		await expect(
			page.locator( '#settings_ui_component_missing_value' )
		).toBeVisible();
		await expect( page.locator( '[data-wc-settings-ui]' ) ).toHaveCount(
			0
		);
		await expect(
			page.getByRole( 'button', { name: 'Save changes' } )
		).toBeVisible();

		await page.goto( settingsUrl );
		await expect(
			page.getByRole( 'link', { name: 'Use classic settings' } )
		).toBeVisible();
	} );
} );
