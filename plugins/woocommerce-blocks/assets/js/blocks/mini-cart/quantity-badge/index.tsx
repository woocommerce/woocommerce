/**
 * External dependencies
 */
import { cartOutline, bag, bagAlt } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';
import { IconType, ColorItem } from '.././types';

interface Props {
	count: number;
	icon?: IconType;
	iconColor: ColorItem | { color: undefined };
	productCountColor: ColorItem | { color: undefined };
	productCountVisibility: string;
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

	function determineCount( count: number, visibility: string ) {
		switch ( visibility ) {
			case 'greater_than_zero':
				return count > 0 ? count : '';
			case 'always':
				return count;
			case 'never':
				return '';
			default:
				return count > 0 ? count : '';
		}
	}

	const displayCount = determineCount( count, productCountVisibility );

	return (
		<span className="wc-block-mini-cart__quantity-badge">
			<Icon
				className="wc-block-mini-cart__icon"
				color={ iconColor.color }
				size={ 20 }
				icon={ getIcon( icon ) }
			/>
			<span
				className="wc-block-mini-cart__badge"
				style={ { background: productCountColor.color } }
			>
				{ displayCount }
			</span>
		</span>
	);
};

export default QuantityBadge;
