/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { FraudProtectionAdvancedSettingsPage } from '../fraud-protection/advanced';
import {
	isSellingToAvsSupportedLocations,
	readRuleset,
	writeRuleset,
} from '../fraud-protection/advanced/utils';
import {
	CheckOperators,
	Checks,
	Outcomes,
	Rules,
} from '../fraud-protection/advanced/constants';

const mockCreateErrorNotice = jest.fn();
const mockSaveSettings = jest.fn();
const mockUseSettings = jest.fn();
const mockUseCurrentProtectionLevel = jest.fn();
const mockUseAdvancedFraudProtectionSettings = jest.fn();
const mockUseGetSettings = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	dispatch: jest.fn( () => ( {
		createErrorNotice: mockCreateErrorNotice,
	} ) ),
} ) );
jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '../data/hooks', () => ( {
	useSettings: () => mockUseSettings(),
	useCurrentProtectionLevel: () => mockUseCurrentProtectionLevel(),
	useAdvancedFraudProtectionSettings: () =>
		mockUseAdvancedFraudProtectionSettings(),
	useGetSettings: () => mockUseGetSettings(),
} ) );

const setProtectionLevel = jest.fn();
const setAdvancedFraudProtectionSettings = jest.fn();
const mockRecordEvent = recordEvent as jest.MockedFunction<
	typeof recordEvent
>;
const ADVANCED_RULE_CARD_VIEW_EVENTS = [
	[
		'avs-mismatch-card',
		'wcpay_fraud_protection_advanced_settings_card_avs_mismatch_viewed',
	],
	[
		'cvc-verification-card',
		'wcpay_fraud_protection_advanced_settings_card_cvc_verification_viewed',
	],
	[
		'international-ip-address-card',
		'wcpay_fraud_protection_advanced_settings_card_international_ip_address_card_viewed',
	],
	[
		'ip-address-mismatch-card',
		'wcpay_fraud_protection_advanced_settings_card_ip_address_mismatch_card_viewed',
	],
	[
		'address-mismatch-card',
		'wcpay_fraud_protection_advanced_settings_card_address_mismatch_viewed',
	],
	[
		'purchase-price-threshold-card',
		'wcpay_fraud_protection_advanced_settings_card_price_threshold_viewed',
	],
	[
		'order-items-threshold-card',
		'wcpay_fraud_protection_advanced_settings_card_items_threshold_viewed',
	],
] as const;

const getAdvancedCardEventNames = () =>
	mockRecordEvent.mock.calls
		.map( ( [ eventName ] ) => eventName )
		.filter(
			( eventName ): eventName is string =>
				typeof eventName === 'string' &&
				eventName.startsWith(
					'wcpay_fraud_protection_advanced_settings_card_'
				)
		);

type MockIntersectionObserverInstance = IntersectionObserver & {
	observe: jest.Mock;
	unobserve: jest.Mock;
	disconnect: jest.Mock;
};

type WindowWithWooSettings = Window & {
	wcSettings?: {
		countries?: Record< string, string >;
	};
};

let mockIntersectionObserverCallback: IntersectionObserverCallback;
let mockIntersectionObserverInstance: MockIntersectionObserverInstance;

const installMockIntersectionObserver = () => {
	window.IntersectionObserver = jest.fn(
		( callback: IntersectionObserverCallback ) => {
			mockIntersectionObserverCallback = callback;
			mockIntersectionObserverInstance = {
				root: null,
				rootMargin: '',
				thresholds: [ 1 ],
				observe: jest.fn(),
				unobserve: jest.fn(),
				disconnect: jest.fn(),
				takeRecords: jest.fn( () => [] ),
			} as MockIntersectionObserverInstance;

			return mockIntersectionObserverInstance;
		}
	);
};

