/**
 * External dependencies
 */
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { Fragment } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ValidatedTextInputProps } from '../../../../../../packages/components/text-input/types';

const guestCheckoutNoticeId = 'wc-guest-checkout-notice';

const EmailField = ( {
	onChange,
	value,
	...props
}: ValidatedTextInputProps ): JSX.Element => {
	const allowGuestCheckout = getSetting( 'checkoutAllowsGuest', false );

	return (
		<Fragment>
			<ValidatedTextInput
				{ ...props }
				onChange={ onChange }
				value={ value }
			/>
			{ allowGuestCheckout ? (
				<p
					id={ guestCheckoutNoticeId }
					className="wc-block-checkout__guest-checkout-notice"
				>
					{ __(
						'You are currently checking out as a guest.',
						'woocommerce'
					) }
				</p>
			) : null }
		</Fragment>
	);
};

export { EmailField };
