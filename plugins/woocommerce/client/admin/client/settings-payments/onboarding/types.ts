/**
 * External dependencies
 */
import { type ReactNode } from 'react';
import { type RecommendedPaymentMethod } from '@woocommerce/data';

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

// To-do: Move WooPayments related types to a separate file.

/**
 * Props for the WooPayments onboarding modal.
 */
export interface WooPaymentsModalProps {
	isOpen: boolean;
	setIsOpen: ( isOpen: boolean ) => void;
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
	status?: 'completed' | 'incomplete';
	dependencies?: string[];
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
	};
	content?: ReactNode;
	context?: {
		payment_methods: RecommendedPaymentMethod[];
	};
}

/**
 * WooPayments provider onboarding context type.
 */
export interface OnboardingContextType {
	steps: WooPaymentsProviderOnboardingStep[];
	context: object;
	isLoading: boolean;
	currentStep: WooPaymentsProviderOnboardingStep | undefined;
	navigateToStep: ( stepKey: string ) => void;
	navigateToNextStep: () => void;
	getStepByKey: (
		stepKey: string
	) => WooPaymentsProviderOnboardingStep | undefined;
	refreshOnboardingSteps: () => void;
}
