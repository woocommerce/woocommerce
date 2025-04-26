/**
 * External dependencies
 */
import { SHOP_URL } from '@woocommerce/block-settings';
import Button from '@woocommerce/base-components/button';
import { useStyleProps } from '@woocommerce/base-hooks';
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

	const styleProps = useStyleProps( {
		style,
		textColor,
		backgroundColor,
	} );

	return (
		<div className="wp-block-button has-text-align-center">
			<Button
				className={ clsx(
					className,
					styleProps.className,
					'wc-block-mini-cart__shopping-button'
				) }
				style={ styleProps.style }
				variant={ getVariant( className, 'contained' ) }
				href={ SHOP_URL }
			>
				{ startShoppingButtonLabel || defaultStartShoppingButtonLabel }
			</Button>
		</div>
	);
};

export default Block;
