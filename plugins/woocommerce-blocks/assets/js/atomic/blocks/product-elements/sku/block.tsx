/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import type { HTMLAttributes } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';
import type { BlockAttributes } from './types';

type Props = BlockAttributes & HTMLAttributes< HTMLDivElement >;

const Block = ( props: Props ): JSX.Element | null => {
	const { className } = props;
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();
	const sku = product.sku;

	if ( ! sku ) {
		return null;
	}

	return (
		<div
			className={ classnames(
				className,
				'wc-block-components-product-sku',
				{
					[ `${ parentClassName }__product-sku` ]: parentClassName,
				}
			) }
		>
			{ __( 'SKU:', 'woo-gutenberg-products-block' ) }{ ' ' }
			<strong>{ sku }</strong>
		</div>
	);
};

export default withProductDataContext( Block );
