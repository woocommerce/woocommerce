/**
 * External dependencies
 */
import classnames from 'classnames';

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
export const PaymentMethodIcons = ( { icons = [], align = 'center' } ) => {
	const iconConfigs = normalizeIconConfig( icons );

	if ( iconConfigs.length === 0 ) {
		return null;
	}

	const containerClass = classnames(
		'wc-block-components-payment-method-icons',
		{
			'wc-block-components-payment-method-icons--align-left':
				align === 'left',
			'wc-block-components-payment-method-icons--align-right':
				align === 'right',
		}
	);

	return (
		<div className={ containerClass }>
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
