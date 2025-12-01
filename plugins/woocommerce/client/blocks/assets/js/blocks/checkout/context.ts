/**
 * External dependencies
 */
import {
	FormFields,
	defaultFields as defaultFieldsSetting,
} from '@woocommerce/settings';
import { createContext, useContext } from '@wordpress/element';

/**
 * Context consumed by inner blocks.
 */
export type CheckoutBlockContextProps = {
	showOrderNotes: boolean;
	showPolicyLinks: boolean;
	showReturnToCart: boolean;
	cartPageId: number;
	showRateAfterTaxName: boolean;
	showFormStepNumbers: boolean;
	defaultFields: FormFields;
};

const defaultCheckoutBlockContext = {
	showOrderNotes: true,
	showPolicyLinks: true,
	showReturnToCart: true,
	cartPageId: 0,
	showRateAfterTaxName: false,
	showFormStepNumbers: false,
	defaultFields: defaultFieldsSetting,
};

export const CheckoutBlockContext: React.Context<
	Partial< CheckoutBlockContextProps >
> = createContext< CheckoutBlockContextProps >( defaultCheckoutBlockContext );

export const useCheckoutBlockContext = (): CheckoutBlockContextProps => {
	const context = useContext( CheckoutBlockContext );
	return {
		...defaultCheckoutBlockContext,
		...context,
	};
};
