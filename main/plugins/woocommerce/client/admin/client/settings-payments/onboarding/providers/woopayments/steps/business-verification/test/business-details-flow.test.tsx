/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import React from 'react';

/**
 * Internal dependencies
 */
import { useOnboardingContext } from '../../../data/onboarding-context';
import { useStepperContext } from '../components/stepper';
import { BusinessVerificationContextProvider } from '../data/business-verification-context';
import { OnboardingForm } from '../components/form';
import BusinessDetails from '../sections/business-details';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

jest.mock( '@wordpress/components', () => {
	const ReactModule = jest.requireActual< typeof import('react') >( 'react' );

	return {
		Button: ReactModule.forwardRef(
			(
				{
					children,
					isBusy,
					variant,
					...props
				}: React.ButtonHTMLAttributes< HTMLButtonElement > & {
					isBusy?: boolean;
					variant?: string;
				},
				ref: React.Ref< HTMLButtonElement >
			) => (
				<button
					{ ...props }
					ref={ ref }
					data-busy={ isBusy ? 'true' : undefined }
					data-variant={ variant }
				>
					{ children }
				</button>
			)
		),
		TextControl: ReactModule.forwardRef(
			(
				{
					label,
					onChange,
					value,
					...props
				}: React.InputHTMLAttributes< HTMLInputElement > & {
					label?: string;
					onChange?: ( value: string ) => void;
				},
				ref: React.Ref< HTMLInputElement >
			) => (
				<>
					{ label && <span>{ label }</span> }
					<input
						{ ...props }
						ref={ ref }
						aria-label={ label }
						value={ value }
						onChange={ ( event ) =>
							onChange?.( event.target.value )
						}
					/>
				</>
			)
		),
	};
} );

jest.mock( '../../../data/onboarding-context', () => ( {
	useOnboardingContext: jest.fn(),
} ) );

jest.mock( '../components/stepper', () => ( {
	useStepperContext: jest.fn(),
} ) );

jest.mock( '~/settings-payments/utils', () => ( {
	recordPaymentsOnboardingEvent: jest.fn(),
} ) );

const mockApiFetch = apiFetch as jest.Mock;
const mockUseOnboardingContext = useOnboardingContext as jest.Mock;
const mockUseStepperContext = useStepperContext as jest.Mock;
const mockNextStep = jest.fn();

