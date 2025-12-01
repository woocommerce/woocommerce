/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { LOGIN_URL } from '@woocommerce/block-settings';
import { useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';

const LOGIN_TO_CHECKOUT_URL = `${ LOGIN_URL }?redirect_to=${ encodeURIComponent(
	window.location.href
) }`;

const LoginPrompt = () => {
	const customerId = useSelect( ( select ) =>
		select( checkoutStore ).getCustomerId()
	);

	if ( ! getSetting( 'checkoutShowLoginReminder', true ) || customerId ) {
		return null;
	}

	return (
		<a
			className="wc-block-checkout__login-prompt"
			href={ LOGIN_TO_CHECKOUT_URL }
		>
			{ __( 'Log in', 'woocommerce' ) }
		</a>
	);
};

export default LoginPrompt;
