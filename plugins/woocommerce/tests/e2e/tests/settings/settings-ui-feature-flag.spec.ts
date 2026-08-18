/**
 * Internal dependencies
 */
import { expect, test, tags, request } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { setFeatureFlag, resetFeatureFlags } from '../../utils/features';
import { setOption } from '../../utils/options';

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
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeEach( async ( { baseURL } ) => {
		const url = getBaseURL( baseURL );

		await setFeatureFlag( request, url, 'settings-ui', false );
		await setOption( request, url, 'woocommerce_enable_reviews', 'yes' );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const url = getBaseURL( baseURL );

		await resetFeatureFlags( request, url );
		await setOption( request, url, 'woocommerce_enable_reviews', 'yes' );
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
} );