const fields = {
	available_countries: {
		CA: 'Canada',
		GB: 'United Kingdom (UK)',
		JP: 'Japan',
		US: 'United States (US)',
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
		{
			key: 'CA',
			name: 'Canada',
			types: [
				{
					key: 'company',
					name: 'Company',
					description: '',
					structures: [
						{
							key: 'nil',
							name: 'None',
						},
					],
				},
			],
		},
		{
			key: 'GB',
			name: 'United Kingdom (UK)',
			types: [
				{
					key: 'individual',
					name: 'Individual',
					description: '',
					structures: [],
				},
			],
		},
		{
			key: 'JP',
			name: 'Japan',
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
					requires_structure: false,
					structures: [
						{
							key: 'sole_proprietorship',
							name: 'Sole proprietorship',
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

const createCurrentStep = () => ( {
	id: 'business_verification',
	status: 'not_started',
	actions: {
		save: {
			href: '/wc/v3/payments/onboarding/business-verification',
		},
	},
	context: {
		fields,
		self_assessment: {},
		sub_steps: {
			business: { status: 'not_started' },
			embedded: { status: 'not_started' },
		},
	},
} );

const renderBusinessDetailsForm = (
	initialData: Record< string, string | undefined > = {}
) => {
	return render(
		<BusinessVerificationContextProvider
			initialData={ {
				country: 'US',
				...initialData,
			} }
		>
			<OnboardingForm>
				<BusinessDetails />
			</OnboardingForm>
		</BusinessVerificationContextProvider>
	);
};

describe( 'Business details onboarding flow', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockApiFetch.mockResolvedValue( {} );
		mockUseOnboardingContext.mockReturnValue( {
			currentStep: createCurrentStep(),
			sessionEntryPoint: 'settings',
		} );
		mockUseStepperContext.mockReturnValue( {
			nextStep: mockNextStep,
		} );
	} );

	it( 'opens the business type options using the real business details form', () => {
		renderBusinessDetailsForm();

		fireEvent.click(
			screen.getByRole( 'combobox', {
				name: 'What type of legal entity is your business?',
			} )
		);

		expect(
			screen.getByRole( 'option', { name: /Company/ } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'option', { name: /Individual/ } )
		).toBeInTheDocument();
	} );

	it( 'selects a business type with keyboard commands and renders dependent fields', async () => {
		renderBusinessDetailsForm( {
			business_type: 'company',
		} );

		expect(
			screen.getByRole( 'combobox', {
				name: 'What category of legal entity identify your business?',
			} )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'combobox', {
				name: /What type of goods or services does your business sell?/,
			} )
		).not.toBeInTheDocument();

		const businessTypeSelect = screen.getByRole( 'combobox', {
			name: 'What type of legal entity is your business?',
		} );

		fireEvent.keyDown( businessTypeSelect, { key: 'ArrowDown' } );
		fireEvent.keyDown( businessTypeSelect, { key: 'ArrowDown' } );
		fireEvent.keyDown( businessTypeSelect, { key: 'Enter' } );

		await waitFor( () => {
			expect(
				screen.getByRole( 'combobox', {
					name: /What type of goods or services does your business sell?/,
				} )
			).toBeInTheDocument();
		} );

		expect(
			screen.queryByRole( 'combobox', {
				name: 'What category of legal entity identify your business?',
			} )
		).not.toBeInTheDocument();

		await waitFor( () => expect( mockApiFetch ).toHaveBeenCalled() );
		const savePayload = mockApiFetch.mock.calls.at( -1 )?.[ 0 ].data;

		expect( savePayload ).toEqual( {
			self_assessment: {
				business_type: 'individual',
				'company.structure': undefined,
			},
			source: 'settings',
		} );
	} );

	it( 'continues without showing optional business structure selection', async () => {
		renderBusinessDetailsForm( {
			country: 'JP',
			business_type: 'company',
			'company.structure': '',
			mcc: '5812',
		} );

		expect(
			screen.queryByRole( 'combobox', {
				name: 'What category of legal entity identify your business?',
			} )
		).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'combobox', {
				name: /What type of goods or services does your business sell?/,
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText( /By using WooPayments/ )
		).toBeInTheDocument();

		await waitFor( () => expect( mockApiFetch ).toHaveBeenCalled() );
		const savePayload = mockApiFetch.mock.calls.at( -1 )?.[ 0 ].data;

		expect( savePayload ).toEqual( {
			self_assessment: {
				country: 'JP',
				business_type: 'company',
				'company.structure': undefined,
				mcc: '5812',
			},
			source: 'settings',
		} );
	} );

	it( 'continues without showing business structure when nil is the only option', async () => {
		renderBusinessDetailsForm( {
			country: 'CA',
			business_type: 'company',
			'company.structure': 'nil',
			mcc: '5812',
		} );

		expect(
			screen.queryByRole( 'combobox', {
				name: 'What category of legal entity identify your business?',
			} )
		).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'combobox', {
				name: /What type of goods or services does your business sell?/,
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText( /By using WooPayments/ )
		).toBeInTheDocument();

		await waitFor( () => expect( mockApiFetch ).toHaveBeenCalled() );
		const savePayload = mockApiFetch.mock.calls.at( -1 )?.[ 0 ].data;

		expect( savePayload ).toEqual( {
			self_assessment: {
				country: 'CA',
				business_type: 'company',
				'company.structure': undefined,
				mcc: '5812',
			},
			source: 'settings',
		} );
	} );

	it( 'resets dependent business details when the country changes', async () => {
		renderBusinessDetailsForm( {
			business_type: 'company',
			'company.structure': 'llc',
		} );

		expect(
			screen.getByRole( 'combobox', {
				name: 'What category of legal entity identify your business?',
			} )
		).toBeInTheDocument();

		fireEvent.click(
			screen.getByRole( 'combobox', {
				name: 'Where is your business located?',
			} )
		);
		fireEvent.click(
			screen.getByRole( 'option', { name: 'United Kingdom (UK)' } )
		);

		await waitFor( () => {
			expect(
				screen.getByRole( 'combobox', {
					name: 'What type of legal entity is your business?',
				} )
			).toHaveTextContent( 'Select an option' );
		} );

		expect(
			screen.queryByRole( 'combobox', {
				name: 'What category of legal entity identify your business?',
			} )
		).not.toBeInTheDocument();

		await waitFor( () => expect( mockApiFetch ).toHaveBeenCalled() );
		const savePayload = mockApiFetch.mock.calls.at( -1 )?.[ 0 ].data;

		expect( savePayload ).toEqual( {
			self_assessment: {
				country: 'GB',
				business_type: undefined,
			},
			source: 'settings',
		} );
	} );

	it( 'marks the business sub-step complete when required details are present', async () => {
		renderBusinessDetailsForm( {
			business_type: 'individual',
			mcc: '5812',
		} );

		fireEvent.click( screen.getByRole( 'button', { name: 'Continue' } ) );

		await waitFor( () => expect( mockNextStep ).toHaveBeenCalled() );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			url: '/wc/v3/payments/onboarding/business-verification',
			method: 'POST',
			data: {
				sub_steps: {
					business: { status: 'completed' },
					embedded: { status: 'not_started' },
				},
			},
		} );
	} );
} );
