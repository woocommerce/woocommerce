/**
 * External dependencies
 */
import classnames from 'classnames';
import { Icon, bank, bill, card, checkPayment } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import './style.scss';

const namedIcons = {
	bank,
	bill,
	card,
	checkPayment,
};

/**
 * Exposed to payment methods for the label shown on checkout. Allows icons to be added as well as
 * text.
 *
 * @param {Object} props Component props.
 * @param {*} props.icon Show an icon beside the text if provided. Can be a string to use a named
 *                       icon, or an SVG element.
 * @param {string} props.text Text shown next to icon.
 */
export const PaymentMethodLabel = ( { icon = '', text = '' } ) => {
	const hasIcon = !! icon;
	const hasNamedIcon =
		hasIcon && typeof icon === 'string' && namedIcons[ icon ];
	const className = classnames( 'wc-block-cart__payment-method-label', {
		'wc-block-cart__payment-method-label--with-icon': hasIcon,
	} );

	return (
		<span className={ className }>
			{ hasNamedIcon ? <Icon srcElement={ namedIcons[ icon ] } /> : icon }
			{ text }
		</span>
	);
};

export default PaymentMethodLabel;
