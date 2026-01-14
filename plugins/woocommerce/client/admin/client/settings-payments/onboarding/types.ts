/**
 * External dependencies
 */
import { type ReactNode } from 'react';
import {
	type RecommendedPaymentMethod,
	type PaymentsProvider,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	Country,
	MccsDisplayTreeItem,
} from './providers/woopayments/steps/business-verification/types'; // To-do: Maybe move to @woocommerce/data

/**
 * Error object returned from the onboarding backend.
 *
 * The backend always sanitizes errors to include both fields.
 * Fields may be empty strings if not provided by the upstream source.
 *
 * @see WooPaymentsService::sanitize_onboarding_step_errors()
 */
export interface OnboardingError {
	message: string;
	code: string;
}

/**
 * Props for the Onboarding Modal component.
 */
export interface OnboardingModalProps {
	setIsOpen: ( isOpen: boolean ) => void;
	children?: ReactNode;
}

/**
 * Sidebar navigation item props
 */
export interface SidebarItemProps {
	label: string;
	isCompleted?: boolean;
	isActive?: boolean;
}

/**
 * Props for the WooPayments onboarding modal.
 */
export interface WooPaymentsModalProps {
	isOpen: boolean;
	setIsOpen: ( isOpen: boolean ) => void;
	providerData: PaymentsProvider;
}

/**
 * WooPayments provider onboarding step that extends the base WooPaymentsOnboardingStepContent
 * with additional fields specific to the provider implementation.
 */
export interface WooPaymentsProviderOnboardingStep {
	id: string;
	type: 'backend' | 'frontend';
	label: string;
	path?: string;
	order: number;
	status?: 'not_started' | 'in_progress' | 'completed' | 'failed' | 'blocked';
	dependencies?: string[];
	subSteps?: WooPaymentsProviderOnboardingStep[];
	actions?: {
		save?: {
			type?: string;
			href?: string;
		};
		start?: {
			type?: string;
			href?: string;
		};
		finish?: {
			type?: string;
			href?: string;
		};
		init?: {
			type?: string;
			href?: string;
		};
		clean?: {
			type?: string;
			href?: string;
		};
		check?: {
			type?: string;
			href?: string;
		};
		kyc_fallback?: {
			type?: string;
			href?: string;
		};
		kyc_session?: {
			type?: string;
			href?: string;
		};
		kyc_session_finish?: {
			type?: string;
			href?: string;
		};
		auth?: {
			type?: string;
			href?: string;
		};
		reset?: {
			type?: string;
			href?: string;
		};
		test_account_disable?: {
			type?: string;
			href?: string;
		};
	};
	content?: ReactNode;
	context?: {
		recommended_pms: RecommendedPaymentMethod[];
		pms_state: Record< string, boolean >;
		overview_page_url?: string;
		fields: {
			business_types: Country[];
			industry_to_mcc: Record< string, string >;
			mccs_display_tree: MccsDisplayTreeItem;
			available_countries: Record< string, string >;
			location: string;
		};
		self_assessment: Record< string, string >;
		sub_steps: Record<
			string,
			{
				status: 'completed' | 'not_started' | 'started';
			}
		>;
		// True when a test (test-drive) account is connected.
		has_test_account?: boolean;
		// True when a sandbox (test-mode, non-test-drive) account is connected.
		has_sandbox_account?: boolean;
	};
	errors?: OnboardingError[];
}

/**
 * WooPayments provider onboarding context type.
 */
export interface OnboardingContextType {
	steps: WooPaymentsProviderOnboardingStep[];
	context: {
		urls?: {
			overview_page?: string;
		};
	};
	isLoading: boolean;
	currentStep: WooPaymentsProviderOnboardingStep | undefined;
	currentTopLevelStep: WooPaymentsProviderOnboardingStep | undefined;
	navigateToStep: ( stepKey: string ) => void;
	navigateToNextStep: () => void;
	getStepByKey: (
		stepKey: string
	) => WooPaymentsProviderOnboardingStep | undefined;
	refreshStoreData: () => void;
	closeModal: () => void;
	justCompletedStepId: string | null;
	setJustCompletedStepId: ( stepId: string ) => void;
	sessionEntryPoint: string;
	snackbar: {
		show: boolean;
		message: string;
		className?: string;
		duration?: number;
	};
	setSnackbar: ( snackbar: {
		show: boolean;
		message: string;
		duration?: number;
		className?: string;
	} ) => void;
}
