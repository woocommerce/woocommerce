/**
 * External dependencies
 */
import { _n, sprintf } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import type { ReactElement } from 'react';
import { CartCheckoutCompatibilityNotice } from '@woocommerce/editor-components/compatibility-notices';

const MiniCartBlock = (): ReactElement => {
	const blockProps = useBlockProps( {
		className: 'wc-block-mini-cart',
	} );

	const productCount = 0;

	return (
		<div { ...blockProps }>
			<button className="wc-block-mini-cart__button">
				{ sprintf(
					/* translators: %d is the number of products in the cart. */
					_n(
						'%d product',
						'%d products',
						productCount,
						'woo-gutenberg-products-block'
					),
					productCount
				) }
			</button>
			<CartCheckoutCompatibilityNotice blockName="mini-cart" />
		</div>
	);
};

export default MiniCartBlock;
