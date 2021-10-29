/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import type { ReactElement } from 'react';
import { formatPrice } from '@woocommerce/price-format';
import { CartCheckoutCompatibilityNotice } from '@woocommerce/editor-components/compatibility-notices';

/**
 * Internal dependencies
 */
import QuantityBadge from './quantity-badge';

const MiniCartBlock = (): ReactElement => {
	const blockProps = useBlockProps( {
		className: 'wc-block-mini-cart',
	} );

	const productCount = 0;
	const productTotal = 0;

	return (
		<div { ...blockProps }>
			<button className="wc-block-mini-cart__button">
				<span className="wc-block-mini-cart__amount">
					{ formatPrice( productTotal ) }
				</span>
				<QuantityBadge count={ productCount } />
			</button>
			<CartCheckoutCompatibilityNotice blockName="mini-cart" />
		</div>
	);
};

export default MiniCartBlock;