const intersectElement = ( target: Element ) => {
	act( () => {
		mockIntersectionObserverCallback(
			[
				{
					isIntersecting: true,
					target,
				} as IntersectionObserverEntry,
			],
			mockIntersectionObserverInstance
		);
	} );
};

const setHookDefaults = () => {
	mockUseSettings.mockReturnValue( {
		isLoading: false,
		isSaving: false,
		isDirty: false,
		saveSettings: mockSaveSettings,
	} );
	mockUseCurrentProtectionLevel.mockReturnValue( [
		'basic',
		setProtectionLevel,
	] );
	mockUseAdvancedFraudProtectionSettings.mockReturnValue( [
		[],
		setAdvancedFraudProtectionSettings,
	] );
	mockUseGetSettings.mockReturnValue( {
		store_currency: 'USD',
		fraud_protection: {
			decline_on_avs_failure: true,
			decline_on_cvc_failure: true,
		},
		fraud_protection_allowed_countries: {
			type: 'all',
			countries: [],
		},
		is_fraud_protection_review_feature_active: false,
	} );
};

describe( 'fraud protection advanced ruleset utilities', () => {
	it( 'reads and writes provider fraud rulesets', () => {
		const uiSettings = readRuleset(
			[
				{
					key: Rules.RULE_AVS_VERIFICATION,
					outcome: Outcomes.BLOCK,
					check: {
						key: 'avs_mismatch',
						operator: 'equals',
						value: true,
					},
				},
			],
			{
				isReviewFeatureActive: false,
				isAvsFailureDeclineEnabled: false,
			}
		);

		expect( uiSettings[ Rules.RULE_AVS_VERIFICATION ].enabled ).toBe(
			true
		);

		const ruleset = writeRuleset(
			{
				[ Rules.RULE_PURCHASE_PRICE_THRESHOLD ]: {
					enabled: true,
					block: false,
					min_amount: '10',
					max_amount: '',
				},
			},
			{
				storeCurrency: 'USD',
				isReviewFeatureActive: true,
				allowedCountriesType: 'all',
				settingCountries: [],
			}
		);

		expect( ruleset ).toEqual( [
			expect.objectContaining( {
				key: Rules.RULE_PURCHASE_PRICE_THRESHOLD,
				outcome: Outcomes.REVIEW,
				check: expect.objectContaining( {
					key: 'order_total',
					operator: 'less_than',
					value: '1000|USD',
				} ),
			} ),
		] );
	} );

	it( 'evaluates AVS supported selling locations', () => {
		expect(
			isSellingToAvsSupportedLocations( {
				allowedCountriesType: 'all',
				settingCountries: [],
			} )
		).toBe( true );
		expect(
			isSellingToAvsSupportedLocations( {
				allowedCountriesType: 'specific',
				settingCountries: [ 'RO', 'US' ],
			} )
		).toBe( true );
		expect(
			isSellingToAvsSupportedLocations( {
				allowedCountriesType: 'specific',
				settingCountries: [ 'RO' ],
			} )
		).toBe( false );
		expect(
			isSellingToAvsSupportedLocations( {
				allowedCountriesType: 'all_except',
				settingCountries: [ 'US', 'CA', 'GB' ],
			} )
		).toBe( false );
	} );
} );

