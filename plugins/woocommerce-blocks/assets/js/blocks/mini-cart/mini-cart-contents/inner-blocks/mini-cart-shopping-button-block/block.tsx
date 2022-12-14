/**
 * External dependencies
 */
import { SHOP_URL } from '@woocommerce/block-settings';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { defaultStartShoppingButtonLabel } from './constants';

type MiniCartShoppingButtonBlockProps = {
	className: string;
	startShoppingButtonLabel: string;
};

const Block = ( {
	className,
	startShoppingButtonLabel,
}: MiniCartShoppingButtonBlockProps ): JSX.Element | null => {
	if ( ! SHOP_URL ) {
		return null;
	}

	return (
		<div
			className={ classNames(
				className,
				'wc-block-mini-cart__shopping-button'
			) }
		>
			<a href={ SHOP_URL }>
				{ startShoppingButtonLabel || defaultStartShoppingButtonLabel }
			</a>
		</div>
	);
};

export default Block;
