/**
 * External dependencies
 */
import { useCheckoutContext } from '@woocommerce/base-context';

/**
 * Returns redirect url interface from checkout context.
 */
export const useCheckoutRedirectUrl = () => {
	const { redirectUrl, dispatchActions } = useCheckoutContext();

	return {
		redirectUrl,
		setRedirectUrl: dispatchActions.setRedirectUrl,
	};
};