describe( 'FraudProtectionAdvancedSettingsPage', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		delete (
			window as typeof window & {
				IntersectionObserver?: typeof IntersectionObserver;
			}
		 ).IntersectionObserver;
		( window as WindowWithWooSettings ).wcSettings = {
			countries: {
				CA: 'Canada',
				CW: 'Cura&ccedil;ao',
				RO: 'Romania',
				US: 'United States',
			},
		};
		setHookDefaults();
	} );

	it( 'renders advanced fraud rule placeholders while settings load', () => {
		mockUseSettings.mockReturnValue( {
			isLoading: true,
			isSaving: false,
			isDirty: false,
			saveSettings: mockSaveSettings,
		} );

		render( <FraudProtectionAdvancedSettingsPage /> );

		expect(
			document.querySelector( '.woopayments-fraud-protection-advanced' )
		).toHaveAttribute( 'aria-busy', 'true' );
		expect(
			screen.getByRole( 'link', { name: 'Back to WooPayments settings' } )
		).toHaveAttribute(
			'href',
			expect.stringContaining( 'path=%2Fwoopayments%2Fsettings' )
		);
		expect(
			screen.getByRole( 'heading', {
				name: 'Advanced fraud protection',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', { name: 'Filter configuration' } )
		).toBeInTheDocument();
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Loading fraud protection rules'
		);
		expect(
			document.querySelectorAll(
				'.woopayments-fraud-protection-rule--loading'
			)
		).toHaveLength( ADVANCED_RULE_CARD_VIEW_EVENTS.length );
		expect(
			screen.queryByRole( 'checkbox', {
				name: 'Enable AVS Mismatch filter',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'marks the advanced fraud settings surface busy while saving', () => {
		mockUseSettings.mockReturnValue( {
			isLoading: false,
			isSaving: true,
			isDirty: true,
			saveSettings: mockSaveSettings,
		} );

		render( <FraudProtectionAdvancedSettingsPage /> );

		expect(
			document.querySelector( '.woopayments-fraud-protection-advanced' )
		).toHaveAttribute( 'aria-busy', 'true' );
		expect(
			screen.getByRole( 'button', { name: 'Save changes' } )
		).toBeDisabled();
	} );

	it( 'renders the advanced fraud protection rule configuration page', () => {
		render( <FraudProtectionAdvancedSettingsPage /> );

		expect(
			screen.getByRole( 'heading', {
				name: 'Advanced fraud protection',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'Back to WooPayments settings' } )
		).toHaveAttribute(
			'href',
			expect.stringContaining( 'path=%2Fwoopayments%2Fsettings' )
		);
		expect(
			screen.getByRole( 'heading', { name: 'Filter configuration' } )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Set up advanced fraud filters. Enable at least one filter to activate advanced protection.'
			)
		).toBeInTheDocument();
		[
			'AVS Mismatch',
			'CVC Verification',
			'International IP Address',
			'IP Address Mismatch',
			'Address Mismatch',
			'Purchase Price Threshold',
			'Order Items Threshold',
		].forEach( ( heading ) => {
			expect(
				screen.getByRole( 'heading', { name: heading } )
			).toBeInTheDocument();
		} );
		expect(
			screen.getByRole( 'button', { name: 'Save changes' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: /Learn more/ } )
		).toHaveAttribute(
			'href',
			expect.stringContaining(
				'/document/woopayments/fraud-and-disputes/fraud-protection/#advanced-configuration'
			)
		);
	} );

	it( 'links AVS unsupported-location warning to selling locations settings', () => {
		mockUseGetSettings.mockReturnValue( {
			store_currency: 'USD',
			fraud_protection: {
				decline_on_avs_failure: true,
				decline_on_cvc_failure: true,
			},
			fraud_protection_allowed_countries: {
				type: 'specific',
				countries: [ 'RO' ],
			},
			is_fraud_protection_review_feature_active: false,
		} );

		render( <FraudProtectionAdvancedSettingsPage /> );

		expect( screen.getByText( /None of your/ ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'selling locations' } )
		).toHaveAttribute( 'href', expect.stringContaining( 'tab=general' ) );
	} );

	it( 'renders International IP links and allowed-countries notice', () => {
		mockUseGetSettings.mockReturnValue( {
			store_currency: 'USD',
			fraud_protection: {
				decline_on_avs_failure: true,
				decline_on_cvc_failure: true,
			},
			fraud_protection_allowed_countries: {
				type: 'specific',
				countries: [ 'US', 'CA' ],
			},
			is_fraud_protection_review_feature_active: false,
		} );

		render( <FraudProtectionAdvancedSettingsPage /> );

		expect(
			screen.getByRole( 'link', { name: /IP addresses/ } )
		).toHaveAttribute(
			'href',
			'https://simple.wikipedia.org/wiki/IP_address'
		);
		expect(
			screen.getByRole( 'link', { name: 'supported countries' } )
		).toHaveAttribute( 'href', expect.stringContaining( 'tab=general' ) );
		expect(
			screen.getByText(
				/Orders from outside of the following countries will be blocked by the filter/
			)
		).toBeInTheDocument();
		expect(
			screen.getByText( 'United States, Canada' )
		).toBeInTheDocument();
	} );

	it( 'renders screened allowed-country copy when fraud review outcomes are active', () => {
		mockUseGetSettings.mockReturnValue( {
			store_currency: 'USD',
			fraud_protection: {
				decline_on_avs_failure: true,
				decline_on_cvc_failure: true,
			},
			fraud_protection_allowed_countries: {
				type: 'specific',
				countries: [ 'RO' ],
			},
			is_fraud_protection_review_feature_active: true,
		} );

		render( <FraudProtectionAdvancedSettingsPage /> );

		expect(
			screen.getByText(
				/Orders from outside of the following countries will be screened by the filter/
			)
		).toBeInTheDocument();
		expect( screen.getByText( 'Romania' ) ).toBeInTheDocument();
	} );

	it( 'renders all-except allowed-country copy', () => {
		mockUseGetSettings.mockReturnValue( {
			store_currency: 'USD',
			fraud_protection: {
				decline_on_avs_failure: true,
				decline_on_cvc_failure: true,
			},
			fraud_protection_allowed_countries: {
				type: 'all_except',
				countries: [ 'RO' ],
			},
			is_fraud_protection_review_feature_active: false,
		} );

		render( <FraudProtectionAdvancedSettingsPage /> );

		expect(
			screen.getByText(
				/Orders from the following countries will be blocked by the filter/
			)
		).toBeInTheDocument();
		expect( screen.getByText( 'Romania' ) ).toBeInTheDocument();
	} );

	it( 'renders screened all-except allowed-country copy', () => {
		mockUseGetSettings.mockReturnValue( {
			store_currency: 'USD',
			fraud_protection: {
				decline_on_avs_failure: true,
				decline_on_cvc_failure: true,
			},
			fraud_protection_allowed_countries: {
				type: 'all_except',
				countries: [ 'CW' ],
			},
			is_fraud_protection_review_feature_active: true,
		} );

		render( <FraudProtectionAdvancedSettingsPage /> );

		expect(
			screen.getByText(
				/Orders from the following countries will be screened by the filter/
			)
		).toBeInTheDocument();
		expect( screen.getByText( 'Curaçao' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Cura&ccedil;ao' )
		).not.toBeInTheDocument();
	} );

	it( 'links IP Address Mismatch copy to an IP address explainer', () => {
		render( <FraudProtectionAdvancedSettingsPage /> );

		expect(
			screen.getByRole( 'link', { name: /IP address/ } )
		).toHaveAttribute(
			'href',
			'https://simple.wikipedia.org/wiki/IP_address'
		);
	} );

	it( 'renders purchase price threshold limits with inline notices', async () => {
		render( <FraudProtectionAdvancedSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Enable Purchase Price Threshold filter',
			} )
		);

		expect( screen.getByText( 'Limits' ) ).toBeInTheDocument();
		expect(
			screen.getByLabelText( 'Minimum purchase price' )
		).toHaveAccessibleDescription(
			'Leave blank for no limit Amount is in USD.'
		);
		expect(
			screen.getByLabelText( 'Maximum purchase price' )
		).toHaveAccessibleDescription(
			'Leave blank for no limit Amount is in USD.'
		);
		expect(
			screen.getAllByText( 'Leave blank for no limit' )
		).toHaveLength( 2 );
		expect( screen.getAllByText( '$' ) ).toHaveLength( 2 );
		expect(
			screen.getByText(
				'A price range must be set for this filter to take effect.',
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();

		await userEvent.type(
			screen.getByLabelText( 'Minimum purchase price' ),
			'20'
		);
		await userEvent.type(
			screen.getByLabelText( 'Maximum purchase price' ),
			'10'
		);

		expect(
			screen.getByText(
				'Maximum purchase price must be greater than the minimum purchase price.',
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();
	} );

	it( 'renders order item threshold limits with inline notices', async () => {
		render( <FraudProtectionAdvancedSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Enable Order Items Threshold filter',
			} )
		);

		expect( screen.getByText( 'Limits' ) ).toBeInTheDocument();
		expect(
			screen.getByLabelText( 'Minimum items per order' )
		).toHaveAttribute( 'min', '1' );
		expect(
			screen.getByLabelText( 'Maximum items per order' )
		).toHaveAttribute( 'step', '1' );
		expect(
			screen.getAllByText( 'Leave blank for no limit' )
		).toHaveLength( 2 );
		expect(
			screen.getByText(
				'An item range must be set for this filter to take effect.',
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();

		await userEvent.type(
			screen.getByLabelText( 'Minimum items per order' ),
			'5'
		);
		await userEvent.type(
			screen.getByLabelText( 'Maximum items per order' ),
			'3'
		);

		expect(
			screen.getByText(
				'Maximum item count must be greater than the minimum item count.',
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();
	} );

	it( 'saves a valid purchase-price minimum threshold', async () => {
		mockSaveSettings.mockResolvedValueOnce( true );
		render( <FraudProtectionAdvancedSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Enable Purchase Price Threshold filter',
			} )
		);
		await userEvent.type(
			screen.getByLabelText( 'Minimum purchase price' ),
			'20'
		);

		expect(
			screen.queryByText(
				'A price range must be set for this filter to take effect.',
				{ selector: '.components-notice__content' }
			)
		).not.toBeInTheDocument();

		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Save changes' } )
			);
			await Promise.resolve();
		} );

		expect( setAdvancedFraudProtectionSettings ).toHaveBeenCalledWith(
			expect.arrayContaining( [
				expect.objectContaining( {
					key: Rules.RULE_PURCHASE_PRICE_THRESHOLD,
					outcome: Outcomes.BLOCK,
					check: expect.objectContaining( {
						key: Checks.CHECK_ORDER_TOTAL,
						operator: CheckOperators.OPERATOR_LT,
						value: '2000|USD',
					} ),
				} ),
			] )
		);
		expect( mockSaveSettings ).toHaveBeenCalled();
	} );

	it( 'saves a valid order-items maximum threshold', async () => {
		mockSaveSettings.mockResolvedValueOnce( true );
		render( <FraudProtectionAdvancedSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Enable Order Items Threshold filter',
			} )
		);
		await userEvent.type(
			screen.getByLabelText( 'Maximum items per order' ),
			'3'
		);

		expect(
			screen.queryByText(
				'An item range must be set for this filter to take effect.',
				{ selector: '.components-notice__content' }
			)
		).not.toBeInTheDocument();

		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Save changes' } )
			);
			await Promise.resolve();
		} );

		expect( setAdvancedFraudProtectionSettings ).toHaveBeenCalledWith(
			expect.arrayContaining( [
				expect.objectContaining( {
					key: Rules.RULE_ORDER_ITEMS_THRESHOLD,
					outcome: Outcomes.BLOCK,
					check: expect.objectContaining( {
						key: Checks.CHECK_ITEM_COUNT,
						operator: CheckOperators.OPERATOR_GT,
						value: 3,
					} ),
				} ),
			] )
		);
		expect( mockSaveSettings ).toHaveBeenCalled();
	} );

	it( 'adds rule context to filter action radio groups for screen readers', async () => {
		mockUseGetSettings.mockReturnValue( {
			store_currency: 'USD',
			fraud_protection: {
				decline_on_avs_failure: false,
				decline_on_cvc_failure: true,
			},
			fraud_protection_allowed_countries: {
				type: 'specific',
				countries: [ 'US' ],
			},
			is_fraud_protection_review_feature_active: true,
		} );
		render( <FraudProtectionAdvancedSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Enable AVS Mismatch filter',
			} )
		);

		expect(
			screen.getByRole( 'group', {
				name: 'Filter action for AVS Mismatch',
			} )
		).toBeInTheDocument();
	} );

	it( 'does not render unsavable rule controls when fraud settings failed to load', () => {
		mockUseAdvancedFraudProtectionSettings.mockReturnValue( [
			'error',
			setAdvancedFraudProtectionSettings,
		] );

		render( <FraudProtectionAdvancedSettingsPage /> );

		expect(
			screen.getByText(
				'There was an error retrieving your fraud protection settings. Please refresh the page to try again.',
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'checkbox', {
				name: 'Enable AVS Mismatch filter',
			} )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Save changes' } )
		).not.toBeInTheDocument();
	} );

	it( 'blocks saving advanced protection without enabled filters while still Basic', async () => {
		mockUseGetSettings.mockReturnValue( {
			store_currency: 'USD',
			fraud_protection: {
				decline_on_avs_failure: false,
				decline_on_cvc_failure: true,
			},
			fraud_protection_allowed_countries: {
				type: 'specific',
				countries: [ 'US' ],
			},
			is_fraud_protection_review_feature_active: false,
		} );
		render( <FraudProtectionAdvancedSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Enable Address Mismatch filter',
			} )
		);
		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Enable Address Mismatch filter',
			} )
		);
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Save changes' } )
		);

		expect( dispatch ).toHaveBeenCalledWith( 'core/notices' );
		expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
			'At least one risk filter needs to be enabled for advanced protection.'
		);
		expect( mockSaveSettings ).not.toHaveBeenCalled();
	} );

	it( 'saves enabled filters and switches the account to Advanced', async () => {
		mockSaveSettings.mockResolvedValueOnce( true );
		mockUseGetSettings.mockReturnValue( {
			store_currency: 'USD',
			fraud_protection: {
				decline_on_avs_failure: false,
				decline_on_cvc_failure: true,
			},
			fraud_protection_allowed_countries: {
				type: 'all',
				countries: [],
			},
			is_fraud_protection_review_feature_active: false,
		} );
		render( <FraudProtectionAdvancedSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Enable AVS Mismatch filter',
			} )
		);
		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Save changes' } )
			);
			await Promise.resolve();
		} );

		expect( setProtectionLevel ).toHaveBeenCalledWith( 'advanced' );
		expect( setAdvancedFraudProtectionSettings ).toHaveBeenCalledWith( [
			expect.objectContaining( {
				key: Rules.RULE_AVS_VERIFICATION,
				outcome: Outcomes.BLOCK,
			} ),
		] );
		expect( mockSaveSettings ).toHaveBeenCalled();
		await waitFor( () =>
			expect(
				screen.getByRole( 'button', { name: 'Save changes' } )
			).toBeDisabled()
		);
	} );

	it( 'records advanced fraud settings when saving enabled filters', async () => {
		mockSaveSettings.mockResolvedValueOnce( true );
		mockUseGetSettings.mockReturnValue( {
			store_currency: 'USD',
			fraud_protection: {
				decline_on_avs_failure: false,
				decline_on_cvc_failure: true,
			},
			fraud_protection_allowed_countries: {
				type: 'all',
				countries: [],
			},
			is_fraud_protection_review_feature_active: false,
		} );
		render( <FraudProtectionAdvancedSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Enable AVS Mismatch filter',
			} )
		);
		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Save changes' } )
			);
			await Promise.resolve();
		} );

		await waitFor( () =>
			expect( mockRecordEvent ).toHaveBeenCalledWith(
				'wcpay_fraud_protection_advanced_settings_saved',
				expect.any( Object )
			)
		);
		const saveEvent = mockRecordEvent.mock.calls.find(
			( [ eventName ] ) =>
				eventName === 'wcpay_fraud_protection_advanced_settings_saved'
		);
		expect( saveEvent ).toBeDefined();
		const properties = saveEvent?.[ 1 ] as { settings: string };
		expect( JSON.parse( properties.settings ) ).toEqual( [
			expect.objectContaining( {
				key: Rules.RULE_AVS_VERIFICATION,
				outcome: Outcomes.BLOCK,
			} ),
		] );
	} );

	it( 'does not record advanced fraud settings when saving enabled filters fails', async () => {
		mockSaveSettings.mockResolvedValueOnce( false );
		mockUseGetSettings.mockReturnValue( {
			store_currency: 'USD',
			fraud_protection: {
				decline_on_avs_failure: false,
				decline_on_cvc_failure: true,
			},
			fraud_protection_allowed_countries: {
				type: 'all',
				countries: [],
			},
			is_fraud_protection_review_feature_active: false,
		} );
		render( <FraudProtectionAdvancedSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Enable AVS Mismatch filter',
			} )
		);
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Save changes' } )
		);

		await waitFor( () => expect( mockSaveSettings ).toHaveBeenCalled() );
		expect(
			mockRecordEvent.mock.calls.some(
				( [ eventName ] ) =>
					eventName ===
					'wcpay_fraud_protection_advanced_settings_saved'
			)
		).toBe( false );
	} );

	it( 'records advanced rule card impressions once when cards become visible', () => {
		installMockIntersectionObserver();
		render( <FraudProtectionAdvancedSettingsPage /> );

		ADVANCED_RULE_CARD_VIEW_EVENTS.forEach( ( [ cardId ] ) => {
			const card = document.getElementById( cardId );
			expect( card ).not.toBeNull();
			expect(
				mockIntersectionObserverInstance.observe
			).toHaveBeenCalledWith( card );
		} );

		ADVANCED_RULE_CARD_VIEW_EVENTS.forEach( ( [ cardId ] ) => {
			intersectElement(
				document.getElementById( cardId ) as HTMLElement
			);
		} );
		intersectElement(
			document.getElementById( 'avs-mismatch-card' ) as HTMLElement
		);

		expect( getAdvancedCardEventNames() ).toEqual(
			ADVANCED_RULE_CARD_VIEW_EVENTS.map(
				( [ , eventName ] ) => eventName
			)
		);
	} );

	it( 'focuses the validation error when invalid thresholds block saving', async () => {
		render( <FraudProtectionAdvancedSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Enable Purchase Price Threshold filter',
			} )
		);
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Save changes' } )
		);

		await screen.findByText( /Settings were not saved/, {
			selector: '.components-notice__content',
		} );
		const errorContainer = document.querySelector(
			'.woopayments-fraud-protection-advanced__error'
		);

		expect( errorContainer ).not.toBeNull();
		await waitFor( () => expect( errorContainer ).toHaveFocus() );
		expect( mockSaveSettings ).not.toHaveBeenCalled();
	} );

	it( 'does not clobber existing beforeunload handlers while tracking dirty state', async () => {
		const existingBeforeUnloadHandler = jest.fn();
		window.onbeforeunload = existingBeforeUnloadHandler;

		const { unmount } = render( <FraudProtectionAdvancedSettingsPage /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Enable AVS Mismatch filter',
			} )
		);

		expect( window.onbeforeunload ).toBe( existingBeforeUnloadHandler );

		unmount();

		expect( window.onbeforeunload ).toBe( existingBeforeUnloadHandler );

		window.onbeforeunload = null;
	} );
} );
