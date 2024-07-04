/**
 * External dependencies
 */
import { cartOutline, bag, bagAlt } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';
import { IconType, ColorItem, productCountVisibilityType } from '.././types';

interface Props {
	count: number;
	icon?: IconType;
	iconColor: ColorItem | { color: undefined };
	productCountColor: ColorItem | { color: undefined };
	productCountVisibility?: productCountVisibilityType;
}

const QuantityBadge = ( {
	count,
	icon,
	iconColor,
	productCountColor,
	productCountVisibility,
}: Props ): JSX.Element => {
	function getIcon( iconName?: 'cart' | 'bag' | 'bag-alt' ) {
		switch ( iconName ) {
			case 'cart':
				return cartOutline;
			case 'bag':
				return bag;
			case 'bag-alt':
				return bagAlt;
			default:
				return cartOutline;
		}
	}

	const shouldDisplayCount =
		productCountVisibility === 'always' ||
		( productCountVisibility === 'greater_than_zero' && count > 0 );

	const displayCount = shouldDisplayCount ? count : '';

	return (
		<span className="wc-block-mini-cart__quantity-badge">
			<Icon
				className="wc-block-mini-cart__icon"
				color={ iconColor.color }
				size={ 20 }
				icon={ getIcon( icon ) }
			/>
			{ shouldDisplayCount && (
				<span
					className="wc-block-mini-cart__badge"
					style={ { background: productCountColor.color } }
				>
					{ displayCount }
				</span>
			) }
		</span>
	);
};

export default QuantityBadge;
