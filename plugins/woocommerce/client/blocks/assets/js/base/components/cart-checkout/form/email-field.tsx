/**
 * External dependencies
 */
import {
	ValidatedTextInput,
	ValidatedTextInputHandle,
} from '@woocommerce/blocks-components';
import { Fragment, forwardRef } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { ValidatedTextInputProps } from '../../../../../../packages/components/text-input/types';

const guestCheckoutNoticeId = 'wc-guest-checkout-notice';

const EmailField = forwardRef(
	(
		{ onChange, value, ...props }: ValidatedTextInputProps,
		ref: React.Ref< ValidatedTextInputHandle >
	): JSX.Element => {
		const allowGuestCheckout = getSetting( 'checkoutAllowsGuest', false );
		const { customerId } = useSelect(
			( select ) => ( {
				customerId: select( checkoutStore ).getCustomerId(),
			} ),
			[]
		);

		return (
			<Fragment>
				<ValidatedTextInput
					{ ...props }
					ref={ ref }
					onChange={ onChange }
					value={ value }
				/>
				{ allowGuestCheckout && ! customerId ? (
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
	}
);

export { EmailField };
