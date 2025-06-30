/**
 * External dependencies
 */
import {
	percent,
	shipping,
	brush,
	tag,
	payment,
	check,
} from '@wordpress/icons';
/**
 * Internal dependencies
 */
import CompletedStep from './payments-icons/completed-step';
import ActiveStep from './payments-icons/active-step';
export const taskCompleteIcon = check;

export const taskIcons: Record< string, JSX.Element > = {
	tax: percent,
	shipping,
	'customize-store': brush,
	payments: payment,
	'woocommerce-payments': payment,
	products: tag,
	activePaymentStep: ActiveStep,
	completedPaymentStep: CompletedStep,
};
