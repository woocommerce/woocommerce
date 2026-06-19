/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { FraudProtectionAdvancedSettingsPage } from '../fraud-protection/advanced';
import {
	isSellingToAvsSupportedLocations,
	readRuleset,
	writeRuleset,
} from '../fraud-protection/advanced/utils';
import { Outcomes, Rules } from '../fraud-protection/advanced/constants';

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

jest.mock( '../data/hooks', () => ( {
	useSettings: () => mockUseSettings(),
	useCurrentProtectionLevel: () => mockUseCurrentProtectionLevel(),
	useAdvancedFraudProtectionSettings: () =>
		mockUseAdvancedFraudProtectionSettings(),
	useGetSettings: () => mockUseGetSettings(),
} ) );

const setProtectionLevel = jest.fn();
const setAdvancedFraudProtectionSettings = jest.fn();

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
		setHookDefaults();
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
