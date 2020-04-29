/**
 * Internal dependencies
 */
import PaymentMethodIcon from './payment-method-icon';
import { getCommonIconProps } from './common-icons';
import { normalizeIconConfig } from './utils';
import './style.scss';

/**
 * For a given list of icons, render each as a list item, using common icons
 * where available.
 *
 * @param {Object} props Component props.
 * @param {Array} props.icons  Array of icons object configs or ids as strings.
 */
export const PaymentMethodIcons = ( { icons = [] } ) => {
	const iconConfigs = normalizeIconConfig( icons );

	if ( iconConfigs.length === 0 ) {
		return null;
	}

	return (
		<div className="wc-block-cart__payment-method-icons">
			{ iconConfigs.map( ( icon ) => {
				const iconProps = {
					...icon,
					...getCommonIconProps( icon.id ),
				};
				return (
					<PaymentMethodIcon
						key={ 'payment-method-icon-' + icon.id }
						{ ...iconProps }
					/>
				);
			} ) }
		</div>
	);
};

export default PaymentMethodIcons;
