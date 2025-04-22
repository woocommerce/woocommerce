/**
 * External dependencies
 */
import { SHOP_URL } from '@woocommerce/block-settings';
import Button from '@woocommerce/base-components/button';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { defaultStartShoppingButtonLabel } from './constants';
import { getVariant } from '../utils';

type MiniCartShoppingButtonBlockProps = {
	className: string;
	startShoppingButtonLabel: string;
	style?: Record< string, any >;
	textColor?: string;
	backgroundColor?: string;
};

const Block = ( {
	className,
	startShoppingButtonLabel,
	style,
	textColor,
	backgroundColor,
}: MiniCartShoppingButtonBlockProps ): JSX.Element | null => {
	if ( ! SHOP_URL ) {
		return null;
	}

	// Generate color classes based on attributes.
	const colorClasses = clsx( {
		[ `has-${ textColor }-color` ]: textColor,
		[ `has-${ backgroundColor }-background-color` ]: backgroundColor,
	} );

	const buttonStyles: Record< string, string > = {};

	const parsedStyle =
		typeof style === 'string' ? JSON.parse( style ) : style || {};
	if ( parsedStyle?.color?.text ) {
		buttonStyles.color = parsedStyle.color.text;
	}
	if ( parsedStyle?.color?.background ) {
		buttonStyles.backgroundColor = parsedStyle.color.background;
	}

	return (
		<div className="wp-block-button has-text-align-center">
			<Button
				className={ clsx(
					className,
					'wp-block-button__link',
					'wc-block-mini-cart__shopping-button',
					colorClasses
				) }
				style={
					Object.keys( buttonStyles ).length > 0
						? buttonStyles
						: undefined
				}
				variant={ getVariant( className, 'contained' ) }
				href={ SHOP_URL }
			>
				{ startShoppingButtonLabel || defaultStartShoppingButtonLabel }
			</Button>
		</div>
	);
};

export default Block;
