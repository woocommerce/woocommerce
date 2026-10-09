/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

// Match the WC Admin payments providers endpoint, allowing optional query args.
const PROVIDERS_ENDPOINT =
	/\/wp-json\/wc-admin\/settings\/payments\/providers(\?.*)?$/;
// Match the WooPayments onboarding endpoint without matching nested step endpoints.
const ONBOARDING_ENDPOINT =
	/\/wp-json\/wc-admin\/settings\/payments\/woopayments\/onboarding(\?.*)?$/;
const WOO_PAYMENTS_PROVIDER = {
	_type: 'gateway',
	_order: 1,
	id: 'woocommerce_payments',
	title: 'WooPayments',
	description:
		'Accept credit cards and other payment methods with WooPayments.',
	icon: '',
	plugin: {
		slug: 'woocommerce-payments',
		file: 'woocommerce-payments/woocommerce-payments.php',
		status: 'active',
	},
	supports: [],
	management: {
		_links: {
			settings: {
				href: '/wp-admin/admin.php?page=wc-settings&tab=checkout',
			},
		},
	},
	state: {
		enabled: false,
		account_connected: false,
		needs_setup: true,
		test_mode: false,
		dev_mode: false,
	},
	onboarding: {
		type: 'native_in_context',
		state: {
			supported: true,
			started: false,
			completed: false,
			test_mode: false,
			wpcom_has_working_connection: true,
		},
		messages: {},
		_links: {
			onboard: { href: '#' },
			reset: { href: '#' },
		},
		recommended_payment_methods: [],
	},
	_links: {},
};

const ONBOARDING_FIELDS = {
	available_countries: {
		US: 'United States (US)',
		GB: 'United Kingdom (UK)',
	},
	business_types: [
		{
			key: 'US',
			name: 'United States (US)',
			types: [
				{
					key: 'individual',
					name: 'Individual',
					description: '',
					structures: [],
				},
				{
					key: 'company',
					name: 'Company',
					description: '',
					structures: [
						{
							key: 'llc',
							name: 'Limited liability company',
						},
					],
				},
			],
		},
	],
	mccs_display_tree: [
		{
			id: 'food-and-drink',
			type: 'category',
			title: 'Food and drink',
			items: [
				{
					id: '5812',
					type: 'mcc',
					title: 'Restaurants',
					mcc: 5812,
					keywords: [ 'food' ],
				},
			],
		},
	],
	industry_to_mcc: {},
	location: 'US',
};

const ONBOARDING_RESPONSE = {
	steps: [
		{
			id: 'payment_methods',
			label: 'Choose your payment methods',
			path: '/woopayments/onboarding/payment-methods',
			order: 1,
			status: 'completed',
			dependencies: [],
			actions: {},
			context: {
				recommended_pms: [],
				pms_state: {},
			},
		},
		{
			id: 'wpcom_connection',
			label: 'Connect with WordPress.com',
			path: '/woopayments/onboarding/wpcom-connection',
			order: 2,
			status: 'completed',
			dependencies: [ 'payment_methods' ],
			actions: {},
			context: {},
		},
		{
			id: 'business_verification',
			label: 'Activate payments',
			path: '/woopayments/onboarding/business-verification',
			order: 3,
			status: 'started',
			dependencies: [ 'test_or_live_account' ],
			actions: {},
			context: {
				fields: ONBOARDING_FIELDS,
				self_assessment: {},
				sub_steps: {
					business: { status: 'not_started' },
					embedded: { status: 'not_started' },
				},
			},
		},
	],
	context: {},
};

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
} );

test.describe(
	'NOX onboarding critical flows',
	{ tag: [ tags.PAYMENTS ] },
	() => {
		test.beforeEach( async ( { page } ) => {
			await page.route( PROVIDERS_ENDPOINT, async ( route ) => {
				await route.fulfill( {
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify( {
						providers: [ WOO_PAYMENTS_PROVIDER ],
						offline_payment_methods: [],
						suggestions: [],
						suggestion_categories: [],
					} ),
				} );
			} );

			await page.route( ONBOARDING_ENDPOINT, async ( route ) => {
				await route.fulfill( {
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify( ONBOARDING_RESPONSE ),
				} );
			} );
		} );

		test( 'can start in-context onboarding from Payments settings', async ( {
			page,
		} ) => {
			const consoleErrors: string[] = [];
			page.on( 'console', ( message ) => {
				if ( message.type() === 'error' ) {
					consoleErrors.push( message.text() );
				}
			} );

			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=checkout&from=wc_settings_payments'
			);

			await expect(
				page.getByText( 'Payment providers', { exact: true } )
			).toBeVisible( { timeout: 30000 } );

			await page
				.locator( '.woocommerce-list__item' )
				.filter( { hasText: 'WooPayments' } )
				.getByRole( 'button', { name: 'Complete setup' } )
				.click();

			await expect(
				page.getByRole( 'heading', { name: 'Set up WooPayments' } )
			).toBeVisible();
			await expect(
				page.getByText( 'Activate payments', { exact: true } )
			).toBeVisible();

			// Guard against runtime failures seen when entering the in-context
			// onboarding shell without failing on unrelated WordPress admin console noise.
			expect(
				consoleErrors.filter(
					( message ) =>
						message.includes( 'stateReducer' ) ||
						message.includes(
							'Cannot read properties of undefined'
						) ||
						message.includes( 'TypeError' )
				)
			).toEqual( [] );
		} );
	}
);
