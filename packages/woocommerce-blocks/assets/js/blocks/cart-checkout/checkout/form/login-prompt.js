/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CHECKOUT_SHOW_LOGIN_REMINDER } from '@woocommerce/block-settings';
import { useCheckoutContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import { LOGIN_TO_CHECKOUT_URL } from '../utils';

const LoginPrompt = () => {
	const { customerId } = useCheckoutContext();

	if ( ! CHECKOUT_SHOW_LOGIN_REMINDER || customerId ) {
		return null;
	}

	return (
		<>
			{ __(
				'Already have an account? ',
				'woocommerce'
			) }
			<a href={ LOGIN_TO_CHECKOUT_URL }>
				{ __( 'Log in.', 'woocommerce' ) }
			</a>
		</>
	);
};

export default LoginPrompt;
