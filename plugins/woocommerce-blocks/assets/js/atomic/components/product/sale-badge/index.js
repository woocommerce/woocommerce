/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

const ProductSaleBadge = ( { className, product, align } ) => {
	const alignClass =
		typeof align === 'string'
			? 'wc-block-grid__product-onsale--align' + align
			: '';

	if ( product && product.onsale ) {
		return (
			<div
				className={ classnames(
					className,
					alignClass,
					'wc-block-grid__product-onsale'
				) }
			>
				{ __( 'Sale', 'woo-gutenberg-products-block' ) }
			</div>
		);
	}

	return null;
};

export default ProductSaleBadge;
