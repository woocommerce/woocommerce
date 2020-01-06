/**
 * External dependencies
 */
import useCheckoutContext from '@woocommerce/base-context/checkout-context';

const useCheckoutRedirectUrls = () => {
	const {
		successRedirectUrl,
		setSuccessRedirectUrl,
		failureRedirectUrl,
		setFailureRedirectUrl,
	} = useCheckoutContext();

	return {
		successRedirectUrl,
		setSuccessRedirectUrl,
		failureRedirectUrl,
		setFailureRedirectUrl,
	};
};

export default useCheckoutRedirectUrls;
