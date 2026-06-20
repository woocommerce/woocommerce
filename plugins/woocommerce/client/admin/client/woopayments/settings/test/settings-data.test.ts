/**
 * External dependencies
 */
import directApiFetch from '@wordpress/api-fetch';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockCreateSuccessNotice = jest.fn();
const mockCreateErrorNotice = jest.fn();
const mockGetSettings = jest.fn();
const mockRegister = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	combineReducers: jest.fn( ( reducers ) => reducers ),
	createReduxStore: jest.fn( ( name, config ) => ( {
		name,
		...config,
	} ) ),
	dispatch: jest.fn( ( storeName: string ) => {
		if ( storeName === 'core/notices' ) {
			return {
				createSuccessNotice: mockCreateSuccessNotice,
				createErrorNotice: mockCreateErrorNotice,
			};
		}

		return {
			startResolution: jest.fn(),
			finishResolution: jest.fn(),
		};
	} ),
	register: mockRegister,
	select: jest.fn( () => ( {
		getSettings: mockGetSettings,
	} ) ),
	useDispatch: jest.fn(),
	useSelect: jest.fn(),
} ) );

const mockDirectApiFetch = directApiFetch as jest.MockedFunction<
	typeof directApiFetch
>;

describe( 'WooPayments settings data store', () => {
	beforeEach( () => {
		mockCreateSuccessNotice.mockReset();
		mockCreateErrorNotice.mockReset();
		mockGetSettings.mockReset();
		mockRegister.mockReset();
		mockDirectApiFetch.mockReset();
		delete (
			window as typeof window & {
				wcSettings?: unknown;
				wcpaySettings?: unknown;
			}
		 ).wcSettings;
		delete (
			window as typeof window & {
				wcSettings?: unknown;
				wcpaySettings?: unknown;
			}
		 ).wcpaySettings;
	} );

	it( 'registers the public settings store name used by WooPayments settings components', async () => {
		const { STORE_NAME, store } = await import( '../data/store' );

		expect( STORE_NAME ).toBe( 'wc/payments/settings' );
		expect( store.name ).toBe( 'wc/payments/settings' );
	} );

	it( 'resolves settings from the preserved WooPayments settings endpoint', async () => {
		const { getSettings } = await import( '../data/resolvers' );
		const resolver = getSettings();

		expect( resolver.next().value ).toEqual( {
			type: 'API_FETCH',
			request: {
				path: '/wc/v3/payments/settings',
			},
		} );
	} );

	it( 'saves settings to the preserved WooPayments settings endpoint', async () => {
		const settings = {
			is_wcpay_enabled: true,
			enabled_payment_method_ids: [ 'card' ],
		};
		const response = {
			data: {
				woopay_last_disable_date: '2026-06-20',
				payment_method_statuses: {
					card_payments: {
						status: 'active',
						requirements: [],
					},
				},
			},
		};
		mockGetSettings.mockReturnValue( settings );

		const { saveSettings } = await import( '../data/actions' );
		const action = saveSettings();

		action.next();

		expect( action.next().value ).toEqual( {
			type: 'API_FETCH',
			request: {
				path: '/wc/v3/payments/settings',
				method: 'post',
				data: settings,
			},
		} );

		expect( action.next( response ).value ).toEqual( {
			type: 'SET_SETTINGS',
			data: {
				...settings,
				woopay_last_disable_date: '2026-06-20',
				payment_method_statuses: {
					card_payments: {
						status: 'active',
						requirements: [],
					},
				},
			},
		} );
		action.next();
		action.next();
		const result = action.next();

		expect( result.value ).toBe( true );
		expect( mockCreateSuccessNotice ).toHaveBeenCalledWith(
			'Settings saved.'
		);
	} );

	it( 'accepts unwrapped REST settings responses when saving settings', async () => {
		const settings = {
			is_wcpay_enabled: true,
			enabled_payment_method_ids: [ 'card' ],
		};
		mockGetSettings.mockReturnValue( settings );

		const { saveSettings } = await import( '../data/actions' );
		const action = saveSettings();

		action.next();
		action.next();
		action.next( {
			payment_method_statuses: {
				card_payments: {
					status: 'active',
					requirements: [],
				},
			},
		} );
		action.next();
		action.next();
		const result = action.next();

		expect( result.value ).toBe( true );
		expect( mockCreateSuccessNotice ).toHaveBeenCalledWith(
			'Settings saved.'
		);
	} );

	it( 'suppresses the raw server error notice when saving settings fails with field-level details', async () => {
		const settings = {
			is_wcpay_enabled: true,
		};
		const error = {
			server_error:
				'The statement descriptor contains invalid characters.',
			data: {
				details: {
					account_statement_descriptor: {
						message:
							'The statement descriptor contains invalid characters.',
					},
				},
			},
		};
		mockGetSettings.mockReturnValue( settings );

		const { saveSettings } = await import( '../data/actions' );
		const action = saveSettings();

		action.next();
		action.next();
		let result = action.throw( error );
		while ( ! result.done ) {
			result = action.next();
		}

		expect( result.value ).toBe( false );
		expect( mockCreateErrorNotice ).toHaveBeenCalledTimes( 1 );
		expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
			'Error saving settings.'
		);
	} );

	it( 'shows the raw server error notice when saving settings fails without field-level details', async () => {
		const settings = {
			is_wcpay_enabled: true,
		};
		const error = {
			server_error: 'The request could not be completed.',
			data: {
				details: {},
			},
		};
		mockGetSettings.mockReturnValue( settings );

		const { saveSettings } = await import( '../data/actions' );
		const action = saveSettings();

		action.next();
		action.next();
		let result = action.throw( error );
		while ( ! result.done ) {
			result = action.next();
		}

		expect( result.value ).toBe( false );
		expect( mockCreateErrorNotice ).toHaveBeenCalledTimes( 2 );
		expect( mockCreateErrorNotice ).toHaveBeenNthCalledWith(
			1,
			'Error saving settings.'
		);
		expect( mockCreateErrorNotice ).toHaveBeenNthCalledWith(
			2,
			'The request could not be completed.'
		);
	} );

	it( 'saves allowlisted options through the preserved option endpoint', async () => {
		mockDirectApiFetch.mockResolvedValue( {} );

		const { saveOption } = await import( '../data/actions' );

		await saveOption(
			'wcpay_fraud_protection_welcome_tour_dismissed',
			true
		);

		expect( mockDirectApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/settings/wcpay_fraud_protection_welcome_tour_dismissed',
			method: 'post',
			data: { value: true },
		} );
	} );

	it( 'reads account fees and dismissed duplicate notices from settings state', async () => {
		const selectors = await import( '../data/selectors' );
		const state = {
			settings: {
				data: {
					account_fees: {
						card: {
							base: {
								percentage_rate: 0.029,
								fixed_rate: 30,
								currency: 'USD',
							},
						},
					},
					dismissed_duplicate_payment_method_notices: {
						card: [ 'legacy_card_gateway' ],
					},
				},
			},
		};

		expect( selectors.getAccountFees( state ) ).toEqual( {
			card: {
				base: {
					percentage_rate: 0.029,
					fixed_rate: 30,
					currency: 'USD',
				},
			},
		} );
		expect(
			selectors.getDismissedDuplicatePaymentMethodNotices( state )
		).toEqual( {
			card: [ 'legacy_card_gateway' ],
		} );
	} );

	it( 'updates dismissed duplicate notices in the settings store', async () => {
		const { updateDismissedDuplicatePaymentMethodNotices } = await import(
			'../data/actions'
		);

		expect(
			updateDismissedDuplicatePaymentMethodNotices( {
				card: [ 'woocommerce_payments', 'legacy_card_gateway' ],
			} )
		).toEqual( {
			type: 'SET_SETTINGS_VALUES',
			payload: {
				dismissed_duplicate_payment_method_notices: {
					card: [ 'woocommerce_payments', 'legacy_card_gateway' ],
				},
			},
		} );
	} );

	it( 'does not export Stripe Billing migration actions, selectors, or hooks', async () => {
		const [ actions, selectors, hooks ] = await Promise.all( [
			import( '../data/actions' ),
			import( '../data/selectors' ),
			import( '../data/hooks' ),
		] );

		expect( actions ).not.toHaveProperty(
			'submitStripeBillingSubscriptionMigration'
		);
		expect( actions ).not.toHaveProperty( 'updateIsStripeBillingEnabled' );
		expect( selectors ).not.toHaveProperty( 'getIsStripeBillingEnabled' );
		expect( selectors ).not.toHaveProperty(
			'getIsStripeBillingMigrationInProgress'
		);
		expect( hooks ).not.toHaveProperty( 'useStripeBilling' );
		expect( hooks ).not.toHaveProperty( 'useStripeBillingMigration' );
		expect( JSON.stringify( actions ) ).not.toContain(
			'/settings/schedule-stripe-billing-migration'
		);
	} );

	it( 'reads settings bootstrap data from the Core-owned wcSettings admin payload', async () => {
		(
			window as typeof window & {
				wcSettings: {
					admin: {
						woopaymentsSettings: {
							accountStatus: string;
						};
					};
				};
				wcpaySettings: {
					accountStatus: string;
				};
			}
		 ).wcSettings = {
			admin: {
				woopaymentsSettings: {
					accountStatus: 'connected',
				},
			},
		};
		(
			window as typeof window & {
				wcpaySettings: {
					accountStatus: string;
				};
			}
		 ).wcpaySettings = {
			accountStatus: 'legacy-plugin-global',
		};

		const { getWooPaymentsSettingsBootstrap } = await import(
			'../bootstrap'
		);

		expect( getWooPaymentsSettingsBootstrap() ).toEqual( {
			accountStatus: 'connected',
		} );
	} );
} );
