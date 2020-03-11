/**
 * External dependencies
 */
import { useCheckoutContext } from '@woocommerce/base-context';

/**
 * Returns the submitLabel and onSubmit interface from the checkout context
 */
export const useCheckoutSubmit = () => {
	const { submitLabel, onSubmit } = useCheckoutContext();
	return { submitLabel, onSubmit };
};
