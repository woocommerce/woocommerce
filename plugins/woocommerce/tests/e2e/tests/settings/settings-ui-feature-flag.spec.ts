/**
 * Internal dependencies
 */
import { expect, test, tags, request } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { setFeatureFlag, resetFeatureFlags } from '../../utils/features';
import { setOption } from '../../utils/options';

type BrowserReactRoot = {
	render: ( element: unknown ) => void;
	unmount: () => void;
};

type BrowserElementAPI = {
	createElement: (
		type: unknown,
		props: Record< string, unknown >
	) => unknown;
	createRoot: ( container: Element ) => BrowserReactRoot;
};

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

	test( 'mounts the DataForm runtime against WordPress globals', async ( {
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

		await page.evaluate( () => {
			const browserWindow = window as typeof window & {
				wc?: { settingsUi?: { DataForm?: unknown } };
				wp?: { element?: BrowserElementAPI };
				__wooprd3591DataFormRoot?: BrowserReactRoot;
				__wooprd3591DataFormChanges?: unknown[];
			};
			const DataForm = browserWindow.wc?.settingsUi?.DataForm;
			const element = browserWindow.wp?.element;

			if ( ! DataForm || ! element?.createRoot ) {
				throw new Error(
					'Expected the Settings UI DataForm runtime and WordPress element API.'
				);
			}

			// WOOPRD-3596 will replace the native renderer with DataForm. Remove
			// the current renderer before mounting this compatibility fixture so
			// the test exercises that target topology instead of two renderers.
			document.querySelector( '[data-wc-settings-ui]' )?.remove();

			const container = document.createElement( 'div' );
			container.id = 'wooprd-3591-dataform-smoke';
			document.body.appendChild( container );

			const fields = [
				{ id: 'title', label: 'Runtime title', type: 'text' },
				{
					id: 'choice',
					label: 'Runtime choice',
					type: 'text',
					elements: [
						{ value: 'one', label: 'One' },
						{ value: 'two', label: 'Two' },
					],
				},
				{
					id: 'multipleChoices',
					label: 'Runtime multiple choices',
					type: 'array',
					elements: [
						{ value: 'one', label: 'One' },
						{ value: 'two', label: 'Two' },
					],
				},
				{
					id: 'enabled',
					label: 'Runtime enabled',
					type: 'boolean',
					Edit: 'checkbox',
				},
				{
					id: 'notes',
					label: 'Runtime notes',
					type: 'text',
					Edit: 'textarea',
				},
				{ id: 'amount', label: 'Runtime amount', type: 'number' },
				{
					id: 'publishDate',
					label: 'Runtime publish date',
					type: 'date',
				},
				{
					id: 'publishAt',
					label: 'Runtime publish at',
					type: 'datetime',
					Edit: { control: 'datetime', compact: true },
				},
			];
			const data = {
				title: 'Original title',
				choice: 'one',
				multipleChoices: [ 'one' ],
				enabled: true,
				notes: 'Initial notes',
				amount: 2.5,
				publishDate: '2026-08-03',
				publishAt: '2026-08-03T10:00:00Z',
			};
			const root = element.createRoot( container );
			browserWindow.__wooprd3591DataFormRoot = root;
			browserWindow.__wooprd3591DataFormChanges = [];
			root.render(
				element.createElement( DataForm, {
					data,
					fields,
					form: { fields: fields.map( ( field ) => field.id ) },
					onChange: ( nextData: unknown ) => {
						browserWindow.__wooprd3591DataFormChanges?.push(
							nextData
						);
					},
				} )
			);
		} );

		const fixture = page.locator( '#wooprd-3591-dataform-smoke' );
		await expect( fixture.getByLabel( 'Runtime title' ) ).toHaveValue(
			'Original title'
		);
		await expect( fixture.getByLabel( 'Runtime choice' ) ).toHaveValue(
			'one'
		);
		await expect(
			fixture.getByLabel( 'Runtime multiple choices' ).first()
		).toBeVisible();
		await expect( fixture.getByLabel( 'Runtime enabled' ) ).toBeChecked();
		await expect( fixture.getByLabel( 'Runtime notes' ) ).toHaveValue(
			'Initial notes'
		);
		await expect( fixture.getByLabel( 'Runtime amount' ) ).toHaveValue(
			'2.5'
		);
		await expect( fixture.locator( 'input[type="date"]' ) ).toBeVisible();
		await expect(
			fixture.locator( 'input[type="datetime-local"]' )
		).toBeVisible();

		await fixture.getByLabel( 'Runtime title' ).fill( 'Updated title' );
		await expect
			.poll( () =>
				page.evaluate( () => {
					const browserWindow = window as typeof window & {
						__wooprd3591DataFormChanges?: Array< {
							title?: string;
						} >;
					};
					return browserWindow.__wooprd3591DataFormChanges?.at( -1 )
						?.title;
				} )
			)
			.toBe( 'Updated title' );

		await page.evaluate( () => {
			const browserWindow = window as typeof window & {
				__wooprd3591DataFormRoot?: BrowserReactRoot;
			};
			browserWindow.__wooprd3591DataFormRoot?.unmount();
			document.querySelector( '#wooprd-3591-dataform-smoke' )?.remove();
			delete browserWindow.__wooprd3591DataFormRoot;
		} );

		expect( compatibilityFailures ).toEqual( [] );
	} );
} );
