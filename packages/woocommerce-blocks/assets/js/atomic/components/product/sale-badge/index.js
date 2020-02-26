/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useProductLayoutContext } from '@woocommerce/base-context/product-layout-context';

const ProductSaleBadge = ( { className, product, align } ) => {
	const { layoutStyleClassPrefix } = useProductLayoutContext();
	const alignClass =
		typeof align === 'string'
			? `${ layoutStyleClassPrefix }__product-onsale--align${ align }`
			: '';

	if ( product && product.on_sale ) {
		return (
			<div
				className={ classnames(
					className,
					alignClass,
					`${ layoutStyleClassPrefix }__product-onsale`
				) }
			>
				{ __( 'Sale', 'woocommerce' ) }
			</div>
		);
	}

	return null;
};

export default ProductSaleBadge;
